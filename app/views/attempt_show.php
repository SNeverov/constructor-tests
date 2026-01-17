<?php
declare(strict_types=1);

/** @var array $attempt */
/** @var array $test */
/** @var array $questions */
/** @var array $optionsByQuestionId */
/** @var array $correctOptionIdsByQ */
/** @var array $correctTextAnswersByQ */
/** @var array $userByQ */

function _h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="attempt">
    <div class="attempt__header">
        <div class="attempt__meta">
            <span class="badge">Тест ID: <?= (int)($test['id'] ?? 0) ?></span>
            <span class="badge">Попытка ID: <?= (int)($attempt['id'] ?? 0) ?></span>
        </div>

        <h1 class="attempt__title"><?= _h((string)($test['title'] ?? 'Тест')) ?></h1>

        <div class="attempt__summary">
            <div class="sum">
                <div class="sum__label">Правильных</div>
                <div class="sum__value sum__value--ok"><?= (int)($attempt['correct_count'] ?? 0) ?></div>
            </div>
            <div class="sum">
                <div class="sum__label">Неправильных</div>
                <div class="sum__value sum__value--bad"><?= (int)($attempt['wrong_count'] ?? 0) ?></div>
            </div>
            <div class="sum">
                <div class="sum__label">Процент</div>
                <div class="sum__value"><?= (float)($attempt['percent'] ?? 0) ?>%</div>
            </div>
        </div>

        <div class="attempt__actions">
            <a class="btn btn--ghost" href="/tests/<?= (int)($test['id'] ?? 0) ?>">К описанию теста</a>
            <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">Пройти ещё раз</a>
            <a class="btn btn--link" href="/">На главную</a>
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

        $isCorrect = false;

        if ($type === 'input') {
            $userNorm = normalize_input_answer($userTextRaw);
            $variants = $correctTextAnswersByQ[$qid] ?? [];
            $variantsNorm = [];
            foreach ($variants as $v) {
                $variantsNorm[] = normalize_input_answer((string)$v);
            }
            $isCorrect = ($userNorm !== '') && in_array($userNorm, $variantsNorm, true);
        } elseif ($type === 'checkbox') {
            $u = $userOptIds; sort($u);
            $c = $correctOptIds; sort($c);
            $isCorrect = (!empty($u) || !empty($c)) && ($u === $c);
        } else { // radio
            $isCorrect = (!empty($userOptIds)) && in_array((int)$userOptIds[0], $correctOptIds, true);
        }
        ?>

        <div class="qres">
            <div class="qres__top">
                <div class="qres__num">Вопрос #<?= (int)($i + 1) ?></div>
                <div class="qres__state <?= $isCorrect ? 'qres__state--ok' : 'qres__state--bad' ?>">
                    <?= $isCorrect ? 'Правильно' : 'Неверно' ?>
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
                        <div class="input-res__row">
                            <div class="input-res__label">Правильные варианты</div>
                            <div class="input-res__value ok">
                                <?php
                                $vars = $correctTextAnswersByQ[$qid] ?? [];
                                echo _h(implode(' / ', array_map('strval', $vars)));
                                ?>
                            </div>
                        </div>
                    </div>
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
            </div>
        </div>

    <?php endforeach; ?>
</div>
