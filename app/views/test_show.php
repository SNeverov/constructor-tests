<?php
declare(strict_types=1);

/** @var array|null $test */
/** @var int $questions_count */
/** @var int $rating_count */
/** @var float $rating_avg */
/** @var int|null $user_rating */
/** @var bool $can_rate */
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

        <div
            class="test-show__rating"
            data-rating-block
            data-rating-current="<?= (int)($user_rating ?? 0) ?>"
            data-rating-avg="<?= number_format((float)($rating_avg ?? 0), 2, '.', '') ?>"
            data-rating-count="<?= (int)($rating_count ?? 0) ?>"
        >
            <div class="test-show__rating-head">Оценка теста</div>
            <div class="test-show__rating-stars-wrap">
                <?php if (auth_is_logged_in() && $can_rate): ?>
                    <?= form_open('/tests/' . (int)($test['id'] ?? 0) . '/rate', 'post', [
                        'class' => 'test-show__rating-form',
                        'data-rating-form' => '1',
                    ]) ?>
                        <div class="test-show__rating-stars" role="radiogroup" aria-label="Поставить оценку тесту">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button
                                    type="submit"
                                    name="rating"
                                    value="<?= $i ?>"
                                    class="test-show__star-btn<?= (($user_rating ?? 0) >= $i) ? ' is-active' : '' ?>"
                                    data-rating-value="<?= $i ?>"
                                    aria-label="Оценка <?= $i ?> из 5"
                                >★</button>
                            <?php endfor; ?>
                        </div>
                    </form>
                <?php elseif (auth_is_logged_in() && !$can_rate): ?>
                    <div class="test-show__rating-lock">
                        <div class="test-show__rating-stars-readonly" aria-label="Средняя оценка теста">
                            <div class="test-show__rating-stars-base" aria-hidden="true">★★★★★</div>
                            <div class="test-show__rating-stars-fill" style="width: <?= (float)((($rating_avg ?? 0) / 5) * 100) ?>%;" aria-hidden="true">★★★★★</div>
                        </div>
                        <div class="test-show__rating-hint">Чтобы оценить тест, сначала пройдите его.</div>
                    </div>
                <?php else: ?>
                    <div class="test-show__rating-stars-readonly" aria-label="Средняя оценка теста">
                        <div class="test-show__rating-stars-base" aria-hidden="true">★★★★★</div>
                        <div class="test-show__rating-stars-fill" style="width: <?= (float)((($rating_avg ?? 0) / 5) * 100) ?>%;" aria-hidden="true">★★★★★</div>
                    </div>
                    <div class="test-show__rating-login-note">Оценку могут ставить только авторизованные пользователи</div>
                <?php endif; ?>

                <div class="test-show__rating-text">
                    <span data-rating-avg-text><?= number_format((float)($rating_avg ?? 0), 1, '.', '') ?></span>
                    <span>/ 5</span>
                    <span class="test-show__rating-sep">·</span>
                    <span data-rating-count-text><?= (int)($rating_count ?? 0) ?></span>
                    <span>оценок</span>
                    <?php if (auth_is_logged_in() && $can_rate): ?>
                        <span class="test-show__rating-sep">·</span>
                        <span>ваша: <strong data-rating-user-text><?= (int)($user_rating ?? 0) ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="test-show__actions">
            <a class="btn btn--primary" href="/tests/<?= (int)($test['id'] ?? 0) ?>/pass">Начать тест</a>
            <a class="btn btn--ghost" href="/">К списку тестов</a>
        </div>
    </div>
</div>
