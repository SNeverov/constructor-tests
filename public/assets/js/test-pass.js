const testPassRoot = document.querySelector('.test-pass');
const testId = testPassRoot?.dataset.testId;

if (!testId) {
	console.warn('test-pass: testId not found');
}

function getStorageKey() {
	return `test-progress:${testId}`;
}

function saveProgress() {
	if (!testId) return;

	const data = {};

	document.querySelectorAll('input, textarea').forEach((el) => {
		if (!el.name) return;

		if (el.type === 'radio') {
			if (el.checked) {
				data[el.name] = el.value;
			}
		} else if (el.type === 'checkbox') {
			if (!data[el.name]) data[el.name] = [];
			if (el.checked) data[el.name].push(el.value);
		} else {
			data[el.name] = el.value;
		}
	});

	localStorage.setItem(getStorageKey(), JSON.stringify(data));
}

function restoreProgress() {
	if (!testId) return;

	const raw = localStorage.getItem(getStorageKey());
	if (!raw) return;

	let data;
	try {
		data = JSON.parse(raw);
	} catch {
		return;
	}

	Object.entries(data).forEach(([name, value]) => {
		const inputs = document.querySelectorAll(`[name="${CSS.escape(name)}"]`);

		inputs.forEach((el) => {
			if (el.type === 'radio') {
				el.checked = el.value === value;
			} else if (el.type === 'checkbox') {
				el.checked = Array.isArray(value) && value.includes(el.value);
			} else {
				el.value = value;
			}
		});
	});
}


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

document.addEventListener('change', (e) => {
	if (e.target.closest('.test-pass')) {
		saveProgress();
	}
}, true);

document.addEventListener('input', (e) => {
	if (e.target.closest('.test-pass')) {
		saveProgress();
	}
}, true);

restoreProgress();

document.addEventListener('submit', (e) => {
	const form = e.target;
	if (form && form.id === 'testPassForm') {
		localStorage.removeItem(getStorageKey());
	}
}, true);
