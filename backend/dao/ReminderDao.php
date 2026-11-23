<?php
class ReminderDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct(
            'reminders',
            ['vehicle_id', 'service_type_id', 'due_date', 'due_mileage', 'status'],
            'reminders.due_date IS NULL, reminders.due_date ASC, reminders.id ASC'
        );
    }

    protected function baseSelect(): string
    {
        return "SELECT reminders.*,
                       service_types.name AS service_type_name,
                       CONCAT(vehicles.make, ' ', vehicles.model) AS vehicle_label
                FROM reminders
                JOIN service_types ON service_types.id = reminders.service_type_id
                JOIN vehicles ON vehicles.id = reminders.vehicle_id";
    }

    public function getByVehicleId(int $vehicleId): array
    {
        return $this->query(
            $this->baseSelect() . " WHERE reminders.vehicle_id = :vehicle_id ORDER BY {$this->orderBy}",
            ['vehicle_id' => $vehicleId]
        );
    }

    /** Pending reminders whose date has passed or whose mileage has been reached become overdue. */
    public function markOverdue(): int
    {
        return $this->execute(
            "UPDATE reminders
             JOIN vehicles ON vehicles.id = reminders.vehicle_id
             SET reminders.status = 'overdue'
             WHERE reminders.status = 'pending'
               AND ((reminders.due_date IS NOT NULL AND reminders.due_date < CURDATE())
                 OR (reminders.due_mileage IS NOT NULL AND reminders.due_mileage <= vehicles.current_mileage))"
        );
    }
}
