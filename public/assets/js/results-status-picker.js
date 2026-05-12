(() => {
    const pickers = document.querySelectorAll('[data-results-status-picker]');
    if (pickers.length === 0) {
        return;
    }

    const transitionMs = 150;

    const openPicker = (picker) => {
        const panel = picker.querySelector('[data-results-status-panel]');
        const trigger = picker.querySelector('[data-results-status-trigger]');
        if (!panel || !trigger) {
            return;
        }

        if (panel._hideTimer) {
            clearTimeout(panel._hideTimer);
            panel._hideTimer = null;
        }

        panel.hidden = false;
        requestAnimationFrame(() => {
            picker.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
        });
    };

    const closePicker = (picker) => {
        const panel = picker.querySelector('[data-results-status-panel]');
        const trigger = picker.querySelector('[data-results-status-trigger]');
        if (!panel || !trigger) {
            return;
        }

        picker.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');

        if (panel._hideTimer) {
            clearTimeout(panel._hideTimer);
        }

        panel._hideTimer = setTimeout(() => {
            panel.hidden = true;
            panel._hideTimer = null;
        }, transitionMs);
    };

    const closeAll = () => {
        pickers.forEach((picker) => closePicker(picker));
    };

    pickers.forEach((picker) => {
        const input = picker.querySelector('[data-results-status-input]');
        const trigger = picker.querySelector('[data-results-status-trigger]');
        const panel = picker.querySelector('[data-results-status-panel]');
        const current = picker.querySelector('[data-results-status-current]');
        const options = Array.from(picker.querySelectorAll('[data-results-status-option]'));
        if (!input || !trigger || !panel || !current || options.length === 0) {
            return;
        }

        const setSelected = (value) => {
            const selected = options.find((option) => option.dataset.value === value) ?? options[0];
            input.value = selected.dataset.value || 'all';
            current.textContent = selected.textContent || '';
            trigger.classList.toggle('is-placeholder', (selected.dataset.value || 'all') === 'all');

            options.forEach((option) => {
                const isSelected = option === selected;
                option.classList.toggle('is-selected', isSelected);
                option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        };

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const isOpen = picker.classList.contains('is-open');
            closeAll();
            if (isOpen) {
                return;
            }
            openPicker(picker);
        });

        options.forEach((option) => {
            option.addEventListener('click', () => {
                setSelected(option.dataset.value || 'all');
                closeAll();
            });
        });

        setSelected(input.value || 'all');
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-results-status-picker]')) {
            return;
        }
        closeAll();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
})();
