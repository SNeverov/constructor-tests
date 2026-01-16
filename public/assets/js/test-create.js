const MAX_OPTIONS = 10;


function setQuestionIndex(q, index) {
    q.dataset.index = String(index);

    q.querySelectorAll('[name]').forEach((el) => {
        const name = el.getAttribute('name');
        if (!name) return;

        // заменяем только первый индекс questions[ЧИСЛО]
        const updated = name.replace(/^questions\[\d+]/, `questions[${index}]`);
        el.setAttribute('name', updated);
    });
}

function reindexOptions(q) {
    const options = q.querySelectorAll('[data-option]');
    options.forEach((opt, i) => {
        opt.querySelectorAll('[name]').forEach((el) => {
            const name = el.getAttribute('name');
            if (!name) return;

            const updated = name.replace(
                /\[options]\[\d+]/,
                `[options][${i}]`
            );

            el.setAttribute('name', updated);
        });
    });
}

function updateAddOptionVisibility(q) {
    const optionsBlock = q.querySelector('[data-block="options"]');
    const addOptionBtn = q.querySelector('[data-add-option]');
    if (!optionsBlock || !addOptionBtn) return;

    const count = optionsBlock.querySelectorAll('[data-option]').length;

    // если 10 или больше — скрываем кнопку
    addOptionBtn.style.display = count >= MAX_OPTIONS ? 'none' : '';
}


function initQuestion(q) {
    const typeSelect = q.querySelector('[data-question-type]');

    // уникальный id вопроса (для radio-группы)
    if (!q.dataset.qid) {
        q.dataset.qid = String(Date.now()) + String(Math.floor(Math.random() * 10000));
    }

    if (!typeSelect) return;

    const optionsBlock = q.querySelector('[data-block="options"]');
    const textBlock = q.querySelector('[data-block="text"]');
    const addOptionBtn = q.querySelector('[data-add-option]');

    // удаление варианта ответа
    if (optionsBlock) {
        optionsBlock.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove-option]');
            if (!btn) return;

            const opt = btn.closest('[data-option]');
            if (!opt) return;

            const all = optionsBlock.querySelectorAll('[data-option]');

            // не даём удалить последний вариант (он нужен как "шаблон" для клонирования)
            if (all.length <= 1) {
                // вместо удаления просто очищаем поля
                opt.querySelectorAll('input').forEach((input) => {
                    if (input.type === 'text') input.value = '';
                    if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                });
                return;
            }

            opt.remove();
            reindexOptions(q);
            sync(); // чтобы радио-группа/видимость блоков корректно обновились
            updateAddOptionVisibility(q);
        });
    }

    function sync() {
        const type = typeSelect.value;
        const isInput = type === 'input';

        if (optionsBlock) optionsBlock.style.display = isInput ? 'none' : '';
        if (textBlock) textBlock.style.display = isInput ? '' : 'none';

        q.querySelectorAll('.option-kind').forEach((el) => {
            el.type = type === 'checkbox' ? 'checkbox' : 'radio';

            // радио должны работать как группа внутри одного вопроса
            if (el.type === 'radio') {
                el.name = 'kind_' + q.dataset.qid;
            } else {
                el.removeAttribute('name');
            }
        });
        updateAddOptionVisibility(q);
    }

    typeSelect.addEventListener('change', sync);
    sync();

    if (addOptionBtn && optionsBlock) {
        addOptionBtn.addEventListener('click', () => {
            const count = optionsBlock.querySelectorAll('[data-option]').length;
            if (count >= MAX_OPTIONS) return;

            const option = optionsBlock.querySelector('[data-option]');
            if (!option) return;

            const clone = option.cloneNode(true);

            clone.querySelectorAll('input').forEach((input) => {
                if (input.type === 'text') input.value = '';
                if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
            });

            optionsBlock.insertBefore(clone, addOptionBtn);
            reindexOptions(q);
            sync();
            updateAddOptionVisibility(q);
        });
    }


    reindexOptions(q);
    updateAddOptionVisibility(q);
}

document.addEventListener('DOMContentLoaded', () => {
    const oldQuestions = window.__OLD_QUESTIONS__ || [];

    if (Array.isArray(oldQuestions) && oldQuestions.length > 0) {
        const wrap = document.querySelector('#questions');
        const tpl = wrap ? wrap.querySelector('[data-question]') : null;

        if (wrap && tpl) {
            const template = tpl.cloneNode(true); // <-- сохраняем шаблон ДО очистки
            wrap.innerHTML = '';

            oldQuestions.forEach((qData, idx) => {
                const q = template.cloneNode(true);
                q.removeAttribute('data-qid');

                // поставить индекс (чтобы name'ы были questions[idx]...)
                setQuestionIndex(q, idx);

                // 1) текст вопроса
                const qText = q.querySelector(`input[name="questions[${idx}][text]"]`);
                if (qText) qText.value = qData.text ?? '';

                // 2) тип
                const qType = q.querySelector('[data-question-type]');
                if (qType) qType.value = qData.type ?? 'radio';

                // 3) варианты (radio/checkbox)
                const optionsBlock = q.querySelector('[data-block="options"]');
                const addOptionBtn = q.querySelector('[data-add-option]');

                const incomingOptions = Array.isArray(qData.options) ? qData.options : [];
                if (optionsBlock && addOptionBtn && incomingOptions.length > 0) {
                    // оставляем только 1 строку-шаблон, остальные удаляем
                    const rows = optionsBlock.querySelectorAll('[data-option]');
                    rows.forEach((row, i) => { if (i > 0) row.remove(); });

                    // делаем нужное количество строк
                    for (let i = 1; i < incomingOptions.length; i++) {
                        const firstRow = optionsBlock.querySelector('[data-option]');
                        const cloneRow = firstRow.cloneNode(true);

                        cloneRow.querySelectorAll('input').forEach((input) => {
                            if (input.type === 'text') input.value = '';
                            if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                        });

                        optionsBlock.insertBefore(cloneRow, addOptionBtn);
                    }

                    // переиндексируем name'ы options[0..n]
                    reindexOptions(q);

                    // заполняем значения
                    const optRows = optionsBlock.querySelectorAll('[data-option]');
                    optRows.forEach((row, i) => {
                        const opt = incomingOptions[i] || {};

                        const textInput = row.querySelector(`input[name="questions[${idx}][options][${i}][text]"]`);
                        if (textInput) textInput.value = opt.text ?? '';

                        const correctCheckbox = row.querySelector(`input[type="checkbox"][name="questions[${idx}][options][${i}][is_correct]"]`);
                        if (correctCheckbox) correctCheckbox.checked = String(opt.is_correct ?? '0') === '1';
                    });
                }

                // 4) текстовые ответы (input)
                const textBlock = q.querySelector('[data-block="text"]');
                const incomingAnswers = Array.isArray(qData.answers) ? qData.answers : [];

                if (textBlock && incomingAnswers.length > 0) {
                    incomingAnswers.forEach((val, i) => {
                        const inp = textBlock.querySelector(`input[name="questions[${idx}][answers][${i}]"]`);
                        if (inp) inp.value = val ?? '';
                    });
                }

                wrap.appendChild(q);
                initQuestion(q); // важно: после заполнения
            });
        }
    }


    const questions = document.querySelectorAll('[data-question]');
    questions.forEach((q, i) => setQuestionIndex(q, i));

    document.querySelectorAll('[data-question]').forEach(initQuestion);

    const addQuestionBtn = document.querySelector('[data-add-question]');
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', () => {
            const questionsWrap = document.querySelector('#questions');
            const question = questionsWrap.querySelector('[data-question]');
            if (!question) return;

            const clone = question.cloneNode(true);
            clone.removeAttribute('data-qid');

            clone.querySelectorAll('input, textarea').forEach((el) => {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = false;
                } else {
                    el.value = '';
                }
            });

            const select = clone.querySelector('[data-question-type]');
            if (select) select.value = 'radio';

            questionsWrap.appendChild(clone);

            const newIndex = questionsWrap.querySelectorAll('[data-question]').length - 1;
            setQuestionIndex(clone, newIndex);

            initQuestion(clone);
        });
    }
});

let formDirty = false;
let isSubmitting = false;

function hasPrefilledData() {
    const oldQuestions = window.__OLD_QUESTIONS__ || [];
    if (Array.isArray(oldQuestions) && oldQuestions.length > 0) return true;

    const fields = document.querySelectorAll('input, textarea');
    for (const el of fields) {
        if (el.type === 'hidden') continue;
        if (el.type === 'checkbox' || el.type === 'radio') {
            if (el.checked) return true;
        } else if (String(el.value || '').trim() !== '') {
            return true;
        }
    }

    return false;
}

document.addEventListener('DOMContentLoaded', () => {
    formDirty = hasPrefilledData(); // true только если реально есть введённые/восстановленные данные
});

document.addEventListener('input', (e) => {
    if (e.target.closest('form')) {
        formDirty = true;
    }
});

document.addEventListener('change', (e) => {
    if (e.target.closest('form')) {
        formDirty = true;
    }
});

document.addEventListener('submit', (e) => {
  const form = e.target;
  if (form && form.tagName === 'FORM') {
    isSubmitting = true;
    formDirty = false;
  }
}, true);

window.addEventListener('pageshow', () => {
  isSubmitting = false;
  formDirty = hasPrefilledData();
});


window.addEventListener('beforeunload', (e) => {
    if (!formDirty || isSubmitting) return;

    e.preventDefault();
    e.returnValue = '';
});
