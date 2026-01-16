<h2>Создать тест</h2>

<?php if (!empty($errors) && is_array($errors)): ?>
    <div class="alert alert-error" style="margin: 12px 0; padding: 12px; border: 1px solid #f5c2c7; background: #f8d7da;">
        <div style="font-weight: 700; margin-bottom: 8px;">Не получилось сохранить тест:</div>
        <ul style="margin: 0; padding-left: 18px;">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>


<?= form_open('/my/tests', 'post', ['class' => 'form']) ?>
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

        <hr class="hr">

    <div class="form-row">
        <div class="form-label">Вопросы</div>

        <div id="questions">
            <div class="question-card" data-question data-index="0">
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

                        <div class="answer-row" data-option>
                            <input type="radio" class="option-kind">

                            <label class="correct-flag">
                                <input type="hidden" name="questions[0][options][0][is_correct]" value="0">
                                <input type="checkbox" name="questions[0][options][0][is_correct]" value="1">
                                правильный
                            </label>

                            <input
                                type="text"
                                name="questions[0][options][0][text]"
                                class="input"
                                placeholder="Вариант ответа"
                            >

                            <button type="button" data-remove-option>Удалить</button>
                        </div>


                        <div class="answer-row" data-option>
                            <input type="radio" class="option-kind">

                            <label class="correct-flag">
                                <input type="hidden" name="questions[0][options][1][is_correct]" value="0">
                                <input type="checkbox" name="questions[0][options][1][is_correct]" value="1">
                                правильный
                            </label>

                            <input
                                type="text"
                                name="questions[0][options][1][text]"
                                class="input"
                                placeholder="Вариант ответа"
                            >

                            <button type="button" data-remove-option>Удалить</button>

                        </div>


                        <button type="button" class="btn btn-small" data-add-option>
                            + Добавить вариант
                        </button>
                    </div>



                    <div class="text-answers-block" data-block="text">
                        <div class="form-label">Правильные ответы (текст)</div>

                        <div class="text-answer-row" data-answer>
                            <input
                                type="text"
                                name="questions[0][answers][0]"
                                class="input"
                                placeholder="Например: молоко"
                            >
                            <button type="button" data-remove-answer>Удалить</button>
                        </div>

                        <div class="text-answer-row" data-answer>
                            <input
                                type="text"
                                name="questions[0][answers][1]"
                                class="input"
                                placeholder="Альтернативный вариант (если нужен)"
                            >
                            <button type="button" data-remove-answer>Удалить</button>
                        </div>


                        <button type="button" class="btn btn-small" data-add-answer>
                            + Добавить правильный ответ
                        </button>

                        <div class="hint">
                            Для проверки мы потом будем нормализовать ответ (регистр, пробелы, ё→е).
                        </div>
                    </div>


                </div>
            </div>

        </div>

        <button type="button" id="add-question" class="btn" data-add-question>
            + Добавить вопрос
        </button>
    </div>


    <button type="submit" class="btn btn--primary">Сохранить тест</button>
</form>

<script>
    window.__OLD_QUESTIONS__ = <?= json_encode(array_values($old['questions'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="/assets/js/test-create.js"></script>
