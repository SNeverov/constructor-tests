<?php
declare(strict_types=1);

require __DIR__ . '/../app/core/env.php';
env_load(__DIR__ . '/../.env');

require __DIR__ . '/../app/core/helpers.php';
require __DIR__ . '/../app/core/config.php';
require __DIR__ . '/../app/core/cache.php';
require __DIR__ . '/../app/core/session.php';
require __DIR__ . '/../app/core/observability.php';
require __DIR__ . '/../app/core/rate_limit.php';
require __DIR__ . '/../app/core/security.php';
require __DIR__ . '/../app/core/flash.php';
require __DIR__ . '/../app/core/view.php';
require __DIR__ . '/../app/core/error.php';
require __DIR__ . '/../app/controllers/HomeController.php';
require __DIR__ . '/../app/controllers/AuthController.php';
require __DIR__ . '/../app/controllers/TestsController.php';
require __DIR__ . '/../app/controllers/AccountController.php';
require __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/db.php';
require __DIR__ . '/../app/core/tests.php';
require __DIR__ . '/../app/core/csrf.php';
require __DIR__ . '/../app/core/form.php';
require __DIR__ . '/../app/core/upload.php';

app_session_bootstrap();
observability_bootstrap();
app_session_start();

security_apply_headers();

set_exception_handler(function (Throwable $e): void {
    $errorId = app_error_id();
    app_log_exception($errorId, $e);

    if ($e instanceof PDOException) {
        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Ошибка БД. Попробуйте позже. Код: ' . $errorId,
        ]);
        return;
    }

    http_response_code(500);
    view_render('error', [
        'title' => 'Ошибка',
        'message' => 'Внутренняя ошибка сервера. Код: ' . $errorId,
    ]);
});

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    csrf_verify();
    security_enforce_post_rate_limit($path);
}

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

if ($path === '/register' && $method === 'GET') {
    auth_register_form();
    exit();
}

if ($path === '/register' && $method === 'POST') {
    auth_register_submit();
    exit();
}

if ($path === '/logout' && $method === 'POST') {
    auth_logout_submit();
    exit();
}

if ($path === '/account' && $method === 'GET') {
    account_index();
    exit();
}

if ($path === '/my/results' && $method === 'GET') {
    my_results_index();
    exit();
}

if ($path === '/my/bookmarks' && $method === 'GET') {
    my_bookmarks_index();
    exit();
}

if ($path === '/my/header/counters' && $method === 'GET') {
    header_counters_json();
    exit();
}

if ($path === '/my/tests' && $method === 'GET') {
    my_tests_index();
    exit();
}

if ($path === '/my/tests/trash' && $method === 'GET') {
    my_tests_trash_index();
    exit();
}


if ($path === '/my/tests/create' && $method === 'GET') {
    my_tests_create_form();
    exit();
}

if ($path === '/my/tests/upload-image' && $method === 'POST') {
    my_tests_upload_image();
    exit();
}

if ($method === 'GET' && preg_match('~^/my/tests/(\d+)/edit$~', $path, $m)) {
    my_tests_edit_form((int)$m[1]);
    exit();
}

if ($path === '/my/tests' && $method === 'POST') {
    my_tests_store();
    exit();
}

if ($method === 'POST' && preg_match('~^/my/tests/(\d+)$~', $path, $m)) {
    my_tests_update((int)$m[1]);
    exit();
}

if ($method === 'POST' && preg_match('~^/my/tests/(\d+)/delete$~', $path, $m)) {
    my_tests_delete((int)$m[1]);
    exit();
}

if ($method === 'POST' && preg_match('~^/my/tests/(\d+)/restore$~', $path, $m)) {
    my_tests_restore((int)$m[1]);
    exit();
}

if ($method === 'POST' && preg_match('~^/my/tests/(\d+)/destroy$~', $path, $m)) {
    my_tests_destroy((int)$m[1]);
    exit();
}

if ($method === 'POST' && preg_match('~^/my/bookmarks/(\d+)/toggle$~', $path, $m)) {
    my_bookmarks_toggle((int)$m[1]);
    exit();
}

if ($path === '/my/tests/trash/restore-all' && $method === 'POST') {
    my_tests_trash_restore_all();
    exit();
}

if ($path === '/my/tests/trash/empty' && $method === 'POST') {
    my_tests_trash_empty();
    exit();
}



if ($method === 'POST' && preg_match('~^/tests/(\d+)/finish$~', $path, $m)) {
    test_finish((int)$m[1]);
    exit();
}

if ($method === 'POST' && preg_match('~^/tests/(\d+)/rate$~', $path, $m)) {
    test_rate((int)$m[1]);
    exit();
}

if ($method === 'GET' && preg_match('~^/attempts/(\d+)$~', $path, $m)) {
    attempt_show((int)$m[1]);
    exit();
}


if ($method === 'GET' && preg_match('~^/tests/(\d+)/pass$~', $path, $m)) {
    test_pass((int)$m[1]);
    exit();
}

if ($method === 'GET' && preg_match('~^/tests/(\d+)$~', $path, $m)) {
    test_show((int)$m[1]);
    exit();
}


http_response_code(404);
view_render('404', [
    'title' => '404',
]);
