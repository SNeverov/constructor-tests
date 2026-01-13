<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../app/core/helpers.php';
require __DIR__ . '/../app/core/view.php';
require __DIR__ . '/../app/controllers/HomeController.php';
require __DIR__ . '/../app/controllers/AuthController.php';
require __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/db.php';

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

if ($path === '/db-test') {
    $res = mysqli_query(db(), 'SELECT NOW() AS now');
    $row = mysqli_fetch_assoc($res);
    echo 'OK ' . $row['now'];
    exit();
}

if ($path === '/user-insert-test') {
    $hash = password_hash('12345678', PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        db(),
        'INSERT INTO users (login, email, password_hash) VALUES (?, ?, ?)',
    );
    mysqli_stmt_bind_param($stmt, 'sss', $login, $email, $hash);

    $login = 'test_user';
    $email = 'test@test.ru';

    mysqli_stmt_execute($stmt);

    echo 'INSERT OK, id=' . mysqli_insert_id(db());
    exit();
}

if ($path === '/my/tests') {
    auth_required();
    echo 'TODO: Мои тесты';
    exit();
}

if ($path === '/my/tests/create') {
    auth_required();
    echo 'TODO: Создание теста';
    exit();
}

http_response_code(404);
view_render('404', [
    'title' => '404',
]);
