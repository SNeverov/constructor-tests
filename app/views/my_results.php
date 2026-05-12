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
        'all' => 'Результат',
        'excellent' => 'Отлично',
        'good' => 'Хорошо',
        'satisfactory' => 'Удовлетворительно',
        'bad' => 'Плохо',
        'passed' => 'Сдано',
        'failed' => 'Не сдано',
    ];
    if (!array_key_exists($status, $statusOptions)) {
        $status = 'all';
    }

    $defaultCover = '/assets/img/cover/default_test_cover.webp';
    $formatFinishedAtLong = static function (string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '—';
        }

        $date = DateTime::createFromFormat('Y-m-d H:i:s', $value)
            ?: DateTime::createFromFormat('Y-m-d H:i', $value);
        if (!$date) {
            return $value;
        }

        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
        ];

        $month = $months[(int)$date->format('n')] ?? $date->format('m');
        return $date->format('d') . ' ' . $month . ' ' . $date->format('Y H:i');
    };
    $formatDuration = static function (int $seconds): string {
        if ($seconds <= 0) {
            return '—';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        if ($minutes > 0) {
            return $minutes . ' мин';
        }

        return $secs . ' сек';
    };
    $pluralRu = static function (int $count, string $one, string $few, string $many): string {
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return $one;
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $few;
        }
        return $many;
    };
?>

<div class="results-page" data-list-shell>
    <div class="page-head">
        <h1>Мои результаты</h1>
    </div>

    <form class="results-filters card" method="get" action="/my/results">
        <div class="results-filters__grid">
            <label class="results-filters__field results-filters__field--search">
                <input
                    type="text"
                    class="input"
                    name="search"
                    value="<?= htmlspecialchars((string)($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Название теста"
                    aria-label="Тест"
                >
            </label>

            <label class="results-filters__field results-filters__field--status">
                <div class="results-status" data-results-status-picker>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" data-results-status-input>
                    <button type="button" class="input results-status__trigger<?= $status === 'all' ? ' is-placeholder' : '' ?>" data-results-status-trigger aria-haspopup="listbox" aria-expanded="false" aria-label="Результат">
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

            <label class="results-filters__field results-filters__field--date">
                <div class="date-field">
                    <input
                        type="text"
                        class="input"
                        placeholder="с дд.мм.гггг"
                        aria-label="Дата с"
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

            <label class="results-filters__field results-filters__field--date">
                <div class="date-field">
                    <input
                        type="text"
                        class="input"
                        placeholder="по дд.мм.гггг"
                        aria-label="Дата по"
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

            <div class="results-filters__actions">
                <button type="submit" class="btn btn-primary btn-md btn-with-icon">
                    <svg class="btn__icon-img" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Применить
                </button>
                <a href="/my/results" class="btn btn-outline btn-md btn-with-icon">
                    <svg class="btn__icon-img" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                    Сбросить
                </a>
            </div>
        </div>
    </form>

    <div class="results-meta muted">Найдено: <?= $total ?></div>

    <?php if (empty($rows)): ?>
        <div class="empty-state" data-list-content>
            <div class="empty-state__card">
                <h3 class="empty-state__title">Результаты не найдены</h3>
                <p class="empty-state__text">Попробуй изменить фильтры или пройти тесты.</p>
                <div class="results-empty__actions">
                    <a href="/my/tests" class="btn btn-outline btn-md btn-with-icon">
                        <svg class="btn__icon-img" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Перейти к тестам
                    </a>
                    <a href="/my/results" class="btn btn-primary btn-md btn-with-icon">
                        <svg class="btn__icon-img" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        </svg>
                        Сбросить фильтры
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="results-list" data-list-content>
            <?php foreach ($rows as $row): ?>
                <?php
                $percent = (float)($row['percent'] ?? 0);
                $rateLabel = trim((string)($row['result_label_snapshot'] ?? ''));
                if ($rateLabel === '') {
                    $rateLabel = 'Плохо';
                }
                $rateClass = 'score-pill--bad';
                $itemToneClass = 'result-item--bad';
                if ($percent >= 90.0) {
                    $rateLabel = trim((string)($row['result_label_snapshot'] ?? '')) !== '' ? $rateLabel : 'Отлично';
                    $rateClass = 'score-pill--excellent';
                    $itemToneClass = 'result-item--excellent';
                } elseif ($percent >= 80.0) {
                    $rateLabel = trim((string)($row['result_label_snapshot'] ?? '')) !== '' ? $rateLabel : 'Хорошо';
                    $rateClass = 'score-pill--good';
                    $itemToneClass = 'result-item--good';
                } elseif ($percent >= 60.0) {
                    $rateLabel = trim((string)($row['result_label_snapshot'] ?? '')) !== '' ? $rateLabel : 'Удовлетворительно';
                    $rateClass = 'score-pill--ok';
                    $itemToneClass = 'result-item--ok';
                }
                if (mb_strtolower($rateLabel) === 'не сдано') {
                    $rateClass = 'score-pill--bad';
                    $itemToneClass = 'result-item--bad';
                }
                $barWidth = max(0.0, min(100.0, $percent));
                $percentText = rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.');

                $testTitle = trim((string)($row['live_test_title'] ?? ''));
                if ($testTitle === '') {
                    $testTitle = trim((string)($row['test_title_snapshot'] ?? ''));
                }
                if ($testTitle === '') {
                    $testTitle = 'Тест';
                }

                $testIdRaw = $row['test_id'] ?? null;
                $testId = ($testIdRaw === null || $testIdRaw === '') ? 0 : (int)$testIdRaw;
                $attemptNo = max(1, (int)($row['attempt_no'] ?? 1));
                $finishedAt = $formatFinishedAtLong((string)($row['finished_at'] ?? ''));
                $coverSrc = trim((string)($row['live_cover_image'] ?? '')) !== '' ? (string)$row['live_cover_image'] : $defaultCover;
                $durationText = $formatDuration((int)($row['duration_sec'] ?? 0));
                $questionsCount = (int)($row['total_questions'] ?? 0);
                ?>
                <article class="card result-item <?= $itemToneClass ?>">
                    <div class="result-item__cover">
                        <img
                            src="<?= htmlspecialchars($coverSrc, ENT_QUOTES, 'UTF-8') ?>"
                            alt=""
                            class="result-item__cover-img"
                            loading="lazy"
                            decoding="async"
                        >
                        <div class="result-item__cover-shade" aria-hidden="true"></div>
                        <div class="result-item__badges">
                            <span class="result-badge result-badge--attempt">Попытка #<?= $attemptNo ?></span>
                            <span class="result-badge result-badge--date">
                                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true"><rect x="1" y="1.5" width="9" height="8.5" rx="1.5" stroke="currentColor" stroke-width="1.1"></rect><path d="M1 4.5h9M3.5 1v1.5M7.5 1v1.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path></svg>
                                <?= htmlspecialchars($finishedAt, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <h2 class="result-item__title"><?= htmlspecialchars($testTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>

                    <div class="result-item__body">
                        <div class="result-progress">
                            <div class="result-progress__head">
                                <strong><?= htmlspecialchars($percentText, ENT_QUOTES, 'UTF-8') ?>%</strong>
                                <span class="result-badge result-badge--score <?= $rateClass ?>"><?= htmlspecialchars($rateLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="result-progress__track" aria-hidden="true">
                                <div class="result-progress__fill" style="width: <?= $barWidth ?>%"></div>
                            </div>
                        </div>

                        <div class="result-stats" aria-label="Краткая статистика">
                            <div class="result-stat">
                                <span class="result-stat__icon result-stat__icon--questions" aria-hidden="true">?</span>
                                <strong><?= $questionsCount ?></strong>
                                <span><?= htmlspecialchars($pluralRu($questionsCount, 'вопрос', 'вопроса', 'вопросов'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="result-stat">
                                <span class="result-stat__icon result-stat__icon--correct" aria-hidden="true"></span>
                                <strong><?= (int)($row['correct_count'] ?? 0) ?></strong>
                                <span>Правильно</span>
                            </div>
                            <div class="result-stat">
                                <span class="result-stat__icon result-stat__icon--wrong" aria-hidden="true"></span>
                                <strong><?= (int)($row['wrong_count'] ?? 0) ?></strong>
                                <span>Неправильно</span>
                            </div>
                            <div class="result-stat">
                                <span class="result-stat__icon result-stat__icon--time" aria-hidden="true"></span>
                                <strong><?= htmlspecialchars($durationText, ENT_QUOTES, 'UTF-8') ?></strong>
                                <!-- <span>Время</span> -->
                            </div>
                        </div>

                        <div class="result-card-footer">
                            <a class="btn btn-primary btn-md btn-with-icon result-item__button" href="/attempts/<?= (int)($row['id'] ?? 0) ?>">
                                <span>Подробнее</span>
                                <svg class="btn__icon-img" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <div class="result-tech">
                                ID: <?= (int)($row['id'] ?? 0) ?> · Тест: <?= $testId > 0 ? $testId : 'удалён' ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
        <?php
        $prevQuery = $queryBase;
        $prevQuery['page'] = max(1, $page - 1);
        $nextQuery = $queryBase;
        $nextQuery['page'] = min($pages, $page + 1);
        $pagerPage = $page;
        $pagerPages = $pages;
        $pagerPrevUrl = '/my/results?' . http_build_query($prevQuery);
        $pagerNextUrl = '/my/results?' . http_build_query($nextQuery);
        $pagerLabel = 'Пагинация результатов';
        require __DIR__ . '/partials/pager.php';
        ?>
    <?php endif; ?>

    <div class="list-loading" data-list-loading hidden aria-hidden="true">
        <article class="card result-item skeleton-card"></article>
        <article class="card result-item skeleton-card"></article>
        <article class="card result-item skeleton-card"></article>
    </div>
</div>
