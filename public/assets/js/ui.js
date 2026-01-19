// ui.js — модал подтверждения + тосты

(function () {
  "use strict";

	function flipAnimateList(items, firstRects) {
		// после изменения DOM: считаем новые позиции и анимируем разницу
		items.forEach((el) => {
			const first = firstRects.get(el);
			if (!first) return;

			const last = el.getBoundingClientRect();
			const dx = first.left - last.left;
			const dy = first.top - last.top;

			if (dx === 0 && dy === 0) return;

			el.style.transform = `translate(${dx}px, ${dy}px)`;
			el.style.transition = "transform 0ms";
		});

		// в следующий кадр включаем плавный переход обратно к transform: none
		requestAnimationFrame(() => {
			items.forEach((el) => {
			if (!firstRects.has(el)) return;
			el.style.transition = "transform 380ms cubic-bezier(.22,.61,.36,1)";
			el.style.transform = "";
			});

			// чистим инлайны после окончания
			setTimeout(() => {
			items.forEach((el) => {
				el.style.transition = "";
				el.style.transform = "";
			});
			}, 420);
		});
	}


  // ---------- Toast ----------
  const toastStack = (function ensureToastStack() {
    let el = document.querySelector(".ui-toast-stack");
    if (!el) {
      el = document.createElement("div");
      el.className = "ui-toast-stack";
      document.body.appendChild(el);
    }
    return el;
  })();

  function toastShow(text, type = "success", timeoutMs = 8000) {
    const toast = document.createElement("div");
    toast.className = `ui-toast ui-toast--${type}`;

    toast.innerHTML = `
      <div class="ui-toast__dot" aria-hidden="true"></div>
      <div class="ui-toast__text"></div>
      <button type="button" class="ui-toast__close" aria-label="Закрыть">✕</button>
    `;

    const textEl = toast.querySelector(".ui-toast__text");
    const closeBtn = toast.querySelector(".ui-toast__close");
    textEl.textContent = String(text || "");

    closeBtn.addEventListener("click", () => {
      toast.classList.remove("is-show");
      setTimeout(() => toast.remove(), 420);
    });

    toastStack.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("is-show"));

    if (timeoutMs > 0) {
      setTimeout(() => {
        if (!toast.isConnected) return;
        toast.classList.remove("is-show");
        setTimeout(() => toast.remove(), 420);
      }, timeoutMs);
    }
  }

  // ---------- Confirm modal ----------
  function ensureConfirmModal() {
    let backdrop = document.querySelector(".ui-backdrop[data-ui-confirm]");
    if (backdrop) return backdrop;

    backdrop = document.createElement("div");
    backdrop.className = "ui-backdrop";
    backdrop.setAttribute("data-ui-confirm", "1");
    backdrop.innerHTML = `
      <div class="ui-modal" role="dialog" aria-modal="true" aria-labelledby="uiConfirmTitle">
        <div class="ui-modal__head">
          <h3 class="ui-modal__title" id="uiConfirmTitle">Подтверди действие</h3>
        </div>
        <div class="ui-modal__body">
          <p class="ui-modal__text">Точно выполнить действие?</p>
        </div>
        <div class="ui-modal__foot">
          <button type="button" class="btn btn--ghost" data-ui-cancel>Отмена</button>
          <button type="button" class="btn btn--danger" data-ui-ok>Удалить</button>
        </div>
      </div>
    `;

    document.body.appendChild(backdrop);

    // клик по фону = отмена
    backdrop.addEventListener("click", (e) => {
      if (e.target === backdrop) {
        closeConfirm(false);
      }
    });

    return backdrop;
  }

  let confirmResolver = null;

  function openConfirm({ title, text, okText }) {
    const backdrop = ensureConfirmModal();
    const titleEl = backdrop.querySelector(".ui-modal__title");
    const textEl = backdrop.querySelector(".ui-modal__text");
    const okBtn = backdrop.querySelector("[data-ui-ok]");
    const cancelBtn = backdrop.querySelector("[data-ui-cancel]");

    titleEl.textContent = title || "Подтверди действие";
    textEl.textContent = text || "Точно выполнить действие?";
    okBtn.textContent = okText || "Ок";

    function onOk() {
      closeConfirm(true);
    }
    function onCancel() {
      closeConfirm(false);
    }
    function onEsc(e) {
      if (e.key === "Escape") closeConfirm(false);
    }

    okBtn.addEventListener("click", onOk, { once: true });
    cancelBtn.addEventListener("click", onCancel, { once: true });
    window.addEventListener("keydown", onEsc);

    requestAnimationFrame(() => {
		backdrop.classList.add("is-open");
	});


    return new Promise((resolve) => {
      confirmResolver = (val) => {
        window.removeEventListener("keydown", onEsc);
        resolve(val);
      };
    });
  }

  function closeConfirm(result) {
    const backdrop = document.querySelector(".ui-backdrop[data-ui-confirm]");
    if (!backdrop) return;

    backdrop.classList.remove("is-open");

    if (typeof confirmResolver === "function") {
      const r = confirmResolver;
      confirmResolver = null;
      r(result);
    }
  }

  // ---------- Wiring ----------
  // Атрибуты:
  // data-confirm="1"
  // data-confirm-title="..."
  // data-confirm-text="..."
  // data-confirm-ok="..."
	document.addEventListener("submit", async (e) => {
		const form = e.target;
		if (!(form instanceof HTMLFormElement)) return;

		if (!form.hasAttribute("data-confirm")) return;

		e.preventDefault();

		const title = form.getAttribute("data-confirm-title") || "Подтверди удаление";
		const text = form.getAttribute("data-confirm-text") || "Точно удалить?";
		const okText = form.getAttribute("data-confirm-ok") || "Удалить";

		const ok = await openConfirm({ title, text, okText });
		if (!ok) return;

		// --- АНИМАЦИЯ УДАЛЕНИЯ КАРТОЧКИ ---
		const card = form.closest(".test-card");
			if (card) {
				const list = card.parentElement;

				// Берём все карточки в этом списке (кроме удаляемой) и фиксируем их позиции
				const others = list
				? Array.from(list.querySelectorAll(".test-card")).filter((el) => el !== card)
				: [];

				const firstRects = new Map();
				others.forEach((el) => firstRects.set(el, el.getBoundingClientRect()));

				// Запускаем exit-анимацию удаляемой карточки
				card.classList.add("ui-removing");

				// Через 260ms (длительность exit-анимации) прячем карточку из потока
				setTimeout(() => {
				card.style.display = "none";

				// FLIP: остальные карточки плавно "подъезжают" на новое место
				flipAnimateList(others, firstRects);

				// И уже после старта анимации — реально отправляем форму
				setTimeout(() => {
					try {
						sessionStorage.setItem("uiScroll:/my/tests", String(window.scrollY || 0));
					} catch (e) {}
					form.submit();
					}, 120);
				}, 260);
			} else {
				form.submit();
			}

	});


  // ---------- Flash toast from server ----------
  // layout.php может поставить: <body data-toast='{"type":"success","text":"..."}'>
	document.addEventListener("DOMContentLoaded", () => {
	// --- Restore scroll (after POST/redirect) ---
	try {
		const key = "uiScroll:/my/tests";
		const saved = sessionStorage.getItem(key);
		if (saved) {
		sessionStorage.removeItem(key);
		const y = parseInt(saved, 10);
		if (!Number.isNaN(y)) {
			// даём странице дорендериться и потом возвращаем позицию
			requestAnimationFrame(() => {
			window.scrollTo(0, y);
			});
		}
		}
	} catch (e) {
		// молча игнорируем
	}

	// --- Flash toast from server ---
	const body = document.body;
	const raw = body ? body.getAttribute("data-toast") : "";
	if (!raw) return;

	try {
		const data = JSON.parse(raw);
		if (data && data.text) {
		toastShow(data.text, data.type || "success", 8000);
		}
	} catch (err) {
		// молча игнорируем
	}
	});

})();
