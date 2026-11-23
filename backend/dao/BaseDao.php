<?php
/**
 * Reusable CRUD for one table. Child classes only say which table and which
 * columns may be written, and add their own queries.
 *
 * Safety: values are always bound with prepared statements. Column names in
 * INSERT/UPDATE come from the child's $fillable whitelist, never from user input.
 */
abstract class BaseDao
{
    protected PDO $db;

    public function __construct(
        protected string $table,
        protected array $fillable,
        protected string $orderBy = 'id DESC'   // defined in code, never from the request
    ) {
        $this->db = Database::connect();
    }

    /** The SELECT used by getAll/getById. Children override it to add JOINs. */
    protected function baseSelect(): string
    {
        return "SELECT * FROM {$this->table}";
    }

    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function queryOne(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)[0] ?? null;
    }

    /** Runs INSERT/UPDATE/DELETE and returns the number of affected rows. */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Keeps only whitelisted columns and turns booleans into 0/1. */
    private function onlyFillable(array $data): array
    {
        $clean = [];
        foreach ($this->fillable as $column) {
            if (array_key_exists($column, $data)) {
                $value = $data[$column];
                $clean[$column] = is_bool($value) ? (int)$value : $value;
            }
        }
        return $clean;
    }

    public function getAll(): array
    {
        return $this->query($this->baseSelect() . " ORDER BY {$this->orderBy}");
    }

    public function getById(int $id): ?array
    {
        return $this->queryOne($this->baseSelect() . " WHERE {$this->table}.id = :id", ['id' => $id]);
    }

    /** Inserts a row and returns the new id. */
    public function insert(array $data): int
    {
        $data = $this->onlyFillable($data);
        if (!$data) {
            throw new InvalidArgumentException('No valid columns to insert.');
        }
        $columns = array_keys($data);
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ")"
             . " VALUES (:" . implode(', :', $columns) . ")";
        $this->execute($sql, $data);
        return (int)$this->db->lastInsertId();
    }

    /** Updates the given columns. Returns false if the row does not exist. */
    public function update(int $id, array $data): bool
    {
        $data = $this->onlyFillable($data);
        if (!$data) {
            return false;
        }
        $set = implode(', ', array_map(fn($column) => "$column = :$column", array_keys($data)));
        $data['id'] = $id;
        return $this->execute("UPDATE {$this->table} SET $set WHERE id = :id", $data) > 0;
    }

    /** Deletes a row. Returns false if it did not exist. */
    public function delete(int $id): bool
    {
        return $this->execute("DELETE FROM {$this->table} WHERE id = :id", ['id' => $id]) > 0;
    }
}
