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

<div class="page-head page-head--row">
    <h1>Корзина</h1>

    <div class="page-head__actions">
        <a href="/my/tests" class="btn btn--ghost" style="padding-left: 14px; padding-right: 14px;">
            <img src="/assets/img/hdr-icon-tests-new.svg" class="btn-icon" width="20" height="20" style="filter: brightness(0)" aria-hidden="true">
            Мои тесты
        </a>

        <?php if (!empty($tests)): ?>
            <?= form_open('/my/tests/trash/restore-all', 'post', [
                'class' => 'inline',
                'data-confirm' => '1',
                'data-confirm-title' => 'Восстановить всё?',
                'data-confirm-text' => 'Восстановить все тесты из корзины?',
                'data-confirm-ok' => 'Восстановить',
            ]) ?>
                <button type="submit" class="btn">
                    <img src="/assets/img/undo.svg" class="btn-icon" width="16" height="16" aria-hidden="true">
                    Восстановить все
                </button>
            </form>

            <?= form_open('/my/tests/trash/empty', 'post', [
                'class' => 'inline',
                'data-confirm' => '1',
                'data-confirm-title' => 'Очистить корзину?',
                'data-confirm-text' => 'Удалить все тесты из корзины навсегда? Это нельзя отменить.',
                'data-confirm-ok' => 'Удалить навсегда',
            ]) ?>
                <button type="submit" class="btn btn--danger" style="padding-left: 14px; padding-right: 14px;">
                    <img src="/assets/img/broom.svg" class="btn-icon" width="22" height="22" style="filter: brightness(0) saturate(100%) invert(19%) sepia(96%) saturate(7484%) hue-rotate(359deg)" aria-hidden="true">
                    Очистить корзину
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($tests)): ?>
    <div class="results-meta muted">Найдено в корзине: <?= $total ?></div>
<?php endif; ?>

<?php if (empty($tests)): ?>
    <div class="empty-state">
        <div class="empty-state__card">
            <div class="empty-state__icon"></div>

            <h3 class="empty-state__title">
                Корзина пуста
            </h3>

            <p class="empty-state__text">
                Здесь будут тесты, которые ты отправишь в корзину. Их можно восстановить или удалить навсегда.
            </p>

            <a href="/my/tests" class="btn btn--primary">
                <img src="/assets/img/arrow-left.svg" class="btn-icon" width="16" height="16" style="filter: invert(1)" aria-hidden="true">
                Вернуться к моим тестам
            </a>
        </div>
    </div>
<?php else: ?>

    <?php foreach ($tests as $test): ?>
        <?php
            $test['bookmark_availability'] = 'trashed';
            $cardContext = 'trash';
        ?>
        <?php require __DIR__ . '/partials/test-card.php'; ?>
    <?php endforeach; ?>

    <?php if ($pages > 1): ?>
        <?php
        $pagerPage = $page;
        $pagerPages = $pages;
        $pagerPrevUrl = '/my/tests/trash?page=' . max(1, $page - 1);
        $pagerNextUrl = '/my/tests/trash?page=' . min($pages, $page + 1);
        $pagerLabel = 'Пагинация корзины';
        require __DIR__ . '/partials/pager.php';
        ?>
    <?php endif; ?>

<?php endif; ?>
