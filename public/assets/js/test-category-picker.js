(() => {
    const normalize = (value) => String(value || '').trim().toLowerCase();
    const escapeAttr = (value) => String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const escapeHtml = (value) => String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const PLACEHOLDER = 'Выберите категории';
    const triggerText = (values) => {
        if (values.length === 0) return PLACEHOLDER;
        if (values.length <= 2) return values.join(', ');
        return `${values[0]}, ${values[1]} +${values.length - 2}`;
    };

    document.querySelectorAll('[data-category-picker]').forEach((picker) => {
        const hiddenInputsWrap = picker.querySelector('[data-category-hidden-inputs]');
        const trigger = picker.querySelector('[data-category-trigger]');
        const current = picker.querySelector('[data-category-current]');
        const panel = picker.querySelector('[data-category-panel]');
        const search = picker.querySelector('[data-category-search]');
        const empty = picker.querySelector('[data-category-empty]');
        const selectedWrap = picker.querySelector('[data-category-selected]');
        const applyButton = picker.querySelector('[data-category-apply]');
        const options = Array.from(picker.querySelectorAll('[data-category-option]'));

        if (!hiddenInputsWrap || !trigger || !current || !panel || !search || !selectedWrap || !applyButton || options.length === 0) {
            return;
        }

        const getSelectedValues = () => Array.from(hiddenInputsWrap.querySelectorAll('[data-category-hidden-value]'))
            .map((input) => input.value)
            .filter((value, index, list) => value && list.indexOf(value) === index);

        const renderSelected = (values) => {
            hiddenInputsWrap.innerHTML = values
                .map((value) => `<input type="hidden" name="category_names[]" value="${escapeAttr(value)}" data-category-hidden-value>`)
                .join('');

            options.forEach((option) => {
                const isSelected = values.includes(option.dataset.categoryValue || '');
                option.classList.toggle('is-selected', isSelected);
                option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });

            selectedWrap.innerHTML = values.length === 0
                ? '<div class="tc-category__selected-empty">Ничего не выбрано</div>'
                : values
                    .map((value) => `
                        <span class="tc-category__selected-chip">
                            <span>${escapeHtml(value)}</span>
                            <button type="button" class="tc-category__selected-remove" data-category-remove="${escapeAttr(value)}" aria-label="Удалить категорию">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </span>
                    `)
                    .join('');

            current.textContent = triggerText(values);
            current.classList.toggle('is-placeholder', values.length === 0);
        };

        const applyFilter = () => {
            const query = normalize(search.value);
            let visibleCount = 0;

            options.forEach((option) => {
                const matches = query === '' || normalize(option.dataset.categoryValue).includes(query);
                option.hidden = !matches;
                if (matches) {
                    visibleCount += 1;
                }
            });

            empty.hidden = visibleCount !== 0;
        };

        const openPanel = () => {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            picker.classList.add('is-open');
            search.focus();
            search.select();
            applyFilter();
        };

        const closePanel = () => {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            picker.classList.remove('is-open');
        };

        const removeSelectedValue = (value) => {
            const next = getSelectedValues().filter((item) => item !== value);
            renderSelected(next);
        };

        trigger.addEventListener('click', () => {
            if (panel.hidden) {
                openPanel();
            } else {
                closePanel();
            }
        });

        search.addEventListener('input', applyFilter);

        options.forEach((option) => {
            option.addEventListener('click', () => {
                const value = option.dataset.categoryValue || '';
                const selected = getSelectedValues();
                const next = selected.includes(value)
                    ? selected.filter((item) => item !== value)
                    : [...selected, value];

                renderSelected(next);
            });
        });

        selectedWrap.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-category-remove]');
            if (removeButton) {
                event.preventDefault();
                event.stopPropagation();
                removeSelectedValue(removeButton.getAttribute('data-category-remove') || '');
                return;
            }

            const chip = event.target.closest('.tc-category__selected-chip');
            if (!chip) {
                return;
            }

            const chipButton = chip.querySelector('[data-category-remove]');
            if (!chipButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            removeSelectedValue(chipButton.getAttribute('data-category-remove') || '');
        });

        applyButton.addEventListener('mousedown', (event) => {
            event.preventDefault();
            event.stopPropagation();
        });

        applyButton.addEventListener('click', () => {
            closePanel();
            trigger.focus();
        });

        document.addEventListener('mousedown', (event) => {
            if (!picker.contains(event.target)) {
                closePanel();
            }
        });

        picker.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePanel();
                trigger.focus();
            }
        });

        renderSelected(getSelectedValues());
        applyFilter();
        closePanel();
    });
})();
