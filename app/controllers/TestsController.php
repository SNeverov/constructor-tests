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

    $errors = [];

    $title = trim($_POST['title'] ?? '');
    $questions = $_POST['questions'] ?? [];



    if (!is_array($questions) || count($questions) === 0) {
        $errors[] = 'Добавьте хотя бы один вопрос';
    }


    if (is_array($questions)) {
        foreach ($questions as $i => $q) {
            $num = $i + 1;

            $qText = trim($q['text'] ?? '');
            $qType = $q['type'] ?? '';

            if ($qText === '') {
                $errors[] = "Вопрос #{$num}: текст вопроса обязателен";
                continue;
            }

            if (!in_array($qType, ['radio', 'checkbox', 'input'], true)) {
                $errors[] = "Вопрос #{$num}: неверный тип вопроса";
                continue;
            }

            if ($qType === 'input') {
                $answers = $q['answers'] ?? [];
                if (!is_array($answers)) $answers = [];

                $answers = array_values(array_filter($answers, fn($a) => trim((string)$a) !== ''));

                if (count($answers) < 1) {
                    $errors[] = "Вопрос #{$num}: укажи хотя бы один правильный текстовый ответ";
                }
            } else {
                $options = $q['options'] ?? [];
                if (!is_array($options)) $options = [];

                // берём только непустые варианты (чтобы не считались пустые строки)
                $options = array_values(array_filter($options, fn($o) => trim((string)($o['text'] ?? '')) !== ''));

                if (count($options) < 2) {
                    $errors[] = "Вопрос #{$num}: минимум два варианта ответа";
                    continue;
                }

                $correctCount = 0;
                foreach ($options as $o) {
                    $isCorrect = (int)($o['is_correct'] ?? 0);
                    if ($isCorrect === 1) $correctCount++;
                }

                if ($qType === 'radio' && $correctCount !== 1) {
                    $errors[] = "Вопрос #{$num}: при radio должен быть ровно 1 правильный вариант";
                }

                if ($qType === 'checkbox' && $correctCount < 1) {
                    $errors[] = "Вопрос #{$num}: при checkbox отметь хотя бы 1 правильный вариант";
                }
            }
        }
    }

    if (!empty($errors)) {
            echo '<pre>';
            print_r($errors);
            echo '</pre>';
            exit;
    }



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
