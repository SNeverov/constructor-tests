<?php
declare(strict_types=1);

/** @var array $test */
/** @var int $questions_count */
/** @var bool $has_active_attempt */

$timeLimitSec  = isset($test['time_limit_sec']) && (int)$test['time_limit_sec'] > 0 ? (int)$test['time_limit_sec'] : null;
$viewsCount    = (int)($test['views_count'] ?? 0);
$attemptsCount = (int)($test['attempts_count'] ?? 0);
$creatorLogin  = trim((string)($test['creator_login'] ?? ''));
$creatorUrl    = $creatorLogin !== '' ? ('/u/' . rawurlencode($creatorLogin)) : '';
$defaultCover  = '/assets/img/cover/default_test_cover.webp';
$answersMode   = test_answers_mode_from_value($test['show_answers'] ?? test_answers_mode_after_finish());
$answersModeLabel = test_answers_mode_label($answersMode);
$testUrl       = test_url((int)($test['id'] ?? 0), (string)($test['title'] ?? 'Тест'));

$categoryNames        = test_category_display_names($test['category_names'] ?? ($test['category_name'] ?? null));
$visibleCategoryNames = array_slice($categoryNames, 0, 3);
$hiddenCategoryNames  = array_slice($categoryNames, 3);
$hiddenCategoryTitle  = implode(', ', $hiddenCategoryNames);

$createdAt   = trim((string)($test['created_at'] ?? ''));
$createdDate = '';
if ($createdAt !== '') {
    $datePart  = preg_split('/\s+/', $createdAt)[0] ?? $createdAt;
    $dateObj   = DateTime::createFromFormat('Y-m-d', $datePart);
    $createdDate = $dateObj ? $dateObj->format('d.m.Y') : $datePart;
}

$pluralRu = static function (int $n, string $one, string $few, string $many): string {
    $n  = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) return $many;
    if ($n1 > 1  && $n1 < 5) return $few;
    if ($n1 === 1) return $one;
    return $many;
};

$formatTimeLimit = static function (?int $seconds) use ($pluralRu): array {
    if ($seconds === null || $seconds <= 0) {
        return ['value' => 'Без ограничений', 'label' => ''];
    }
    if ($seconds % 3600 === 0) {
        $hours = (int)($seconds / 3600);
        return ['value' => (string)$hours, 'label' => $pluralRu($hours, 'час', 'часа', 'часов')];
    }
    if ($seconds % 60 === 0) {
        $minutes = (int)($seconds / 60);
        return ['value' => (string)$minutes, 'label' => $pluralRu($minutes, 'минута', 'минуты', 'минут')];
    }
    return ['value' => gmdate('H:i:s', $seconds), 'label' => ''];
};

$timeLimitView = $formatTimeLimit($timeLimitSec);
?>

<div class="test-show">
    <?php $coverSrc = trim((string)($test['cover_image'] ?? '')) !== '' ? (string)$test['cover_image'] : $defaultCover; ?>
    <div class="test-show__card">

        <div class="test-show__cover">
            <img
                src="<?= htmlspecialchars($coverSrc, ENT_QUOTES, 'UTF-8') ?>"
                alt=""
                class="test-show__cover-img"
                loading="lazy"
                decoding="async"
            >
        </div>

        <div class="test-show__top">
            <div class="test-show__meta">
                <button
                    type="button"
                    class="ts-badge ts-badge--copy ui-tooltip ui-tooltip--bottom"
                    data-copy="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>"
                    data-tooltip="Скопировать ссылку"
                >
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <span data-copy-label>ID: <?= (int)($test['id'] ?? 0) ?></span>
                </button>

                <span class="ts-badge <?= (($test['access_level'] ?? '') === 'public') ? 'ts-badge--ok' : 'ts-badge--warn' ?>">
                    <?= (($test['access_level'] ?? '') === 'public') ? 'Для всех' : 'Для пользователей' ?>
                </span>

                <span class="ts-badge ts-badge--answers">
                    <?= htmlspecialchars($answersModeLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>

                <?php if ($creatorLogin !== ''): ?>
                    <span class="test-chip test-chip--soft">
                        <img src="/assets/img/test_card_svg/user.svg" alt="" aria-hidden="true">
                        <a class="test-show__author-link" href="<?= htmlspecialchars($creatorUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($creatorLogin, ENT_QUOTES, 'UTF-8') ?></a>
                    </span>
                <?php endif; ?>

                <?php if ($createdDate !== ''): ?>
                    <span class="test-chip test-chip--soft">
                        <img src="/assets/img/test_card_svg/calendar.svg" alt="" aria-hidden="true">
                        <span><?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($categoryNames !== []): ?>
                <div class="test-show__categories" aria-label="Категории теста">
                    <?php foreach ($visibleCategoryNames as $categoryName): ?>
                        <a class="test-chip test-chip--category" href="<?= htmlspecialchars(test_category_url_by_name($categoryName), ENT_QUOTES, 'UTF-8') ?>">
                            <img src="/assets/img/test_card_svg/pinpaper-filled.svg" alt="" aria-hidden="true">
                            <span><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($hiddenCategoryNames !== []): ?>
                        <span class="test-chip test-chip--more ui-tooltip ui-tooltip--bottom" data-tooltip="<?= htmlspecialchars($hiddenCategoryTitle, ENT_QUOTES, 'UTF-8') ?>" tabindex="0">
                            +<?= count($hiddenCategoryNames) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <h1 class="test-show__title"><?= htmlspecialchars((string)($test['title'] ?? 'Тест'), ENT_QUOTES, 'UTF-8') ?></h1>

            <?php $desc = trim((string)($test['description'] ?? '')); ?>
            <?php if ($desc !== ''): ?>
                <p class="test-show__desc"><?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>
        </div>

        <div class="test-show__stats">
            <div class="test-stat-item">
                <svg class="test-stat-item__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span class="test-stat-item__val"><?= (int)$questions_count ?></span>
                <span class="test-stat-item__label"><?= $pluralRu((int)$questions_count, 'вопрос', 'вопроса', 'вопросов') ?></span>
            </div>

            <div class="test-stat-item">
                <?php if ($timeLimitSec !== null && $timeLimitSec > 0): ?>
                    <svg class="test-stat-item__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span class="test-stat-item__val"><?= htmlspecialchars($timeLimitView['value'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($timeLimitView['label'] !== ''): ?>
                        <span class="test-stat-item__label"><?= htmlspecialchars($timeLimitView['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <svg class="test-stat-item__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.178 8c5.096 0 5.096 8 0 8-5.095 0-7.133-8-12.739-8-4.585 0-4.585 8 0 8 5.606 0 7.644-8 12.739-8z"/></svg>
                    <span class="test-stat-item__val test-stat-item__val--sm">Без ограничений</span>
                <?php endif; ?>
            </div>

            <div class="test-stat-item">
                <svg class="test-stat-item__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <span class="test-stat-item__val"><?= $viewsCount ?></span>
                <span class="test-stat-item__label"><?= $pluralRu($viewsCount, 'просмотр', 'просмотра', 'просмотров') ?></span>
            </div>

            <div class="test-stat-item">
                <svg class="test-stat-item__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                <?php if ($attemptsCount === 0): ?>
                    <span class="test-stat-item__val test-stat-item__val--sm">Не проходили</span>
                <?php else: ?>
                    <span class="test-stat-item__val"><?= $attemptsCount ?></span>
                    <span class="test-stat-item__label"><?= $pluralRu($attemptsCount, 'раз', 'раза', 'раз') ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($has_active_attempt)): ?>
        <div class="test-show__resume">
            <div class="test-show__resume-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="test-show__resume-body">
                <div class="test-show__resume-title">Есть незавершённая попытка</div>
                <div class="test-show__resume-hint">Можно продолжить с того места, где остановились.</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="test-show__actions">
            <?php if (!empty($has_active_attempt)): ?>
                <a class="btn btn-primary btn-md btn-with-icon" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">
                    <svg class="btn__icon-img" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Продолжить тест
                </a>
                <a class="btn btn-outline btn-md btn-with-icon" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass?restart=1">
                    <svg class="btn__icon-img" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Начать заново
                </a>
            <?php else: ?>
                <a class="btn btn-primary btn-md btn-with-icon" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">
                    <svg class="btn__icon-img" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Начать тест
                </a>
            <?php endif; ?>
            <a class="btn btn-ghost btn-md btn-with-icon" href="/">
                <svg class="btn__icon-img" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                К тестам
            </a>
        </div>

    </div>
</div>
