<div class="auth">

	<div class="auth-card">
		<h1 class="auth-title">Вход</h1>
		<p class="auth-subtitle">Войдите, чтобы проходить и создавать тесты, и смотреть результаты.</p>

		<?php if (!empty($errors)): ?>
			<div class="alert alert--error">
				<ul class="alert__list">
					<?php foreach ($errors as $e): ?>
						<li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>


		<?= form_open('/login') ?>
			<div class="form-row">
				<label class="form-label" for="identity">Логин или email</label>
				<input class="input" type="text" name="identity" value="<?= htmlspecialchars(
					(string) ($old['identity'] ?? ''),
				) ?>">
			</div>

			<div class="form-row">
				<label class="form-label" for="password">Пароль</label>
				<input class="input" type="password" name="password">
			</div>

			<div class="auth-actions">
				<button class="btn btn--primary" type="submit">Войти</button>

				<div class="auth-hint">
					Нет аккаунта? <a href="/register">Регистрация</a>
				</div>
			</div>


		</form>
	</div>
</div>
