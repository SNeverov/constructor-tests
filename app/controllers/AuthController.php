<?php
declare(strict_types=1);

function auth_register_form(array $errors = [], array $old = []): void
{
    if (auth_is_logged_in()) {
        redirect('/');
    }

    view_render('register', [
        'title' => 'Регистрация',
        'errors' => $errors,
        'old' => $old,
		'styles' => ['/assets/css/auth.css'],
    ]);
}

function auth_login_form(): void
{
    if (auth_is_logged_in()) {
        redirect('/');
    }

    view_render('login', [
        'title' => 'Вход',
		'styles' => ['/assets/css/auth.css'],
    ]);
}

function auth_login_submit(): void
{
    if (auth_is_logged_in()) {
        redirect('/');
    }

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = (string)($_POST['remember_me'] ?? '') === '1';
    $identityNorm = mb_strtolower($identity);
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    $errors = [];

    if ($identity === '') {
        $errors[] = 'Введите логин или email';
    } elseif (mb_strlen($identity) > 255) {
        $errors[] = 'Логин/email слишком длинный.';
    }

    if ($password === '') {
        $errors[] = 'Введите пароль';
    } elseif (mb_strlen($password) > 255) {
        $errors[] = 'Пароль слишком длинный.';
    }

    if ($errors) {
        view_render('login', [
            'title' => 'Вход',
            'errors' => $errors,
            'old' => ['identity' => $identity, 'remember_me' => $remember],
			'styles' => ['/assets/css/auth.css'],
        ]);
        return;
    }

    $lockBucket = 'login-lock:' . hash('sha256', $ip . '|' . $identityNorm);
    if (!rate_limit_consume($lockBucket, 1, 600)) {
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Слишком много попыток входа. Повторите через 10 минут.'],
            'old' => ['identity' => $identity, 'remember_me' => $remember],
			'styles' => ['/assets/css/auth.css'],
        ]);
        return;
    }
    rate_limit_reset($lockBucket);

    $failBucket = 'login-fail:' . hash('sha256', $ip . '|' . $identityNorm);

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
        $errorId = app_error_id();
        app_log_exception($errorId, $e);
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Временная ошибка сервера. Код: ' . $errorId],
            'old' => ['identity' => $identity, 'remember_me' => $remember],
			'styles' => ['/assets/css/auth.css'],
        ]);
        return;
    }


    if (!$user) {
        if (!rate_limit_consume($failBucket, 10, 600)) {
            rate_limit_consume($lockBucket, 1, 600);
        }
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Неверный логин/email или пароль'],
            'old' => ['identity' => $identity, 'remember_me' => $remember],
			'styles' => ['/assets/css/auth.css'],
        ]);
        return;
    }

    if (!password_verify($password, $user['password_hash'])) {
        if (!rate_limit_consume($failBucket, 10, 600)) {
            rate_limit_consume($lockBucket, 1, 600);
        }
        view_render('login', [
            'title' => 'Вход',
            'errors' => ['Неверный логин/email или пароль'],
            'old' => ['identity' => $identity, 'remember_me' => $remember],
			'styles' => ['/assets/css/auth.css'],
        ]);
        return;
    }

    session_regenerate_id(true);
    rate_limit_reset($failBucket);
    rate_limit_reset($lockBucket);

    auth_login([
        'id' => (int) $user['id'],
        'login' => (string) $user['login'],
    ], $remember);

    $to = $_SESSION['redirect_to'] ?? '/';
    unset($_SESSION['redirect_to']);

    if (!is_string($to) || $to === '' || $to[0] !== '/') {
        $to = '/';
    }

    redirect($to);
}

function auth_register_submit(): void
{
    if (auth_is_logged_in()) {
        redirect('/');
    }

    $loginRaw = (string) ($_POST['login'] ?? '');
    $login = trim($loginRaw);
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');

    $old = ['login' => $login, 'email' => $email];
    $errors = [];

    if ($login === '' || mb_strlen($login) < 3 || mb_strlen($login) > 24) {
        $errors[] = 'Логин: от 3 до 24 символов.';
    } elseif (preg_match('/\s/u', $loginRaw) === 1) {
        $errors[] = 'Логин не должен содержать пробелы.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email указан некорректно.';
    } elseif (mb_strlen($email) > 255) {
        $errors[] = 'Email слишком длинный.';
    }
    if ($pass === '' || mb_strlen($pass) < 6 || mb_strlen($pass) > 255) {
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
        $errorId = app_error_id();
        app_log_exception($errorId, $e);
        auth_register_form(['Временная ошибка сервера. Код: ' . $errorId], $old);
        return;
    }


    // 3) Логин “по-настоящему”
    auth_login([
        'id' => $userId,
        'login' => $login,
        'email' => $email,
    ]);

    redirect('/');
}

function auth_logout_submit(): void
{
    auth_logout();
    redirect('/');
}
?>
