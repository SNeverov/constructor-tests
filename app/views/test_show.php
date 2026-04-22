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

    return ['value' => gmdate('H:i:s', $seconds), 'label' => 'лимит'];
};

$timeLimitView = $formatTimeLimit($timeLimitSec);
?>

<div class="test-show">
    <?php $showCover = trim((string)($test['cover_image'] ?? '')) !== ''; ?>
    <div class="test-show__card">
        <?php if ($showCover): ?>
            <div class="test-show__cover">
                <img
                    src="<?= htmlspecialchars((string)$test['cover_image'], ENT_QUOTES, 'UTF-8') ?>"
                    alt=""
                    class="test-show__cover-img"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        <?php endif; ?>
        <div class="test-show__top">
            <div class="test-show__meta">
                <button
                    type="button"
                    class="badge badge--copy badge--copy-link"
                    data-copy="/tests/<?= (int)($test['id'] ?? 0) ?>"
                    title="Скопировать ссылку на тест"
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
                    <?= (($test['access_level'] ?? '') === 'public') ? 'Доступен всем' : 'Только для зарегистрированных' ?>
                </span>
            </div>

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

        <div class="test-show__actions">
            <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">Начать тест</a>
            <a class="btn btn--ghost" href="/">К списку тестов</a>
        </div>
    </div>
</div>
