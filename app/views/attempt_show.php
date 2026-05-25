<?php
declare(strict_types=1);

/** @var array $attempt */
/** @var array $test */
/** @var array $questions */
/** @var array $optionsByQuestionId */
/** @var array $correctOptionIdsByQ */
/** @var array $correctTextAnswersByQ */
/** @var array $userByQ */
/** @var bool $snapshotMode */
/** @var bool $testMissing */
/** @var string $sourceState */
/** @var bool $show_rate_prompt */
/** @var bool $revealCorrectAnswers */
/** @var bool $can_manage_share */

function _h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function _norm_opt(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return mb_strtolower($s);
}

$summaryCorrect = 0;
$summaryWrong = 0;
$revealCorrectAnswers = (bool)($revealCorrectAnswers ?? true);
$testUrl = test_url((int)($test['id'] ?? 0), (string)($test['title'] ?? 'Тест'));

foreach ($questions as $q) {
    $qid = (int)($q['id'] ?? 0);
    $type = (string)($q['type'] ?? 'radio');
    $userRows = $userByQ[$qid] ?? [];

    $userOptIds = [];
    $userTextRaw = '';
    foreach ($userRows as $r) {
        $oid = $r['option_id'] ?? null;
        if ($oid !== null && $oid !== '') {
            $userOptIds[] = (int)$oid;
        }
        if ($r['text_answer'] !== null && $r['text_answer'] !== '') {
            $userTextRaw = (string)$r['text_answer'];
        }
    }
    $userOptIds = array_values(array_unique($userOptIds));

    $correctPayload = null;
    if (!empty($userRows)) {
        $rawPayload = $userRows[0]['correct_payload_snapshot'] ?? null;
        if (is_string($rawPayload) && trim($rawPayload) !== '') {
            $decoded = json_decode($rawPayload, true);
            if (is_array($decoded)) {
                $correctPayload = $decoded;
            }
        }
    }

    $isCorrect = false;
    $questionScore = 0.0;

    if (!empty($snapshotMode) && !empty($userRows)) {
        $firstRow = $userRows[0];
        $isCorrect = (int)($firstRow['is_correct_snapshot'] ?? 0) === 1;

        if ($type === 'checkbox') {
            $snapshotCorrectOptionTexts = [];
            $selectedSnapshotOptions = [];
            if (is_array($correctPayload)) {
                $snapshotCorrectOptionTexts = $correctPayload['correct_option_texts'] ?? [];
                if (!is_array($snapshotCorrectOptionTexts)) {
                    $snapshotCorrectOptionTexts = [];
                }
                $selectedSnapshotOptions = $correctPayload['selected_option_texts'] ?? [];
                if (!is_array($selectedSnapshotOptions)) {
                    $selectedSnapshotOptions = [];
                }
            }

            $correctNorm = array_values(array_unique(array_map('_norm_opt', array_map('strval', $snapshotCorrectOptionTexts))));
            $selectedNorm = array_values(array_unique(array_map('_norm_opt', array_map('strval', $selectedSnapshotOptions))));
            foreach ($userRows as $r) {
                $txt = trim((string)($r['option_text_snapshot'] ?? ''));
                if ($txt !== '') {
                    $selectedNorm[] = _norm_opt($txt);
                }
            }
            $selectedNorm = array_values(array_unique($selectedNorm));

            sort($correctNorm);
            sort($selectedNorm);
            $isCorrect = (!empty($correctNorm) || !empty($selectedNorm)) && ($selectedNorm === $correctNorm);
            $questionScore = $isCorrect ? 1.0 : 0.0;
        } else {
            $questionScore = $isCorrect ? 1.0 : 0.0;
        }
    } else {
        if ($type === 'input') {
            $userNorm = normalize_input_answer($userTextRaw);
            $variants = $correctTextAnswersByQ[$qid] ?? [];
            $variantsNorm = [];
            foreach ($variants as $v) {
                $variantsNorm[] = normalize_input_answer((string)$v);
            }
            $isCorrect = ($userNorm !== '') && in_array($userNorm, $variantsNorm, true);
            $questionScore = $isCorrect ? 1.0 : 0.0;
        } elseif ($type === 'checkbox') {
            $u = $userOptIds; sort($u);
            $c = array_map('intval', $correctOptionIdsByQ[$qid] ?? []); sort($c);
            $isCorrect = (!empty($u) || !empty($c)) && ($u === $c);
            $questionScore = $isCorrect ? 1.0 : 0.0;
        } elseif ($type === 'order') {
            // order type always stores a snapshot; without one, treat as wrong
            $isCorrect = false;
            $questionScore = 0.0;
        } else {
            $correctOptIds = array_map('intval', $correctOptionIdsByQ[$qid] ?? []);
            $isCorrect = (!empty($userOptIds)) && in_array((int)$userOptIds[0], $correctOptIds, true);
            $questionScore = $isCorrect ? 1.0 : 0.0;
        }
    }

    if ($isCorrect) {
        $summaryCorrect++;
    } else {
        $summaryWrong++;
    }
}

$sourceNotice = '';
if (($sourceState ?? 'ok') === 'changed') {
    $sourceNotice = 'Исходная версия теста недоступна: тест был изменён.';
} elseif (($sourceState ?? 'ok') === 'deleted') {
    $sourceNotice = 'Исходная версия теста недоступна: тест был удалён.';
}

$attemptStatus = (string)($attempt['status'] ?? '');
$expiredNotice = '';
$percentVal = (float)($attempt['percent'] ?? 0);
$resultLabel = test_attempt_result_label_from_snapshot($attempt);
$scoreGrade = $percentVal >= 90 ? 'excellent' : ($percentVal >= 80 ? 'good' : ($percentVal >= 60 ? 'ok' : 'bad'));

$durationSec = (int)($attempt['duration_sec'] ?? 0);
$elapsedFormatted = '';
if ($durationSec > 0) {
    $eh = intdiv($durationSec, 3600);
    $em = intdiv($durationSec % 3600, 60);
    $es = $durationSec % 60;
    if ($eh > 0) {
        $elapsedFormatted = sprintf('%d:%02d:%02d', $eh, $em, $es);
    } elseif ($em > 0) {
        $elapsedFormatted = sprintf('%d:%02d', $em, $es);
    } else {
        $elapsedFormatted = $es . ' сек';
    }
}
if ($attemptStatus === 'expired') {
    $expiredNotice = 'Время на прохождение истекло. Попытка завершена автоматически, засчитаны только ответы, на которые вы успели ответить.';
}
$canManageShare = (bool)($can_manage_share ?? false);
$shareToken = trim((string)($attempt['share_token'] ?? ''));
$shareEnabled = (int)($attempt['share_enabled'] ?? 0) === 1 && $shareToken !== '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$shareUrl = $shareEnabled ? ($scheme . '://' . $host . '/s/' . $shareToken) : '';
?>
<?php if (!empty($show_rate_prompt) && auth_is_logged_in() && (int)($test['id'] ?? 0) > 0): ?>
    <div class="ui-backdrop is-open" data-rate-modal="1" aria-hidden="false">
        <div class="ui-modal attempt-rate-modal" role="dialog" aria-modal="true" aria-labelledby="attemptRateTitle">
            <div class="ui-modal__head">
                <h3 class="ui-modal__title" id="attemptRateTitle">Оцените тест</h3>
            </div>
            <div class="ui-modal__body">
                <?= form_open('/tests/' . (int)$test['id'] . '/rate', 'post', [
                    'class' => 'attempt-rate-modal__form',
                    'data-rate-modal-form' => '1',
                ]) ?>
                    <div class="attempt-rate-modal__stars" data-rate-modal-stars>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button
                                type="submit"
                                name="rating"
                                value="<?= $i ?>"
                                class="attempt-rate-modal__star"
                                data-rate-value="<?= $i ?>"
                                aria-label="Оценка <?= $i ?> из 5"
                            >★</button>
                        <?php endfor; ?>
                    </div>
                    <div class="attempt-rate-modal__result" data-rate-modal-result></div>
                    <button type="button" class="btn btn--ghost attempt-rate-modal__later" data-rate-modal-later>Оценить позже</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="attempt">
    <div class="attempt__header">
        <?php
        $testIdRaw = $test['id'] ?? null;
        $testIdInt = ($testIdRaw === null || $testIdRaw === '') ? 0 : (int)$testIdRaw;
        $testIdBadge = $testIdInt > 0 ? ('Тест ID: ' . $testIdInt) : 'Тест ID: удалён';
        ?>
        <div class="attempt__meta">
            <span class="badge"><?= _h($testIdBadge) ?></span>
            <span class="badge">Попытка №<?= max(1, (int)($attempt['attempt_no'] ?? 1)) ?></span>
            <span class="badge">Результат ID: <?= (int)($attempt['id'] ?? 0) ?></span>
            <?php if ($attemptStatus === 'expired'): ?>
                <span class="badge badge--danger-soft">Время истекло</span>
            <?php endif; ?>
        </div>

        <div class="attempt__meta-sep" aria-hidden="true"></div>

        <h1 class="attempt__title"><?= _h((string)($test['title'] ?? 'Тест')) ?></h1>

        <?php if ($sourceNotice !== ''): ?>
            <div class="attempt__notice attempt__notice--danger" role="alert">
                <svg class="attempt__notice-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= _h($sourceNotice) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($expiredNotice !== ''): ?>
            <div class="attempt__notice attempt__notice--warn" role="alert">
                <svg class="attempt__notice-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= _h($expiredNotice) ?></span>
            </div>
        <?php endif; ?>

        <div class="scorecard scorecard--<?= $scoreGrade ?>">
            <div class="scorecard__body">
                <div class="scorecard__hero">
                    <div class="scorecard__percent scorecard__percent--<?= $scoreGrade ?>">
                        <?= number_format((float)($attempt['percent'] ?? 0), 0) ?>%
                    </div>
                    <div class="scorecard__bar-wrap">
                        <div class="scorecard__bar scorecard__bar--<?= $scoreGrade ?>" style="width: <?= min(100.0, (float)($attempt['percent'] ?? 0)) ?>%"></div>
                    </div>
                    <div class="scorecard__hero-label">Результат</div>
                    <div class="scorecard__result-label"><?= _h($resultLabel) ?></div>
                </div>

                <div class="scorecard__divider" aria-hidden="true"></div>

                <div class="scorecard__stats">
                    <div class="scorecard__stat">
                        <span class="scorecard__stat-icon scorecard__stat-icon--ok" aria-hidden="true">✓</span>
                        <span class="scorecard__stat-val"><?= (int)$summaryCorrect ?></span>
                        <span class="scorecard__stat-label">Правильных</span>
                    </div>
                    <div class="scorecard__stat">
                        <span class="scorecard__stat-icon scorecard__stat-icon--bad" aria-hidden="true">✗</span>
                        <span class="scorecard__stat-val"><?= (int)$summaryWrong ?></span>
                        <span class="scorecard__stat-label">Неправильных</span>
                    </div>
                    <?php if ($elapsedFormatted !== ''): ?>
                        <div class="scorecard__stat">
                            <span class="scorecard__stat-icon scorecard__stat-icon--time" aria-hidden="true">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </span>
                            <span class="scorecard__stat-val scorecard__stat-val--time"><?= _h($elapsedFormatted) ?></span>
                            <span class="scorecard__stat-label">Затрачено</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($revealCorrectAnswers): ?>
                <div class="scorecard__legend" aria-label="Легенда цветов результата">
                    <span class="legend-dot legend-dot--ok" aria-hidden="true"></span>
                    <span>Правильный вариант</span>
                    <span class="legend-sep" aria-hidden="true">·</span>
                    <span class="legend-dot legend-dot--bad" aria-hidden="true"></span>
                    <span>Выбранный неверный</span>
                </div>
            <?php else: ?>
                <div class="scorecard__legend" aria-label="Ответы скрыты">
                    Правильные ответы скрыты настройками теста.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canManageShare): ?>
            <div class="share-result">
                <div class="share-result__head">
                    <div>
                        <div class="share-result__title">Поделиться результатом</div>
                        <div class="share-result__hint">Публичная ссылка показывает подробности только вам и автору теста.</div>
                    </div>
                    <?php if (!$shareEnabled): ?>
                        <?= form_open('/attempts/' . (int)($attempt['id'] ?? 0) . '/share', 'post') ?>
                            <button type="submit" class="btn btn--primary">Создать ссылку</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($shareEnabled): ?>
                    <div class="share-result__body">
                        <input class="share-result__link input" type="text" value="<?= _h($shareUrl) ?>" readonly>
                        <button type="button" class="btn btn--ghost" data-copy="/s/<?= _h($shareToken) ?>">
                            <span data-copy-label>Копировать</span>
                        </button>
                        <?= form_open('/attempts/' . (int)($attempt['id'] ?? 0) . '/share/disable', 'post', ['class' => 'inline-form']) ?>
                            <button type="submit" class="btn btn--danger">Отключить ссылку</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="attempt__actions-wrap">
        <div class="attempt__actions">
            <?php if (empty($testMissing)): ?>
                <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">
                    <svg class="btn-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3V9M3 9H9M3 9C5.33 6.91 7.48 4.55 10.75 4.09C12.68 3.82 14.65 4.18 16.35 5.12C18.06 6.07 19.42 7.54 20.21 9.32M21 21V15M21 15H15M21 15C18.67 17.09 16.52 19.45 13.25 19.91C11.32 20.18 9.35 19.82 7.65 18.88C5.94 17.93 4.58 16.46 3.79 14.68"/></svg>
                    Пройти ещё раз
                </a>
                <a class="btn btn--ghost" href="<?= _h($testUrl) ?>">
                    <svg class="btn-icon" width="15" height="15" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="4" aria-hidden="true"><path d="M16 10h24l10 10v32H16z" stroke-linejoin="round"/><path d="M40 10v10h10" stroke-linejoin="round"/><path d="M23 30h20M23 38h20M23 46h20" stroke-linecap="round"/></svg>
                    К описанию теста
                </a>
            <?php endif; ?>
            <?php if (auth_is_logged_in()): ?>
                <a class="btn btn--ghost" href="/my/results">
                    <svg class="btn-icon" width="15" height="15" viewBox="0 0 64 64" fill="none" aria-hidden="true"><path d="M10 50h44" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><rect x="14" y="34" width="8" height="14" rx="2.2" fill="currentColor"/><rect x="27" y="25" width="8" height="23" rx="2.2" fill="currentColor"/><rect x="40" y="16" width="8" height="32" rx="2.2" fill="currentColor"/></svg>
                    Мои результаты
                </a>
            <?php endif; ?>
            <a class="btn btn--ghost" href="/">
                <svg class="btn-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 9L12 3l7.5 6V21h-5v-6.5h-5V21h-5V9z"/></svg>
                На главную
            </a>
        </div>
        </div>

    </div>

    <?php foreach ($questions as $i => $q): ?>
        <?php
        $qid = (int)($q['id'] ?? 0);
        $type = (string)($q['type'] ?? 'radio');
        $qText = (string)($q['question_text'] ?? '');

        $opts = $optionsByQuestionId[$qid] ?? [];
        $correctOptIds = array_map('intval', $correctOptionIdsByQ[$qid] ?? []);

        $userRows = $userByQ[$qid] ?? [];

        $userOptIds = [];
        $userTextRaw = '';
        foreach ($userRows as $r) {
            $oid = $r['option_id'] ?? null;
            if ($oid !== null && $oid !== '') {
                $userOptIds[] = (int)$oid;
            }
            if ($r['text_answer'] !== null && $r['text_answer'] !== '') {
                $userTextRaw = (string)$r['text_answer'];
            }
        }
        $userOptIds = array_values(array_unique($userOptIds));

        $correctPayload = null;
        if (!empty($userRows)) {
            $rawPayload = $userRows[0]['correct_payload_snapshot'] ?? null;
            if (is_string($rawPayload) && trim($rawPayload) !== '') {
                $decoded = json_decode($rawPayload, true);
                if (is_array($decoded)) {
                    $correctPayload = $decoded;
                }
            }
        }

        $isCorrect = false;
        $questionScore = 0.0;
        $snapshotCorrectOptionTexts = [];
        $snapshotCorrectTextAnswers = [];

        if (!empty($snapshotMode) && !empty($userRows)) {
            $firstRow = $userRows[0];
            $isCorrect = (int)($firstRow['is_correct_snapshot'] ?? 0) === 1;

            if (is_array($correctPayload)) {
                $snapshotCorrectOptionTexts = $correctPayload['correct_option_texts'] ?? [];
                if (!is_array($snapshotCorrectOptionTexts)) {
                    $snapshotCorrectOptionTexts = [];
                }

                $snapshotCorrectTextAnswers = $correctPayload['correct_text_answers'] ?? [];
                if (!is_array($snapshotCorrectTextAnswers)) {
                    $snapshotCorrectTextAnswers = [];
                }
            }

            if ($type === 'checkbox' && !empty($snapshotCorrectOptionTexts)) {
                $correctNorm = array_values(array_unique(array_map('_norm_opt', array_map('strval', $snapshotCorrectOptionTexts))));
                $selectedNorm = [];
                foreach ($userRows as $r) {
                    $txt = trim((string)($r['option_text_snapshot'] ?? ''));
                    if ($txt !== '') {
                        $selectedNorm[] = _norm_opt($txt);
                    }
                }
                $selectedNorm = array_values(array_unique($selectedNorm));

                sort($correctNorm);
                sort($selectedNorm);
                $isCorrect = (!empty($correctNorm) || !empty($selectedNorm)) && ($selectedNorm === $correctNorm);
                $questionScore = $isCorrect ? 1.0 : 0.0;
            } else {
                $questionScore = $isCorrect ? 1.0 : 0.0;
            }
        } else {
            if ($type === 'input') {
                $userNorm = normalize_input_answer($userTextRaw);
                $variants = $correctTextAnswersByQ[$qid] ?? [];
                $variantsNorm = [];
                foreach ($variants as $v) {
                    $variantsNorm[] = normalize_input_answer((string)$v);
                }
                $isCorrect = ($userNorm !== '') && in_array($userNorm, $variantsNorm, true);
                $questionScore = $isCorrect ? 1.0 : 0.0;
            } elseif ($type === 'checkbox') {
                $u = $userOptIds; sort($u);
                $c = $correctOptIds; sort($c);
                $isCorrect = (!empty($u) || !empty($c)) && ($u === $c);
                $questionScore = $isCorrect ? 1.0 : 0.0;
            } else { // radio
                $isCorrect = (!empty($userOptIds)) && in_array((int)$userOptIds[0], $correctOptIds, true);
                $questionScore = $isCorrect ? 1.0 : 0.0;
            }
        }

        $stateText = 'Неверно';
        $stateClass = 'qres__state--bad';
        if ($isCorrect) {
            $stateText = 'Правильно';
            $stateClass = 'qres__state--ok';
        }
        ?>

        <div class="qres">
            <div class="qres__head">
                <div class="qres__title">
                    <span class="qres__num"><?= (int)($i + 1) ?></span>
                    Вопрос
                </div>
                <?php if ($revealCorrectAnswers): ?>
                    <div class="qres__state <?= $stateClass ?>">
                        <?= $stateText ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="qres__body">
            <div class="qres__text"><?= nl2br(_h($qText)) ?></div>

            <?php $qImg = trim((string)($q['image_path'] ?? '')); ?>
            <?php if ($qImg !== ''): ?>
                <div class="qres__image">
                    <img
                        src="<?= _h($qImg) ?>"
                        alt=""
                        class="qres__image-img"
                        loading="lazy"
                    >
                </div>
            <?php endif; ?>

            <div class="qres__answers">
                <?php if ($type === 'order'): ?>
                    <?php
                    $orderCorrectTexts = [];
                    $orderSubmittedTexts = [];
                    if (is_array($correctPayload) && ($correctPayload['type'] ?? '') === 'order') {
                        $orderCorrectTexts = array_values(array_map('strval', (array)($correctPayload['correct_order_texts'] ?? [])));
                        $orderSubmittedTexts = array_values(array_map('strval', (array)($correctPayload['submitted_order_texts'] ?? [])));
                    }
                    ?>
                    <div class="order-res">
                        <div class="order-res__col">
                            <div class="order-res__label"><?= ($revealCorrectAnswers && $isCorrect) ? 'Правильно расставлено' : 'Твой порядок' ?></div>
                            <div class="order-res__list">
                                <?php if (empty($orderSubmittedTexts)): ?>
                                    <div class="muted">Не расставлено.</div>
                                <?php else: ?>
                                    <?php foreach ($orderSubmittedTexts as $pos => $itemText): ?>
                                        <?php
                                        $correctText = $orderCorrectTexts[$pos] ?? null;
                                        $posCorrect = $revealCorrectAnswers && ($correctText !== null && _norm_opt($itemText) === _norm_opt($correctText));
                                        ?>
                                        <div class="order-res__item <?= $revealCorrectAnswers ? ($posCorrect ? 'order-res__item--ok' : 'order-res__item--bad') : '' ?>">
                                            <span class="order-res__num"><?= (int)$pos + 1 ?></span>
                                            <span class="order-res__text"><?= _h($itemText) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($revealCorrectAnswers && !$isCorrect && !empty($orderCorrectTexts)): ?>
                        <div class="order-res__col">
                            <div class="order-res__label order-res__label--correct">Правильный порядок</div>
                            <div class="order-res__list">
                                <?php foreach ($orderCorrectTexts as $pos => $itemText): ?>
                                    <div class="order-res__item order-res__item--ok">
                                        <span class="order-res__num"><?= (int)$pos + 1 ?></span>
                                        <span class="order-res__text"><?= _h($itemText) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($type === 'input'): ?>
                    <div class="input-res">
                        <div class="input-res__row">
                            <div class="input-res__label">Твой ответ</div>
                            <div class="input-res__value <?= $revealCorrectAnswers ? ($isCorrect ? 'ok' : 'bad') : '' ?>">
                                <?= _h($userTextRaw !== '' ? $userTextRaw : '—') ?>
                            </div>
                        </div>
                        <?php if ($revealCorrectAnswers && !$isCorrect): ?>
                            <div class="input-res__row">
                                <div class="input-res__label">Правильные варианты</div>
                                <?php if (!empty($snapshotMode)): ?>
                                    <?php
                                    $snapshotTextVars = $snapshotCorrectTextAnswers;
                                    ?>
                                    <?php if (!empty($snapshotTextVars)): ?>
                                        <div class="input-res__value ok">
                                            <?= _h(implode(', ', array_map('strval', $snapshotTextVars))) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="input-res__value muted">
                                            Недоступно в snapshot-режиме
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="input-res__value ok">
                                        <?php
                                        $vars = $correctTextAnswersByQ[$qid] ?? [];
                                        echo _h(implode(', ', array_map('strval', $vars)));
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if (!empty($snapshotMode)): ?>
                        <?php
                        $snapshotOptions = [];
                        $correctNormMap = [];
                        $selectedNormMap = [];
                        $allSnapshotOptions = [];
                        $selectedSnapshotOptions = [];

                        if (is_array($correctPayload)) {
                            $allSnapshotOptions = $correctPayload['all_option_texts'] ?? [];
                            if (!is_array($allSnapshotOptions)) {
                                $allSnapshotOptions = [];
                            }

                            $selectedSnapshotOptions = $correctPayload['selected_option_texts'] ?? [];
                            if (!is_array($selectedSnapshotOptions)) {
                                $selectedSnapshotOptions = [];
                            }
                        }

                        foreach ($snapshotCorrectOptionTexts as $ct) {
                            $correctNormMap[_norm_opt((string)$ct)] = true;
                        }
                        foreach ($selectedSnapshotOptions as $selectedText) {
                            $selectedNormMap[_norm_opt((string)$selectedText)] = true;
                        }
                        foreach ($userRows as $r) {
                            $oid = (int)($r['option_id'] ?? 0);
                            $snapshotText = trim((string)($r['option_text_snapshot'] ?? ''));
                            if ($snapshotText === '' && $oid <= 0) {
                                continue;
                            }
                            if ($snapshotText === '') {
                                $snapshotText = 'Вариант #' . $oid;
                            }

                            $snapshotOptions[$oid > 0 ? $oid : (count($snapshotOptions) + 1)] = $snapshotText;
                            $selectedNormMap[_norm_opt($snapshotText)] = true;
                        }
                        ?>
                        <?php
                        $renderOptions = [];
                        if (!empty($allSnapshotOptions)) {
                            $renderOptions = array_values(array_map('strval', $allSnapshotOptions));
                        } elseif (!empty($snapshotOptions)) {
                            $renderOptions = array_values($snapshotOptions);
                        }

                        // карта нормализованный_текст => image_path из живой БД
                        $optImgByNorm = [];
                        foreach ($optionsByQuestionId[$qid] ?? [] as $liveOpt) {
                            $liveImg = trim((string)($liveOpt['image_path'] ?? ''));
                            if ($liveImg !== '') {
                                $optImgByNorm[_norm_opt((string)($liveOpt['option_text'] ?? ''))] = $liveImg;
                            }
                        }
                        ?>
                        <?php if (empty($renderOptions)): ?>
                            <div class="muted">Ответ не выбран.</div>
                        <?php else: ?>
                            <?php foreach ($renderOptions as $snapshotText): ?>
                                <?php
                                $norm = _norm_opt((string)$snapshotText);
                                $isCorrectOpt = isset($correctNormMap[$norm]);
                                $isUserOpt = isset($selectedNormMap[$norm]);
                                $optImg = $optImgByNorm[$norm] ?? '';

                                $optCls = 'opt';
                                if ($revealCorrectAnswers && $isCorrectOpt) $optCls .= ' opt--correct';
                                if ($revealCorrectAnswers && $isUserOpt && !$isCorrectOpt) $optCls .= ' opt--wrong';
                                if ($optImg !== '') $optCls .= ' opt--has-image';
                                ?>
                                <div class="<?= $optCls ?>">
                                    <div class="opt__mark">
                                        <?php if ($isUserOpt): ?>✓<?php else: ?>&nbsp;<?php endif; ?>
                                    </div>
                                    <?php if ($optImg !== ''): ?>
                                        <img src="<?= _h($optImg) ?>" alt="" class="opt__image" loading="lazy">
                                    <?php endif; ?>
                                    <div class="opt__text"><?= _h($snapshotText) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($revealCorrectAnswers && !$isCorrect && !empty($snapshotCorrectOptionTexts)): ?>
                            <div class="input-res">
                                <div class="input-res__row">
                                    <div class="input-res__label">Правильные варианты</div>
                                    <div class="input-res__value ok">
                                        <?= _h(implode(', ', array_map('strval', $snapshotCorrectOptionTexts))) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (empty($opts)): ?>
                            <div class="muted">Нет вариантов ответа.</div>
                        <?php else: ?>
                            <?php foreach ($opts as $opt): ?>
                                <?php
                                $oid = (int)($opt['id'] ?? 0);
                                $otext = (string)($opt['option_text'] ?? '');
                                $oimg = trim((string)($opt['image_path'] ?? ''));

                                $isCorrectOpt = in_array($oid, $correctOptIds, true);
                                $isUserOpt = in_array($oid, $userOptIds, true);

                                $cls = 'opt';
                                if ($revealCorrectAnswers && $isCorrectOpt) $cls .= ' opt--correct';
                                if ($revealCorrectAnswers && $isUserOpt && !$isCorrectOpt) $cls .= ' opt--wrong';
                                if ($oimg !== '') $cls .= ' opt--has-image';
                                ?>
                                <div class="<?= $cls ?>">
                                    <div class="opt__mark">
                                        <?php if ($isUserOpt): ?>✓<?php else: ?>&nbsp;<?php endif; ?>
                                    </div>
                                    <?php if ($oimg !== ''): ?>
                                        <img
                                            src="<?= _h($oimg) ?>"
                                            alt=""
                                            class="opt__image"
                                            loading="lazy"
                                        >
                                    <?php endif; ?>
                                    <div class="opt__text"><?= _h($otext) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            </div>
        </div>

    <?php endforeach; ?>
</div>

<div id="imgLightbox" class="img-lightbox" aria-hidden="true" role="dialog" aria-label="Просмотр изображения">
    <div class="img-lightbox__backdrop" data-lightbox-close></div>
    <div class="img-lightbox__content">
        <button class="img-lightbox__close" data-lightbox-close aria-label="Закрыть">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <img src="" alt="" class="img-lightbox__img" id="imgLightboxImg">
    </div>
</div>
