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

    function sync() {
        const type = typeSelect.value;
        const isInput = (type === 'input');

        if (optionsBlock) optionsBlock.style.display = isInput ? 'none' : '';
        if (textBlock) textBlock.style.display = isInput ? '' : 'none';

        q.querySelectorAll('.option-kind').forEach((el) => {
            el.type = (type === 'checkbox') ? 'checkbox' : 'radio';

            // радио должны работать как группа внутри одного вопроса
            if (el.type === 'radio') {
                el.name = 'kind_' + q.dataset.qid;
            } else {
                el.removeAttribute('name');
            }
        });

    }

    typeSelect.addEventListener('change', sync);
    sync();

    if (addOptionBtn && optionsBlock) {
        addOptionBtn.addEventListener('click', () => {
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
        });
    }

  reindexOptions(q);
}

document.addEventListener('DOMContentLoaded', () => {
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
