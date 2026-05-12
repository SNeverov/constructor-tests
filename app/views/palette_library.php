<?php
declare(strict_types=1);

$paletteSections = [
    [
        'number' => '1',
        'title' => 'Primary Colors',
        'description' => 'Основные mint / teal акценты интерфейса',
        'colors' => [
            ['Primary', '#0D9488', '--color-primary', 'Основной CTA / primary buttons'],
            ['Primary Hover', '#0F766E', '--color-primary-hover', 'Hover для основных действий'],
            ['Primary Active', '#115E59', '--color-primary-active', 'Active / pressed состояние'],
            ['Soft Mint', '#ECFDF5', '--color-primary-soft', 'Мягкий фон secondary'],
            ['Soft Mint Alt', '#F0FDFA', '--color-primary-soft-alt', 'Hover фон outline / mint surface'],
        ],
    ],
    [
        'number' => '2',
        'title' => 'Neutral / Surface Colors',
        'description' => 'Светлые фоны, поверхности и границы',
        'colors' => [
            ['Page Background', '#F8FAFC', '--color-bg-page', 'Фон страницы'],
            ['Page Background Soft', '#F6F8F7', '--color-bg-page-soft', 'Мягкий фон секций'],
            ['Card Background', '#FFFFFF', '--color-surface', 'Фон карточек'],
            ['Surface Muted', '#F8FAFC', '--color-surface-muted', 'Muted surface'],
            ['Border Light', '#E2E8F0', '--color-border', 'Основная граница'],
            ['Border Soft', '#DDE8E6', '--color-border-soft', 'Мягкая граница'],
            ['Border Alt', '#E5EDED', '--color-border-alt', 'Альтернативная граница'],
        ],
    ],
    [
        'number' => '3',
        'title' => 'Text Colors',
        'description' => 'Текстовые цвета без тёмной фоновой схемы',
        'colors' => [
            ['Text Primary', '#0F172A', '--color-text', 'Основной текст'],
            ['Text Secondary', '#64748B', '--color-text-secondary', 'Вторичный текст'],
            ['Text Muted', '#94A3B8', '--color-text-muted', 'Подписи и muted labels'],
        ],
    ],
    [
        'number' => '4',
        'title' => 'Status Colors',
        'description' => 'Семантические состояния и уведомления',
        'colors' => [
            ['Success', '#10B981', '--color-success', 'Успешные действия'],
            ['Success Soft', '#ECFDF5', '--color-success-soft', 'Мягкий фон успеха'],
            ['Danger', '#EF4444', '--color-danger', 'Удаление / ошибка'],
            ['Danger Hover', '#DC2626', '--color-danger-hover', 'Hover опасного действия'],
            ['Danger Soft', '#FEF2F2', '--color-danger-soft', 'Мягкий фон ошибки'],
            ['Danger Border', '#FECACA', '--color-danger-border', 'Граница ошибки'],
            ['Warning', '#F59E0B', '--color-warning', 'Предупреждение'],
            ['Warning Soft', '#FFF7ED', '--color-warning-soft', 'Мягкий фон предупреждения'],
            ['Info', '#3B82F6', '--color-info', 'Информационный акцент'],
            ['Info Soft', '#EFF6FF', '--color-info-soft', 'Мягкий фон info'],
        ],
    ],
];

$tokens = <<<'CSS'
:root {
  --color-bg-page: #F8FAFC;
  --color-bg-page-soft: #F6F8F7;
  --color-surface: #FFFFFF;
  --color-surface-muted: #F8FAFC;
  --color-border: #E2E8F0;
  --color-border-soft: #DDE8E6;
  --color-border-alt: #E5EDED;
  --color-text: #0F172A;
  --color-text-secondary: #64748B;
  --color-text-muted: #94A3B8;
  --color-primary: #0D9488;
  --color-primary-hover: #0F766E;
  --color-primary-active: #115E59;
  --color-primary-soft: #ECFDF5;
  --color-primary-soft-alt: #F0FDFA;
  --color-danger: #EF4444;
  --color-danger-hover: #DC2626;
  --color-danger-soft: #FEF2F2;
  --color-danger-border: #FECACA;
  --color-success: #10B981;
  --color-warning: #F59E0B;
  --color-info: #3B82F6;
}
CSS;
?>

<section class="palette-library-page" aria-labelledby="paletteLibraryTitle">
    <div class="palette-library-shell">
        <div class="palette-library-card">
            <header class="palette-library-header">
                <span class="palette-library-badge">4</span>
                <div>
                    <h1 class="palette-library-title" id="paletteLibraryTitle">Color Palette</h1>
                    <p class="palette-library-muted">Цветовая система проекта</p>
                </div>
            </header>

            <div class="palette-library-layout">
                <div class="palette-library-main">
                    <?php foreach ($paletteSections as $section): ?>
                        <section class="palette-section" aria-labelledby="paletteSection<?= htmlspecialchars($section['number'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="palette-section__head">
                                <span class="palette-section__badge"><?= htmlspecialchars($section['number'], ENT_QUOTES, 'UTF-8') ?></span>
                                <div>
                                    <h2 id="paletteSection<?= htmlspecialchars($section['number'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p><?= htmlspecialchars($section['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="palette-grid">
                                <?php foreach ($section['colors'] as [$name, $hex, $token, $usage]): ?>
                                    <?php $textClass = in_array(strtoupper($hex), ['#0F172A', '#64748B', '#0D9488', '#0F766E', '#115E59', '#EF4444', '#DC2626', '#10B981', '#3B82F6'], true) ? ' palette-swatch--dark' : ''; ?>
                                    <article class="palette-color-card">
                                        <div class="palette-swatch<?= $textClass ?>" style="--swatch-color: <?= htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <h3><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="palette-color-card__hex"><?= htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') ?></p>
                                        <code><?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?></code>
                                        <span><?= htmlspecialchars($usage, ENT_QUOTES, 'UTF-8') ?></span>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>

                <aside class="palette-library-side" aria-label="Примеры и CSS tokens">
                    <section class="palette-panel" aria-labelledby="usageExamplesTitle">
                        <div class="palette-section__head">
                            <span class="palette-section__badge">5</span>
                            <div>
                                <h2 id="usageExamplesTitle">Usage Examples</h2>
                                <p>Мини-примеры применения палитры</p>
                            </div>
                        </div>
                        <div class="palette-examples">
                            <div class="palette-example-row"><span>Primary Button</span><button type="button" class="btn btn-primary btn-md">Основное действие</button></div>
                            <div class="palette-example-row"><span>Secondary Button</span><button type="button" class="btn btn-secondary btn-md">Вторичное действие</button></div>
                            <div class="palette-example-row"><span>Outline Button</span><button type="button" class="btn btn-outline btn-md">Контурная кнопка</button></div>
                            <div class="palette-example-row"><span>Danger Button</span><button type="button" class="btn btn-danger btn-md">Удалить</button></div>
                            <div class="palette-example-row"><span>Success Badge</span><span class="palette-success-badge">Успешно</span></div>
                            <div class="palette-example-row"><span>Card Border</span><span class="palette-border-sample"></span></div>
                            <div class="palette-example-row"><span>Muted Text</span><span class="palette-muted-sample">Вторичный текст интерфейса</span></div>
                            <div class="palette-example-row"><span>Page Background</span><span class="palette-bg-sample">#F8FAFC</span></div>
                        </div>
                    </section>

                    <section class="palette-panel" aria-labelledby="cssTokensTitle">
                        <div class="palette-section__head">
                            <span class="palette-section__badge">6</span>
                            <div>
                                <h2 id="cssTokensTitle">CSS Tokens</h2>
                                <p>Готово для :root</p>
                            </div>
                        </div>
                        <pre class="palette-code"><code><?= htmlspecialchars($tokens, ENT_QUOTES, 'UTF-8') ?></code></pre>
                    </section>
                </aside>
            </div>

            <footer class="palette-note">
                <strong>Правило использования:</strong>
                новые цвета проекта должны браться из палитры и CSS-токенов, а не задаваться вручную в каждом компоненте.
            </footer>
        </div>
    </div>
</section>
