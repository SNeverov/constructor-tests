document.addEventListener('DOMContentLoaded', () => {
    const backBtn = document.querySelector('[data-nf-back]');
    if (!backBtn) return;

    backBtn.addEventListener('click', () => {
        // Если есть куда возвращаться — возвращаемся, иначе кидаем на главную
        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        window.location.href = '/';
    });
});
