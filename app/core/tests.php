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

function normalize_test_attempt_limit(mixed $raw, bool $strictValidation = true): ?int
{
    $value = trim((string)$raw);
    if ($value === '' || $value === '0') {
        return null;
    }

    if (!preg_match('/^\d+$/', $value)) {
        if ($strictValidation) {
            throw new InvalidArgumentException('Количество попыток должно быть целым числом');
        }
        return null;
    }

    $limit = (int)$value;
    if ($limit <= 0) {
        return null;
    }
    if ($limit > 1000) {
        if ($strictValidation) {
            throw new InvalidArgumentException('Количество попыток не должно быть больше 1000');
        }
        return 1000;
    }

    return $limit;
}

function test_bool_setting(mixed $raw): int
{
    return (int)$raw === 1 ? 1 : 0;
}

function test_shuffle_questions_enabled(array $test): bool
{
    return (int)($test['shuffle_questions'] ?? 0) === 1;
}

function test_shuffle_answers_enabled(array $test): bool
{
    return (int)($test['shuffle_answers'] ?? 0) === 1;
}

function test_attempt_limit_from_row(array $test): ?int
{
    $limit = $test['attempt_limit'] ?? null;
    if ($limit === null || $limit === '') {
        return null;
    }
    $limit = (int)$limit;
    return $limit > 0 ? $limit : null;
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

function test_status_draft(): string
{
    return 'draft';
}

function test_status_published(): string
{
    return 'published';
}

function test_status_is_valid(string $status): bool
{
    return in_array($status, [test_status_draft(), test_status_published()], true);
}

function tests_is_draft_row(array $test): bool
{
    return (string)($test['status'] ?? test_status_published()) === test_status_draft();
}

function tests_is_published_row(array $test): bool
{
    return (string)($test['status'] ?? test_status_published()) === test_status_published();
}

function test_answers_mode_never(): int
{
    return 2;
}

function test_answers_mode_immediate(): int
{
    return 1;
}

function test_answers_mode_after_finish(): int
{
    return 0;
}

function test_answers_mode_from_value(mixed $raw): int
{
    $mode = (int)$raw;
    return in_array($mode, [
        test_answers_mode_never(),
        test_answers_mode_immediate(),
        test_answers_mode_after_finish(),
    ], true) ? $mode : test_answers_mode_after_finish();
}

function test_answers_show_immediately(array $test): bool
{
    return test_answers_mode_from_value($test['show_answers'] ?? test_answers_mode_after_finish()) === test_answers_mode_immediate();
}

function test_answers_reveal_after_finish(array $test): bool
{
    return test_answers_mode_from_value($test['show_answers'] ?? test_answers_mode_after_finish()) !== test_answers_mode_never();
}

function test_answers_mode_label(int $mode): string
{
    return match (test_answers_mode_from_value($mode)) {
        test_answers_mode_never() => 'Без ответов',
        test_answers_mode_immediate() => 'Ответы сразу',
        default => 'Ответы после завершения',
    };
}

// --- Tests ---

function tests_list_by_user_id(int $userId): array
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'user_id = :user_id AND ';

	$stmt = $pdo->prepare("
		SELECT
            id,
            user_id,
            title,
            description,
            access_level,
            status,
            published_at,
            last_saved_at,
            COALESCE(time_limit_sec, CASE WHEN time_limit_min IS NOT NULL THEN time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            time_limit_min,
            created_at,
            updated_at
		FROM tests
		WHERE {$ownerWhere}deleted_at IS NULL
		ORDER BY created_at DESC
	");

    $params = [];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

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
    ?int $timeLimitSec = null,
    string $status = 'published',
    ?string $publishedAt = null,
    ?string $lastSavedAt = null,
    int $showAnswers = 0,
    int $shuffleQuestions = 0,
    int $shuffleAnswers = 0,
    ?int $attemptLimit = null
): int
{
    $pdo = db();
    $primaryCategoryName = $categoryNames[0] ?? test_category_default_name();
    if (!test_status_is_valid($status)) {
        $status = test_status_published();
    }

    $stmt = $pdo->prepare("
        INSERT INTO tests (
            user_id,
            title,
            description,
            access_level,
            category_name,
            cover_image,
            time_limit_sec,
            show_answers,
            shuffle_questions,
            shuffle_answers,
            attempt_limit,
            status,
            published_at,
            last_saved_at
        )
        VALUES (
            :user_id,
            :title,
            :description,
            :access_level,
            :category_name,
            :cover_image,
            :time_limit_sec,
            :show_answers,
            :shuffle_questions,
            :shuffle_answers,
            :attempt_limit,
            :status,
            :published_at,
            :last_saved_at
        )
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':access_level' => $accessLevel,
        ':category_name' => $primaryCategoryName,
        ':cover_image' => $coverImage,
        ':time_limit_sec' => ($timeLimitSec !== null && $timeLimitSec > 0) ? $timeLimitSec : null,
        ':show_answers' => test_answers_mode_from_value($showAnswers),
        ':shuffle_questions' => test_bool_setting($shuffleQuestions),
        ':shuffle_answers' => test_bool_setting($shuffleAnswers),
        ':attempt_limit' => ($attemptLimit !== null && $attemptLimit > 0) ? $attemptLimit : null,
        ':status' => $status,
        ':published_at' => $publishedAt,
        ':last_saved_at' => $lastSavedAt,
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
    $statusWhere = auth_is_admin() ? '' : "AND t.status = 'published'";

    $stmt = $pdo->prepare("
		SELECT
            t.id,
            t.user_id,
            t.title,
            t.description,
            t.access_level,
            t.status,
            t.published_at,
            t.last_saved_at,
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
            t.show_answers,
            t.shuffle_questions,
            t.shuffle_answers,
            t.attempt_limit,
            (SELECT COUNT(*) FROM attempts a WHERE a.test_id = t.id) AS attempts_count
		FROM tests t
        LEFT JOIN users u ON u.id = t.user_id
		WHERE t.id = :id
          AND t.deleted_at IS NULL
          {$statusWhere}
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

function tests_list_by_user_id_paginated(int $userId, int $limit, int $offset, ?string $categoryName = null, string $sort = 'new'): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 't.user_id = :user_id AND ';
    $params = [
        ':viewer_id' => $userId,
    ];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $categoryWhere = '';
    if ($categoryName !== null && trim($categoryName) !== '') {
        $categoryWhere = ' AND ' . tests_category_filter_sql($categoryName, $params, 't', 'my_tests_category');
    }
    $orderBy = tests_user_order_by($sort);

    $stmt = $pdo->prepare("
		SELECT
            t.id,
            t.user_id,
            t.title,
            t.description,
            t.access_level,
            t.status,
            t.published_at,
            t.last_saved_at,
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
            t.show_answers,
            t.shuffle_questions,
            t.shuffle_answers,
            t.attempt_limit,
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
		WHERE {$ownerWhere}t.deleted_at IS NULL
          {$categoryWhere}
		ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
	");

    $stmt->execute($params);

    return tests_attach_category_names($stmt->fetchAll());
}

function tests_count_for_home(): int
{
    $pdo = db();

    $where = "t.deleted_at IS NULL AND t.status = 'published'";

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
    $categoryNames = test_category_match_names($categoryName);
    $primaryPlaceholders = [];
    $linkPlaceholders = [];
    $params = [];
    foreach ($categoryNames as $index => $name) {
        $primaryParam = ':category_name_primary_' . $index;
        $linkParam = ':category_name_link_' . $index;
        $primaryPlaceholders[] = $primaryParam;
        $linkPlaceholders[] = $linkParam;
        $params[$primaryParam] = $name;
        $params[$linkParam] = $name;
    }

    $where = "t.deleted_at IS NULL
      AND t.status = 'published'
      AND (
        t.category_name IN (" . implode(', ', $primaryPlaceholders) . ")
        OR EXISTS (
          SELECT 1
          FROM test_category_links tcl
          WHERE tcl.test_id = t.id
            AND tcl.category_name IN (" . implode(', ', $linkPlaceholders) . ")
        )
    )";

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests t
        WHERE {$where}
    ");

    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function tests_category_counts_for_home(): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT category_name, COUNT(DISTINCT test_id) AS tests_count
        FROM (
            SELECT t.id AS test_id, t.category_name AS category_name
            FROM tests t
            WHERE t.deleted_at IS NULL
              AND t.status = 'published'
              AND t.category_name IS NOT NULL
              AND t.category_name <> ''

            UNION

            SELECT t.id AS test_id, tcl.category_name AS category_name
            FROM tests t
            INNER JOIN test_category_links tcl ON tcl.test_id = t.id
            WHERE t.deleted_at IS NULL
              AND t.status = 'published'
              AND tcl.category_name IS NOT NULL
              AND tcl.category_name <> ''
        ) category_tests
        GROUP BY category_name
    ");
    $stmt->execute();

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $categoryName = trim((string)($row['category_name'] ?? ''));
        if ($categoryName === '') {
            continue;
        }

        $canonicalName = test_category_display_name($categoryName);
        $counts[$canonicalName] = (int)($counts[$canonicalName] ?? 0) + (int)($row['tests_count'] ?? 0);
    }

    return $counts;
}

function tests_home_order_by(string $sort): string
{
    return match ($sort) {
        'questions' => 'questions_count DESC, t.created_at DESC, t.id DESC',
        'time' => 'time_limit_sec DESC, t.created_at DESC, t.id DESC',
        'views' => 't.views_count DESC, t.created_at DESC, t.id DESC',
        'attempts' => 'attempts_count DESC, t.created_at DESC, t.id DESC',
        default => 't.created_at DESC, t.id DESC',
    };
}

function tests_user_order_by(string $sort): string
{
    return match ($sort) {
        'questions' => 'questions_count DESC, COALESCE(t.last_saved_at, t.updated_at, t.created_at) DESC, t.id DESC',
        'time' => 'time_limit_sec DESC, COALESCE(t.last_saved_at, t.updated_at, t.created_at) DESC, t.id DESC',
        'views' => 't.views_count DESC, COALESCE(t.last_saved_at, t.updated_at, t.created_at) DESC, t.id DESC',
        'attempts' => 'attempts_count DESC, COALESCE(t.last_saved_at, t.updated_at, t.created_at) DESC, t.id DESC',
        default => 'COALESCE(t.last_saved_at, t.updated_at, t.created_at) DESC, t.id DESC',
    };
}

function tests_bookmarks_order_by(string $sort): string
{
    return match ($sort) {
        'questions' => 'questions_count DESC, tb.created_at DESC, t.id DESC',
        'time' => 'time_limit_sec DESC, tb.created_at DESC, t.id DESC',
        'views' => 't.views_count DESC, tb.created_at DESC, t.id DESC',
        'attempts' => 'attempts_count DESC, tb.created_at DESC, t.id DESC',
        default => 'tb.created_at DESC, t.id DESC',
    };
}

function tests_category_filter_sql(string $categoryName, array &$params, string $alias = 't', string $prefix = 'category'): string
{
    $categoryNames = test_category_match_names($categoryName);
    $primaryPlaceholders = [];
    $linkPlaceholders = [];

    foreach ($categoryNames as $index => $name) {
        $primaryParam = ':' . $prefix . '_primary_' . $index;
        $linkParam = ':' . $prefix . '_link_' . $index;
        $primaryPlaceholders[] = $primaryParam;
        $linkPlaceholders[] = $linkParam;
        $params[$primaryParam] = $name;
        $params[$linkParam] = $name;
    }

    return "(
        {$alias}.category_name IN (" . implode(', ', $primaryPlaceholders) . ")
        OR EXISTS (
            SELECT 1
            FROM test_category_links tcl
            WHERE tcl.test_id = {$alias}.id
              AND tcl.category_name IN (" . implode(', ', $linkPlaceholders) . ")
        )
    )";
}

function tests_list_for_home(int $limit = 20, int $offset = 0, string $sort = 'new'): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $viewerId = auth_is_logged_in() ? (int)(auth_user()['id'] ?? 0) : 0;

    $where = "t.deleted_at IS NULL AND t.status = 'published'";

    $bookmarksJoin = '';
    if ($viewerId > 0) {
        $bookmarksJoin = 'LEFT JOIN test_bookmarks tb ON tb.test_id = t.id AND tb.user_id = :viewer_id';
    }

    $orderBy = tests_home_order_by($sort);

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.user_id,
            t.title,
            t.description,
            t.access_level,
            t.status,
            t.category_name,
            COALESCE(t.time_limit_sec, CASE WHEN t.time_limit_min IS NOT NULL THEN t.time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            t.time_limit_min,
            t.created_at,
            t.updated_at,
            t.published_at,
            t.last_saved_at,
            t.bookmarks_count,
            t.views_count,
            t.rating_count,
            t.rating_sum,
            COALESCE(u.login, '') AS creator_login,
            t.cover_image,
            t.show_answers,
            t.shuffle_questions,
            t.shuffle_answers,
            t.attempt_limit,
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
        ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
    ");

    $params = [];
    if ($viewerId > 0) {
        $params[':viewer_id'] = $viewerId;
    }
    $stmt->execute($params);
    return tests_attach_category_names($stmt->fetchAll());
}

function tests_list_for_category(string $categoryName, int $limit = 20, int $offset = 0, string $sort = 'new'): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $viewerId = auth_is_logged_in() ? (int)(auth_user()['id'] ?? 0) : 0;
    $categoryNames = test_category_match_names($categoryName);
    $primaryPlaceholders = [];
    $linkPlaceholders = [];
    $params = [];
    foreach ($categoryNames as $index => $name) {
        $primaryParam = ':category_name_primary_' . $index;
        $linkParam = ':category_name_link_' . $index;
        $primaryPlaceholders[] = $primaryParam;
        $linkPlaceholders[] = $linkParam;
        $params[$primaryParam] = $name;
        $params[$linkParam] = $name;
    }

    $where = "t.deleted_at IS NULL
      AND t.status = 'published'
      AND (
        t.category_name IN (" . implode(', ', $primaryPlaceholders) . ")
        OR EXISTS (
          SELECT 1
          FROM test_category_links tcl
          WHERE tcl.test_id = t.id
            AND tcl.category_name IN (" . implode(', ', $linkPlaceholders) . ")
        )
    )";

    $bookmarksJoin = '';
    if ($viewerId > 0) {
        $bookmarksJoin = 'LEFT JOIN test_bookmarks tb ON tb.test_id = t.id AND tb.user_id = :viewer_id';
    }

    $orderBy = tests_home_order_by($sort);

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
            t.show_answers,
            t.shuffle_questions,
            t.shuffle_answers,
            t.attempt_limit,
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
        ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
    ");

    if ($viewerId > 0) {
        $params[':viewer_id'] = $viewerId;
    }

    $stmt->execute($params);
    return tests_attach_category_names($stmt->fetchAll());
}

function tests_count_bookmarked_by_user_id(int $userId, ?string $categoryName = null): int
{
    $pdo = db();
    $params = [':user_id' => $userId];
    $categoryWhere = '';
    if ($categoryName !== null && trim($categoryName) !== '') {
        $categoryWhere = ' AND t.id IS NOT NULL AND ' . tests_category_filter_sql($categoryName, $params, 't', 'bookmarks_count_category');
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM test_bookmarks tb
        LEFT JOIN tests t ON t.id = tb.test_id
        WHERE tb.user_id = :user_id
          {$categoryWhere}
    ");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function tests_list_bookmarked_by_user_id_paginated(int $userId, int $limit, int $offset, ?string $categoryName = null, string $sort = 'new'): array
{
    $pdo = db();
    $limit = max(1, min(100, $limit));
    $offset = max(0, $offset);
    $params = [':user_id' => $userId];
    $categoryWhere = '';
    if ($categoryName !== null && trim($categoryName) !== '') {
        $categoryWhere = ' AND t.id IS NOT NULL AND ' . tests_category_filter_sql($categoryName, $params, 't', 'bookmarks_list_category');
    }
    $orderBy = tests_bookmarks_order_by($sort);

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
            t.show_answers,
            t.shuffle_questions,
            t.shuffle_answers,
            t.attempt_limit,
            1 AS is_bookmarked,
            CASE
                WHEN t.id IS NULL THEN 'deleted'
                WHEN t.deleted_forever_at IS NOT NULL THEN 'deleted'
                WHEN t.deleted_at IS NOT NULL THEN 'trashed'
                WHEN t.status <> 'published' THEN 'deleted'
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
          {$categoryWhere}
        ORDER BY {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);
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
                  AND t.status = 'published'
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
                      AND status = 'published'
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
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'AND user_id = :user_id';

    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            title,
            description,
            access_level,
            status,
            published_at,
            last_saved_at,
            category_name,
            cover_image,
            show_answers,
            shuffle_questions,
            shuffle_answers,
            attempt_limit,
            COALESCE(time_limit_sec, CASE WHEN time_limit_min IS NOT NULL THEN time_limit_min * 60 ELSE NULL END) AS time_limit_sec,
            time_limit_min,
            created_at,
            updated_at
        FROM tests
        WHERE id = :id
          {$ownerWhere}
          AND deleted_at IS NULL
        LIMIT 1
    ");

    $params = [
        ':id' => $testId,
    ];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

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
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'AND user_id = :user_id';

	$stmt = $pdo->prepare("
		UPDATE tests
		SET deleted_at = NOW()
		WHERE id = :id
          {$ownerWhere}
          AND deleted_at IS NULL
          AND deleted_forever_at IS NULL
		LIMIT 1
	");

    $params = [
        ':id' => $testId,
    ];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

    return $stmt->rowCount() === 1;
}

function tests_trash_list_by_user_id(int $userId): array
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'user_id = :user_id AND ';

    $stmt = $pdo->prepare("
        SELECT id, user_id, title, description, access_level, created_at, updated_at, deleted_at
        FROM tests
        WHERE {$ownerWhere}deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
        ORDER BY deleted_at DESC
    ");

    $params = [];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function tests_restore_by_id_and_user_id(int $testId, int $userId): bool
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'AND user_id = :user_id';

    $stmt = $pdo->prepare("
        UPDATE tests
        SET deleted_at = NULL
        WHERE id = :id
          {$ownerWhere}
          AND deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
        LIMIT 1
    ");

    $params = [
        ':id' => $testId,
    ];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

    return $stmt->rowCount() === 1;
}

function tests_destroy_by_id_and_user_id(int $testId, int $userId): bool
{
    $pdo = db();
    $uploadPaths = [];
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'AND user_id = :user_id';

    $pdo->beginTransaction();
    try {
        $uploadPaths = tests_upload_paths_by_test_ids([$testId], $pdo);

        $stmt = $pdo->prepare("
            DELETE FROM tests
            WHERE id = :id
              {$ownerWhere}
              AND deleted_at IS NOT NULL
              AND deleted_forever_at IS NULL
            LIMIT 1
        ");

        $params = [
            ':id' => $testId,
        ];
        if (!$isAdmin) {
            $params[':user_id'] = $userId;
        }
        $stmt->execute($params);

        $deleted = $stmt->rowCount() === 1;
        if (!$deleted) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    tests_delete_unreferenced_uploads($uploadPaths);
    return true;
}

function tests_trash_restore_all_by_user_id(int $userId): int
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'user_id = :user_id AND ';

    $stmt = $pdo->prepare("
        UPDATE tests
        SET deleted_at = NULL
        WHERE {$ownerWhere}deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
    ");

    $params = [];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

    return (int)$stmt->rowCount();
}

function tests_trash_empty_by_user_id(int $userId): int
{
    $pdo = db();
    $uploadPaths = [];
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'user_id = :user_id AND ';

    $pdo->beginTransaction();
    try {
        $idStmt = $pdo->prepare("
            SELECT id
            FROM tests
            WHERE {$ownerWhere}deleted_at IS NOT NULL
              AND deleted_forever_at IS NULL
        ");
        $params = [];
        if (!$isAdmin) {
            $params[':user_id'] = $userId;
        }
        $idStmt->execute($params);
        $testIds = array_map('intval', $idStmt->fetchAll(PDO::FETCH_COLUMN));
        if ($testIds === []) {
            $pdo->rollBack();
            return 0;
        }

        $uploadPaths = tests_upload_paths_by_test_ids($testIds, $pdo);

        $stmt = $pdo->prepare("
            DELETE FROM tests
            WHERE {$ownerWhere}deleted_at IS NOT NULL
              AND deleted_forever_at IS NULL
        ");

        $stmt->execute($params);

        $deletedCount = (int)$stmt->rowCount();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    tests_delete_unreferenced_uploads($uploadPaths);
    return $deletedCount;
}

function tests_trash_count_by_user_id(int $userId): int
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'user_id = :user_id AND ';

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests
        WHERE {$ownerWhere}deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
    ");

    $params = [];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

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
    ?int $timeLimitSec = null,
    ?string $status = null,
    ?string $publishedAt = null,
    bool $touchLastSavedAt = true,
    int $showAnswers = 0,
    int $shuffleQuestions = 0,
    int $shuffleAnswers = 0,
    ?int $attemptLimit = null
): bool
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'AND user_id = :user_id';
    $primaryCategoryName = $categoryNames[0] ?? test_category_default_name();
    if ($status !== null && !test_status_is_valid($status)) {
        $status = null;
    }
    $stmt = $pdo->prepare("
        UPDATE tests
        SET title = :title,
            description = :description,
            access_level = :access_level,
            category_name = :category_name,
            cover_image = :cover_image,
            time_limit_sec = :time_limit_sec,
            show_answers = :show_answers,
            shuffle_questions = :shuffle_questions,
            shuffle_answers = :shuffle_answers,
            attempt_limit = :attempt_limit,
            status = COALESCE(:status, status),
            published_at = CASE
                WHEN :published_at_check IS NULL THEN published_at
                ELSE :published_at_value
            END,
            last_saved_at = CASE
                WHEN :touch_last_saved_at = 1 THEN NOW()
                ELSE last_saved_at
            END,
            updated_at = NOW()
        WHERE id = :id
          {$ownerWhere}
          AND deleted_at IS NULL
        LIMIT 1
    ");

    $params = [
        ':title' => $title,
        ':description' => $description,
        ':access_level' => $accessLevel,
        ':category_name' => $primaryCategoryName,
        ':cover_image' => $coverImage,
        ':time_limit_sec' => ($timeLimitSec !== null && $timeLimitSec > 0) ? $timeLimitSec : null,
        ':show_answers' => test_answers_mode_from_value($showAnswers),
        ':shuffle_questions' => test_bool_setting($shuffleQuestions),
        ':shuffle_answers' => test_bool_setting($shuffleAnswers),
        ':attempt_limit' => ($attemptLimit !== null && $attemptLimit > 0) ? $attemptLimit : null,
        ':status' => $status,
        ':published_at_check' => $publishedAt,
        ':published_at_value' => $publishedAt,
        ':touch_last_saved_at' => $touchLastSavedAt ? 1 : 0,
        ':id' => $testId,
    ];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

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
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'user_id = :user_id AND ';

    $stmt = $pdo->prepare("
        SELECT id, user_id, title, description, access_level, created_at, updated_at, deleted_at
        FROM tests
        WHERE {$ownerWhere}deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
        ORDER BY deleted_at DESC, id DESC
        LIMIT {$limit} OFFSET {$offset}
    ");

    $params = [];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function tests_count_active_by_user_id(int $userId, ?string $categoryName = null): int
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 't.user_id = :user_id AND ';
    $params = [];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $categoryWhere = '';
    if ($categoryName !== null && trim($categoryName) !== '') {
        $categoryWhere = ' AND ' . tests_category_filter_sql($categoryName, $params, 't', 'active_count_category');
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests t
        WHERE {$ownerWhere}t.deleted_at IS NULL
          {$categoryWhere}
    ");
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function tests_category_counts_by_user_id(int $userId): array
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWherePrimary = $isAdmin ? '' : 'AND t.user_id = :user_id_primary';
    $ownerWhereLinked = $isAdmin ? '' : 'AND t.user_id = :user_id_linked';

    $stmt = $pdo->prepare("
        SELECT category_name, COUNT(DISTINCT test_id) AS tests_count
        FROM (
            SELECT t.id AS test_id, t.category_name AS category_name
            FROM tests t
            WHERE t.deleted_at IS NULL
              {$ownerWherePrimary}
              AND t.category_name IS NOT NULL
              AND t.category_name <> ''

            UNION

            SELECT t.id AS test_id, tcl.category_name AS category_name
            FROM tests t
            INNER JOIN test_category_links tcl ON tcl.test_id = t.id
            WHERE t.deleted_at IS NULL
              {$ownerWhereLinked}
              AND tcl.category_name IS NOT NULL
              AND tcl.category_name <> ''
        ) category_tests
        GROUP BY category_name
    ");
    $params = [];
    if (!$isAdmin) {
        $params[':user_id_primary'] = $userId;
        $params[':user_id_linked'] = $userId;
    }
    $stmt->execute($params);

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $categoryName = trim((string)($row['category_name'] ?? ''));
        if ($categoryName === '') {
            continue;
        }

        $canonicalName = test_category_display_name($categoryName);
        $counts[$canonicalName] = (int)($counts[$canonicalName] ?? 0) + (int)($row['tests_count'] ?? 0);
    }

    return $counts;
}

function tests_category_counts_bookmarked_by_user_id(int $userId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT category_name, COUNT(DISTINCT test_id) AS tests_count
        FROM (
            SELECT t.id AS test_id, t.category_name AS category_name
            FROM test_bookmarks tb
            INNER JOIN tests t ON t.id = tb.test_id
            WHERE tb.user_id = :user_id_primary
              AND t.category_name IS NOT NULL
              AND t.category_name <> ''

            UNION

            SELECT t.id AS test_id, tcl.category_name AS category_name
            FROM test_bookmarks tb
            INNER JOIN tests t ON t.id = tb.test_id
            INNER JOIN test_category_links tcl ON tcl.test_id = t.id
            WHERE tb.user_id = :user_id_linked
              AND tcl.category_name IS NOT NULL
              AND tcl.category_name <> ''
        ) category_tests
        GROUP BY category_name
    ");
    $stmt->execute([
        ':user_id_primary' => $userId,
        ':user_id_linked' => $userId,
    ]);

    $counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $categoryName = trim((string)($row['category_name'] ?? ''));
        if ($categoryName === '') {
            continue;
        }

        $canonicalName = test_category_display_name($categoryName);
        $counts[$canonicalName] = (int)($counts[$canonicalName] ?? 0) + (int)($row['tests_count'] ?? 0);
    }

    return $counts;
}

function tests_count_deleted_by_user_id(int $userId): int
{
    $pdo = db();
    $isAdmin = auth_is_admin();
    $ownerWhere = $isAdmin ? '' : 'user_id = :user_id AND ';
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tests
        WHERE {$ownerWhere}deleted_at IS NOT NULL
          AND deleted_forever_at IS NULL
    ");
    $params = [];
    if (!$isAdmin) {
        $params[':user_id'] = $userId;
    }
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function tests_upload_paths_by_test_ids(array $testIds, ?PDO $pdo = null): array
{
    $testIds = array_values(array_unique(array_filter(array_map('intval', $testIds), static fn(int $id): bool => $id > 0)));
    if ($testIds === []) {
        return [];
    }

    $pdo ??= db();
    $placeholders = implode(',', array_fill(0, count($testIds), '?'));
    $paths = [];

    $queries = [
        "SELECT cover_image AS path FROM tests WHERE id IN ($placeholders) AND cover_image IS NOT NULL AND cover_image <> ''",
        "SELECT q.image_path AS path FROM questions q WHERE q.test_id IN ($placeholders) AND q.image_path IS NOT NULL AND q.image_path <> ''",
        "SELECT o.image_path AS path
         FROM options o
         INNER JOIN questions q ON q.id = o.question_id
         WHERE q.test_id IN ($placeholders)
           AND o.image_path IS NOT NULL
           AND o.image_path <> ''",
    ];

    foreach ($queries as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($testIds);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
            $path = trim((string)$path);
            if ($path === '' || !str_starts_with($path, '/uploads/')) {
                continue;
            }
            $paths[$path] = true;
        }
    }

    return array_keys($paths);
}

function tests_upload_path_is_still_referenced(string $path, ?PDO $pdo = null): bool
{
    $path = trim($path);
    if ($path === '' || !str_starts_with($path, '/uploads/')) {
        return false;
    }

    $pdo ??= db();
    $queries = [
        'SELECT 1 FROM tests WHERE cover_image = :path LIMIT 1',
        'SELECT 1 FROM questions WHERE image_path = :path LIMIT 1',
        'SELECT 1 FROM options WHERE image_path = :path LIMIT 1',
    ];

    foreach ($queries as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':path' => $path,
        ]);
        if ($stmt->fetchColumn() !== false) {
            return true;
        }
    }

    return false;
}

function tests_delete_unreferenced_uploads(array $paths): void
{
    if ($paths === []) {
        return;
    }

    $pdo = db();
    foreach (array_values(array_unique(array_filter(array_map('strval', $paths)))) as $path) {
        if (tests_upload_path_is_still_referenced($path, $pdo)) {
            continue;
        }

        upload_delete_file($path);
    }
}
