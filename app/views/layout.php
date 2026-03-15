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
		<?php
			$asset = static function (string $path): string {
				$fullPath = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . $path;
				$version = is_file($fullPath) ? (string)filemtime($fullPath) : '1';
				return $path . '?v=' . rawurlencode($version);
			};
		?>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/base.css'), ENT_QUOTES, 'UTF-8') ?>">
		<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/ui.css'), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($styles) && is_array($styles)): ?>
            <?php foreach ($styles as $href): ?>
                <?php
                    $hrefStr = (string)$href;
                    $hrefOut = str_starts_with($hrefStr, '/') ? $asset($hrefStr) : $hrefStr;
                ?>
                <link rel="stylesheet" href="<?= htmlspecialchars($hrefOut, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
        <?php endif; ?>

    </head>

		<?php
			$bodyClass = trim((string)($bodyClass ?? ''));
			$toast = flash_get('toast', null);
			$toastAttr = '';
            $is404Page = str_contains($bodyClass, 'page-404');

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
                        <a class="brand-wrap" href="/" aria-label="Q-Platform">
                            <img src="<?= htmlspecialchars($asset('/assets/img/hdr-logo-q.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" class="brand-wrap__logo" aria-hidden="true">
                            <span class="brand-wrap__text">Platform</span>
                        </a>
                    </div>

                    <div class="site-header__right">
                        <?php if (auth_is_logged_in()): ?>
                            <div class="header-controls">
                                <nav class="header-actions" aria-label="Главная навигация">
                                    <a class="icon-btn ui-tooltip ui-tooltip--bottom" href="/my/tests/create" data-tooltip="Создать тест" aria-label="Создать тест">
                                        <img src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-create.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="icon-btn ui-tooltip ui-tooltip--bottom" href="/my/tests" data-tooltip="Мои тесты" aria-label="Мои тесты">
                                        <img src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-tests-new.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="icon-btn ui-tooltip ui-tooltip--bottom" href="/my/results" data-tooltip="Мои результаты" aria-label="Мои результаты">
                                        <img src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-results.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="icon-btn ui-tooltip ui-tooltip--bottom" href="/account" data-tooltip="Мой профиль" aria-label="Мой профиль">
                                        <img src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-profile.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="icon-btn ui-tooltip ui-tooltip--bottom" href="/feedback" data-tooltip="Обратная связь" aria-label="Обратная связь">
                                        <img src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-feedback.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="icon-btn ui-tooltip ui-tooltip--bottom" href="/my/tests/trash" data-tooltip="Корзина" aria-label="Корзина">
                                        <img src="<?= htmlspecialchars($asset('/assets/img/trash.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                        <?php if (!empty($trashCount)): ?>
                                            <span class="icon-btn__badge" aria-label="В корзине: <?= (int)$trashCount ?>">
                                                <?= (int)$trashCount ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </nav>

                                <?= form_open('/logout', 'post', ['class' => 'inline-form']) ?>
                                    <button type="submit" class="icon-btn icon-btn--logout ui-tooltip ui-tooltip--bottom" data-tooltip="Выйти" aria-label="Выйти">
                                        <img src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-logout.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <a href="/login" class="btn btn--ghost">Войти</a>
                            <a href="/register" class="btn btn--primary">Регистрация</a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <main class="page page--full">
                <div class="container">
                    <?php if ($is404Page): ?>
                        <?= $content ?>
                    <?php else: ?>
                        <div class="page-content-wrap">
                            <?= $content ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>

            <button type="button" class="scroll-top" id="scrollTopBtn" aria-label="Наверх" title="Наверх">
                <span class="scroll-top__icon" aria-hidden="true"></span>
            </button>

			<script src="<?= htmlspecialchars($asset('/assets/js/ui.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
            <script src="<?= htmlspecialchars($asset('/assets/js/scroll-top.js'), ENT_QUOTES, 'UTF-8') ?>"></script>


            <?php if (!empty($scripts) && is_array($scripts)): ?>
                <?php foreach ($scripts as $src): ?>
                    <?php
                        $srcStr = (string)$src;
                        $srcOut = str_starts_with($srcStr, '/') ? $asset($srcStr) : $srcStr;
                    ?>
                    <script src="<?= htmlspecialchars($srcOut, ENT_QUOTES, 'UTF-8') ?>"></script>
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
                        <a href="/account">Профиль</a>
                        <a href="/my/results">Мои результаты</a>
						<a href="/my/tests">Мои тесты</a>
						<a href="/my/tests/trash">Корзина</a>
						<a href="/my/tests/create">Создать тест</a>
					</nav>

				</div>
			</footer>



        </body>


</html>
