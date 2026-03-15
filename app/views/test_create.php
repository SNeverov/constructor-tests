<div class="test-create">
    <?php
        $isEdit = !empty($is_edit);
        $formAction = (string)($form_action ?? '/my/tests');
        $pageHeading = $isEdit ? 'Редактировать тест' : 'Создать тест';
        $submitLabel = (string)($submit_label ?? ($isEdit ? 'Сохранить изменения' : 'Сохранить тест'));
    ?>

    <div class="page-head">
        <h1><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>

    <?php if (!empty($errors)): ?>
		<div class="form-errors">
			<div class="form-errors__title">Не получилось сохранить тест</div>
			<ul>
				<?php foreach ($errors as $error): ?>
					<li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>



    <?= form_open($formAction, 'post', ['class' => 'form', 'id' => 'testCreateForm']) ?>
        <div class="form-section">
            <div class="section-title">Параметры теста</div>
            <div class="form-row">
                <label>
					<label class="sr-only" for="test_title">Название теста</label>
                    <input placeholder="Название теста" type="text" name="title" required class="input"
                        value="<?= htmlspecialchars((string)($old['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >

                </label>
            </div>

            <div class="form-row">
				<textarea placeholder="Кратко описание, например, о чём или для чего данный тест." name="description" rows="4" class="textarea"><?= htmlspecialchars((string)($old['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-row">
                <label>
					<?php $access = (string)($old['access_level'] ?? 'public'); ?>

					<div class="segmented" role="radiogroup" aria-label="Доступ к тесту">
						<label class="segmented__item">
							<input type="radio" name="access_level" value="public" <?= $access === 'public' ? 'checked' : '' ?>>
							<span>Доступен всем</span>
						</label>

						<label class="segmented__item">
							<input type="radio" name="access_level" value="registered" <?= $access === 'registered' ? 'checked' : '' ?>>
							<span>Только для зарегистрированных</span>
						</label>
					</div>
				</label>

            </div>
        </div>

        <div class="questions-section">
            <div class="questions">
                <div id="questionsList" class="questions__list">
                        <div class="question-card" data-question data-index="0">
                            <div class="question-card__head">
                                <div class="question-card__title" data-question-title>Вопрос #1</div>
                                <button type="button" class="btn btn--danger btn--sm btn-remove-question" data-action="remove-question" aria-label="Удалить вопрос">
                                    <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
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
                                        <div class="question-text-limit" data-question-text-limit>0/1000</div>
                                    </div>
                                </div>

                                <div class="question-answers-wrap">
                                    <div class="form-row question-type-row">
                                        <label class="form-label">Формат ответов</label>

                                        <select name="questions[0][type]" class="input u-hidden" data-question-type aria-hidden="true" tabindex="-1">
                                            <option value="radio">Один вариант (radio)</option>
                                            <option value="checkbox">Несколько вариантов (checkbox)</option>
                                            <option value="input">Ввод текста (input)</option>
                                        </select>

                                        <div class="segmented" data-question-type-ui role="radiogroup" aria-label="Тип вопроса">
                                            <label class="segmented__item">
                                                <input type="radio" value="radio" data-question-type-radio checked>
                                                <span>Один</span>
                                            </label>
                                            <label class="segmented__item">
                                                <input type="radio" value="checkbox" data-question-type-radio>
                                                <span>Несколько</span>
                                            </label>
                                            <label class="segmented__item">
                                                <input type="radio" value="input" data-question-type-radio>
                                                <span>Текст</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="answers-block" data-block="options">
                                        <div class="answers">
                                        <div class="answer-row" data-option>
                                            <label class="correct-flag" title="Правильный ответ">
                                                <input type="hidden" name="questions[0][options][0][is_correct]" value="0">
                                                <input type="checkbox" name="questions[0][options][0][is_correct]" value="1" class="option-correct" aria-label="Правильный ответ">
                                            </label>

                                            <input
                                                type="text"
                                                name="questions[0][options][0][text]"
                                                class="input"
                                                placeholder="Вариант ответа"
                                                maxlength="1000"
                                            >

                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-option aria-label="Удалить вариант">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>

                                        <div class="answer-row" data-option>
                                            <label class="correct-flag" title="Правильный ответ">
                                                <input type="hidden" name="questions[0][options][1][is_correct]" value="0">
                                                <input type="checkbox" name="questions[0][options][1][is_correct]" value="1" class="option-correct" aria-label="Правильный ответ">
                                            </label>

                                            <input
                                                type="text"
                                                name="questions[0][options][1][text]"
                                                class="input"
                                                placeholder="Вариант ответа"
                                                maxlength="1000"
                                            >

                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-option aria-label="Удалить вариант">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="text-answers-block" data-block="text">
                                        <div class="answers">
                                        <div class="text-answer-row" data-answer>
                                            <input
                                                type="text"
                                                name="questions[0][answers][0]"
                                                class="input"
                                                placeholder="Например: молоко"
                                                maxlength="1000"
                                            >
                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-answer aria-label="Удалить ответ">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>

                                        <div class="text-answer-row" data-answer>
                                            <input
                                                type="text"
                                                name="questions[0][answers][1]"
                                                class="input"
                                                placeholder="Альтернативный вариант (если нужен)"
                                                maxlength="1000"
                                            >
                                            <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-answer aria-label="Удалить ответ">
                                                <img src="/assets/img/trash-red.svg?v=2" alt="" aria-hidden="true">
                                            </button>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="question-actions question-actions--answers">
                                        <button type="button" class="btn btn--ghost btn-add-variant" data-add-option>
                                            <img src="/assets/img/add-tests-create.svg?v=2" class="btn-add-icon" alt="" aria-hidden="true">
                                            <span>Добавить вариант</span>
                                        </button>
                                        <button type="button" class="btn btn--ghost btn-add-variant" data-add-answer>
                                            + Добавить правильный ответ
                                        </button>
                                    </div>

                                </div>

                                <div class="question-actions question-actions--question">
                                    <button type="button" class="btn btn--ghost btn-add-question" data-action="add-question-after" data-add-question>
                                        <img src="/assets/img/add-tests-create.svg?v=2" class="btn-add-icon" alt="" aria-hidden="true">
                                        <span>Добавить вопрос</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <div class="test-create-submit">
            <button type="submit" class="btn btn--primary"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </form>

    <script>
        window.__OLD_QUESTIONS__ = <?= json_encode(array_values($old['questions'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <script src="/assets/js/test-create.js"></script>

</div>
