// ─── Lightbox ────────────────────────────────────────────────────────────────

let _lbScrollY = 0;

function lockScroll() {
	_lbScrollY = window.scrollY;
}

function unlockScroll() {
	window.scrollTo(0, _lbScrollY);
}

function openLightbox(src, fullSize) {
	const lb = document.getElementById('imgLightbox');
	const img = document.getElementById('imgLightboxImg');
	if (!lb || !img) return;

	img.style.width = '';
	img.style.height = '';
	img.src = '';
	lb.setAttribute('aria-hidden', 'false');
	lockScroll();

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
	lb.addEventListener(
		'transitionend',
		() => {
			lb.classList.remove('is-open', 'is-closing');
			lb.setAttribute('aria-hidden', 'true');
			unlockScroll();
		},
		{ once: true },
	);
}

document.addEventListener('click', (e) => {
	if (e.target.closest('[data-lightbox-close]')) {
		e.preventDefault();
		closeLightbox();
		return;
	}

	const zoomImg = e.target.closest('.qcard__image-img, .opt__image');
	if (zoomImg && zoomImg.src) {
		e.preventDefault();
		e.stopPropagation();
		openLightbox(zoomImg.src, true);
	}
});

document.addEventListener('keydown', (e) => {
	if (e.key === 'Escape') closeLightbox();
});

// ─── Test pass ───────────────────────────────────────────────────────────────

const testPassRoot = document.querySelector('.test-pass');
const testId = testPassRoot?.dataset.testId;
const timeLimitSec = Number.parseInt(testPassRoot?.dataset.timeLimitSec || '0', 10) || 0;
const initialRemainingSecRaw = testPassRoot?.dataset.remainingSec ?? '';
const initialRemainingSec =
	initialRemainingSecRaw === '' ? null : Number.parseInt(initialRemainingSecRaw, 10) || 0;

if (!testId) {
	console.warn('test-pass: testId not found');
}

function getStorageKey() {
	return `test-progress:${testId}`;
}

function updateOrderNums(list) {
	list.querySelectorAll('[data-order-item]').forEach((item, idx) => {
		const num = item.querySelector('[data-order-num]');
		if (num) num.textContent = idx + 1;
	});
}

function initOrderInitialState() {
	document.querySelectorAll('[data-order-list]').forEach((list) => {
		if (list.dataset.initialOrder) return;
		const ids = [...list.querySelectorAll('[data-order-item]')].map((item) =>
			String(item.dataset.optId || ''),
		);
		list.dataset.initialOrder = ids.join(',');
	});
}

function isOrderListInteracted(list) {
	const currentOrder = [...list.querySelectorAll('[data-order-item]')]
		.map((item) => String(item.dataset.optId || ''))
		.join(',');
	return currentOrder !== (list.dataset.initialOrder || '');
}

function isQuestionInteracted(card) {
	if (!card) return false;
	const type = card.dataset.questionType || '';

	if (type === 'input') {
		const input = card.querySelector('input[type="text"]');
		return Boolean(input && input.value.trim() !== '');
	}

	if (type === 'order') {
		const list = card.querySelector('[data-order-list]');
		return Boolean(list && isOrderListInteracted(list));
	}

	return Boolean(
		card.querySelector('input[type="radio"]:checked, input[type="checkbox"]:checked'),
	);
}

function updateQuestionNavigator() {
	const nav = document.querySelector('[data-question-nav]');
	if (!nav) return;

	const cards = [...document.querySelectorAll('[data-question-card]')];
	const total = cards.length;
	let completed = 0;

	cards.forEach((card, idx) => {
		const questionIndex = card.dataset.questionIndex || String(idx + 1);
		const button = nav.querySelector(`[data-question-nav-btn="${CSS.escape(questionIndex)}"]`);
		const interacted = isQuestionInteracted(card);
		if (interacted) completed += 1;
		if (!button) return;
		button.classList.toggle('btn-outline', !interacted);
		button.classList.toggle('btn-primary', interacted);
		button.setAttribute(
			'aria-label',
			`Перейти к вопросу ${questionIndex}${interacted ? ' - есть ответ' : ' - нет ответа'}`,
		);
	});

	const countNode = nav.querySelector('[data-question-nav-count]');
	if (countNode) countNode.textContent = `${completed} / ${total}`;

	const progressNode = nav.querySelector('[data-question-nav-progress]');
	if (progressNode) {
		const percent = total > 0 ? (completed / total) * 100 : 0;
		progressNode.style.width = `${percent}%`;
	}
}

function initQuestionNavigatorScroll() {
	const nav = document.querySelector('[data-question-nav]');
	if (!nav) return;

	nav.addEventListener('click', (e) => {
		const button = e.target.closest('[data-question-nav-btn]');
		if (!button) return;

		const targetId = button.getAttribute('href') || '';
		if (!targetId.startsWith('#')) return;

		const target = document.getElementById(targetId.slice(1));
		if (!target) return;

		e.preventDefault();

		const form = document.getElementById('testPassForm');
		const formStyles = form ? window.getComputedStyle(form) : null;
		const navStyles = window.getComputedStyle(nav);
		const cardGap = Number.parseFloat(formStyles?.rowGap || formStyles?.gap || '14') || 14;
		const stickyTop = Number.parseFloat(navStyles.top || '0') || 0;
		const targetTop =
			target.getBoundingClientRect().top +
			window.scrollY -
			nav.getBoundingClientRect().height -
			stickyTop -
			cardGap;

		window.scrollTo({
			top: Math.max(0, targetTop),
			behavior: 'smooth',
		});

		if (window.history?.pushState) {
			window.history.pushState(null, '', targetId);
		}
	});
}

function saveProgress() {
	if (!testId) return;

	const data = {};

	document.querySelectorAll('input, textarea').forEach((el) => {
		if (!el.name) return;
		// пропускаем скрытые инпуты внутри order-списков — они сохраняются отдельно
		if (el.closest('[data-order-list]')) return;

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

	// сохраняем текущий порядок каждого order-списка
	document.querySelectorAll('[data-order-list]').forEach((list) => {
		const qid = list.dataset.qid;
		if (!qid) return;
		if (!isOrderListInteracted(list)) return;
		const ids = [];
		list.querySelectorAll('[data-order-item]').forEach((item) => {
			ids.push(String(item.dataset.optId));
		});
		data['__order__' + qid] = ids;
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
		// восстанавливаем порядок order-списка
		if (name.startsWith('__order__')) {
			const qid = name.replace('__order__', '');
			const savedIds = Array.isArray(value) ? value.map(String) : [];
			if (!savedIds.length) return;

			const list = document.querySelector(`[data-order-list][data-qid="${CSS.escape(qid)}"]`);
			if (!list) return;

			const items = [...list.querySelectorAll('[data-order-item]')];
			const idToItem = {};
			items.forEach((item) => {
				idToItem[String(item.dataset.optId)] = item;
			});

			savedIds.forEach((id) => {
				const item = idToItem[id];
				if (item) list.appendChild(item);
			});
			updateOrderNums(list);
			return;
		}

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

initOrderInitialState();
initQuestionNavigatorScroll();

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

			// Сбрасываем визуальные стили всех карточек
			document.querySelectorAll('[data-question-card]').forEach((card) => {
				card.querySelectorAll('.opt').forEach((opt) => {
					opt.classList.remove('opt--correct', 'opt--wrong');
				});
				card.querySelectorAll('.order-item').forEach((item) => {
					item.classList.remove('order-item--correct', 'order-item--wrong');
				});
				card.querySelectorAll('input[type="text"]').forEach((inp) => {
					inp.classList.remove('input--wrong');
				});
				const resultEl = card.querySelector('.qcard__answer-result');
				if (resultEl) resultEl.remove();
				card.dataset.answered = '';
				card.classList.remove('qcard--answered');
				const answerBtn = card.querySelector('[data-answer-btn]');
				if (answerBtn) {
					answerBtn.disabled = false;
					answerBtn.textContent = 'Ответить';
				}
			});

			if (testId) localStorage.removeItem(getStorageKey());
			updateQuestionNavigator();
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
			finishBtn.textContent =
				form.dataset.autoSubmit === '1' ? 'Завершаем...' : 'Сохраняем...';
		}
		if (resetBtn) {
			resetBtn.disabled = true;
		}
	});
})();

document.addEventListener(
	'change',
	(e) => {
		if (e.target.closest('.test-pass')) {
			saveProgress();
			updateQuestionNavigator();
		}
	},
	true,
);

document.addEventListener(
	'input',
	(e) => {
		if (e.target.closest('.test-pass')) {
			saveProgress();
			updateQuestionNavigator();
		}
	},
	true,
);

restoreProgress();
updateQuestionNavigator();

document.addEventListener(
	'submit',
	(e) => {
		const form = e.target;
		if (form && form.id === 'testPassForm') {
			localStorage.removeItem(getStorageKey());
		}
	},
	true,
);

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

// ─── Order questions (drag-and-drop + move buttons) ──────────────────────────

(function () {
	let dragSrc = null;

	function onDragStart(e) {
		dragSrc = e.currentTarget;
		dragSrc.classList.add('is-dragging');
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/plain', '');
	}

	function onDragEnd() {
		if (dragSrc) dragSrc.classList.remove('is-dragging');
		dragSrc = null;
		document
			.querySelectorAll('.order-item.drag-over')
			.forEach((el) => el.classList.remove('drag-over'));
	}

	function onDragOver(e) {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		const target = e.currentTarget;
		if (target !== dragSrc) {
			document
				.querySelectorAll('.order-item.drag-over')
				.forEach((el) => el.classList.remove('drag-over'));
			target.classList.add('drag-over');
		}
	}

	function onDragLeave(e) {
		e.currentTarget.classList.remove('drag-over');
	}

	function onDrop(e) {
		e.preventDefault();
		const target = e.currentTarget;
		target.classList.remove('drag-over');
		if (!dragSrc || dragSrc === target) return;

		const list = target.closest('[data-order-list]');
		if (!list) return;

		const items = [...list.querySelectorAll('[data-order-item]')];
		const srcIdx = items.indexOf(dragSrc);
		const tgtIdx = items.indexOf(target);

		if (srcIdx < tgtIdx) {
			target.after(dragSrc);
		} else {
			target.before(dragSrc);
		}

		updateOrderNums(list);
		saveProgress();
		updateQuestionNavigator();
		list.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function initOrderItem(item) {
		if (item.dataset.orderInited === '1') return;
		item.dataset.orderInited = '1';
		item.setAttribute('draggable', 'true');
		item.addEventListener('dragstart', onDragStart);
		item.addEventListener('dragend', onDragEnd);
		item.addEventListener('dragover', onDragOver);
		item.addEventListener('dragleave', onDragLeave);
		item.addEventListener('drop', onDrop);
	}

	document.querySelectorAll('[data-order-item]').forEach(initOrderItem);

	// кнопки вверх / вниз
	document.addEventListener('click', (e) => {
		const upBtn = e.target.closest('[data-order-up]');
		const downBtn = e.target.closest('[data-order-down]');
		const btn = upBtn || downBtn;
		if (!btn) return;

		const item = btn.closest('[data-order-item]');
		const list = btn.closest('[data-order-list]');
		if (!item || !list) return;

		const items = [...list.querySelectorAll('[data-order-item]')];
		const idx = items.indexOf(item);

		if (upBtn && idx > 0) {
			items[idx - 1].before(item);
		} else if (downBtn && idx < items.length - 1) {
			items[idx + 1].after(item);
		} else {
			return;
		}

		updateOrderNums(list);
		saveProgress();
		updateQuestionNavigator();
		list.dispatchEvent(new Event('change', { bubbles: true }));
	});
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
		{ threshold: 0 },
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

// ─── Show-answers mode: «Ответить» / «Сбросить» buttons ─────────────────────

(function () {
	const root = document.querySelector('.test-pass[data-show-answers="1"]');
	if (!root) return;

	function normalizeAnswer(s) {
		return s.trim().replace(/\s+/g, ' ').toLowerCase().replace(/ё/g, 'е');
	}

	function lockCard(card) {
		card.dataset.answered = '1';
		card.classList.add('qcard--answered');
		const btn = card.querySelector('[data-answer-btn]');
		if (btn) {
			btn.disabled = true;
			btn.textContent = 'Отвечено';
		}
	}

	function resetCard(card) {
		// Сбрасываем значения
		card.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach((inp) => {
			inp.checked = false;
		});
		card.querySelectorAll('input[type="text"]').forEach((inp) => {
			inp.value = '';
			inp.classList.remove('input--wrong');
		});

		// Убираем визуальные классы вариантов
		card.querySelectorAll('.opt').forEach((opt) => {
			opt.classList.remove('opt--correct', 'opt--wrong');
		});

		// Убираем визуальные классы order-элементов
		card.querySelectorAll('.order-item').forEach((item) => {
			item.classList.remove('order-item--correct', 'order-item--wrong');
		});

		// Убираем баннер результата
		const resultEl = card.querySelector('.qcard__answer-result');
		if (resultEl) resultEl.remove();

		// Разблокируем карточку
		card.dataset.answered = '';
		card.classList.remove('qcard--answered');

		const answerBtn = card.querySelector('[data-answer-btn]');
		if (answerBtn) {
			answerBtn.disabled = false;
			answerBtn.textContent = 'Ответить';
		}

		saveProgress();
		updateQuestionNavigator();
	}

	function handleOrderAnswer(card) {
		const list = card.querySelector('[data-order-list]');
		if (!list) return;

		let correctOrder = [];
		try {
			correctOrder = JSON.parse(list.dataset.correctOrder || '[]').map(String);
		} catch {
			return;
		}

		if (correctOrder.length === 0) return;

		const items = [...list.querySelectorAll('[data-order-item]')];
		let allCorrect = true;

		items.forEach((item, idx) => {
			const isCorrectPos = correctOrder[idx] === String(item.dataset.optId);
			if (!isCorrectPos) allCorrect = false;
			item.classList.toggle('order-item--correct', isCorrectPos);
			item.classList.toggle('order-item--wrong', !isCorrectPos);
		});

		showResult(
			card,
			allCorrect ? 'correct' : 'wrong',
			allCorrect ? 'Правильно!' : 'Неправильно',
		);
		lockCard(card);
	}

	function showResult(card, type, text) {
		let el = card.querySelector('.qcard__answer-result');
		if (!el) {
			el = document.createElement('div');
			card.querySelector('.qcard__foot').before(el);
		}
		el.className = 'qcard__answer-result qcard__answer-result--' + type;
		el.textContent = text;
	}

	function handleOptionAnswer(card) {
		const qType = card.dataset.questionType;
		const allOpts = [...card.querySelectorAll('.opt')];
		const correctLabels = card.querySelectorAll('.opt[data-is-correct]');

		const correctIds = new Set();
		correctLabels.forEach((label) => {
			const inp = label.querySelector('input');
			if (inp) correctIds.add(inp.value);
		});

		const selectedIds = new Set();
		allOpts.forEach((label) => {
			const inp = label.querySelector('input[type="radio"], input[type="checkbox"]');
			if (inp && inp.checked) selectedIds.add(inp.value);
		});

		if (selectedIds.size === 0) {
			showResult(card, 'warn', 'Выберите вариант ответа');
			return;
		}

		let isCorrect;
		if (qType === 'radio') {
			isCorrect = selectedIds.size === 1 && correctIds.has([...selectedIds][0]);
		} else {
			isCorrect =
				selectedIds.size === correctIds.size &&
				[...selectedIds].every((id) => correctIds.has(id));
		}

		allOpts.forEach((label) => {
			const inp = label.querySelector('input[type="radio"], input[type="checkbox"]');
			if (!inp) return;
			const correct = correctIds.has(inp.value);
			const selected = selectedIds.has(inp.value);
			if (correct) {
				label.classList.add('opt--correct');
			} else if (selected) {
				label.classList.add('opt--wrong');
			}
		});

		showResult(card, isCorrect ? 'correct' : 'wrong', isCorrect ? 'Правильно!' : 'Неправильно');
		lockCard(card);
	}

	function handleInputAnswer(card) {
		const inp = card.querySelector('input[type="text"]');
		if (!inp) return;

		let correctAnswers = [];
		try {
			correctAnswers = JSON.parse(inp.dataset.correctTextAnswers || '[]');
		} catch {
			return;
		}

		const userNorm = normalizeAnswer(inp.value);
		if (userNorm === '') {
			showResult(card, 'warn', 'Введите ответ');
			return;
		}

		const isCorrect = correctAnswers.includes(userNorm);
		inp.classList.toggle('input--wrong', !isCorrect);

		if (isCorrect) {
			showResult(card, 'correct', 'Правильно!');
		} else {
			const display = correctAnswers.join(' / ');
			showResult(card, 'wrong', 'Неправильно. Правильный ответ: ' + display);
		}
		lockCard(card);
	}

	document.addEventListener('click', (e) => {
		// Кнопка «Сбросить» на карточке
		const resetCardBtn = e.target.closest('[data-reset-btn]');
		if (resetCardBtn) {
			const card = resetCardBtn.closest('[data-question-card]');
			if (card) resetCard(card);
			return;
		}

		// Кнопка «Ответить»
		const answerBtn = e.target.closest('[data-answer-btn]');
		if (!answerBtn) return;

		const card = answerBtn.closest('[data-question-card]');
		if (!card || card.dataset.answered === '1') return;

		const qType = card.dataset.questionType;
		if (qType === 'input') {
			handleInputAnswer(card);
		} else if (qType === 'order') {
			handleOrderAnswer(card);
		} else {
			handleOptionAnswer(card);
		}
	});
})();
