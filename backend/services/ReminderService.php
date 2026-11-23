<?php
class ReminderService extends BaseService
{
    private VehicleDao $vehicles;
    private ServiceTypeDao $types;

    public function __construct()
    {
        parent::__construct(new ReminderDao(), 'Reminder');
        $this->vehicles = new VehicleDao();
        $this->types = new ServiceTypeDao();
    }

    protected function prepare(array $data, ?array $existing): array
    {
        $clean = Validator::validate($data, [
            'vehicle_id'      => ['type' => 'int', 'required' => true, 'min' => 1],
            'service_type_id' => ['type' => 'int', 'required' => true, 'min' => 1],
            'due_date'        => ['type' => 'date'],
            'due_mileage'     => ['type' => 'int', 'min' => 0, 'max' => 5000000],
            'status'          => ['type' => 'enum', 'values' => ['pending', 'done', 'overdue'], 'default' => 'pending'],
        ], $existing !== null);

        if (isset($clean['vehicle_id']) && $this->vehicles->getById($clean['vehicle_id']) === null) {
            throw new ApiException(400, 'Validation failed.', ['vehicle_id' => 'Vehicle does not exist.']);
        }
        if (isset($clean['service_type_id']) && $this->types->getById($clean['service_type_id']) === null) {
            throw new ApiException(400, 'Validation failed.', ['service_type_id' => 'Service type does not exist.']);
        }

        // Business rule: a reminder needs a due date, a due mileage, or both.
        $dueDate = array_key_exists('due_date', $clean) ? $clean['due_date'] : ($existing['due_date'] ?? null);
        $dueMileage = array_key_exists('due_mileage', $clean) ? $clean['due_mileage'] : ($existing['due_mileage'] ?? null);
        if ($dueDate === null && $dueMileage === null) {
            throw new ApiException(400, 'Validation failed.', ['due_date' => 'Set a due date, a due mileage, or both.']);
        }
        return $clean;
    }

    // Business rule: pending reminders that are past due become "overdue" whenever they are read.
    public function getAll(): array
    {
        $this->dao->markOverdue();
        return parent::getAll();
    }

    public function getById(int $id): array
    {
        $this->dao->markOverdue();
        return parent::getById($id);
    }

    public function getByVehicle(int $vehicleId): array
    {
        if ($this->vehicles->getById($vehicleId) === null) {
            throw new ApiException(404, 'Vehicle not found.');
        }
        $this->dao->markOverdue();
        return $this->presentAll($this->dao->getByVehicleId($vehicleId));
    }
}
