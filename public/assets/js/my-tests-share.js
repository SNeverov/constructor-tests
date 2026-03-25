(function () {
    const DEFAULT_TOOLTIP = 'Поделиться';
    const COPIED_TOOLTIP = 'Ссылка скопирована';

    async function copyText(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (_) {
            const tmp = document.createElement('textarea');
            tmp.value = text;
            document.body.appendChild(tmp);
            tmp.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(tmp);
            return ok;
        }
    }

    function flashTooltip(btn) {
        const prev = btn.getAttribute('data-tooltip') || DEFAULT_TOOLTIP;
        btn.setAttribute('data-tooltip', COPIED_TOOLTIP);
        btn.classList.add('is-tooltip-open');

        setTimeout(() => {
            btn.classList.remove('is-tooltip-open');
            btn.setAttribute('data-tooltip', prev);
        }, 1200);
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-share-copy]');
        if (!btn) return;

        const path = btn.getAttribute('data-share-copy') || '';
        if (!path) return;

        const url = window.location.origin + path;
        const ok = await copyText(url);
        if (ok) flashTooltip(btn);
    });
})();
