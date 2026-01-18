<?php
/** @var array $tests */
?>

<div class="page-head">
    <h1>Мои тесты</h1>
</div>

<?php if (empty($tests)): ?>
    <div class="empty-state">
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
<?php endif; ?>


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
