<?php
declare(strict_types=1);

function category_show(string $slug): void
{
    $categoryName = test_category_slug_to_name($slug);
    if ($categoryName === null) {
        http_response_code(404);
        view_render('404', [
            'title' => '404',
        ]);
        return;
    }

    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 20;
    $total = tests_count_for_category($categoryName);
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_list_for_category($categoryName, $perPage, $offset);

    view_render('home', [
        'title' => $categoryName,
        'tests' => $tests,
        'page_heading' => $categoryName,
        'results_label' => 'Найдено',
        'empty_title' => 'В этой категории пока нет тестов',
        'empty_text' => 'Тесты этой категории появятся здесь позже.',
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'path' => '/categories/' . rawurlencode($slug),
        ],
        'scripts' => ['/assets/js/my-tests-share.js'],
        'styles' => ['/assets/css/my-tests.css'],
    ]);
}
