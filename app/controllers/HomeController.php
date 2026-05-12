<?php
declare(strict_types=1);

function home_index(): void
{
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

    $perPage = 21;
    $homeTotal = tests_count_for_home();
    $total = $categoryName !== null ? tests_count_for_category($categoryName) : $homeTotal;
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = $categoryName !== null
        ? tests_list_for_category($categoryName, $perPage, $offset, $sort)
        : tests_list_for_home($perPage, $offset, $sort);

    $pagerQuery = [];
    if ($categorySlug !== '') {
        $pagerQuery['category'] = $categorySlug;
    }
    if ($sort !== 'new') {
        $pagerQuery['sort'] = $sort;
    }

    view_render('home', [
        'title' => $categoryName !== null ? $categoryName : 'Главная',
        'tests' => $tests,
        'page_heading' => $categoryName !== null ? $categoryName : 'Главная',
        'home_total' => $homeTotal,
        'category_options' => test_categories_catalog(),
        'category_counts' => tests_category_counts_for_home(),
        'selected_category_slug' => $categorySlug,
        'sort_options' => $sortOptions,
        'selected_sort' => $sort,
        'empty_title' => $categoryName !== null ? 'В этой категории пока нет тестов' : 'Пока нет опубликованных тестов',
        'empty_text' => $categoryName !== null ? 'Тесты этой категории появятся здесь позже.' : 'Здесь появятся тесты пользователей.',
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'path' => '/',
            'query' => $pagerQuery,
        ],
        'scripts' => ['/assets/js/my-tests-share.js', '/assets/js/home-category-filter.js'],
        'styles' => ['/assets/css/my-tests.css', '/assets/css/filter-panels.css'],
    ]);
}
