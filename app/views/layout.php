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
		<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/page-shell.css'), ENT_QUOTES, 'UTF-8') ?>">
		<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/ui-buttons.css'), ENT_QUOTES, 'UTF-8') ?>">
		<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/dashboard-shell.css'), ENT_QUOTES, 'UTF-8') ?>">
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
        <link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/form-focus.css'), ENT_QUOTES, 'UTF-8') ?>">

    </head>

		<?php
			$bodyClass = trim((string)($bodyClass ?? ''));
			$toast = flash_get('toast', null);
            $syncEvent = flash_get('sync_event', null);
			$toastAttr = '';
            $is404Page = str_contains($bodyClass, 'page-404');

			if (is_array($toast) && !empty($toast['text'])) {
				$toastJson = json_encode($toast, JSON_UNESCAPED_UNICODE);
				if (is_string($toastJson)) {
					$toastAttr = " data-toast='" . htmlspecialchars($toastJson, ENT_QUOTES, 'UTF-8') . "'";
				}
			}

            $syncEventJson = '';
            if (is_array($syncEvent) && !empty($syncEvent['type'])) {
                $tmpJson = json_encode($syncEvent, JSON_UNESCAPED_UNICODE);
                if (is_string($tmpJson)) {
                    $syncEventJson = $tmpJson;
                }
            }
		?>
		<body<?= $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '' ?><?= $toastAttr ?>>


            <header class="site-header">
                <div class="container site-header__inner">
                    <div class="site-header__left">
                        <a class="brand-wrap" href="/" aria-label="Q-Platform">
                            <span class="brand-wrap__text">Q U A Z A R</span>
                            <span class="brand-wrap__subtitle">Платформа тестов</span>
                        </a>
                    </div>

                    <div class="site-header__search" role="search" aria-label="Поиск тестов">
                        <span class="site-header__search-icon" aria-hidden="true"></span>
                        <input class="site-header__search-input" type="search" placeholder="Поиск тестов..." aria-label="Поиск тестов">
                        <span class="site-header__search-kbd" aria-hidden="true">Ctrl K</span>
                    </div>

                    <div class="site-header__right">
                        <?php if (auth_is_logged_in()): ?>
                            <div class="header-controls">
                                <nav class="header-actions" aria-label="Главная навигация">
                                    <a class="btn btn-primary btn-md btn-with-icon icon-btn icon-btn--create ui-tooltip ui-tooltip--bottom" href="/my/tests/create" data-tooltip="Создать тест" aria-label="Создать тест">
                                        <img class="btn__icon-img" src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-create.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                        <span class="icon-btn__text">Создать тест</span>
                                    </a>
                                    <a class="btn btn-outline btn-icon icon-btn ui-tooltip ui-tooltip--bottom" href="/my/tests" data-tooltip="Мои тесты" aria-label="Мои тесты">
                                        <img class="btn__icon-img" src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-tests-new.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="btn btn-outline btn-icon icon-btn ui-tooltip ui-tooltip--bottom" href="/my/results" data-tooltip="Мои результаты" aria-label="Мои результаты">
                                        <img class="btn__icon-img" src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-results.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <?php $bookmarksTotal = (int)($bookmarksCount ?? 0); ?>
                                    <a class="btn btn-outline btn-icon icon-btn icon-btn--bookmarks ui-tooltip ui-tooltip--bottom" href="/my/bookmarks" data-tooltip="Мои закладки" aria-label="Мои закладки" data-header-bookmarks-link>
                                        <img src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-bookmarks.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true" class="btn__icon-img icon-btn__bookmark-icon">
                                        <span
                                            class="icon-btn__badge<?= $bookmarksTotal > 0 ? '' : ' is-hidden' ?>"
                                            aria-label="В закладках: <?= $bookmarksTotal ?>"
                                            data-header-bookmarks-badge
                                        >
                                            <?= $bookmarksTotal ?>
                                        </span>
                                    </a>
                                    <a class="btn btn-outline btn-icon icon-btn ui-tooltip ui-tooltip--bottom" href="/account" data-tooltip="Мой профиль" aria-label="Мой профиль">
                                        <img class="btn__icon-img" src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-profile.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="btn btn-outline btn-icon icon-btn ui-tooltip ui-tooltip--bottom" href="/feedback" data-tooltip="Обратная связь" aria-label="Обратная связь">
                                        <img class="btn__icon-img" src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-feedback.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </a>
                                    <a class="btn btn-danger-soft btn-icon icon-btn ui-tooltip ui-tooltip--bottom" href="/my/tests/trash" data-tooltip="Корзина" aria-label="Корзина">
                                        <img class="btn__icon-img" src="<?= htmlspecialchars($asset('/assets/img/trash.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                        <?php $trashTotal = (int)($trashCount ?? 0); ?>
                                        <span
                                            class="icon-btn__badge<?= $trashTotal > 0 ? '' : ' is-hidden' ?>"
                                            aria-label="В корзине: <?= $trashTotal ?>"
                                            data-header-trash-badge
                                        >
                                            <?= $trashTotal ?>
                                        </span>
                                    </a>
                                </nav>

                                <?= form_open('/logout', 'post', ['class' => 'inline-form']) ?>
                                    <button type="submit" class="btn btn-danger-soft btn-icon icon-btn icon-btn--logout ui-tooltip ui-tooltip--bottom" data-tooltip="Выйти" aria-label="Выйти">
                                        <img class="btn__icon-img" src="<?= htmlspecialchars($asset('/assets/img/hdr-icon-logout.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="auth-menu" id="authMenu">
                                <button type="button" class="btn btn-outline auth-menu__btn" id="authMenuTrigger" aria-expanded="false" aria-haspopup="menu">
                                    Войти
                                    <svg class="auth-menu__chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                        <path d="M2 4.5l4 3 4-3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <div class="auth-menu__dropdown" id="authMenuDropdown" aria-hidden="true" role="menu" aria-labelledby="authMenuTrigger">
                                    <a href="/login" class="auth-menu__item" role="menuitem">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Войти
                                    </a>
                                    <div class="auth-menu__sep" aria-hidden="true"></div>
                                    <a href="/register" class="auth-menu__item auth-menu__item--register" role="menuitem">
                                        <img src="/assets/img/user-plus.svg" width="16" height="16" aria-hidden="true">
                                        Создать аккаунт
                                    </a>
                                </div>
                            </div>
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

            <button type="button" class="scroll-top btn btn-icon btn-lg ui-tooltip ui-tooltip--force-top" id="scrollTopBtn" aria-label="Наверх" data-tooltip="Наверх">
                <svg class="scroll-top__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="8 13 12 9 16 13"/>
                </svg>
            </button>

            <?php
                $hidePendingBanner = (bool)($hidePendingBanner ?? false);
                $pendingAttempt = (!$hidePendingBanner) ? pending_attempt_for_user() : null;
            ?>
            <?php if ($pendingAttempt !== null): ?>
                <div
                    class="pending-banner"
                    id="pendingBanner"
                    data-pending-attempt-id="<?= (int)$pendingAttempt['attempt_id'] ?>"
                    role="alert"
                    aria-live="polite"
                >
                    <span class="pending-banner__icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                    <span class="pending-banner__text">
                        Незавершённый тест:
                        <strong class="pending-banner__title"><?= htmlspecialchars($pendingAttempt['test_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                    <a class="pending-banner__btn" href="<?= htmlspecialchars($pendingAttempt['pass_url'], ENT_QUOTES, 'UTF-8') ?>">
                        Продолжить
                    </a>
                    <button type="button" class="pending-banner__close" id="pendingBannerClose" aria-label="Закрыть уведомление">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            <?php endif; ?>

			<script src="<?= htmlspecialchars($asset('/assets/js/ui.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
            <script src="<?= htmlspecialchars($asset('/assets/js/bookmarks.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
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

            <?php if ($syncEventJson !== ''): ?>
                <script>
                    (function () {
                        try {
                            if (!('BroadcastChannel' in window)) return;
                            var data = <?= $syncEventJson ?>;
                            var ch = new BroadcastChannel('qp-header-counters');
                            ch.postMessage(data);
                            setTimeout(function () { try { ch.close(); } catch (e) {} }, 120);
                        } catch (e) {}
                    })();
                </script>
            <?php endif; ?>


			<footer class="site-footer">
				<div class="container site-footer__inner">

					<div class="site-footer__left">
						<div class="site-footer__brand">
							Q U A Z A R — платформа тестов
						</div>
						<div class="site-footer__copy">
							© <?= date('Y') ?>
						</div>
					</div>

					<nav class="site-footer__nav">
						<a href="/">Главная</a>
                        <a href="/account">Профиль</a>
                        <a href="/my/results">Мои результаты</a>
                        <a href="/my/bookmarks">Мои закладки</a>
						<a href="/my/tests">Мои тесты</a>
						<a href="/my/tests/trash">Корзина</a>
						<a href="/my/tests/create">Создать тест</a>
					</nav>

				</div>
			</footer>



        </body>


</html>
