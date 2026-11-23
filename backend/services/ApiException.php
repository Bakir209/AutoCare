<?php
/**
 * An error that should be shown to the API client with a specific HTTP status.
 * Throw it from services; the error handler in index.php turns it into JSON.
 */
class ApiException extends RuntimeException
{
    public function __construct(private int $status, string $message, private array $errors = [])
    {
        parent::__construct($message);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /** Field-level messages, e.g. ['email' => 'Enter a valid email address.'] */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
