<?php
class FuelLogService extends BaseService
{
    protected array $casts = ['liters' => 'float', 'price_per_liter' => 'float', 'total_cost' => 'float'];

    private VehicleDao $vehicles;

    public function __construct()
    {
        parent::__construct(new FuelLogDao(), 'Fuel log');
        $this->vehicles = new VehicleDao();
    }

    protected function prepare(array $data, ?array $existing): array
    {
        $clean = Validator::validate($data, [
            'vehicle_id'      => ['type' => 'int', 'required' => true, 'min' => 1],
            'log_date'        => ['type' => 'date', 'required' => true, 'not_future' => true],
            'liters'          => ['type' => 'float', 'required' => true, 'min' => 0.01, 'max' => 1000, 'decimals' => 2],
            'price_per_liter' => ['type' => 'float', 'required' => true, 'min' => 0.001, 'max' => 100, 'decimals' => 3],
            'mileage'         => ['type' => 'int', 'required' => true, 'min' => 0, 'max' => 5000000],
        ], $existing !== null);

        if (isset($clean['vehicle_id']) && $this->vehicles->getById($clean['vehicle_id']) === null) {
            throw new ApiException(400, 'Validation failed.', ['vehicle_id' => 'Vehicle does not exist.']);
        }

        // Business rule: the total is always calculated by the server, never trusted from the client.
        if (isset($clean['liters']) || isset($clean['price_per_liter'])) {
            $liters = $clean['liters'] ?? (float)$existing['liters'];
            $price = $clean['price_per_liter'] ?? (float)$existing['price_per_liter'];
            $clean['total_cost'] = round($liters * $price, 2);
        }
        return $clean;
    }

    public function create(array $data): array
    {
        $log = parent::create($data);
        $this->vehicles->updateMileageIfHigher($log['vehicle_id'], $log['mileage']);
        return $log;
    }

    public function update(int $id, array $data): array
    {
        $log = parent::update($id, $data);
        $this->vehicles->updateMileageIfHigher($log['vehicle_id'], $log['mileage']);
        return $log;
    }

    public function getByVehicle(int $vehicleId): array
    {
        if ($this->vehicles->getById($vehicleId) === null) {
            throw new ApiException(404, 'Vehicle not found.');
        }
        return $this->presentAll($this->dao->getByVehicleId($vehicleId));
    }
}
