<?php
class UserService extends BaseService
{
    protected array $hidden = ['password_hash'];

    public function __construct()
    {
        parent::__construct(new UserDao(), 'User');
    }

    protected function prepare(array $data, ?array $existing): array
    {
        $clean = Validator::validate($data, [
            'name'     => ['type' => 'string', 'required' => true, 'min' => 2, 'max' => 100],
            'email'    => ['type' => 'email',  'required' => true],
            'password' => ['type' => 'string', 'required' => true, 'min' => 8, 'max' => 72],   // bcrypt reads 72 bytes
            'role'     => ['type' => 'enum', 'values' => ['admin', 'user'], 'default' => 'user'],
        ], $existing !== null);

        if (isset($clean['email']) && $this->dao->emailExists($clean['email'], $existing['id'] ?? null)) {
            throw new ApiException(409, 'This email is already registered.', ['email' => 'Already in use.']);
        }

        if (isset($clean['password'])) {
            $clean['password_hash'] = password_hash($clean['password'], PASSWORD_DEFAULT);
            unset($clean['password']);
        }
        return $clean;
    }
}
