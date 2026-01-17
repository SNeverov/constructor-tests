(function () {
    const form = document.getElementById('testPassForm');
    if (!form) return;

    const resetBtn = document.getElementById('resetAnswersBtn');
    const finishBtn = document.getElementById('finishTestBtn');
    const note = document.getElementById('finishNote');

    let formDirty = false;

    form.addEventListener('input', () => {
        formDirty = true;
    });

    form.addEventListener('change', () => {
        formDirty = true;
    });

    window.addEventListener('beforeunload', (e) => {
        if (!formDirty) return;
        e.preventDefault();
        e.returnValue = '';
    });


    function showNote(text) {
        if (!note) return;
        note.hidden = false;
        note.textContent = String(text || '');
    }

    function hideNote() {
        if (!note) return;
        note.hidden = true;
        note.textContent = '';
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (formDirty) {
                const ok = window.confirm('Сбросить все ответы? Это действие нельзя отменить.');
                if (!ok) return;
            }

            form.reset();
            hideNote();
            formDirty = false;
        });

    }

    if (finishBtn) {
        finishBtn.addEventListener('click', () => {
			const ok = window.confirm('Закончить тест? После этого ответы менять не получится (пока это заглушка).');
			if (!ok) return;

			formDirty = false;

            hideNote();

            const qCards = form.querySelectorAll('[data-question-card]');
            let answered = 0;

            qCards.forEach((card) => {
                const hasChecked = card.querySelector('input[type="radio"]:checked, input[type="checkbox"]:checked');
                const textInput = card.querySelector('input[type="text"]');

                if (hasChecked) {
                    answered += 1;
                    return;
                }

                if (textInput && String(textInput.value || '').trim() !== '') {
                    answered += 1;
                }
            });

            showNote(`Ответов заполнено: ${answered} из ${qCards.length}. (Пока без подсчёта результата и без сохранения)`);
        });
    }
})();
