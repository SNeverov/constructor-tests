<?php
/** @var array $tests */
?>

<h2>Мои тесты</h2>

<?php if (empty($tests)): ?>
    <p>Вы ещё не создали ни одного теста</p>
    <p><a class="btn" href="/my/tests/create">Создать тест</a></p>
<?php else: ?>

    <?php foreach ($tests as $test): ?>
        <div style="border:1px solid #ddd; padding:12px; margin:12px 0; border-radius:8px;">
            <div style="font-size:12px; color:#666;">
                ID: <?= (int) $test['id'] ?> · Доступ:
                <?= $test['access_level'] === 'registered'
                    ? 'Только для зарегистрированных'
                    : 'Доступен всем' ?>
            </div>

            <h3 style="margin:8px 0;">
                <?= htmlspecialchars($test['title'], ENT_QUOTES, 'UTF-8') ?>
            </h3>

            <?php if (!empty($test['description'])): ?>
                <p style="margin:8px 0;">
                    <?= nl2br(htmlspecialchars($test['description'], ENT_QUOTES, 'UTF-8')) ?>
                </p>
            <?php endif; ?>

            <div style="margin-top:10px;">
                <a href="/my/tests/<?= (int) $test['id'] ?>/edit">Редактировать</a>
                <form method="post" action="/my/tests/<?= (int) $test[
                    'id'
                ] ?>/delete" style="display:inline">
                    <button type="submit" onclick="return confirm('Удалить тест?')">Удалить</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
