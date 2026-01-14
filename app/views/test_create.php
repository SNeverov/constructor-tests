<h2>Создать тест</h2>

<form method="post" action="/my/tests" class="form">
    <div class="form-row">
        <label>
            Название теста<br>
            <input type="text" name="title" required class="input">
        </label>
    </div>

    <div class="form-row">
        <label>
            Описание<br>
            <textarea name="description" rows="4" class="textarea"></textarea>
        </label>
    </div>

    <div class="form-row">
        <label>
            Доступ<br>
            <select name="access_level" class="select">
                <option value="public">Доступен всем</option>
                <option value="registered">Только для зарегистрированных</option>
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
                                placeholder="Вариант ответа 1"
                            >
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
                                placeholder="Вариант ответа 2"
                            >
                        </div>


                        <button type="button" class="btn btn-small" data-add-option>
                            + Добавить вариант
                        </button>
                    </div>



                    <div class="text-answers-block" data-block="text">
                        <div class="form-label">Правильные ответы (текст)</div>

                        <div class="text-answer-row">
                            <input
                                type="text"
                                name="questions[0][answers][0]"
                                class="input"
                                placeholder="Например: молоко"
                            >
                        </div>

                        <div class="text-answer-row">
                            <input
                                type="text"
                                name="questions[0][answers][1]"
                                class="input"
                                placeholder="Альтернативный вариант (если нужен)"
                            >
                        </div>

                        <button type="button" class="btn btn-small">
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

<script src="/assets/js/test-create.js"></script>
