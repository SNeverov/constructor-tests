<?php
/** @var string $content */
/** @var string $title */
?>
<!doctype html>
<html lang="ru">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title ?? 'Конструктор тестов', ENT_QUOTES, 'UTF-8') ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="/assets/css/base.css">
        <?php if (!empty($styles) && is_array($styles)): ?>
            <?php foreach ($styles as $href): ?>
                <link rel="stylesheet" href="<?= htmlspecialchars((string) $href, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
        <?php endif; ?>

    </head>

        <body>

            <header class="site-header">
                <div class="container site-header__inner">
                    <div class="site-header__left">
                        <a class="brand" href="/">Q-Platform</a>

                        <?php if (auth_is_logged_in()): ?>
                            <span class="muted">Привет, <?= htmlspecialchars(auth_user()['login']) ?></span>
                            <nav class="nav">
                                <a href="/my/tests">Мои тесты</a>
                            </nav>
                        <?php endif; ?>
                    </div>

                    <div class="site-header__right">
                        <?php if (auth_is_logged_in()): ?>
                            <?= form_open('/logout', 'post', ['class' => 'inline-form']) ?>
                                <button type="submit" class="btn btn--ghost">Выйти</button>
                            </form>

                            <a href="/my/tests/create" class="btn btn--primary">Создать тест</a>
                        <?php else: ?>
                            <a href="/login" class="btn btn--ghost">Войти</a>
                            <a href="/register" class="btn btn--primary">Регистрация</a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <main class="page">
                <div class="container">
                    <?= $content ?>
                </div>
            </main>

            <?php if (!empty($scripts) && is_array($scripts)): ?>
                <?php foreach ($scripts as $src): ?>
                    <script src="<?= htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8') ?>"></script>
                <?php endforeach; ?>
            <?php endif; ?>


			<footer class="site-footer">
				<div class="container site-footer__inner">

					<div class="site-footer__left">
						<div class="site-footer__brand">
							Q-Platform — конструктор онлайн-тестов
						</div>
						<div class="site-footer__copy">
							© <?= date('Y') ?>
						</div>
					</div>

					<nav class="site-footer__nav">
						<a href="/">Главная</a>
						<a href="/my/tests">Мои тесты</a>
						<a href="/my/tests/create">Создать тест</a>
					</nav>

				</div>
			</footer>



        </body>


</html>
