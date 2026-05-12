<?php
/** @var int $homeTotal */
/** @var array $categoryOptions */
/** @var array $categoryCounts */
/** @var string $selectedCategorySlug */
/** @var string $selectedCategoryName */
/** @var array $sortOptions */
/** @var string $selectedSort */

$filterAction = (string)($filter_action ?? '/');
$filterId = preg_replace('/[^a-z0-9_-]+/i', '_', (string)($filter_id ?? 'home'));
$filterAria = (string)($filter_aria ?? 'Дополнительные фильтры');
$filterPanelClass = trim((string)($filter_panel_class ?? ''));
$filterShowActions = (bool)($filter_show_actions ?? false);
$filterResetUrl = (string)($filter_reset_url ?? $filterAction);
$filterShowSearch = (bool)($filter_show_search ?? false);
$filterSearchValue = (string)($filter_search_value ?? '');
$filterPanelClasses = trim('home-filter-panel' . ($filterPanelClass !== '' ? ' ' . $filterPanelClass : ''));
?>

<div class="<?= htmlspecialchars($filterPanelClasses, ENT_QUOTES, 'UTF-8') ?>" aria-label="Фильтры тестов">
    <form class="home-filter-controls" action="<?= htmlspecialchars($filterAction, ENT_QUOTES, 'UTF-8') ?>" method="get" aria-label="<?= htmlspecialchars($filterAria, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($filterShowSearch): ?>
            <label class="home-filter-search">
                <input
                    type="text"
                    class="input"
                    name="search"
                    value="<?= htmlspecialchars($filterSearchValue, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Название теста"
                    aria-label="Название теста"
                >
            </label>
        <?php endif; ?>
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
                <label class="sr-only" for="<?= htmlspecialchars($filterId, ENT_QUOTES, 'UTF-8') ?>_category_search">Поиск категорий</label>
                <input id="<?= htmlspecialchars($filterId, ENT_QUOTES, 'UTF-8') ?>_category_search" type="search" class="home-category-filter__search" placeholder="Поиск категорий" data-home-category-search autocomplete="off">
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
        <?php if ($filterShowActions): ?>
            <div class="home-filter-actions">
                <button type="submit" class="btn btn-primary btn-md btn-with-icon">
                    <svg class="btn__icon-img" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Применить
                </button>
                <a href="<?= htmlspecialchars($filterResetUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline btn-md btn-with-icon">
                    <svg class="btn__icon-img" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                    Сбросить
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>
