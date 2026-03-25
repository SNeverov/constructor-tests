(() => {
  const form = document.querySelector("[data-rating-form]");
  const block = document.querySelector("[data-rating-block]");
  if (!form || !block) return;

  const stars = Array.from(form.querySelectorAll("[data-rating-value]"));
  if (!stars.length) return;

  const avgNode = block.querySelector("[data-rating-avg-text]");
  const countNode = block.querySelector("[data-rating-count-text]");
  const userNode = block.querySelector("[data-rating-user-text]");
  const csrfNode = form.querySelector('input[name="csrf_token"]');
  if (!csrfNode) return;

  const setActive = (value) => {
    stars.forEach((btn) => {
      const starValue = Number(btn.getAttribute("data-rating-value") || "0");
      btn.classList.toggle("is-active", starValue <= value);
    });
  };

  const current = Number(block.getAttribute("data-rating-current") || "0");
  setActive(current);

  let pending = false;
  stars.forEach((btn) => {
    btn.addEventListener("mouseenter", () => {
      const rating = Number(btn.getAttribute("data-rating-value") || "0");
      if (Number.isFinite(rating) && rating >= 1 && rating <= 5) {
        setActive(rating);
      }
    });
  });

  const starsWrap = form.querySelector(".test-show__rating-stars");
  if (starsWrap) {
    starsWrap.addEventListener("mouseleave", () => {
      const saved = Number(block.getAttribute("data-rating-current") || "0");
      setActive(saved);
    });
  }

  stars.forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();
      if (pending) return;

      const rating = Number(btn.getAttribute("data-rating-value") || "0");
      if (!Number.isFinite(rating) || rating < 1 || rating > 5) return;

      pending = true;
      setActive(rating);

      try {
        const body = new URLSearchParams();
        body.set("csrf_token", csrfNode.value);
        body.set("rating", String(rating));

        const res = await fetch(form.action, {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
          },
          body: body.toString(),
          credentials: "same-origin",
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data || data.ok !== true) {
          throw new Error("rate-failed");
        }

        const userRating = Number(data.user_rating || rating);
        const avg = Number(data.rating_avg || 0);
        const count = Number(data.rating_count || 0);

        block.setAttribute("data-rating-current", String(userRating));
        block.setAttribute("data-rating-avg", String(avg));
        block.setAttribute("data-rating-count", String(count));
        setActive(userRating);

        if (avgNode) avgNode.textContent = avg.toFixed(1);
        if (countNode) countNode.textContent = String(count);
        if (userNode) userNode.textContent = String(userRating);
      } catch (err) {
        const prev = Number(block.getAttribute("data-rating-current") || "0");
        setActive(prev);
      } finally {
        pending = false;
      }
    });
  });
})();
