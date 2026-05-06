<?php
/** @var array $tests */
/** @var array $pagination */
/** @var int $home_total */
/** @var array $category_options */
/** @var array $category_counts */
/** @var string $selected_category_slug */
/** @var array $sort_options */
/** @var string $selected_sort */

$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);
$pagerPath = (string)($pagination['path'] ?? '/my/tests');
$pagerQuery = is_array($pagination['query'] ?? null) ? $pagination['query'] : [];
$defaultCover = '/assets/img/cover/default_test_cover.webp';
$currentUser = auth_user();
$currentUserId = (int)($currentUser['id'] ?? 0);
$currentUserLogin = trim((string)($currentUser['login'] ?? ''));
$homeTotal = (int)($home_total ?? $total);
$categoryOptions = is_array($category_options ?? null) ? $category_options : test_categories_catalog();
$categoryCounts = is_array($category_counts ?? null) ? $category_counts : [];
$selectedCategorySlug = trim((string)($selected_category_slug ?? ''));
$selectedCategoryName = $selectedCategorySlug !== '' && isset($categoryOptions[$selectedCategorySlug])
    ? (string)$categoryOptions[$selectedCategorySlug]
    : '';
$sortOptions = is_array($sort_options ?? null) ? $sort_options : [
    'new' => 'Новые',
    'questions' => 'По вопросам',
    'time' => 'По времени',
    'views' => 'По просмотрам',
    'attempts' => 'По прохождениям',
];
$selectedSort = (string)($selected_sort ?? 'new');
if (!isset($sortOptions[$selectedSort])) {
    $selectedSort = 'new';
}
$emptyTitle = $selectedCategorySlug !== '' ? 'В этой категории нет ваших тестов' : 'У вас пока нет тестов';
$emptyText = $selectedCategorySlug !== ''
    ? 'Попробуйте выбрать другую категорию или сбросить фильтр.'
    : 'Здесь будут отображаться все тесты, которые вы создадите. Вы сможете редактировать их, удалять и смотреть результаты прохождения.';
$pageUrl = static function (string $path, array $query, int $targetPage): string {
    if ($targetPage > 1) {
        $query['page'] = $targetPage;
    } else {
        unset($query['page']);
    }

    $queryString = http_build_query($query);
    return $path . ($queryString !== '' ? '?' . $queryString : '');
};
$formatNumber = static function (int $value): string {
    return number_format($value, 0, '', ' ');
};
$pluralRu = static function (int $n, string $one, string $few, string $many): string {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $many;
    if ($n1 > 1 && $n1 < 5) return $few;
    if ($n1 === 1) return $one;
    return $many;
};
?>

<div class="my-tests-page" data-list-shell>
    <div class="page-head">
        <h1>Мои тесты</h1>
    </div>

    <?php require __DIR__ . '/partials/home-filter-panel.php'; ?>

    <?php if (!empty($tests)): ?>
        <div class="results-meta muted">Найдено: <?= $total ?></div>
    <?php endif; ?>

    <?php if (empty($tests)): ?>
        <div class="empty-state" data-list-content>
            <div class="empty-state__card">
                <div class="empty-state__icon"></div>

                <h3 class="empty-state__title"><?= htmlspecialchars($emptyTitle, ENT_QUOTES, 'UTF-8') ?></h3>

                <p class="empty-state__text"><?= htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8') ?></p>

                <?php if ($selectedCategorySlug === ''): ?>
                    <a href="/my/tests/create" class="btn btn--primary">
                        <img src="/assets/img/hdr-icon-create.svg" class="btn-icon" width="16" height="16" aria-hidden="true">
                        Создать первый тест
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>

        <div class="test-card-grid" data-list-content>
            <?php foreach ($tests as $test): ?>
                <?php $cardContext = 'my_tests'; ?>
                <?php require __DIR__ . '/partials/test-card.php'; ?>
            <?php endforeach; ?>

            <?php if ($pages > 1): ?>
                <nav class="pager" aria-label="Пагинация тестов">
                    <?php $prevPage = max(1, $page - 1); ?>
                    <?php $nextPage = min($pages, $page + 1); ?>

                    <?php if ($page > 1): ?>
                        <a class="pager__btn" href="<?= htmlspecialchars($pageUrl($pagerPath, $pagerQuery, $prevPage), ENT_QUOTES, 'UTF-8') ?>" aria-label="Предыдущая страница">
                            <img src="/assets/img/next-page.svg" class="pager__arrow pager__arrow--prev" alt="" aria-hidden="true">
                        </a>
                    <?php else: ?>
                        <span class="pager__btn is-disabled" aria-hidden="true">
                            <img src="/assets/img/next-page.svg" class="pager__arrow pager__arrow--prev" alt="" aria-hidden="true">
                        </span>
                    <?php endif; ?>

                    <span class="pager__info">
                        Страница <strong><?= $page ?></strong> из <strong><?= $pages ?></strong>
                    </span>

                    <?php if ($page < $pages): ?>
                        <a class="pager__btn" href="<?= htmlspecialchars($pageUrl($pagerPath, $pagerQuery, $nextPage), ENT_QUOTES, 'UTF-8') ?>" aria-label="Следующая страница">
                            <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                        </a>
                    <?php else: ?>
                        <span class="pager__btn is-disabled" aria-hidden="true">
                            <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                        </span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <div class="list-loading" data-list-loading hidden aria-hidden="true">
        <div class="card test-card skeleton-card"></div>
        <div class="card test-card skeleton-card"></div>
        <div class="card test-card skeleton-card"></div>
    </div>
</div>
