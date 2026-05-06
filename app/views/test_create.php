<div class="test-create">
    <?php
        $oldData = isset($old) && is_array($old) ? $old : [];
        $viewErrors = isset($errors) && is_array($errors) ? $errors : [];
        $isEdit = !empty($is_edit);
        $formAction = (string)($form_action ?? '/my/tests');
        $pageHeading = $isEdit ? 'Редактировать тест' : 'Создать тест';
        $submitLabel = (string)($submit_label ?? ($isEdit ? 'Сохранить изменения' : 'Опубликовать'));
        $oldCover = (string)($oldData['cover_image'] ?? '');
        $testId = (int)($oldData['id'] ?? 0);
        $testStatus = (string)($oldData['status'] ?? ($isEdit ? 'published' : 'draft'));
        $isDraft = $testStatus === 'draft';
        $lastSavedAt = trim((string)($oldData['last_saved_at'] ?? ''));
        $answersMode = test_answers_mode_from_value($oldData['show_answers'] ?? test_answers_mode_after_finish());
        $shuffleQuestions = (int)($oldData['shuffle_questions'] ?? 0) === 1;
        $shuffleAnswers = (int)($oldData['shuffle_answers'] ?? 0) === 1;
        $attemptLimit = test_attempt_limit_from_row($oldData);
        $categoryOptions = test_categories_catalog();
        $selectedCategories = [];
        if (array_key_exists('category_names', $oldData)) {
            $selectedCategories = test_category_names_from_input($oldData['category_names']);
        } elseif ($isEdit || array_key_exists('category_name', $oldData)) {
            $selectedCategories = test_category_display_names($oldData['category_name'] ?? null);
        }
        $selectedCategoryText = test_category_trigger_text($selectedCategories);
        $testCreateJs = '/assets/js/test-create.js';
        $testCategoryPickerJs = '/assets/js/test-category-picker.js';
        $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $testCreateJsVersion = is_file($documentRoot . $testCreateJs) ? (string)filemtime($documentRoot . $testCreateJs) : '1';
        $testCategoryPickerJsVersion = is_file($documentRoot . $testCategoryPickerJs) ? (string)filemtime($documentRoot . $testCategoryPickerJs) : '1';
    ?>

    <div class="page-head">
        <h1><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($isDraft): ?>
            <div class="badge badge--warn">Черновик</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($viewErrors)): ?>
		<div class="form-errors">
			<div class="form-errors__title">Не получилось сохранить тест</div>
			<ul>
				<?php foreach ($viewErrors as $error): ?>
					<li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
    <?= form_open($formAction, 'post', [
        'class' => 'form',
        'id' => 'testCreateForm',
        'enctype' => 'multipart/form-data',
        'data-draft-enabled' => $isDraft || !$isEdit ? '1' : '0',
        'data-draft-url' => $testId > 0 ? '/my/tests/' . $testId . '/draft' : '/my/tests/draft',
        'data-edit-url' => $testId > 0 ? '/my/tests/' . $testId . '/edit' : '',
        'data-test-id' => (string)$testId,
        'data-test-status' => $testStatus,
    ]) ?>
        <input type="hidden" name="draft_test_id" value="<?= $testId > 0 ? (int)$testId : '' ?>" data-draft-test-id>
        <div class="form-section">
            <div class="section-title">Параметры теста</div>
            <div class="form-row">
				<label class="sr-only" for="test_title">Название теста</label>
                <div class="test-title-wrap">
                    <input
                        id="test_title"
                        placeholder="Название теста"
                        type="text"
                        name="title"
                        required
                        maxlength="200"
                        class="input"
                        data-test-title
                        value="<?= htmlspecialchars((string)($oldData['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <div class="test-title-limit" data-test-title-limit>0/200</div>
                </div>
            </div>

            <div class="form-row">
                <div class="test-description-wrap">
                    <textarea
                        placeholder="Кратко описание, например, о чём или для чего данный тест."
                        name="description"
                        rows="1"
                        maxlength="500"
                        class="textarea"
                        data-test-description
                    ><?= htmlspecialchars((string)($oldData['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="test-description-limit" data-test-description-limit>0/500</div>
                </div>
            </div>

            <div class="tc-settings-row">
                <?php $access = (string)($oldData['access_level'] ?? 'public'); ?>

                <div class="tc-setting tc-setting--access">
                    <span class="tc-setting__icon" aria-hidden="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11V7a4 4 0 0 0-8 0v4"/><rect x="5" y="11" width="14" height="10" rx="2"/></svg>
                    </span>
                    <span class="tc-setting__label">Доступ</span>
                    <div class="access-mode-control" role="radiogroup" aria-label="Доступ к тесту">
                        <label class="access-mode-control__item">
                            <input type="radio" name="access_level" value="public" <?= $access === 'public' ? 'checked' : '' ?>>
                            <span>Для всех</span>
                        </label>
                        <label class="access-mode-control__item">
                            <input type="radio" name="access_level" value="registered" <?= $access === 'registered' ? 'checked' : '' ?>>
                            <span>Для зарегистрированных</span>
                        </label>
                    </div>
                </div>

                <!-- Time limit -->
                <label class="tc-setting tc-setting--time ui-tooltip ui-tooltip--bottom" data-tooltip="Оставьте пустым или 00:00:00 для прохождения без лимита">
                    <span class="tc-setting__icon" aria-hidden="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                    <span class="tc-setting__label">Время</span>
                    <input
                        type="time"
                        name="time_limit"
                        class="tc-setting__control tc-setting__time-input"
                        step="1"
                        value="<?= htmlspecialchars((string)($oldData['time_limit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </label>

                <!-- Cover image -->
                <div class="tc-setting tc-setting--cover" data-img-upload="cover">
                    <input type="hidden" name="cover_image" value="<?= htmlspecialchars($oldCover, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="tc-setting__icon" aria-hidden="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </span>
                    <span class="tc-setting__label">Обложка</span>
                    <div class="img-upload-preview <?= $oldCover !== '' ? 'has-image' : '' ?>" data-img-preview>
                        <?php if ($oldCover !== ''): ?>
                            <img src="<?= htmlspecialchars($oldCover, ENT_QUOTES, 'UTF-8') ?>" alt="Обложка" class="img-upload-thumb">
                            <button type="button" class="img-upload-zoom" data-img-zoom aria-label="Увеличить">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="img-upload-controls">
                        <label class="btn btn--ghost btn--xs img-upload-btn" tabindex="0" aria-label="Загрузить обложку">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span data-img-btn-label><?= $oldCover !== '' ? 'Изменить' : 'Загрузить' ?></span>
                            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-img-file-input>
                        </label>
                        <button type="button" class="btn btn--danger btn--xs img-upload-remove tc-cover-remove" aria-label="Удалить обложку" data-img-remove style="<?= $oldCover !== '' ? '' : 'display:none' ?>">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="img-upload-error" data-img-error></div>
                </div>

                <div class="tc-setting tc-setting--answers-mode" data-tooltip="Когда показывать правильные ответы">
                    <span class="tc-setting__icon" aria-hidden="true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    <span class="tc-setting__label">Показывать ответы</span>
                    <div class="answers-mode-control" role="radiogroup" aria-label="Показывать ответы">
                        <label class="answers-mode-control__item">
                            <input type="radio" name="show_answers" value="<?= test_answers_mode_never() ?>" <?= $answersMode === test_answers_mode_never() ? 'checked' : '' ?>>
                            <span>Никогда</span>
                        </label>
                        <label class="answers-mode-control__item">
                            <input type="radio" name="show_answers" value="<?= test_answers_mode_immediate() ?>" <?= $answersMode === test_answers_mode_immediate() ? 'checked' : '' ?>>
                            <span>Сразу</span>
                        </label>
                        <label class="answers-mode-control__item">
                            <input type="radio" name="show_answers" value="<?= test_answers_mode_after_finish() ?>" <?= $answersMode === test_answers_mode_after_finish() ? 'checked' : '' ?>>
                            <span>По завершению</span>
                        </label>
                    </div>
                </div>

                <div class="tc-setting tc-setting--shuffle">
                    <span class="tc-setting__icon" aria-hidden="true">
                        <img src="/assets/img/test_create_svg/shuffle.svg" alt="" width="13" height="13">
                    </span>
                    <span class="tc-setting__label">Перемешивание</span>
                    <div class="setting-toggle-list">
                        <label class="setting-toggle">
                            <input type="checkbox" name="shuffle_questions" value="1" <?= $shuffleQuestions ? 'checked' : '' ?>>
                            <span class="setting-toggle__ui" aria-hidden="true"></span>
                            <span class="setting-toggle__body">
                                <span class="setting-toggle__title">Вопросы</span>
                                <span class="setting-toggle__hint">Вопросы будут показываться в случайном порядке.</span>
                            </span>
                        </label>
                        <label class="setting-toggle">
                            <input type="checkbox" name="shuffle_answers" value="1" <?= $shuffleAnswers ? 'checked' : '' ?>>
                            <span class="setting-toggle__ui" aria-hidden="true"></span>
                            <span class="setting-toggle__body">
                                <span class="setting-toggle__title">Ответы</span>
                                <span class="setting-toggle__hint">Варианты ответа будут перемешаны внутри каждого вопроса.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <label class="tc-setting tc-setting--attempt-limit">
                    <span class="tc-setting__icon" aria-hidden="true">
                        <img src="/assets/img/test_card_svg/refresh.svg" alt="" width="13" height="13">
                    </span>
                    <span class="tc-setting__label">Количество попыток</span>
                    <input
                        type="number"
                        name="attempt_limit"
                        class="tc-setting__attempt-input"
                        min="0"
                        max="1000"
                        step="1"
                        inputmode="numeric"
                        placeholder="0"
                        value="<?= $attemptLimit !== null ? (int)$attemptLimit : '' ?>"
                    >
                    <span class="tc-setting__hint">0 или пусто — бесконечно</span>
                </label>

                <div class="tc-setting tc-setting--category" data-category-picker>
                    <div data-category-hidden-inputs>
                        <?php foreach ($selectedCategories as $selectedCategory): ?>
                            <input type="hidden" name="category_names[]" value="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>" data-category-hidden-value>
                        <?php endforeach; ?>
                    </div>
                    <span class="tc-setting__icon" aria-hidden="true">
                        <img src="/assets/img/test_card_svg/pinpaper-filled.svg" alt="" width="13" height="13">
                    </span>
                    <!-- <span class="tc-setting__label">Категории</span> -->
                    <button type="button" class="tc-category__trigger" data-category-trigger aria-haspopup="listbox" aria-expanded="false">
                        <span class="tc-category__trigger-text<?= $selectedCategories === [] ? ' is-placeholder' : '' ?>" data-category-current><?= htmlspecialchars($selectedCategoryText, ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="tc-category__trigger-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="tc-category__panel" data-category-panel hidden>
                        <label class="sr-only" for="test_category_search">Поиск категории</label>
                        <input id="test_category_search" type="search" class="input tc-category__search" placeholder="Поиск категорий" data-category-search autocomplete="off">
                        <div class="tc-category__selected" data-category-selected>
                            <?php foreach ($selectedCategories as $selectedCategory): ?>
                                <span class="tc-category__selected-chip">
                                    <span><?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?></span>
                                    <button type="button" class="tc-category__selected-remove" data-category-remove="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>" aria-label="Удалить категорию">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="tc-category__list" role="listbox" aria-label="Категории тестов">
                            <?php foreach ($categoryOptions as $slug => $categoryName): ?>
                                <button
                                    type="button"
                                    class="tc-category__option<?= in_array($categoryName, $selectedCategories, true) ? ' is-selected' : '' ?>"
                                    data-category-option
                                    data-category-slug="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                                    data-category-value="<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>"
                                    role="option"
                                    aria-selected="<?= in_array($categoryName, $selectedCategories, true) ? 'true' : 'false' ?>"
                                >
                                    <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="tc-category__empty" data-category-empty hidden>Категория не найдена</div>
                        <div class="tc-category__actions">
                            <button type="button" class="btn btn--primary btn--xs" data-category-apply>Готово</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="questions-section">
            <div class="questions">
                <div id="questionsList" class="questions__list">
                        <div class="question-card" data-question data-index="0">
                            <div class="question-card__head">
                                <div class="question-card__title" data-question-title><span class="question-card__num">1</span> Вопрос</div>
                                <button type="button" class="btn btn--danger btn--sm btn-remove-question ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить вопрос" data-action="remove-question" aria-label="Удалить вопрос">
                                    <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                                    <span>Удалить</span>
                                </button>
                            </div>

                            <div class="question-card__body">
                                <div class="form-row">
                                    <label class="form-label">Текст вопроса</label>
                                    <div class="question-text-wrap">
                                        <textarea
                                            name="questions[0][text]"
                                            class="input question-textarea"
                                            data-question-text
                                            maxlength="1000"
                                            rows="1"
                                            placeholder="Например: Сколько будет 2+2?"
                                        ></textarea>
                                        <div class="field-char-limit" data-question-text-limit>0/1000</div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="img-upload-wrap" data-img-upload="question">
                                        <input type="hidden" name="questions[0][image_path]" value="">
                                        <div class="img-upload-preview" data-img-preview></div>
                                        <div class="img-upload-controls">
                                            <label class="btn btn--ghost btn--sm img-upload-btn ui-tooltip ui-tooltip--bottom" tabindex="0" data-tooltip="Добавить фото" aria-label="Добавить фото">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                                <span data-img-btn-label>Добавить фото к вопросу</span>
                                                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-img-file-input>
                                            </label>
                                            <button type="button" class="btn btn--danger btn--sm img-upload-remove ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить фото" aria-label="Удалить фото" data-img-remove style="display:none">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                                Удалить
                                            </button>
                                        </div>
                                        <div class="img-upload-error" data-img-error></div>
                                    </div>
                                </div>

                                <div class="question-answers-wrap">
                                    <div class="form-row question-type-row">
                                        <label class="form-label question-type-label">Тип ответа</label>

                                        <select name="questions[0][type]" class="input u-hidden" data-question-type aria-hidden="true" tabindex="-1">
                                            <option value="radio">Один вариант (radio)</option>
                                            <option value="checkbox">Несколько вариантов (checkbox)</option>
                                            <option value="input">Ввод текста (input)</option>
                                            <option value="order">Порядок</option>
                                        </select>

                                        <div class="segmented segmented--answer-type" data-question-type-ui role="radiogroup" aria-label="Тип ответа">
                                            <label class="segmented__item">
                                                <input type="radio" value="radio" data-question-type-radio checked>
                                                <span class="answer-type-btn">
                                                    <i class="answer-type-mark answer-type-mark--radio" aria-hidden="true"></i>
                                                    <b>Один</b>
                                                </span>
                                            </label>
                                            <label class="segmented__item">
                                                <input type="radio" value="checkbox" data-question-type-radio>
                                                <span class="answer-type-btn">
                                                    <i class="answer-type-mark answer-type-mark--checkbox" aria-hidden="true"></i>
                                                    <b>Несколько</b>
                                                </span>
                                            </label>
                                            <label class="segmented__item">
                                                <input type="radio" value="input" data-question-type-radio>
                                                <span class="answer-type-btn">
                                                    <img class="answer-type-icon" src="/assets/img/test_create_svg/text.svg" alt="" aria-hidden="true">
                                                    <b>Текст</b>
                                                </span>
                                            </label>
                                            <label class="segmented__item">
                                                <input type="radio" value="order" data-question-type-radio>
                                                <span class="answer-type-btn">
                                                    <img class="answer-type-icon answer-type-icon--order" src="/assets/img/test_create_svg/sort-list.svg" alt="" aria-hidden="true">
                                                    <b>Порядок</b>
                                                </span>
                                            </label>
                                        </div>

                                        <div class="answer-type-hints" aria-live="polite">
                                            <p class="answer-type-hint answer-type-hint--radio">Один верный вариант ответа.</p>
                                            <p class="answer-type-hint answer-type-hint--checkbox">Один или несколько верных вариантов ответа.</p>
                                            <p class="answer-type-hint answer-type-hint--input">Ответ необходимо написать текстом.</p>
                                            <p class="answer-type-hint answer-type-hint--order">Добавьте варианты в правильном порядке. В тесте они будут перемешаны.</p>
                                        </div>
                                    </div>

                                    <div class="answers-block" data-block="options">
                                        <div class="answers">
                                        <div class="answer-row" data-option>
                                            <label class="correct-flag" title="Правильный ответ">
                                                <input type="hidden" name="questions[0][options][0][is_correct]" value="0">
                                                <input type="checkbox" name="questions[0][options][0][is_correct]" value="1" class="option-correct" aria-label="Правильный ответ">
                                            </label>

                                            <div class="option-text-wrap">
                                                <textarea
                                                    name="questions[0][options][0][text]"
                                                    class="input option-textarea"
                                                    data-option-text
                                                    placeholder="Вариант ответа"
                                                    maxlength="1000"
                                                    rows="1"
                                                ></textarea>
                                                <div class="field-char-limit" data-option-text-limit>0/1000</div>
                                            </div>

                                            <div class="img-upload-wrap img-upload-wrap--inline" data-img-upload="option">
                                                <input type="hidden" name="questions[0][options][0][image_path]" value="">
                                                <div class="img-upload-preview" data-img-preview></div>
                                                <label class="btn btn--ghost btn--xs img-upload-btn img-upload-btn--icon ui-tooltip ui-tooltip--bottom" tabindex="0" data-tooltip="Добавить фото" aria-label="Добавить фото">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-img-file-input>
                                                </label>
                                                <button type="button" class="btn btn--danger btn--xs img-upload-remove img-upload-remove--icon ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить фото" aria-label="Удалить фото" data-img-remove style="display:none">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>

                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-option aria-label="Удалить ответ">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>

                                        <div class="answer-row" data-option>
                                            <label class="correct-flag" title="Правильный ответ">
                                                <input type="hidden" name="questions[0][options][1][is_correct]" value="0">
                                                <input type="checkbox" name="questions[0][options][1][is_correct]" value="1" class="option-correct" aria-label="Правильный ответ">
                                            </label>

                                            <div class="option-text-wrap">
                                                <textarea
                                                    name="questions[0][options][1][text]"
                                                    class="input option-textarea"
                                                    data-option-text
                                                    placeholder="Вариант ответа"
                                                    maxlength="1000"
                                                    rows="1"
                                                ></textarea>
                                                <div class="field-char-limit" data-option-text-limit>0/1000</div>
                                            </div>

                                            <div class="img-upload-wrap img-upload-wrap--inline" data-img-upload="option">
                                                <input type="hidden" name="questions[0][options][1][image_path]" value="">
                                                <div class="img-upload-preview" data-img-preview></div>
                                                <label class="btn btn--ghost btn--xs img-upload-btn img-upload-btn--icon ui-tooltip ui-tooltip--bottom" tabindex="0" data-tooltip="Добавить фото" aria-label="Добавить фото">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-img-file-input>
                                                </label>
                                                <button type="button" class="btn btn--danger btn--xs img-upload-remove img-upload-remove--icon ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить фото" aria-label="Удалить фото" data-img-remove style="display:none">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>

                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-option aria-label="Удалить ответ">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="text-answers-block" data-block="text">
                                        <div class="answers">
                                        <div class="text-answer-row" data-answer>
                                            <div class="answer-text-wrap">
                                                <textarea
                                                    name="questions[0][answers][0]"
                                                    class="input answer-textarea"
                                                    data-answer-text
                                                    placeholder="Например: молоко"
                                                    maxlength="1000"
                                                    rows="1"
                                                ></textarea>
                                                <div class="field-char-limit" data-answer-text-limit>0/1000</div>
                                            </div>
                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-answer aria-label="Удалить ответ">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>

                                        <div class="text-answer-row" data-answer>
                                            <div class="answer-text-wrap">
                                                <textarea
                                                    name="questions[0][answers][1]"
                                                    class="input answer-textarea"
                                                    data-answer-text
                                                    placeholder="Альтернативный вариант (если нужен)"
                                                    maxlength="1000"
                                                    rows="1"
                                                ></textarea>
                                                <div class="field-char-limit" data-answer-text-limit>0/1000</div>
                                            </div>
                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-answer aria-label="Удалить ответ">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="question-actions question-actions--answers">
                                        <button type="button" class="btn btn--ghost btn-add-variant" data-add-option>
                                            <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                            <span>Добавить вариант</span>
                                        </button>
                                        <button type="button" class="btn btn--ghost btn-add-variant" data-add-answer>
                                            <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                            Добавить правильный ответ
                                        </button>
                                    </div>

                                </div>

                                <div class="question-actions question-actions--question">
                                    <button type="button" class="btn btn--ghost btn-add-question" data-action="add-question-after" data-add-question>
                                        <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                        <span>Добавить вопрос</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <div class="test-create-submit">
            <?php if ($isDraft || !$isEdit): ?>
                <button type="button" class="btn btn--ghost btn--draft" data-save-draft>
                    <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 9V17.8C19 18.9201 19 19.4802 18.782 19.908C18.5903 20.2843 18.2843 20.5903 17.908 20.782C17.4802 21 16.9201 21 15.8 21H8.2C7.07989 21 6.51984 21 6.09202 20.782C5.71569 20.5903 5.40973 20.2843 5.21799 19.908C5 19.4802 5 18.9201 5 17.8V6.2C5 5.07989 5 4.51984 5.21799 4.09202C5.40973 3.71569 5.71569 3.40973 6.09202 3.21799C6.51984 3 7.0799 3 8.2 3H13M19 9L13 3M19 9H14C13.4477 9 13 8.55228 13 8V3"/></svg>
                    Сохранить черновик
                </button>
            <?php endif; ?>

            <button type="submit" class="btn btn--primary">
                <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?>
            </button>
            <?php
                $initialSaveState = $isDraft
                    ? ($lastSavedAt !== '' ? 'saved' : 'idle')
                    : 'idle';
            ?>
            <div class="test-create-save-status" data-save-status data-save-state="<?= htmlspecialchars($initialSaveState, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isDraft && $lastSavedAt !== ''): ?>
                    Черновик сохранён
                <?php elseif ($isDraft): ?>
                    Черновик ещё не сохранён
                <?php else: ?>
                    Публикация без черновика
                <?php endif; ?>
            </div>
        </div>
    </form>

    <script>
        window.__OLD_QUESTIONS__ = <?= json_encode(array_values($oldData['questions'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <div id="imgLightbox" class="img-lightbox" aria-hidden="true" role="dialog" aria-label="Просмотр изображения">
        <div class="img-lightbox__backdrop" data-lightbox-close></div>
        <div class="img-lightbox__content">
            <button type="button" class="img-lightbox__close" data-lightbox-close aria-label="Закрыть">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <img src="" alt="" class="img-lightbox__img" id="imgLightboxImg">
        </div>
    </div>

    <script src="<?= htmlspecialchars($testCreateJs . '?v=' . rawurlencode($testCreateJsVersion), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars($testCategoryPickerJs . '?v=' . rawurlencode($testCategoryPickerJsVersion), ENT_QUOTES, 'UTF-8') ?>"></script>

</div>
