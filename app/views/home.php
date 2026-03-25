<?php
/** @var array $tests */
/** @var array $pagination */

$defaultCover = '/assets/img/cover/default_test_cover.webp';
$currentUser = auth_user();
$currentUserId = (int)($currentUser['id'] ?? 0);
$currentUserLogin = trim((string)($currentUser['login'] ?? ''));
$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);

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
    <h1>Главная</h1>
</div>

<?php if (!empty($tests)): ?>
    <div class="results-meta muted">Найдено: <?= $total ?></div>
<?php endif; ?>

<?php if (empty($tests)): ?>
    <div class="empty-state">
        <div class="empty-state__card">
            <h3 class="empty-state__title">Пока нет опубликованных тестов</h3>
            <p class="empty-state__text">Здесь появятся тесты пользователей.</p>
        </div>
    </div>
<?php else: ?>
    <div>
        <?php foreach ($tests as $test): ?>
            <?php $cardContext = 'home'; ?>
            <?php require __DIR__ . '/partials/test-card.php'; ?>
        <?php endforeach; ?>

        <?php if ($pages > 1): ?>
            <nav class="pager" aria-label="Пагинация тестов">
                <?php $prevPage = max(1, $page - 1); ?>
                <?php $nextPage = min($pages, $page + 1); ?>

                <?php if ($page > 1): ?>
                    <a class="pager__btn" href="/?page=<?= $prevPage ?>" aria-label="Предыдущая страница">
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
                    <a class="pager__btn" href="/?page=<?= $nextPage ?>" aria-label="Следующая страница">
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
