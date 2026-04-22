<?php
declare(strict_types=1);

function expire_attempt_by_timeout(int $attemptId, int $testId): void
{
    $questions = questions_list_by_test_id($testId);
    $totalQuestions = count($questions);
    $testSnapshotHash = test_snapshot_hash_by_test_id($testId);

    attempt_finish_update(
        $attemptId,
        0,
        $totalQuestions,
        0.0,
        $totalQuestions,
        $testSnapshotHash,
        'expired'
    );
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

    $payload = tests_payload_for_pass_cached($testId);
    $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];
    $optionsByQuestionId = is_array($payload['options_by_question_id'] ?? null)
        ? $payload['options_by_question_id']
        : [];

	$userId = null;
	if (auth_is_logged_in()) {
		$u = auth_user();
		$userId = isset($u['id']) ? (int)$u['id'] : null;
	}

	$attemptId = 0;

	// Переиспользуем "активную" попытку в рамках сессии, чтобы F5 не плодил attempts
	if (isset($_SESSION['active_attempt_id_by_test'][$testId])) {
		$candidateId = (int)$_SESSION['active_attempt_id_by_test'][$testId];
		$candidate = attempt_find_by_id($candidateId);

		$candidateOk = ($candidate !== null)
			&& (int)($candidate['test_id'] ?? 0) === $testId
			&& (($candidate['finished_at'] ?? null) === null);

		if ($candidateOk) {
            if (attempt_is_expired($candidate)) {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $lockedAttempt = attempt_find_by_id_for_update($candidateId);
                    if ($lockedAttempt !== null && (int)($lockedAttempt['test_id'] ?? 0) === $testId && ($lockedAttempt['finished_at'] ?? null) === null && attempt_is_expired($lockedAttempt)) {
                        expire_attempt_by_timeout($candidateId, $testId);
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }

                unset($_SESSION['active_attempt_id_by_test'][$testId]);
                if ($userId === null) {
                    if (!isset($_SESSION['guest_attempt_ids']) || !is_array($_SESSION['guest_attempt_ids'])) {
                        $_SESSION['guest_attempt_ids'] = [];
                    }
                    $_SESSION['guest_attempt_ids'][] = $candidateId;
                    $_SESSION['guest_attempt_ids'] = array_values(array_unique(array_map('intval', $_SESSION['guest_attempt_ids'])));
                    if (count($_SESSION['guest_attempt_ids']) > 200) {
                        $_SESSION['guest_attempt_ids'] = array_slice($_SESSION['guest_attempt_ids'], -200);
                    }
                }
                flash_set('toast', ['type' => 'danger', 'text' => 'Время на прохождение истекло. Попытка завершена автоматически.']);
                redirect('/attempts/' . $candidateId);
                return;
            }

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

    $attempt = attempt_find_by_id($attemptId);
    $timeLimitSec = $attempt !== null ? (int)($attempt['time_limit_sec_snapshot'] ?? 0) : 0;
    $expiresAt = ($attempt !== null) ? trim((string)($attempt['expires_at'] ?? '')) : '';
    $remainingSec = null;
    if ($timeLimitSec > 0 && $expiresAt !== '') {
        $remainingSec = max(0, (int)floor((strtotime($expiresAt) ?: 0) - time()));
    }

    view_render('test_pass', [
        'title' => (string)($test['title'] ?? 'Прохождение теста'),
        'test' => $test,
        'questions' => $questions,
        'optionsByQuestionId' => $optionsByQuestionId,
		'attempt' => $attempt,
		'attemptId' => $attemptId,
        'timeLimitSec' => $timeLimitSec,
        'remainingSec' => $remainingSec,
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

    $MAX_INPUT_ANSWER_LEN = 1000;
    $pdo = db();

    try {
        $pdo->beginTransaction();

        $attemptId = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;

        if ($attemptId > 0) {
			$attempt = attempt_find_by_id_for_update($attemptId);

			if ($attempt === null || (int)($attempt['test_id'] ?? 0) !== $testId) {
				throw new RuntimeException('Invalid attempt_id');
			}

			// привязка к юзеру (чтобы нельзя было подсунуть чужую попытку)
			$attemptUserId = $attempt['user_id'] ?? null;
			if ($userId === null) {
                $sessionAttemptId = (int)($_SESSION['active_attempt_id_by_test'][$testId] ?? 0);
                if ($sessionAttemptId !== $attemptId) {
                    throw new RuntimeException('Invalid guest attempt session binding');
                }
				if ($attemptUserId !== null) {
					throw new RuntimeException('Invalid attempt owner');
				}
			} else {
				if ((int)$attemptUserId !== $userId) {
					throw new RuntimeException('Invalid attempt owner');
				}
			}

            // Idempotency: repeated finish must not create duplicate answers/stats.
            if (($attempt['finished_at'] ?? null) !== null) {
                $pdo->commit();
                redirect('/attempts/' . $attemptId);
                return;
            }
		} else {
			$attemptId = attempt_create($testId, $userId);
            $attempt = attempt_find_by_id_for_update($attemptId);
            if ($attempt === null) {
                throw new RuntimeException('Failed to lock new attempt');
            }
		}

        // Attempt may be past its expires_at by the time the request arrives (network delay,
        // or the client-side auto-submit fires right at the deadline). We still process the
        // submitted answers so the user doesn't lose their work; the status is set to 'expired'
        // and finished_at will be clamped to expires_at by attempt_finish_update.
        $isExpiredSubmit = attempt_is_expired($attempt);

        $questions = questions_list_by_test_id($testId);
        $questionIds = [];
        foreach ($questions as $q) {
            $questionIds[] = (int)($q['id'] ?? 0);
        }

        $optionsByQ = options_list_by_question_ids($questionIds);
        $optionTextById = [];
        foreach ($optionsByQ as $opts) {
            foreach ($opts as $opt) {
                $oid = (int)($opt['id'] ?? 0);
                if ($oid <= 0) {
                    continue;
                }
                $optionTextById[$oid] = (string)($opt['option_text'] ?? '');
            }
        }
        $allOptionTextsByQ = [];
        foreach ($optionsByQ as $qidTmp => $opts) {
            $list = [];
            foreach ($opts as $opt) {
                $list[] = (string)($opt['option_text'] ?? '');
            }
            $allOptionTextsByQ[(int)$qidTmp] = $list;
        }

        $correctOptionIdsByQ = options_correct_ids_by_question_ids($questionIds);
        $correctTextAnswersByQ = text_answers_by_question_ids($questionIds);

        $posted = $_POST['answers'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }
        if (count($posted) > max(1, count($questions) * 3)) {
            throw new InvalidArgumentException('Invalid submitted answers payload');
        }

        $total = count($questions);
        $correctCount = 0;
        $wrongCount = 0;
        $earnedPoints = 0.0;

        $answerRows = [];
        $allowedOptionIdsByQ = [];
        foreach ($optionsByQ as $qidTmp => $optsTmp) {
            foreach ($optsTmp as $optTmp) {
                $oidTmp = (int)($optTmp['id'] ?? 0);
                if ($oidTmp <= 0) {
                    continue;
                }
                $allowedOptionIdsByQ[(int)$qidTmp][$oidTmp] = true;
            }
        }

        foreach ($questions as $q) {
            $qid = (int)($q['id'] ?? 0);
            $type = (string)($q['type'] ?? 'radio');

            $isCorrect = false;
            $questionScore = 0.0;
            $correctPayloadSnapshot = null;

            if ($type === 'input') {
                $userTextRaw = '';
                if (isset($posted[$qid]) && !is_array($posted[$qid])) {
                    $userTextRaw = (string)$posted[$qid];
                }
                if (mb_strlen($userTextRaw) > $MAX_INPUT_ANSWER_LEN) {
                    throw new InvalidArgumentException('Input answer is too long');
                }

                $userNorm = normalize_input_answer($userTextRaw);

                $variants = $correctTextAnswersByQ[$qid] ?? [];
                $variantsNorm = [];
                foreach ($variants as $v) {
                    $variantsNorm[] = normalize_input_answer((string)$v);
                }

                $isCorrect = ($userNorm !== '') && in_array($userNorm, $variantsNorm, true);
                $questionScore = $isCorrect ? 1.0 : 0.0;
                $correctPayloadSnapshot = json_encode([
                    'type' => 'input',
                    'correct_text_answers' => array_values(array_map('strval', $variants)),
                ], JSON_UNESCAPED_UNICODE);

                $answerRows[] = [
					'question_id' => $qid,
					'option_id' => null,
					'text_answer' => $userTextRaw,
					'question_type_snapshot' => $type,
					'question_text_snapshot' => (string)($q['question_text'] ?? ''),
				'question_image_snapshot' => trim((string)($q['image_path'] ?? '')) !== '' ? (string)$q['image_path'] : null,
					'option_text_snapshot' => null,
					'is_correct_snapshot' => $isCorrect ? 1 : 0,
					'correct_payload_snapshot' => $correctPayloadSnapshot,
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
                $allowedIds = $allowedOptionIdsByQ[$qid] ?? [];
                $userOptIds = array_values(array_filter($userOptIds, fn($oid) => isset($allowedIds[(int)$oid])));
                $userOptIds = array_values(array_unique(array_map('intval', $userOptIds)));

                $correctIds = $correctOptionIdsByQ[$qid] ?? [];

                sort($userOptIds);
                $correctSorted = array_values(array_map('intval', $correctIds));
                sort($correctSorted);

                $isCorrect = (!empty($correctSorted) || !empty($userOptIds)) && ($userOptIds === $correctSorted);
                $correctCountTotal = count($correctSorted);
                $correctSelectedCount = 0;
                if ($correctCountTotal > 0) {
                    foreach ($userOptIds as $selectedOid) {
                        if (in_array((int)$selectedOid, $correctSorted, true)) {
                            $correctSelectedCount++;
                        }
                    }
                    $questionScore = $correctSelectedCount / $correctCountTotal;
                }
                $correctOptionTexts = [];
                foreach ($correctSorted as $correctOid) {
                    $correctOptionTexts[] = (string)($optionTextById[$correctOid] ?? ('Вариант #' . $correctOid));
                }
                $selectedOptionTexts = [];
                foreach ($userOptIds as $selectedOid) {
                    $selectedOptionTexts[] = (string)($optionTextById[(int)$selectedOid] ?? ('Вариант #' . (int)$selectedOid));
                }
                $correctPayloadSnapshot = json_encode([
                    'type' => 'checkbox',
                    'all_option_texts' => array_values(array_map('strval', $allOptionTextsByQ[$qid] ?? [])),
                    'correct_option_texts' => $correctOptionTexts,
                    'selected_option_texts' => $selectedOptionTexts,
                ], JSON_UNESCAPED_UNICODE);

                foreach ($userOptIds as $oid) {
                    $answerRows[] = [
						'question_id' => $qid,
						'option_id' => $oid,
						'text_answer' => null,
						'question_type_snapshot' => $type,
						'question_text_snapshot' => (string)($q['question_text'] ?? ''),
				'question_image_snapshot' => trim((string)($q['image_path'] ?? '')) !== '' ? (string)$q['image_path'] : null,
						'option_text_snapshot' => (string)($optionTextById[$oid] ?? ''),
						'is_correct_snapshot' => $isCorrect ? 1 : 0,
						'correct_payload_snapshot' => $correctPayloadSnapshot,
					];
                }
                if (empty($userOptIds)) {
                    $answerRows[] = [
                        'question_id' => $qid,
                        'option_id' => null,
                        'text_answer' => null,
                        'question_type_snapshot' => $type,
                        'question_text_snapshot' => (string)($q['question_text'] ?? ''),
				'question_image_snapshot' => trim((string)($q['image_path'] ?? '')) !== '' ? (string)$q['image_path'] : null,
                        'option_text_snapshot' => null,
                        'is_correct_snapshot' => 0,
                        'correct_payload_snapshot' => $correctPayloadSnapshot,
                    ];
                }
            } else { // radio по умолчанию
                $userOptId = 0;
                if (isset($posted[$qid]) && !is_array($posted[$qid])) {
                    $userOptId = (int)$posted[$qid];
                }
                $allowedIds = $allowedOptionIdsByQ[$qid] ?? [];
                if ($userOptId > 0 && !isset($allowedIds[$userOptId])) {
                    $userOptId = 0;
                }

                $correctIds = $correctOptionIdsByQ[$qid] ?? [];
                $isCorrect = ($userOptId > 0) && in_array($userOptId, array_map('intval', $correctIds), true);
                $questionScore = $isCorrect ? 1.0 : 0.0;
                $correctOptionTexts = [];
                foreach (array_map('intval', $correctIds) as $correctOid) {
                    $correctOptionTexts[] = (string)($optionTextById[$correctOid] ?? ('Вариант #' . $correctOid));
                }
                $selectedOptionTexts = [];
                if ($userOptId > 0) {
                    $selectedOptionTexts[] = (string)($optionTextById[$userOptId] ?? ('Вариант #' . $userOptId));
                }
                $correctPayloadSnapshot = json_encode([
                    'type' => 'radio',
                    'all_option_texts' => array_values(array_map('strval', $allOptionTextsByQ[$qid] ?? [])),
                    'correct_option_texts' => $correctOptionTexts,
                    'selected_option_texts' => $selectedOptionTexts,
                ], JSON_UNESCAPED_UNICODE);

                $answerRows[] = [
                    'question_id' => $qid,
                    'option_id' => $userOptId > 0 ? $userOptId : null,
                    'text_answer' => null,
                    'question_type_snapshot' => $type,
                    'question_text_snapshot' => (string)($q['question_text'] ?? ''),
				'question_image_snapshot' => trim((string)($q['image_path'] ?? '')) !== '' ? (string)$q['image_path'] : null,
                    'option_text_snapshot' => $userOptId > 0 ? (string)($optionTextById[$userOptId] ?? '') : null,
                    'is_correct_snapshot' => $isCorrect ? 1 : 0,
                    'correct_payload_snapshot' => $correctPayloadSnapshot,
                ];
            }

            if ($isCorrect) {
                $correctCount++;
            } else {
                $wrongCount++;
            }

            $earnedPoints += $questionScore;
        }

        $percent = ($total > 0) ? round(($earnedPoints / $total) * 100, 2) : 0.0;

		answers_insert_batch($attemptId, $answerRows);

		$totalQuestions = $total;
		$testSnapshotHash = test_snapshot_hash_by_test_id($testId);
		$finishStatus = $isExpiredSubmit ? 'expired' : 'finished';
		$finished = attempt_finish_update($attemptId, $correctCount, $wrongCount, $percent, $totalQuestions, $testSnapshotHash, $finishStatus);
        if (!$finished) {
            $freshAttempt = attempt_find_by_id($attemptId);
            if ($freshAttempt !== null && ($freshAttempt['finished_at'] ?? null) !== null) {
                $pdo->commit();
                unset($_SESSION['active_attempt_id_by_test'][$testId]);
                redirect('/attempts/' . $attemptId);
                return;
            }
            throw new RuntimeException('Attempt finalize race detected');
        }

		unset($_SESSION['active_attempt_id_by_test'][$testId]);
        if ($userId === null) {
            if (!isset($_SESSION['guest_attempt_ids']) || !is_array($_SESSION['guest_attempt_ids'])) {
                $_SESSION['guest_attempt_ids'] = [];
            }
            $_SESSION['guest_attempt_ids'][] = $attemptId;
            $_SESSION['guest_attempt_ids'] = array_values(array_unique(array_map('intval', $_SESSION['guest_attempt_ids'])));
            if (count($_SESSION['guest_attempt_ids']) > 200) {
                $_SESSION['guest_attempt_ids'] = array_slice($_SESSION['guest_attempt_ids'], -200);
            }
        }

        $pdo->commit();
        if ($userId !== null && $userId > 0) {
            $existingRating = test_rating_find_by_test_id_and_user_id($testId, $userId);
            if ($existingRating === null) {
                flash_set('rate_prompt', [
                    'attempt_id' => $attemptId,
                    'test_id' => $testId,
                ]);
            }
        }

        redirect('/attempts/' . $attemptId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof InvalidArgumentException) {
            http_response_code(422);
            view_render('error', [
                'title' => 'Ошибка',
                'message' => 'Некорректные данные формы. Обновите страницу и попробуйте ещё раз.',
            ]);
            return;
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

    $attemptUserId = $attempt['user_id'] ?? null;
    if ($attemptUserId !== null && $attemptUserId !== '') {
        if (!auth_is_logged_in()) {
            $_SESSION['redirect_to'] = '/attempts/' . $attemptId;
            redirect('/login');
        }

        $viewer = auth_user();
        $viewerId = (int)($viewer['id'] ?? 0);
        if ($viewerId <= 0 || $viewerId !== (int)$attemptUserId) {
            http_response_code(403);
            view_render('error', [
                'title' => 'Ошибка 403',
                'message' => 'Нет доступа к этому результату.',
            ]);
            return;
        }
    } else {
        $guestAttemptIds = $_SESSION['guest_attempt_ids'] ?? [];
        if (!is_array($guestAttemptIds) || !in_array($attemptId, array_map('intval', $guestAttemptIds), true)) {
            http_response_code(403);
            view_render('error', [
                'title' => 'Ошибка 403',
                'message' => 'Нет доступа к этому результату.',
            ]);
            return;
        }
    }

    $testId = (int)($attempt['test_id'] ?? 0);
    $ratePrompt = flash_get('rate_prompt', null);
    $showRatePrompt = false;
    if (auth_is_logged_in() && is_array($ratePrompt)) {
        $promptAttemptId = (int)($ratePrompt['attempt_id'] ?? 0);
        $promptTestId = (int)($ratePrompt['test_id'] ?? 0);
        $showRatePrompt = ($promptAttemptId === $attemptId && $promptTestId === $testId);
    }

    $test = tests_find_by_id($testId);
    $testMissing = false;
    $sourceState = 'ok';
    if ($test === null) {
        $testMissing = true;
        $sourceState = 'deleted';
        $snapshotTitle = trim((string)($attempt['test_title_snapshot'] ?? ''));
        $snapshotAccess = trim((string)($attempt['test_access_snapshot'] ?? ''));

        $test = [
            'id' => $testId,
            'title' => $snapshotTitle !== '' ? $snapshotTitle : 'Тест',
            'access_level' => $snapshotAccess !== '' ? $snapshotAccess : 'public',
        ];
    }

    if (!$testMissing) {
        $attemptHash = trim((string)($attempt['test_snapshot_hash'] ?? ''));
        if ($attemptHash !== '') {
            $currentHash = test_snapshot_hash_by_test_id((int)($test['id'] ?? 0));
            if ($currentHash !== '' && !hash_equals($attemptHash, $currentHash)) {
                $sourceState = 'changed';
            }
        }
    }

    if (($test['access_level'] ?? '') === 'registered' && !auth_is_logged_in()) {
        $_SESSION['redirect_to'] = '/attempts/' . $attemptId;
        redirect('/login');
    }

    $questions = questions_list_by_test_id($testId);
    $questionIds = [];
    foreach ($questions as $q) {
        $questionIds[] = (int)($q['id'] ?? 0);
    }

    $optionsByQuestionId = options_list_by_question_ids($questionIds);
    $correctOptionIdsByQ = options_correct_ids_by_question_ids($questionIds);
    $correctTextAnswersByQ = text_answers_by_question_ids($questionIds);

	$optionTextById = [];
	foreach ($optionsByQuestionId as $qidTmp => $opts) {
		foreach ($opts as $opt) {
			$oidTmp = (int)($opt['id'] ?? 0);
			if ($oidTmp <= 0) continue;
			$optionTextById[$oidTmp] = (string)($opt['option_text'] ?? '');
		}
	}

    $questionImageByQid = [];
    $questionImageByTextNorm = [];
    foreach ($questions as $q) {
        $qidTmp = (int)($q['id'] ?? 0);
        $img = trim((string)($q['image_path'] ?? ''));
        if ($img === '') continue;
        if ($qidTmp > 0) {
            $questionImageByQid[$qidTmp] = $img;
        }
        $textNorm = mb_strtolower(trim((string)($q['question_text'] ?? '')));
        if ($textNorm !== '') {
            $questionImageByTextNorm[$textNorm] = $img;
        }
    }

    $userAnswers = answers_list_by_attempt_id($attemptId);

	$snapshotMode = false;
	foreach ($userAnswers as $a) {
		if (!empty($a['question_type_snapshot'])) {
			$snapshotMode = true;
			break;
		}
	}

    $userByQ = [];
    foreach ($userAnswers as $a) {
        $qid = (int)($a['question_id'] ?? 0);
        if ($qid <= 0) continue;
        $userByQ[$qid][] = $a;
    }

	if ($snapshotMode) {
		$snapshotTitle = (string)($attempt['test_title_snapshot'] ?? '');
		if ($snapshotTitle !== '') {
			$test['title'] = $snapshotTitle;
		}

		$snapshotAccess = (string)($attempt['test_access_snapshot'] ?? '');
		if ($snapshotAccess !== '') {
			$test['access_level'] = $snapshotAccess;
		}

		$questions = [];
		$optionsByQuestionId = [];
		$correctOptionIdsByQ = [];
		$correctTextAnswersByQ = [];

		$questionIds = [];
		foreach ($userAnswers as $a) {
			$qid = (int)($a['question_id'] ?? 0);
			if ($qid > 0) {
				$questionIds[$qid] = true;
			}
		}
		$questionIds = array_keys($questionIds);

		if (!empty($questionIds)) {
			$optionsByQuestionId = options_list_by_question_ids($questionIds);
			$correctOptionIdsByQ = options_correct_ids_by_question_ids($questionIds);
			$correctTextAnswersByQ = text_answers_by_question_ids($questionIds);
		}

		$userByQ = [];
		$groupIdByKey = [];
		$nextSyntheticQid = -1;

		foreach ($userAnswers as $a) {
			$rawQid = $a['question_id'] ?? null;
			$realQid = ($rawQid === null || $rawQid === '') ? 0 : (int)$rawQid;
			$qText = (string)($a['question_text_snapshot'] ?? '');
			$qType = (string)($a['question_type_snapshot'] ?? '');

			$groupKey = $realQid > 0
				? 'q:' . $realQid
				: 's:' . hash('sha1', $qType . "\n" . $qText);

			if (!isset($groupIdByKey[$groupKey])) {
				$groupIdByKey[$groupKey] = ($realQid > 0) ? $realQid : $nextSyntheticQid--;
			}

			$groupQid = $groupIdByKey[$groupKey];
			$userByQ[$groupQid][] = $a;

			if (!isset($questions[$groupQid])) {
				$imgSnapshotted = trim((string)($a['question_image_snapshot'] ?? ''));
				$questions[$groupQid] = [
					'id'            => $groupQid,
					'question_text' => $qText,
					'type'          => $qType,
					'question_type' => $qType,
					'image_path'    => $imgSnapshotted !== '' ? $imgSnapshotted : null,
				];
			}
		}

		$questions = array_values($questions);

        foreach ($questions as &$qSnap) {
            if (!empty($qSnap['image_path'])) {
                continue; // already set from snapshot
            }
            // Fallback for old attempts without question_image_snapshot
            $qSnapId = (int)($qSnap['id'] ?? 0);
            if ($qSnapId > 0 && isset($questionImageByQid[$qSnapId])) {
                $qSnap['image_path'] = $questionImageByQid[$qSnapId];
            } else {
                $textNorm = mb_strtolower(trim((string)($qSnap['question_text'] ?? '')));
                if ($textNorm !== '' && isset($questionImageByTextNorm[$textNorm])) {
                    $qSnap['image_path'] = $questionImageByTextNorm[$textNorm];
                }
            }
        }
        unset($qSnap);
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
        'snapshotMode' => $snapshotMode,
        'testMissing' => $testMissing,
        'sourceState' => $sourceState,
        'styles' => ['/assets/css/attempt-show.css'],
        'scripts' => ['/assets/js/attempt-show.js', '/assets/js/attempt-rate-modal.js'],
        'show_rate_prompt' => $showRatePrompt,
    ]);
}
