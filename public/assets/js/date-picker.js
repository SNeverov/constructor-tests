(function () {
  "use strict";

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-date-open]");
    if (!btn) return;

    const wrap = btn.closest(".date-field");
    if (!wrap) return;

    const input = wrap.querySelector("[data-date-input]");
    if (!(input instanceof HTMLInputElement)) return;

    input.focus();
    if (typeof input.showPicker === "function") {
      input.showPicker();
    }
  });
})();
