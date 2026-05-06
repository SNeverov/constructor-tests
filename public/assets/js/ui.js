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

  let toastOffsetRafId = 0;
  function updateToastStackOffset() {
    const defaultTop = 16;
    const gapUnderHeader = 12;
    const header = document.querySelector(".site-header");

    let top = defaultTop;
    if (header) {
      const rect = header.getBoundingClientRect();
      const isHeaderVisible = rect.bottom > 0 && rect.top < window.innerHeight;
      if (isHeaderVisible) {
        const visibleBottom = Math.min(window.innerHeight, rect.bottom);
        top = Math.max(defaultTop, Math.round(visibleBottom + gapUnderHeader));
      }
    }

    toastStack.style.top = `${top}px`;
  }

  function queueToastStackOffsetUpdate() {
    if (toastOffsetRafId) return;
    toastOffsetRafId = requestAnimationFrame(() => {
      toastOffsetRafId = 0;
      updateToastStackOffset();
    });
  }

  window.addEventListener("scroll", queueToastStackOffsetUpdate, { passive: true });
  window.addEventListener("resize", queueToastStackOffsetUpdate);
  queueToastStackOffsetUpdate();

  // ---------- Tooltip viewport fit ----------
  const tooltipMeasureEl = (function ensureTooltipMeasureEl() {
    const el = document.createElement("div");
    el.style.position = "fixed";
    el.style.left = "-9999px";
    el.style.top = "-9999px";
    el.style.visibility = "hidden";
    el.style.pointerEvents = "none";
    el.style.maxWidth = "260px";
    el.style.padding = "7px 10px";
    el.style.border = "1px solid rgba(148, 163, 184, 0.28)";
    el.style.borderRadius = "9px";
    el.style.fontSize = "12px";
    el.style.lineHeight = "1.25";
    el.style.whiteSpace = "normal";
    el.style.fontFamily = "Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    document.body.appendChild(el);
    return el;
  })();

  function measureTooltipSize(text) {
    tooltipMeasureEl.textContent = text || "";
    const width = Math.ceil(tooltipMeasureEl.offsetWidth);
    const height = Math.ceil(tooltipMeasureEl.offsetHeight);
    return { width, height };
  }

  function fitTooltipToViewport(el) {
    if (!(el instanceof HTMLElement)) return;
    const text = el.getAttribute("data-tooltip");
    if (!text) return;

    const rect = el.getBoundingClientRect();
    const { width, height } = measureTooltipSize(text);
    const viewportW = window.innerWidth || document.documentElement.clientWidth || 0;
    const viewportH = window.innerHeight || document.documentElement.clientHeight || 0;
    const edge = 8;
    const gap = 10;

    // horizontal clamp
    const centerX = rect.left + (rect.width / 2);
    const left = centerX - (width / 2);
    let shiftX = 0;
    if (left < edge) shiftX = edge - left;
    if ((left + width) > (viewportW - edge)) shiftX = (viewportW - edge) - (left + width);
    el.style.setProperty("--tt-shift-x", `${Math.round(shiftX)}px`);

    // vertical auto flip
    el.classList.remove("ui-tooltip--force-top", "ui-tooltip--force-bottom");
    const preferredBottom = el.classList.contains("ui-tooltip--bottom");

    if (preferredBottom) {
      const bottomTop = rect.bottom + gap;
      if ((bottomTop + height) > (viewportH - edge)) {
        el.classList.add("ui-tooltip--force-top");
      } else {
        el.classList.add("ui-tooltip--force-bottom");
      }
      return;
    }

    const topBottom = rect.top - gap;
    if ((topBottom - height) < edge) {
      el.classList.add("ui-tooltip--force-bottom");
    } else {
      el.classList.add("ui-tooltip--force-top");
    }
  }

  function fitAllTooltips() {
    document.querySelectorAll(".ui-tooltip[data-tooltip]").forEach((el) => {
      fitTooltipToViewport(el);
    });
  }

  document.addEventListener("mouseover", (e) => {
    const target = e.target && e.target.closest
      ? e.target.closest(".ui-tooltip[data-tooltip]")
      : null;
    if (target) fitTooltipToViewport(target);
  });

  document.addEventListener("focusin", (e) => {
    const target = e.target && e.target.closest
      ? e.target.closest(".ui-tooltip[data-tooltip]")
      : null;
    if (target) fitTooltipToViewport(target);
  });

  window.addEventListener("resize", fitAllTooltips);
  window.addEventListener("scroll", fitAllTooltips, { passive: true });

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
    queueToastStackOffsetUpdate();
    fitAllTooltips();
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
      queueToastStackOffsetUpdate();
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

  // ---------- Auth menu dropdown ----------
  (function initAuthMenu() {
    var menu = document.getElementById("authMenu");
    var trigger = document.getElementById("authMenuTrigger");
    var dropdown = document.getElementById("authMenuDropdown");
    if (!menu || !trigger || !dropdown) return;

    function open() {
      dropdown.classList.add("is-open");
      trigger.setAttribute("aria-expanded", "true");
      dropdown.removeAttribute("aria-hidden");
    }

    function close() {
      dropdown.classList.remove("is-open");
      trigger.setAttribute("aria-expanded", "false");
      dropdown.setAttribute("aria-hidden", "true");
    }

    trigger.addEventListener("click", function (e) {
      e.stopPropagation();
      if (dropdown.classList.contains("is-open")) {
        close();
      } else {
        open();
      }
    });

    document.addEventListener("click", function (e) {
      if (!menu.contains(e.target)) close();
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") close();
    });
  })();

  // ─── Pending attempt banner dismiss ────────────────────────────────────────
  (function () {
    var banner = document.getElementById('pendingBanner');
    var closeBtn = document.getElementById('pendingBannerClose');
    if (!banner || !closeBtn) return;

    var attemptId = banner.dataset.pendingAttemptId || '';
    var storageKey = 'pending-banner-dismissed:' + attemptId;

    // Hide if already dismissed this session
    if (attemptId && sessionStorage.getItem(storageKey)) {
      banner.remove();
      return;
    }

    closeBtn.addEventListener('click', function () {
      banner.classList.add('is-hiding');
      if (attemptId) sessionStorage.setItem(storageKey, '1');
      banner.addEventListener('animationend', function () {
        banner.remove();
      }, { once: true });
    });
  })();

})();
