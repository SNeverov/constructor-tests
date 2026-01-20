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

	// Глобальные данные для layout (не размазываем по всем контроллерам)
    if (auth_is_logged_in() && !array_key_exists('trashCount', $data)) {
        $user = auth_user();
        $userId = (int)($user['id'] ?? 0);

        if ($userId > 0 && function_exists('tests_trash_count_by_user_id')) {
            $data['trashCount'] = tests_trash_count_by_user_id($userId);
        } else {
            $data['trashCount'] = 0;
        }
    }


    // данные станут переменными в шаблоне
    extract($data, EXTR_SKIP);

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    $layoutFile = $viewsDir . '/layout.php';
    require $layoutFile;
}
