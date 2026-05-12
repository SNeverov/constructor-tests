<?php
declare(strict_types=1);

$pagerPage = max(1, (int)($pagerPage ?? 1));
$pagerPages = max(1, (int)($pagerPages ?? 1));
$pagerPrevUrl = (string)($pagerPrevUrl ?? '');
$pagerNextUrl = (string)($pagerNextUrl ?? '');
$pagerLabel = (string)($pagerLabel ?? 'Пагинация');
$pagerBtnClass = 'pager__btn btn btn-outline btn-icon btn-lg';
?>

<nav class="pager" aria-label="<?= htmlspecialchars($pagerLabel, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($pagerPage > 1 && $pagerPrevUrl !== ''): ?>
        <a class="<?= $pagerBtnClass ?>" href="<?= htmlspecialchars($pagerPrevUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Предыдущая страница">
            <svg class="pager__arrow pager__arrow--prev" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 18l6-6-6-6"></path>
            </svg>
        </a>
    <?php else: ?>
        <span class="<?= $pagerBtnClass ?> is-disabled" aria-hidden="true">
            <svg class="pager__arrow pager__arrow--prev" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 18l6-6-6-6"></path>
            </svg>
        </span>
    <?php endif; ?>

    <span class="pager__info">
        Страница <strong><?= $pagerPage ?></strong> из <strong><?= $pagerPages ?></strong>
    </span>

    <?php if ($pagerPage < $pagerPages && $pagerNextUrl !== ''): ?>
        <a class="<?= $pagerBtnClass ?>" href="<?= htmlspecialchars($pagerNextUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Следующая страница">
            <svg class="pager__arrow" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 18l6-6-6-6"></path>
            </svg>
        </a>
    <?php else: ?>
        <span class="<?= $pagerBtnClass ?> is-disabled" aria-hidden="true">
            <svg class="pager__arrow" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 18l6-6-6-6"></path>
            </svg>
        </span>
    <?php endif; ?>
</nav>
