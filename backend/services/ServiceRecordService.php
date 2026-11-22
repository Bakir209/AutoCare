<?php
class ServiceRecordService extends BaseService
{
    protected array $casts = ['cost' => 'float', 'is_repair' => 'bool'];

    private VehicleDao $vehicles;
    private ServiceTypeDao $types;

    public function __construct()
    {
        parent::__construct(new ServiceRecordDao(), 'Service record');
        $this->vehicles = new VehicleDao();
        $this->types = new ServiceTypeDao();
    }

    protected function prepare(array $data, ?array $existing): array
    {
        $clean = Validator::validate($data, [
            'vehicle_id'      => ['type' => 'int', 'required' => true, 'min' => 1],
            'service_type_id' => ['type' => 'int', 'required' => true, 'min' => 1],
            'service_date'    => ['type' => 'date', 'required' => true, 'not_future' => true],
            'mileage'         => ['type' => 'int', 'required' => true, 'min' => 0, 'max' => 5000000],
            'cost'            => ['type' => 'float', 'min' => 0, 'max' => 10000000, 'decimals' => 2, 'default' => 0],
            'workshop'        => ['type' => 'string', 'max' => 100],
            'notes'           => ['type' => 'string', 'max' => 2000],
            'is_repair'       => ['type' => 'bool', 'default' => false],
        ], $existing !== null);

        if (isset($clean['vehicle_id']) && $this->vehicles->getById($clean['vehicle_id']) === null) {
            throw new ApiException(400, 'Validation failed.', ['vehicle_id' => 'Vehicle does not exist.']);
        }
        if (isset($clean['service_type_id']) && $this->types->getById($clean['service_type_id']) === null) {
            throw new ApiException(400, 'Validation failed.', ['service_type_id' => 'Service type does not exist.']);
        }
        return $clean;
    }

    public function create(array $data): array
    {
        $record = parent::create($data);
        // Business rule: logging a service at a higher mileage updates the vehicle's odometer.
        $this->vehicles->updateMileageIfHigher($record['vehicle_id'], $record['mileage']);
        return $record;
    }

    public function update(int $id, array $data): array
    {
        $record = parent::update($id, $data);
        $this->vehicles->updateMileageIfHigher($record['vehicle_id'], $record['mileage']);
        return $record;
    }

    public function getByVehicle(int $vehicleId): array
    {
        if ($this->vehicles->getById($vehicleId) === null) {
            throw new ApiException(404, 'Vehicle not found.');
        }
        return $this->presentAll($this->dao->getByVehicleId($vehicleId));
    }
}
