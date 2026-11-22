<?php
class VehicleDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct(
            'vehicles',
            ['user_id', 'make', 'model', 'year', 'vin', 'plate', 'current_mileage', 'fuel_type'],
            'vehicles.id ASC'
        );
    }

    public function getByUserId(int $userId): array
    {
        return $this->query(
            "SELECT * FROM vehicles WHERE user_id = :user_id ORDER BY id ASC",
            ['user_id' => $userId]
        );
    }

    public function vinExists(string $vin, ?int $exceptId = null): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM vehicles WHERE vin = :vin AND id <> :except",
            ['vin' => $vin, 'except' => $exceptId ?? 0]
        );
        return $row !== null;
    }

    /** Raises the stored mileage, never lowers it. */
    public function updateMileageIfHigher(int $id, int $mileage): void
    {
        $this->execute(
            "UPDATE vehicles SET current_mileage = :mileage WHERE id = :id AND current_mileage < :mileage_check",
            ['mileage' => $mileage, 'mileage_check' => $mileage, 'id' => $id]
        );
    }
}
