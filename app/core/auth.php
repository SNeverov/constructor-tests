<?php
declare(strict_types=1);

if (!function_exists('auth_is_logged_in')) {
    function auth_is_logged_in(): bool
    {
        return isset($_SESSION['user']);
    }
}

if (!function_exists('auth_required')) {
    function auth_required(): void
    {
        if (!auth_is_logged_in()) {
            // Запоминаем, куда пользователь шёл
            $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? '/';

            redirect('/login');
        }
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('auth_login')) {
    function auth_login(array $user): void
    {
        $_SESSION['user'] = $user;
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
        unset($_SESSION['user']);
    }
}
?>
