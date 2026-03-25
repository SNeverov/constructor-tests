(() => {
  const backdrop = document.querySelector('[data-rate-modal="1"]');
  if (!backdrop) return;

  const form = backdrop.querySelector('[data-rate-modal-form]');
  const starsWrap = backdrop.querySelector('[data-rate-modal-stars]');
  const resultNode = backdrop.querySelector('[data-rate-modal-result]');
  const laterBtn = backdrop.querySelector('[data-rate-modal-later]');
  if (!form || !starsWrap || !resultNode) return;

  const stars = Array.from(starsWrap.querySelectorAll('[data-rate-value]'));
  const csrfNode = form.querySelector('input[name="csrf_token"]');
  if (!csrfNode || !stars.length) return;

  let pending = false;

  const closeModal = () => {
    backdrop.classList.remove('is-open');
    backdrop.setAttribute('aria-hidden', 'true');
    setTimeout(() => {
      if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
    }, 220);
  };

  const paint = (value, className = 'is-hover') => {
    stars.forEach((star) => {
      const v = Number(star.getAttribute('data-rate-value') || '0');
      star.classList.remove('is-hover', 'is-active');
      if (v <= value) {
        star.classList.add(className);
      }
    });
  };

  stars.forEach((star) => {
    star.addEventListener('mouseenter', () => {
      const value = Number(star.getAttribute('data-rate-value') || '0');
      if (value >= 1 && value <= 5) paint(value, 'is-hover');
    });
  });

  starsWrap.addEventListener('mouseleave', () => {
    stars.forEach((star) => star.classList.remove('is-hover'));
  });

  stars.forEach((star) => {
    star.addEventListener('click', async (e) => {
      e.preventDefault();
      if (pending) return;

      const value = Number(star.getAttribute('data-rate-value') || '0');
      if (!Number.isFinite(value) || value < 1 || value > 5) return;

      pending = true;
      paint(value, 'is-active');

      const body = new URLSearchParams();
      body.set('csrf_token', csrfNode.value);
      body.set('rating', String(value));

      try {
        const res = await fetch(form.action, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          },
          credentials: 'same-origin',
          body: body.toString(),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data || data.ok !== true) {
          throw new Error('rate-failed');
        }

        starsWrap.classList.add('is-hidden');
        resultNode.classList.add('is-show');
        resultNode.textContent = `Вы оценили на ${value}`;

        setTimeout(() => {
          closeModal();
        }, 1850);
      } catch (err) {
        starsWrap.classList.remove('is-hidden');
        resultNode.classList.add('is-show');
        resultNode.textContent = 'Не удалось сохранить оценку';
      } finally {
        pending = false;
      }
    });
  });

  if (laterBtn) {
    laterBtn.addEventListener('click', () => {
      closeModal();
    });
  }

  backdrop.addEventListener('click', (e) => {
    if (e.target === backdrop) {
      closeModal();
    }
  });
})();
