<?php
/**
 * Small rule-based validator used by the services.
 *
 * Rule keys: type (string|email|int|float|date|bool|enum), required, default,
 * min, max (length for strings, value for numbers), pattern, uppercase,
 * decimals, not_future, values (for enum).
 *
 * Only fields listed in $rules are returned, so unknown fields in the request are ignored.
 * With $partial = true (updates) missing fields are skipped instead of being required.
 */
class Validator
{
    public static function validate(array $data, array $rules, bool $partial = false): array
    {
        $clean = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            $required = $rule['required'] ?? false;

            if (!array_key_exists($field, $data)) {
                if (!$partial) {
                    if ($required) {
                        $errors[$field] = 'This field is required.';
                    } elseif (array_key_exists('default', $rule)) {
                        $clean[$field] = $rule['default'];
                    }
                }
                continue;
            }

            $value = $data[$field];
            if ($value === null || (is_string($value) && trim($value) === '')) {
                if ($required) {
                    $errors[$field] = 'This field is required.';
                } elseif (array_key_exists('default', $rule)) {
                    $clean[$field] = $rule['default'];
                } else {
                    $clean[$field] = null;
                }
                continue;
            }

            $error = null;
            $normalised = self::check($value, $rule, $error);
            if ($error !== null) {
                $errors[$field] = $error;
            } else {
                $clean[$field] = $normalised;
            }
        }

        if ($errors) {
            throw new ApiException(400, 'Validation failed.', $errors);
        }
        return $clean;
    }

    private static function check(mixed $value, array $rule, ?string &$error): mixed
    {
        switch ($rule['type']) {
            case 'string':
            case 'email':
                if (!is_string($value)) { $error = 'Must be text.'; return null; }
                $value = trim($value);
                if (!empty($rule['uppercase'])) { $value = strtoupper($value); }
                if ($rule['type'] === 'email') {
                    $value = strtolower($value);
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) { $error = 'Enter a valid email address.'; return null; }
                }
                $length = mb_strlen($value);
                $max = $rule['max'] ?? ($rule['type'] === 'email' ? 150 : null);
                if (isset($rule['min']) && $length < $rule['min']) { $error = "Must be at least {$rule['min']} characters."; return null; }
                if ($max !== null && $length > $max) { $error = "Must be at most $max characters."; return null; }
                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $error = $rule['message'] ?? 'Invalid format.';
                    return null;
                }
                return $value;

            case 'int':
                if (is_string($value) && preg_match('/^-?\d+$/', $value)) { $value = (int)$value; }
                elseif (is_float($value) && floor($value) === $value) { $value = (int)$value; }
                if (!is_int($value)) { $error = 'Must be a whole number.'; return null; }
                return self::checkRange($value, $rule, $error);

            case 'float':
                if (is_bool($value) || !is_numeric($value)) { $error = 'Must be a number.'; return null; }
                $value = (float)$value;
                if (isset($rule['decimals'])) { $value = round($value, $rule['decimals']); }
                return self::checkRange($value, $rule, $error);

            case 'date':
                $date = is_string($value) ? DateTime::createFromFormat('Y-m-d', $value) : false;
                if (!$date || $date->format('Y-m-d') !== $value) { $error = 'Use the format YYYY-MM-DD.'; return null; }
                if (!empty($rule['not_future']) && $value > date('Y-m-d')) { $error = 'Date cannot be in the future.'; return null; }
                return $value;

            case 'bool':
                $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool === null) { $error = 'Must be true or false.'; return null; }
                return $bool;

            case 'enum':
                if (!in_array($value, $rule['values'], true)) {
                    $error = 'Must be one of: ' . implode(', ', $rule['values']) . '.';
                    return null;
                }
                return $value;
        }

        throw new LogicException("Unknown rule type {$rule['type']}");
    }

    private static function checkRange(int|float $value, array $rule, ?string &$error): int|float|null
    {
        if (isset($rule['min']) && $value < $rule['min']) { $error = "Must be at least {$rule['min']}."; return null; }
        if (isset($rule['max']) && $value > $rule['max']) { $error = "Must be at most {$rule['max']}."; return null; }
        return $value;
    }
}
