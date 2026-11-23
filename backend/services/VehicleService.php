<?php
class VehicleService extends BaseService
{
    private UserDao $users;

    public function __construct()
    {
        parent::__construct(new VehicleDao(), 'Vehicle');
        $this->users = new UserDao();
    }

    protected function prepare(array $data, ?array $existing): array
    {
        $clean = Validator::validate($data, [
            'user_id'         => ['type' => 'int', 'required' => true, 'min' => 1],
            'make'            => ['type' => 'string', 'required' => true, 'min' => 1, 'max' => 50],
            'model'           => ['type' => 'string', 'required' => true, 'min' => 1, 'max' => 50],
            'year'            => ['type' => 'int', 'required' => true, 'min' => 1950, 'max' => (int)date('Y') + 1],
            'vin'             => ['type' => 'string', 'required' => true, 'uppercase' => true,
                                  'pattern' => '/^[A-HJ-NPR-Z0-9]{17}$/',
                                  'message' => 'A VIN has exactly 17 letters and digits (no I, O or Q).'],
            'plate'           => ['type' => 'string', 'required' => true, 'min' => 1, 'max' => 20],
            'current_mileage' => ['type' => 'int', 'min' => 0, 'max' => 5000000, 'default' => 0],
            'fuel_type'       => ['type' => 'enum', 'required' => true,
                                  'values' => ['petrol', 'diesel', 'hybrid', 'electric', 'lpg']],
        ], $existing !== null);

        if (isset($clean['user_id']) && $this->users->getById($clean['user_id']) === null) {
            throw new ApiException(400, 'Validation failed.', ['user_id' => 'User does not exist.']);
        }
        if (isset($clean['vin']) && $this->dao->vinExists($clean['vin'], $existing['id'] ?? null)) {
            throw new ApiException(409, 'A vehicle with this VIN already exists.', ['vin' => 'Already in use.']);
        }
        // Business rule: the odometer only goes up.
        if ($existing !== null && isset($clean['current_mileage']) && $clean['current_mileage'] < $existing['current_mileage']) {
            throw new ApiException(400, 'Validation failed.', ['current_mileage' => 'Cannot be lower than the current value.']);
        }
        return $clean;
    }

    public function getByUser(int $userId): array
    {
        if ($this->users->getById($userId) === null) {
            throw new ApiException(404, 'User not found.');
        }
        return $this->presentAll($this->dao->getByUserId($userId));
    }
}
