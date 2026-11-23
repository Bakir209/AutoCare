<?php
/**
 * Shared service logic: fetch with 404, validate, create, update, delete.
 * Services are the only layer that talks to the DAOs. Child services provide
 * the entity's validation and business rules in prepare().
 */
abstract class BaseService
{
    /** Columns that must never leave the API (e.g. password_hash). */
    protected array $hidden = [];

    /** Output types for columns PDO returns as strings: ['cost' => 'float']. */
    protected array $casts = [];

    public function __construct(protected BaseDao $dao, protected string $label)
    {
    }

    /**
     * Validate and normalise request data. $existing is the current row on update, null on create.
     * Throw ApiException for anything that breaks a rule.
     */
    abstract protected function prepare(array $data, ?array $existing): array;

    protected function present(array $row): array
    {
        foreach ($this->hidden as $column) {
            unset($row[$column]);
        }
        foreach ($this->casts as $column => $type) {
            if (array_key_exists($column, $row) && $row[$column] !== null) {
                $row[$column] = match ($type) {
                    'float' => (float)$row[$column],
                    'int'   => (int)$row[$column],
                    'bool'  => (bool)$row[$column],
                };
            }
        }
        return $row;
    }

    protected function presentAll(array $rows): array
    {
        return array_map(fn($row) => $this->present($row), $rows);
    }

    /** Raw row from the DAO or a 404. */
    protected function find(int $id): array
    {
        $row = $this->dao->getById($id);
        if ($row === null) {
            throw new ApiException(404, "{$this->label} not found.");
        }
        return $row;
    }

    public function getAll(): array
    {
        return $this->presentAll($this->dao->getAll());
    }

    public function getById(int $id): array
    {
        return $this->present($this->find($id));
    }

    public function create(array $data): array
    {
        $id = $this->dao->insert($this->prepare($data, null));
        return $this->getById($id);
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->find($id);
        $clean = $this->prepare($data, $existing);
        if (!$clean) {
            throw new ApiException(400, 'Nothing to update. Send at least one valid field.');
        }
        $this->dao->update($id, $clean);
        return $this->getById($id);
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->dao->delete($id);
    }
}
