<?php
/** @var array $test */
/** @var string $cardContext */
/** @var string $defaultCover */
/** @var callable $formatNumber */
/** @var callable $pluralRu */
/** @var int $currentUserId */
/** @var string $currentUserLogin */

$cardContext = (string)($cardContext ?? 'home');
$isTrashContext = $cardContext === 'trash';
$contextClass = ' test-card--ctx-' . preg_replace('/[^a-z0-9_-]/i', '', $cardContext);

$testId = (int)($test['id'] ?? 0);
$cardTitle = trim((string)($test['title'] ?? 'Без названия'));
$description = trim((string)($test['description'] ?? ''));
$descriptionText = $description !== '' ? $description : 'Добавьте описание теста, чтобы пользователям было проще понять его тему и цель.';
$isPublic = (string)($test['access_level'] ?? 'public') === 'public';
$createdAt = trim((string)($test['created_at'] ?? ''));
$deletedAt = trim((string)($test['deleted_at'] ?? ''));
$canPass = $isPublic || auth_is_logged_in();
$questionsCount = (int)($test['questions_count'] ?? 0);
$viewsCount = (int)($test['views_count'] ?? 0);
$attemptsCount = (int)($test['attempts_count'] ?? 0);
$ratingCount = (int)($test['rating_count'] ?? 0);
$ratingSum = (int)($test['rating_sum'] ?? 0);
$ratingRaw = $ratingCount > 0 ? ($ratingSum / $ratingCount) : 0.0;
$ratingValue = max(0.0, min(5.0, (float)$ratingRaw));
$isBookmarked = (int)($test['is_bookmarked'] ?? 0) === 1;
$timeLimitSec = isset($test['time_limit_sec']) && (int)$test['time_limit_sec'] > 0 ? (int)$test['time_limit_sec'] : null;
$categoryName = trim((string)($test['category_name'] ?? ''));
if ($categoryName === '') {
    $categoryName = 'Программирование';
}
$creatorName = trim((string)($test['creator_login'] ?? ''));
if ($creatorName === '') {
    $creatorName = $currentUserLogin !== '' ? $currentUserLogin : '—';
}
$createdDate = '—';
if ($createdAt !== '') {
    $createdDate = preg_split('/\s+/', $createdAt)[0] ?? $createdAt;
}
$isOwner = $currentUserId > 0 && (int)($test['user_id'] ?? 0) === $currentUserId;

$availability = (string)($test['bookmark_availability'] ?? ($isTrashContext ? 'trashed' : 'available'));
$isUnavailable = $availability !== 'available';
$stateClass = '';
$statusLabel = '';
$statusText = '';
if ($availability === 'trashed') {
    $stateClass = ' test-card--trashed';
    $statusLabel = 'В корзине';
    $statusText = 'Тест временно недоступен';
} elseif ($availability === 'deleted') {
    $stateClass = ' test-card--deleted';
    $statusLabel = 'Удалён';
    $statusText = 'Тест больше недоступен';
}

$showPass = !$isUnavailable && !$isTrashContext && $canPass;
$showBookmark = !$isTrashContext && auth_is_logged_in();
$showShare = !$isUnavailable && !$isTrashContext;
$showEdit = !$isUnavailable && !$isTrashContext && $isOwner;
$showDeleteToTrash = !$isUnavailable && $cardContext === 'my_tests' && $isOwner;
$showRestoreDestroy = $isTrashContext;
$showStats = !$isUnavailable && !$isTrashContext;

$formatTimeLimitShort = static function (?int $seconds): string {
    if ($seconds === null || $seconds <= 0) {
        return 'Без времени';
    }

    if ($seconds % 3600 === 0) {
        return (int)($seconds / 3600) . ' ч';
    }

    if ($seconds % 60 === 0) {
        return (int)($seconds / 60) . ' мин';
    }

    return gmdate('H:i:s', $seconds);
};
?>

<article class="card test-card test-card--premium<?= $isUnavailable ? ' test-card--unavailable' : '' ?><?= $stateClass ?><?= $contextClass ?>" data-test-card-id="<?= $testId ?>">
    <?php $coverSrc = trim((string)($test['cover_image'] ?? '')) !== '' ? (string)$test['cover_image'] : $defaultCover; ?>
    <div class="test-card__cover">
        <img
            src="<?= htmlspecialchars($coverSrc, ENT_QUOTES, 'UTF-8') ?>"
            alt=""
            class="test-card__cover-img"
            loading="lazy"
            decoding="async"
        >
        <div class="test-card__cover-info">
            <div class="test-card__cover-title">
                <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </div>

    <div class="test-card__content">
        <?php if (!$isUnavailable && !$isTrashContext): ?>
            <a class="test-title-link" href="/tests/<?= $testId ?>">
                <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php else: ?>
            <div class="test-title-link">
                <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="test-card__footer-tags">
            <?php if (!$isUnavailable && !$isTrashContext): ?>
                <span class="test-chip test-chip--category">
                    <img src="/assets/img/test_card_svg/pinpaper-filled.svg" alt="" aria-hidden="true">
                    <span><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            <?php endif; ?>

            <?php if ($isUnavailable || $isTrashContext): ?>
                <span class="badge <?= $availability === 'deleted' ? 'badge--deleted' : 'badge--trashed' ?>">
                    <?= htmlspecialchars($statusLabel !== '' ? $statusLabel : 'В корзине', ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php else: ?>
                <span class="badge <?= $isPublic ? 'badge--ok' : 'badge--warn' ?>">
                    <?= $isPublic ? 'Доступен всем' : 'Только для зарегистрированных' ?>
                </span>
            <?php endif; ?>

            <span class="test-chip test-chip--soft">
                <img src="/assets/img/test_card_svg/user.svg" alt="" aria-hidden="true">
                <span><?= htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8') ?></span>
            </span>

            <?php if ($isTrashContext && $deletedAt !== ''): ?>
                <span class="test-chip test-chip--soft">
                    <img src="/assets/img/test_card_svg/calendar.svg" alt="" aria-hidden="true">
                    <span><?= htmlspecialchars($deletedAt, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            <?php else: ?>
                <span class="test-chip test-chip--soft">
                    <img src="/assets/img/test_card_svg/calendar.svg" alt="" aria-hidden="true">
                    <span><?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            <?php endif; ?>
        </div>

        <p class="test-description">
            <?= htmlspecialchars(($isUnavailable || $isTrashContext) && $statusText !== '' ? $statusText : $descriptionText, ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="test-card__icon-actions">
            <?php if ($showPass): ?>
                <a
                    href="/tests/<?= $testId ?>"
                    class="test-card__icon-btn test-card__icon-btn--primary ui-tooltip ui-tooltip--bottom"
                    data-tooltip="Пройти тест"
                    aria-label="Пройти тест"
                >
                    <img src="/assets/img/test_card_svg/play.svg" alt="" aria-hidden="true">
                </a>
            <?php endif; ?>

            <?php if ($showBookmark): ?>
                <?= form_open('/my/bookmarks/' . $testId . '/toggle', 'post', [
                    'class' => 'inline',
                    'data-bookmark-toggle' => '1',
                    'data-test-id' => (string)$testId,
                ]) ?>
                    <button
                        type="submit"
                        class="test-card__icon-btn ui-tooltip ui-tooltip--bottom<?= $isBookmarked ? ' is-active' : '' ?>"
                        data-tooltip="<?= $isBookmarked ? 'Убрать из закладок' : 'В закладки' ?>"
                        data-tooltip-add="В закладки"
                        data-tooltip-remove="Убрать из закладок"
                        aria-label="<?= $isBookmarked ? 'Убрать из закладок' : 'В закладки' ?>"
                        data-bookmark-button
                    >
                        <img src="/assets/img/test_card_svg/bookmark.svg" alt="" aria-hidden="true">
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($showShare): ?>
                <button
                    type="button"
                    class="test-card__icon-btn ui-tooltip ui-tooltip--bottom"
                    data-share-copy="/tests/<?= $testId ?>"
                    data-tooltip="Поделиться"
                    aria-label="Поделиться ссылкой на тест"
                >
                    <img src="/assets/img/test_card_svg/share-1.svg" alt="" aria-hidden="true">
                    <span data-copy-label hidden>Поделиться</span>
                </button>
            <?php endif; ?>

            <?php if ($showEdit): ?>
                <a
                    href="/my/tests/<?= $testId ?>/edit"
                    class="test-card__icon-btn ui-tooltip ui-tooltip--bottom"
                    data-tooltip="Редактировать"
                    aria-label="Редактировать тест"
                >
                    <img src="/assets/img/test_card_svg/edit-2-svgrepo-com.svg" alt="" aria-hidden="true">
                </a>
            <?php endif; ?>

            <?php if ($showDeleteToTrash): ?>
                <?= form_open('/my/tests/' . $testId . '/delete', 'post', [
                    'class' => 'inline',
                    'data-confirm' => '1',
                    'data-confirm-title' => 'Отправить в корзину?',
                    'data-confirm-text' => 'Убрать этот тест в корзину? Его можно будет восстановить.',
                    'data-confirm-ok' => 'В корзину',
                ]) ?>
                    <button
                        type="submit"
                        class="test-card__icon-btn test-card__icon-btn--danger ui-tooltip ui-tooltip--bottom"
                        data-tooltip="Удалить"
                        aria-label="Удалить тест"
                    >
                        <img src="/assets/img/test_card_svg/trash-test-card.svg" alt="" aria-hidden="true">
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($showRestoreDestroy): ?>
                <?= form_open('/my/tests/' . $testId . '/restore', 'post', ['class' => 'inline']) ?>
                    <button type="submit" class="btn">Восстановить</button>
                </form>

                <?= form_open('/my/tests/' . $testId . '/destroy', 'post', [
                    'class' => 'inline',
                    'data-confirm' => '1',
                    'data-confirm-title' => 'Удалить навсегда?',
                    'data-confirm-text' => 'Удалить этот тест навсегда? Это действие нельзя отменить.',
                    'data-confirm-ok' => 'Удалить навсегда',
                ]) ?>
                    <button type="submit" class="btn btn--danger">Удалить навсегда</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($showStats): ?>
        <div class="test-card__stats">
            <div class="test-stat">
                <img src="/assets/img/test_card_svg/question.svg" alt="" aria-hidden="true">
                <span><?= $formatNumber($questionsCount) ?> <?= $pluralRu($questionsCount, 'вопрос', 'вопроса', 'вопросов') ?></span>
            </div>
            <div class="test-stat">
                <?php if ($timeLimitSec !== null && $timeLimitSec > 0): ?>
                    <img src="/assets/img/test_card_svg/clock.svg" alt="" aria-hidden="true">
                    <span><?= htmlspecialchars($formatTimeLimitShort($timeLimitSec), ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <img src="/assets/img/test_card_svg/infinity.svg" alt="" aria-hidden="true">
                    <span>Без времени</span>
                <?php endif; ?>
            </div>
            <div class="test-stat">
                <img src="/assets/img/test_card_svg/eye-open.svg" alt="" aria-hidden="true">
                <span><?= $formatNumber($viewsCount) ?></span>
            </div>
            <div class="test-stat">
                <img src="/assets/img/test_card_svg/refresh.svg" alt="" aria-hidden="true">
                <span><?= $formatNumber($attemptsCount) ?></span>
            </div>
            <div class="test-card__rating test-card__rating--in-stats" aria-label="Рейтинг теста">
                <div class="test-stars" aria-hidden="true">
                    <div class="test-stars__base">★★★★★</div>
                    <div class="test-stars__fill" style="width: <?= (float)(($ratingValue / 5) * 100) ?>%;">★★★★★</div>
                </div>
                <span class="test-card__rating-text">
                    <?= number_format($ratingValue, 1, '.', '') ?>
                    (<?= $formatNumber($ratingCount) ?>)
                </span>
            </div>
        </div>
    <?php endif; ?>
</article>
