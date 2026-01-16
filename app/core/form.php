<?php
declare(strict_types=1);

// Вспомогалки для форм, чтобы CSRF нельзя было "забыть".

if (!function_exists('form_open')) {
    /**
     * @param array<string, string|int|float|bool|null> $attrs
     */
    function form_open(string $action, string $method = 'post', array $attrs = []): string
    {
        $method = strtolower($method);

        // HTML поддерживает только GET/POST. Остальное — через hidden _method (на будущее).
        $realMethod = in_array($method, ['get', 'post'], true) ? $method : 'post';

        $attrString = '';
        foreach ($attrs as $k => $v) {
            if ($v === null) {
                continue;
            }

            $attrString .= ' ' . htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '"';
        }

        $html = '<form method="' . $realMethod . '" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '"' . $attrString . '>';

        if ($realMethod === 'post') {
            $html .= csrf_field();
        }

        if ($method !== $realMethod) {
            $html .= '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
        }

        return $html;
    }
}
?>
