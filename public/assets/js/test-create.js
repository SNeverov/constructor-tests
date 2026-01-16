const MAX_OPTIONS = 10;
const MAX_INPUT_ANSWERS = 10;


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

function reindexAnswers(q) {
    const rows = q.querySelectorAll('[data-answer]');
    rows.forEach((row, i) => {
        row.querySelectorAll('[name]').forEach((el) => {
            const name = el.getAttribute('name');
            if (!name) return;

            const updated = name.replace(/\[answers]\[\d+]/, `[answers][${i}]`);
            el.setAttribute('name', updated);
        });
    });
}

function updateAddAnswerVisibility(q) {
    const textBlock = q.querySelector('[data-block="text"]');
    const addAnswerBtn = q.querySelector('[data-add-answer]');
    if (!textBlock || !addAnswerBtn) return;

    const count = textBlock.querySelectorAll('[data-answer]').length;
    addAnswerBtn.style.display = count >= MAX_INPUT_ANSWERS ? 'none' : '';
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
    if (q.dataset.inited === '1') return;
    q.dataset.inited = '1';
    const typeSelect = q.querySelector('[data-question-type]');

    // уникальный id вопроса (для radio-группы)
    if (!q.dataset.qid) {
        q.dataset.qid = String(Date.now()) + String(Math.floor(Math.random() * 10000));
    }

    if (!typeSelect) return;

    const optionsBlock = q.querySelector('[data-block="options"]');
    const textBlock = q.querySelector('[data-block="text"]');
    const addOptionBtn = q.querySelector('[data-add-option]');
    const addAnswerBtn = q.querySelector('[data-add-answer]');

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

    // удаление текстового ответа (input)
    if (textBlock) {
        textBlock.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove-answer]');
            if (!btn) return;

            const row = btn.closest('[data-answer]');
            if (!row) return;

            const all = textBlock.querySelectorAll('[data-answer]');

            // не даём удалить последний — очищаем
            if (all.length <= 1) {
                row.querySelectorAll('input').forEach((input) => {
                    if (input.type === 'text') input.value = '';
                });
                return;
            }

            row.remove();
            reindexAnswers(q);
            updateAddAnswerVisibility(q);
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
        updateAddAnswerVisibility(q);
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

    // добавление текстового ответа (input)
    if (addAnswerBtn && textBlock) {
        addAnswerBtn.addEventListener('click', () => {
            const count = textBlock.querySelectorAll('[data-answer]').length;
            if (count >= MAX_INPUT_ANSWERS) return;

            const row = textBlock.querySelector('[data-answer]');
            if (!row) return;

            const clone = row.cloneNode(true);

            clone.querySelectorAll('input').forEach((input) => {
                if (input.type === 'text') input.value = '';
            });

            textBlock.insertBefore(clone, addAnswerBtn);
            reindexAnswers(q);
            updateAddAnswerVisibility(q);
        });
    }



    reindexOptions(q);
    updateAddOptionVisibility(q);

    reindexAnswers(q);
    updateAddAnswerVisibility(q);
}

document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.querySelector('#questions');
    const baseTemplate = wrap ? wrap.querySelector('[data-question]') : null;
    const questionTemplate = baseTemplate ? baseTemplate.cloneNode(true) : null;

    if (questionTemplate) {
        questionTemplate.removeAttribute('data-qid');
        questionTemplate.removeAttribute('data-inited');
    }

    const oldQuestions = window.__OLD_QUESTIONS__ || [];

    if (Array.isArray(oldQuestions) && oldQuestions.length > 0) {
        if (wrap && questionTemplate) {
            wrap.innerHTML = '';

            oldQuestions.forEach((qData, idx) => {
                const q = questionTemplate.cloneNode(true);
                q.removeAttribute('data-qid');
                q.removeAttribute('data-inited');

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
                    const addAnswerBtn = q.querySelector('[data-add-answer]');
                    const incomingAnswers = Array.isArray(qData.answers) ? qData.answers : [];

                    if (textBlock && addAnswerBtn && incomingAnswers.length > 0) {
                        // оставляем только 1 строку-шаблон, остальные удаляем
                        const rows = textBlock.querySelectorAll('[data-answer]');
                        rows.forEach((row, i) => { if (i > 0) row.remove(); });

                        // создаём нужное количество строк
                        for (let i = 1; i < incomingAnswers.length; i++) {
                            const firstRow = textBlock.querySelector('[data-answer]');
                            const cloneRow = firstRow.cloneNode(true);

                            cloneRow.querySelectorAll('input').forEach((input) => {
                                if (input.type === 'text') input.value = '';
                            });

                            textBlock.insertBefore(cloneRow, addAnswerBtn);
                        }

                        // переиндексируем name'ы answers[0..n]
                        reindexAnswers(q);

                        // заполняем значения
                        const answerRows = textBlock.querySelectorAll('[data-answer]');
                        answerRows.forEach((row, i) => {
                            const val = incomingAnswers[i] ?? '';
                            const inp = row.querySelector(`input[name="questions[${idx}][answers][${i}]"]`);
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
            if (!questionsWrap || !questionTemplate) return;

            const clone = questionTemplate.cloneNode(true);
            clone.removeAttribute('data-qid');
            clone.removeAttribute('data-inited');

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
