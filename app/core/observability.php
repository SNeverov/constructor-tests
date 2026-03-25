<?php
declare(strict_types=1);

if (!function_exists('observability_bootstrap')) {
    function observability_bootstrap(): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $phpErrorLog = $logDir . '/php-error.log';

        @ini_set('log_errors', '1');
        @ini_set('display_errors', '0');
        @ini_set('error_log', $phpErrorLog);
    }
}
