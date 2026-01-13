<h2>Регистрация</h2>

<?php if (!empty($errors)): ?>
  <div style="border:1px solid #d33; padding:12px; margin:12px 0;">
    <b>Ошибки:</b>
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="/register">
  <div>
    <label>Логин</label><br>
    <input type="text" name="login" value="<?= htmlspecialchars(
        $old['login'] ?? '',
        ENT_QUOTES,
        'UTF-8',
    ) ?>" required>
  </div>
  <br>

  <div>
    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars(
        $old['email'] ?? '',
        ENT_QUOTES,
        'UTF-8',
    ) ?>" required>
  </div>
  <br>

  <div>
    <label>Пароль</label><br>
    <input type="password" name="password" required>
  </div>
  <br>

  <button type="submit">Зарегистрироваться</button>
</form>
