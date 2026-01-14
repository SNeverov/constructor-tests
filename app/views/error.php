<?php
declare(strict_types=1);
?>

<h1><?= htmlspecialchars($title ?? 'Ошибка', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($message ?? 'Произошла ошибка.', ENT_QUOTES, 'UTF-8') ?></p>
