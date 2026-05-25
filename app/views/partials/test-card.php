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
$answersMode = test_answers_mode_from_value($test['show_answers'] ?? test_answers_mode_after_finish());
$answersModeLabel = test_answers_mode_label($answersMode);
$timeLimitSec = isset($test['time_limit_sec']) && (int)$test['time_limit_sec'] > 0 ? (int)$test['time_limit_sec'] : null;
$creatorName = trim((string)($test['creator_login'] ?? ''));
if ($creatorName === '') {
    $creatorName = $currentUserLogin !== '' ? $currentUserLogin : '—';
}
$creatorUrl = $creatorName !== '—' ? ('/u/' . rawurlencode($creatorName)) : '';
$createdDate = '—';
if ($createdAt !== '') {
    $datePart = preg_split('/\s+/', $createdAt)[0] ?? $createdAt;
    $dateObj = DateTime::createFromFormat('Y-m-d', $datePart);
    $createdDate = $dateObj ? $dateObj->format('d-m-Y') : $datePart;
}
$isAdmin = auth_is_admin();
$isOwner = $isAdmin || ($currentUserId > 0 && (int)($test['user_id'] ?? 0) === $currentUserId);
$testStatus = (string)($test['status'] ?? 'published');
$isDraft = $testStatus === 'draft';

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
} elseif ($isDraft) {
    $statusLabel = 'Черновик';
    $statusText = 'Тест сохранён как черновик и виден только вам';
}

$showPass = !$isDraft && !$isUnavailable && !$isTrashContext;
$showBookmark = !$isDraft
    && !$isTrashContext
    && auth_is_logged_in()
    && ($cardContext === 'my_bookmarks' || ($cardContext !== 'my_tests' && !$isOwner));
$showShare = !$isDraft && !$isUnavailable && !$isTrashContext;
$showFooterManage = !$isUnavailable && !$isTrashContext && $cardContext === 'my_tests' && $isOwner;
$showEdit = !$showFooterManage && !$isUnavailable && !$isTrashContext && $cardContext === 'my_tests' && $isOwner;
$showDeleteToTrash = !$showFooterManage && !$isUnavailable && $cardContext === 'my_tests' && $isOwner;
$showRestoreDestroy = $isTrashContext;
$showStats = !$isDraft && ($isTrashContext || !$isUnavailable);

$formatTimeLimitShort = static function (?int $seconds): string {
    if ($seconds === null || $seconds <= 0) {
        return 'Без времени';
    }

    return (int)floor($seconds / 60) . ' мин';
};

$formatTimeStat = static function (?int $seconds): array {
    if ($seconds === null || $seconds <= 0) {
        return ['0', 'без времени'];
    }

    return [(string)(int)floor($seconds / 60), 'мин'];
};

$testUrl = test_url($testId, $cardTitle);
?>

<article class="card test-card test-card--premium<?= $isUnavailable ? ' test-card--unavailable' : '' ?><?= $stateClass ?><?= $contextClass ?><?= !$showBookmark ? ' test-card--no-bookmark' : '' ?>" data-test-card-id="<?= $testId ?>">
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
            <?php if ($isDraft && $isOwner && !$isTrashContext): ?>
                <a class="test-card__cover-title" href="/my/tests/<?= $testId ?>/edit">
                    <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php elseif (!$isUnavailable && !$isTrashContext): ?>
                <a class="test-card__cover-title" href="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php else: ?>
                <div class="test-card__cover-title">
                    <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="test-card__time-badge">
            <img src="/assets/img/test_card_svg/clock.svg" alt="" aria-hidden="true">
            <span><?= htmlspecialchars($formatTimeLimitShort($timeLimitSec), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <div class="test-card__content">
        <?php if ($isDraft && $isOwner && !$isTrashContext): ?>
            <a class="test-title-link" href="/my/tests/<?= $testId ?>/edit">
                <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php elseif (!$isUnavailable && !$isTrashContext): ?>
            <a class="test-title-link" href="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php else: ?>
            <div class="test-title-link">
                <?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="test-card__footer-tags">
            <?php if ($isUnavailable || $isTrashContext || $isDraft): ?>
                <span class="badge <?= $isDraft ? 'badge--warn' : ($availability === 'deleted' ? 'badge--deleted' : 'badge--trashed') ?>">
                    <?= htmlspecialchars($statusLabel !== '' ? $statusLabel : ($isDraft ? 'Черновик' : 'В корзине'), ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php else: ?>
                <span class="badge <?= $isPublic ? 'badge--ok' : 'badge--registered' ?>">
                    <?= $isPublic ? 'Для всех' : 'Для зарегистрированных' ?>
                </span>
                <span class="badge badge--answers">
                    <?= htmlspecialchars($answersModeLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>

            <span class="test-chip test-chip--soft test-chip--creator">
                <img src="/assets/img/test_card_svg/user.svg" alt="" aria-hidden="true">
                <?php if ($creatorUrl !== ''): ?>
                    <a class="test-card__author-link" href="<?= htmlspecialchars($creatorUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </span>

            <?php if ($isTrashContext && $deletedAt !== ''): ?>
                <?php
                    $deletedDatePart = preg_split('/\s+/', $deletedAt)[0] ?? $deletedAt;
                    $deletedDateObj = DateTime::createFromFormat('Y-m-d', $deletedDatePart);
                    $deletedDateFmt = $deletedDateObj ? $deletedDateObj->format('d-m-Y') : $deletedDatePart;
                ?>
                <span class="test-chip test-chip--soft test-chip--date">
                    <img src="/assets/img/test_card_svg/calendar.svg" alt="" aria-hidden="true">
                    <span><?= htmlspecialchars($deletedDateFmt, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            <?php else: ?>
                <span class="test-chip test-chip--soft test-chip--date">
                    <img src="/assets/img/test_card_svg/calendar.svg" alt="" aria-hidden="true">
                    <span><?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            <?php endif; ?>
        </div>

        <p class="test-description">
            <?= htmlspecialchars(($isDraft || ($isUnavailable && !$isTrashContext)) && $statusText !== '' ? $statusText : $descriptionText, ENT_QUOTES, 'UTF-8') ?>
        </p>

    </div>

    <div class="test-card__side">
        <div class="test-card__icon-actions">
            <?php if ($showPass): ?>
                <a
                    href="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="test-card__icon-btn test-card__icon-btn--primary<?= !$canPass ? ' test-card__icon-btn--primary-auth' : '' ?>"
                    aria-label="<?= $canPass ? 'Пройти тест' : 'Войти для прохождения теста' ?>"
                >
                    <img src="/assets/img/test_card_svg/play.svg" alt="" aria-hidden="true">
                    <span class="test-card__icon-btn-text">Перейти к тесту</span>
                </a>
            <?php endif; ?>

            <?php if ($showBookmark): ?>
                <?= form_open('/my/bookmarks/' . $testId . '/toggle', 'post', [
                    'class' => 'inline test-card__bookmark-action',
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
                    class="test-card__icon-btn test-card__icon-btn--share ui-tooltip ui-tooltip--bottom"
                    data-share-copy="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>"
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
                    <button type="submit" class="btn btn-ghost btn-sm btn-with-icon" aria-label="Восстановить тест">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        Восстановить
                    </button>
                </form>

                <?= form_open('/my/tests/' . $testId . '/destroy', 'post', [
                    'class'              => 'inline',
                    'data-confirm'       => '1',
                    'data-confirm-title' => 'Удалить навсегда?',
                    'data-confirm-text'  => 'Удалить этот тест навсегда? Это действие нельзя отменить.',
                    'data-confirm-ok'    => 'Удалить навсегда',
                ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm btn-with-icon" aria-label="Удалить тест навсегда">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        Удалить навсегда
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($showStats): ?>
        <div class="test-card__stats">
            <div class="test-stat">
                <span class="test-stat__main">
                    <img src="/assets/img/test_card_svg/question.svg" alt="" aria-hidden="true">
                    <span class="test-stat__value"><?= $formatNumber($questionsCount) ?></span>
                </span>
                <span class="test-stat__label"><?= $pluralRu($questionsCount, 'вопрос', 'вопроса', 'вопросов') ?></span>
            </div>
            <div class="test-stat">
                <?php [$timeStatValue, $timeStatLabel] = $formatTimeStat($timeLimitSec); ?>
                <span class="test-stat__main">
                    <img src="/assets/img/test_card_svg/clock.svg" alt="" aria-hidden="true">
                    <span class="test-stat__value"><?= htmlspecialchars($timeStatValue, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <span class="test-stat__label"><?= htmlspecialchars($timeStatLabel, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="test-stat">
                <span class="test-stat__main">
                    <img src="/assets/img/test_card_svg/eye-open.svg" alt="" aria-hidden="true">
                    <span class="test-stat__value"><?= $formatNumber($viewsCount) ?></span>
                </span>
                <span class="test-stat__label"><?= $pluralRu($viewsCount, 'просмотр', 'просмотра', 'просмотров') ?></span>
            </div>
            <div class="test-stat">
                <span class="test-stat__main">
                    <img src="/assets/img/test_card_svg/refresh.svg" alt="" aria-hidden="true">
                    <span class="test-stat__value"><?= $formatNumber($attemptsCount) ?></span>
                </span>
                <span class="test-stat__label"><?= $pluralRu($attemptsCount, 'прохождение', 'прохождения', 'прохождений') ?></span>
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

    <div class="test-card__home-footer">
        <?php if ($isTrashContext): ?>
            <span class="test-card__home-footer-item test-card__home-footer-item--date">
                <img src="/assets/img/test_card_svg/calendar.svg" alt="" aria-hidden="true">
                <span class="test-card__home-footer-text">
                    <span class="test-card__home-footer-value"><?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="test-card__home-footer-label">Создан</span>
                </span>
            </span>
        <?php else: ?>
            <?php if ($showFooterManage): ?>
                <span class="test-card__home-footer-item test-card__home-footer-item--actions">
                    <a
                        href="/my/tests/<?= $testId ?>/edit"
                        class="test-card__footer-action ui-tooltip ui-tooltip--top"
                        data-tooltip="Редактировать"
                        aria-label="Редактировать тест"
                    >
                        <img src="/assets/img/test_card_svg/edit-2-svgrepo-com.svg" alt="" aria-hidden="true">
                    </a>

                    <?= form_open('/my/tests/' . $testId . '/delete', 'post', [
                        'class' => 'inline',
                        'data-confirm' => '1',
                        'data-confirm-title' => 'Отправить в корзину?',
                        'data-confirm-text' => 'Убрать этот тест в корзину? Его можно будет восстановить.',
                        'data-confirm-ok' => 'В корзину',
                    ]) ?>
                        <button
                            type="submit"
                            class="test-card__footer-action test-card__footer-action--danger ui-tooltip ui-tooltip--top"
                            data-tooltip="Удалить"
                            aria-label="Удалить тест"
                        >
                            <img src="/assets/img/test_card_svg/trash-test-card.svg" alt="" aria-hidden="true">
                        </button>
                    </form>
                </span>
            <?php else: ?>
                <span class="test-card__home-footer-item test-card__home-footer-item--creator">
                    <img src="/assets/img/test_card_svg/autor_of_tests.svg" alt="" aria-hidden="true">
                    <span class="test-card__home-footer-text">
                        <?php if ($creatorUrl !== ''): ?>
                            <a class="test-card__home-footer-value test-card__author-link" href="<?= htmlspecialchars($creatorUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <span class="test-card__home-footer-value"><?= htmlspecialchars($creatorName, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <span class="test-card__home-footer-label">Автор теста</span>
                    </span>
                </span>
            <?php endif; ?>
            <span class="test-card__home-footer-item test-card__home-footer-item--date">
                <img src="/assets/img/test_card_svg/calendar.svg" alt="" aria-hidden="true">
                <span class="test-card__home-footer-text">
                    <span class="test-card__home-footer-value"><?= htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="test-card__home-footer-label">Создан</span>
                </span>
            </span>
            <span class="test-card__home-footer-item test-card__home-footer-item--rating">
                <span class="test-card__home-footer-star" aria-hidden="true">★</span>
                <span class="test-card__home-footer-text">
                    <span class="test-card__home-footer-value"><?= number_format($ratingValue, 1, '.', '') ?> (<?= $formatNumber($ratingCount) ?>)</span>
                    <span class="test-card__home-footer-label">Рейтинг</span>
                </span>
            </span>
        <?php endif; ?>
    </div>
</article>
