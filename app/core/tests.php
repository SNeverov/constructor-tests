<?php
declare(strict_types=1);

// --- Domain helpers (also used by controllers) ---

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

function normalize_test_time_limit_sec(?string $raw): ?int
{
    $raw = trim((string)$raw);
    if ($raw === '' || $raw === '00:00' || $raw === '00:00:00') {
        return null;
    }

    if (!preg_match('/^(?<hours>\d{2}):(?<minutes>\d{2})(?::(?<seconds>\d{2}))?$/', $raw, $m)) {
        throw new InvalidArgumentException('Некорректный формат лимита времени');
    }

    $hours = (int)($m['hours'] ?? 0);
    $minutes = (int)($m['minutes'] ?? 0);
    $seconds = isset($m['seconds']) ? (int)$m['seconds'] : 0;

    if ($minutes > 59 || $seconds > 59) {
        throw new InvalidArgumentException('Некорректное значение лимита времени');
    }

    $total = ($hours * 3600) + ($minutes * 60) + $seconds;
    if ($total <= 0) {
        return null;
    }

    $max = 23 * 3600 + 59 * 60 + 59;
    if ($total > $max) {
        throw new InvalidArgumentException('Лимит времени слишком большой');
    }

    return $total;
}

function format_time_limit_hms(?int $seconds): string
{
    $seconds = ($seconds !== null) ? max(0, (int)$seconds) : 0;
    if ($seconds <= 0) {
        return '00:00:00';
    }

    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

// --- Tests ---

function tests_list_by_user_id(int $userId): array
{
    $pdo = db();

	$stmt = $pdo->prepare("
		SELECT
            id,
            user_id,
            title,
            description,
            access_level,
            COALESCE(time_limit_sec, CASE WHEN time_limit_min IS NOT NULL THEN time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            time_limit_min,
            created_at,
            updated_at
		FROM tests
		WHERE user_id = :user_id AND deleted_at IS NULL
		ORDER BY created_at DESC
	");

    $stmt->execute([
        ':user_id' => $userId,
    ]);

    return $stmt->fetchAll();
}

function test_time_limit_sec_from_row(array $test): ?int
{
    $seconds = $test['time_limit_sec'] ?? null;
    if ($seconds !== null && $seconds !== '') {
        $seconds = (int)$seconds;
        return $seconds > 0 ? $seconds : null;
    }

    $minutes = $test['time_limit_min'] ?? null;
    if ($minutes !== null && $minutes !== '') {
        $minutes = (int)$minutes;
        if ($minutes > 0) {
            return $minutes * 60;
        }
    }

    return null;
}

function test_categories_replace_by_test_id(int $testId, array $categoryNames): void
{
    $pdo = db();

    $deleteStmt = $pdo->prepare('DELETE FROM test_category_links WHERE test_id = :test_id');
    $deleteStmt->execute([
        ':test_id' => $testId,
    ]);

    $insertStmt = $pdo->prepare('
        INSERT INTO test_category_links (test_id, category_name)
        VALUES (:test_id, :category_name)
    ');

    foreach ($categoryNames as $categoryName) {
        $insertStmt->execute([
            ':test_id' => $testId,
            ':category_name' => $categoryName,
        ]);
    }
}

function test_categories_names_map_by_test_ids(array $testIds): array
{
    $testIds = array_values(array_unique(array_filter(array_map('intval', $testIds), static fn(int $id): bool => $id > 0)));
    if ($testIds === []) {
        return [];
    }

    $pdo = db();
    $placeholders = [];
    $params = [];
    foreach ($testIds as $index => $testId) {
        $key = ':id_' . $index;
        $placeholders[] = $key;
        $params[$key] = $testId;
    }

    $stmt = $pdo->prepare('
        SELECT test_id, category_name
        FROM test_category_links
        WHERE test_id IN (' . implode(', ', $placeholders) . ')
        ORDER BY category_name ASC
    ');
    $stmt->execute($params);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $testId = (int)($row['test_id'] ?? 0);
        $categoryName = trim((string)($row['category_name'] ?? ''));
        if ($testId <= 0 || $categoryName === '') {
            continue;
        }

        $map[$testId] ??= [];
        $map[$testId][] = $categoryName;
    }

    return $map;
}

function tests_attach_category_names(array $tests): array
{
    if ($tests === []) {
        return [];
    }

    $testIds = [];
    foreach ($tests as $test) {
        $testIds[] = (int)($test['id'] ?? 0);
    }

    $categoryMap = test_categories_names_map_by_test_ids($testIds);

    foreach ($tests as $index => $test) {
        $testId = (int)($test['id'] ?? 0);
        $tests[$index]['category_names'] = $categoryMap[$testId] ?? test_category_display_names($test['category_name'] ?? null);
    }

    return $tests;
}

function tests_create(
    int $userId,
    string $title,
    string $description,
    string $accessLevel,
    array $categoryNames,
    ?string $coverImage = null,
    ?int $timeLimitSec = null
): int
{
    $pdo = db();
    $primaryCategoryName = $categoryNames[0] ?? test_category_default_name();

    $stmt = $pdo->prepare("
        INSERT INTO tests (user_id, title, description, access_level, category_name, cover_image, time_limit_sec)
        VALUES (:user_id, :title, :description, :access_level, :category_name, :cover_image, :time_limit_sec)
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':access_level' => $accessLevel,
        ':category_name' => $primaryCategoryName,
        ':cover_image' => $coverImage,
        ':time_limit_sec' => ($timeLimitSec !== null && $timeLimitSec > 0) ? $timeLimitSec : null,
    ]);

    return (int)$pdo->lastInsertId();
}

function questions_create(
    int $testId,
    string $type,
    string $questionText,
    int $position,
    ?string $imagePath = null
): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO questions (test_id, type, question_text, position, image_path)
        VALUES (:test_id, :type, :question_text, :position, :image_path)
    ");

    $stmt->execute([
        ':test_id' => $testId,
        ':type' => $type,
        ':question_text' => $questionText,
        ':position' => $position,
        ':image_path' => $imagePath,
    ]);

    return (int)$pdo->lastInsertId();
}

function options_create(
    int $questionId,
    string $optionText,
    int $isCorrect,
    int $position,
    ?string $imagePath = null
): int
{
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO options (question_id, option_text, is_correct, position, image_path)
        VALUES (:question_id, :option_text, :is_correct, :position, :image_path)
    ");

    $stmt->execute([
        ':question_id' => $questionId,
        ':option_text' => $optionText,
        ':is_correct' => $isCorrect,
        ':position' => $position,
        ':image_path' => $imagePath,
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
            COALESCE(t.time_limit_sec, CASE WHEN t.time_limit_min IS NOT NULL THEN t.time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            t.cover_image,
            (SELECT COUNT(*) FROM attempts a WHERE a.test_id = t.id) AS attempts_count
		FROM tests t
        LEFT JOIN users u ON u.id = t.user_id
		WHERE t.id = :id AND t.deleted_at IS NULL
		LIMIT 1
	");

    $stmt->execute([
        ':id' => $testId,
    ]);

    $row = $stmt->fetch();
    if ($row === false) {
        return null;
    }

    $rows = tests_attach_category_names([$row]);
    return $rows[0] ?? null;
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
            COALESCE(t.time_limit_sec, CASE WHEN t.time_limit_min IS NOT NULL THEN t.time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            t.cover_image,
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

    return tests_attach_category_names($stmt->fetchAll());
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

function tests_count_for_category(string $categoryName): int
{
    $pdo = db();

    $where = "t.deleted_at IS NULL AND EXISTS (
        SELECT 1
        FROM test_category_links tcl
        WHERE tcl.test_id = t.id
          AND tcl.category_name = :category_name
    )";
    if (!auth_is_logged_in()) {
        $where .= " AND t.access_level = 'public'";
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests t
        WHERE {$where}
    ");

    $stmt->execute([
        ':category_name' => $categoryName,
    ]);

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
            COALESCE(t.time_limit_sec, CASE WHEN t.time_limit_min IS NOT NULL THEN t.time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            t.cover_image,
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
    return tests_attach_category_names($stmt->fetchAll());
}

function tests_list_for_category(string $categoryName, int $limit = 20, int $offset = 0): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $viewerId = auth_is_logged_in() ? (int)(auth_user()['id'] ?? 0) : 0;

    $where = "t.deleted_at IS NULL AND EXISTS (
        SELECT 1
        FROM test_category_links tcl
        WHERE tcl.test_id = t.id
          AND tcl.category_name = :category_name
    )";
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
            COALESCE(t.time_limit_sec, CASE WHEN t.time_limit_min IS NOT NULL THEN t.time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            t.cover_image,
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

    $params = [
        ':category_name' => $categoryName,
    ];
    if ($viewerId > 0) {
        $params[':viewer_id'] = $viewerId;
    }

    $stmt->execute($params);
    return tests_attach_category_names($stmt->fetchAll());
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
            COALESCE(t.time_limit_sec, CASE WHEN t.time_limit_min IS NOT NULL THEN t.time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            t.cover_image,
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
    return tests_attach_category_names($stmt->fetchAll());
}

function tests_bookmark_toggle_by_user_id(int $userId, int $testId): ?array
{
    $pdo = db();

    $pdo->beginTransaction();
    try {
        $isBookmarked = false;

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
        SELECT
            id,
            user_id,
            title,
            description,
            access_level,
            category_name,
            cover_image,
            COALESCE(time_limit_sec, CASE WHEN time_limit_min IS NOT NULL THEN time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            time_limit_min,
            created_at,
            updated_at
        FROM tests
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $testId,
        ':user_id' => $userId,
    ]);

    $row = $stmt->fetch();
    if ($row === false) {
        return null;
    }

    $rows = tests_attach_category_names([$row]);
    return $rows[0] ?? null;
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

// --- Cache ---

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

// --- Questions & Options ---

function questions_list_by_test_id(int $testId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, test_id, type, question_text, image_path, position
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
        SELECT id, question_id, option_text, image_path, position
        FROM options
        WHERE question_id IN ($placeholders)
        ORDER BY question_id ASC, position ASC, id ASC
    ");

    $stmt->execute($questionIds);

    $rows = $stmt->fetchAll();

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
        SELECT id, question_id, option_text, image_path, is_correct, position
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

function tests_update_by_id_and_user_id(
    int $testId,
    int $userId,
    string $title,
    string $description,
    string $accessLevel,
    array $categoryNames,
    ?string $coverImage = null,
    ?int $timeLimitSec = null
): bool
{
    $pdo = db();
    $primaryCategoryName = $categoryNames[0] ?? test_category_default_name();
    $stmt = $pdo->prepare("
        UPDATE tests
        SET title = :title,
            description = :description,
            access_level = :access_level,
            category_name = :category_name,
            cover_image = :cover_image,
            time_limit_sec = :time_limit_sec,
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
        ':category_name' => $primaryCategoryName,
        ':cover_image' => $coverImage,
        ':time_limit_sec' => ($timeLimitSec !== null && $timeLimitSec > 0) ? $timeLimitSec : null,
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
