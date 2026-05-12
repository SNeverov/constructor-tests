<?php
declare(strict_types=1);

/** @var array|null $test */
/** @var int $questions_count */
/** @var int $rating_count */
/** @var float $rating_avg */
/** @var int|null $user_rating */
/** @var bool $can_rate */

$timeLimitSec  = isset($test['time_limit_sec']) && (int)$test['time_limit_sec'] > 0 ? (int)$test['time_limit_sec'] : null;
$viewsCount    = (int)($test['views_count'] ?? 0);
$attemptsCount = (int)($test['attempts_count'] ?? 0);
$creatorLogin  = trim((string)($test['creator_login'] ?? ''));
$creatorUrl = $creatorLogin !== '' ? ('/u/' . rawurlencode($creatorLogin)) : '';
$defaultCover  = '/assets/img/cover/default_test_cover.webp';
$answersMode = test_answers_mode_from_value($test['show_answers'] ?? test_answers_mode_after_finish());
$answersModeLabel = test_answers_mode_label($answersMode);
$testUrl = test_url((int)($test['id'] ?? 0), (string)($test['title'] ?? 'Тест'));

$categoryNames = test_category_display_names($test['category_names'] ?? ($test['category_name'] ?? null));
$visibleCategoryNames = array_slice($categoryNames, 0, 3);
$hiddenCategoryNames = array_slice($categoryNames, 3);
$hiddenCategoryTitle = implode(', ', $hiddenCategoryNames);
$createdAt = trim((string)($test['created_at'] ?? ''));
$createdDate = '';
if ($createdAt !== '') {
    $datePart = preg_split('/\s+/', $createdAt)[0] ?? $createdAt;
    $dateObj = DateTime::createFromFormat('Y-m-d', $datePart);
    $createdDate = $dateObj ? $dateObj->format('d-m-Y') : $datePart;
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
        return ['value' => 'Без времени', 'label' => ''];
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
                    class="badge badge--copy badge--copy-link ui-tooltip ui-tooltip--bottom"
                    data-copy="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>"
                    data-tooltip="Скопировать ссылку на тест"
                >
                    <img
                        src="/assets/img/link-svgrepo-com.svg"
                        alt=""
                        class="badge__icon"
                        aria-hidden="true"
                    >
                    <span data-copy-label>ID: <?= (int)($test['id'] ?? 0) ?></span>
                </button>

                <span class="badge <?= (($test['access_level'] ?? '') === 'public') ? 'badge--ok' : 'badge--warn' ?>">
                    <?= (($test['access_level'] ?? '') === 'public') ? 'Для всех' : 'Для зарегистрированных' ?>
                </span>
                <span class="badge badge--answers">
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
                <div class="test-show__desc">
                    <?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="test-show__stats">
            <div class="test-stat-item">
                <img src="/assets/img/test_card_svg/question.svg" alt="" class="test-stat-item__icon" aria-hidden="true">
                <span class="test-stat-item__val"><?= (int)$questions_count ?></span>
                <span class="test-stat-item__label"><?= $pluralRu((int)$questions_count, 'вопрос', 'вопроса', 'вопросов') ?></span>
            </div>
            <div class="test-stat-item">
                <?php if ($timeLimitSec !== null && $timeLimitSec > 0): ?>
                    <img src="/assets/img/test_card_svg/clock.svg" alt="" class="test-stat-item__icon" aria-hidden="true">
                    <span class="test-stat-item__val"><?= htmlspecialchars($timeLimitView['value'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($timeLimitView['label'] !== ''): ?>
                        <span class="test-stat-item__label"><?= htmlspecialchars($timeLimitView['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <img src="/assets/img/test_card_svg/infinity.svg" alt="" class="test-stat-item__icon" aria-hidden="true">
                    <span class="test-stat-item__val test-stat-item__val--sm">Без времени</span>
                <?php endif; ?>
            </div>
            <div class="test-stat-item">
                <img src="/assets/img/test_card_svg/eye-open.svg" alt="" class="test-stat-item__icon" aria-hidden="true">
                <span class="test-stat-item__val"><?= $viewsCount ?></span>
                <span class="test-stat-item__label"><?= $pluralRu($viewsCount, 'просмотр', 'просмотра', 'просмотров') ?></span>
            </div>
            <div class="test-stat-item">
                <img src="/assets/img/test_card_svg/refresh.svg" alt="" class="test-stat-item__icon" aria-hidden="true">
                <?php if ($attemptsCount === 0): ?>
                    <span class="test-stat-item__val test-stat-item__val--sm">Не проходили</span>
                <?php else: ?>
                    <span class="test-stat-item__val"><?= $attemptsCount ?></span>
                    <span class="test-stat-item__label"><?= $pluralRu($attemptsCount, 'раз прошли', 'раза прошли', 'раз прошли') ?></span>
                <?php endif; ?>
            </div>
            <div class="test-stat-item test-stat-item--rating">
                <div
                    class="test-show__rating"
                    data-rating-block
                    data-rating-current="<?= (int)($user_rating ?? 0) ?>"
                    data-rating-avg="<?= number_format((float)($rating_avg ?? 0), 2, '.', '') ?>"
                    data-rating-count="<?= (int)($rating_count ?? 0) ?>"
                >
                    <?php if (auth_is_logged_in() && $can_rate): ?>
                        <?= form_open('/tests/' . (int)($test['id'] ?? 0) . '/rate', 'post', [
                            'class' => 'test-show__rating-form',
                            'data-rating-form' => '1',
                        ]) ?>
                            <div class="test-show__rating-stars" role="radiogroup" aria-label="Поставить оценку тесту">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button
                                        type="submit"
                                        name="rating"
                                        value="<?= $i ?>"
                                        class="test-show__star-btn<?= (($user_rating ?? 0) >= $i) ? ' is-active' : '' ?>"
                                        data-rating-value="<?= $i ?>"
                                        aria-label="Оценка <?= $i ?> из 5"
                                    >★</button>
                                <?php endfor; ?>
                            </div>
                        </form>
                    <?php elseif (auth_is_logged_in() && !$can_rate): ?>
                        <div class="test-show__rating-stars-readonly" aria-label="Средняя оценка теста">
                            <div class="test-show__rating-stars-base" aria-hidden="true">★★★★★</div>
                            <div class="test-show__rating-stars-fill" style="width: <?= (float)((($rating_avg ?? 0) / 5) * 100) ?>%;" aria-hidden="true">★★★★★</div>
                        </div>
                    <?php else: ?>
                        <div class="test-show__rating-stars-readonly" aria-label="Средняя оценка теста">
                            <div class="test-show__rating-stars-base" aria-hidden="true">★★★★★</div>
                            <div class="test-show__rating-stars-fill" style="width: <?= (float)((($rating_avg ?? 0) / 5) * 100) ?>%;" aria-hidden="true">★★★★★</div>
                        </div>
                    <?php endif; ?>
                    <div class="test-show__rating-text">
                        <span data-rating-avg-text><?= number_format((float)($rating_avg ?? 0), 1, '.', '') ?></span>
                        <span class="test-show__rating-sep">·</span>
                        <span data-rating-count-text><?= (int)($rating_count ?? 0) ?></span>
                        <span>оценок</span>
                        <?php if (auth_is_logged_in() && $can_rate): ?>
                            <span class="test-show__rating-sep">·</span>
                            <span>ваша: <strong data-rating-user-text><?= (int)($user_rating ?? 0) ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <?php if (auth_is_logged_in() && !$can_rate): ?>
                        <div class="test-show__rating-hint">Пройдите тест, чтобы оценить</div>
                    <?php elseif (!auth_is_logged_in()): ?>
                        <div class="test-show__rating-hint">Войдите, чтобы поставить оценку</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($has_active_attempt)): ?>
        <div class="test-show__resume">
            <div class="test-show__resume-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="test-show__resume-body">
                <div class="test-show__resume-title">У вас есть незавершённая попытка</div>
                <div class="test-show__resume-hint">Вы можете продолжить с того места, где остановились.</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="test-show__actions">
            <?php if (!empty($has_active_attempt)): ?>
                <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">
                    <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Продолжить тест
                </a>
                <a class="btn btn--ghost test-show__restart-btn" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass?restart=1">
                    <img src="/assets/img/undo.svg" class="btn-icon" width="15" height="15" aria-hidden="true">
                    Начать заново
                </a>
            <?php else: ?>
                <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">
                    <img src="/assets/img/test_card_svg/play.svg" class="btn-icon" width="20" height="20" style="filter: invert(1)" aria-hidden="true">
                    Начать тест
                </a>
            <?php endif; ?>
            <a class="btn btn--ghost" href="/">
                <img src="/assets/img/arrow-left.svg" class="btn-icon" width="15" height="15" aria-hidden="true">
                К списку тестов
            </a>
        </div>
    </div>
</div>
