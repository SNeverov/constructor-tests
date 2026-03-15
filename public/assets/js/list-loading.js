(function () {
  "use strict";

  function showLoading(shell) {
    if (!shell || shell.classList.contains("is-list-loading")) return;
    shell.classList.add("is-list-loading");

    const loading = shell.querySelector("[data-list-loading]");
    if (loading) {
      loading.removeAttribute("hidden");
      loading.setAttribute("aria-hidden", "false");
    }
  }

  document.addEventListener("submit", (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.hasAttribute("data-confirm")) return;

    const shell = form.closest("[data-list-shell]");
    if (!shell) return;

    showLoading(shell);
  }, true);

  document.addEventListener("click", (e) => {
    const link = e.target.closest("a");
    if (!link) return;

    const shell = link.closest("[data-list-shell]");
    if (!shell) return;

    if (!link.closest(".results-pagination")) return;
    showLoading(shell);
  });
})();
