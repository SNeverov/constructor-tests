<?php
declare(strict_types=1);

/** @var array $test */
/** @var array $questions */
/** @var array $optionsByQuestionId */
/** @var array|null $attempt */
/** @var int $attemptId */
/** @var int $timeLimitSec */
/** @var int|null $remainingSec */
/** @var bool $showAnswers */
/** @var array $correctOptionIdsByQ */
/** @var array $correctTextAnswersByQ */

$showAnswers = (bool)($showAnswers ?? false);
$correctOptionIdsByQ = is_array($correctOptionIdsByQ ?? null) ? $correctOptionIdsByQ : [];
$correctTextAnswersByQ = is_array($correctTextAnswersByQ ?? null) ? $correctTextAnswersByQ : [];
$testUrl = test_url((int)($test['id'] ?? 0), (string)($test['title'] ?? 'Тест'));
?>

<div
    class="test-pass"
    data-test-id="<?= (int)$test['id'] ?>"
    data-attempt-id="<?= (int)($attempt['id'] ?? 0) ?>"
    data-time-limit-sec="<?= (int)($timeLimitSec ?? 0) ?>"
    data-remaining-sec="<?= ($remainingSec === null) ? '' : (int)$remainingSec ?>"
    <?= $showAnswers ? 'data-show-answers="1"' : '' ?>
>
    <div class="test-pass__header">
        <div class="test-pass__meta">
            <button
				type="button"
				class="badge badge--copy badge--copy-link btn btn-outline btn-sm btn-with-icon"
				data-copy="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>"
				title="Скопировать ссылку на тест"
			>
				<svg viewBox="0 0 24 24" class="badge__icon" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
				<span data-copy-label>ID: <?= (int)($test['id'] ?? 0) ?></span>
			</button>

            <span class="badge <?= (($test['access_level'] ?? '') === 'public') ? 'badge--ok' : 'badge--registered' ?>">
                <?= (($test['access_level'] ?? '') === 'public') ? 'Для всех' : 'Для зарегистрированных' ?>
            </span>

            <?php if ($showAnswers): ?>
                <span class="badge badge--answers-mode">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Режим с ответами
                </span>
            <?php endif; ?>
        </div>

        <h1 class="test-pass__title"><?= htmlspecialchars((string)($test['title'] ?? 'Тест'), ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ((int)($timeLimitSec ?? 0) > 0): ?>
            <div class="test-pass__timer-wrap">
                <div class="test-pass__timer" data-test-pass-timer>
                    <span class="test-pass__timer-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                    <div class="test-pass__timer-body">
                        <div class="test-pass__timer-label">Осталось времени</div>
                        <div class="test-pass__timer-value" data-timer-value>00:00:00</div>
                    </div>
                </div>
                <div class="test-pass__timer-note" data-timer-note>По истечении лимита попытка завершится автоматически.</div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($questions)): ?>
        <nav class="test-pass-nav" aria-label="Навигация по вопросам" data-question-nav>
            <div class="test-pass-nav__head">
                <span class="test-pass-nav__label">Прогресс</span>
                <span class="test-pass-nav__count" data-question-nav-count>0 / <?= count($questions) ?></span>
            </div>
            <div class="test-pass-nav__progress" aria-hidden="true">
                <div class="test-pass-nav__progress-fill" data-question-nav-progress></div>
            </div>
            <div class="test-pass-nav__buttons">
                <?php foreach ($questions as $i => $_q): ?>
                    <?php $questionNum = (int)($i + 1); ?>
                    <a
                        class="btn btn-outline btn-icon btn-sm test-pass-nav__btn"
                        href="#question-<?= $questionNum ?>"
                        aria-label="Перейти к вопросу <?= $questionNum ?>"
                        data-question-nav-btn="<?= $questionNum ?>"
                    >
                        <?= $questionNum ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>

    <form id="testPassForm" class="test-pass__form" method="post" action="/tests/<?= (int)($test['id'] ?? 0) ?>/finish" autocomplete="off">
        <?= csrf_field() ?>
		<input type="hidden" name="attempt_id" value="<?= (int)$attemptId ?>">
		<?php if (empty($questions)): ?>
            <div class="empty">
                В этом тесте пока нет вопросов.
            </div>
        <?php else: ?>
            <?php foreach ($questions as $i => $q): ?>
                <?php
                $qid = (int)($q['id'] ?? 0);
                $qType = (string)($q['type'] ?? 'radio');
                $qTypeLabel = match ($qType) {
                    'checkbox' => 'Несколько ответов',
                    'input' => 'Вписать ответ',
                    'order' => 'Расставить по порядку',
                    default => 'Один ответ',
                };
                $qText = (string)($q['question_text'] ?? '');
                $opts = $optionsByQuestionId[$qid] ?? [];

                $correctOptIds = [];
                if ($showAnswers && in_array($qType, ['radio', 'checkbox'], true)) {
                    $correctOptIds = array_map('intval', (array)($correctOptionIdsByQ[$qid] ?? []));
                }
                $correctTexts = [];
                if ($showAnswers && $qType === 'input') {
                    foreach ((array)($correctTextAnswersByQ[$qid] ?? []) as $ct) {
                        $correctTexts[] = normalize_input_answer((string)$ct);
                    }
                }
                ?>

                <div class="qcard" id="question-<?= (int)($i + 1) ?>" data-question-card data-question-index="<?= (int)($i + 1) ?>" data-question-type="<?= htmlspecialchars($qType, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="qcard__head">
                        <div class="qcard__title">
                            <span class="qcard__num"><?= (int)($i + 1) ?></span>
                            Вопрос
                        </div>
                        <div class="qcard__type"><?= htmlspecialchars($qTypeLabel, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="qcard__body">
                        <div class="qcard__text">
                            <?= nl2br(htmlspecialchars($qText, ENT_QUOTES, 'UTF-8')) ?>
                        </div>

                        <?php if (trim((string)($q['image_path'] ?? '')) !== ''): ?>
                            <div class="qcard__image">
                                <img
                                    src="<?= htmlspecialchars((string)$q['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt=""
                                    class="qcard__image-img"
                                    loading="lazy"
                                >
                            </div>
                        <?php endif; ?>

                        <div class="qcard__answers">
                            <?php if ($qType === 'input'): ?>
                                <input
                                    class="input"
                                    type="text"
                                    name="answers[<?= $qid ?>]"
                                    placeholder="Введи ответ..."
                                    <?php if ($showAnswers): ?>
                                        data-correct-text-answers="<?= htmlspecialchars(json_encode($correctTexts, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
                                    <?php endif; ?>
                                >
                            <?php elseif ($qType === 'order'): ?>
                                <div class="order-list" data-order-list data-qid="<?= $qid ?>"
                                    <?php if ($showAnswers && !empty($correctOptionIdsByQ[$qid])): ?>
                                        data-correct-order="<?= htmlspecialchars(json_encode(array_map('intval', $correctOptionIdsByQ[$qid]), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
                                    <?php endif; ?>
                                >
                                    <?php foreach ($opts as $oIdx => $opt): ?>
                                        <?php
                                        $oid = (int)($opt['id'] ?? 0);
                                        $otext = (string)($opt['option_text'] ?? '');
                                        ?>
                                        <div class="order-item" data-order-item data-opt-id="<?= $oid ?>">
                                            <span class="order-item__num" data-order-num aria-hidden="true"><?= $oIdx + 1 ?></span>
                                            <button type="button" class="order-item__handle btn btn-outline btn-icon btn-sm" data-order-drag aria-label="Перетащить элемент">
                                                <img class="btn__icon-img" src="/assets/img/test_create_svg/move.svg" alt="" width="16" height="16">
                                            </button>
                                            <span class="order-item__text"><?= htmlspecialchars($otext, ENT_QUOTES, 'UTF-8') ?></span>
                                            <div class="order-item__controls">
                                                <button type="button" class="order-item__btn btn btn-outline btn-icon btn-sm" data-order-up aria-label="Переместить вверх">
                                                    <img class="btn__icon-img" src="/assets/img/test_create_svg/move-up.svg" alt="" width="14" height="14">
                                                </button>
                                                <button type="button" class="order-item__btn btn btn-outline btn-icon btn-sm" data-order-down aria-label="Переместить вниз">
                                                    <img class="btn__icon-img" src="/assets/img/test_create_svg/move-down.svg" alt="" width="14" height="14">
                                                </button>
                                            </div>
                                            <input type="hidden" name="answers[<?= $qid ?>][]" value="<?= $oid ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?php if (empty($opts)): ?>
                                    <div class="muted">У этого вопроса нет вариантов.</div>
                                <?php else: ?>
                                    <?php foreach ($opts as $opt): ?>
                                        <?php
                                        $oid = (int)($opt['id'] ?? 0);
                                        $otext = (string)($opt['option_text'] ?? '');
                                        $oimg  = trim((string)($opt['image_path'] ?? ''));
                                        $name = ($qType === 'checkbox')
                                            ? 'answers[' . $qid . '][]'
                                            : 'answers[' . $qid . ']';
                                        $type = ($qType === 'checkbox') ? 'checkbox' : 'radio';
                                        $isCorrectOpt = $showAnswers && in_array($oid, $correctOptIds, true);
                                        ?>
                                        <label
                                            class="opt<?= $oimg !== '' ? ' opt--has-image' : '' ?>"
                                            <?= $isCorrectOpt ? 'data-is-correct' : '' ?>
                                        >
                                            <input
                                                class="opt__control"
                                                type="<?= $type ?>"
                                                name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                                                value="<?= $oid ?>"
                                            >
                                            <?php if ($oimg !== ''): ?>
                                                <img
                                                    src="<?= htmlspecialchars($oimg, ENT_QUOTES, 'UTF-8') ?>"
                                                    alt=""
                                                    class="opt__image"
                                                    loading="lazy"
                                                >
                                            <?php endif; ?>
                                            <span class="opt__text"><?= htmlspecialchars($otext, ENT_QUOTES, 'UTF-8') ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($showAnswers && in_array($qType, ['radio', 'checkbox', 'input', 'order'], true)): ?>
                    <div class="qcard__foot">
                        <button type="button" class="btn btn-ghost btn-sm" data-reset-btn>
                            Сбросить
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" data-answer-btn>
                            Ответить
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="test-pass__actions">
                <button type="button" class="btn btn-outline btn-md btn-with-icon" id="resetAnswersBtn">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21C7.02944 21 3 16.9706 3 12C3 9.69494 3.86656 7.59227 5.29168 6L8 3M12 3C16.9706 3 21 7.02944 21 12C21 14.3051 20.1334 16.4077 18.7083 18L16 21M3 3H8M8 3V8M21 21H16M16 21V16"/></svg>
                    Сбросить ответы
                </button>
                <button type="submit" class="btn btn-primary btn-md btn-with-icon" id="finishTestBtn">
                    <img src="/assets/img/done-ring-round.svg" class="btn__icon-img test-pass__finish-icon" width="18" height="18" aria-hidden="true">
                    Закончить тест
                </button>
                <a class="btn btn-ghost btn-md btn-with-icon" href="<?= htmlspecialchars($testUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <img src="/assets/img/arrow-left.svg" class="btn__icon-img" width="15" height="15" aria-hidden="true">
                    Назад к описанию
                </a>
            </div>

            <div class="test-pass__note" id="finishNote" hidden></div>
        <?php endif; ?>
    </form>
</div>

<?php if ((int)($timeLimitSec ?? 0) > 0): ?>
<div class="timer-pill" data-timer-pill aria-hidden="true">
    <span class="timer-pill__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </span>
    <span class="timer-pill__value" data-timer-pill-value>—</span>
</div>
<?php endif; ?>

<div id="imgLightbox" class="img-lightbox" aria-hidden="true" role="dialog" aria-label="Просмотр изображения">
    <div class="img-lightbox__backdrop" data-lightbox-close></div>
    <div class="img-lightbox__content">
        <button type="button" class="img-lightbox__close btn btn-ghost btn-icon" data-lightbox-close aria-label="Закрыть">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <img src="" alt="" class="img-lightbox__img" id="imgLightboxImg">
    </div>
</div>
