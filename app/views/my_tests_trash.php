<?php
/** @var array $tests */
/** @var array $pagination */

$page = (int)($pagination['page'] ?? 1);
$pages = (int)($pagination['pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);
?>

<div class="page-head page-head--row">
    <h1>Корзина</h1>

    <div class="page-head__actions">
        <a href="/my/tests" class="btn btn--ghost">← Мои тесты</a>

        <?php if (!empty($tests)): ?>
            <?= form_open('/my/tests/trash/restore-all', 'post', [
                'class' => 'inline',
                'data-confirm' => '1',
                'data-confirm-title' => 'Восстановить всё?',
                'data-confirm-text' => 'Восстановить все тесты из корзины?',
                'data-confirm-ok' => 'Восстановить',
            ]) ?>
                <button type="submit" class="btn">Восстановить все</button>
            </form>

            <?= form_open('/my/tests/trash/empty', 'post', [
                'class' => 'inline',
                'data-confirm' => '1',
                'data-confirm-title' => 'Очистить корзину?',
                'data-confirm-text' => 'Удалить все тесты из корзины навсегда? Это нельзя отменить.',
                'data-confirm-ok' => 'Удалить навсегда',
            ]) ?>
                <button type="submit" class="btn btn--danger">Очистить корзину</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($tests)): ?>
    <div class="results-meta muted">Найдено в корзине: <?= $total ?></div>
<?php endif; ?>

<?php if (empty($tests)): ?>
    <div class="empty-state">
        <div class="empty-state__card">
            <div class="empty-state__icon"></div>

            <h3 class="empty-state__title">
                Корзина пуста
            </h3>

            <p class="empty-state__text">
                Здесь будут тесты, которые ты отправишь в корзину. Их можно восстановить или удалить навсегда.
            </p>

            <a href="/my/tests" class="btn btn--primary">
                Вернуться к моим тестам
            </a>
        </div>
    </div>
<?php else: ?>

    <?php foreach ($tests as $test): ?>
        <div class="card test-card test-card--trash">

            <div class="test-meta">
                <span class="badge badge--warn">
                    В корзине
                </span>

                <?php if (!empty($test['deleted_at'])): ?>
                    <span class="badge">
                        <?= htmlspecialchars((string)$test['deleted_at'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="test-title-link">
                <?= htmlspecialchars((string)$test['title'], ENT_QUOTES, 'UTF-8') ?>
            </div>

            <p class="test-description">
                <?= htmlspecialchars((string)$test['description'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <div class="test-actions">
                <?= form_open('/my/tests/' . (int)$test['id'] . '/restore', 'post', ['class' => 'inline']) ?>
                    <button type="submit" class="btn">Восстановить</button>
                </form>

                <?= form_open('/my/tests/' . (int)$test['id'] . '/destroy', 'post', [
                    'class' => 'inline',
                    'data-confirm' => '1',
                    'data-confirm-title' => 'Удалить навсегда?',
                    'data-confirm-text' => 'Удалить этот тест навсегда? Это действие нельзя отменить.',
                    'data-confirm-ok' => 'Удалить навсегда',
                ]) ?>
                    <button type="submit" class="btn btn--danger">Удалить навсегда</button>
                </form>
            </div>

        </div>
    <?php endforeach; ?>

    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Пагинация корзины">
            <?php $prevPage = max(1, $page - 1); ?>
            <?php $nextPage = min($pages, $page + 1); ?>

            <?php if ($page > 1): ?>
                <a class="pager__btn" href="/my/tests/trash?page=<?= $prevPage ?>" aria-label="Предыдущая страница">
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
                <a class="pager__btn" href="/my/tests/trash?page=<?= $nextPage ?>" aria-label="Следующая страница">
                    <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                </a>
            <?php else: ?>
                <span class="pager__btn is-disabled" aria-hidden="true">
                    <img src="/assets/img/next-page.svg" class="pager__arrow" alt="" aria-hidden="true">
                </span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

<?php endif; ?>
