(function () {
    'use strict';

    // Хорошо — синий, Отлично — зелёный
    const GS_COLORS = ['#EF4444', '#F59E0B', '#3B82F6', '#22C55E', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];

    const PF_FAIL_COLOR = '#EF4444';
    const PF_PASS_COLOR = '#22C55E';
    const PF_POINT_COLOR = '#0D9488';

    function getLevelColor(index, total) {
        if (total <= GS_COLORS.length) return GS_COLORS[index % GS_COLORS.length];
        const mapped = Math.round((index / (total - 1)) * (GS_COLORS.length - 1));
        return GS_COLORS[Math.min(mapped, GS_COLORS.length - 1)];
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ── Grade Scale ────────────────────────────────────────────────────────────

    function initGradeScale(container) {
        const rows     = [...container.querySelectorAll('[data-gs-row]')];
        const track    = container.querySelector('[data-gs-track]');
        const labelsEl = container.querySelector('[data-gs-labels]');

        if (!rows.length || !track || !labelsEl) return;

        const n = rows.length;

        // Dot colors
        rows.forEach((row, i) => {
            const dot = row.querySelector('[data-gs-dot]');
            if (dot) dot.style.setProperty('--gs-color', getLevelColor(i, n));
        });

        // ── Data ─────────────────────────────────────────────────────────────────

        function getMaxValues() {
            return rows.map(row => {
                const v = parseInt(row.querySelector('[data-gs-max]')?.value ?? '0', 10);
                return isNaN(v) ? 0 : Math.max(0, Math.min(100, v));
            });
        }

        function setMaxValue(index, value) {
            const hidden = rows[index].querySelector('[data-gs-max]');
            const visible = rows[index].querySelector('[data-gs-max-input]');
            if (hidden)  hidden.value  = value;
            if (visible) visible.value = value;
        }

        function updateLastRange() {
            const maxValues = getMaxValues();
            const display   = rows[n - 1].querySelector('[data-gs-range-display]');
            if (display) display.textContent = `от ${n > 1 ? maxValues[n - 2] + 1 : 0} до 100 %`;
        }

        // ── DOM references (created once in setup) ────────────────────────────

        let segEls   = [];
        let pointEls = [];
        let labelEls = [];
        let startPtEl, endPtEl;

        function setup() {
            // Segments
            for (let i = 0; i < n; i++) {
                const el = document.createElement('div');
                el.className = 'gs-segment';
                track.appendChild(el);
                segEls.push(el);
            }

            // Start fixed point
            startPtEl = document.createElement('div');
            startPtEl.className = 'gs-point gs-point--fixed';
            track.appendChild(startPtEl);

            // Boundary points (n-1) — visual only, not interactive
            for (let i = 0; i < n - 1; i++) {
                const pt = document.createElement('div');
                pt.className = 'gs-point gs-point--fixed gs-point--boundary';
                pt.dataset.gsPoint = i;
                track.appendChild(pt);
                pointEls.push(pt);
            }

            // End fixed point
            endPtEl = document.createElement('div');
            endPtEl.className = 'gs-point gs-point--fixed';
            track.appendChild(endPtEl);

            // Labels
            for (let i = 0; i < n; i++) {
                const el = document.createElement('div');
                el.className = 'gs-label';
                el.innerHTML = '<span class="gs-label__range"></span><span class="gs-label__name"></span>';
                labelsEl.appendChild(el);
                labelEls.push(el);
            }

            // Round only the outer edges of the strip
            segEls[0].classList.add('gs-segment--first');
            segEls[n - 1].classList.add('gs-segment--last');

            // Enable CSS transitions after first paint
            requestAnimationFrame(() => {
                requestAnimationFrame(() => { container.classList.add('gs-animated'); });
            });
        }

        function update() {
            const maxValues = getMaxValues();

            maxValues.forEach((max, i) => {
                const min = i > 0 ? maxValues[i - 1] + 1 : 0;
                const w   = max - min + 1;
                const color = getLevelColor(i, n);
                segEls[i].style.left       = `${min}%`;
                segEls[i].style.width      = `${w}%`;
                segEls[i].style.background = color;
            });

            startPtEl.style.left       = '0%';
            startPtEl.style.background = getLevelColor(0, n);
            endPtEl.style.left         = '100%';
            endPtEl.style.background   = getLevelColor(n - 1, n);

            for (let i = 0; i < n - 1; i++) {
                const color = getLevelColor(i, n);
                pointEls[i].style.left       = `${maxValues[i] + 0.5}%`;
                pointEls[i].style.background = color;
            }

            maxValues.forEach((max, i) => {
                const min   = i > 0 ? maxValues[i - 1] + 1 : 0;
                const mid   = (min + max) / 2;
                const label = rows[i].querySelector('[data-gs-label]')?.value?.trim() ?? '';
                labelEls[i].style.left = `${mid}%`;
                labelEls[i].querySelector('.gs-label__range').textContent = `${min}–${max}`;
                labelEls[i].querySelector('.gs-label__name').textContent  = label;
            });

            updateLastRange();
        }

        setup();
        update();

        // ── Input listeners ───────────────────────────────────────────────────────

        rows.forEach((row, idx) => {
            const input = row.querySelector('[data-gs-max-input]');
            if (!input) return;

            // Во время ввода — не клэмпим, только показываем ошибку
            input.addEventListener('input', () => {
                const val = parseInt(input.value, 10);
                if (isNaN(val)) return;
                const maxValues = getMaxValues();
                const minOk = idx > 0 ? maxValues[idx - 1] + 1 : 1;
                const maxOk = idx < n - 2 ? maxValues[idx + 1] - 1 : 99;
                input.classList.toggle('is-error', val < minOk || val > maxOk);
                // Обновляем трек только если значение в допустимом диапазоне
                if (val >= minOk && val <= maxOk) {
                    rows[idx].querySelector('[data-gs-max]').value = val;
                    update();
                }
            });

            // При потере фокуса — фиксируем значение
            input.addEventListener('blur', () => {
                let val = parseInt(input.value, 10);
                const maxValues = getMaxValues();
                const minOk = idx > 0 ? maxValues[idx - 1] + 1 : 1;
                const maxOk = idx < n - 2 ? maxValues[idx + 1] - 1 : 99;
                if (isNaN(val)) val = minOk;
                val = Math.max(minOk, Math.min(maxOk, val));
                setMaxValue(idx, val);
                input.classList.remove('is-error');
                update();
            });
        });

        rows.forEach(row => {
            const labelInput = row.querySelector('[data-gs-label]');
            if (labelInput) labelInput.addEventListener('input', update);
        });

        // ── Reset to defaults ─────────────────────────────────────────────────────

        const resetBtn = container.querySelector('[data-gs-reset]');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                let defaults;
                try { defaults = JSON.parse(resetBtn.dataset.gsDefaults || '[]'); } catch { defaults = []; }
                if (!defaults.length) return;
                rows.forEach((row, i) => {
                    const def = defaults[i];
                    if (!def) return;
                    const labelInput = row.querySelector('[data-gs-label]');
                    if (labelInput) labelInput.value = def.label;
                    if (i < n - 1) setMaxValue(i, def.max);
                    row.querySelector('[data-gs-max-input]')?.classList.remove('is-error');
                });
                update();
            });
        }
    }

    // ── Pass/Fail mini-scale ───────────────────────────────────────────────────

    function initPassFail(container) {
        const percentInput = container.querySelector('[data-pf-percent]');
        const track        = container.querySelector('[data-pf-track]');
        const labelsEl     = container.querySelector('[data-pf-labels]');

        if (!percentInput || !track || !labelsEl) return;

        // Create segments
        const failSeg = document.createElement('div');
        failSeg.className = 'gs-segment gs-segment--first';
        track.appendChild(failSeg);

        const passSeg = document.createElement('div');
        passSeg.className = 'gs-segment gs-segment--last';
        track.appendChild(passSeg);

        // Fixed start
        const startPt = document.createElement('div');
        startPt.className = 'gs-point gs-point--fixed';
        startPt.style.left       = '0%';
        startPt.style.background = PF_FAIL_COLOR;
        track.appendChild(startPt);

        // Threshold point — visual only, not interactive
        const threshPt = document.createElement('div');
        threshPt.className = 'gs-point gs-point--fixed gs-point--boundary';
        track.appendChild(threshPt);

        // Fixed end
        const endPt = document.createElement('div');
        endPt.className = 'gs-point gs-point--fixed';
        endPt.style.left       = '100%';
        endPt.style.background = PF_PASS_COLOR;
        track.appendChild(endPt);

        // Labels
        const failLabel = document.createElement('div');
        failLabel.className = 'gs-label';
        failLabel.innerHTML = '<span class="gs-label__range"></span><span class="gs-label__name">Не сдано</span>';
        labelsEl.appendChild(failLabel);

        const passLabel = document.createElement('div');
        passLabel.className = 'gs-label';
        passLabel.innerHTML = '<span class="gs-label__range"></span><span class="gs-label__name">Сдано</span>';
        labelsEl.appendChild(passLabel);

        function update() {
            const val = Math.max(0, Math.min(100, parseInt(percentInput.value, 10) || 0));

            failSeg.style.left       = '0%';
            failSeg.style.width      = `${val}%`;
            failSeg.style.background = PF_FAIL_COLOR;

            passSeg.style.left       = `${val}%`;
            passSeg.style.width      = `${100 - val}%`;
            passSeg.style.background = PF_PASS_COLOR;

            threshPt.style.left       = `${val}%`;
            threshPt.style.background = PF_POINT_COLOR;

            failLabel.style.left = `${val / 2}%`;
            failLabel.querySelector('.gs-label__range').textContent = `0–${val}`;

            passLabel.style.left = `${val + (100 - val) / 2}%`;
            passLabel.querySelector('.gs-label__range').textContent = `${val + 1}–100`;
        }

        // Enable animation after first paint
        requestAnimationFrame(() => {
            requestAnimationFrame(() => { container.classList.add('gs-animated'); });
        });

        update();

        percentInput.addEventListener('input', () => {
            const raw = parseInt(percentInput.value, 10);
            if (!isNaN(raw)) {
                const clamped = Math.max(0, Math.min(100, raw));
                if (clamped !== raw) percentInput.value = clamped;
            }
            update();
        });

        percentInput.addEventListener('blur', () => {
            let val = parseInt(percentInput.value, 10);
            if (isNaN(val)) val = 60;
            percentInput.value = Math.max(0, Math.min(100, val));
            update();
        });
    }

    // ── Init ───────────────────────────────────────────────────────────────────

    function init() {
        const gsContainer = document.querySelector('[data-gs-container]');
        if (gsContainer) initGradeScale(gsContainer);

        const pfContainer = document.querySelector('[data-pf-container]');
        if (pfContainer) initPassFail(pfContainer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
