<?php
declare(strict_types=1);

if (!function_exists('app_session_driver')) {
    function app_session_driver(): string
    {
        $driver = strtolower(trim((string)app_config('drivers.session', 'files')));
        if ($driver === 'redis' || $driver === 'files') {
            return $driver;
        }
        return 'files';
    }
}

if (!function_exists('app_session_redis_available')) {
    function app_session_redis_available(): bool
    {
        return class_exists('Redis');
    }
}

if (!function_exists('app_session_redis_save_path')) {
    function app_session_redis_save_path(): string
    {
        $host = (string)app_config('redis.host', '127.0.0.1');
        $port = (int)app_config('redis.port', 6379);
        $password = (string)app_config('redis.password', '');
        $db = max(0, (int)app_config('redis.db', 0));

        $query = [
            'database=' . $db,
            'prefix=sess:',
            'timeout=1',
            'read_timeout=1',
            'persistent=0',
        ];
        if ($password !== '') {
            $query[] = 'auth=' . rawurlencode($password);
        }

        return 'tcp://' . $host . ':' . $port . '?' . implode('&', $query);
    }
}

if (!function_exists('app_session_bootstrap')) {
    function app_session_bootstrap(): void
    {
        $driver = app_session_driver();
        if ($driver === 'redis' && app_session_redis_available()) {
            $savePath = app_session_redis_save_path();
            $okHandler = @ini_set('session.save_handler', 'redis');
            $okPath = @ini_set('session.save_path', $savePath);

            // Fallback to files when runtime refuses redis session handler/path.
            if ($okHandler === false || $okPath === false) {
                @ini_set('session.save_handler', 'files');
            }
        } else {
            @ini_set('session.save_handler', 'files');
        }
    }
}

if (!function_exists('app_session_start')) {
    function app_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        app_session_bootstrap();

        $started = @session_start();
        if ($started === true) {
            return;
        }

        // Runtime fallback: Redis can be configured but temporarily unavailable.
        @ini_set('session.save_handler', 'files');
        @session_start();
    }
}
