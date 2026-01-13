<?php
declare(strict_types=1);

function tests_list_by_user_id(int $userId): array
{
    $sql = 'SELECT id, user_id, title, description, access_level, created_at, updated_at
            FROM tests
            WHERE user_id = ?
            ORDER BY created_at DESC';

    $stmt = mysqli_prepare(db(), $sql);

    if (!$stmt) {
        die('DB PREPARE ERROR: ' . mysqli_error(db()));
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        die('DB RESULT ERROR: ' . mysqli_error(db()));
    }

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);

    return $rows ?: [];
}

function tests_create(int $userId, string $title, string $description, string $accessLevel): void
{
    $sql = 'INSERT INTO tests (user_id, title, description, access_level)
            VALUES (?, ?, ?, ?)';

    $stmt = mysqli_prepare(db(), $sql);
    if (!$stmt) {
        die('DB PREPARE ERROR: ' . mysqli_error(db()));
    }

    mysqli_stmt_bind_param($stmt, 'isss', $userId, $title, $description, $accessLevel);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

?>
