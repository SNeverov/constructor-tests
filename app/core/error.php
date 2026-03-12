<?php
declare(strict_types=1);

if (!function_exists('app_error_id')) {
    function app_error_id(): string
    {
        return strtoupper(bin2hex(random_bytes(6)));
    }
}

if (!function_exists('app_log_exception')) {
    function app_log_exception(string $errorId, Throwable $e): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/app.log';
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $method = (string)($_SERVER['REQUEST_METHOD'] ?? '');
        $line = sprintf(
            "[%s] error_id=%s method=%s uri=%s type=%s message=%s file=%s:%d\n",
            date('c'),
            $errorId,
            $method,
            $uri,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        @file_put_contents($logFile, $line, FILE_APPEND);
    }
}

