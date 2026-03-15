(function () {
  "use strict";

  const THRESHOLD = 420;
  const btn = document.getElementById("scrollTopBtn");
  if (!btn) return;

  let ticking = false;

  function updateVisibility() {
    const y = window.scrollY || window.pageYOffset || 0;
    if (y > THRESHOLD) {
      btn.classList.add("is-visible");
    } else {
      btn.classList.remove("is-visible");
    }
    ticking = false;
  }

  function onScroll() {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(updateVisibility);
  }

  btn.addEventListener("click", function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  window.addEventListener("scroll", onScroll, { passive: true });
  updateVisibility();
})();
