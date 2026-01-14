<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../app/core/helpers.php';
require __DIR__ . '/../app/core/view.php';
require __DIR__ . '/../app/controllers/HomeController.php';
require __DIR__ . '/../app/controllers/AuthController.php';
require __DIR__ . '/../app/controllers/TestsController.php';
require __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/db.php';
require __DIR__ . '/../app/core/tests.php';

set_exception_handler(function (Throwable $e): void {
    if ($e instanceof PDOException) {
        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Ошибка БД. Попробуйте позже.',
        ]);
        return;
    }

    http_response_code(500);
    echo 'Server error';
});

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/login' && $method === 'GET') {
    auth_login_form();
    exit();
}

if ($path === '/login' && $method === 'POST') {
    auth_login_submit();
    exit();
}

if ($path === '/' || $path === '') {
    home_index();
    exit();
}

if ($path === '/register' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    auth_register_form();
    exit();
}

if ($path === '/register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    auth_register_submit();
    exit();
}

if ($path === '/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    auth_logout_submit();
    exit();
}

if ($path === '/my/tests' && $method === 'GET') {
    my_tests_index();
    exit();
}

if ($path === '/my/tests/create' && $method === 'GET') {
    my_tests_create_form();
    exit();
}

if ($path === '/my/tests' && $method === 'POST') {
    my_tests_store();
    exit();
}

http_response_code(404);
view_render('404', [
    'title' => '404',
]);
