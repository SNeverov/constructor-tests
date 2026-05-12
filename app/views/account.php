<?php
declare(strict_types=1);

/** @var array $profile */
/** @var array $stats */

$login = trim((string)($profile['login'] ?? ''));
$email = trim((string)($profile['email'] ?? ''));
$createdAt = trim((string)($profile['created_at'] ?? ''));
$registeredAt = $createdAt;
if ($createdAt !== '') {
    try {
        $registeredAt = (new DateTimeImmutable($createdAt))->format('d.m.Y H:i');
    } catch (Throwable $e) {
        $registeredAt = $createdAt;
    }
}
$avatarInitial = mb_strtoupper(mb_substr($login !== '' ? $login : $email, 0, 1));
$publicProfileUrl = $login !== '' ? ('/u/' . rawurlencode($login)) : '';
?>

<div class="account">
    <div class="account__hero">
        <div class="account__identity">
            <div class="account__avatar" aria-hidden="true">
                <?= htmlspecialchars($avatarInitial !== '' ? $avatarInitial : 'U', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="account__intro">
                <div class="account__eyebrow">Профиль</div>
                <h1><?= htmlspecialchars($login !== '' ? $login : 'Пользователь', ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="account__email"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($publicProfileUrl !== ''): ?>
                    <div class="account__public-actions">
                        <a class="account__public-link" href="<?= htmlspecialchars($publicProfileUrl, ENT_QUOTES, 'UTF-8') ?>">
                            Открыть публичный профиль
                        </a>
                        <button
                            type="button"
                            class="account__public-link account__public-link--copy"
                            data-copy="<?= htmlspecialchars($publicProfileUrl, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <span data-copy-label>Скопировать ссылку</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="account__actions" aria-label="Быстрые разделы">
            <a href="/my/tests" class="account-action">
                <span class="account-action__label">Мои тесты</span>
                <span class="account-action__value"><?= (int)($stats['tests_active'] ?? 0) ?></span>
            </a>
            <a href="/my/results" class="account-action account-action--primary">
                <span class="account-action__label">Результаты</span>
                <span class="account-action__value"><?= (int)($stats['results_total'] ?? 0) ?></span>
            </a>
            <a href="/my/tests/trash" class="account-action">
                <span class="account-action__label">Корзина</span>
                <span class="account-action__value"><?= (int)($stats['tests_deleted'] ?? 0) ?></span>
            </a>
        </div>
    </div>

    <div class="account__grid">
        <section class="account-panel account-panel--details">
            <div class="account-panel__head">
                <h2 class="account__title">Данные аккаунта</h2>
            </div>
            <div class="account__rows">
                <div class="account__row">
                    <span class="account__label">ID</span>
                    <span class="account__value"><?= (int)($profile['id'] ?? 0) ?></span>
                </div>
                <div class="account__row">
                    <span class="account__label">Логин</span>
                    <span class="account__value"><?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="account__row">
                    <span class="account__label">Email</span>
                    <span class="account__value"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="account__row">
                    <span class="account__label">Регистрация</span>
                    <span class="account__value"><?= htmlspecialchars($registeredAt, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </section>

        <section class="account-panel account-panel--stats">
            <div class="account-panel__head">
                <h2 class="account__title">Статистика</h2>
            </div>
            <div class="account__stats">
                <div class="account-stat account-stat--tests">
                    <div class="sum__label">Активных тестов</div>
                    <div class="sum__value"><?= (int)($stats['tests_active'] ?? 0) ?></div>
                </div>
                <div class="account-stat account-stat--trash">
                    <div class="sum__label">В корзине</div>
                    <div class="sum__value"><?= (int)($stats['tests_deleted'] ?? 0) ?></div>
                </div>
                <div class="account-stat account-stat--results">
                    <div class="sum__label">Результатов</div>
                    <div class="sum__value"><?= (int)($stats['results_total'] ?? 0) ?></div>
                </div>
                <div class="account-stat account-stat--avg">
                    <div class="sum__label">Средний процент</div>
                    <div class="sum__value"><?= (float)($stats['avg_percent'] ?? 0) ?>%</div>
                </div>
            </div>
        </section>
    </div>
</div>
