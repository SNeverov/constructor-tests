// ─── Lightbox ────────────────────────────────────────────────────────────────

function openLightbox(src, fullSize) {
    const lb = document.getElementById('imgLightbox');
    const img = document.getElementById('imgLightboxImg');
    if (!lb || !img) return;

    img.style.width = '';
    img.style.height = '';
    img.src = '';
    lb.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    const tmp = new Image();
    tmp.onload = () => {
        if (!fullSize) {
            const maxSide = 500;
            const w = tmp.naturalWidth;
            const h = tmp.naturalHeight;
            if (w > maxSide || h > maxSide) {
                const ratio = Math.min(maxSide / w, maxSide / h);
                img.style.width = Math.round(w * ratio) + 'px';
                img.style.height = Math.round(h * ratio) + 'px';
            }
        }
        img.src = src;
        requestAnimationFrame(() => lb.classList.add('is-open'));
    };
    tmp.src = src;
}

function closeLightbox() {
    const lb = document.getElementById('imgLightbox');
    if (!lb) return;
    lb.classList.add('is-closing');
    lb.addEventListener('transitionend', () => {
        lb.classList.remove('is-open', 'is-closing');
        lb.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }, { once: true });
}

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-lightbox-close]')) { closeLightbox(); return; }

    const zoomImg = e.target.closest('.qcard__image-img, .opt__image');
    if (zoomImg && zoomImg.src) openLightbox(zoomImg.src, true);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
});

// ─── Test pass ───────────────────────────────────────────────────────────────

const testPassRoot = document.querySelector('.test-pass');
const testId = testPassRoot?.dataset.testId;
const timeLimitSec = Number.parseInt(testPassRoot?.dataset.timeLimitSec || '0', 10) || 0;
const initialRemainingSecRaw = testPassRoot?.dataset.remainingSec ?? '';
const initialRemainingSec = initialRemainingSecRaw === '' ? null : (Number.parseInt(initialRemainingSecRaw, 10) || 0);

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
        finishBtn.addEventListener('click', (e) => {
            if (form.dataset.autoSubmit === '1') {
                formDirty = false;
                hideNote();
                return;
            }

			const ok = window.confirm('Закончить тест? После этого ответы менять не получится.');
			if (!ok) {
                e.preventDefault();
                return;
            }

			formDirty = false;
            hideNote();
        });
    }

    form.addEventListener('submit', () => {
        formDirty = false;
        if (form.dataset.autoSubmit !== '1') {
            hideNote();
        } else {
            showNote('Время истекло. Отправляем ответы...');
        }

        if (finishBtn) {
            finishBtn.disabled = true;
            finishBtn.textContent = form.dataset.autoSubmit === '1' ? 'Завершаем...' : 'Сохраняем...';
        }
        if (resetBtn) {
            resetBtn.disabled = true;
        }
    });
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

(function () {
    if (!testPassRoot || timeLimitSec <= 0 || initialRemainingSec === null) return;

    const form = document.getElementById('testPassForm');
    const timer = testPassRoot.querySelector('[data-test-pass-timer]');
    const valueNode = testPassRoot.querySelector('[data-timer-value]');
    const noteNode = testPassRoot.querySelector('[data-timer-note]');
    const finishBtn = document.getElementById('finishTestBtn');
    const resetBtn = document.getElementById('resetAnswersBtn');

    if (!form || !timer || !valueNode) return;

    let remaining = Math.max(0, initialRemainingSec);
    let autoSubmitting = false;

    const formatCountdown = (totalSec) => {
        const safe = Math.max(0, totalSec);
        const hours = Math.floor(safe / 3600);
        const minutes = Math.floor((safe % 3600) / 60);
        const seconds = safe % 60;
        return [hours, minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':');
    };

    const syncTimerState = () => {
        valueNode.textContent = formatCountdown(remaining);

        timer.classList.toggle('is-warning', remaining > 0 && remaining <= 300);
        timer.classList.toggle('is-danger', remaining > 0 && remaining <= 60);

        if (!noteNode) return;
        if (remaining > 300) {
            noteNode.textContent = 'По истечении лимита попытка завершится автоматически.';
        } else if (remaining > 60) {
            noteNode.textContent = 'До автозавершения осталось меньше 5 минут.';
        } else if (remaining > 0) {
            noteNode.textContent = 'До автозавершения осталась меньше минуты.';
        } else {
            noteNode.textContent = 'Время истекло, отправляем ответы.';
        }
    };

    const submitExpiredAttempt = () => {
        if (autoSubmitting) return;
        autoSubmitting = true;

        if (finishBtn) {
            finishBtn.disabled = true;
            finishBtn.textContent = 'Время истекло...';
        }
        if (resetBtn) {
            resetBtn.disabled = true;
        }

        form.dataset.autoSubmit = '1';
        syncTimerState();
        form.requestSubmit();
    };

    syncTimerState();
    if (remaining <= 0) {
        submitExpiredAttempt();
        return;
    }

    const timerId = window.setInterval(() => {
        remaining -= 1;
        syncTimerState();

        if (remaining <= 0) {
            window.clearInterval(timerId);
            submitExpiredAttempt();
        }
    }, 1000);
})();

// ─── Floating timer pill ─────────────────────────────────────────────────────

(function () {
    const headerTimer = testPassRoot?.querySelector('[data-test-pass-timer]');
    const pill = document.querySelector('[data-timer-pill]');
    const pillValue = pill?.querySelector('[data-timer-pill-value]');
    const headerValue = testPassRoot?.querySelector('[data-timer-value]');

    if (!headerTimer || !pill || !pillValue || !headerValue) return;

    const syncPill = () => {
        pillValue.textContent = headerValue.textContent;
        pill.classList.toggle('is-warning', headerTimer.classList.contains('is-warning'));
        pill.classList.toggle('is-danger', headerTimer.classList.contains('is-danger'));
    };

    new IntersectionObserver(
        ([entry]) => {
            const hidden = !entry.isIntersecting;
            pill.classList.toggle('is-visible', hidden);
            pill.setAttribute('aria-hidden', hidden ? 'false' : 'true');
            if (hidden) syncPill();
        },
        { threshold: 0 }
    ).observe(headerTimer);

    new MutationObserver(syncPill).observe(headerValue, {
        childList: true,
        characterData: true,
        subtree: true,
    });

    new MutationObserver(syncPill).observe(headerTimer, {
        attributes: true,
        attributeFilter: ['class'],
    });
})();
