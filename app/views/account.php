<?php
declare(strict_types=1);

/** @var array $profile */
/** @var array $stats */
?>

<div class="account">
    <div class="page-head">
        <h1>Профиль</h1>
    </div>

    <div class="account__grid">
        <section class="card account__card">
            <h2 class="account__title">Данные аккаунта</h2>
            <div class="account__rows">
                <div class="account__row">
                    <span class="account__label">ID</span>
                    <span class="account__value"><?= (int)($profile['id'] ?? 0) ?></span>
                </div>
                <div class="account__row">
                    <span class="account__label">Логин</span>
                    <span class="account__value"><?= htmlspecialchars((string)($profile['login'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="account__row">
                    <span class="account__label">Email</span>
                    <span class="account__value"><?= htmlspecialchars((string)($profile['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="account__row">
                    <span class="account__label">Регистрация</span>
                    <span class="account__value"><?= htmlspecialchars((string)($profile['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </section>

        <section class="card account__card">
            <h2 class="account__title">Статистика</h2>
            <div class="account__stats">
                <div class="sum">
                    <div class="sum__label">Активных тестов</div>
                    <div class="sum__value"><?= (int)($stats['tests_active'] ?? 0) ?></div>
                </div>
                <div class="sum">
                    <div class="sum__label">В корзине</div>
                    <div class="sum__value"><?= (int)($stats['tests_deleted'] ?? 0) ?></div>
                </div>
                <div class="sum">
                    <div class="sum__label">Результатов</div>
                    <div class="sum__value"><?= (int)($stats['results_total'] ?? 0) ?></div>
                </div>
                <div class="sum">
                    <div class="sum__label">Средний процент</div>
                    <div class="sum__value"><?= (float)($stats['avg_percent'] ?? 0) ?>%</div>
                </div>
            </div>
        </section>
    </div>

    <div class="account__actions">
        <a href="/my/tests" class="btn btn--ghost">Мои тесты</a>
        <a href="/my/results" class="btn btn--primary">Мои результаты</a>
        <a href="/my/tests/trash" class="btn btn--ghost">Корзина</a>
    </div>
</div>
