<?php
declare(strict_types=1);

/**
 * Read env var with explicit fallback.
 * This keeps values like "0" intact (important for tests).
 */
function db_env(string $key, string $default, bool $allowEmpty = false): string
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    $value = (string)$value;
    if (!$allowEmpty && trim($value) === '') {
        return $default;
    }

    return $value;
}

return [
    'host' => db_env('DB_HOST', 'mysql-8.2'),
    'port' => db_env('DB_PORT', '3306'),
    'name' => db_env('DB_NAME', 'constructor_tests'),
    'user' => db_env('DB_USER', 'root'),
    'pass' => db_env('DB_PASS', '', true),
    'charset' => db_env('DB_CHARSET', 'utf8mb4'),
];

