<?php
declare(strict_types=1);

if (!function_exists('rate_limit_consume')) {
    function rate_limit_consume(string $bucket, int $limit, int $windowSec): bool
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

if (!function_exists('rate_limit_reset')) {
    function rate_limit_reset(string $bucket): void
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
