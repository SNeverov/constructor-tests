(() => {
  let dirty = false;
  let submitting = false;

  // помечаем как "есть несохранённые изменения" при любом ответе
  document.addEventListener('input', (e) => {
    const t = e.target;
    if (!t) return;

    if (
      t.matches('input[type="radio"], input[type="checkbox"], input[type="text"], textarea')
    ) {
      dirty = true;
    }
  }, true);

  document.addEventListener('change', (e) => {
    const t = e.target;
    if (!t) return;

    if (
      t.matches('input[type="radio"], input[type="checkbox"], select')
    ) {
      dirty = true;
    }
  }, true);

  // если юзер реально завершает тест — не показываем подтверждение
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;

    // ограничим только формой прохождения теста (её id добавим следующим шагом)
    if (form.id === 'testPassForm') {
      submitting = true;
      dirty = false;
    }
  }, true);

  window.addEventListener('beforeunload', (e) => {
    if (!dirty) return;
    if (submitting) return;

    e.preventDefault();
    /** @type {any} */ (e).returnValue = '';
  });
})();
