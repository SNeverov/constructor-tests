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
    $status = (string)($filters['status'] ?? 'all');
    $statusOptions = [
        'all' => 'Все',
        'excellent' => 'Отлично',
        'good' => 'Хорошо',
        'satisfactory' => 'Удовлетворительно',
        'bad' => 'Плохо',
    ];
    if (!array_key_exists($status, $statusOptions)) {
        $status = 'all';
    }
?>

<div class="results-page" data-list-shell>
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
                <div class="results-status" data-results-status-picker>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" data-results-status-input>
                    <button type="button" class="input results-status__trigger" data-results-status-trigger aria-haspopup="listbox" aria-expanded="false">
                        <span class="results-status__value" data-results-status-current><?= htmlspecialchars($statusOptions[$status], ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="results-status__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="results-status__panel" data-results-status-panel hidden>
                        <div class="results-status__list" role="listbox" aria-label="Статус результата">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <button
                                    type="button"
                                    class="results-status__option<?= $status === $value ? ' is-selected' : '' ?>"
                                    data-results-status-option
                                    data-value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
                                    role="option"
                                    aria-selected="<?= $status === $value ? 'true' : 'false' ?>"
                                >
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </label>

            <label class="results-filters__field">
                <span>Дата с</span>
                <div class="date-field">
                    <input
                        type="text"
                        class="input"
                        placeholder="дд.мм.гггг"
                        autocomplete="off"
                        readonly
                        data-date-input
                    >
                    <input
                        type="hidden"
                        name="date_from"
                        data-date-hidden
                        value="<?= htmlspecialchars((string)($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <button type="button" class="date-field__btn ui-tooltip" data-tooltip="Открыть календарь" data-date-open aria-label="Открыть календарь">
                        <span class="date-field__icon" aria-hidden="true"></span>
                    </button>
                </div>
            </label>

            <label class="results-filters__field">
                <span>Дата по</span>
                <div class="date-field">
                    <input
                        type="text"
                        class="input"
                        placeholder="дд.мм.гггг"
                        autocomplete="off"
                        readonly
                        data-date-input
                    >
                    <input
                        type="hidden"
                        name="date_to"
                        data-date-hidden
                        value="<?= htmlspecialchars((string)($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <button type="button" class="date-field__btn ui-tooltip" data-tooltip="Открыть календарь" data-date-open aria-label="Открыть календарь">
                        <span class="date-field__icon" aria-hidden="true"></span>
                    </button>
                </div>
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
        <div class="empty-state" data-list-content>
            <div class="empty-state__card">
                <h3 class="empty-state__title">Результаты не найдены</h3>
                <p class="empty-state__text">Попробуй изменить фильтры или пройти тесты.</p>
                <div class="results-empty__actions">
                    <a href="/my/tests" class="btn btn--ghost">Перейти к тестам</a>
                    <a href="/my/results" class="btn btn--primary">Сбросить фильтры</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="results-list" data-list-content>
            <?php foreach ($rows as $row): ?>
                <?php
                $percent = (float)($row['percent'] ?? 0);
                $rateLabel = 'Плохо';
                $rateClass = 'score-pill--bad';
                $itemToneClass = 'result-item--bad';
                if ($percent >= 90.0) {
                    $rateLabel = 'Отлично';
                    $rateClass = 'score-pill--excellent';
                    $itemToneClass = 'result-item--excellent';
                } elseif ($percent >= 80.0) {
                    $rateLabel = 'Хорошо';
                    $rateClass = 'score-pill--good';
                    $itemToneClass = 'result-item--good';
                } elseif ($percent >= 60.0) {
                    $rateLabel = 'Удовлетворительно';
                    $rateClass = 'score-pill--ok';
                    $itemToneClass = 'result-item--ok';
                }
                $barWidth = max(0.0, min(100.0, $percent));

                $testTitle = trim((string)($row['live_test_title'] ?? ''));
                if ($testTitle === '') {
                    $testTitle = trim((string)($row['test_title_snapshot'] ?? ''));
                }
                if ($testTitle === '') {
                    $testTitle = 'Тест';
                }

                $testIdRaw = $row['test_id'] ?? null;
                $testId = ($testIdRaw === null || $testIdRaw === '') ? 0 : (int)$testIdRaw;
                ?>
                <article class="card result-item <?= $itemToneClass ?>">
                    <div class="result-item__layout">
                        <div class="result-item__main">
                            <div class="result-item__title">
                                <?= htmlspecialchars($testTitle, ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <div class="result-item__meta">
                                <span class="meta-pill">Результат ID: <?= (int)($row['id'] ?? 0) ?></span>
                                <span class="meta-pill">
                                    <?php if ($testId > 0): ?>
                                        Тест ID: <?= $testId ?>
                                    <?php else: ?>
                                        Тест ID: удалён
                                    <?php endif; ?>
                                </span>
                                <span class="meta-pill">Дата: <?= htmlspecialchars((string)($row['finished_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="result-item__actions">
                                <a class="btn btn--ghost" href="/attempts/<?= (int)($row['id'] ?? 0) ?>">Открыть результат</a>
                            </div>
                        </div>

                        <div class="result-item__score">
                            <div class="score-pill <?= $rateClass ?>" aria-label="Процент результата">
                                <div class="score-pill__track" aria-hidden="true">
                                    <div class="score-pill__fill" style="width: <?= $barWidth ?>%"></div>
                                </div>
                                <div class="score-pill__text">
                                    <span class="score-pill__percent"><?= $percent ?>%</span>
                                    <span class="score-pill__label"><?= $rateLabel ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Пагинация результатов">
            <?php
            $prevPage = max(1, $page - 1);
            $nextPage = min($pages, $page + 1);
            ?>
            <?php if ($page > 1): ?>
                <?php $q = $queryBase; $q['page'] = $prevPage; ?>
                <a class="pager__btn" href="/my/results?<?= htmlspecialchars(http_build_query($q), ENT_QUOTES, 'UTF-8') ?>" aria-label="Предыдущая страница">
                    <img src="/assets/img/next-page.svg" class="pager__arrow pager__arrow--prev" alt="" aria-hidden="true">
                </a>
            <?php else: ?>
                <span class="pager__btn is-disabled" aria-hidden="true">
                    <img src="/assets/img/next-page.svg" class="pager__arrow pager__arrow--prev" alt="" aria-hidden="true">
                </span>
            <?php endif; ?>

            <span class="pager__info">
                Страница <strong><?= $page ?></strong> из <strong><?= $pages ?></strong>
            </span>

            <?php if ($page < $pages): ?>
                <?php $q = $queryBase; $q['page'] = $nextPage; ?>
                <a class="pager__btn" href="/my/results?<?= htmlspecialchars(http_build_query($q), ENT_QUOTES, 'UTF-8') ?>" aria-label="Следующая страница">
                    <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                </a>
            <?php else: ?>
                <span class="pager__btn is-disabled" aria-hidden="true">
                    <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                </span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <div class="list-loading" data-list-loading hidden aria-hidden="true">
        <article class="card result-item skeleton-card"></article>
        <article class="card result-item skeleton-card"></article>
        <article class="card result-item skeleton-card"></article>
    </div>
</div>
