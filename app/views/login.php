<div class="auth">

	<div class="auth-card">
		<h1 class="auth-title">Вход</h1>
		<p class="auth-subtitle">Войдите, чтобы проходить и создавать тесты, и смотреть результаты.</p>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<div class="form-errors__title">Не получилось войти</div>
				<ul>
					<?php foreach ($errors as $e): ?>
						<li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>



		<?= form_open('/login') ?>
			<div class="form-row">
				<label class="form-label" for="identity">Логин или email</label>
				<input class="input" id="identity" type="text" name="identity" value="<?= htmlspecialchars(
					(string) ($old['identity'] ?? ''),
				) ?>">
			</div>

			<div class="form-row">
				<label class="form-label" for="password">Пароль</label>
				<input class="input" id="password" type="password" name="password">
			</div>

			<label class="remember-check">
				<input
					class="remember-check__input"
					type="checkbox"
					name="remember_me"
					value="1"
					<?= !empty($old['remember_me']) ? 'checked' : '' ?>
				>
				<span class="remember-check__box" aria-hidden="true"></span>
				<span class="remember-check__text">Запомнить меня</span>
			</label>

			<div class="auth-actions">
				<button class="btn btn-primary btn-md btn-with-icon" type="submit">
					<svg class="btn__icon-img" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
					Войти
				</button>

				<div class="auth-hint">
					Нет аккаунта? <a href="/register">Регистрация</a>
				</div>
			</div>


		</form>
	</div>
</div>
