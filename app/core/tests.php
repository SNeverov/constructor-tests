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
		SELECT
            t.id,
            t.user_id,
            t.title,
            t.description,
            t.access_level,
            t.category_name,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login
		FROM tests t
        LEFT JOIN users u ON u.id = t.user_id
		WHERE t.id = :id AND t.deleted_at IS NULL
		LIMIT 1
	");


    $stmt->execute([
        ':id' => $testId,
    ]);

    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

function test_rating_find_by_test_id_and_user_id(int $testId, int $userId): ?int
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT rating
        FROM test_ratings
        WHERE test_id = :test_id
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':test_id' => $testId,
        ':user_id' => $userId,
    ]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        return null;
    }
    return (int)$value;
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

function tests_list_by_user_id_paginated(int $userId, int $limit, int $offset): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);

    $stmt = $pdo->prepare("
		SELECT
            t.id,
            t.user_id,
            t.title,
            t.description,
            t.access_level,
            t.category_name,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            CASE WHEN tb.user_id IS NULL THEN 0 ELSE 1 END AS is_bookmarked,
            (
                SELECT COUNT(*)
                FROM questions q
                WHERE q.test_id = t.id
            ) AS questions_count,
            (
                SELECT COUNT(*)
                FROM attempts a
                WHERE a.test_id = t.id
            ) AS attempts_count
		FROM tests t
        LEFT JOIN users u ON u.id = t.user_id
        LEFT JOIN test_bookmarks tb ON tb.test_id = t.id AND tb.user_id = :viewer_id
		WHERE t.user_id = :user_id AND t.deleted_at IS NULL
		ORDER BY t.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
	");

    $stmt->execute([
        ':user_id' => $userId,
        ':viewer_id' => $userId,
    ]);

    return $stmt->fetchAll();
}

function tests_count_for_home(): int
{
    $pdo = db();

    $where = 't.deleted_at IS NULL';
    if (!auth_is_logged_in()) {
        $where .= " AND t.access_level = 'public'";
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests t
        WHERE {$where}
    ");

    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function tests_list_for_home(int $limit = 20, int $offset = 0): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $viewerId = auth_is_logged_in() ? (int)(auth_user()['id'] ?? 0) : 0;

    $where = 't.deleted_at IS NULL';
    if (!auth_is_logged_in()) {
        $where .= " AND t.access_level = 'public'";
    }

    $bookmarksJoin = '';
    if ($viewerId > 0) {
        $bookmarksJoin = 'LEFT JOIN test_bookmarks tb ON tb.test_id = t.id AND tb.user_id = :viewer_id';
    }

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.user_id,
            t.title,
            t.description,
            t.access_level,
            t.category_name,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            " . ($viewerId > 0 ? 'CASE WHEN tb.user_id IS NULL THEN 0 ELSE 1 END' : '0') . " AS is_bookmarked,
            (
                SELECT COUNT(*)
                FROM questions q
                WHERE q.test_id = t.id
            ) AS questions_count,
            (
                SELECT COUNT(*)
                FROM attempts a
                WHERE a.test_id = t.id
            ) AS attempts_count
        FROM tests t
        LEFT JOIN users u ON u.id = t.user_id
        {$bookmarksJoin}
        WHERE {$where}
        ORDER BY t.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");

    $params = [];
    if ($viewerId > 0) {
        $params[':viewer_id'] = $viewerId;
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function tests_count_bookmarked_by_user_id(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM test_bookmarks tb
        WHERE tb.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    return (int)$stmt->fetchColumn();
}

function tests_list_bookmarked_by_user_id_paginated(int $userId, int $limit, int $offset): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(t.id, tb.test_id) AS id,
            t.user_id,
            COALESCE(t.title, 'Удалённый тест') AS title,
            t.description,
            t.access_level,
            t.category_name,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            1 AS is_bookmarked,
            CASE
                WHEN t.id IS NULL THEN 'deleted'
                WHEN t.deleted_forever_at IS NOT NULL THEN 'deleted'
                WHEN t.deleted_at IS NOT NULL THEN 'trashed'
                ELSE 'available'
            END AS bookmark_availability,
            (
                SELECT COUNT(*)
                FROM questions q
                WHERE q.test_id = t.id
            ) AS questions_count,
            (
                SELECT COUNT(*)
                FROM attempts a
                WHERE a.test_id = t.id
            ) AS attempts_count
        FROM test_bookmarks tb
        LEFT JOIN tests t ON t.id = tb.test_id
        LEFT JOIN users u ON u.id = t.user_id
        WHERE tb.user_id = :user_id
        ORDER BY tb.created_at DESC, t.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

function tests_bookmark_toggle_by_user_id(int $userId, int $testId): ?array
{
    $pdo = db();

    $pdo->beginTransaction();
    try {
        $isBookmarked = false;

        // First try to remove current bookmark.
        $delStmt = $pdo->prepare("
            DELETE FROM test_bookmarks
            WHERE user_id = :user_id
              AND test_id = :test_id
            LIMIT 1
        ");
        $delStmt->execute([
            ':user_id' => $userId,
            ':test_id' => $testId,
        ]);
        $removed = $delStmt->rowCount() === 1;

        if ($removed) {
            $updStmt = $pdo->prepare("
                UPDATE tests
                SET bookmarks_count = GREATEST(0, bookmarks_count - 1)
                WHERE id = :id
                LIMIT 1
            ");
            $updStmt->execute([':id' => $testId]);
            $isBookmarked = false;
        } else {
            // Then try to add bookmark atomically only for active test.
            $insStmt = $pdo->prepare("
                INSERT IGNORE INTO test_bookmarks (user_id, test_id, created_at)
                SELECT :user_id, t.id, NOW()
                FROM tests t
                WHERE t.id = :test_id
                  AND t.deleted_at IS NULL
                  AND t.deleted_forever_at IS NULL
                LIMIT 1
            ");
            $insStmt->execute([
                ':user_id' => $userId,
                ':test_id' => $testId,
            ]);
            $inserted = $insStmt->rowCount() === 1;

            if ($inserted) {
                $updStmt = $pdo->prepare("
                    UPDATE tests
                    SET bookmarks_count = bookmarks_count + 1
                    WHERE id = :id
                    LIMIT 1
                ");
                $updStmt->execute([':id' => $testId]);
                $isBookmarked = true;
            } else {
                // No row inserted: either test does not exist/trashed or
                // bookmark already exists because of a concurrent request.
                $testStmt = $pdo->prepare("
                    SELECT id
                    FROM tests
                    WHERE id = :id
                      AND deleted_at IS NULL
                      AND deleted_forever_at IS NULL
                    LIMIT 1
                ");
                $testStmt->execute([':id' => $testId]);
                if ($testStmt->fetchColumn() === false) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    return null;
                }

                $existsStmt = $pdo->prepare("
                    SELECT 1
                    FROM test_bookmarks
                    WHERE user_id = :user_id
                      AND test_id = :test_id
                    LIMIT 1
                ");
                $existsStmt->execute([
                    ':user_id' => $userId,
                    ':test_id' => $testId,
                ]);
                $isBookmarked = $existsStmt->fetchColumn() !== false;
            }
        }

        $pdo->commit();

        $testCountStmt = $pdo->prepare("
            SELECT bookmarks_count
            FROM tests
            WHERE id = :id
            LIMIT 1
        ");
        $testCountStmt->execute([':id' => $testId]);
        $testBookmarksCount = (int)($testCountStmt->fetchColumn() ?: 0);

        $userCountStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM test_bookmarks
            WHERE user_id = :user_id
        ");
        $userCountStmt->execute([':user_id' => $userId]);
        $userBookmarksCount = (int)($userCountStmt->fetchColumn() ?: 0);

        return [
            'ok' => true,
            'is_bookmarked' => $isBookmarked,
            'test_bookmarks_count' => $testBookmarksCount,
            'user_bookmarks_count' => $userBookmarksCount,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function test_views_track(int $testId, ?int $userId = null): void
{
    $pdo = db();
    $viewerId = (int)($userId ?? 0);
    $sessionKey = session_id();
    if (!is_string($sessionKey) || $sessionKey === '') {
        $sessionKey = bin2hex(random_bytes(16));
    }

    // One unique viewer key per test:
    // - logged user: stable user-based key
    // - guest: session-based key
    $viewerKey = $viewerId > 0
        ? 'u:' . (string)$viewerId
        : 'g:' . $sessionKey;

    $pdo->beginTransaction();
    try {
        $insStmt = $pdo->prepare("
            INSERT INTO test_views (test_id, user_id, session_key, viewed_at)
            VALUES (:test_id, :user_id, :session_key, NOW())
            ON DUPLICATE KEY UPDATE viewed_at = viewed_at
        ");
        $insStmt->execute([
            ':test_id' => $testId,
            ':user_id' => $viewerId > 0 ? $viewerId : null,
            ':session_key' => $viewerKey,
        ]);

        $inserted = $insStmt->rowCount() === 1;
        if (!$inserted) {
            $pdo->commit();
            return;
        }

        $updStmt = $pdo->prepare("
            UPDATE tests
            SET views_count = views_count + 1
            WHERE id = :id
            LIMIT 1
        ");
        $updStmt->execute([':id' => $testId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function test_rating_upsert_by_user_id(int $testId, int $userId, int $rating): bool
{
    if ($rating < 1 || $rating > 5) {
        return false;
    }

    $pdo = db();

    $pdo->beginTransaction();
    try {
        $testExistsStmt = $pdo->prepare("
            SELECT id
            FROM tests
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
            FOR UPDATE
        ");
        $testExistsStmt->execute([':id' => $testId]);
        if ($testExistsStmt->fetchColumn() === false) {
            $pdo->rollBack();
            return false;
        }

        $upsertStmt = $pdo->prepare("
            INSERT INTO test_ratings (test_id, user_id, rating, created_at, updated_at)
            VALUES (:test_id, :user_id, :rating, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                updated_at = NOW()
        ");
        $upsertStmt->execute([
            ':test_id' => $testId,
            ':user_id' => $userId,
            ':rating' => $rating,
        ]);

        $recalcStmt = $pdo->prepare("
            UPDATE tests t
            SET
                t.rating_count = (
                    SELECT COUNT(*)
                    FROM test_ratings tr
                    WHERE tr.test_id = t.id
                ),
                t.rating_sum = (
                    SELECT COALESCE(SUM(tr2.rating), 0)
                    FROM test_ratings tr2
                    WHERE tr2.test_id = t.id
                )
            WHERE t.id = :id
            LIMIT 1
        ");
        $recalcStmt->execute([':id' => $testId]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function tests_find_active_by_id_and_user_id(int $testId, int $userId): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, user_id, title, description, access_level, created_at, updated_at
        FROM tests
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $testId,
        ':user_id' => $userId,
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
		WHERE id = :id
          AND user_id = :user_id
          AND deleted_at IS NULL
          AND deleted_forever_at IS NULL
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
        WHERE user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
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
        WHERE id = :id
          AND user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
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
        UPDATE tests
        SET deleted_forever_at = NOW()
        WHERE id = :id
          AND user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
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
        WHERE user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
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
        UPDATE tests
        SET deleted_forever_at = NOW()
        WHERE user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
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
        WHERE user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
    ");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return (int)$stmt->fetchColumn();
}

function tests_payload_cache_key(int $testId): string
{
    return 'test:' . $testId . ':payload';
}

function tests_payload_for_pass_uncached(int $testId): array
{
    $questions = questions_list_by_test_id($testId);
    $questionIds = [];
    foreach ($questions as $q) {
        $questionIds[] = (int)($q['id'] ?? 0);
    }

    $optionsByQuestionId = options_list_by_question_ids($questionIds);

    return [
        'questions' => $questions,
        'options_by_question_id' => $optionsByQuestionId,
    ];
}

function tests_payload_for_pass_cached(int $testId): array
{
    $cacheKey = tests_payload_cache_key($testId);
    $cached = cache_get($cacheKey);
    if (is_array($cached) && isset($cached['questions']) && isset($cached['options_by_question_id'])) {
        return $cached;
    }

    $payload = tests_payload_for_pass_uncached($testId);
    $ttl = (int)app_config('cache.test_payload_ttl_sec', 300);
    if ($ttl > 0) {
        cache_put($cacheKey, $payload, $ttl);
    }

    return $payload;
}

function tests_payload_cache_invalidate(int $testId): void
{
    cache_forget(tests_payload_cache_key($testId));
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

function options_full_list_by_question_ids(array $questionIds): array
{
    $questionIds = array_values(array_filter(array_map('intval', $questionIds), fn($v) => $v > 0));
    if (count($questionIds) === 0) {
        return [];
    }

    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));

    $stmt = $pdo->prepare("
        SELECT id, question_id, option_text, is_correct, position
        FROM options
        WHERE question_id IN ($placeholders)
        ORDER BY question_id ASC, position ASC, id ASC
    ");

    $stmt->execute($questionIds);
    $rows = $stmt->fetchAll();

    $grouped = [];
    foreach ($rows as $row) {
        $qid = (int)($row['question_id'] ?? 0);
        if ($qid <= 0) {
            continue;
        }
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
): bool
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

    return $stmt->rowCount() === 1;
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
        $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
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

function tests_update_by_id_and_user_id(int $testId, int $userId, string $title, string $description, string $accessLevel): bool
{
    $pdo = db();
    $stmt = $pdo->prepare("
        UPDATE tests
        SET title = :title,
            description = :description,
            access_level = :access_level,
            updated_at = NOW()
        WHERE id = :id
          AND user_id = :user_id
          AND deleted_at IS NULL
        LIMIT 1
    ");

    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':access_level' => $accessLevel,
        ':id' => $testId,
        ':user_id' => $userId,
    ]);

    return $stmt->rowCount() === 1;
}

function questions_delete_by_test_id(int $testId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("
        DELETE FROM questions
        WHERE test_id = :test_id
    ");
    $stmt->execute([
        ':test_id' => $testId,
    ]);

    return (int)$stmt->rowCount();
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
            duration_sec,
            total_questions,
            correct_count,
            wrong_count,
            percent
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
            duration_sec,
            total_questions,
            correct_count,
            wrong_count,
            percent
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

function tests_trash_list_by_user_id_paginated(int $userId, int $limit, int $offset): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);

    $stmt = $pdo->prepare("
        SELECT id, user_id, title, description, access_level, created_at, updated_at, deleted_at
        FROM tests
        WHERE user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
        ORDER BY deleted_at DESC, id DESC
        LIMIT {$limit} OFFSET {$offset}
    ");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return $stmt->fetchAll();
}

function tests_count_active_by_user_id(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests
        WHERE user_id = :user_id
          AND deleted_at IS NULL
    ");
    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return (int)$stmt->fetchColumn();
}

function tests_count_deleted_by_user_id(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests
        WHERE user_id = :user_id
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
    ");
    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return (int)$stmt->fetchColumn();
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
        $where[] = 'a.percent >= 90';
    } elseif ($status === 'good') {
        $where[] = 'a.percent >= 80 AND a.percent < 90';
    } elseif ($status === 'satisfactory' || $status === 'partial') {
        $where[] = 'a.percent >= 70 AND a.percent < 80';
    } elseif ($status === 'bad' || $status === 'wrong') {
        $where[] = 'a.percent < 70';
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
            a.correct_count,
            a.wrong_count,
            a.total_questions,
            a.percent,
            a.started_at,
            a.finished_at,
            t.id AS live_test_id,
            t.title AS live_test_title
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
