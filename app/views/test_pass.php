<?php
declare(strict_types=1);

/** @var array $test */
/** @var array $questions */
/** @var array $optionsByQuestionId */
?>

<div class="test-pass" data-test-id="<?= (int)$test['id'] ?>">
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
        <div class="test-pass__hint">Во время прохождения правильность не показывается</div>
    </div>

    <form id="testPassForm" class="test-pass__form" method="post" action="/tests/<?= (int)($test['id'] ?? 0) ?>/finish" autocomplete="off">
        <?= csrf_field() ?>
		<?php if (empty($questions)): ?>
            <div class="empty">
                В этом тесте пока нет вопросов.
            </div>
        <?php else: ?>
            <?php foreach ($questions as $i => $q): ?>
                <?php
                $qid = (int)($q['id'] ?? 0);
                $qType = (string)($q['type'] ?? 'radio');
                $qText = (string)($q['question_text'] ?? '');
                $opts = $optionsByQuestionId[$qid] ?? [];
                ?>

                <div class="qcard" data-question-card>
                    <div class="qcard__top">
                        <div class="qcard__num">Вопрос #<?= (int)($i + 1) ?></div>
                        <div class="qcard__type"><?= htmlspecialchars($qType, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>

                    <div class="qcard__text">
                        <?= nl2br(htmlspecialchars($qText, ENT_QUOTES, 'UTF-8')) ?>
                    </div>

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
                                    $name = ($qType === 'checkbox')
                                        ? 'answers[' . $qid . '][]'
                                        : 'answers[' . $qid . ']';
                                    $type = ($qType === 'checkbox') ? 'checkbox' : 'radio';
                                    ?>
                                    <label class="opt">
                                        <input
                                            class="opt__control"
                                            type="<?= $type ?>"
                                            name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                                            value="<?= $oid ?>"
                                        >
                                        <span class="opt__text"><?= htmlspecialchars($otext, ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
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
