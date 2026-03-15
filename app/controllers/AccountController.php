<?php
declare(strict_types=1);

function account_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    if ($userId <= 0) {
        redirect('/login');
    }

    $pdo = db();
    $stmt = $pdo->prepare('
        SELECT id, login, email, created_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute([
        ':id' => $userId,
    ]);
    $profile = $stmt->fetch();

    if ($profile === false) {
        auth_logout();
        redirect('/login');
    }

    $stats = [
        'tests_active' => tests_count_active_by_user_id($userId),
        'tests_deleted' => tests_count_deleted_by_user_id($userId),
        'results_total' => attempts_count_finished_by_user_id($userId),
        'avg_percent' => round(attempts_avg_percent_by_user_id($userId), 2),
    ];

    view_render('account', [
        'title' => 'Профиль',
        'profile' => $profile,
        'stats' => $stats,
        'styles' => ['/assets/css/account.css'],
    ]);
}

function my_results_index(): void
{
    auth_required();

    $user = auth_user();
    $userId = (int)($user['id'] ?? 0);
    if ($userId <= 0) {
        redirect('/login');
    }

    $search = trim((string)($_GET['search'] ?? ''));
    if (mb_strlen($search) > 100) {
        $search = mb_substr($search, 0, 100);
    }

    $status = (string)($_GET['status'] ?? 'all');
    if (!in_array($status, ['all', 'excellent', 'good', 'satisfactory', 'bad', 'correct', 'partial', 'wrong'], true)) {
        $status = 'all';
    }

    // Backward compatibility with old filter values.
    if ($status === 'correct') {
        $status = 'excellent';
    } elseif ($status === 'partial') {
        $status = 'satisfactory';
    } elseif ($status === 'wrong') {
        $status = 'bad';
    }

    $dateFrom = trim((string)($_GET['date_from'] ?? ''));
    if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = '';
    }

    $dateTo = trim((string)($_GET['date_to'] ?? ''));
    if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = '';
    }

    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) {
        $page = 1;
    }

    $perPage = 10;

    $filters = [
        'search' => $search,
        'status' => $status,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ];

    $total = attempts_count_by_user_id_filtered($userId, $filters);
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;

    $rows = attempts_list_by_user_id_filtered($userId, $filters, $perPage, $offset);

    view_render('my_results', [
        'title' => 'Мои результаты',
        'rows' => $rows,
        'filters' => $filters,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'total' => $total,
        ],
        'scripts' => ['/assets/js/list-loading.js', '/assets/js/date-picker.js'],
        'styles' => ['/assets/css/my-results.css'],
    ]);
}
