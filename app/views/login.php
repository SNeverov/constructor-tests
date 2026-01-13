<h1><?= htmlspecialchars($title ?? 'Вход') ?></h1>

<?php if (!empty($errors)): ?>
    <div style="color:red; margin:10px 0;">
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="/login">
    <div style="margin-bottom: 10px;">
        <label>
            Логин или email<br>
            <input type="text" name="identity" value="<?= htmlspecialchars(
                (string) ($old['identity'] ?? ''),
            ) ?>">
        </label>
    </div>

    <div style="margin-bottom: 10px;">
        <label>
            Пароль<br>
            <input type="password" name="password">
        </label>
    </div>

    <button type="submit">Войти</button>
</form>
