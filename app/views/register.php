<div class="auth">
    <div class="auth-card">
        <h1 class="auth-title">Регистрация</h1>
        <p class="auth-subtitle">Создай аккаунт, чтобы создавать тесты и сохранять результаты.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error">
                <ul class="alert__list">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?= form_open('/register') ?>

            <div class="form-row">
                <label class="form-label" for="login">Логин</label>
                <input
                    id="login"
                    class="input"
                    type="text"
                    name="login"
                    value="<?= htmlspecialchars((string)($old['login'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label class="form-label" for="email">Email</label>
                <input
                    id="email"
                    class="input"
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label class="form-label" for="password">Пароль</label>
                <input
                    id="password"
                    class="input"
                    type="password"
                    name="password"
                    required
                >
            </div>

            <div class="auth-actions">
                <button class="btn btn--primary" type="submit">Зарегистрироваться</button>

                <div class="auth-hint">
                    Уже есть аккаунт? <a href="/login">Войти</a>
                </div>
            </div>

        </form>
    </div>
</div>
