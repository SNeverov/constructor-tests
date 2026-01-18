<?php
declare(strict_types=1);
?>

<div class="error-page">
    <div class="error-card">
        <h1 class="error-code"><?= htmlspecialchars($title ?? 'Ошибка', ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="error-message"><?= htmlspecialchars($message ?? 'Произошла ошибка.', ENT_QUOTES, 'UTF-8') ?></p>
		<div style="margin-top: 16px;">
			<a class="btn btn--primary" href="/">На главную</a>
		</div>

    </div>
</div>
