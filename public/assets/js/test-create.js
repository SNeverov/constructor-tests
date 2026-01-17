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

function reindexQuestions() {
    const questions = document.querySelectorAll('[data-question]');
    questions.forEach((q, i) => {
        setQuestionIndex(q, i);

        const title = q.querySelector('[data-question-title]') || q.querySelector('.question-card__title');
        if (title) title.textContent = `Вопрос #${i + 1}`;

        reindexOptions(q);
        reindexAnswers(q);
        updateAddOptionVisibility(q);
        updateAddAnswerVisibility(q);
    });
}

function updateAddAnswerVisibility(q) {
    const textBlock = q.querySelector('[data-block="text"]');
    const addAnswerBtn = q.querySelector('[data-add-answer]');
    if (!textBlock || !addAnswerBtn) return;

    const typeSelect = q.querySelector('[data-question-type]');
    const isInput = typeSelect && typeSelect.value === 'input';
    if (!isInput) {
        addAnswerBtn.style.display = 'none';
        return;
    }

    const count = textBlock.querySelectorAll('[data-answer]').length;
    addAnswerBtn.style.display = count >= MAX_INPUT_ANSWERS ? 'none' : '';
}


function updateAddOptionVisibility(q) {
    const optionsBlock = q.querySelector('[data-block="options"]');
    const addOptionBtn = q.querySelector('[data-add-option]');
    if (!optionsBlock || !addOptionBtn) return;

    const typeSelect = q.querySelector('[data-question-type]');
    const isInput = typeSelect && typeSelect.value === 'input';
    if (isInput) {
        addOptionBtn.style.display = 'none';
        return;
    }

    const count = optionsBlock.querySelectorAll('[data-option]').length;

    // если 10 или больше - скрываем кнопку
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

        optionsBlock.addEventListener('change', (e) => {
            const correctInput = e.target.closest('.option-correct');
            if (!correctInput) return;
            if (typeSelect.value !== 'radio') return;
            if (!correctInput.checked) return;

            q.querySelectorAll('.option-correct').forEach((el) => {
                if (el !== correctInput) el.checked = false;
            });
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
        if (addOptionBtn) addOptionBtn.style.display = isInput ? 'none' : '';

        const correctInputs = q.querySelectorAll('.option-correct');
        correctInputs.forEach((el) => {
            if (type === 'radio') {
                el.type = 'radio';
            } else {
                el.type = 'checkbox';
            }
        });

        if (type === 'radio') {
            let firstChecked = null;
            correctInputs.forEach((el) => {
                if (el.checked) {
                    if (!firstChecked) {
                        firstChecked = el;
                    } else {
                        el.checked = false;
                    }
                }
            });
        }
        updateAddOptionVisibility(q);
        updateAddAnswerVisibility(q);
    }

    typeSelect.addEventListener('change', sync);
    sync();

    if (addOptionBtn && optionsBlock) {
        addOptionBtn.addEventListener('click', () => {
            const count = optionsBlock.querySelectorAll('[data-option]').length;
            if (count >= MAX_OPTIONS) return;

            const optionsList = optionsBlock.querySelector('.answers');
            const option = optionsBlock.querySelector('[data-option]');
            if (!option) return;

            const clone = option.cloneNode(true);

            clone.querySelectorAll('input').forEach((input) => {
                if (input.type === 'text') input.value = '';
                if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
            });

            if (optionsList) {
                optionsList.appendChild(clone);
            } else {
                optionsBlock.insertBefore(clone, addOptionBtn);
            }
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

            const answersList = textBlock.querySelector('.answers');
            if (answersList) {
                answersList.appendChild(clone);
            } else {
                textBlock.appendChild(clone);
            }
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
    const wrap = document.querySelector('#questionsList') || document.querySelector('#questions');
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
                const optionsList = optionsBlock ? optionsBlock.querySelector('.answers') : null;
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

                        if (optionsList) {
                            optionsList.appendChild(cloneRow);
                        } else {
                            optionsBlock.insertBefore(cloneRow, addOptionBtn);
                        }
                    }

                    // переиндексируем name'ы options[0..n]
                    reindexOptions(q);

                    // заполняем значения
                    const optRows = optionsBlock.querySelectorAll('[data-option]');
                    optRows.forEach((row, i) => {
                        const opt = incomingOptions[i] || {};

                        const textInput = row.querySelector(`input[name="questions[${idx}][options][${i}][text]"]`);
                        if (textInput) textInput.value = opt.text ?? '';

                        const correctInput = row.querySelector(`input.option-correct[name="questions[${idx}][options][${i}][is_correct]"]`);
                        if (correctInput) correctInput.checked = String(opt.is_correct ?? '0') === '1';
                    });
                }

                // 4) текстовые ответы (input)
                    const textBlock = q.querySelector('[data-block="text"]');
                    const incomingAnswers = Array.isArray(qData.answers) ? qData.answers : [];
                    const answersList = textBlock ? textBlock.querySelector('.answers') : null;

                    if (textBlock && incomingAnswers.length > 0) {
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

                            if (answersList) {
                                answersList.appendChild(cloneRow);
                            } else {
                                textBlock.appendChild(cloneRow);
                            }
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


    document.querySelectorAll('[data-question]').forEach(initQuestion);
    reindexQuestions();

    document.addEventListener('click', (e) => {
        const addAfterBtn = e.target.closest('[data-action="add-question-after"]');
        if (addAfterBtn) {
            const current = addAfterBtn.closest('[data-question]');
            if (!current || !questionTemplate) return;

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

            current.after(clone);
            initQuestion(clone);
            reindexQuestions();
            return;
        }

        const removeBtn = e.target.closest('[data-action="remove-question"]');
        if (removeBtn) {
            const current = removeBtn.closest('[data-question]');
            if (!current) return;

            const allQuestions = document.querySelectorAll('[data-question]');
            if (allQuestions.length <= 1) {
                alert('Нельзя удалить последний вопрос');
                return;
            }

            current.remove();
            reindexQuestions();
        }
    });
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
