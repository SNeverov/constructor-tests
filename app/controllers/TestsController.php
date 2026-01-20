<?php
declare(strict_types=1);

function normalize_input_answer(string $s): string
{
    $s = trim($s);
    if ($s === '') {
        return '';
    }

    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    $s = mb_strtolower($s);
    $s = str_replace('ё', 'е', $s);

    return $s;
}


function my_tests_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int) ($user['id'] ?? 0);

    $tests = tests_list_by_user_id($userId);

    view_render('my_tests', [
        'title' => 'Мои тесты',
        'tests' => $tests,
		'scripts' => ['/assets/js/copy-link.js'],
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

function my_tests_store(): void
{

    auth_required();

    $errors = [];

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

                // дедуп по нормализованному виду
                $seenAnswers = [];
                foreach ($answers as $a) {
                    $norm = normalize_input_answer((string) $a);

                    if ($norm === '') {
                        continue;
                    }

                    if (isset($seenAnswers[$norm])) {
                        $errors[] = "Вопрос #{$num}: текстовые ответы не должны повторяться (регистр/пробелы/ё→е)";
                        continue 2; // сразу к следующему вопросу
                    }

                    $seenAnswers[$norm] = true;
                }



                foreach ($answers as $a) {
                    $aText = trim((string)$a);
                    $aLen = mb_strlen($aText);

                    if ($aLen < 1 || $aLen > 200) {
                        $errors[] = "Вопрос #{$num}: текстовый ответ должен быть от 1 до 200 символов";
                        break;
                    }
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
                        continue 2; // выйти из foreach и сразу к следующему вопросу
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
                'questions'    => $_POST['questions'] ?? [],
            ],
        ]);
        exit();
    }



    $user = auth_user();
    $userId = (int) $user['id'];

    $title = trim($_POST['title'] ?? '');



    $testId = tests_create($userId, $title, $description, $accessLevel);

    // Отправка вопросов в БД
    $questions = array_values($questions);


    foreach ($questions as $qIndex => $q) {
        if (!is_array($q)) {
            continue;
        }

        $qType = (string)($q['type'] ?? '');
        $qText = trim((string)($q['text'] ?? ''));
        $qPos  = $qIndex + 1;

        $questionId = questions_create($testId, $qType, $qText, $qPos);

        // 19.2 — варианты (radio/checkbox)
        if ($qType === 'radio' || $qType === 'checkbox') {
            $options = $q['options'] ?? [];
            if (!is_array($options)) {
                $options = [];
            }

            // убираем пустые варианты
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

                options_create($questionId, $optText, $isCorrect, $pos);
            }
        }

        // 19.3 — текстовые ответы (input)
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


    // временно: чтобы убедиться, что question_id реально создаётся
    // echo "test_id={$testId}, question_id={$questionId}"; exit;

    header('Location: /my/tests');
    exit();

}

function my_tests_delete(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int) ($user['id'] ?? 0);

    $deleted = tests_delete_by_id_and_user_id($testId, $userId);

    if (!$deleted) {
        http_response_code(403);
        view_render('error', [
            'title' => 'Ошибка 403',
            'message' => 'Нельзя удалить этот тест (нет прав или тест не найден).',
        ]);
        return;
    }

	flash_set('toast', ['type' => 'success', 'text' => 'Тест отправлен в корзину']);
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

    $questionsCount = questions_count_by_test_id($testId);

    view_render('test_show', [
        'title' => (string)($test['title'] ?? 'Тест'),
        'test' => $test,
        'questions_count' => $questionsCount,
        'styles' => ['/assets/css/test-show.css'],
		'scripts' => ['/assets/js/copy-link.js'],
    ]);
}

function test_pass(int $testId): void
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
        $_SESSION['redirect_to'] = '/tests/' . $testId . '/pass';
        redirect('/login');
    }

    $questions = questions_list_by_test_id($testId);

    $questionIds = [];
    foreach ($questions as $q) {
        $questionIds[] = (int)($q['id'] ?? 0);
    }

	$userId = null;
	if (auth_is_logged_in()) {
		$u = auth_user();
		$userId = isset($u['id']) ? (int)$u['id'] : null;
	}

	$attemptId = 0;

	// Переиспользуем “активную” попытку в рамках сессии, чтобы F5 не плодил attempts
	if (isset($_SESSION['active_attempt_id_by_test'][$testId])) {
		$candidateId = (int)$_SESSION['active_attempt_id_by_test'][$testId];
		$candidate = attempt_find_by_id($candidateId);

		$candidateOk = ($candidate !== null)
			&& (int)($candidate['test_id'] ?? 0) === $testId
			&& (($candidate['finished_at'] ?? null) === null);

		if ($candidateOk) {
			$candidateUserId = $candidate['user_id'] ?? null;

			if ($userId === null) {
				if ($candidateUserId === null) {
					$attemptId = $candidateId;
				}
			} else {
				if ((int)$candidateUserId === $userId) {
					$attemptId = $candidateId;
				}
			}
		}
	}

	if ($attemptId === 0) {
		$attemptId = attempt_create($testId, $userId);
		$_SESSION['active_attempt_id_by_test'][$testId] = $attemptId;
	}


    $optionsByQuestionId = options_list_by_question_ids($questionIds);

    view_render('test_pass', [
        'title' => (string)($test['title'] ?? 'Прохождение теста'),
        'test' => $test,
        'questions' => $questions,
        'optionsByQuestionId' => $optionsByQuestionId,
		'attemptId' => $attemptId,
        'styles' => ['/assets/css/test-pass.css'],
		'scripts' => [
			'/assets/js/test-pass.js',
			'/assets/js/test-pass-guard.js',
			'/assets/js/copy-link.js',
],

    ]);
}

function test_finish(int $testId): void
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

    $userId = null;
    if (auth_is_logged_in()) {
        $u = auth_user();
        $userId = isset($u['id']) ? (int)$u['id'] : null;
    }

    $pdo = db();

    try {
        $pdo->beginTransaction();

        $attemptId = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;

		if ($attemptId > 0) {
			$attempt = attempt_find_by_id($attemptId);

			if ($attempt === null || (int)($attempt['test_id'] ?? 0) !== $testId) {
				throw new RuntimeException('Invalid attempt_id');
			}

			// привязка к юзеру (чтобы нельзя было подсунуть чужую попытку)
			$attemptUserId = $attempt['user_id'] ?? null;
			if ($userId === null) {
				if ($attemptUserId !== null) {
					throw new RuntimeException('Invalid attempt owner');
				}
			} else {
				if ((int)$attemptUserId !== $userId) {
					throw new RuntimeException('Invalid attempt owner');
				}
			}
		} else {
			$attemptId = attempt_create($testId, $userId);
		}


        $questions = questions_list_by_test_id($testId);
        $questionIds = [];
        foreach ($questions as $q) {
            $questionIds[] = (int)($q['id'] ?? 0);
        }

        $correctOptionIdsByQ = options_correct_ids_by_question_ids($questionIds);
        $correctTextAnswersByQ = text_answers_by_question_ids($questionIds);

        $posted = $_POST['answers'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }

        $total = count($questions);
        $correctCount = 0;
        $wrongCount = 0;

        $answerRows = [];

        foreach ($questions as $q) {
            $qid = (int)($q['id'] ?? 0);
            $type = (string)($q['type'] ?? 'radio');

            $isCorrect = false;

            if ($type === 'input') {
                $userTextRaw = '';
                if (isset($posted[$qid]) && !is_array($posted[$qid])) {
                    $userTextRaw = (string)$posted[$qid];
                }

                $userNorm = normalize_input_answer($userTextRaw);

                $variants = $correctTextAnswersByQ[$qid] ?? [];
                $variantsNorm = [];
                foreach ($variants as $v) {
                    $variantsNorm[] = normalize_input_answer((string)$v);
                }

                $isCorrect = ($userNorm !== '') && in_array($userNorm, $variantsNorm, true);

                // сохраняем текстовый ответ одной строкой
                $answerRows[] = [
                    'question_id' => $qid,
                    'option_id' => null,
                    'text_answer' => $userTextRaw,
                ];
            } elseif ($type === 'checkbox') {
                $userOptIds = [];
                if (isset($posted[$qid])) {
                    if (is_array($posted[$qid])) {
                        $userOptIds = array_values(array_filter(array_map('intval', $posted[$qid]), fn($v) => $v > 0));
                    } else {
                        $one = (int)$posted[$qid];
                        if ($one > 0) $userOptIds = [$one];
                    }
                }

                $correctIds = $correctOptionIdsByQ[$qid] ?? [];

                sort($userOptIds);
                $correctSorted = array_values(array_map('intval', $correctIds));
                sort($correctSorted);

                $isCorrect = (!empty($correctSorted) || !empty($userOptIds)) && ($userOptIds === $correctSorted);

                // сохраняем каждую галочку отдельной строкой
                foreach ($userOptIds as $oid) {
                    $answerRows[] = [
                        'question_id' => $qid,
                        'option_id' => $oid,
                        'text_answer' => null,
                    ];
                }
            } else { // radio по умолчанию
                $userOptId = 0;
                if (isset($posted[$qid]) && !is_array($posted[$qid])) {
                    $userOptId = (int)$posted[$qid];
                }

                $correctIds = $correctOptionIdsByQ[$qid] ?? [];
                $isCorrect = ($userOptId > 0) && in_array($userOptId, array_map('intval', $correctIds), true);

                // сохраняем выбранный option_id одной строкой (или 0 не пишем)
                if ($userOptId > 0) {
                    $answerRows[] = [
                        'question_id' => $qid,
                        'option_id' => $userOptId,
                        'text_answer' => null,
                    ];
                }
            }

            if ($isCorrect) {
                $correctCount++;
            } else {
                $wrongCount++;
            }
        }

        $percent = ($total > 0) ? round(($correctCount / $total) * 100, 2) : 0.0;

        answers_insert_batch($attemptId, $answerRows);
        attempt_finish_update($attemptId, $correctCount, $wrongCount, $percent);
		unset($_SESSION['active_attempt_id_by_test'][$testId]);

        $pdo->commit();

        redirect('/attempts/' . $attemptId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        view_render('error', [
            'title' => 'Ошибка',
            'message' => 'Не удалось сохранить результат прохождения теста.',
        ]);
        return;
    }
}

function attempt_show(int $attemptId): void
{
    $attempt = attempt_find_by_id($attemptId);

    if ($attempt === null) {
        http_response_code(404);
        view_render('404', [
            'title' => '404',
        ]);
        return;
    }

    $testId = (int)($attempt['test_id'] ?? 0);
    $test = tests_find_by_id($testId);

    if ($test === null) {
        http_response_code(404);
        view_render('404', [
            'title' => '404',
        ]);
        return;
    }

    // Доступ: если тест "только для зарегистрированных", то и результат смотреть только после входа
    if (($test['access_level'] ?? '') === 'registered' && !auth_is_logged_in()) {
        $_SESSION['redirect_to'] = '/attempts/' . $attemptId;
        redirect('/login');
    }

    $questions = questions_list_by_test_id($testId);
    $questionIds = [];
    foreach ($questions as $q) {
        $questionIds[] = (int)($q['id'] ?? 0);
    }

    $optionsByQuestionId = options_list_by_question_ids($questionIds); // без is_correct — нам тексты нужны
    $correctOptionIdsByQ = options_correct_ids_by_question_ids($questionIds);
    $correctTextAnswersByQ = text_answers_by_question_ids($questionIds);

    $userAnswers = answers_list_by_attempt_id($attemptId);

    // сгруппуем ответы пользователя по вопросу
    $userByQ = [];
    foreach ($userAnswers as $a) {
        $qid = (int)($a['question_id'] ?? 0);
        if ($qid <= 0) continue;
        $userByQ[$qid][] = $a;
    }

    view_render('attempt_show', [
        'title' => 'Результат: ' . (string)($test['title'] ?? 'Тест'),
        'attempt' => $attempt,
        'test' => $test,
        'questions' => $questions,
        'optionsByQuestionId' => $optionsByQuestionId,
        'correctOptionIdsByQ' => $correctOptionIdsByQ,
        'correctTextAnswersByQ' => $correctTextAnswersByQ,
        'userByQ' => $userByQ,
        'styles' => ['/assets/css/attempt-show.css'],
    ]);
}


function my_tests_trash_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    $tests = tests_trash_list_by_user_id($userId);

    view_render('my_tests_trash', [
        'title' => 'Корзина',
        'tests' => $tests,
        'styles' => ['/assets/css/my-tests.css', '/assets/css/my-tests-trash.css'],
    ]);
}

function my_tests_restore(int $testId): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    $restored = tests_restore_by_id_and_user_id($testId, $userId);

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

    $deleted = tests_destroy_by_id_and_user_id($testId, $userId);

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

    $count = tests_trash_restore_all_by_user_id($userId);

    flash_set('toast', ['type' => 'success', 'text' => "Восстановлено: {$count}"]);
    redirect('/my/tests/trash');
}

function my_tests_trash_empty(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    $count = tests_trash_empty_by_user_id($userId);

    flash_set('toast', ['type' => 'success', 'text' => "Удалено навсегда: {$count}"]);
    redirect('/my/tests/trash');
}
