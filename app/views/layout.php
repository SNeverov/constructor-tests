<?php
/** @var string $content */
/** @var string $title */
?>
<!doctype html>
<html lang="ru">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Q-Platform — конструктор онлайн-тестов: создавайте тесты, проходите их и сохраняйте результаты.">
		<meta name="robots" content="index, follow">
		<meta name="color-scheme" content="light dark">

        <?php
			$baseTitle = 'Q-Platform';
			$pageTitle = trim((string)($title ?? ''));
			$fullTitle = $pageTitle !== '' && $pageTitle !== $baseTitle
				? $pageTitle . ' — ' . $baseTitle
				: $baseTitle;
		?>
		<title><?= htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') ?></title>
		<meta property="og:site_name" content="Q-Platform">
		<meta property="og:title" content="<?= htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') ?>">
		<meta property="og:type" content="website">

		<!-- Favicons -->
		<link rel="icon" href="/assets/img/favicon/favicon.ico" sizes="any">
		<link rel="icon" type="image/svg+xml" href="/assets/img/favicon/favicon.svg">
		<link rel="icon" type="image/png" sizes="96x96" href="/assets/img/favicon/favicon-96x96.png">
		<link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">

		<!-- PWA -->
		<link rel="manifest" href="/assets/img/favicon/site.webmanifest">

		<!-- Theme -->
		<meta name="theme-color" content="#0B1220">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="/assets/css/base.css">
		<link rel="stylesheet" href="/assets/css/ui.css">
        <?php if (!empty($styles) && is_array($styles)): ?>
            <?php foreach ($styles as $href): ?>
                <link rel="stylesheet" href="<?= htmlspecialchars((string) $href, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
        <?php endif; ?>

    </head>

        <?php
			$bodyClass = trim((string)($bodyClass ?? ''));
			$toast = flash_get('toast', null);
			$toastAttr = '';

			if (is_array($toast) && !empty($toast['text'])) {
				$toastJson = json_encode($toast, JSON_UNESCAPED_UNICODE);
				if (is_string($toastJson)) {
					$toastAttr = " data-toast='" . htmlspecialchars($toastJson, ENT_QUOTES, 'UTF-8') . "'";
				}
			}
		?>
		<body<?= $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '' ?><?= $toastAttr ?>>


            <header class="site-header">
                <div class="container site-header__inner">
                    <div class="site-header__left">
                        <a class="brand" href="/">Q-Platform</a>

                        <?php if (auth_is_logged_in()): ?>
                            <span class="muted">Привет, <?= htmlspecialchars(auth_user()['login']) ?></span>
                            <nav class="nav">
                                <a href="/my/tests">Мои тесты</a>
								<a href="/my/tests/trash" class="nav-pill" aria-label="Корзина">
									<span class="nav-pill__icon">
										<img
											src="/assets/img/trash-can.svg"
											width="16"
											height="16"
											alt=""
											aria-hidden="true"
										/>
									</span>

									<!-- <span class="nav-pill__label">Корзина</span> -->

									<?php if (!empty($trashCount)): ?>
										<span class="nav-pill__count"><?= (int)$trashCount ?></span>
									<?php endif; ?>
								</a>





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

            <main class="page page--full">
                <div class="container">
                    <?= $content ?>
                </div>
            </main>

			<script src="/assets/js/ui.js"></script>


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
						<a href="/my/tests/trash">Корзина</a>
						<a href="/my/tests/create">Создать тест</a>
					</nav>

				</div>
			</footer>



        </body>


</html>
