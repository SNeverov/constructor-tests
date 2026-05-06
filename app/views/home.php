<?php
/** @var array $tests */
/** @var array $pagination */
/** @var int $home_total */
/** @var array $category_options */
/** @var array $category_counts */
/** @var string $selected_category_slug */
/** @var array $sort_options */
/** @var string $selected_sort */

$defaultCover = '/assets/img/cover/default_test_cover.webp';
$currentUser = auth_user();
$currentUserId = (int)($currentUser['id'] ?? 0);
$currentUserLogin = trim((string)($currentUser['login'] ?? ''));
$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);
$pagerPath = (string)($pagination['path'] ?? '/');
$pagerQuery = is_array($pagination['query'] ?? null) ? $pagination['query'] : [];
$pageHeading = trim((string)($page_heading ?? 'Главная'));
$resultsLabel = trim((string)($results_label ?? 'Найдено'));
$emptyTitle = trim((string)($empty_title ?? 'Пока нет опубликованных тестов'));
$emptyText = trim((string)($empty_text ?? 'Здесь появятся тесты пользователей.'));
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

<div class="home-page">
<div class="home-filter-panel" aria-label="Фильтры тестов">
    <form class="home-filter-controls" action="/" method="get" aria-label="Дополнительные фильтры">
        <div class="home-category-filter" data-home-category-filter>
            <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategorySlug, ENT_QUOTES, 'UTF-8') ?>" data-home-category-value>
            <button class="home-category-filter__trigger" type="button" data-home-category-trigger aria-haspopup="listbox" aria-expanded="false">
                <span class="home-category-filter__trigger-icon" aria-hidden="true">
                    <img src="/assets/img/test_card_svg/pinpaper-filled.svg" alt="">
                </span>
                <span class="home-category-filter__trigger-text<?= $selectedCategorySlug === '' ? ' is-placeholder' : '' ?>" data-home-category-current>
                    <?= htmlspecialchars($selectedCategoryName !== '' ? $selectedCategoryName : 'Все категории', ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="home-category-filter__trigger-count" data-home-category-current-count><?= $selectedCategorySlug !== '' ? (int)($categoryCounts[$selectedCategoryName] ?? 0) : $homeTotal ?></span>
                <svg class="home-category-filter__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            <div class="home-category-filter__panel" data-home-category-panel hidden>
                <label class="sr-only" for="home_category_search">Поиск категорий</label>
                <input id="home_category_search" type="search" class="home-category-filter__search" placeholder="Поиск категорий" data-home-category-search autocomplete="off">
                <div class="home-category-filter__list" role="listbox" aria-label="Категории тестов">
                    <button
                        type="button"
                        class="home-category-filter__option<?= $selectedCategorySlug === '' ? ' is-selected' : '' ?>"
                        data-home-category-option
                        data-category-slug=""
                        data-category-name="Все категории"
                        data-category-count="<?= $homeTotal ?>"
                        aria-selected="<?= $selectedCategorySlug === '' ? 'true' : 'false' ?>"
                    >
                        <span>Все категории</span>
                        <strong><?= $homeTotal ?></strong>
                    </button>
                    <?php foreach ($categoryOptions as $categorySlug => $categoryName): ?>
                        <?php $categoryCount = (int)($categoryCounts[(string)$categoryName] ?? 0); ?>
                        <button
                            type="button"
                            class="home-category-filter__option<?= $selectedCategorySlug === (string)$categorySlug ? ' is-selected' : '' ?>"
                            data-home-category-option
                            data-category-slug="<?= htmlspecialchars((string)$categorySlug, ENT_QUOTES, 'UTF-8') ?>"
                            data-category-name="<?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?>"
                            data-category-count="<?= $categoryCount ?>"
                            aria-selected="<?= $selectedCategorySlug === (string)$categorySlug ? 'true' : 'false' ?>"
                        >
                            <span><?= htmlspecialchars((string)$categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= $categoryCount ?></strong>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="home-category-filter__empty" data-home-category-empty hidden>Категория не найдена</div>
                <div class="home-category-filter__actions">
                    <button class="home-filter-apply" type="submit">Готово</button>
                </div>
            </div>
        </div>
        <div class="home-sort-filter" data-home-sort-filter>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($selectedSort, ENT_QUOTES, 'UTF-8') ?>" data-home-sort-value>
            <button class="home-sort-filter__trigger" type="button" data-home-sort-trigger aria-haspopup="listbox" aria-expanded="false">
                <span>Сортировка:</span>
                <strong data-home-sort-current><?= htmlspecialchars((string)$sortOptions[$selectedSort], ENT_QUOTES, 'UTF-8') ?></strong>
                <svg class="home-sort-filter__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            <div class="home-sort-filter__panel" data-home-sort-panel hidden>
                <div class="home-sort-filter__list" role="listbox" aria-label="Сортировка тестов">
                    <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                        <button
                            type="button"
                            class="home-sort-filter__option<?= $selectedSort === (string)$sortValue ? ' is-selected' : '' ?>"
                            data-home-sort-option
                            data-sort-value="<?= htmlspecialchars((string)$sortValue, ENT_QUOTES, 'UTF-8') ?>"
                            data-sort-label="<?= htmlspecialchars((string)$sortLabel, ENT_QUOTES, 'UTF-8') ?>"
                            aria-selected="<?= $selectedSort === (string)$sortValue ? 'true' : 'false' ?>"
                        >
                            <?= htmlspecialchars((string)$sortLabel, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($tests)): ?>
    <div class="results-meta muted"><?= htmlspecialchars($resultsLabel, ENT_QUOTES, 'UTF-8') ?>: <?= $total ?></div>
<?php endif; ?>

<?php if (empty($tests)): ?>
    <div class="empty-state">
        <div class="empty-state__card">
            <h3 class="empty-state__title"><?= htmlspecialchars($emptyTitle, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="empty-state__text"><?= htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="test-card-grid home-tests-grid">
        <?php foreach ($tests as $test): ?>
            <?php $cardContext = 'home'; ?>
            <?php require __DIR__ . '/partials/test-card.php'; ?>
        <?php endforeach; ?>
    </div>

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
<?php endif; ?>
</div>
