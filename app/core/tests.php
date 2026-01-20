<?php
declare(strict_types=1);

function tests_list_by_user_id(int $userId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
		SELECT id, user_id, title, description, access_level, created_at, updated_at
		FROM tests
		WHERE user_id = :user_id AND deleted_at IS NULL
		ORDER BY created_at DESC
	");


    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return $stmt->fetchAll();
}

function tests_create(int $userId, string $title, string $description, string $accessLevel): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO tests (user_id, title, description, access_level)
        VALUES (:user_id, :title, :description, :access_level)
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':access_level' => $accessLevel,
    ]);

    return (int)$pdo->lastInsertId();
}

function questions_create(
    int $testId,
    string $type,
    string $questionText,
    int $position
): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO questions (test_id, type, question_text, position)
        VALUES (:test_id, :type, :question_text, :position)
    ");

    $stmt->execute([
        ':test_id' => $testId,
        ':type' => $type,
        ':question_text' => $questionText,
        ':position' => $position,
    ]);

    return (int)$pdo->lastInsertId();
}


function options_create(
    int $questionId,
    string $optionText,
    int $isCorrect,
    int $position
): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO options (question_id, option_text, is_correct, position)
        VALUES (:question_id, :option_text, :is_correct, :position)
    ");

    $stmt->execute([
        ':question_id' => $questionId,
        ':option_text' => $optionText,
        ':is_correct' => $isCorrect,
        ':position' => $position,
    ]);

    return (int)$pdo->lastInsertId();
}

function question_text_answers_create(int $questionId, string $answerText): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO question_text_answers (question_id, answer_text)
        VALUES (:question_id, :answer_text)
    ");

    $stmt->execute([
        ':question_id' => $questionId,
        ':answer_text' => $answerText,
    ]);

    return (int)$pdo->lastInsertId();
}

function questions_count_by_test_id(int $testId): int
{
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM questions WHERE test_id = :test_id'
    );

    $stmt->execute([
        ':test_id' => $testId,
    ]);

    return (int) $stmt->fetchColumn();
}

function tests_find_by_id(int $testId): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare("
		SELECT id, user_id, title, description, access_level, created_at, updated_at
		FROM tests
		WHERE id = :id AND deleted_at IS NULL
		LIMIT 1
	");


    $stmt->execute([
        ':id' => $testId,
    ]);

    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

function tests_delete_by_id_and_user_id(int $testId, int $userId): bool
{
    $pdo = db();

    $stmt = $pdo->prepare("
		UPDATE tests
		SET deleted_at = NOW()
		WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
		LIMIT 1
	");


    $stmt->execute([
        ':id' => $testId,
        ':user_id' => $userId,
    ]);

    return $stmt->rowCount() === 1;
}

function tests_trash_list_by_user_id(int $userId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, user_id, title, description, access_level, created_at, updated_at, deleted_at
        FROM tests
        WHERE user_id = :user_id AND deleted_at IS NOT NULL
        ORDER BY deleted_at DESC
    ");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return $stmt->fetchAll();
}

function tests_restore_by_id_and_user_id(int $testId, int $userId): bool
{
    $pdo = db();

    $stmt = $pdo->prepare("
        UPDATE tests
        SET deleted_at = NULL
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NOT NULL
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $testId,
        ':user_id' => $userId,
    ]);

    return $stmt->rowCount() === 1;
}

function tests_destroy_by_id_and_user_id(int $testId, int $userId): bool
{
    $pdo = db();

    $stmt = $pdo->prepare("
        DELETE FROM tests
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NOT NULL
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $testId,
        ':user_id' => $userId,
    ]);

    return $stmt->rowCount() === 1;
}

function tests_trash_restore_all_by_user_id(int $userId): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        UPDATE tests
        SET deleted_at = NULL
        WHERE user_id = :user_id AND deleted_at IS NOT NULL
    ");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return (int)$stmt->rowCount();
}

function tests_trash_empty_by_user_id(int $userId): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        DELETE FROM tests
        WHERE user_id = :user_id AND deleted_at IS NOT NULL
    ");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return (int)$stmt->rowCount();
}

function tests_trash_count_by_user_id(int $userId): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests
        WHERE user_id = :user_id AND deleted_at IS NOT NULL
    ");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return (int)$stmt->fetchColumn();
}



function questions_list_by_test_id(int $testId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, test_id, type, question_text, position
        FROM questions
        WHERE test_id = :test_id
        ORDER BY position ASC, id ASC
    ");

    $stmt->execute([
        ':test_id' => $testId,
    ]);

    return $stmt->fetchAll();
}

function options_list_by_question_ids(array $questionIds): array
{
    $questionIds = array_values(array_filter(array_map('intval', $questionIds), fn($v) => $v > 0));
    if (count($questionIds) === 0) {
        return [];
    }

    $pdo = db();

    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));

    // ВАЖНО: is_correct НЕ выбираем, чтобы на странице прохождения не было спойлеров
    $stmt = $pdo->prepare("
        SELECT id, question_id, option_text, position
        FROM options
        WHERE question_id IN ($placeholders)
        ORDER BY question_id ASC, position ASC, id ASC
    ");

    $stmt->execute($questionIds);

    $rows = $stmt->fetchAll();

    // сгруппируем по question_id для удобства в шаблоне
    $grouped = [];
    foreach ($rows as $row) {
        $qid = (int)($row['question_id'] ?? 0);
        if ($qid <= 0) continue;
        $grouped[$qid][] = $row;
    }

    return $grouped;
}

function options_correct_ids_by_question_ids(array $questionIds): array
{
    $questionIds = array_values(array_filter(array_map('intval', $questionIds), fn($v) => $v > 0));
    if (count($questionIds) === 0) {
        return [];
    }

    $pdo = db();

    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));

    $stmt = $pdo->prepare("
        SELECT question_id, id
        FROM options
        WHERE question_id IN ($placeholders) AND is_correct = 1
        ORDER BY question_id ASC, position ASC, id ASC
    ");

    $stmt->execute($questionIds);

    $rows = $stmt->fetchAll();

    $grouped = [];
    foreach ($rows as $row) {
        $qid = (int)($row['question_id'] ?? 0);
        $oid = (int)($row['id'] ?? 0);
        if ($qid <= 0 || $oid <= 0) continue;
        $grouped[$qid][] = $oid;
    }

    return $grouped;
}

function text_answers_by_question_ids(array $questionIds): array
{
    $questionIds = array_values(array_filter(array_map('intval', $questionIds), fn($v) => $v > 0));
    if (count($questionIds) === 0) {
        return [];
    }

    $pdo = db();

    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));

    $stmt = $pdo->prepare("
        SELECT question_id, answer_text
        FROM question_text_answers
        WHERE question_id IN ($placeholders)
        ORDER BY question_id ASC, id ASC
    ");

    $stmt->execute($questionIds);

    $rows = $stmt->fetchAll();

    $grouped = [];
    foreach ($rows as $row) {
        $qid = (int)($row['question_id'] ?? 0);
        $a = (string)($row['answer_text'] ?? '');
        if ($qid <= 0) continue;
        $grouped[$qid][] = $a;
    }

    return $grouped;
}

function attempt_create(int $testId, ?int $userId): int
{
    $pdo = db();

	// snapshots (на момент старта попытки)
	$stmt = $pdo->prepare("
		SELECT title, access_level
		FROM tests
		WHERE id = :test_id
		LIMIT 1
	");
	$stmt->execute([':test_id' => $testId]);
	$testRow = $stmt->fetch();

	$testTitleSnapshot  = (string)($testRow['title'] ?? '');
	$testAccessSnapshot = (string)($testRow['access_level'] ?? '');


    // 1. Получаем номер следующей попытки
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(attempt_no), 0) + 1
        FROM attempts
        WHERE test_id = :test_id
          AND user_id = :user_id
    ");
    $stmt->execute([
        ':test_id' => $testId,
        ':user_id' => $userId,
    ]);

    $attemptNo = (int)$stmt->fetchColumn();

    // 2. Создаём попытку с зафиксированным attempt_no
    $stmt = $pdo->prepare("
		INSERT INTO attempts (test_id, test_title_snapshot, test_access_snapshot, user_id, attempt_no, started_at)
		VALUES (:test_id, :test_title_snapshot, :test_access_snapshot, :user_id, :attempt_no, CURRENT_TIMESTAMP)
	");


    $stmt->execute([
		':test_id' => $testId,
		':test_title_snapshot' => $testTitleSnapshot,
		':test_access_snapshot' => $testAccessSnapshot,
		':user_id' => $userId,
		':attempt_no' => $attemptNo,
	]);


    return (int)$pdo->lastInsertId();
}

function test_snapshot_hash_by_test_id(int $testId): string
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            t.updated_at AS updated_at,
            (SELECT COUNT(*) FROM questions q WHERE q.test_id = t.id) AS questions_count,
            (
                SELECT COUNT(*)
                FROM options o
                JOIN questions q2 ON q2.id = o.question_id
                WHERE q2.test_id = t.id
            ) AS options_count
        FROM tests t
        WHERE t.id = :test_id
        LIMIT 1
    ");

    $stmt->execute([
        ':test_id' => $testId,
    ]);

    $row = $stmt->fetch();
    if ($row === false) {
        return hash('sha256', 'missing-test|' . $testId);
    }

    $updatedAt = (string)($row['updated_at'] ?? '');
    $qCount = (int)($row['questions_count'] ?? 0);
    $oCount = (int)($row['options_count'] ?? 0);

    return hash('sha256', $testId . '|' . $updatedAt . '|' . $qCount . '|' . $oCount);
}


function attempt_finish_update(
    int $attemptId,
    int $correctCount,
    int $wrongCount,
    float $percent,
    int $totalQuestions,
    string $testSnapshotHash
): void
{
    $pdo = db();

    $stmt = $pdo->prepare("
        UPDATE attempts
        SET finished_at = CURRENT_TIMESTAMP,
            duration_sec = TIMESTAMPDIFF(SECOND, started_at, CURRENT_TIMESTAMP),
            total_questions = :total_questions,
            test_snapshot_hash = :test_snapshot_hash,
            correct_count = :correct_count,
            wrong_count = :wrong_count,
            percent = :percent
        WHERE id = :id
          AND finished_at IS NULL
        LIMIT 1
    ");

    $stmt->execute([
        ':total_questions' => $totalQuestions,
        ':test_snapshot_hash' => $testSnapshotHash,
        ':correct_count' => $correctCount,
        ':wrong_count' => $wrongCount,
        ':percent' => $percent,
        ':id' => $attemptId,
    ]);
}


/**
 * $rows формат:
 * [
 *   ['question_id' => 1, 'option_id' => 10, 'text_answer' => null],
 *   ['question_id' => 2, 'option_id' => null, 'text_answer' => 'молоко'],
 * ]
 */
function answers_insert_batch(int $attemptId, array $rows): void
{
    if (empty($rows)) {
        return;
    }

    $pdo = db();

    $values = [];
    $params = [];

    foreach ($rows as $i => $row) {
        $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $params[] = $attemptId;
        $params[] = (int)($row['question_id'] ?? 0);

        $optionId = $row['option_id'] ?? null;
        $params[] = ($optionId === null || $optionId === '') ? null : (int)$optionId;

        $text = $row['text_answer'] ?? null;
        $params[] = ($text === null) ? null : (string)$text;
		$params[] = (string)($row['question_type_snapshot'] ?? '');
		$params[] = (string)($row['question_text_snapshot'] ?? '');
		$params[] = array_key_exists('option_text_snapshot', $row) ? $row['option_text_snapshot'] : null;
		$params[] = (int)($row['is_correct_snapshot'] ?? 0);

    }

    $sql = "
        INSERT INTO answers (
			attempt_id,
			question_id,
			option_id,
			text_answer,
			question_type_snapshot,
			question_text_snapshot,
			option_text_snapshot,
			is_correct_snapshot,
			created_at
		)

        VALUES " . implode(",\n", $values) . "
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function attempt_find_by_id(int $attemptId): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, test_id, user_id, started_at, finished_at, correct_count, wrong_count, percent
        FROM attempts
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $attemptId,
    ]);

    $row = $stmt->fetch();

    return ($row !== false) ? $row : null;
}

function answers_list_by_attempt_id(int $attemptId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
		SELECT
			id,
			attempt_id,
			question_id,
			option_id,
			text_answer,
			question_type_snapshot,
			question_text_snapshot,
			option_text_snapshot,
			is_correct_snapshot,
			created_at
		FROM answers
		WHERE attempt_id = :attempt_id
		ORDER BY id ASC
	");

    $stmt->execute([
        ':attempt_id' => $attemptId,
    ]);

    return $stmt->fetchAll();
}
