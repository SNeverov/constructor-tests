<?php
declare(strict_types=1);

function home_index(): void
{
    view_render('home', [
        'title' => 'Главная',
    ]);
}
