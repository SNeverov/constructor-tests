<?php
declare(strict_types=1);

/** @var array|null $test */
/** @var int $questions_count */
?>

<div class="test-show">
    <div class="test-show__card">
        <div class="test-show__top">
            <div class="test-show__meta">
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

            <h1 class="test-show__title"><?= htmlspecialchars((string)($test['title'] ?? 'Тест'), ENT_QUOTES, 'UTF-8') ?></h1>

            <?php $desc = trim((string)($test['description'] ?? '')); ?>
            <?php if ($desc !== ''): ?>
                <div class="test-show__desc">
                    <?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="test-show__info">
            <div class="info-row">
                <span class="info-row__label">Вопросов</span>
                <span class="info-row__value"><?= (int)$questions_count ?></span>
            </div>
            <div class="info-row">
                <span class="info-row__label">Подсказка</span>
                <span class="info-row__value">Во время прохождения правильность не показывается</span>
            </div>
        </div>

        <div class="test-show__actions">
            <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">Начать тест</a>
            <a class="btn btn--ghost" href="/">К списку тестов</a>
        </div>
    </div>
</div>
