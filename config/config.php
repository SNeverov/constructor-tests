<?php
declare(strict_types=1);

/**
 * Read env var with explicit fallback.
 * Keeps values like "0" and empty password intact when needed.
 */
function app_env(string $key, string $default, bool $allowEmpty = false): string
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
    'drivers' => [
        'rate_limit' => app_env('RATE_LIMIT_DRIVER', 'file'),
        'cache' => app_env('CACHE_DRIVER', 'file'),
        'session' => app_env('SESSION_DRIVER', 'files'),
    ],
    'redis' => [
        'host' => app_env('REDIS_HOST', '127.0.0.1'),
        'port' => (int)app_env('REDIS_PORT', '6379'),
        'password' => app_env('REDIS_PASSWORD', '', true),
        'db' => (int)app_env('REDIS_DB', '0'),
    ],
    'cache' => [
        'test_payload_ttl_sec' => 300,
    ],
];
