<?php
/** @var array $tests */
/** @var array $pagination */

$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);
$defaultCover = '/assets/img/cover/default_test_cover.webp';
$currentUser = auth_user();
$currentUserId = (int)($currentUser['id'] ?? 0);
$currentUserLogin = trim((string)($currentUser['login'] ?? ''));
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

<div class="page-head">
    <h1>Мои закладки</h1>
</div>

<div class="results-meta muted<?= $total > 0 ? '' : ' is-hidden' ?>" data-bookmarks-page-meta>
    В закладках: <span data-bookmarks-page-count><?= $total ?></span>
</div>

<div class="empty-state<?= empty($tests) ? '' : ' is-hidden' ?>" data-bookmarks-empty>
    <div class="empty-state__card">
        <div class="empty-state__icon"></div>

        <h3 class="empty-state__title">Пока нет закладок</h3>

        <p class="empty-state__text">
            Добавляй интересные тесты в закладки, чтобы быстро возвращаться к ним позже.
        </p>

        <a href="/" class="btn btn--primary">
            Перейти к тестам
        </a>
    </div>
</div>

<div data-bookmarks-list-wrap<?= empty($tests) ? ' class="is-hidden"' : '' ?>>
    <div data-bookmarks-list>
        <?php foreach ($tests as $test): ?>
            <?php $cardContext = 'my_bookmarks'; ?>
            <?php require __DIR__ . '/partials/test-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Пагинация закладок">
            <?php $prevPage = max(1, $page - 1); ?>
            <?php $nextPage = min($pages, $page + 1); ?>

            <?php if ($page > 1): ?>
                <a class="pager__btn" href="/my/bookmarks?page=<?= $prevPage ?>" aria-label="Предыдущая страница">
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
                <a class="pager__btn" href="/my/bookmarks?page=<?= $nextPage ?>" aria-label="Следующая страница">
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
