<?php
declare(strict_types=1);

function my_tests_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int) ($user['id'] ?? 0);

    $tests = tests_list_by_user_id($userId);

    view_render('my_tests', [
        'title' => 'Мои тесты',
        'tests' => $tests,
    ]);
}

function my_tests_create_form(): void
{
    auth_required();

    view_render('test_create', [
        'title' => 'Создать тест',
    ]);
}

function my_tests_store(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int) $user['id'];

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $accessLevel = $_POST['access_level'] ?? 'public';

    if ($title === '') {
        die('Название теста обязательно');
    }

    tests_create($userId, $title, $description, $accessLevel);

    header('Location: /my/tests');
    exit();
}

?>
