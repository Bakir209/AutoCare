<?php
class UserDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('users', ['name', 'email', 'password_hash', 'role'], 'users.id ASC');
    }

    public function getByEmail(string $email): ?array
    {
        return $this->queryOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    }

    /** True if another user already has this email ($exceptId = the user being edited). */
    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM users WHERE email = :email AND id <> :except",
            ['email' => $email, 'except' => $exceptId ?? 0]
        );
        return $row !== null;
    }
}
