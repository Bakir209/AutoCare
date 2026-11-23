<?php
/**
 * Quick check that every DAO can create, read, update and delete.
 * Run from the project root:  php backend/tests/dao_smoke_test.php
 * Everything happens inside a transaction that is rolled back,
 * so your data is not changed.
 */
spl_autoload_register(function ($class) {
    foreach (['config', 'dao'] as $dir) {
        $file = __DIR__ . "/../$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});

$db = Database::connect();
$db->beginTransaction();

function check(string $label, bool $ok): void {
    echo ($ok ? "PASS " : "FAIL ") . $label . PHP_EOL;
    if (!$ok) { $GLOBALS['failed'] = true; }
}

try {
    $users = new UserDao(); $vehicles = new VehicleDao(); $types = new ServiceTypeDao();
    $records = new ServiceRecordDao(); $fuel = new FuelLogDao(); $reminders = new ReminderDao();

    $userId = $users->insert(['name' => 'Test User', 'email' => 'smoke@test.local', 'password_hash' => 'x', 'role' => 'user']);
    check('users: insert + getById', $users->getById($userId)['email'] === 'smoke@test.local');
    check('users: update', $users->update($userId, ['name' => 'Renamed']) && $users->getById($userId)['name'] === 'Renamed');

    $vehicleId = $vehicles->insert(['user_id' => $userId, 'make' => 'Test', 'model' => 'Car', 'year' => 2020,
        'vin' => 'SMOKETEST0000001A', 'plate' => 'T-1', 'current_mileage' => 1000, 'fuel_type' => 'petrol']);
    check('vehicles: insert + getByUserId', count($vehicles->getByUserId($userId)) === 1);

    $typeId = $types->insert(['name' => 'Smoke type', 'default_interval_km' => 5000]);
    check('service_types: insert', $types->getById($typeId)['name'] === 'Smoke type');

    $recordId = $records->insert(['vehicle_id' => $vehicleId, 'service_type_id' => $typeId, 'service_date' => '2026-01-01',
        'mileage' => 1200, 'cost' => 50.5, 'is_repair' => true]);
    check('service_records: insert + join', $records->getById($recordId)['service_type_name'] === 'Smoke type');

    $fuelId = $fuel->insert(['vehicle_id' => $vehicleId, 'log_date' => '2026-01-02', 'liters' => 40,
        'price_per_liter' => 1.9, 'total_cost' => 76, 'mileage' => 1300]);
    check('fuel_logs: insert + getByVehicleId', count($fuel->getByVehicleId($vehicleId)) === 1);

    $reminderId = $reminders->insert(['vehicle_id' => $vehicleId, 'service_type_id' => $typeId, 'due_date' => '2020-01-01']);
    $reminders->markOverdue();
    check('reminders: markOverdue', $reminders->getById($reminderId)['status'] === 'overdue');

    check('service_types: isInUse', $types->isInUse($typeId) === true);
    check('vehicles: updateMileageIfHigher', (function () use ($vehicles, $vehicleId) {
        $vehicles->updateMileageIfHigher($vehicleId, 900);   // lower, ignored
        $vehicles->updateMileageIfHigher($vehicleId, 2000);  // higher, applied
        return (int)$vehicles->getById($vehicleId)['current_mileage'] === 2000;
    })());

    check('users: delete cascades to vehicles', $users->delete($userId) && $vehicles->getById($vehicleId) === null);
    check('service_types: delete', $types->delete($typeId) && $types->getById($typeId) === null);
} finally {
    $db->rollBack();
}

echo isset($failed) ? "\nSome checks failed.\n" : "\nAll checks passed (changes rolled back).\n";
exit(isset($failed) ? 1 : 0);
