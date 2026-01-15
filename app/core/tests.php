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

