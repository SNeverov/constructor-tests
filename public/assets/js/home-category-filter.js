(() => {
    const normalize = (value) => String(value || '').trim().toLowerCase();

    document.querySelectorAll('[data-home-category-filter]').forEach((filter) => {
        const form = filter.closest('form');
        const input = filter.querySelector('[data-home-category-value]');
        const trigger = filter.querySelector('[data-home-category-trigger]');
        const current = filter.querySelector('[data-home-category-current]');
        const currentCount = filter.querySelector('[data-home-category-current-count]');
        const panel = filter.querySelector('[data-home-category-panel]');
        const search = filter.querySelector('[data-home-category-search]');
        const empty = filter.querySelector('[data-home-category-empty]');
        const options = Array.from(filter.querySelectorAll('[data-home-category-option]'));

        if (!form || !input || !trigger || !current || !currentCount || !panel || !search || !empty || options.length === 0) {
            return;
        }

        const setSelected = (option) => {
            const slug = option.dataset.categorySlug || '';
            const name = option.dataset.categoryName || 'Все категории';
            const count = option.dataset.categoryCount || '0';

            input.value = slug;
            current.textContent = name;
            current.classList.toggle('is-placeholder', slug === '');
            currentCount.textContent = count;

            options.forEach((item) => {
                const isSelected = item === option;
                item.classList.toggle('is-selected', isSelected);
                item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        };

        const applyFilter = () => {
            const query = normalize(search.value);
            let visibleCount = 0;

            options.forEach((option) => {
                const name = normalize(option.dataset.categoryName);
                const isVisible = query === '' || name.includes(query);
                option.hidden = !isVisible;
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            empty.hidden = visibleCount !== 0;
        };

        const openPanel = () => {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            filter.classList.add('is-open');
            search.focus();
            search.select();
            applyFilter();
        };

        const closePanel = () => {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            filter.classList.remove('is-open');
            search.value = '';
            applyFilter();
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
                setSelected(option);
            });
        });

        form.addEventListener('submit', () => {
            if (input.value === '') {
                input.disabled = true;
            }
        });

        document.addEventListener('mousedown', (event) => {
            if (!filter.contains(event.target)) {
                closePanel();
            }
        });

        filter.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePanel();
                trigger.focus();
            }
        });

        closePanel();
    });

    document.querySelectorAll('[data-home-sort-filter]').forEach((filter) => {
        const form = filter.closest('form');
        const input = filter.querySelector('[data-home-sort-value]');
        const trigger = filter.querySelector('[data-home-sort-trigger]');
        const current = filter.querySelector('[data-home-sort-current]');
        const panel = filter.querySelector('[data-home-sort-panel]');
        const options = Array.from(filter.querySelectorAll('[data-home-sort-option]'));

        if (!form || !input || !trigger || !current || !panel || options.length === 0) {
            return;
        }

        const closePanel = () => {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            filter.classList.remove('is-open');
        };

        const openPanel = () => {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            filter.classList.add('is-open');
        };

        const setSelected = (option) => {
            input.value = option.dataset.sortValue || 'new';
            current.textContent = option.dataset.sortLabel || 'Новые';

            options.forEach((item) => {
                const isSelected = item === option;
                item.classList.toggle('is-selected', isSelected);
                item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        };

        form.addEventListener('submit', () => {
            if (input.value === 'new') {
                input.disabled = true;
            }
        });

        trigger.addEventListener('click', () => {
            if (panel.hidden) {
                openPanel();
            } else {
                closePanel();
            }
        });

        options.forEach((option) => {
            option.addEventListener('click', () => {
                setSelected(option);
                closePanel();
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.submit();
            });
        });

        document.addEventListener('mousedown', (event) => {
            if (!filter.contains(event.target)) {
                closePanel();
            }
        });

        filter.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePanel();
                trigger.focus();
            }
        });

        closePanel();
    });
})();
