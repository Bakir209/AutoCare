<?php
class FuelLogDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct(
            'fuel_logs',
            ['vehicle_id', 'log_date', 'liters', 'price_per_liter', 'total_cost', 'mileage'],
            'fuel_logs.log_date DESC, fuel_logs.id DESC'
        );
    }

    protected function baseSelect(): string
    {
        return "SELECT fuel_logs.*, CONCAT(vehicles.make, ' ', vehicles.model) AS vehicle_label
                FROM fuel_logs
                JOIN vehicles ON vehicles.id = fuel_logs.vehicle_id";
    }

    public function getByVehicleId(int $vehicleId): array
    {
        return $this->query(
            $this->baseSelect() . " WHERE fuel_logs.vehicle_id = :vehicle_id ORDER BY {$this->orderBy}",
            ['vehicle_id' => $vehicleId]
        );
    }
}
