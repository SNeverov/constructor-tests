<?php
declare(strict_types=1);

function my_bookmarks_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    $categorySlug = test_category_canonical_slug((string)($_GET['category'] ?? ''));
    $categoryName = $categorySlug !== '' ? test_category_slug_to_name($categorySlug) : null;
    if ($categoryName === null) {
        $categorySlug = '';
    }
    $sort = (string)($_GET['sort'] ?? 'new');
    $sortOptions = [
        'new' => 'Новые',
        'questions' => 'По вопросам',
        'time' => 'По времени',
        'views' => 'По просмотрам',
        'attempts' => 'По прохождениям',
    ];
    if (!isset($sortOptions[$sort])) {
        $sort = 'new';
    }

    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 10;
    $allTotal = tests_count_bookmarked_by_user_id($userId);
    $total = $categoryName !== null ? tests_count_bookmarked_by_user_id($userId, $categoryName) : $allTotal;
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_list_bookmarked_by_user_id_paginated($userId, $perPage, $offset, $categoryName, $sort);
    $pagerQuery = [];
    if ($categorySlug !== '') {
        $pagerQuery['category'] = $categorySlug;
    }
    if ($sort !== 'new') {
        $pagerQuery['sort'] = $sort;
    }

    view_render('my_bookmarks', [
        'title' => 'Мои закладки',
        'tests' => $tests,
        'filter_action' => '/my/bookmarks',
        'filter_id' => 'my_bookmarks',
        'home_total' => $allTotal,
        'category_options' => test_categories_catalog(),
        'category_counts' => tests_category_counts_bookmarked_by_user_id($userId),
        'selected_category_slug' => $categorySlug,
        'sort_options' => $sortOptions,
        'selected_sort' => $sort,
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'path' => '/my/bookmarks',
            'query' => $pagerQuery,
        ],
        'scripts' => ['/assets/js/list-loading.js', '/assets/js/my-tests-share.js', '/assets/js/home-category-filter.js'],
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
