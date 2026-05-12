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

if (!function_exists('redirect_permanent')) {
    function redirect_permanent(string $to): void
    {
        $to = trim($to);
        if ($to === '' || $to[0] !== '/' || str_starts_with($to, '//') || str_contains($to, '\\') || str_contains($to, "\r") || str_contains($to, "\n")) {
            $to = '/';
        }

        header('Location: ' . $to, true, 301);
        exit();
    }
}

if (!function_exists('test_slug')) {
    function test_slug(string $title): string
    {
        $title = trim(mb_strtolower($title));
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $slug = strtr($title, $map);
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;

        return $slug !== '' ? $slug : 'test';
    }
}

if (!function_exists('test_url')) {
    function test_url(int $testId, string $title): string
    {
        return '/tests/' . $testId . '-' . test_slug($title);
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
