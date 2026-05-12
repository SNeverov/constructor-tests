<?php
declare(strict_types=1);

if (!function_exists('auth_is_logged_in')) {
    function auth_is_logged_in(): bool
    {
        auth_refresh_remember_cookie();
        return isset($_SESSION['user']);
    }
}

if (!function_exists('auth_required')) {
    function auth_required(): void
    {
        if (!auth_is_logged_in()) {
            if (request_expects_json()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'Сессия истекла. Войдите снова.',
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }

            // Запоминаем, куда пользователь шёл
            $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? '/';

            redirect('/login');
        }
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        auth_refresh_remember_cookie();
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('auth_is_admin')) {
    function auth_is_admin(?array $user = null): bool
    {
        $user ??= auth_user();
        return trim((string)($user['login'] ?? '')) === 'Admin';
    }
}

if (!function_exists('auth_refresh_remember_cookie')) {
    function auth_refresh_remember_cookie(): void
    {
        if (empty($_SESSION['user']) || empty($_SESSION['remember_me'])) {
            return;
        }

        $lifetime = app_session_remember_lifetime();
        $_SESSION['remember_me_until'] = time() + $lifetime;
        app_session_persist_cookie($lifetime);
    }
}

if (!function_exists('auth_login')) {
    function auth_login(array $user, bool $remember = false): void
    {
        $_SESSION['user'] = $user;

        if ($remember) {
            $_SESSION['remember_me'] = true;
            $_SESSION['remember_me_until'] = time() + app_session_remember_lifetime();
            app_session_persist_cookie(app_session_remember_lifetime());
            return;
        }

        unset($_SESSION['remember_me'], $_SESSION['remember_me_until']);
        app_session_persist_cookie(0);
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        app_session_clear_cookie();
    }
}
?>
