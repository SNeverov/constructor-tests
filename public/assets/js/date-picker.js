(function () {
  "use strict";

  function initDatePicker(wrap) {
    const displayInput = wrap.querySelector("[data-date-input]");
    const hiddenInput = wrap.querySelector("[data-date-hidden]");
    const btn = wrap.querySelector("[data-date-open]");

    if (!(displayInput instanceof HTMLInputElement)) return;
    if (!(hiddenInput instanceof HTMLInputElement)) return;
    if (typeof AirDatepicker === "undefined") return;

    const dp = new AirDatepicker(displayInput, {
      dateFormat: "dd.MM.yyyy",
      altField: hiddenInput,
      altFieldDateFormat: "yyyy-MM-dd",
      autoClose: true,
      position: "bottom left",
      // Disable focus-triggered show — we control visibility manually
      showEvent: "",
      isMobile: window.matchMedia("(max-width: 640px)").matches,
      onShow() {
        const viewportPadding = 24;
        const maxWidth = Math.max(220, window.innerWidth - viewportPadding);
        const calendarWidth = Math.min(Math.max(displayInput.offsetWidth, 286), maxWidth);
        dp.$datepicker.style.width = calendarWidth + "px";
      },
      buttons: [
        {
          content: "Сегодня",
          onClick: (dp) => {
            dp.selectDate(new Date());
            dp.hide();
          },
        },
        {
          content: "Очистить",
          onClick: (dp) => {
            dp.clear();
            hiddenInput.value = "";
            dp.hide();
          },
        },
      ],
    });

    // Restore pre-selected value coming from the server (YYYY-MM-DD)
    const initial = hiddenInput.value;
    if (initial && /^\d{4}-\d{2}-\d{2}$/.test(initial)) {
      const [y, m, d] = initial.split("-").map(Number);
      dp.selectDate(new Date(y, m - 1, d), { silent: true });
    }

    // Clicking the text input also toggles the calendar
    displayInput.addEventListener("click", () => {
      dp.visible ? dp.hide() : dp.show();
    });

    if (btn) {
      btn.addEventListener("click", () => {
        dp.visible ? dp.hide() : dp.show();
      });
    }

    // Close when clicking anywhere outside the field and the calendar popup
    document.addEventListener("click", (e) => {
      if (!dp.visible) return;
      if (wrap.contains(e.target)) return;
      if (dp.$datepicker.contains(e.target)) return;
      dp.hide();
    });
  }

  document.querySelectorAll(".date-field").forEach(initDatePicker);
})();
