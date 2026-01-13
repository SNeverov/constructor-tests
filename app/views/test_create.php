<h2>Создать тест</h2>

<form method="post" action="/my/tests">
    <div style="margin-bottom:12px;">
        <label>
            Название теста<br>
            <input type="text" name="title" required style="width:300px;">
        </label>
    </div>

    <div style="margin-bottom:12px;">
        <label>
            Описание<br>
            <textarea name="description" rows="4" style="width:300px;"></textarea>
        </label>
    </div>

    <div style="margin-bottom:12px;">
        <label>
            Доступ<br>
            <select name="access_level">
                <option value="public">Доступен всем</option>
                <option value="registered">Только для зарегистрированных</option>
            </select>
        </label>
    </div>

    <button type="submit">Сохранить тест</button>
</form>
