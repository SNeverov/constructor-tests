<?php
declare(strict_types=1);

function category_show(string $slug): void
{
    $slug = test_category_canonical_slug($slug);
    $categoryName = test_category_slug_to_name($slug);
    if ($categoryName === null) {
        http_response_code(404);
        view_render('404', [
            'title' => '404',
        ]);
        return;
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

    $perPage = 21;
    $total = tests_count_for_category($categoryName);
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_list_for_category($categoryName, $perPage, $offset, $sort);

    view_render('home', [
        'title' => $categoryName,
        'tests' => $tests,
        'page_heading' => $categoryName,
        'home_total' => tests_count_for_home(),
        'category_options' => test_categories_catalog(),
        'category_counts' => tests_category_counts_for_home(),
        'selected_category_slug' => $slug,
        'sort_options' => $sortOptions,
        'selected_sort' => $sort,
        'results_label' => 'Найдено',
        'empty_title' => 'В этой категории пока нет тестов',
        'empty_text' => 'Тесты этой категории появятся здесь позже.',
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'path' => '/categories/' . rawurlencode($slug),
            'query' => $sort !== 'new' ? ['sort' => $sort] : [],
        ],
        'scripts' => ['/assets/js/my-tests-share.js', '/assets/js/home-category-filter.js'],
        'styles' => ['/assets/css/my-tests.css', '/assets/css/filter-panels.css'],
    ]);
}
