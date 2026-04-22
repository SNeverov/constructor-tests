<?php
declare(strict_types=1);

/** @var array $test */
/** @var array $questions */
/** @var array $optionsByQuestionId */
/** @var array|null $attempt */
/** @var int $timeLimitSec */
/** @var int|null $remainingSec */
?>

<div
    class="test-pass"
    data-test-id="<?= (int)$test['id'] ?>"
    data-attempt-id="<?= (int)($attempt['id'] ?? 0) ?>"
    data-time-limit-sec="<?= (int)($timeLimitSec ?? 0) ?>"
    data-remaining-sec="<?= ($remainingSec === null) ? '' : (int)$remainingSec ?>"
>
    <div class="test-pass__header">
        <div class="test-pass__meta">
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
                    default => 'Один ответ',
                };
                $qText = (string)($q['question_text'] ?? '');
                $opts = $optionsByQuestionId[$qid] ?? [];
                ?>

                <div class="qcard" data-question-card>
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
                                >
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
                                        ?>
                                        <label class="opt<?= $oimg !== '' ? ' opt--has-image' : '' ?>">
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
                </div>
            <?php endforeach; ?>

            <div class="test-pass__actions">
                <button type="button" class="btn btn--ghost" id="resetAnswersBtn">Сбросить ответы</button>
                <button type="submit" class="btn btn--primary" id="finishTestBtn">Закончить тест</button>
                <a class="btn btn--link" href="/tests/<?= (int)($test['id'] ?? 0) ?>">Назад к описанию</a>
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
        <button class="img-lightbox__close" data-lightbox-close aria-label="Закрыть">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <img src="" alt="" class="img-lightbox__img" id="imgLightboxImg">
    </div>
</div>
