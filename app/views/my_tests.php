<?php
/** @var array $tests */
?>

<div class="page-head">
    <h1>Мои тесты</h1>
</div>

<?php if (empty($tests)): ?>
    <p>Вы ещё не создали ни одного теста</p>
    <p><a class="btn" href="/my/tests/create">Создать тест</a></p>
<?php else: ?>

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


            <p class="test-description"><?= htmlspecialchars($test['description']) ?></p>

            <div class="test-actions">
				<a class="btn btn--ghost" href="/tests/<?= (int)$test['id'] ?>">Пройти тест</a>
                <a href="/my/tests/<?= (int)$test['id'] ?>/edit" class="btn">Редактировать</a>

                <?= form_open('/my/tests/' . (int) $test['id'] . '/delete', 'post', ['class' => 'inline-form']) ?>
                    <button type="submit" class="btn btn--danger">Удалить</button>
                </form>
            </div>
        </div>

    <?php endforeach; ?>

<?php endif; ?>
