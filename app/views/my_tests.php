<?php
/** @var array $tests */
/** @var array $pagination */

$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);
?>

<div class="my-tests-page" data-list-shell>
    <div class="page-head">
        <h1>Мои тесты</h1>
    </div>

    <?php if (!empty($tests)): ?>
        <div class="results-meta muted">Найдено: <?= $total ?></div>
    <?php endif; ?>

    <?php if (empty($tests)): ?>
        <div class="empty-state" data-list-content>
            <div class="empty-state__card">
                <div class="empty-state__icon"></div>

                <h3 class="empty-state__title">
                    У вас пока нет тестов
                </h3>

                <p class="empty-state__text">
                    Здесь будут отображаться все тесты, которые вы создадите.
                    Вы сможете редактировать их, удалять и смотреть результаты прохождения.
                </p>

                <a href="/my/tests/create" class="btn btn--primary">
                    Создать первый тест
                </a>
            </div>
        </div>
    <?php else: ?>

        <div data-list-content>
            <?php foreach ($tests as $test): ?>
                <div class="card test-card">

                    <div class="test-meta">

                        <button
                            type="button"
                            class="badge badge--copy badge--copy-link"
                            data-copy="/tests/<?= (int)$test['id'] ?>"
                            title="Скопировать ссылку на тест"
                        >
                            <img
                                src="/assets/img/link-svgrepo-com.svg"
                                alt=""
                                class="badge__icon"
                                aria-hidden="true"
                            >
                            <span data-copy-label>ID: <?= (int)$test['id'] ?></span>
                        </button>

                        <span class="badge <?= ($test['access_level'] === 'public') ? 'badge--ok' : 'badge--warn' ?>">
                            <?= ($test['access_level'] === 'public') ? 'Доступен всем' : 'Только для зарегистрированных' ?>
                        </span>

                    </div>

                    <a class="test-title-link" href="/tests/<?= (int)$test['id'] ?>">
                        <?= htmlspecialchars((string)$test['title'], ENT_QUOTES, 'UTF-8') ?>
                    </a>

                    <p class="test-description">
                        <?= htmlspecialchars((string)$test['description'], ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <div class="test-actions">
                        <a class="btn btn--ghost" href="/tests/<?= (int)$test['id'] ?>">Пройти тест</a>
                        <a href="/my/tests/<?= (int)$test['id'] ?>/edit" class="btn">Редактировать</a>

                        <?= form_open('/my/tests/' . (int)$test['id'] . '/delete', 'post', [
                            'class' => 'inline',
                            'data-confirm' => '1',
                            'data-confirm-title' => 'Отправить в корзину?',
                            'data-confirm-text' => 'Убрать этот тест в корзину? Его можно будет восстановить.',
                            'data-confirm-ok' => 'В корзину',
                        ]) ?>
                            <button type="submit" class="btn btn--danger">В корзину</button>
                        </form>

                    </div>

                </div>
            <?php endforeach; ?>

            <?php if ($pages > 1): ?>
                <nav class="pager" aria-label="Пагинация тестов">
                    <?php $prevPage = max(1, $page - 1); ?>
                    <?php $nextPage = min($pages, $page + 1); ?>

                    <?php if ($page > 1): ?>
                        <a class="pager__btn" href="/my/tests?page=<?= $prevPage ?>" aria-label="Предыдущая страница">
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
                        <a class="pager__btn" href="/my/tests?page=<?= $nextPage ?>" aria-label="Следующая страница">
                            <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                        </a>
                    <?php else: ?>
                        <span class="pager__btn is-disabled" aria-hidden="true">
                            <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                        </span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <div class="list-loading" data-list-loading hidden aria-hidden="true">
        <div class="card test-card skeleton-card"></div>
        <div class="card test-card skeleton-card"></div>
        <div class="card test-card skeleton-card"></div>
    </div>
</div>
