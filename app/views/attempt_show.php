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

function _h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function _norm_opt(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return mb_strtolower($s);
}

$summaryCorrect = 0;
$summaryPartial = 0;
$summaryWrong = 0;

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

            $tp = 0;
            foreach ($selectedNorm as $sNorm) {
                if (in_array($sNorm, $correctNorm, true)) {
                    $tp++;
                }
            }
            $questionScore = count($correctNorm) > 0 ? ($tp / count($correctNorm)) : 0.0;
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
            $tp = 0;
            foreach ($u as $oid) {
                if (in_array((int)$oid, $c, true)) {
                    $tp++;
                }
            }
            $questionScore = count($c) > 0 ? ($tp / count($c)) : 0.0;
        } else {
            $correctOptIds = array_map('intval', $correctOptionIdsByQ[$qid] ?? []);
            $isCorrect = (!empty($userOptIds)) && in_array((int)$userOptIds[0], $correctOptIds, true);
            $questionScore = $isCorrect ? 1.0 : 0.0;
        }
    }

    if ($isCorrect) {
        $summaryCorrect++;
    } elseif ($questionScore > 0.0) {
        $summaryPartial++;
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
?>

<div class="attempt">
    <div class="attempt__header">
        <?php
        $testIdRaw = $test['id'] ?? null;
        $testIdInt = ($testIdRaw === null || $testIdRaw === '') ? 0 : (int)$testIdRaw;
        $testIdBadge = $testIdInt > 0 ? ('Тест ID: ' . $testIdInt) : 'Тест ID: удалён';
        ?>
        <div class="attempt__meta">
            <span class="badge"><?= _h($testIdBadge) ?></span>
            <span class="badge">Результат ID: <?= (int)($attempt['id'] ?? 0) ?></span>
        </div>

        <h1 class="attempt__title"><?= _h((string)($test['title'] ?? 'Тест')) ?></h1>

        <div class="attempt__summary">
            <div class="sum">
                <div class="sum__label">Правильных</div>
                <div class="sum__value sum__value--ok"><?= (int)$summaryCorrect ?></div>
            </div>
            <div class="sum">
                <div class="sum__label">Частично верно</div>
                <div class="sum__value"><?= (int)$summaryPartial ?></div>
            </div>
            <div class="sum">
                <div class="sum__label">Неправильных</div>
                <div class="sum__value sum__value--bad"><?= (int)$summaryWrong ?></div>
            </div>
            <div class="sum">
                <div class="sum__label">Процент</div>
                <div class="sum__value"><?= (float)($attempt['percent'] ?? 0) ?>%</div>
            </div>
        </div>

        <div class="attempt__actions">
            <?php if (auth_is_logged_in()): ?>
                <a class="btn btn--ghost" href="/my/results">Мои результаты</a>
            <?php endif; ?>
            <?php if (empty($testMissing)): ?>
                <a class="btn btn--ghost" href="/tests/<?= (int)($test['id'] ?? 0) ?>">К описанию теста</a>
                <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">Пройти ещё раз</a>
            <?php endif; ?>
            <a class="btn btn--ghost" href="/">На главную</a>
        </div>

        <?php if ($sourceNotice !== ''): ?>
            <div class="attempt__notice attempt__notice--danger"><?= _h($sourceNotice) ?></div>
        <?php endif; ?>
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

                $tp = 0;
                foreach ($selectedNorm as $sNorm) {
                    if (in_array($sNorm, $correctNorm, true)) {
                        $tp++;
                    }
                }
                $questionScore = count($correctNorm) > 0 ? ($tp / count($correctNorm)) : 0.0;
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
                $tp = 0;
                foreach ($u as $oid) {
                    if (in_array((int)$oid, $c, true)) {
                        $tp++;
                    }
                }
                $questionScore = count($c) > 0 ? ($tp / count($c)) : 0.0;
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
        } elseif ($questionScore > 0.0) {
            $stateText = 'Частично верно';
            $stateClass = 'qres__state--ok';
        }
        ?>

        <div class="qres">
            <div class="qres__top">
                <div class="qres__num">Вопрос #<?= (int)($i + 1) ?></div>
                <div class="qres__state <?= $stateClass ?>">
                    <?= $stateText ?>
                </div>
            </div>

            <div class="qres__text"><?= nl2br(_h($qText)) ?></div>

            <div class="qres__answers">
                <?php if ($type === 'input'): ?>
                    <div class="input-res">
                        <div class="input-res__row">
                            <div class="input-res__label">Твой ответ</div>
                            <div class="input-res__value <?= $isCorrect ? 'ok' : 'bad' ?>">
                                <?= _h($userTextRaw !== '' ? $userTextRaw : '—') ?>
                            </div>
                        </div>
                        <?php if (!$isCorrect): ?>
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
                        ?>
                        <?php if (empty($renderOptions)): ?>
                            <div class="muted">Ответ не выбран.</div>
                        <?php else: ?>
                            <?php foreach ($renderOptions as $snapshotText): ?>
                                <?php
                                $norm = _norm_opt((string)$snapshotText);
                                $isCorrectOpt = isset($correctNormMap[$norm]);
                                $isUserOpt = isset($selectedNormMap[$norm]);

                                $optCls = 'opt';
                                if ($isCorrectOpt) {
                                    $optCls .= ' opt--correct';
                                }
                                if ($isUserOpt && !$isCorrectOpt) {
                                    $optCls .= ' opt--wrong';
                                }
                                ?>
                                <div class="<?= $optCls ?>">
                                    <div class="opt__mark">
                                        <?php if ($isUserOpt): ?>✓<?php else: ?>&nbsp;<?php endif; ?>
                                    </div>
                                    <div class="opt__text"><?= _h($snapshotText) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!$isCorrect && !empty($snapshotCorrectOptionTexts)): ?>
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

                                $isCorrectOpt = in_array($oid, $correctOptIds, true);
                                $isUserOpt = in_array($oid, $userOptIds, true);

                                $cls = 'opt';
                                if ($isCorrectOpt) $cls .= ' opt--correct';
                                if ($isUserOpt && !$isCorrectOpt) $cls .= ' opt--wrong';
                                ?>
                                <div class="<?= $cls ?>">
                                    <div class="opt__mark">
                                        <?php if ($isUserOpt): ?>✓<?php else: ?>&nbsp;<?php endif; ?>
                                    </div>
                                    <div class="opt__text"><?= _h($otext) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php endforeach; ?>
</div>
