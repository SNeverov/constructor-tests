<?php
declare(strict_types=1);

if (!function_exists('redirect')) {
    function redirect(string $to): void
    {
        // базовая защита от open redirect
        if ($to === '' || $to[0] !== '/') {
            $to = '/';
        }

        header('Location: ' . $to, true, 302);
        exit();
    }
}
?>
