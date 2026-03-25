<?php
declare(strict_types=1);

if (!function_exists('app_config_all')) {
    function app_config_all(): array
    {
        static $cfg;
        if (is_array($cfg)) {
            return $cfg;
        }

        $loaded = require __DIR__ . '/../../config/config.php';
        $cfg = is_array($loaded) ? $loaded : [];

        return $cfg;
    }
}

if (!function_exists('app_config')) {
    /**
     * Dot-notation config lookup: app_config('drivers.rate_limit', 'file')
     */
    function app_config(string $key, mixed $default = null): mixed
    {
        $cfg = app_config_all();
        if ($key === '') {
            return $cfg;
        }

        $parts = explode('.', $key);
        $current = $cfg;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }
}
