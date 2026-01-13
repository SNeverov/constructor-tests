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
        <link rel="stylesheet" href="/assets/css/base.css">
    </head>

    <body>

        <header class="header">
            <div class="container">
                <a class="logo" href="/">Конструктор тестов</a>
                <nav class="nav">

                    <?php if (auth_is_logged_in()): ?>
                        <span>Привет, <?= htmlspecialchars(
                            auth_user()['login'],
                            ENT_QUOTES,
                            'UTF-8',
                        ) ?>
                        </span>

                        <a href="/my/tests">Мои тесты</a>

                        <form method="post" action="/logout" style="display:inline">
                            <button type="submit">Выйти</button>
                        </form>

                    <?php else: ?>
                        <a href="/login">Войти</a>
                        <a href="/register">Регистрация</a>
                    <?php endif; ?>

                    <a class="btn" href="/my/tests/create">Создать тест</a>
                </nav>

            </div>
        </header>

        <main class="container">
            <?= $content ?>
        </main>

    </body>

</html>
