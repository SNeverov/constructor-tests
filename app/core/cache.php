<?php
declare(strict_types=1);

if (!function_exists('cache_driver')) {
    function cache_driver(): string
    {
        $driver = strtolower(trim((string)app_config('drivers.cache', 'file')));
        if ($driver === 'redis' || $driver === 'file') {
            return $driver;
        }
        return 'file';
    }
}

if (!function_exists('cache_file_dir')) {
    function cache_file_dir(): string
    {
        $dir = __DIR__ . '/../../storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }
}

if (!function_exists('cache_file_path')) {
    function cache_file_path(string $key): string
    {
        return cache_file_dir() . '/' . hash('sha256', $key) . '.cache';
    }
}

if (!function_exists('cache_redis_client')) {
    function cache_redis_client(): ?Redis
    {
        static $client = null;
        static $attempted = false;

        if ($client instanceof Redis) {
            return $client;
        }
        if ($attempted) {
            return null;
        }
        $attempted = true;

        if (!class_exists('Redis')) {
            return null;
        }

        $host = (string)app_config('redis.host', '127.0.0.1');
        $port = (int)app_config('redis.port', 6379);
        $password = (string)app_config('redis.password', '');
        $db = (int)app_config('redis.db', 0);

        try {
            $redis = new Redis();
            $ok = $redis->connect($host, $port, 0.2);
            if ($ok !== true) {
                return null;
            }

            if ($password !== '') {
                if ($redis->auth($password) !== true) {
                    return null;
                }
            }

            if ($db > 0) {
                if ($redis->select($db) !== true) {
                    return null;
                }
            }

            $client = $redis;
            return $client;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('cache_redis_key')) {
    function cache_redis_key(string $key): string
    {
        return 'cache:v1:' . hash('sha256', $key);
    }
}

if (!function_exists('cache_get')) {
    function cache_get(string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $default;
        }

        if (cache_driver() === 'redis') {
            $redis = cache_redis_client();
            if ($redis instanceof Redis) {
                try {
                    $raw = $redis->get(cache_redis_key($key));
                    if (!is_string($raw) || $raw === '') {
                        return $default;
                    }
                    $decoded = @unserialize($raw, ['allowed_classes' => false]);
                    return $decoded === false && $raw !== serialize(false) ? $default : $decoded;
                } catch (Throwable $e) {
                    // fall through to file
                }
            }
        }

        $path = cache_file_path($key);
        if (!is_file($path)) {
            return $default;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $default;
        }

        $expiresAt = (int)($decoded['expires_at'] ?? 0);
        if ($expiresAt <= time()) {
            @unlink($path);
            return $default;
        }

        $payload = (string)($decoded['payload'] ?? '');
        if ($payload === '') {
            return $default;
        }

        $serialized = base64_decode($payload, true);
        if (!is_string($serialized)) {
            return $default;
        }

        $value = @unserialize($serialized, ['allowed_classes' => false]);
        return $value === false && $serialized !== serialize(false) ? $default : $value;
    }
}

if (!function_exists('cache_put')) {
    function cache_put(string $key, mixed $value, int $ttlSec): bool
    {
        if ($key === '' || $ttlSec <= 0) {
            return false;
        }

        if (cache_driver() === 'redis') {
            $redis = cache_redis_client();
            if ($redis instanceof Redis) {
                try {
                    return $redis->setex(cache_redis_key($key), $ttlSec, serialize($value));
                } catch (Throwable $e) {
                    // fall through to file
                }
            }
        }

        $path = cache_file_path($key);
        $data = [
            'expires_at' => time() + $ttlSec,
            'payload' => base64_encode(serialize($value)),
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return false;
        }

        return @file_put_contents($path, $json, LOCK_EX) !== false;
    }
}

if (!function_exists('cache_forget')) {
    function cache_forget(string $key): void
    {
        if ($key === '') {
            return;
        }

        if (cache_driver() === 'redis') {
            $redis = cache_redis_client();
            if ($redis instanceof Redis) {
                try {
                    $redis->del(cache_redis_key($key));
                } catch (Throwable $e) {
                    // ignore and continue with file cleanup
                }
            }
        }

        $path = cache_file_path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
