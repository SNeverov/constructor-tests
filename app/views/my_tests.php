<?php
/** @var array $tests */
?>

<h2>Мои тесты</h2>

<?php if (empty($tests)): ?>
    <p>Вы ещё не создали ни одного теста</p>
    <p><a class="btn" href="/my/tests/create">Создать тест</a></p>
<?php else: ?>

    <?php foreach ($tests as $test): ?>
        <div class="card test-card">

            <div class="test-meta">
                ID: <?= (int)$test['id'] ?> - <?= $test['access_level'] === 'public' ? 'Доступен всем' : 'Только для зарегистрированных'?>
            </div>

            <h3 class="test-title"><?= htmlspecialchars($test['title']) ?></h3>

            <p class="test-description"><?= htmlspecialchars($test['description']) ?></p>

            <div class="test-actions">
                <a href="/my/tests/<?= (int)$test['id'] ?>/edit" class="btn">Редактировать</a>

                <?= form_open('/my/tests/' . (int) $test['id'] . '/delete', 'post', ['class' => 'inline-form']) ?>
                    <button type="submit" class="btn btn--danger">Удалить</button>
                </form>
            </div>
        </div>

    <?php endforeach; ?>

<?php endif; ?>
