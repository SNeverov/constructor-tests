<?php
declare(strict_types=1);

/** @var array $profile */
/** @var array $tests */
/** @var array $pagination */

$login = trim((string)($profile['login'] ?? ''));
$displayLogin = $login !== '' ? $login : 'Пользователь';
$initial = mb_strtoupper(mb_substr($displayLogin, 0, 1));
$createdAt = trim((string)($profile['created_at'] ?? ''));
$createdDate = '';
if ($createdAt !== '') {
    $datePart = preg_split('/\s+/', $createdAt)[0] ?? $createdAt;
    $dateObj = DateTime::createFromFormat('Y-m-d', $datePart);
    $createdDate = $dateObj ? $dateObj->format('d.m.Y') : $datePart;
}

$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);
$path = (string)($pagination['path'] ?? ('/u/' . rawurlencode($login)));

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

$pageUrl = static function (string $path, int $targetPage): string {
    return $targetPage > 1 ? ($path . '?page=' . $targetPage) : $path;
};
?>

<div class="public-user">
    <section class="public-user__hero">
        <div class="public-user__avatar" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="public-user__main">
            <div class="public-user__eyebrow">Публичная страница автора</div>
            <h1><?= htmlspecialchars($displayLogin, ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="public-user__meta">
                <?php if ($createdDate !== ''): ?>
                    <span class="badge">На сайте с <?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="badge"><?= $formatNumber($total) ?> <?= $pluralRu($total, 'публичный тест', 'публичных теста', 'публичных тестов') ?></span>
            </div>
        </div>
    </section>

    <div class="public-user__section-head">
        <h2>Публичные тесты</h2>
        <span class="muted">Показаны опубликованные тесты, видимые в каталоге</span>
    </div>

    <?php if ($tests === []): ?>
        <div class="public-user__empty">
            <h3>У пользователя пока нет публичных тестов</h3>
            <p>Черновики, удалённые и закрытые тесты здесь не показываются.</p>
        </div>
    <?php else: ?>
        <div class="test-card-grid public-user__grid">
            <?php foreach ($tests as $test): ?>
                <?php
                $cardContext = 'public_user';
                require __DIR__ . '/partials/test-card.php';
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
        <?php
        $pagerPage = $page;
        $pagerPages = $pages;
        $pagerPrevUrl = $pageUrl($path, max(1, $page - 1));
        $pagerNextUrl = $pageUrl($path, min($pages, $page + 1));
        $pagerLabel = 'Пагинация тестов автора';
        require __DIR__ . '/partials/pager.php';
        ?>
    <?php endif; ?>
</div>
