<div class="test-create">

    <div class="page-head">
        <h1>Создать тест</h1>
    </div>

    <?php if (!empty($errors) && is_array($errors)): ?>
        <div class="alert alert--danger">
            <div class="alert__title">Не получилось сохранить тест:</div>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>


    <?= form_open('/my/tests', 'post', ['class' => 'form']) ?>
        <div class="form-section section">
            <div class="section-title">Параметры теста</div>
            <div class="form-row">
                <label>
                    Название теста<br>
                    <input type="text" name="title" required class="input"
                        value="<?= htmlspecialchars((string)($old['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >

                </label>
            </div>

            <div class="form-row">
                <label>
                    Описание<br>
                    <textarea placeholder="Кратко опишите о чём или для чего данный тест." name="description" rows="4" class="textarea"><?= htmlspecialchars((string)($old['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
            </div>

            <div class="form-row">
                <label>
                    Доступ<br>
                    <?php $access = (string)($old['access_level'] ?? 'public'); ?>
                    <select name="access_level" class="select">
                        <option value="public" <?= $access === 'public' ? 'selected' : '' ?>>Доступен всем</option>
                        <option value="registered" <?= $access === 'registered' ? 'selected' : '' ?>>Только для зарегистрированных</option>
                    </select>

                </label>
            </div>
        </div>

        <div class="form-section section">
            <div class="section-title">Вопросы</div>
            <div class="form-row">
                <div class="questions">
                    <div id="questionsList" class="questions__list">
                        <div class="question-card" data-question data-index="0">
                            <div class="question-card__head">
                                <div class="question-card__title" data-question-title>Вопрос #1</div>
                            </div>

                            <div class="question-card__body">
                                <div class="form-row">
                                    <label class="form-label">Текст вопроса</label>
                                    <input type="text" name="questions[0][text]" class="input" placeholder="Например: Сколько будет 2+2?">
                                </div>

                                <div class="form-row">
                                    <label class="form-label">Тип вопроса</label>
                                    <select name="questions[0][type]" class="input" data-question-type>
                                        <option value="radio">Один вариант (radio)</option>
                                        <option value="checkbox">Несколько вариантов (checkbox)</option>
                                        <option value="input">Ввод текста (input)</option>
                                    </select>

                                    <div class="answers-block" data-block="options">
                                        <div class="form-label">Варианты ответа</div>

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
                                                >

                                                <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-option>
                                                    <img src="/assets/img/delete-svgrepo-com.svg" alt="Удалить">
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
                                                >

                                                <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-option>
                                                    <img src="/assets/img/delete-svgrepo-com.svg" alt="Удалить">
                                                </button>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="text-answers-block" data-block="text">
                                        <div class="form-label">Правильные ответы (текст)</div>
                                        <div class="answers">
                                            <div class="text-answer-row" data-answer>
                                                <input
                                                    type="text"
                                                    name="questions[0][answers][0]"
                                                    class="input"
                                                    placeholder="Например: молоко"
                                                >
                                                <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-answer>
                                                    <img src="/assets/img/delete-svgrepo-com.svg" alt="Удалить">
                                                </button>
                                            </div>

                                            <div class="text-answer-row" data-answer>
                                                <input
                                                    type="text"
                                                    name="questions[0][answers][1]"
                                                    class="input"
                                                    placeholder="Альтернативный вариант (если нужен)"
                                                >
                                                <button type="button" class="btn btn--danger btn--sm btn-del-variant" data-remove-answer>
                                                    <img src="/assets/img/delete-svgrepo-com.svg" alt="Удалить">
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="question-actions">
                                        <button type="button" class="btn btn--ghost btn-add-variant" data-add-option>
                                            + Добавить вариант
                                        </button>
                                        <button type="button" class="btn btn--ghost btn-add-variant" data-add-answer>
                                            + Добавить правильный ответ
                                        </button>
                                        <button type="button" class="btn btn--ghost btn-add-question" data-action="add-question-after" data-add-question>
                                            + Добавить вопрос
                                        </button>
                                        <button type="button" class="btn btn--danger btn-remove-question" data-action="remove-question">
                                            - Удалить вопрос
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <button type="submit" class="btn btn--primary">Сохранить тест</button>
        </div>
    </form>

    <script>
        window.__OLD_QUESTIONS__ = <?= json_encode(array_values($old['questions'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <script src="/assets/js/test-create.js"></script>

</div>
