<?php
declare(strict_types=1);

if (!function_exists('redirect')) {
    function redirect(string $to): void
    {
        // Accept only internal absolute paths like "/path?x=1".
        // Reject protocol-relative ("//host"), backslashes and CR/LF.
        $to = trim($to);
        if ($to === '') {
            $to = '/';
        }
        if (str_contains($to, "\r") || str_contains($to, "\n")) {
            $to = '/';
        }
        if ($to[0] !== '/') {
            $to = '/';
        }
        if (str_starts_with($to, '//')) {
            $to = '/';
        }
        if (str_contains($to, '\\')) {
            $to = '/';
        }

        header('Location: ' . $to, true, 302);
        exit();
    }
}

if (!function_exists('request_expects_json')) {
    function request_expects_json(): bool
    {
        $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));

        return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }
}
?>
