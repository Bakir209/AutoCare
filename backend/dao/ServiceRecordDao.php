<?php
class ServiceRecordDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct(
            'service_records',
            ['vehicle_id', 'service_type_id', 'service_date', 'mileage', 'cost', 'workshop', 'notes', 'is_repair'],
            'service_records.service_date DESC, service_records.id DESC'
        );
    }

    /** Adds the service type name and a readable vehicle label to every row. */
    protected function baseSelect(): string
    {
        return "SELECT service_records.*,
                       service_types.name AS service_type_name,
                       CONCAT(vehicles.make, ' ', vehicles.model) AS vehicle_label
                FROM service_records
                JOIN service_types ON service_types.id = service_records.service_type_id
                JOIN vehicles ON vehicles.id = service_records.vehicle_id";
    }

    public function getByVehicleId(int $vehicleId): array
    {
        return $this->query(
            $this->baseSelect() . " WHERE service_records.vehicle_id = :vehicle_id ORDER BY {$this->orderBy}",
            ['vehicle_id' => $vehicleId]
        );
    }
}
