<?php
declare(strict_types=1);

$icons = [
    'search' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>',
    'bookmark' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 4h12v16l-6-3-6 3V4Z"/></svg>',
    'bell' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>',
    'mail' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6Z"/><path d="m4 7 8 6 8-6"/></svg>',
    'plus' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
    'trash' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 14h10l1-14"/><path d="M9 7V4h6v3"/></svg>',
    'chart' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19h16"/><path d="M7 16v-5M12 16V7M17 16v-8"/></svg>',
    'check' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
    'eye' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2 3.5-.2-.1a1.7 1.7 0 0 0-2 .1 8 8 0 0 1-1.7 1l-.2.1a1.7 1.7 0 0 0-1.4 1.4V23H8.3v-.2A1.7 1.7 0 0 0 7 21.4a8 8 0 0 1-1.7-1 1.7 1.7 0 0 0-2-.1l-.2.1-2-3.5.1-.1A1.7 1.7 0 0 0 1.6 15a8 8 0 0 1 0-2 1.7 1.7 0 0 0-.3-1.9l-.1-.1 2-3.5.2.1a1.7 1.7 0 0 0 2-.1 8 8 0 0 1 1.7-1l.2-.1A1.7 1.7 0 0 0 8.6 5V4h4v1a1.7 1.7 0 0 0 1.4 1.4l.2.1a8 8 0 0 1 1.7 1 1.7 1.7 0 0 0 2 .1l.2-.1 2 3.5-.1.1a1.7 1.7 0 0 0-.3 1.9 8 8 0 0 1-.3 2Z"/></svg>',
];

$variants = [
    ['Primary', '.btn-primary', 'btn-primary', 'Основное действие'],
    ['Secondary', '.btn-secondary', 'btn-secondary', 'Вторичное действие'],
    ['Outline', '.btn-outline', 'btn-outline', 'Контурная кнопка'],
    ['Ghost', '.btn-ghost', 'btn-ghost', 'Призрачная кнопка'],
    ['Danger', '.btn-danger', 'btn-danger', 'Удалить'],
    ['Danger Soft', '.btn-danger-soft', 'btn-danger-soft', 'Удалить'],
    ['Success', '.btn-success', 'btn-success', 'Успешно'],
];
?>

<section class="button-library-page" aria-labelledby="buttonLibraryTitle">
    <div class="button-library-shell">
        <div class="button-library-card">
            <header class="button-library-header">
                <span class="button-library-badge">3</span>
                <div>
                    <h1 class="button-library-title" id="buttonLibraryTitle">Button Library</h1>
                    <p class="button-library-muted">Единая система кнопок для конструктора тестов</p>
                </div>
            </header>

            <section class="button-library-section" aria-labelledby="variantsTitle">
                <h2 class="button-library-section-title" id="variantsTitle">Variants</h2>
                <div class="button-library-grid button-library-grid--variants">
                    <?php foreach ($variants as [$name, $className, $class, $label]): ?>
                        <?php $isDeleteDemo = in_array($class, ['btn-danger', 'btn-danger-soft'], true); ?>
                        <div class="button-library-column">
                            <h3><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="button-library-muted"><?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8') ?></p>
                            <button type="button" class="btn <?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?> btn-lg <?= $isDeleteDemo ? 'btn-demo-delete-lg' : 'btn-demo-width-lg' ?>"><?= $class === 'btn-success' ? $icons['check'] : '' ?><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                            <button type="button" class="btn <?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?> btn-md <?= $isDeleteDemo ? 'btn-demo-delete-md' : 'btn-demo-width-md' ?>"><?= $class === 'btn-success' ? $icons['check'] : '' ?><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                            <button type="button" class="btn <?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?> btn-sm <?= $isDeleteDemo ? 'btn-demo-delete-sm' : 'btn-demo-width-sm' ?>"><?= $class === 'btn-success' ? $icons['check'] : '' ?><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="button-library-split">
                <section class="button-library-section" aria-labelledby="sizesTitle">
                    <h2 class="button-library-section-title" id="sizesTitle">Sizes</h2>
                    <p class="button-library-muted">Размеры кнопок</p>
                    <div class="button-library-size-table">
                        <div class="button-library-row">
                            <span>Large / 44px</span>
                            <button type="button" class="btn btn-primary btn-lg btn-demo-width-lg">Кнопка</button>
                            <button type="button" class="btn btn-secondary btn-lg btn-demo-width-lg">Кнопка</button>
                            <button type="button" class="btn btn-outline btn-lg btn-demo-width-lg">Кнопка</button>
                            <button type="button" class="btn btn-ghost btn-lg btn-demo-width-lg">Кнопка</button>
                        </div>
                        <div class="button-library-row">
                            <span>Medium / 36px</span>
                            <button type="button" class="btn btn-primary btn-md btn-demo-width-md">Кнопка</button>
                            <button type="button" class="btn btn-secondary btn-md btn-demo-width-md">Кнопка</button>
                            <button type="button" class="btn btn-outline btn-md btn-demo-width-md">Кнопка</button>
                            <button type="button" class="btn btn-ghost btn-md btn-demo-width-md">Кнопка</button>
                        </div>
                        <div class="button-library-row">
                            <span>Small / 28px</span>
                            <button type="button" class="btn btn-primary btn-sm btn-demo-width-sm">Кнопка</button>
                            <button type="button" class="btn btn-secondary btn-sm btn-demo-width-sm">Кнопка</button>
                            <button type="button" class="btn btn-outline btn-sm btn-demo-width-sm">Кнопка</button>
                            <button type="button" class="btn btn-ghost btn-sm btn-demo-width-sm">Кнопка</button>
                        </div>
                    </div>
                </section>

                <section class="button-library-section" aria-labelledby="iconsTitle">
                    <h2 class="button-library-section-title" id="iconsTitle">Icon Buttons</h2>
                    <p class="button-library-muted">.btn-icon</p>
                    <div class="button-library-icon-grid">
                        <button type="button" class="btn btn-outline btn-icon"><?= $icons['search'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon"><?= $icons['bookmark'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon"><?= $icons['bell'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon"><?= $icons['mail'] ?></button>
                        <button type="button" class="btn btn-secondary btn-icon"><?= $icons['plus'] ?></button>
                        <button type="button" class="btn btn-danger-soft btn-icon"><?= $icons['trash'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-hover"><?= $icons['search'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-hover"><?= $icons['bookmark'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-hover"><?= $icons['bell'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-hover"><?= $icons['mail'] ?></button>
                        <button type="button" class="btn btn-secondary btn-icon is-hover"><?= $icons['plus'] ?></button>
                        <button type="button" class="btn btn-danger-soft btn-icon is-hover"><?= $icons['trash'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-disabled"><?= $icons['search'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-disabled"><?= $icons['bookmark'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-disabled"><?= $icons['bell'] ?></button>
                        <button type="button" class="btn btn-outline btn-icon is-disabled"><?= $icons['mail'] ?></button>
                        <button type="button" class="btn btn-secondary btn-icon is-disabled"><?= $icons['plus'] ?></button>
                        <button type="button" class="btn btn-danger-soft btn-icon is-disabled"><?= $icons['trash'] ?></button>
                    </div>
                </section>

            </div>

            <div class="button-library-split button-library-split--bottom">
                <section class="button-library-section" aria-labelledby="statesTitle">
                    <h2 class="button-library-section-title" id="statesTitle">States</h2>
                    <p class="button-library-muted">Состояния кнопок</p>
                    <div class="button-library-states">
                        <div class="button-library-state-head"></div>
                        <div class="button-library-state-head">Default<span>(по умолчанию)</span></div>
                        <div class="button-library-state-head">Hover<span>(наведение)</span></div>
                        <div class="button-library-state-head">Active / Press<span>(нажатие)</span></div>
                        <div class="button-library-state-head">Focus<span>(фокус)</span></div>
                        <div class="button-library-state-head">Disabled<span>(неактивна)</span></div>

                        <div class="button-library-state-label">Primary</div>
                        <button type="button" class="btn btn-primary btn-md btn-demo-width-md">Кнопка</button>
                        <button type="button" class="btn btn-primary btn-md btn-demo-width-md is-hover">Кнопка</button>
                        <button type="button" class="btn btn-primary btn-md btn-demo-width-md is-active">Кнопка</button>
                        <button type="button" class="btn btn-primary btn-md btn-demo-width-md is-focus">Кнопка</button>
                        <button type="button" class="btn btn-primary btn-md btn-demo-width-md is-disabled">Кнопка</button>

                        <div class="button-library-state-label">Outline</div>
                        <button type="button" class="btn btn-outline btn-md btn-demo-width-md">Кнопка</button>
                        <button type="button" class="btn btn-outline btn-md btn-demo-width-md is-hover">Кнопка</button>
                        <button type="button" class="btn btn-outline btn-md btn-demo-width-md is-active">Кнопка</button>
                        <button type="button" class="btn btn-outline btn-md btn-demo-width-md is-focus">Кнопка</button>
                        <button type="button" class="btn btn-outline btn-md btn-demo-width-md is-disabled">Кнопка</button>

                        <div class="button-library-state-label">Danger Soft</div>
                        <button type="button" class="btn btn-danger-soft btn-md btn-demo-delete-md">Удалить</button>
                        <button type="button" class="btn btn-danger-soft btn-md btn-demo-delete-md is-hover">Удалить</button>
                        <button type="button" class="btn btn-danger-soft btn-md btn-demo-delete-md is-active">Удалить</button>
                        <button type="button" class="btn btn-danger-soft btn-md btn-demo-delete-md is-focus">Удалить</button>
                        <button type="button" class="btn btn-danger-soft btn-md btn-demo-delete-md is-disabled">Удалить</button>
                    </div>
                </section>
            </div>

            <div class="button-library-wide-actions">
                <section class="button-library-section" aria-labelledby="withIconTitle">
                    <h2 class="button-library-section-title" id="withIconTitle">With Icon + Text</h2>
                    <p class="button-library-muted">.btn-with-icon</p>
                    <div class="button-library-stack">
                        <button type="button" class="btn btn-primary btn-md btn-with-icon"><?= $icons['plus'] ?>Создать тест</button>
                        <button type="button" class="btn btn-outline btn-md btn-with-icon"><?= $icons['chart'] ?>Статистика</button>
                        <button type="button" class="btn btn-outline btn-md btn-with-icon"><?= $icons['mail'] ?>Сообщения</button>
                        <button type="button" class="btn btn-danger-soft btn-md btn-with-icon"><?= $icons['trash'] ?>Удалить</button>
                    </div>
                </section>

                <section class="button-library-section" aria-labelledby="examplesTitle">
                    <h2 class="button-library-section-title" id="examplesTitle">Examples</h2>
                    <p class="button-library-muted">Примеры использования</p>
                    <div class="button-library-stack">
                        <button type="button" class="btn btn-outline btn-md btn-with-icon"><?= $icons['bookmark'] ?>Сохранить черновик</button>
                        <button type="button" class="btn btn-outline btn-md btn-with-icon"><?= $icons['eye'] ?>Предпросмотр</button>
                        <button type="button" class="btn btn-ghost btn-md btn-with-icon"><img class="btn__icon-img" src="/assets/img/settings.svg" alt="" aria-hidden="true">Настройки</button>
                        <button type="button" class="btn btn-danger-soft btn-md btn-with-icon"><?= $icons['trash'] ?>Удалить вопрос</button>
                    </div>
                </section>

                <section class="button-library-section" aria-labelledby="specialTitle">
                    <h2 class="button-library-section-title" id="specialTitle">Block Button <span class="button-library-muted">.btn-block</span></h2>
                    <button type="button" class="btn btn-primary btn-md btn-block">Широкая кнопка</button>

                    <h2 class="button-library-section-title">Loading State <span class="button-library-muted">.is-loading</span></h2>
                    <button type="button" class="btn btn-primary btn-md btn-block is-loading">Загрузка...</button>

                    <h2 class="button-library-section-title">Disabled State <span class="button-library-muted">.is-disabled</span></h2>
                    <button type="button" class="btn btn-secondary btn-md btn-block is-disabled">Недоступно</button>
                </section>
            </div>

            <footer class="button-library-note">
                <div>
                    <strong>Правило использования:</strong> все кнопки собираются из базового класса .btn + variant + size
                    <span>Пример: &lt;button class=&quot;btn btn-primary btn-md&quot;&gt;Сохранить&lt;/button&gt;</span>
                </div>
                <ul>
                    <li>Единый стиль</li>
                    <li>Доступность</li>
                    <li>Адаптивность</li>
                    <li>Консистентность</li>
                    <li>Легкая поддержка</li>
                </ul>
            </footer>
        </div>
    </div>
</section>
