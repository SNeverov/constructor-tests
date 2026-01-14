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

    $pdo = db();

    try {
        $stmt = $pdo->prepare('
            SELECT id, login, email, password_hash
            FROM users
            WHERE login = :login OR email = :email
            LIMIT 1
        ');

        $stmt->execute([
            ':login' => $identity,
            ':email' => $identity,
        ]);

        $user = $stmt->fetch();
    } catch (PDOException $e) {
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Ошибка БД: ' . $e->getMessage()],
            'old' => ['identity' => $identity],
        ]);
        return;
    }


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

    $pdo = db();

    try {
        // 1) Проверка уникальности
        $stmt = $pdo->prepare('
            SELECT id
            FROM users
            WHERE login = :login OR email = :email
            LIMIT 1
        ');
        $stmt->execute([
            ':login' => $login,
            ':email' => $email,
        ]);

        $exists = $stmt->fetch();

        if ($exists) {
            auth_register_form(['Логин или email уже заняты.'], $old);
            return;
        }

        // 2) INSERT
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('
            INSERT INTO users (login, email, password_hash)
            VALUES (:login, :email, :hash)
        ');
        $stmt->execute([
            ':login' => $login,
            ':email' => $email,
            ':hash' => $hash,
        ]);

        $userId = (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        auth_register_form(['Ошибка БД: ' . $e->getMessage()], $old);
        return;
    }


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
