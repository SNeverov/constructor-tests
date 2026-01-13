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

    <button type="submit" class="btn btn--primary">Сохранить тест</button>
</form>
