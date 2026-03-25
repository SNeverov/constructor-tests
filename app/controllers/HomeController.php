<?php
declare(strict_types=1);

function home_index(): void
{
    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 20;
    $total = tests_count_for_home();
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $tests = tests_list_for_home($perPage, $offset);

    view_render('home', [
        'title' => 'Главная',
        'tests' => $tests,
        'pagination' => [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ],
        'scripts' => ['/assets/js/my-tests-share.js'],
        'styles' => ['/assets/css/my-tests.css'],
    ]);
}
