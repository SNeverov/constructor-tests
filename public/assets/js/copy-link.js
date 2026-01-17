(function () {
    function toast(el, text) {
        const label = el.querySelector('[data-copy-label]');

        if (!label) {
            const oldBtn = el.getAttribute('data-old-text') || el.textContent;
            el.setAttribute('data-old-text', oldBtn);

            el.textContent = text;
            el.classList.add('badge--copied');

            setTimeout(() => {
                el.textContent = el.getAttribute('data-old-text') || oldBtn;
                el.classList.remove('badge--copied');
            }, 900);

            return;
        }

        const old = label.getAttribute('data-old-text') || label.textContent;
        label.setAttribute('data-old-text', old);

        label.textContent = text;
        el.classList.add('badge--copied');

        setTimeout(() => {
            label.textContent = label.getAttribute('data-old-text') || old;
            el.classList.remove('badge--copied');
        }, 900);
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-copy]');
        if (!btn) return;

        const path = btn.getAttribute('data-copy') || '';
        if (!path) return;

        const url = window.location.origin + path;

        try {
            await navigator.clipboard.writeText(url);
            toast(btn, 'Скопировано');
        } catch (err) {
            // fallback для http/старых браузеров
            const tmp = document.createElement('textarea');
            tmp.value = url;
            document.body.appendChild(tmp);
            tmp.select();
            document.execCommand('copy');
            document.body.removeChild(tmp);
            toast(btn, 'Скопировано');
        }
    });
})();
