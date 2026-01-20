<?php
/** @var array $tests */
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

<?php endif; ?>
