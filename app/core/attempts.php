<?php
declare(strict_types=1);

function attempt_is_expired(array $attempt): bool
{
    if (($attempt['finished_at'] ?? null) !== null) {
        return false;
    }

    $expiresAt = trim((string)($attempt['expires_at'] ?? ''));
    if ($expiresAt === '') {
        return false;
    }

    $expiresTs = strtotime($expiresAt);
    if ($expiresTs === false) {
        return false;
    }

    return $expiresTs <= time();
}

function attempts_has_finished_by_test_id_and_user_id(int $testId, int $userId): bool
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT 1
        FROM attempts
        WHERE test_id = :test_id
          AND user_id = :user_id
          AND finished_at IS NOT NULL
        LIMIT 1
    ");
    $stmt->execute([
        ':test_id' => $testId,
        ':user_id' => $userId,
    ]);
    return $stmt->fetchColumn() !== false;
}

function attempts_count_finished_by_test_id_and_user_id(int $testId, int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM attempts
        WHERE test_id = :test_id
          AND user_id = :user_id
          AND finished_at IS NOT NULL
    ");
    $stmt->execute([
        ':test_id' => $testId,
        ':user_id' => $userId,
    ]);

    return (int)$stmt->fetchColumn();
}

function attempts_count_finished_guest_by_test_id(int $testId, array $attemptIds): int
{
    $attemptIds = array_values(array_unique(array_filter(array_map('intval', $attemptIds), static fn(int $id): bool => $id > 0)));
    if ($attemptIds === []) {
        return 0;
    }

    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($attemptIds), '?'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM attempts
        WHERE id IN ($placeholders)
          AND test_id = ?
          AND user_id IS NULL
          AND finished_at IS NOT NULL
    ");
    $params = $attemptIds;
    $params[] = $testId;
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function attempt_create(int $testId, ?int $userId): int
{
    $pdo = db();

	// snapshots (на момент старта попытки)
	$stmt = $pdo->prepare("
		SELECT title, access_level, time_limit_sec, time_limit_min
		FROM tests
		WHERE id = :test_id
		LIMIT 1
	");
	$stmt->execute([':test_id' => $testId]);
	$testRow = $stmt->fetch();

	$testTitleSnapshot  = (string)($testRow['title'] ?? '');
	$testAccessSnapshot = (string)($testRow['access_level'] ?? '');
    $timeLimitSecSnapshot = test_time_limit_sec_from_row(is_array($testRow) ? $testRow : []);
    $expiresAt = null;
    if ($timeLimitSecSnapshot !== null && $timeLimitSecSnapshot > 0) {
        $expiresAt = date('Y-m-d H:i:s', time() + $timeLimitSecSnapshot);
    }

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

    $stmt = $pdo->prepare("
		INSERT INTO attempts (
            test_id,
            test_title_snapshot,
            test_access_snapshot,
            user_id,
            attempt_no,
            started_at,
            time_limit_sec_snapshot,
            expires_at,
            status
        )
		VALUES (
            :test_id,
            :test_title_snapshot,
            :test_access_snapshot,
            :user_id,
            :attempt_no,
            CURRENT_TIMESTAMP,
            :time_limit_sec_snapshot,
            :expires_at,
            'in_progress'
        )
	");

    $stmt->execute([
		':test_id' => $testId,
		':test_title_snapshot' => $testTitleSnapshot,
		':test_access_snapshot' => $testAccessSnapshot,
		':user_id' => $userId,
		':attempt_no' => $attemptNo,
        ':time_limit_sec_snapshot' => $timeLimitSecSnapshot,
        ':expires_at' => $expiresAt,
	]);

    return (int)$pdo->lastInsertId();
}

function test_snapshot_hash_by_test_id(int $testId): string
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            t.updated_at AS updated_at,
            t.result_mode AS result_mode,
            t.pass_percent AS pass_percent,
            t.grade_scale_json AS grade_scale_json,
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
    $resultMode = (string)($row['result_mode'] ?? '');
    $passPercent = (string)($row['pass_percent'] ?? '');
    $gradeScaleJson = (string)($row['grade_scale_json'] ?? '');
    $qCount = (int)($row['questions_count'] ?? 0);
    $oCount = (int)($row['options_count'] ?? 0);

    return hash('sha256', $testId . '|' . $updatedAt . '|' . $resultMode . '|' . $passPercent . '|' . $gradeScaleJson . '|' . $qCount . '|' . $oCount);
}

function attempt_finish_update(
    int $attemptId,
    int $correctCount,
    int $wrongCount,
    float $percent,
    int $totalQuestions,
    string $testSnapshotHash,
    string $status = 'finished',
    ?array $resultSnapshot = null
): bool
{
    $pdo = db();
    $resultSnapshot ??= [
        'mode' => 'scale',
        'label' => '',
        'pass_percent' => null,
        'scale_json' => null,
    ];

    $stmt = $pdo->prepare("
        UPDATE attempts
        SET finished_at = CASE
                WHEN :status_for_finished_at = 'expired' AND expires_at IS NOT NULL THEN expires_at
                ELSE CURRENT_TIMESTAMP
            END,
            duration_sec = TIMESTAMPDIFF(
                SECOND,
                started_at,
                CASE
                    WHEN :status_for_duration = 'expired' AND expires_at IS NOT NULL THEN expires_at
                    ELSE CURRENT_TIMESTAMP
                END
            ),
            total_questions = :total_questions,
            test_snapshot_hash = :test_snapshot_hash,
            correct_count = :correct_count,
            wrong_count = :wrong_count,
            percent = :percent,
            result_mode_snapshot = :result_mode_snapshot,
            result_label_snapshot = :result_label_snapshot,
            pass_percent_snapshot = :pass_percent_snapshot,
            grade_scale_snapshot = :grade_scale_snapshot,
            status = :status
        WHERE id = :id
          AND finished_at IS NULL
          AND status = 'in_progress'
        LIMIT 1
    ");

    $stmt->execute([
        ':total_questions' => $totalQuestions,
        ':test_snapshot_hash' => $testSnapshotHash,
        ':correct_count' => $correctCount,
        ':wrong_count' => $wrongCount,
        ':percent' => $percent,
        ':result_mode_snapshot' => (string)($resultSnapshot['mode'] ?? 'scale'),
        ':result_label_snapshot' => (string)($resultSnapshot['label'] ?? ''),
        ':pass_percent_snapshot' => $resultSnapshot['pass_percent'] ?? null,
        ':grade_scale_snapshot' => $resultSnapshot['scale_json'] ?? null,
        ':status' => ($status === 'expired') ? 'expired' : 'finished',
        ':status_for_finished_at' => ($status === 'expired') ? 'expired' : 'finished',
        ':status_for_duration' => ($status === 'expired') ? 'expired' : 'finished',
        ':id' => $attemptId,
    ]);

    return $stmt->rowCount() === 1;
}

function answers_insert_batch(int $attemptId, array $rows): void
{
    if (empty($rows)) {
        return;
    }

    $pdo = db();

    $values = [];
    $params = [];

    foreach ($rows as $i => $row) {
        $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $params[] = $attemptId;
        $params[] = (int)($row['question_id'] ?? 0);

        $optionId = $row['option_id'] ?? null;
        $params[] = ($optionId === null || $optionId === '') ? null : (int)$optionId;

        $text = $row['text_answer'] ?? null;
        $params[] = ($text === null) ? null : (string)$text;
		$params[] = (string)($row['question_type_snapshot'] ?? '');
		$params[] = (string)($row['question_text_snapshot'] ?? '');
        $imgSnap = $row['question_image_snapshot'] ?? null;
        $params[] = ($imgSnap === null || $imgSnap === '') ? null : (string)$imgSnap;
		$params[] = array_key_exists('option_text_snapshot', $row) ? $row['option_text_snapshot'] : null;
		$params[] = (int)($row['is_correct_snapshot'] ?? 0);
		$params[] = array_key_exists('correct_payload_snapshot', $row) ? $row['correct_payload_snapshot'] : null;
    }

    $sql = "
        INSERT INTO answers (
			attempt_id,
			question_id,
			option_id,
			text_answer,
			question_type_snapshot,
			question_text_snapshot,
			question_image_snapshot,
			option_text_snapshot,
			is_correct_snapshot,
			correct_payload_snapshot,
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
        SELECT
            id,
            test_id,
            test_title_snapshot,
            test_access_snapshot,
            test_snapshot_hash,
            user_id,
            attempt_no,
            started_at,
            finished_at,
            expires_at,
            duration_sec,
            status,
            time_limit_sec_snapshot,
            total_questions,
            correct_count,
            wrong_count,
            percent,
            result_mode_snapshot,
            result_label_snapshot,
            pass_percent_snapshot,
            grade_scale_snapshot,
            share_token,
            share_enabled,
            shared_at
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

function attempt_find_by_id_for_update(int $attemptId): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT
            id,
            test_id,
            test_title_snapshot,
            test_access_snapshot,
            test_snapshot_hash,
            user_id,
            attempt_no,
            started_at,
            finished_at,
            expires_at,
            duration_sec,
            status,
            time_limit_sec_snapshot,
            total_questions,
            correct_count,
            wrong_count,
            percent,
            result_mode_snapshot,
            result_label_snapshot,
            pass_percent_snapshot,
            grade_scale_snapshot,
            share_token,
            share_enabled,
            shared_at
        FROM attempts
        WHERE id = :id
        LIMIT 1
        FOR UPDATE
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
			question_image_snapshot,
			option_text_snapshot,
			is_correct_snapshot,
			correct_payload_snapshot,
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

function attempt_share_generate_token(): string
{
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}

function attempt_enable_share(int $attemptId, int $userId): ?array
{
    $pdo = db();

    $existing = attempt_find_by_id($attemptId);
    if ($existing === null || (int)($existing['user_id'] ?? 0) !== $userId || ($existing['finished_at'] ?? null) === null) {
        return null;
    }

    $currentToken = trim((string)($existing['share_token'] ?? ''));
    if ($currentToken !== '' && (int)($existing['share_enabled'] ?? 0) === 1) {
        return attempt_find_by_id($attemptId);
    }

    $token = $currentToken;
    for ($i = 0; $i < 10; $i++) {
        if ($token === '') {
            $token = attempt_share_generate_token();
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE attempts
                SET share_token = :share_token,
                    share_enabled = 1,
                    shared_at = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND user_id = :user_id
                  AND finished_at IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([
                ':share_token' => $token,
                ':id' => $attemptId,
                ':user_id' => $userId,
            ]);

            return $stmt->rowCount() === 1 ? attempt_find_by_id($attemptId) : null;
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }
            $token = '';
        }
    }

    throw new RuntimeException('Failed to generate unique share token');
}

function attempt_disable_share(int $attemptId, int $userId): bool
{
    $pdo = db();
    $stmt = $pdo->prepare("
        UPDATE attempts
        SET share_enabled = 0
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $attemptId,
        ':user_id' => $userId,
    ]);

    return $stmt->rowCount() === 1;
}

function attempt_find_shared_by_token(string $token): ?array
{
    $token = trim($token);
    if ($token === '' || !preg_match('/^[A-Za-z0-9_-]{32,128}$/', $token)) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.test_id,
            a.test_title_snapshot,
            a.test_access_snapshot,
            a.test_snapshot_hash,
            a.user_id,
            a.attempt_no,
            a.started_at,
            a.finished_at,
            a.expires_at,
            a.duration_sec,
            a.status,
            a.time_limit_sec_snapshot,
            a.total_questions,
            a.correct_count,
            a.wrong_count,
            a.percent,
            a.result_mode_snapshot,
            a.result_label_snapshot,
            a.pass_percent_snapshot,
            a.grade_scale_snapshot,
            a.share_token,
            a.share_enabled,
            a.shared_at,
            COALESCE(u.login, '') AS attempt_user_login,
            t.user_id AS test_author_id,
            t.deleted_at AS test_deleted_at,
            t.deleted_forever_at AS test_deleted_forever_at,
            t.status AS test_status
        FROM attempts a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN tests t ON t.id = a.test_id
        WHERE a.share_token = :token
          AND a.share_enabled = 1
          AND a.finished_at IS NOT NULL
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);

    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

function attempts_count_finished_by_user_id(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM attempts
        WHERE user_id = :user_id
          AND finished_at IS NOT NULL
    ");
    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return (int)$stmt->fetchColumn();
}

function attempts_avg_percent_by_user_id(int $userId): float
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT AVG(percent)
        FROM attempts
        WHERE user_id = :user_id
          AND finished_at IS NOT NULL
    ");
    $stmt->execute([
        ':user_id' => $userId,
    ]);

    $avg = $stmt->fetchColumn();
    return $avg === null ? 0.0 : (float)$avg;
}

function attempts_filters_sql(array $filters, array &$params): string
{
    $where = [
        'a.user_id = :user_id',
        'a.finished_at IS NOT NULL',
    ];

    $search = trim((string)($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = 'COALESCE(NULLIF(t.title, \'\'), NULLIF(a.test_title_snapshot, \'\'), \'Тест\') LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    $status = (string)($filters['status'] ?? 'all');
    if ($status === 'excellent' || $status === 'correct') {
        $where[] = "(a.result_mode_snapshot = 'scale' AND (a.result_label_snapshot = 'Отлично' OR (a.result_label_snapshot = '' AND a.percent >= 90)))";
    } elseif ($status === 'good') {
        $where[] = "(a.result_mode_snapshot = 'scale' AND (a.result_label_snapshot = 'Хорошо' OR (a.result_label_snapshot = '' AND a.percent >= 80 AND a.percent < 90)))";
    } elseif ($status === 'satisfactory' || $status === 'partial') {
        $where[] = "(a.result_mode_snapshot = 'scale' AND (a.result_label_snapshot = 'Удовлетворительно' OR (a.result_label_snapshot = '' AND a.percent >= 60 AND a.percent < 80)))";
    } elseif ($status === 'bad' || $status === 'wrong') {
        $where[] = "(a.result_mode_snapshot = 'scale' AND (a.result_label_snapshot = 'Плохо' OR (a.result_label_snapshot = '' AND a.percent < 60)))";
    } elseif ($status === 'passed') {
        $where[] = "a.result_mode_snapshot = 'pass_fail' AND a.percent >= COALESCE(a.pass_percent_snapshot, 60)";
    } elseif ($status === 'failed') {
        $where[] = "a.result_mode_snapshot = 'pass_fail' AND a.percent < COALESCE(a.pass_percent_snapshot, 60)";
    }

    $dateFrom = (string)($filters['date_from'] ?? '');
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'a.finished_at >= :date_from_ts';
        $params[':date_from_ts'] = $dateFrom . ' 00:00:00';
    }

    $dateTo = (string)($filters['date_to'] ?? '');
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateToTs = strtotime($dateTo . ' +1 day');
        if ($dateToTs !== false) {
            $where[] = 'a.finished_at < :date_to_next_ts';
            $params[':date_to_next_ts'] = date('Y-m-d H:i:s', $dateToTs);
        }
    }

    return implode(' AND ', $where);
}

function attempts_count_by_user_id_filtered(int $userId, array $filters): int
{
    $pdo = db();
    $params = [
        ':user_id' => $userId,
    ];
    $whereSql = attempts_filters_sql($filters, $params);

    $sql = "
        SELECT COUNT(*)
        FROM attempts a
        LEFT JOIN tests t ON t.id = a.test_id
        WHERE {$whereSql}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function attempts_list_by_user_id_filtered(int $userId, array $filters, int $limit, int $offset): array
{
    $pdo = db();
    $params = [
        ':user_id' => $userId,
    ];
    $whereSql = attempts_filters_sql($filters, $params);

    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);

    $sql = "
        SELECT
            a.id,
            a.test_id,
            a.test_title_snapshot,
            a.attempt_no,
            a.correct_count,
            a.wrong_count,
            a.total_questions,
            a.percent,
            a.result_label_snapshot,
            a.result_mode_snapshot,
            a.pass_percent_snapshot,
            a.grade_scale_snapshot,
            a.started_at,
            a.finished_at,
            a.duration_sec,
            t.id AS live_test_id,
            t.title AS live_test_title,
            t.cover_image AS live_cover_image
        FROM attempts a
        LEFT JOIN tests t ON t.id = a.test_id
        WHERE {$whereSql}
        ORDER BY a.finished_at DESC, a.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function pending_attempt_for_user(): ?array
{
    $sessionMap = $_SESSION['active_attempt_id_by_test'] ?? null;
    if (!is_array($sessionMap) || empty($sessionMap)) {
        return null;
    }

    // Most recent attempt is the last entry — iterate in reverse
    $entries = array_reverse($sessionMap, true);

    $pdo = db();
    foreach ($entries as $testId => $attemptId) {
        $attemptId = (int)$attemptId;
        if ($attemptId <= 0) {
            continue;
        }

        $stmt = $pdo->prepare("
            SELECT a.id, a.test_id, a.expires_at,
                   COALESCE(t.title, a.test_title_snapshot, 'Тест') AS test_title
            FROM attempts a
            INNER JOIN tests t
                ON t.id = a.test_id
               AND t.deleted_at IS NULL
               AND t.deleted_forever_at IS NULL
            WHERE a.id = :id
              AND a.finished_at IS NULL
              AND (a.expires_at IS NULL OR a.expires_at > NOW())
            LIMIT 1
        ");
        $stmt->execute([':id' => $attemptId]);
        $row = $stmt->fetch();

        if ($row !== false) {
            return [
                'attempt_id' => (int)$row['id'],
                'test_id'    => (int)$row['test_id'],
                'test_title' => (string)$row['test_title'],
                'pass_url'   => '/tests/' . (int)$row['test_id'] . '/pass',
            ];
        }

        // Stale entry — clean up session
        unset($_SESSION['active_attempt_id_by_test'][(int)$testId]);
    }

    return null;
}
