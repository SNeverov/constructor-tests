<?php
declare(strict_types=1);

if (!function_exists('flash_set')) {
    function flash_set(string $key, mixed $value): void
    {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }

        $_SESSION['flash'][$key] = $value;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return $default;
        }

        if (!array_key_exists($key, $_SESSION['flash'])) {
            return $default;
        }

        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $value;
    }
}
?>
