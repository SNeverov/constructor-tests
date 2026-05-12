<?php
declare(strict_types=1);

function ui_buttons_library(): void
{
    auth_required();

    if (!auth_is_admin()) {
        http_response_code(403);
        view_render('error', [
            'title' => 'Доступ запрещен',
            'message' => 'Эта dev UI page доступна только администратору.',
        ]);
        return;
    }

    view_render('button_library', [
        'title' => 'Button Library',
        'bodyClass' => 'button-library-body',
        'hidePendingBanner' => true,
        'styles' => [
            '/assets/css/ui-buttons.css',
            '/assets/css/button-library.css',
        ],
    ]);
}

function ui_palette_library(): void
{
    auth_required();

    if (!auth_is_admin()) {
        http_response_code(403);
        view_render('error', [
            'title' => 'Доступ запрещен',
            'message' => 'Эта dev UI page доступна только администратору.',
        ]);
        return;
    }

    view_render('palette_library', [
        'title' => 'Color Palette',
        'bodyClass' => 'palette-library-body',
        'hidePendingBanner' => true,
        'styles' => [
            '/assets/css/ui-buttons.css',
            '/assets/css/palette-library.css',
        ],
    ]);
}
