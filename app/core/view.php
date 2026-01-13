<?php
declare(strict_types=1);

function view_render(string $view, array $data = []): void
{
    $viewsDir = __DIR__ . '/../views';

    $viewFile = $viewsDir . '/' . $view . '.php';
    if (!is_file($viewFile)) {
        http_response_code(500);
        echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
        return;
    }

    // данные станут переменными в шаблоне
    extract($data, EXTR_SKIP);

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    $layoutFile = $viewsDir . '/layout.php';
    require $layoutFile;
}
