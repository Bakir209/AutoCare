<?php
class ServiceTypeService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new ServiceTypeDao(), 'Service type');
    }

    protected function prepare(array $data, ?array $existing): array
    {
        $clean = Validator::validate($data, [
            'name'                    => ['type' => 'string', 'required' => true, 'min' => 2, 'max' => 100],
            'description'             => ['type' => 'string', 'max' => 1000],
            'default_interval_km'     => ['type' => 'int', 'min' => 1, 'max' => 1000000],
            'default_interval_months' => ['type' => 'int', 'min' => 1, 'max' => 240],
        ], $existing !== null);

        if (isset($clean['name']) && $this->dao->nameExists($clean['name'], $existing['id'] ?? null)) {
            throw new ApiException(409, 'A service type with this name already exists.', ['name' => 'Already in use.']);
        }
        return $clean;
    }

    /** Business rule: a type that service records or reminders still use cannot be deleted. */
    public function delete(int $id): void
    {
        $this->find($id);
        if ($this->dao->isInUse($id)) {
            throw new ApiException(409, 'This service type is used by service records or reminders and cannot be deleted.');
        }
        $this->dao->delete($id);
    }
}
