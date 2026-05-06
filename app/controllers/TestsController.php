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
                    'is_correct' => ($type === 'order') ? 1 : (int)($opt['is_correct'] ?? 0),
                    'image_path' => ($type === 'order') ? null : ($opt['image_path'] ?? null),
                ];
            }
        }

        $oldQuestions[] = $item;
    }

    return [
        'id' => (int)($test['id'] ?? 0),
        'title' => (string)($test['title'] ?? ''),
        'description' => (string)($test['description'] ?? ''),
        'access_level' => (string)($test['access_level'] ?? 'public'),
        'status' => (string)($test['status'] ?? test_status_published()),
        'published_at' => (string)($test['published_at'] ?? ''),
        'last_saved_at' => (string)($test['last_saved_at'] ?? ''),
        'category_names' => test_category_display_names($test['category_names'] ?? ($test['category_name'] ?? null)),
        'time_limit' => format_time_limit_hms(test_time_limit_sec_from_row($test)),
        'cover_image' => $test['cover_image'] ?? null,
        'show_answers' => test_answers_mode_from_value($test['show_answers'] ?? test_answers_mode_after_finish()),
        'shuffle_questions' => test_bool_setting($test['shuffle_questions'] ?? 0),
        'shuffle_answers' => test_bool_setting($test['shuffle_answers'] ?? 0),
        'attempt_limit' => test_attempt_limit_from_row($test),
        'questions' => $oldQuestions,
    ];
}

function tests_request_is_ajax(): bool
{
    return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
}

function tests_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function tests_normalize_upload_path(mixed $raw): ?string
{
    $value = trim((string)$raw);
    if ($value === '' || !str_starts_with($value, '/uploads/')) {
        return null;
    }

    return $value;
}

function tests_collect_form_payload(array $source, bool $strictValidation = true): array
{
    $errors = [];
    $maxInputAnswerLen = 1000;

    $title = trim((string)($source['title'] ?? ''));
    if ($strictValidation) {
        if ($title === '') {
            $errors[] = 'Название теста обязательно';
        } elseif (mb_strlen($title) < 10 || mb_strlen($title) > 200) {
            $errors[] = 'Название теста должно быть от 10 до 200 символов';
        }
    } elseif (mb_strlen($title) > 200) {
        $title = mb_substr($title, 0, 200);
    }

    $accessLevel = (string)($source['access_level'] ?? 'public');
    if (!in_array($accessLevel, ['public', 'registered'], true)) {
        if ($strictValidation) {
            $errors[] = 'Некорректный уровень доступа теста';
        }
        $accessLevel = 'public';
    }

    $description = str_replace(["\r\n", "\r"], "\n", trim((string)($source['description'] ?? '')));
    $descLen = mb_strlen($description);
    if ($strictValidation) {
        if ($descLen < 30 || $descLen > 500) {
            $errors[] = 'Описание теста должно быть не меньше 30 символов и не больше 500 символов';
        }
    } elseif ($descLen > 500) {
        $description = mb_substr($description, 0, 500);
    }

    $categoryNames = [];
    try {
        $categoryNames = test_category_names_from_input($source['category_names'] ?? null);
    } catch (InvalidArgumentException $e) {
        if ($strictValidation) {
            $errors[] = $e->getMessage();
        }
    }
    if ($strictValidation && $categoryNames === []) {
        $errors[] = 'Выберите хотя бы одну категорию';
    }

    $timeLimitSec = null;
    try {
        $timeLimitSec = normalize_test_time_limit_sec($source['time_limit'] ?? null);
    } catch (InvalidArgumentException $e) {
        if ($strictValidation) {
            $errors[] = $e->getMessage();
        }
    }

    $attemptLimit = null;
    try {
        $attemptLimit = normalize_test_attempt_limit($source['attempt_limit'] ?? null, $strictValidation);
    } catch (InvalidArgumentException $e) {
        if ($strictValidation) {
            $errors[] = $e->getMessage();
        }
    }

    $rawQuestions = $source['questions'] ?? [];
    if (!is_array($rawQuestions)) {
        $rawQuestions = [];
    }

    $maxQuestions = 100;
    $maxOptions = 10;
    $maxInputAnswers = 10;

    if ($strictValidation && count($rawQuestions) < 1) {
        $errors[] = 'Добавь хотя бы один вопрос';
    }
    if (count($rawQuestions) > $maxQuestions) {
        if ($strictValidation) {
            $errors[] = "Слишком много вопросов: максимум {$maxQuestions}";
        }
        $rawQuestions = array_slice($rawQuestions, 0, $maxQuestions);
    }

    $questions = [];

    $rawQuestionsCount = count($rawQuestions);
    foreach ($rawQuestions as $i => $q) {
        $num = $i + 1;
        if (!is_array($q)) {
            if ($strictValidation) {
                $errors[] = "Вопрос #{$num}: некорректные данные";
            }
            continue;
        }

        $qText = trim((string)($q['text'] ?? ''));
        $qType = (string)($q['type'] ?? 'radio');
        if (!in_array($qType, ['radio', 'checkbox', 'input', 'order'], true)) {
            if ($strictValidation) {
                $errors[] = "Вопрос #{$num}: неверный тип вопроса";
            }
            $qType = 'radio';
        }

        $question = [
            'text' => $qText,
            'type' => $qType,
            'image_path' => tests_normalize_upload_path($q['image_path'] ?? null),
            'options' => [],
            'answers' => [],
        ];

        if ($strictValidation) {
            $qLen = mb_strlen($qText);
            if ($qText === '') {
                $errors[] = "Вопрос #{$num}: текст вопроса обязателен";
                continue;
            }
            if ($qLen < 5 || $qLen > 1000) {
                $errors[] = "Вопрос #{$num}: текст вопроса должен быть от 5 до 1000 символов";
                continue;
            }
        } elseif (mb_strlen($qText) > 1000) {
            $question['text'] = mb_substr($qText, 0, 1000);
        }

        if ($qType === 'input') {
            $answers = $q['answers'] ?? [];
            if (!is_array($answers)) {
                $answers = [];
            }

            $answers = array_values(array_filter(array_map(
                static fn($a): string => trim((string)$a),
                $answers
            ), static fn(string $a): bool => $a !== ''));

            if (count($answers) > $maxInputAnswers) {
                if ($strictValidation) {
                    $errors[] = "Вопрос #{$num}: максимум {$maxInputAnswers} текстовых ответов";
                    continue;
                }
                $answers = array_slice($answers, 0, $maxInputAnswers);
            }

            if ($strictValidation) {
                if (count($answers) < 1) {
                    $errors[] = "Вопрос #{$num}: укажи хотя бы один правильный текстовый ответ";
                    continue;
                }

                $seenAnswers = [];
                foreach ($answers as $a) {
                    $norm = normalize_input_answer($a);
                    if ($norm === '') {
                        continue;
                    }
                    if (isset($seenAnswers[$norm])) {
                        $errors[] = "Вопрос #{$num}: текстовые ответы не должны повторяться (регистр/пробелы/ё→е)";
                        continue 2;
                    }
                    $seenAnswers[$norm] = true;
                }
            }

            foreach ($answers as $a) {
                $aLen = mb_strlen($a);
                if ($strictValidation && ($aLen < 1 || $aLen > $maxInputAnswerLen)) {
                    $errors[] = "Вопрос #{$num}: текстовый ответ должен быть от 1 до {$maxInputAnswerLen} символов";
                    continue 2;
                }
                if (!$strictValidation && $aLen > $maxInputAnswerLen) {
                    $a = mb_substr($a, 0, $maxInputAnswerLen);
                }
                $question['answers'][] = $a;
            }
        } else {
            $options = $q['options'] ?? [];
            if (!is_array($options)) {
                $options = [];
            }

            $normalizedOptions = [];
            foreach (array_slice(array_values($options), 0, $maxOptions) as $opt) {
                if (!is_array($opt)) {
                    continue;
                }

                $normalizedOptions[] = [
                    'text' => trim((string)($opt['text'] ?? '')),
                    'is_correct' => ($qType === 'order') ? 1 : ((int)($opt['is_correct'] ?? 0) === 1 ? 1 : 0),
                    'image_path' => tests_normalize_upload_path($opt['image_path'] ?? null),
                ];
            }

            if ($strictValidation) {
                $normalizedOptions = array_values(array_filter(
                    $normalizedOptions,
                    static fn(array $opt): bool => $opt['text'] !== ''
                ));

                if (count($normalizedOptions) < 2) {
                    $errors[] = "Вопрос #{$num}: минимум два варианта ответа";
                    continue;
                }

                $seen = [];
                foreach ($normalizedOptions as $opt) {
                    $t = preg_replace('/\s+/u', ' ', $opt['text']) ?? $opt['text'];
                    $t = mb_strtolower(trim($t));
                    if ($t === '') {
                        continue;
                    }
                    if (isset($seen[$t])) {
                        $errors[] = "Вопрос #{$num}: варианты ответа не должны повторяться";
                        continue 2;
                    }
                    $seen[$t] = true;
                }

                $correctCount = 0;
                foreach ($normalizedOptions as $opt) {
                    $len = mb_strlen($opt['text']);
                    if ($len < 1 || $len > 1000) {
                        $errors[] = "Вопрос #{$num}: вариант ответа должен быть от 1 до 1000 символов";
                        continue 2;
                    }
                    if ((int)$opt['is_correct'] === 1) {
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
                // For 'order' type, no correctCount check — all options are part of the sequence
            } else {
                foreach ($normalizedOptions as &$opt) {
                    if (mb_strlen($opt['text']) > 1000) {
                        $opt['text'] = mb_substr($opt['text'], 0, 1000);
                    }
                }
                unset($opt);
            }

            $question['options'] = $normalizedOptions;
        }

        if (!$strictValidation) {
            $hasOptionContent = false;
            foreach ((array)$question['options'] as $opt) {
                if (!is_array($opt)) {
                    continue;
                }
                if (trim((string)($opt['text'] ?? '')) !== '' || (int)($opt['is_correct'] ?? 0) === 1 || !empty($opt['image_path'])) {
                    $hasOptionContent = true;
                    break;
                }
            }

            $shouldKeepDraftQuestion =
                trim((string)$question['text']) !== ''
                || !empty($question['image_path'])
                || !empty($question['answers'])
                || $hasOptionContent
                || (string)$question['type'] !== 'radio'
                || $rawQuestionsCount > 1;

            if (!$shouldKeepDraftQuestion) {
                continue;
            }
        }

        $questions[] = $question;
    }

    $showAnswers = test_answers_mode_from_value($source['show_answers'] ?? test_answers_mode_after_finish());
    $shuffleQuestions = test_bool_setting($source['shuffle_questions'] ?? 0);
    $shuffleAnswers = test_bool_setting($source['shuffle_answers'] ?? 0);

    return [
        'payload' => [
            'title' => $title,
            'description' => $description,
            'access_level' => $accessLevel,
            'category_names' => $categoryNames,
            'time_limit_sec' => $timeLimitSec,
            'cover_image' => tests_normalize_upload_path($source['cover_image'] ?? null),
            'show_answers' => $showAnswers,
            'shuffle_questions' => $shuffleQuestions,
            'shuffle_answers' => $shuffleAnswers,
            'attempt_limit' => $attemptLimit,
            'questions' => $questions,
        ],
        'errors' => $errors,
    ];
}

function tests_payload_has_meaningful_content(array $payload, array $source): bool
{
    if (trim((string)($payload['title'] ?? '')) !== '') {
        return true;
    }
    if (trim((string)($payload['description'] ?? '')) !== '') {
        return true;
    }
    if (($payload['access_level'] ?? 'public') !== 'public') {
        return true;
    }
    if (!empty($payload['category_names'])) {
        return true;
    }
    if (($payload['time_limit_sec'] ?? null) !== null) {
        return true;
    }
    if (($payload['show_answers'] ?? test_answers_mode_after_finish()) !== test_answers_mode_after_finish()) {
        return true;
    }
    if ((int)($payload['shuffle_questions'] ?? 0) === 1 || (int)($payload['shuffle_answers'] ?? 0) === 1) {
        return true;
    }
    if (($payload['attempt_limit'] ?? null) !== null) {
        return true;
    }
    if (($payload['cover_image'] ?? null) !== null) {
        return true;
    }
    if (!empty($payload['questions'])) {
        return true;
    }

    $rawQuestions = $source['questions'] ?? [];
    if (is_array($rawQuestions) && count($rawQuestions) > 1) {
        return true;
    }

    return false;
}

function tests_replace_questions_payload(int $testId, array $questions): void
{
    questions_delete_by_test_id($testId);

    foreach (array_values($questions) as $qIndex => $q) {
        $questionId = questions_create(
            $testId,
            (string)($q['type'] ?? 'radio'),
            trim((string)($q['text'] ?? '')),
            $qIndex + 1,
            tests_normalize_upload_path($q['image_path'] ?? null)
        );

        if ((string)($q['type'] ?? 'radio') === 'input') {
            foreach ((array)($q['answers'] ?? []) as $answerText) {
                $answerText = trim((string)$answerText);
                if ($answerText === '') {
                    continue;
                }
                question_text_answers_create($questionId, $answerText);
            }
            continue;
        }

        foreach ((array)($q['options'] ?? []) as $optIndex => $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $optText = trim((string)($opt['text'] ?? ''));
            $optImage = tests_normalize_upload_path($opt['image_path'] ?? null);
            $isCorrect = (int)($opt['is_correct'] ?? 0) === 1 ? 1 : 0;
            if ($optText === '' && $optImage === null && $isCorrect !== 1) {
                continue;
            }
            options_create(
                $questionId,
                $optText,
                $isCorrect,
                $optIndex + 1,
                $optImage
            );
        }
    }
}

function tests_render_form_with_errors(string $title, array $errors, array $old, bool $isEdit = false, ?int $testId = null, string $submitLabel = 'Опубликовать'): void
{
    view_render('test_create', [
        'title' => $title,
        'styles' => ['/assets/css/test-create.css'],
        'errors' => $errors,
        'old' => $old,
        'is_edit' => $isEdit,
        'form_action' => $testId !== null ? '/my/tests/' . $testId : '/my/tests',
        'submit_label' => $submitLabel,
    ]);
}

function my_tests_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int) ($user['id'] ?? 0);
    $categorySlug = test_category_canonical_slug((string)($_GET['category'] ?? ''));
    $categoryName = $categorySlug !== '' ? test_category_slug_to_name($categorySlug) : null;
    if ($categoryName === null) {
        $categorySlug = '';
    }
    $sort = (string)($_GET['sort'] ?? 'new');
    $sortOptions = [
        'new' => 'Новые',
        'questions' => 'По вопросам',
        'time' => 'По времени',
        'views' => 'По просмотрам',
        'attempts' => 'По прохождениям',
    ];
    if (!isset($sortOptions[$sort])) {
        $sort = 'new';
    }

    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 10;
    $allTotal = tests_count_active_by_user_id($userId);
    $total = $categoryName !== null ? tests_count_active_by_user_id($userId, $categoryName) : $allTotal;
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_list_by_user_id_paginated($userId, $perPage, $offset, $categoryName, $sort);
    $pagerQuery = [];
    if ($categorySlug !== '') {
        $pagerQuery['category'] = $categorySlug;
    }
    if ($sort !== 'new') {
        $pagerQuery['sort'] = $sort;
    }

    view_render('my_tests', [
        'title' => 'Мои тесты',
        'tests' => $tests,
        'filter_action' => '/my/tests',
        'filter_id' => 'my_tests',
        'home_total' => $allTotal,
        'category_options' => test_categories_catalog(),
        'category_counts' => tests_category_counts_by_user_id($userId),
        'selected_category_slug' => $categorySlug,
        'sort_options' => $sortOptions,
        'selected_sort' => $sort,
		'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'path' => '/my/tests',
            'query' => $pagerQuery,
        ],
		'scripts' => ['/assets/js/list-loading.js', '/assets/js/my-tests-share.js', '/assets/js/home-category-filter.js'],
		'styles' => ['/assets/css/my-tests.css'],
    ]);
}

function my_tests_create_form(): void
{
    auth_required();

    view_render('test_create', [
        'title' => 'Создать тест',
        'styles' => ['/assets/css/test-create.css'],
        'submit_label' => 'Опубликовать',
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
        'submit_label' => tests_is_draft_row($test) ? 'Опубликовать' : 'Сохранить изменения',
        'old' => $old,
    ]);
}

function my_tests_draft_save(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    $pdo = db();

    try {
        $normalized = tests_collect_form_payload($_POST, false);
        $payload = $normalized['payload'];

        if (!tests_payload_has_meaningful_content($payload, $_POST)) {
            tests_json_response([
                'ok' => true,
                'created' => false,
                'saved' => false,
                'message' => 'Черновик пока пустой',
            ]);
        }

        $pdo->beginTransaction();

        $testId = tests_create(
            $userId,
            (string)$payload['title'],
            (string)$payload['description'],
            (string)$payload['access_level'],
            (array)$payload['category_names'],
            $payload['cover_image'],
            $payload['time_limit_sec'],
            test_status_draft(),
            null,
            date('Y-m-d H:i:s'),
            (int)($payload['show_answers'] ?? 0),
            (int)($payload['shuffle_questions'] ?? 0),
            (int)($payload['shuffle_answers'] ?? 0),
            $payload['attempt_limit'] ?? null
        );
        test_categories_replace_by_test_id($testId, (array)$payload['category_names']);
        tests_replace_questions_payload($testId, (array)$payload['questions']);

        $pdo->commit();
        tests_payload_cache_invalidate($testId);

        tests_json_response([
            'ok' => true,
            'created' => true,
            'saved' => true,
            'test_id' => $testId,
            'status' => test_status_draft(),
            'edit_url' => '/my/tests/' . $testId . '/edit',
            'draft_url' => '/my/tests/' . $testId . '/draft',
            'message' => 'Черновик сохранён',
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log_exception(app_error_id(), $e);

        tests_json_response([
            'ok' => false,
            'message' => 'Не удалось сохранить черновик. Попробуйте ещё раз.',
        ], 500);
    }
}

function my_tests_draft_update(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    $existingTest = tests_find_active_by_id_and_user_id($testId, $userId);
    if ($existingTest === null) {
        tests_json_response([
            'ok' => false,
            'message' => 'Черновик не найден',
        ], 404);
    }

    if (!tests_is_draft_row($existingTest)) {
        tests_json_response([
            'ok' => false,
            'message' => 'Сохранять черновик можно только для черновика',
        ], 409);
    }

    $pdo = db();

    try {
        $normalized = tests_collect_form_payload($_POST, false);
        $payload = $normalized['payload'];

        $pdo->beginTransaction();
        tests_update_by_id_and_user_id(
            $testId,
            $userId,
            (string)$payload['title'],
            (string)$payload['description'],
            (string)$payload['access_level'],
            (array)$payload['category_names'],
            $payload['cover_image'],
            $payload['time_limit_sec'],
            test_status_draft(),
            null,
            true,
            (int)($payload['show_answers'] ?? 0),
            (int)($payload['shuffle_questions'] ?? 0),
            (int)($payload['shuffle_answers'] ?? 0),
            $payload['attempt_limit'] ?? null
        );
        test_categories_replace_by_test_id($testId, (array)$payload['category_names']);
        tests_replace_questions_payload($testId, (array)$payload['questions']);

        $pdo->commit();
        tests_payload_cache_invalidate($testId);

        tests_json_response([
            'ok' => true,
            'saved' => true,
            'test_id' => $testId,
            'status' => test_status_draft(),
            'edit_url' => '/my/tests/' . $testId . '/edit',
            'draft_url' => '/my/tests/' . $testId . '/draft',
            'message' => 'Черновик сохранён',
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log_exception(app_error_id(), $e);

        tests_json_response([
            'ok' => false,
            'message' => 'Не удалось сохранить черновик. Попробуйте ещё раз.',
        ], 500);
    }
}

function my_tests_store(): void
{
    auth_required();

    $normalized = tests_collect_form_payload($_POST, true);
    $payload = $normalized['payload'];
    $errors = $normalized['errors'];

    if (!empty($errors)) {
        tests_render_form_with_errors('Создать тест', $errors, [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'access_level' => $_POST['access_level'] ?? 'public',
            'category_names' => $_POST['category_names'] ?? [],
            'time_limit' => $_POST['time_limit'] ?? '',
            'cover_image' => $_POST['cover_image'] ?? null,
            'show_answers' => test_answers_mode_from_value($_POST['show_answers'] ?? test_answers_mode_after_finish()),
            'shuffle_questions' => test_bool_setting($_POST['shuffle_questions'] ?? 0),
            'shuffle_answers' => test_bool_setting($_POST['shuffle_answers'] ?? 0),
            'attempt_limit' => normalize_test_attempt_limit($_POST['attempt_limit'] ?? null, false),
            'questions' => $_POST['questions'] ?? [],
            'status' => test_status_draft(),
        ], false, null, 'Опубликовать');
        return;
    }

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $testId = tests_create(
            $userId,
            (string)$payload['title'],
            (string)$payload['description'],
            (string)$payload['access_level'],
            (array)$payload['category_names'],
            $payload['cover_image'],
            $payload['time_limit_sec'],
            test_status_published(),
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            (int)($payload['show_answers'] ?? 0),
            (int)($payload['shuffle_questions'] ?? 0),
            (int)($payload['shuffle_answers'] ?? 0),
            $payload['attempt_limit'] ?? null
        );
        test_categories_replace_by_test_id($testId, (array)$payload['category_names']);
        tests_replace_questions_payload($testId, (array)$payload['questions']);

        $pdo->commit();
        tests_payload_cache_invalidate($testId);
        flash_set('toast', ['type' => 'success', 'text' => 'Тест опубликован']);
        redirect('/my/tests');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось опубликовать тест. Попробуйте ещё раз.',
        ]);
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

    $normalized = tests_collect_form_payload($_POST, true);
    $payload = $normalized['payload'];
    $errors = $normalized['errors'];

    if (!empty($errors)) {
        tests_render_form_with_errors('Редактировать тест', $errors, [
            'id' => $testId,
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'access_level' => $_POST['access_level'] ?? 'public',
            'category_names' => $_POST['category_names'] ?? [],
            'time_limit' => $_POST['time_limit'] ?? '',
            'cover_image' => $_POST['cover_image'] ?? null,
            'show_answers' => test_answers_mode_from_value($_POST['show_answers'] ?? test_answers_mode_after_finish()),
            'shuffle_questions' => test_bool_setting($_POST['shuffle_questions'] ?? 0),
            'shuffle_answers' => test_bool_setting($_POST['shuffle_answers'] ?? 0),
            'attempt_limit' => normalize_test_attempt_limit($_POST['attempt_limit'] ?? null, false),
            'questions' => $_POST['questions'] ?? [],
            'status' => (string)($existingTest['status'] ?? test_status_published()),
            'published_at' => (string)($existingTest['published_at'] ?? ''),
            'last_saved_at' => (string)($existingTest['last_saved_at'] ?? ''),
        ], true, $testId, tests_is_draft_row($existingTest) ? 'Опубликовать' : 'Сохранить изменения');
        return;
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();

        $publishedAt = tests_is_draft_row($existingTest)
            ? date('Y-m-d H:i:s')
            : ((string)($existingTest['published_at'] ?? '') !== '' ? (string)$existingTest['published_at'] : date('Y-m-d H:i:s'));

        tests_update_by_id_and_user_id(
            $testId,
            $userId,
            (string)$payload['title'],
            (string)$payload['description'],
            (string)$payload['access_level'],
            (array)$payload['category_names'],
            $payload['cover_image'],
            $payload['time_limit_sec'],
            test_status_published(),
            $publishedAt,
            true,
            (int)($payload['show_answers'] ?? 0),
            (int)($payload['shuffle_questions'] ?? 0),
            (int)($payload['shuffle_answers'] ?? 0),
            $payload['attempt_limit'] ?? null
        );
        test_categories_replace_by_test_id($testId, (array)$payload['category_names']);
        tests_replace_questions_payload($testId, (array)$payload['questions']);

        $pdo->commit();
        tests_payload_cache_invalidate($testId);
        flash_set('toast', [
            'type' => 'success',
            'text' => tests_is_draft_row($existingTest) ? 'Черновик опубликован' : 'Тест обновлён',
        ]);
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

    // Проверяем незавершённую попытку для этого теста (из сессии)
    $hasActiveAttempt = false;
    $activeAttemptSessionId = (int)($_SESSION['active_attempt_id_by_test'][$testId] ?? 0);
    if ($activeAttemptSessionId > 0) {
        $candidateAttempt = attempt_find_by_id($activeAttemptSessionId);
        if ($candidateAttempt !== null
            && (int)($candidateAttempt['test_id'] ?? 0) === $testId
            && ($candidateAttempt['finished_at'] ?? null) === null
            && !attempt_is_expired($candidateAttempt)
        ) {
            $hasActiveAttempt = true;
        }
    }

    view_render('test_show', [
        'title' => (string)($test['title'] ?? 'Тест'),
        'test' => $test,
        'questions_count' => $questionsCount,
        'rating_count' => $ratingCount,
        'rating_avg' => $ratingAvg,
        'user_rating' => $userRating,
        'can_rate' => $canRate,
        'has_active_attempt' => $hasActiveAttempt,
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
        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось удалить тест навсегда. Попробуйте ещё раз.',
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
