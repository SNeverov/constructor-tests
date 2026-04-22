<?php
declare(strict_types=1);

function my_bookmarks_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 10;
    $total = tests_count_bookmarked_by_user_id($userId);
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_list_bookmarked_by_user_id_paginated($userId, $perPage, $offset);

    view_render('my_bookmarks', [
        'title' => 'Мои закладки',
        'tests' => $tests,
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ],
        'scripts' => ['/assets/js/list-loading.js', '/assets/js/my-tests-share.js'],
        'styles' => ['/assets/css/my-tests.css'],
    ]);
}

function my_bookmarks_toggle(int $testId): void
{
    auth_required();
    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

    try {
        $result = tests_bookmark_toggle_by_user_id($userId, $testId);
        if ($result === null) {
            if ($isAjax) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'Тест не найден',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            flash_set('toast', ['type' => 'danger', 'text' => 'Тест не найден']);
        } elseif ($isAjax) {
            $result['trash_count'] = tests_trash_count_by_user_id($userId);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            return;
        }
    } catch (Throwable $e) {
        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Не удалось изменить закладку',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        flash_set('toast', ['type' => 'danger', 'text' => 'Не удалось изменить закладку']);
    }

    $back = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($back !== '') {
        redirect($back);
        return;
    }

    redirect('/my/bookmarks');
}

function header_counters_json(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'bookmarks_count' => tests_count_bookmarked_by_user_id($userId),
        'trash_count' => tests_trash_count_by_user_id($userId),
    ], JSON_UNESCAPED_UNICODE);
}
