<?php
declare(strict_types=1);

// CSRF (Cross-Site Request Forgery) защита через синхронизируемый токен в сессии.

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $t . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): void
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $postedToken  = $_POST['csrf_token'] ?? '';

        if (!is_string($sessionToken) || $sessionToken === '' || !is_string($postedToken) || $postedToken === '') {
            if (request_expects_json()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'CSRF-токен отсутствует. Обновите страницу и повторите действие.',
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }

            http_response_code(403);
            view_render('error', [
                'title' => 'Ошибка 403',
                'message' => 'CSRF-токен отсутствует. Обновите страницу и повторите действие.',
            ]);
            exit();
        }

        if (!hash_equals($sessionToken, $postedToken)) {
            if (request_expects_json()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'CSRF-токен неверный. Обновите страницу и повторите действие.',
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }

            http_response_code(403);
            view_render('error', [
                'title' => 'Ошибка 403',
                'message' => 'CSRF-токен неверный. Обнови страницу и повтори действие.',
            ]);
            exit();
        }
    }
}
?>
