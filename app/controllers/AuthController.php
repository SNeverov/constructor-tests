<?php
declare(strict_types=1);

function auth_register_form(array $errors = [], array $old = []): void
{
    view_render('register', [
        'title' => 'Регистрация',
        'errors' => $errors,
        'old' => $old,
    ]);
}

function auth_login_form(): void
{
    view_render('login', [
        'title' => 'Вход',
    ]);
}

function auth_login_submit(): void
{
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $errors = [];

    if ($identity === '') {
        $errors[] = 'Введи логин или email';
    }

    if ($password === '') {
        $errors[] = 'Введи пароль';
    }

    if ($errors) {
        view_render('login', [
            'title' => 'Вход',
            'errors' => $errors,
            'old' => ['identity' => $identity],
        ]);
        return;
    }

    $mysqli = db();

    $stmt = $mysqli->prepare('
    SELECT id, login, email, password_hash
    FROM users
    WHERE login = ? OR email = ?
    LIMIT 1
    ');

    if (!$stmt) {
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Ошибка БД: ' . $mysqli->error],
            'old' => ['identity' => $identity],
        ]);
        return;
    }

    $stmt->bind_param('ss', $identity, $identity);
    $stmt->execute();

    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;

    $stmt->close();

    if (!$user) {
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Пользователь не найден'],
            'old' => ['identity' => $identity],
        ]);
        return;
    }

    if (!password_verify($password, $user['password_hash'])) {
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Неверный логин/email или пароль'],
            'old' => ['identity' => $identity],
        ]);
        return;
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'login' => (string) $user['login'],
    ];

    $to = $_SESSION['redirect_to'] ?? '/';
    unset($_SESSION['redirect_to']);

    if (!is_string($to) || $to === '' || $to[0] !== '/') {
        $to = '/';
    }

    redirect($to);
}

function auth_register_submit(): void
{
    $login = trim((string) ($_POST['login'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');

    $old = ['login' => $login, 'email' => $email];
    $errors = [];

    if ($login === '' || mb_strlen($login) < 3 || mb_strlen($login) > 32) {
        $errors[] = 'Логин: 3–32 символа.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email некорректный.';
    }
    if ($pass === '' || mb_strlen($pass) < 6) {
        $errors[] = 'Пароль: минимум 6 символов.';
    }

    if ($errors) {
        auth_register_form($errors, $old);
        return;
    }

    $mysqli = db();

    // 1) Проверка уникальности
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE login = ? OR email = ? LIMIT 1');
    if (!$stmt) {
        auth_register_form(['Ошибка БД: ' . $mysqli->error], $old);
        return;
    }

    $stmt->bind_param('ss', $login, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        auth_register_form(['Логин или email уже заняты.'], $old);
        return;
    }
    $stmt->close();

    // 2) INSERT
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare('INSERT INTO users (login, email, password_hash) VALUES (?, ?, ?)');
    if (!$stmt) {
        auth_register_form(['Ошибка БД: ' . $mysqli->error], $old);
        return;
    }

    $stmt->bind_param('sss', $login, $email, $hash);

    if (!$stmt->execute()) {
        $stmt->close();
        auth_register_form(['Ошибка БД: ' . $mysqli->error], $old);
        return;
    }

    $userId = (int) $mysqli->insert_id;
    $stmt->close();

    // 3) Логин “по-настоящему”
    auth_login([
        'id' => $userId,
        'login' => $login,
        'email' => $email,
    ]);

    header('Location: /');
    exit();
}

function auth_logout_submit(): void
{
    auth_logout();
    header('Location: /');
    exit();
}
?>
