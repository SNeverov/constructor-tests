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

<div class="my-tests-trash-page" data-list-shell>

    <div data-list-content>

        <div class="page-head page-head--row">
            <h1>Корзина</h1>

            <?php if (!empty($tests)): ?>
            <div class="page-head__actions">
                <?= form_open('/my/tests/trash/restore-all', 'post', [
                    'class'             => 'inline',
                    'data-confirm'      => '1',
                    'data-confirm-title'=> 'Восстановить всё?',
                    'data-confirm-text' => 'Восстановить все тесты из корзины?',
                    'data-confirm-ok'   => 'Восстановить',
                ]) ?>
                    <button type="submit" class="btn btn-outline btn-sm btn-with-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        Восстановить все
                    </button>
                </form>

                <?= form_open('/my/tests/trash/empty', 'post', [
                    'class'             => 'inline',
                    'data-confirm'      => '1',
                    'data-confirm-title'=> 'Очистить корзину?',
                    'data-confirm-text' => 'Удалить все тесты из корзины навсегда? Это нельзя отменить.',
                    'data-confirm-ok'   => 'Удалить навсегда',
                ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm btn-with-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        Очистить корзину
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($tests)): ?>
            <div class="results-meta muted">Найдено в корзине: <?= $total ?></div>
        <?php endif; ?>

        <?php if (empty($tests)): ?>
            <div class="empty-state">
                <div class="empty-state__card">
                    <div class="empty-state__icon"></div>
                    <h3 class="empty-state__title">Корзина пуста</h3>
                    <p class="empty-state__text">
                        Здесь будут тесты, которые вы отправите в корзину. Их можно восстановить или удалить навсегда.
                    </p>
                    <a href="/my/tests" class="btn btn-primary btn-with-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                        Вернуться к моим тестам
                    </a>
                </div>
            </div>
        <?php else: ?>

            <div class="test-card-grid">
                <?php foreach ($tests as $test): ?>
                    <?php $cardContext = 'trash'; ?>
                    <?php require __DIR__ . '/partials/test-card.php'; ?>
                <?php endforeach; ?>

                <?php if ($pages > 1): ?>
                    <?php
                    $pagerPage    = $page;
                    $pagerPages   = $pages;
                    $pagerPrevUrl = '/my/tests/trash?page=' . max(1, $page - 1);
                    $pagerNextUrl = '/my/tests/trash?page=' . min($pages, $page + 1);
                    $pagerLabel   = 'Пагинация корзины';
                    require __DIR__ . '/partials/pager.php';
                    ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div><!-- /data-list-content -->

</div>
