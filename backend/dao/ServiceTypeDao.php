<?php
class ServiceTypeDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct(
            'service_types',
            ['name', 'description', 'default_interval_km', 'default_interval_months'],
            'service_types.name ASC'
        );
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM service_types WHERE name = :name AND id <> :except",
            ['name' => $name, 'except' => $exceptId ?? 0]
        );
        return $row !== null;
    }

    /** True if any service record or reminder still points at this type. */
    public function isInUse(int $id): bool
    {
        $row = $this->queryOne(
            "SELECT (SELECT COUNT(*) FROM service_records WHERE service_type_id = :a)
                  + (SELECT COUNT(*) FROM reminders WHERE service_type_id = :b) AS total",
            ['a' => $id, 'b' => $id]
        );
        return (int)$row['total'] > 0;
    }
}
