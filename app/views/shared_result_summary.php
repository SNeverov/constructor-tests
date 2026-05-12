<?php
declare(strict_types=1);

/** @var array $attempt */
/** @var string $test_title */

$testTitle = (string)($test_title ?? 'Тест');
$login = trim((string)($attempt['attempt_user_login'] ?? ''));
if ($login === '') {
    $login = 'Пользователь';
}
$finishedAt = (string)($attempt['finished_at'] ?? '');
$correct = (int)($attempt['correct_count'] ?? 0);
$total = (int)($attempt['total_questions'] ?? 0);
$percent = (float)($attempt['percent'] ?? 0);
$label = trim((string)($attempt['result_label_snapshot'] ?? ''));
if ($label === '') {
    $label = $percent >= 90 ? 'Отлично' : ($percent >= 80 ? 'Хорошо' : ($percent >= 60 ? 'Удовлетворительно' : 'Плохо'));
}
$testId = (int)($attempt['test_id'] ?? 0);
$canPassTest = $testId > 0
    && empty($attempt['test_deleted_at'])
    && empty($attempt['test_deleted_forever_at'])
    && (string)($attempt['test_status'] ?? 'published') === 'published';
?>

<div class="attempt">
    <div class="shared-summary">
        <div class="badge">Публичный результат</div>
        <h1 class="shared-summary__title"><?= htmlspecialchars($testTitle, ENT_QUOTES, 'UTF-8') ?></h1>

        <div class="shared-summary__meta">
            <span class="badge">Проходил: <?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($finishedAt !== ''): ?>
                <span class="badge">Дата: <?= htmlspecialchars($finishedAt, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="shared-summary__grid">
            <div class="shared-summary__stat">
                <span>Правильных</span>
                <strong><?= $correct ?> / <?= $total ?></strong>
            </div>
            <div class="shared-summary__stat">
                <span>Процент</span>
                <strong><?= htmlspecialchars(number_format($percent, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>%</strong>
            </div>
            <div class="shared-summary__stat">
                <span>Итог</span>
                <strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="shared-summary__stat">
                <span>Вопросов</span>
                <strong><?= $total ?></strong>
            </div>
        </div>

        <?php if ($canPassTest): ?>
            <a class="btn btn--primary" href="/tests/<?= $testId ?>/pass">Пройти тест</a>
        <?php endif; ?>
    </div>
</div>
