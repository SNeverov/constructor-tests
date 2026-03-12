<?php
declare(strict_types=1);

/** @var array $rows */
/** @var array $filters */
/** @var array $pagination */

$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);

$queryBase = [
    'search' => (string)($filters['search'] ?? ''),
    'status' => (string)($filters['status'] ?? 'all'),
    'date_from' => (string)($filters['date_from'] ?? ''),
    'date_to' => (string)($filters['date_to'] ?? ''),
];
?>

<div class="results-page">
    <div class="page-head">
        <h1>Мои результаты</h1>
    </div>

    <form class="results-filters card" method="get" action="/my/results">
        <div class="results-filters__grid">
            <label class="results-filters__field">
                <span>Тест</span>
                <input
                    type="text"
                    class="input"
                    name="search"
                    value="<?= htmlspecialchars((string)($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Название теста"
                >
            </label>

            <label class="results-filters__field">
                <span>Статус</span>
                <select class="input" name="status">
                    <?php $status = (string)($filters['status'] ?? 'all'); ?>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все</option>
                    <option value="correct" <?= $status === 'correct' ? 'selected' : '' ?>>Правильно</option>
                    <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Частично</option>
                    <option value="wrong" <?= $status === 'wrong' ? 'selected' : '' ?>>Неверно</option>
                </select>
            </label>

            <label class="results-filters__field">
                <span>Дата с</span>
                <input type="date" class="input" name="date_from" value="<?= htmlspecialchars((string)($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="results-filters__field">
                <span>Дата по</span>
                <input type="date" class="input" name="date_to" value="<?= htmlspecialchars((string)($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
        </div>

        <div class="results-filters__actions">
            <button type="submit" class="btn btn--primary">Применить</button>
            <a href="/my/results" class="btn btn--ghost">Сбросить</a>
            <a href="/account" class="btn btn--link">В профиль</a>
        </div>
    </form>

    <div class="results-meta muted">Найдено: <?= $total ?></div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="empty-state__card">
                <h3 class="empty-state__title">Результаты не найдены</h3>
                <p class="empty-state__text">Попробуй изменить фильтры или пройти тесты.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="results-list">
            <?php foreach ($rows as $row): ?>
                <?php
                $percent = (float)($row['percent'] ?? 0);
                $statusText = 'Неверно';
                $statusClass = 'badge--bad';
                if ($percent >= 100) {
                    $statusText = 'Правильно';
                    $statusClass = 'badge--ok';
                } elseif ($percent > 0) {
                    $statusText = 'Частично';
                    $statusClass = 'badge--warn';
                }

                $title = trim((string)($row['live_test_title'] ?? ''));
                if ($title === '') {
                    $title = trim((string)($row['test_title_snapshot'] ?? ''));
                }
                if ($title === '') {
                    $title = 'Тест';
                }

                $testIdRaw = $row['test_id'] ?? null;
                $testId = ($testIdRaw === null || $testIdRaw === '') ? 0 : (int)$testIdRaw;
                ?>
                <article class="card result-item">
                    <div class="result-item__head">
                        <div class="result-item__title">
                            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                    </div>

                    <div class="result-item__meta">
                        <span class="badge">Результат ID: <?= (int)($row['id'] ?? 0) ?></span>
                        <span class="badge">
                            <?php if ($testId > 0): ?>
                                Тест ID: <?= $testId ?>
                            <?php else: ?>
                                Тест ID: удалён
                            <?php endif; ?>
                        </span>
                        <span class="badge">Процент: <?= $percent ?>%</span>
                        <span class="badge">Дата: <?= htmlspecialchars((string)($row['finished_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="result-item__actions">
                        <a class="btn btn--ghost" href="/attempts/<?= (int)($row['id'] ?? 0) ?>">Открыть результат</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
        <nav class="results-pagination">
            <?php
            $prevPage = max(1, $page - 1);
            $nextPage = min($pages, $page + 1);
            ?>
            <?php if ($page > 1): ?>
                <?php $q = $queryBase; $q['page'] = $prevPage; ?>
                <a class="btn btn--ghost" href="/my/results?<?= htmlspecialchars(http_build_query($q), ENT_QUOTES, 'UTF-8') ?>">Назад</a>
            <?php endif; ?>

            <span class="results-pagination__info">Страница <?= $page ?> из <?= $pages ?></span>

            <?php if ($page < $pages): ?>
                <?php $q = $queryBase; $q['page'] = $nextPage; ?>
                <a class="btn btn--ghost" href="/my/results?<?= htmlspecialchars(http_build_query($q), ENT_QUOTES, 'UTF-8') ?>">Вперёд</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
