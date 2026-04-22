<?php
declare(strict_types=1);

function test_form_old_from_db(array $test): array
{
    $testId = (int)($test['id'] ?? 0);
    $questions = questions_list_by_test_id($testId);
    $questionIds = [];
    foreach ($questions as $q) {
        $questionIds[] = (int)($q['id'] ?? 0);
    }

    $optionsByQ = options_full_list_by_question_ids($questionIds);
    $textAnswersByQ = text_answers_by_question_ids($questionIds);

    $oldQuestions = [];
    foreach ($questions as $q) {
        $qid = (int)($q['id'] ?? 0);
        $type = (string)($q['type'] ?? 'radio');

        $item = [
            'text' => (string)($q['question_text'] ?? ''),
            'type' => $type,
            'image_path' => $q['image_path'] ?? null,
            'options' => [],
            'answers' => [],
        ];

        if ($type === 'input') {
            $item['answers'] = array_values(array_map('strval', $textAnswersByQ[$qid] ?? []));
        } else {
            foreach (($optionsByQ[$qid] ?? []) as $opt) {
                $item['options'][] = [
                    'text' => (string)($opt['option_text'] ?? ''),
                    'is_correct' => (int)($opt['is_correct'] ?? 0),
                    'image_path' => $opt['image_path'] ?? null,
                ];
            }
        }

        $oldQuestions[] = $item;
    }

    return [
        'title' => (string)($test['title'] ?? ''),
        'description' => (string)($test['description'] ?? ''),
        'access_level' => (string)($test['access_level'] ?? 'public'),
        'category_names' => test_category_display_names($test['category_names'] ?? ($test['category_name'] ?? null)),
        'time_limit' => format_time_limit_hms(test_time_limit_sec_from_row($test)),
        'cover_image' => $test['cover_image'] ?? null,
        'questions' => $oldQuestions,
    ];
}

function my_tests_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int) ($user['id'] ?? 0);

    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 10;
    $total = tests_count_active_by_user_id($userId);
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_list_by_user_id_paginated($userId, $perPage, $offset);

    view_render('my_tests', [
        'title' => 'Мои тесты',
        'tests' => $tests,
		'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ],
		'scripts' => ['/assets/js/list-loading.js', '/assets/js/my-tests-share.js'],
		'styles' => ['/assets/css/my-tests.css'],
    ]);
}

function my_tests_create_form(): void
{
    auth_required();

    view_render('test_create', [
        'title' => 'Создать тест',
        'styles' => ['/assets/css/test-create.css'],
    ]);
}

function my_tests_edit_form(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    $test = tests_find_active_by_id_and_user_id($testId, $userId);

    if ($test === null) {
        http_response_code(404);
        view_render('404', [
            'title' => '404',
        ]);
        return;
    }

    $old = test_form_old_from_db($test);

    view_render('test_create', [
        'title' => 'Редактировать тест',
        'styles' => ['/assets/css/test-create.css'],
        'is_edit' => true,
        'form_action' => '/my/tests/' . $testId,
        'submit_label' => 'Сохранить изменения',
        'old' => $old,
    ]);
}

function my_tests_store(): void
{
    auth_required();

    $errors = [];
    $MAX_INPUT_ANSWER_LEN = 1000;

    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        $errors[] = 'Название теста обязательно';
    } elseif (mb_strlen($title) < 10 || mb_strlen($title) > 200) {
        $errors[] = 'Название теста должно быть от 10 до 200 символов';
    }

    $accessLevel = $_POST['access_level'] ?? '';
    if (!in_array($accessLevel, ['public', 'registered'], true)) {
        $errors[] = 'Некорректный уровень доступа теста';
    }

    $description = trim($_POST['description'] ?? '');
    $descLen = mb_strlen($description);
    if ($descLen < 30 || $descLen > 500) {
        $errors[] = 'Описание теста должно быть не меньше 30 символов и не больше 500 символов';
    }

    $categoryNames = test_category_default_names();
    try {
        $categoryNames = test_category_names_from_input($_POST['category_names'] ?? null);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
    if ($categoryNames === []) {
        $errors[] = 'Выберите хотя бы одну категорию';
    }

    $timeLimitSec = null;
    try {
        $timeLimitSec = normalize_test_time_limit_sec($_POST['time_limit'] ?? null);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }

    $questions = $_POST['questions'] ?? [];
    if (!is_array($questions) || count($questions) < 1) {
        $errors[] = 'Добавь хотя бы один вопрос';
        $questions = [];
    }

    $MAX_QUESTIONS = 100;
    $MAX_OPTIONS = 10;
    $MAX_INPUT_ANSWERS = 10;

    if (count($questions) > $MAX_QUESTIONS) {
        $errors[] = "Слишком много вопросов: максимум {$MAX_QUESTIONS}";
        $questions = array_slice($questions, 0, $MAX_QUESTIONS);
    }

    if (is_array($questions)) {
        foreach ($questions as $i => $q) {
            $num = $i + 1;

            if (!is_array($q)) {
                $errors[] = "Вопрос #{$num}: некорректные данные";
                continue;
            }

            $qText = trim($q['text'] ?? '');
            $qType = (string)($q['type'] ?? '');
            $qLen = mb_strlen($qText);

            if ($qText === '') {
                $errors[] = "Вопрос #{$num}: текст вопроса обязателен";
                continue;
            }

            if ($qLen < 5 || $qLen > 1000) {
                $errors[] = "Вопрос #{$num}: текст вопроса должен быть от 5 до 1000 символов";
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
                    continue;
                }

                if (count($answers) > $MAX_INPUT_ANSWERS) {
                    $errors[] = "Вопрос #{$num}: максимум {$MAX_INPUT_ANSWERS} текстовых ответов";
                    continue;
                }

                $seenAnswers = [];
                foreach ($answers as $a) {
                    $norm = normalize_input_answer((string) $a);

                    if ($norm === '') {
                        continue;
                    }

                    if (isset($seenAnswers[$norm])) {
                        $errors[] = "Вопрос #{$num}: текстовые ответы не должны повторяться (регистр/пробелы/ё→е)";
                        continue 2;
                    }

                    $seenAnswers[$norm] = true;
                }

                foreach ($answers as $a) {
                    $aText = trim((string)$a);
                    $aLen = mb_strlen($aText);

                    if ($aLen < 1 || $aLen > $MAX_INPUT_ANSWER_LEN) {
                        $errors[] = "Вопрос #{$num}: текстовый ответ должен быть от 1 до {$MAX_INPUT_ANSWER_LEN} символов";
                        break;
                    }
                }

            } else {
                $options = $q['options'] ?? [];
                if (!is_array($options)) $options = [];

                $options = array_values(array_filter($options, fn($o) => trim((string)($o['text'] ?? '')) !== ''));

                if (count($options) < 2) {
                    $errors[] = "Вопрос #{$num}: минимум два варианта ответа";
                    continue;
                }

                if (count($options) > $MAX_OPTIONS) {
                    $errors[] = "Вопрос #{$num}: максимум {$MAX_OPTIONS} вариантов ответа";
                    continue;
                }

                $seen = [];
                foreach ($options as $o) {
                    $t = trim((string)($o['text'] ?? ''));
                    $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
                    $t = mb_strtolower($t);

                    if ($t === '') {
                        continue;
                    }

                    if (isset($seen[$t])) {
                        $errors[] = "Вопрос #{$num}: варианты ответа не должны повторяться";
                        continue 2;
                    }

                    $seen[$t] = true;
                }

                foreach ($options as $o) {
                    $optText = trim((string)($o['text'] ?? ''));
                    $len = mb_strlen($optText);

                    if ($len < 1 || $len > 1000) {
                        $errors[] = "Вопрос #{$num}: вариант ответа должен быть от 1 до 1000 символов";
                        break;
                    }
                }

                $correctCount = 0;
                foreach ($options as $o) {
                    $isCorrect = (int)($o['is_correct'] ?? 0);
                    if ($isCorrect === 1) $correctCount++;
                }

                if ($qType === 'radio' && $correctCount !== 1) {
                    $errors[] = "Вопрос #{$num}: при radio должен быть ровно 1 правильный вариант";
                    continue;
                }

                if ($qType === 'checkbox' && $correctCount < 1) {
                    $errors[] = "Вопрос #{$num}: при checkbox отметь хотя бы 1 правильный вариант";
                    continue;
                }
            }
        }
    }

    if (!empty($errors)) {
        view_render('test_create', [
            'title'  => 'Создать тест',
            'styles' => ['/assets/css/test-create.css'],
            'errors' => $errors,
            'old'    => [
                'title'        => $_POST['title'] ?? '',
                'description'  => $_POST['description'] ?? '',
                'access_level' => $_POST['access_level'] ?? 'public',
                'category_names' => $_POST['category_names'] ?? [],
                'time_limit'   => $_POST['time_limit'] ?? '',
                'cover_image'  => $_POST['cover_image'] ?? null,
                'questions'    => $_POST['questions'] ?? [],
            ],
        ]);
        exit();
    }

    $user = auth_user();
    $userId = (int) $user['id'];

    $title = trim($_POST['title'] ?? '');
    $pdo = db();
    try {
        $pdo->beginTransaction();

        $coverImage = trim($_POST['cover_image'] ?? '');
        $coverImage = ($coverImage !== '' && str_starts_with($coverImage, '/uploads/')) ? $coverImage : null;

        $testId = tests_create($userId, $title, $description, $accessLevel, $categoryNames, $coverImage, $timeLimitSec);
        test_categories_replace_by_test_id($testId, $categoryNames);

        $questions = array_values($questions);

        foreach ($questions as $qIndex => $q) {
            if (!is_array($q)) {
                continue;
            }

            $qType = (string)($q['type'] ?? '');
            $qText = trim((string)($q['text'] ?? ''));
            $qPos  = $qIndex + 1;
            $qImg  = trim((string)($q['image_path'] ?? ''));
            $qImg  = ($qImg !== '' && str_starts_with($qImg, '/uploads/')) ? $qImg : null;

            $questionId = questions_create($testId, $qType, $qText, $qPos, $qImg);

            if ($qType === 'radio' || $qType === 'checkbox') {
                $options = $q['options'] ?? [];
                if (!is_array($options)) {
                    $options = [];
                }

                $options = array_values(array_filter(
                    $options,
                    fn($o) => trim((string)($o['text'] ?? '')) !== ''
                ));

                foreach ($options as $i => $opt) {
                    $optText = trim((string)($opt['text'] ?? ''));
                    if ($optText === '') {
                        continue;
                    }

                    $pos = $i + 1;
                    $isCorrect = (int)($opt['is_correct'] ?? 0);
                    $optImg = trim((string)($opt['image_path'] ?? ''));
                    $optImg = ($optImg !== '' && str_starts_with($optImg, '/uploads/')) ? $optImg : null;

                    options_create($questionId, $optText, $isCorrect, $pos, $optImg);
                }
            }

            if ($qType === 'input') {
                $answers = $q['answers'] ?? [];
                if (!is_array($answers)) {
                    $answers = [];
                }

                $answers = array_values(array_filter(
                    $answers,
                    fn($a) => trim((string)$a) !== ''
                ));

                foreach ($answers as $ansText) {
                    $text = trim((string)$ansText);
                    if ($text === '') {
                        continue;
                    }

                    question_text_answers_create($questionId, $text);
                }
            }
        }

        $pdo->commit();
        tests_payload_cache_invalidate($testId);
        redirect('/my/tests');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось сохранить тест целиком. Попробуйте ещё раз.',
        ]);
        return;
    }
}

function my_tests_update(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    $existingTest = tests_find_active_by_id_and_user_id($testId, $userId);
    if ($existingTest === null) {
        http_response_code(404);
        view_render('404', [
            'title' => '404',
        ]);
        return;
    }

    $errors = [];
    $MAX_INPUT_ANSWER_LEN = 1000;

    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        $errors[] = 'Название теста обязательно';
    } elseif (mb_strlen($title) < 10 || mb_strlen($title) > 200) {
        $errors[] = 'Название теста должно быть от 10 до 200 символов';
    }

    $accessLevel = $_POST['access_level'] ?? '';
    if (!in_array($accessLevel, ['public', 'registered'], true)) {
        $errors[] = 'Некорректный уровень доступа теста';
    }

    $description = trim($_POST['description'] ?? '');
    $descLen = mb_strlen($description);
    if ($descLen < 30 || $descLen > 500) {
        $errors[] = 'Описание теста должно быть не меньше 30 символов и не больше 500 символов';
    }

    $categoryNames = test_category_default_names();
    try {
        $categoryNames = test_category_names_from_input($_POST['category_names'] ?? null);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }
    if ($categoryNames === []) {
        $errors[] = 'Выберите хотя бы одну категорию';
    }

    $timeLimitSec = null;
    try {
        $timeLimitSec = normalize_test_time_limit_sec($_POST['time_limit'] ?? null);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
    }

    $questions = $_POST['questions'] ?? [];
    if (!is_array($questions) || count($questions) < 1) {
        $errors[] = 'Добавь хотя бы один вопрос';
        $questions = [];
    }

    $MAX_QUESTIONS = 100;
    $MAX_OPTIONS = 10;
    $MAX_INPUT_ANSWERS = 10;

    if (count($questions) > $MAX_QUESTIONS) {
        $errors[] = "Слишком много вопросов: максимум {$MAX_QUESTIONS}";
        $questions = array_slice($questions, 0, $MAX_QUESTIONS);
    }

    if (is_array($questions)) {
        foreach ($questions as $i => $q) {
            $num = $i + 1;

            if (!is_array($q)) {
                $errors[] = "Вопрос #{$num}: некорректные данные";
                continue;
            }

            $qText = trim($q['text'] ?? '');
            $qType = (string)($q['type'] ?? '');
            $qLen = mb_strlen($qText);

            if ($qText === '') {
                $errors[] = "Вопрос #{$num}: текст вопроса обязателен";
                continue;
            }

            if ($qLen < 5 || $qLen > 1000) {
                $errors[] = "Вопрос #{$num}: текст вопроса должен быть от 5 до 1000 символов";
                continue;
            }

            if (!in_array($qType, ['radio', 'checkbox', 'input'], true)) {
                $errors[] = "Вопрос #{$num}: неверный тип вопроса";
                continue;
            }

            if ($qType === 'input') {
                $answers = $q['answers'] ?? [];
                if (!is_array($answers)) {
                    $answers = [];
                }

                $answers = array_values(array_filter($answers, fn($a) => trim((string)$a) !== ''));

                if (count($answers) < 1) {
                    $errors[] = "Вопрос #{$num}: укажи хотя бы один правильный текстовый ответ";
                    continue;
                }

                if (count($answers) > $MAX_INPUT_ANSWERS) {
                    $errors[] = "Вопрос #{$num}: максимум {$MAX_INPUT_ANSWERS} текстовых ответов";
                    continue;
                }

                $seenAnswers = [];
                foreach ($answers as $a) {
                    $norm = normalize_input_answer((string)$a);

                    if ($norm === '') {
                        continue;
                    }

                    if (isset($seenAnswers[$norm])) {
                        $errors[] = "Вопрос #{$num}: текстовые ответы не должны повторяться (регистр/пробелы/ё→е)";
                        continue 2;
                    }

                    $seenAnswers[$norm] = true;
                }

                foreach ($answers as $a) {
                    $aText = trim((string)$a);
                    $aLen = mb_strlen($aText);

                    if ($aLen < 1 || $aLen > $MAX_INPUT_ANSWER_LEN) {
                        $errors[] = "Вопрос #{$num}: текстовый ответ должен быть от 1 до {$MAX_INPUT_ANSWER_LEN} символов";
                        break;
                    }
                }
            } else {
                $options = $q['options'] ?? [];
                if (!is_array($options)) {
                    $options = [];
                }

                $options = array_values(array_filter($options, fn($o) => trim((string)($o['text'] ?? '')) !== ''));

                if (count($options) < 2) {
                    $errors[] = "Вопрос #{$num}: минимум два варианта ответа";
                    continue;
                }

                if (count($options) > $MAX_OPTIONS) {
                    $errors[] = "Вопрос #{$num}: максимум {$MAX_OPTIONS} вариантов ответа";
                    continue;
                }

                $seen = [];
                foreach ($options as $o) {
                    $t = trim((string)($o['text'] ?? ''));
                    $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
                    $t = mb_strtolower($t);

                    if ($t === '') {
                        continue;
                    }

                    if (isset($seen[$t])) {
                        $errors[] = "Вопрос #{$num}: варианты ответа не должны повторяться";
                        continue 2;
                    }

                    $seen[$t] = true;
                }

                foreach ($options as $o) {
                    $optText = trim((string)($o['text'] ?? ''));
                    $len = mb_strlen($optText);

                    if ($len < 1 || $len > 1000) {
                        $errors[] = "Вопрос #{$num}: вариант ответа должен быть от 1 до 1000 символов";
                        break;
                    }
                }

                $correctCount = 0;
                foreach ($options as $o) {
                    $isCorrect = (int)($o['is_correct'] ?? 0);
                    if ($isCorrect === 1) {
                        $correctCount++;
                    }
                }

                if ($qType === 'radio' && $correctCount !== 1) {
                    $errors[] = "Вопрос #{$num}: при radio должен быть ровно 1 правильный вариант";
                    continue;
                }

                if ($qType === 'checkbox' && $correctCount < 1) {
                    $errors[] = "Вопрос #{$num}: при checkbox отметь хотя бы 1 правильный вариант";
                    continue;
                }
            }
        }
    }

    if (!empty($errors)) {
        view_render('test_create', [
            'title' => 'Редактировать тест',
            'styles' => ['/assets/css/test-create.css'],
            'is_edit' => true,
            'form_action' => '/my/tests/' . $testId,
            'submit_label' => 'Сохранить изменения',
            'errors' => $errors,
            'old' => [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'access_level' => $_POST['access_level'] ?? 'public',
                'category_names' => $_POST['category_names'] ?? [],
                'time_limit' => $_POST['time_limit'] ?? '',
                'cover_image'  => $_POST['cover_image'] ?? null,
                'questions'    => $_POST['questions'] ?? [],
            ],
        ]);
        return;
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $coverImage = trim($_POST['cover_image'] ?? '');
        $coverImage = ($coverImage !== '' && str_starts_with($coverImage, '/uploads/')) ? $coverImage : null;

        tests_update_by_id_and_user_id($testId, $userId, $title, $description, $accessLevel, $categoryNames, $coverImage, $timeLimitSec);
        test_categories_replace_by_test_id($testId, $categoryNames);

        questions_delete_by_test_id($testId);

        $questions = array_values($questions);
        foreach ($questions as $qIndex => $q) {
            if (!is_array($q)) {
                continue;
            }

            $qType = (string)($q['type'] ?? '');
            $qText = trim((string)($q['text'] ?? ''));
            $qPos  = $qIndex + 1;
            $qImg  = trim((string)($q['image_path'] ?? ''));
            $qImg  = ($qImg !== '' && str_starts_with($qImg, '/uploads/')) ? $qImg : null;

            $questionId = questions_create($testId, $qType, $qText, $qPos, $qImg);

            if ($qType === 'radio' || $qType === 'checkbox') {
                $options = $q['options'] ?? [];
                if (!is_array($options)) {
                    $options = [];
                }

                $options = array_values(array_filter(
                    $options,
                    fn($o) => trim((string)($o['text'] ?? '')) !== ''
                ));

                foreach ($options as $i => $opt) {
                    $optText = trim((string)($opt['text'] ?? ''));
                    if ($optText === '') {
                        continue;
                    }

                    $pos = $i + 1;
                    $isCorrect = (int)($opt['is_correct'] ?? 0);
                    $optImg = trim((string)($opt['image_path'] ?? ''));
                    $optImg = ($optImg !== '' && str_starts_with($optImg, '/uploads/')) ? $optImg : null;

                    options_create($questionId, $optText, $isCorrect, $pos, $optImg);
                }
            }

            if ($qType === 'input') {
                $answers = $q['answers'] ?? [];
                if (!is_array($answers)) {
                    $answers = [];
                }

                $answers = array_values(array_filter(
                    $answers,
                    fn($a) => trim((string)$a) !== ''
                ));

                foreach ($answers as $ansText) {
                    $text = trim((string)$ansText);
                    if ($text === '') {
                        continue;
                    }
                    question_text_answers_create($questionId, $text);
                }
            }
        }

        $pdo->commit();
        tests_payload_cache_invalidate($testId);
        flash_set('toast', ['type' => 'success', 'text' => 'Тест обновлён']);
        redirect('/my/tests');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось сохранить изменения теста. Попробуйте ещё раз.',
        ]);
        return;
    }
}

function my_tests_delete(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int) ($user['id'] ?? 0);

    try {
        $deleted = tests_delete_by_id_and_user_id($testId, $userId);
    } catch (Throwable $e) {
        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось отправить тест в корзину. Попробуйте ещё раз.',
        ]);
        return;
    }

    if (!$deleted) {
        http_response_code(403);
        view_render('error', [
            'title' => 'Ошибка 403',
            'message' => 'Нельзя удалить этот тест (нет прав или тест не найден).',
        ]);
        return;
    }

	flash_set('toast', ['type' => 'success', 'text' => 'Тест отправлен в корзину']);
    flash_set('sync_event', [
        'type' => 'test-soft-deleted',
        'test_id' => $testId,
        'bookmarks_count' => tests_count_bookmarked_by_user_id($userId),
        'trash_count' => tests_trash_count_by_user_id($userId),
    ]);
    redirect('/my/tests');
}

function test_show(int $testId): void
{
    $test = tests_find_by_id($testId);

    if ($test === null) {
        http_response_code(404);
        view_render('404', [
            'title' => '404',
        ]);
        return;
    }

    if (($test['access_level'] ?? '') === 'registered' && !auth_is_logged_in()) {
        $_SESSION['redirect_to'] = '/tests/' . $testId;
        redirect('/login');
    }

    $viewerId = null;
    if (auth_is_logged_in()) {
        $viewer = auth_user();
        $viewerId = (int)($viewer['id'] ?? 0);
    }

    try {
        test_views_track($testId, $viewerId);
    } catch (Throwable $e) {
        // Не блокируем просмотр теста из-за ошибки счётчика.
    }

    $questionsCount = questions_count_by_test_id($testId);
    $ratingCount = (int)($test['rating_count'] ?? 0);
    $ratingSum = (int)($test['rating_sum'] ?? 0);
    $ratingAvg = $ratingCount > 0 ? ($ratingSum / $ratingCount) : 0.0;
    $userRating = null;
    $canRate = false;
    if ($viewerId !== null && $viewerId > 0) {
        $userRating = test_rating_find_by_test_id_and_user_id($testId, $viewerId);
        $canRate = attempts_has_finished_by_test_id_and_user_id($testId, $viewerId);
    }

    view_render('test_show', [
        'title' => (string)($test['title'] ?? 'Тест'),
        'test' => $test,
        'questions_count' => $questionsCount,
        'rating_count' => $ratingCount,
        'rating_avg' => $ratingAvg,
        'user_rating' => $userRating,
        'can_rate' => $canRate,
        'styles' => ['/assets/css/test-show.css'],
		'scripts' => ['/assets/js/copy-link.js', '/assets/js/test-rating.js'],
    ]);
}

function my_tests_trash_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 10;
    $total = tests_trash_count_by_user_id($userId);
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_trash_list_by_user_id_paginated($userId, $perPage, $offset);

    view_render('my_tests_trash', [
        'title' => 'Корзина',
        'tests' => $tests,
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ],
        'styles' => ['/assets/css/my-tests.css', '/assets/css/my-tests-trash.css'],
    ]);
}

function my_tests_restore(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    try {
        $restored = tests_restore_by_id_and_user_id($testId, $userId);
    } catch (Throwable $e) {
        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось восстановить тест. Попробуйте ещё раз.',
        ]);
        return;
    }

    if (!$restored) {
        http_response_code(403);
        view_render('error', [
            'title' => 'Ошибка 403',
            'message' => 'Нельзя восстановить этот тест (нет прав или тест не найден).',
        ]);
        return;
    }

    flash_set('toast', ['type' => 'success', 'text' => 'Тест восстановлен']);
    redirect('/my/tests/trash');
}

function my_tests_destroy(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    try {
        $deleted = tests_destroy_by_id_and_user_id($testId, $userId);
    } catch (Throwable $e) {
        http_response_code(409);
        view_render('error', [
            'title' => 'Нельзя удалить тест',
            'message' => 'Тест связан с историей прохождений. Сначала обновите ограничения БД для безопасного удаления.',
        ]);
        return;
    }

    if (!$deleted) {
        http_response_code(403);
        view_render('error', [
            'title' => 'Ошибка 403',
            'message' => 'Нельзя удалить этот тест навсегда (нет прав или тест не найден).',
        ]);
        return;
    }

    flash_set('toast', ['type' => 'success', 'text' => 'Тест удалён навсегда']);
    redirect('/my/tests/trash');
}

function my_tests_trash_restore_all(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    try {
        $count = tests_trash_restore_all_by_user_id($userId);
    } catch (Throwable $e) {
        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось восстановить тесты из корзины. Попробуйте ещё раз.',
        ]);
        return;
    }

    flash_set('toast', ['type' => 'success', 'text' => "Восстановлено: {$count}"]);
    redirect('/my/tests/trash');
}

function my_tests_trash_empty(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    try {
        $count = tests_trash_empty_by_user_id($userId);
    } catch (Throwable $e) {
        http_response_code(409);
        view_render('error', [
            'title' => 'Нельзя очистить корзину',
            'message' => 'Часть тестов связана с историей прохождений. Сначала обновите ограничения БД для безопасного удаления.',
        ]);
        return;
    }

    flash_set('toast', ['type' => 'success', 'text' => "Удалено навсегда: {$count}"]);
    redirect('/my/tests/trash');
}

function my_tests_upload_image(): void
{
    auth_required();

    header('Content-Type: application/json; charset=utf-8');

    $type = trim((string)($_POST['image_type'] ?? ''));
    if (!in_array($type, ['cover', 'question', 'option'], true)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Неверный тип изображения'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $file = $_FILES['image'] ?? null;
    if ($file === null) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Файл не получен'], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $webPath = upload_image($file, $type);
    } catch (RuntimeException $e) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode(['ok' => true, 'path' => $webPath], JSON_UNESCAPED_UNICODE);
}
