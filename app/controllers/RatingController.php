<?php
declare(strict_types=1);

function test_rate(int $testId): void
{
    auth_required();
    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

    if (!attempts_has_finished_by_test_id_and_user_id($testId, $userId)) {
        $msg = 'Оценить тест можно только после прохождения';
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => $msg,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        flash_set('toast', ['type' => 'danger', 'text' => $msg]);
        $back = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($back !== '') {
            redirect($back);
            return;
        }
        redirect('/tests/' . $testId);
    }

    try {
        $ok = test_rating_upsert_by_user_id($testId, $userId, $rating);
        if ($ok && $isAjax) {
            $test = tests_find_by_id($testId);
            $ratingCount = (int)($test['rating_count'] ?? 0);
            $ratingSum = (int)($test['rating_sum'] ?? 0);
            $ratingAvg = $ratingCount > 0 ? ($ratingSum / $ratingCount) : 0.0;

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'rating_count' => $ratingCount,
                'rating_avg' => $ratingAvg,
                'user_rating' => $rating,
                'message' => 'Оценка сохранена',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($ok) {
            flash_set('toast', ['type' => 'success', 'text' => 'Оценка сохранена']);
        } else {
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'Не удалось сохранить оценку',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            flash_set('toast', ['type' => 'danger', 'text' => 'Не удалось сохранить оценку']);
        }
    } catch (Throwable $e) {
        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Ошибка сохранения оценки',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        flash_set('toast', ['type' => 'danger', 'text' => 'Ошибка сохранения оценки']);
    }

    $back = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($back !== '') {
        redirect($back);
        return;
    }

    redirect('/tests/' . $testId);
}
