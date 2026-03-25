<?php
declare(strict_types=1);

if (!function_exists('rate_limit_driver')) {
    function rate_limit_driver(): string
    {
        $driver = (string)app_config('drivers.rate_limit', 'file');
        $driver = strtolower(trim($driver));

        if ($driver === 'redis' || $driver === 'file') {
            return $driver;
        }

        return 'file';
    }
}

if (!function_exists('rate_limit_redis_available')) {
    function rate_limit_redis_available(): bool
    {
        return class_exists('Redis');
    }
}

if (!function_exists('rate_limit_redis_key')) {
    function rate_limit_redis_key(string $bucket): string
    {
        return 'rl:v1:' . hash('sha256', $bucket);
    }
}

if (!function_exists('rate_limit_redis_client')) {
    function rate_limit_redis_client(): ?Redis
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

        if (!rate_limit_redis_available()) {
            return null;
        }

        $host = (string)app_config('redis.host', '127.0.0.1');
        $port = (int)app_config('redis.port', 6379);
        $password = (string)app_config('redis.password', '');
        $db = (int)app_config('redis.db', 0);

        try {
            $redis = new Redis();
            // Short timeout to avoid slowing requests when Redis is unavailable.
            $ok = $redis->connect($host, $port, 0.2);
            if ($ok !== true) {
                return null;
            }

            if ($password !== '') {
                $authOk = $redis->auth($password);
                if ($authOk !== true) {
                    return null;
                }
            }

            if ($db > 0) {
                $selectOk = $redis->select($db);
                if ($selectOk !== true) {
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

if (!function_exists('rate_limit_redis_consume')) {
    function rate_limit_redis_consume(string $bucket, int $limit, int $windowSec): ?bool
    {
        if ($limit <= 0 || $windowSec <= 0) {
            return true;
        }

        $redis = rate_limit_redis_client();
        if (!$redis instanceof Redis) {
            return null;
        }

        $key = rate_limit_redis_key($bucket);
        $now = time();
        $minScoreToKeep = $now - $windowSec;
        $removeTo = (string)($minScoreToKeep - 1);
        $ttl = $windowSec + 1;

        try {
            $member = (string)$now . '-' . bin2hex(random_bytes(8));
            $lua = <<<'LUA'
local key = KEYS[1]
local remove_to = ARGV[1]
local now = ARGV[2]
local member = ARGV[3]
local limit = tonumber(ARGV[4])
local ttl = tonumber(ARGV[5])

redis.call('ZREMRANGEBYSCORE', key, '-inf', remove_to)
local current = redis.call('ZCARD', key)
if current >= limit then
  redis.call('EXPIRE', key, ttl)
  return 0
end

redis.call('ZADD', key, now, member)
redis.call('EXPIRE', key, ttl)
return 1
LUA;

            $result = $redis->eval(
                $lua,
                [$key, $removeTo, (string)$now, $member, (string)$limit, (string)$ttl],
                1
            );

            if ((int)$result === 1) {
                return true;
            }
            if ((int)$result === 0) {
                return false;
            }

            return null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('rate_limit_redis_reset')) {
    function rate_limit_redis_reset(string $bucket): bool
    {
        $redis = rate_limit_redis_client();
        if (!$redis instanceof Redis) {
            return false;
        }

        try {
            $key = rate_limit_redis_key($bucket);
            $redis->del($key);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('rate_limit_file_consume')) {
    function rate_limit_file_consume(string $bucket, int $limit, int $windowSec): bool
    {
        if ($limit <= 0 || $windowSec <= 0) {
            return true;
        }

        $dir = __DIR__ . '/../../storage/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/limits.json';
        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            // Fail-open: if limiter storage is unavailable, do not block users.
            return true;
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return true;
            }

            rewind($fp);
            $raw = stream_get_contents($fp);
            $data = [];
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }

            $now = time();
            $minTs = $now - $windowSec;

            $hits = $data[$bucket] ?? [];
            if (!is_array($hits)) {
                $hits = [];
            }

            $hits = array_values(array_filter($hits, static function ($ts) use ($minTs): bool {
                return is_int($ts) && $ts >= $minTs;
            }));

            $allowed = count($hits) < $limit;
            if ($allowed) {
                $hits[] = $now;
            }
            $data[$bucket] = $hits;

            // Global pruning to keep file compact.
            foreach ($data as $k => $arr) {
                if (!is_array($arr)) {
                    unset($data[$k]);
                    continue;
                }
                $arr = array_values(array_filter($arr, static function ($ts) use ($minTs): bool {
                    return is_int($ts) && $ts >= $minTs;
                }));
                if (count($arr) === 0) {
                    unset($data[$k]);
                } else {
                    $data[$k] = $arr;
                }
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string)json_encode($data, JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            return $allowed;
        } catch (Throwable $e) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }
    }
}

if (!function_exists('rate_limit_file_reset')) {
    function rate_limit_file_reset(string $bucket): void
    {
        $dir = __DIR__ . '/../../storage/ratelimit';
        $file = $dir . '/limits.json';
        if (!is_file($file)) {
            return;
        }

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return;
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return;
            }

            rewind($fp);
            $raw = stream_get_contents($fp);
            $data = [];
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }

            unset($data[$bucket]);

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string)json_encode($data, JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        } catch (Throwable $e) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }
}

if (!function_exists('rate_limit_consume')) {
    function rate_limit_consume(string $bucket, int $limit, int $windowSec): bool
    {
        $driver = rate_limit_driver();

        if ($driver === 'redis') {
            $allowed = rate_limit_redis_consume($bucket, $limit, $windowSec);
            if (is_bool($allowed)) {
                return $allowed;
            }
        }

        return rate_limit_file_consume($bucket, $limit, $windowSec);
    }
}

if (!function_exists('rate_limit_reset')) {
    function rate_limit_reset(string $bucket): void
    {
        $driver = rate_limit_driver();

        if ($driver === 'redis' && rate_limit_redis_reset($bucket)) {
            return;
        }

        rate_limit_file_reset($bucket);
    }
}
