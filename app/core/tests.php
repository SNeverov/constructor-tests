<?php
declare(strict_types=1);

function tests_list_by_user_id(int $userId): array
{
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT id, user_id, title, description, access_level, created_at, updated_at
        FROM tests
        WHERE user_id = :user_id
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
