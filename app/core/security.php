<?php
declare(strict_types=1);

if (!function_exists('security_apply_headers')) {
    function security_apply_headers(): void
    {
        // Security headers are managed in public/.htaccess to avoid duplicates/conflicts
        // with web-server defaults and to cover static files as well.
    }
}

if (!function_exists('security_post_limit_rule')) {
function security_post_limit_rule(string $path): ?array
{
        if ($path === '/register') {
            return ['register', 10, 600];
        }
        if (preg_match('~^/my/tests/\d+/(delete|restore|destroy)$~', $path)) {
            return ['tests-mutate', 40, 60];
        }
        if (preg_match('~^/my/tests/\d+$~', $path)) {
            return ['tests-update', 20, 60];
        }
        if ($path === '/my/tests/trash/restore-all' || $path === '/my/tests/trash/empty') {
            return ['trash-mutate', 15, 60];
        }
        if ($path === '/my/tests') {
            return ['tests-create', 20, 60];
        }
        if (preg_match('~^/my/bookmarks/\d+/toggle$~', $path)) {
            return ['bookmarks-toggle', 80, 60];
        }
        if (preg_match('~^/tests/\d+/finish$~', $path)) {
            return ['tests-finish', 120, 60];
        }
        if (preg_match('~^/tests/\d+/rate$~', $path)) {
            return ['tests-rate', 30, 60];
        }

        return null;
    }
}

if (!function_exists('security_enforce_post_rate_limit')) {
    function security_enforce_post_rate_limit(string $path): void
    {
        $rule = security_post_limit_rule($path);
        if ($rule === null) {
            return;
        }

        [$name, $limit, $window] = $rule;
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $bucket = 'post:' . $name . ':' . hash('sha256', $ip);

        if (rate_limit_consume($bucket, (int)$limit, (int)$window)) {
            return;
        }

        http_response_code(429);
        view_render('error', [
            'title' => 'Слишком много запросов',
            'message' => 'Слишком много запросов за короткое время. Повторите позже.',
        ]);
        exit();
    }
}
