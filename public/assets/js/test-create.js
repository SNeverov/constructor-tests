const MAX_OPTIONS = 10;
const MAX_INPUT_ANSWERS = 10;
const QUESTION_TEXT_MAX = 1000;
const QUESTION_TEXT_MIN_HEIGHT = 36;
const OPTION_TEXT_MAX = 1000;
const OPTION_TEXT_MIN_HEIGHT = 34;
const TEST_TITLE_MAX = 200;
const TEST_DESCRIPTION_MAX = 500;
const TEST_DESCRIPTION_MIN_HEIGHT = 76;
const DRAFT_AUTOSAVE_DELAY = 3000;

function syncAutosizeTextarea(textarea, config) {
    const wrap = textarea.closest(config.wrap);
    if (wrap) wrap.classList.remove('is-multiline');

    textarea.style.height = 'auto';
    const scrollH = textarea.scrollHeight;
    textarea.style.paddingBottom = '';
    const isMultiline = scrollH > config.minHeight + 2;

    if (wrap) wrap.classList.toggle('is-multiline', isMultiline);

    textarea.style.height = 'auto';
    textarea.style.height = `${Math.max(textarea.scrollHeight, config.minHeight)}px`;

    const el = textarea.closest(config.closest)?.querySelector(config.attr);
    if (el) {
        el.textContent = `${textarea.value.length}/${config.max}`;
    }
}

function syncOptionTextUI(textarea) {
    syncAutosizeTextarea(textarea, {
        wrap: '.option-text-wrap',
        closest: '[data-option]',
        attr: '[data-option-text-limit]',
        max: OPTION_TEXT_MAX,
        minHeight: OPTION_TEXT_MIN_HEIGHT
    });
}

function syncAnswerTextUI(textarea) {
    syncAutosizeTextarea(textarea, {
        wrap: '.answer-text-wrap',
        closest: '[data-answer]',
        attr: '[data-answer-text-limit]',
        max: OPTION_TEXT_MAX,
        minHeight: OPTION_TEXT_MIN_HEIGHT
    });
}

document.addEventListener('input', (e) => {
    if (e.target.closest('[data-option-text]')) syncOptionTextUI(e.target.closest('[data-option-text]'));
    if (e.target.closest('[data-answer-text]')) syncAnswerTextUI(e.target.closest('[data-answer-text]'));
});

// ─── Lightbox ────────────────────────────────────────────────────────────────

let lightboxScrollY = 0;

function openLightbox(src, fullSize) {
    const lb = document.getElementById('imgLightbox');
    const img = document.getElementById('imgLightboxImg');
    if (!lb || !img) return;

    lightboxScrollY = window.scrollY || document.documentElement.scrollTop || 0;
    img.style.width = '';
    img.style.height = '';
    img.src = '';
    lb.setAttribute('aria-hidden', 'false');
    document.body.style.position = 'fixed';
    document.body.style.top = `-${lightboxScrollY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';

    const tmp = new Image();
    tmp.onload = () => {
        if (!fullSize) {
            const maxSide = 500;
            const w = tmp.naturalWidth;
            const h = tmp.naturalHeight;
            if (w > maxSide || h > maxSide) {
                const ratio = Math.min(maxSide / w, maxSide / h);
                img.style.width = Math.round(w * ratio) + 'px';
                img.style.height = Math.round(h * ratio) + 'px';
            }
        }
        img.src = src;
        requestAnimationFrame(() => lb.classList.add('is-open'));
    };
    tmp.src = src;
}

function closeLightbox() {
    const lb = document.getElementById('imgLightbox');
    if (!lb) return;
    lb.classList.add('is-closing');
    lb.addEventListener('transitionend', () => {
        lb.classList.remove('is-open', 'is-closing');
        lb.setAttribute('aria-hidden', 'true');
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';
        window.scrollTo(0, lightboxScrollY);
    }, { once: true });
}

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-lightbox-close]')) {
        e.preventDefault();
        closeLightbox();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
});

// ─── Cover crop modal ─────────────────────────────────────────────────────────

let _cropper = null;
let _cropCallback = null;

function openCropModal(src, onApply) {
    const modal = document.getElementById('coverCropModal');
    const img   = document.getElementById('coverCropperImg');
    if (!modal || !img) return;

    if (_cropper) { _cropper.destroy(); _cropper = null; }

    img.src = src;
    _cropCallback = onApply;

    modal.hidden = false;
    modal.removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';

    img.onload = () => {
        _cropper = new Cropper(img, {
            aspectRatio: 16 / 9,
            viewMode: 1,
            dragMode: 'move',       // тащим изображение, не рамку
            autoCropArea: 1,        // рамка = весь canvas
            cropBoxResizable: false, // хэндлы отключены
            cropBoxMovable: false,   // рамка фиксирована
            responsive: true,
            checkOrientation: false,
            background: false,
            guides: false,
            center: false,
            highlight: false,
            toggleDragModeOnDblclick: false,
        });
    };
}

function closeCropModal() {
    const modal = document.getElementById('coverCropModal');
    if (!modal) return;
    if (_cropper) { _cropper.destroy(); _cropper = null; }
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    _cropCallback = null;
}

document.getElementById('cropApplyBtn')?.addEventListener('click', () => {
    if (!_cropper || !_cropCallback) return;
    const btn = document.getElementById('cropApplyBtn');
    btn.disabled = true;
    const cb = _cropCallback;
    _cropper.getCroppedCanvas({ width: 1200, height: 675, imageSmoothingQuality: 'high' })
        .toBlob((blob) => {
            btn.disabled = false;
            closeCropModal();
            if (cb) cb(blob);
        }, 'image/jpeg', 0.92);
});

document.getElementById('cropCancelBtn')?.addEventListener('click', closeCropModal);
document.getElementById('cropCloseBtn')?.addEventListener('click', closeCropModal);
document.getElementById('cropModalBackdrop')?.addEventListener('click', closeCropModal);
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCropModal(); });

async function uploadImageBlob(wrap, blob) {
    const type        = wrap.dataset.imgUpload;
    const err         = wrap.querySelector('[data-img-error]');
    const progressEl  = wrap.querySelector('[data-img-progress]');
    const progressBar = wrap.querySelector('[data-img-progress-bar]');
    const btnLabel    = wrap.querySelector('[data-img-btn-label]');
    const csrfInput   = document.querySelector('input[name="csrf_token"]');

    if (err) err.textContent = '';
    if (btnLabel) btnLabel.textContent = 'Загрузка…';
    if (progressEl) { progressEl.hidden = false; if (progressBar) progressBar.style.width = '0%'; }

    const fd = new FormData();
    fd.append('image', blob, 'cover.jpg');
    fd.append('image_type', type);
    if (csrfInput) fd.append('csrf_token', csrfInput.value);

    try {
        const json = await new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/my/tests/upload-image');
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && progressBar)
                    progressBar.style.width = Math.round(e.loaded / e.total * 100) + '%';
            });
            xhr.addEventListener('load', () => {
                try { resolve(JSON.parse(xhr.responseText)); }
                catch { reject(new Error('parse')); }
            });
            xhr.addEventListener('error', () => reject(new Error('network')));
            xhr.send(fd);
        });
        if (progressBar) progressBar.style.width = '100%';

        if (!json.ok) {
            if (err) err.textContent = json.error ?? 'Ошибка загрузки';
            if (btnLabel) btnLabel.textContent = 'Загрузить';
        } else {
            setImgUploadValue(wrap, json.path);
            markFormDirty();
        }
    } catch {
        if (err) err.textContent = 'Ошибка сети';
        if (btnLabel) btnLabel.textContent = 'Загрузить';
    } finally {
        if (progressEl) setTimeout(() => { progressEl.hidden = true; if (progressBar) progressBar.style.width = '0%'; }, 500);
    }
}

// ─── Image upload widget ──────────────────────────────────────────────────────

function resetImgUploadWrap(wrap) {
    const preview   = wrap.querySelector('[data-img-preview]');
    const hidden    = wrap.querySelector('input[type="hidden"]');
    const remove    = wrap.querySelector('[data-img-remove]');
    const btnLabel  = wrap.querySelector('[data-img-btn-label]');
    const err       = wrap.querySelector('[data-img-error]');
    const fileInput = wrap.querySelector('[data-img-file-input]');
    const placeholder = wrap.querySelector('.tc-cover-zone__placeholder');

    if (preview) { preview.innerHTML = ''; preview.classList.remove('has-image'); }
    if (hidden) hidden.value = '';
    if (remove) { remove.style.display = ''; remove.disabled = true; }
    if (err) err.textContent = '';
    if (fileInput) fileInput.value = '';
    if (placeholder) placeholder.classList.remove('is-hidden');
    if (btnLabel) {
        const type = wrap.dataset.imgUpload;
        btnLabel.textContent = type === 'cover' ? 'Загрузить' : 'Добавить фото';
    }
}

function setImgUploadValue(wrap, path) {
    if (!path) { resetImgUploadWrap(wrap); return; }

    const preview     = wrap.querySelector('[data-img-preview]');
    const hidden      = wrap.querySelector('input[type="hidden"]');
    const remove      = wrap.querySelector('[data-img-remove]');
    const btnLabel    = wrap.querySelector('[data-img-btn-label]');
    const placeholder = wrap.querySelector('.tc-cover-zone__placeholder');
    const isCover     = wrap.dataset.imgUpload === 'cover';

    if (hidden) hidden.value = path;
    if (placeholder) placeholder.classList.add('is-hidden');

    if (preview) {
        preview.innerHTML = `
            <img src="${path}" alt="" class="img-upload-thumb">
            <button type="button" class="img-upload-zoom" data-img-zoom aria-label="Увеличить">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>`;
        preview.classList.add('has-image');
    }
    if (remove) { remove.style.display = ''; remove.disabled = false; }
    if (btnLabel) btnLabel.textContent = isCover ? 'Заменить' : 'Заменить фото';
}

function initImgUpload(wrap) {
    if (wrap.dataset.imgInited === '1') return;
    wrap.dataset.imgInited = '1';

    const type        = wrap.dataset.imgUpload;
    const fileInput   = wrap.querySelector('[data-img-file-input]');
    const remove      = wrap.querySelector('[data-img-remove]');
    const err         = wrap.querySelector('[data-img-error]');
    const progressEl  = wrap.querySelector('[data-img-progress]');
    const progressBar = wrap.querySelector('[data-img-progress-bar]');
    const csrfInput   = document.querySelector('input[name="csrf_token"]');

    if (!fileInput) return;

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        if (err) err.textContent = '';

        // Cover: показываем кроппер перед загрузкой
        if (type === 'cover') {
            const reader = new FileReader();
            reader.onload = (e) => {
                openCropModal(e.target.result, (blob) => uploadImageBlob(wrap, blob));
            };
            reader.readAsDataURL(file);
            fileInput.value = '';
            return;
        }

        // Остальные изображения: загрузка напрямую
        const fd = new FormData();
        fd.append('image', file);
        fd.append('image_type', type);
        if (csrfInput) fd.append('csrf_token', csrfInput.value);

        const btnLabel = wrap.querySelector('[data-img-btn-label]');
        const oldLabel = btnLabel ? btnLabel.textContent : '';
        if (btnLabel) btnLabel.textContent = 'Загрузка…';
        fileInput.disabled = true;

        try {
            const res = await fetch('/my/tests/upload-image', { method: 'POST', body: fd });
            const json = await res.json();

            if (!json.ok) {
                if (err) err.textContent = json.error ?? 'Ошибка загрузки';
                if (btnLabel) btnLabel.textContent = oldLabel;
            } else {
                setImgUploadValue(wrap, json.path);
                markFormDirty();
            }
        } catch {
            if (err) err.textContent = 'Ошибка сети';
            if (btnLabel) btnLabel.textContent = oldLabel;
        } finally {
            fileInput.disabled = false;
            fileInput.value = '';
        }
    });

    if (remove) {
        remove.addEventListener('click', () => {
            resetImgUploadWrap(wrap);
            markFormDirty();
        });
    }

    wrap.addEventListener('click', (e) => {
        if (e.target.closest('[data-img-zoom]') || e.target.closest('.img-upload-thumb')) {
            const hidden = wrap.querySelector('input[type="hidden"]');
            if (hidden && hidden.value) {
                e.preventDefault();
                openLightbox(hidden.value);
            }
        }
    });
}

function initAllImgUploads(root) {
    root.querySelectorAll('[data-img-upload]').forEach(initImgUpload);
}

// ─── Question/option indexing ─────────────────────────────────────────────────

function setQuestionIndex(q, index) {
    q.dataset.index = String(index);

    q.querySelectorAll('[name]').forEach((el) => {
        const name = el.getAttribute('name');
        if (!name) return;
        const updated = name.replace(/^questions\[\d+]/, `questions[${index}]`);
        el.setAttribute('name', updated);
    });
}

function reindexOptions(q) {
    const options = q.querySelectorAll('[data-option]');
    options.forEach((opt, i) => {
        opt.querySelectorAll('[name]').forEach((el) => {
            const name = el.getAttribute('name');
            if (!name) return;
            const updated = name.replace(/\[options]\[\d+]/, `[options][${i}]`);
            el.setAttribute('name', updated);
        });
    });
}

function reindexAnswers(q) {
    const rows = q.querySelectorAll('[data-answer]');
    rows.forEach((row, i) => {
        row.querySelectorAll('[name]').forEach((el) => {
            const name = el.getAttribute('name');
            if (!name) return;
            const updated = name.replace(/\[answers]\[\d+]/, `[answers][${i}]`);
            el.setAttribute('name', updated);
        });
    });
}

function reindexQuestions() {
    const questions = document.querySelectorAll('[data-question]');
    questions.forEach((q, i) => {
        setQuestionIndex(q, i);

        const title = q.querySelector('[data-question-title]') || q.querySelector('.question-card__title');
        if (title) title.innerHTML = `<span class="question-card__num">${i + 1}</span> Вопрос`;

        reindexOptions(q);
        reindexAnswers(q);
        updateAddOptionVisibility(q);
        updateAddAnswerVisibility(q);
    });
}

function updateAddAnswerVisibility(q) {
    const textBlock = q.querySelector('[data-block="text"]');
    const addAnswerBtn = q.querySelector('[data-add-answer]');
    if (!textBlock || !addAnswerBtn) return;

    const typeSelect = q.querySelector('[data-question-type]');
    const isInput = typeSelect && typeSelect.value === 'input';
    if (!isInput) {
        addAnswerBtn.style.display = 'none';
        return;
    }

    const count = textBlock.querySelectorAll('[data-answer]').length;
    addAnswerBtn.style.display = count >= MAX_INPUT_ANSWERS ? 'none' : '';
}

function updateAddOptionVisibility(q) {
    const optionsBlock = q.querySelector('[data-block="options"]');
    const addOptionBtn = q.querySelector('[data-add-option]');
    if (!optionsBlock || !addOptionBtn) return;

    const typeSelect = q.querySelector('[data-question-type]');
    const isInput = typeSelect && typeSelect.value === 'input';
    if (isInput) {
        addOptionBtn.style.display = 'none';
        return;
    }

    const count = optionsBlock.querySelectorAll('[data-option]').length;
    addOptionBtn.style.display = count >= MAX_OPTIONS ? 'none' : '';
}

// ─── Segmented slider ─────────────────────────────────────────────────────────

function initSegmentedSlider(segmented) {
    if (segmented.dataset.sliderInited === '1') return;
    segmented.dataset.sliderInited = '1';

    const pill = document.createElement('span');
    pill.className = 'segmented__pill';
    pill.setAttribute('aria-hidden', 'true');
    segmented.appendChild(pill);

    function movePillTo(label, animate) {
        if (!animate) pill.style.transition = 'none';
        pill.style.width = label.offsetWidth + 'px';
        pill.style.transform = `translateX(${label.offsetLeft}px)`;
        if (!animate) {
            requestAnimationFrame(() => requestAnimationFrame(() => {
                pill.style.transition = '';
            }));
        }
    }

    function update(animate) {
        const checked = segmented.querySelector('input:checked');
        if (!checked) return;
        const label = checked.closest('.segmented__item, .tc-segmented__item');
        if (label) movePillTo(label, animate);
    }

    requestAnimationFrame(() => update(false));
    segmented.addEventListener('change', () => update(true));
}

// ─── Question init ────────────────────────────────────────────────────────────

function initQuestion(q) {
    if (q.dataset.inited === '1') return;
    q.dataset.inited = '1';
    const typeSelect = q.querySelector('[data-question-type]');

    const typeUi = q.querySelector('[data-question-type-ui]');
    const typeRadios = q.querySelectorAll('[data-question-type-radio]');

    function syncTypeUIFromSelect() {
        if (!typeUi) return;
        typeRadios.forEach((r) => {
            r.checked = (r.value === typeSelect.value);
        });
    }

    if (typeUi) {
        typeUi.addEventListener('change', (e) => {
            const r = e.target.closest('[data-question-type-radio]');
            if (!r) return;
            typeSelect.value = r.value;
            typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        });
        initSegmentedSlider(typeUi);
    }

    if (!q.dataset.qid) {
        q.dataset.qid = String(Date.now()) + String(Math.floor(Math.random() * 10000));
    }

    if (!typeSelect) return;

    const questionText = q.querySelector('[data-question-text]');

    function syncQuestionTextUI() {
        if (!questionText) return;
        syncAutosizeTextarea(questionText, {
            wrap: '.question-text-wrap',
            closest: '[data-question]',
            attr: '[data-question-text-limit]',
            max: QUESTION_TEXT_MAX,
            minHeight: QUESTION_TEXT_MIN_HEIGHT
        });
    }

    if (questionText) {
        questionText.maxLength = QUESTION_TEXT_MAX;
        questionText.addEventListener('input', syncQuestionTextUI);
        syncQuestionTextUI();
    }

    const optionsBlock = q.querySelector('[data-block="options"]');
    const textBlock    = q.querySelector('[data-block="text"]');
    const addOptionBtn = q.querySelector('[data-add-option]');
    const addAnswerBtn = q.querySelector('[data-add-answer]');

    if (optionsBlock) {
        optionsBlock.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove-option]');
            if (!btn) return;

            const opt = btn.closest('[data-option]');
            if (!opt) return;

            const all = optionsBlock.querySelectorAll('[data-option]');
            if (all.length <= 1) {
                opt.querySelectorAll('input').forEach((input) => {
                    if (input.type === 'text') input.value = '';
                    if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                });
                opt.querySelectorAll('textarea[data-option-text]').forEach((ta) => {
                    ta.value = '';
                    ta.style.height = '';
                    syncOptionTextUI(ta);
                });
                const imgWrap = opt.querySelector('[data-img-upload]');
                if (imgWrap) resetImgUploadWrap(imgWrap);
                markFormDirty();
                return;
            }

            opt.remove();
            reindexOptions(q);
            sync();
            updateAddOptionVisibility(q);
            markFormDirty();
        });

        optionsBlock.addEventListener('change', (e) => {
            const correctInput = e.target.closest('.option-correct');
            if (!correctInput) return;
            if (typeSelect.value !== 'radio') return;
            if (!correctInput.checked) return;
            q.querySelectorAll('.option-correct').forEach((el) => {
                if (el !== correctInput) el.checked = false;
            });
        });
    }

    if (textBlock) {
        textBlock.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-remove-answer]');
            if (!btn) return;
            const row = btn.closest('[data-answer]');
            if (!row) return;
            const all = textBlock.querySelectorAll('[data-answer]');
            if (all.length <= 1) {
                row.querySelectorAll('input').forEach((input) => {
                    if (input.type === 'text') input.value = '';
                });
                row.querySelectorAll('textarea[data-answer-text]').forEach((ta) => {
                    ta.value = '';
                    ta.style.height = '';
                    syncAnswerTextUI(ta);
                });
                markFormDirty();
                return;
            }
            row.remove();
            reindexAnswers(q);
            updateAddAnswerVisibility(q);
            markFormDirty();
        });
    }

    function sync() {
        syncTypeUIFromSelect();

        const type = typeSelect.value;
        const isInput = type === 'input';
        const isOrder = type === 'order';
        q.classList.toggle('is-type-input', isInput);
        q.classList.toggle('is-type-order', isOrder);

        if (optionsBlock) optionsBlock.style.display = isInput ? 'none' : '';
        if (textBlock) textBlock.style.display = isInput ? '' : 'none';
        if (addOptionBtn) addOptionBtn.style.display = isInput ? 'none' : '';

        // скрываем img-upload на вариантах если input-тип или order-тип
        q.querySelectorAll('[data-option] [data-img-upload="option"]').forEach((w) => {
            w.style.display = (isInput || isOrder) ? 'none' : '';
        });

        const correctInputs = q.querySelectorAll('.option-correct');
        correctInputs.forEach((el) => {
            // для order не меняем тип (is_correct не используется)
            if (!isOrder) {
                el.type = type === 'radio' ? 'radio' : 'checkbox';
            }
        });

        if (type === 'radio') {
            let firstChecked = null;
            correctInputs.forEach((el) => {
                if (el.checked) {
                    if (!firstChecked) firstChecked = el;
                    else el.checked = false;
                }
            });
        }
        updateAddOptionVisibility(q);
        updateAddAnswerVisibility(q);
    }

    typeSelect.addEventListener('change', sync);
    sync();

    if (addOptionBtn && optionsBlock) {
        addOptionBtn.addEventListener('click', () => {
            const count = optionsBlock.querySelectorAll('[data-option]').length;
            if (count >= MAX_OPTIONS) return;

            const optionsList = optionsBlock.querySelector('.answers');
            const option = optionsBlock.querySelector('[data-option]');
            if (!option) return;

            const clone = option.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => {
                if (input.type === 'text') input.value = '';
                if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
            });
            clone.querySelectorAll('textarea[data-option-text]').forEach((ta) => {
                ta.value = '';
                ta.style.height = '';
            });
            const cloneLimit = clone.querySelector('[data-option-text-limit]');
            if (cloneLimit) cloneLimit.textContent = `0/${OPTION_TEXT_MAX}`;

            // сбрасываем img-upload в клоне
            const imgWrap = clone.querySelector('[data-img-upload]');
            if (imgWrap) {
                imgWrap.removeAttribute('data-img-inited');
                const prevEl = imgWrap.querySelector('[data-img-preview]');
                if (prevEl) { prevEl.innerHTML = ''; prevEl.classList.remove('has-image'); }
                const hiddenEl = imgWrap.querySelector('input[type="hidden"]');
                if (hiddenEl) hiddenEl.value = '';
                const removeEl = imgWrap.querySelector('[data-img-remove]');
                if (removeEl) removeEl.style.display = 'none';
                const errEl = imgWrap.querySelector('[data-img-error]');
                if (errEl) errEl.textContent = '';
            }

            if (optionsList) optionsList.appendChild(clone);
            else optionsBlock.insertBefore(clone, addOptionBtn);

            reindexOptions(q);
            sync();
            updateAddOptionVisibility(q);

            if (imgWrap) initImgUpload(imgWrap);
            markFormDirty();
        });
    }

    if (addAnswerBtn && textBlock) {
        addAnswerBtn.addEventListener('click', () => {
            const count = textBlock.querySelectorAll('[data-answer]').length;
            if (count >= MAX_INPUT_ANSWERS) return;

            const row = textBlock.querySelector('[data-answer]');
            if (!row) return;

            const clone = row.cloneNode(true);
            clone.querySelectorAll('input').forEach((input) => {
                if (input.type === 'text') input.value = '';
            });
            clone.querySelectorAll('textarea[data-answer-text]').forEach((ta) => {
                ta.value = '';
                ta.style.height = '';
            });
            const cloneAnswerLimit = clone.querySelector('[data-answer-text-limit]');
            if (cloneAnswerLimit) cloneAnswerLimit.textContent = `0/${OPTION_TEXT_MAX}`;

            const answersList = textBlock.querySelector('.answers');
            if (answersList) answersList.appendChild(clone);
            else textBlock.appendChild(clone);

            reindexAnswers(q);
            updateAddAnswerVisibility(q);
            markFormDirty();
        });
    }

    reindexOptions(q);
    updateAddOptionVisibility(q);
    reindexAnswers(q);
    updateAddAnswerVisibility(q);
    syncQuestionTextUI();

    // инициализируем img-upload внутри карточки вопроса
    initAllImgUploads(q);
}

// ─── DOMContentLoaded ─────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const testTitle = document.querySelector('[data-test-title]');
    const testTitleLimit = document.querySelector('[data-test-title-limit]');
    const testDescription = document.querySelector('[data-test-description]');

    function syncTestTitleUI() {
        if (!testTitleLimit || !testTitle) return;
        testTitleLimit.textContent = `${testTitle.value.length}/${TEST_TITLE_MAX}`;
    }

    if (testTitle) {
        testTitle.maxLength = TEST_TITLE_MAX;
        testTitle.addEventListener('input', syncTestTitleUI);
        syncTestTitleUI();
    }

    function syncTestDescriptionUI() {
        if (!testDescription) return;
        syncAutosizeTextarea(testDescription, {
            wrap: '.test-description-wrap',
            closest: '.test-description-wrap',
            attr: '[data-test-description-limit]',
            max: TEST_DESCRIPTION_MAX,
            minHeight: TEST_DESCRIPTION_MIN_HEIGHT
        });
    }

    if (testDescription) {
        testDescription.maxLength = TEST_DESCRIPTION_MAX;
        testDescription.addEventListener('input', syncTestDescriptionUI);
        syncTestDescriptionUI();
    }

    // img-upload для обложки (вне карточек вопросов)
    document.querySelectorAll('.form-section [data-img-upload]').forEach(initImgUpload);

    const wrap = document.querySelector('#questionsList') || document.querySelector('#questions');
    const baseTemplate = wrap ? wrap.querySelector('[data-question]') : null;
    const questionTemplate = baseTemplate ? baseTemplate.cloneNode(true) : null;

    if (questionTemplate) {
        questionTemplate.removeAttribute('data-qid');
        questionTemplate.removeAttribute('data-inited');
    }

    const oldQuestions = window.__OLD_QUESTIONS__ || [];

    if (Array.isArray(oldQuestions) && oldQuestions.length > 0) {
        if (wrap && questionTemplate) {
            wrap.innerHTML = '';

            oldQuestions.forEach((qData, idx) => {
                const q = questionTemplate.cloneNode(true);
                q.removeAttribute('data-qid');
                q.removeAttribute('data-inited');

                setQuestionIndex(q, idx);

                // 1) текст вопроса
                const qTextEl = q.querySelector(`textarea[name="questions[${idx}][text]"]`);
                if (qTextEl) qTextEl.value = qData.text ?? '';

                // 2) изображение вопроса
                const qImgHidden = q.querySelector(`input[name="questions[${idx}][image_path]"]`);
                if (qImgHidden && qData.image_path) {
                    qImgHidden.value = qData.image_path;
                    const qImgWrap = qImgHidden.closest('[data-img-upload]');
                    if (qImgWrap) {
                        qImgWrap.removeAttribute('data-img-inited');
                        setImgUploadValue(qImgWrap, qData.image_path);
                    }
                }

                // 3) тип
                const qType = q.querySelector('[data-question-type]');
                if (qType) qType.value = qData.type ?? 'radio';

                // 4) варианты (radio/checkbox)
                const optionsBlock = q.querySelector('[data-block="options"]');
                const addOptionBtn = q.querySelector('[data-add-option]');
                const incomingOptions = Array.isArray(qData.options) ? qData.options : [];
                const optionsList = optionsBlock ? optionsBlock.querySelector('.answers') : null;

                if (optionsBlock && addOptionBtn && incomingOptions.length > 0) {
                    const rows = optionsBlock.querySelectorAll('[data-option]');
                    rows.forEach((row, i) => { if (i > 0) row.remove(); });

                    for (let i = 1; i < incomingOptions.length; i++) {
                        const firstRow = optionsBlock.querySelector('[data-option]');
                        const cloneRow = firstRow.cloneNode(true);
                        cloneRow.querySelectorAll('input').forEach((input) => {
                            if (input.type === 'text') input.value = '';
                            if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                        });
                        const imgW = cloneRow.querySelector('[data-img-upload]');
                        if (imgW) {
                            imgW.removeAttribute('data-img-inited');
                            const p = imgW.querySelector('[data-img-preview]');
                            if (p) { p.innerHTML = ''; p.classList.remove('has-image'); }
                            const h = imgW.querySelector('input[type="hidden"]');
                            if (h) h.value = '';
                            const r = imgW.querySelector('[data-img-remove]');
                            if (r) r.style.display = 'none';
                        }
                        if (optionsList) optionsList.appendChild(cloneRow);
                        else optionsBlock.insertBefore(cloneRow, addOptionBtn);
                    }

                    reindexOptions(q);

                    const optRows = optionsBlock.querySelectorAll('[data-option]');
                    optRows.forEach((row, i) => {
                        const opt = incomingOptions[i] || {};

                        const textInput = row.querySelector(
                            `textarea[name="questions[${idx}][options][${i}][text]"], input[name="questions[${idx}][options][${i}][text]"]`
                        );
                        if (textInput) {
                            textInput.value = opt.text ?? '';
                            if (textInput.tagName === 'TEXTAREA') syncOptionTextUI(textInput);
                        }

                        const correctInput = row.querySelector(`input.option-correct[name="questions[${idx}][options][${i}][is_correct]"]`);
                        if (correctInput) correctInput.checked = String(opt.is_correct ?? '0') === '1';

                        // изображение варианта
                        if (opt.image_path) {
                            const optImgWrap = row.querySelector('[data-img-upload="option"]');
                            if (optImgWrap) {
                                optImgWrap.removeAttribute('data-img-inited');
                                setImgUploadValue(optImgWrap, opt.image_path);
                            }
                        }
                    });
                }

                // 5) текстовые ответы (input)
                const textBlock = q.querySelector('[data-block="text"]');
                const incomingAnswers = Array.isArray(qData.answers) ? qData.answers : [];
                const answersList = textBlock ? textBlock.querySelector('.answers') : null;

                if (textBlock && incomingAnswers.length > 0) {
                    const rows = textBlock.querySelectorAll('[data-answer]');
                    rows.forEach((row, i) => { if (i > 0) row.remove(); });

                    for (let i = 1; i < incomingAnswers.length; i++) {
                        const firstRow = textBlock.querySelector('[data-answer]');
                        const cloneRow = firstRow.cloneNode(true);
                        cloneRow.querySelectorAll('input').forEach((input) => {
                            if (input.type === 'text') input.value = '';
                        });
                        if (answersList) answersList.appendChild(cloneRow);
                        else textBlock.appendChild(cloneRow);
                    }

                    reindexAnswers(q);

                    const answerRows = textBlock.querySelectorAll('[data-answer]');
                    answerRows.forEach((row, i) => {
                        const val = incomingAnswers[i] ?? '';
                        const inp = row.querySelector(
                            `textarea[name="questions[${idx}][answers][${i}]"], input[name="questions[${idx}][answers][${i}]"]`
                        );
                        if (inp) {
                            inp.value = val ?? '';
                            if (inp.tagName === 'TEXTAREA') syncAnswerTextUI(inp);
                        }
                    });
                }

                wrap.appendChild(q);
                initQuestion(q);
            });
        }
    }

    document.querySelectorAll('[data-question]').forEach(initQuestion);
    reindexQuestions();
    document.querySelectorAll('[data-option-text]').forEach(syncOptionTextUI);
    document.querySelectorAll('[data-answer-text]').forEach(syncAnswerTextUI);

    document
        .querySelectorAll('.form-section .segmented, .tc-segmented')
        .forEach(initSegmentedSlider);

    document.addEventListener('click', (e) => {
        const addAfterBtn = e.target.closest('[data-action="add-question-after"]');
        if (addAfterBtn) {
            const current = addAfterBtn.closest('[data-question]');
            if (!current || !questionTemplate) return;

            const clone = questionTemplate.cloneNode(true);
            clone.removeAttribute('data-qid');
            clone.removeAttribute('data-inited');

            clone.querySelectorAll('input, textarea').forEach((el) => {
                if (el.type === 'checkbox' || el.type === 'radio') el.checked = false;
                else el.value = '';
            });

            // сбрасываем img-upload в клоне нового вопроса
            clone.querySelectorAll('[data-img-upload]').forEach((imgWrap) => {
                imgWrap.removeAttribute('data-img-inited');
                const prevEl = imgWrap.querySelector('[data-img-preview]');
                if (prevEl) { prevEl.innerHTML = ''; prevEl.classList.remove('has-image'); }
                const hiddenEl = imgWrap.querySelector('input[type="hidden"]');
                if (hiddenEl) hiddenEl.value = '';
                const removeEl = imgWrap.querySelector('[data-img-remove]');
                if (removeEl) removeEl.style.display = 'none';
                const errEl = imgWrap.querySelector('[data-img-error]');
                if (errEl) errEl.textContent = '';
            });

            const select = clone.querySelector('[data-question-type]');
            if (select) select.value = 'radio';

            current.after(clone);
            initQuestion(clone);
            reindexQuestions();
            markFormDirty();
            return;
        }

        const removeBtn = e.target.closest('[data-action="remove-question"]');
        if (removeBtn) {
            const current = removeBtn.closest('[data-question]');
            if (!current) return;

            const allQuestions = document.querySelectorAll('[data-question]');
            if (allQuestions.length <= 1) {
                alert('Нельзя удалить последний вопрос');
                return;
            }

            current.remove();
            reindexQuestions();
            markFormDirty();
        }
    });
});

// ─── Dirty form guard ─────────────────────────────────────────────────────────

let formDirty = false;
let isSubmitting = false;
let autosaveTimer = 0;
let autosaveInFlight = false;
let draftChangeVersion = 0;
let sessionHasDraft = false; // становится true после первого успешного автосохранения черновика

function getTestCreateForm() {
    return document.querySelector('#testCreateForm');
}

function getSaveStatusEl() {
    return document.querySelector('[data-save-status]');
}

function saveStatusStateFromText(text) {
    const normalized = String(text || '').toLowerCase();
    if (normalized.includes('несохран')) return 'dirty';
    if (normalized.includes('ошибка') || normalized.includes('не удалось') || normalized.includes('http')) return 'error';
    if (normalized.includes('сохранение') || normalized.includes('автосохранение')) return 'saving';
    if (normalized.includes('сохранён') || normalized.includes('сохранен')) return 'saved';
    return 'idle';
}

function setSaveStatus(text, state = '') {
    const el = getSaveStatusEl();
    if (!el) return;

    const normalized = String(text || '').replace(/\s+/g, ' ').trim();
    el.textContent = normalized.length > 180
        ? normalized.slice(0, 177) + '...'
        : normalized;
    el.dataset.saveState = state || saveStatusStateFromText(normalized);
}

function isDraftEnabled() {
    const form = getTestCreateForm();
    return !!form && form.dataset.draftEnabled === '1';
}

function scheduleDraftAutosave() {
    if (!isDraftEnabled() || isSubmitting) return;
    window.clearTimeout(autosaveTimer);
    autosaveTimer = window.setTimeout(() => {
        void saveDraft('auto');
    }, DRAFT_AUTOSAVE_DELAY);
}

function markFormDirty() {
    draftChangeVersion += 1;
    formDirty = true;
    if (isDraftEnabled()) {
        setSaveStatus('Есть несохранённые изменения', 'dirty');
        scheduleDraftAutosave();
    }
}

function updateDraftRouting(payload) {
    const form = getTestCreateForm();
    if (!form || !payload || !payload.test_id) return;

    const testId = String(payload.test_id);
    form.dataset.testId = testId;
    form.dataset.testStatus = payload.status || 'draft';
    form.action = `/my/tests/${testId}`;
    if (payload.draft_url) form.dataset.draftUrl = payload.draft_url;
    if (payload.edit_url) {
        form.dataset.editUrl = payload.edit_url;
        if (window.location.pathname === '/my/tests/create') {
            window.history.replaceState({}, '', payload.edit_url);
        }
    }

    const idInput = form.querySelector('[data-draft-test-id]');
    if (idInput) idInput.value = testId;

    sessionHasDraft = true;
}

async function parseResponsePayload(response) {
    const contentType = String(response.headers.get('content-type') || '').toLowerCase();

    if (contentType.includes('application/json')) {
        return await response.json();
    }

    const text = await response.text();
    const compactText = text.replace(/\s+/g, ' ').trim();
    const looksLikeHtmlError =
        /<\s*(?:!doctype|html|head|body|br|font|table|tr|td|th|span)\b/i.test(compactText)
        || compactText.includes('xdebug-error')
        || compactText.includes('Fatal error:')
        || compactText.includes('Stack trace:');

    return {
        ok: false,
        message: looksLikeHtmlError
            ? `Ошибка сервера при сохранении черновика (HTTP ${response.status})`
            : (compactText !== '' ? compactText.slice(0, 180) : `HTTP ${response.status}`)
    };
}

async function saveDraft(reason = 'manual') {
    const form = getTestCreateForm();
    if (!form || !isDraftEnabled() || autosaveInFlight) return false;
    if (!formDirty && reason === 'auto') return true;
    const requestedVersion = draftChangeVersion;

    autosaveInFlight = true;
    window.clearTimeout(autosaveTimer);
    setSaveStatus(reason === 'auto' ? 'Автосохранение…' : 'Сохранение черновика…', 'saving');

    try {
        const response = await fetch(form.dataset.draftUrl || '/my/tests/draft', {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await parseResponsePayload(response);
        if (!response.ok || !data.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        if (data.saved) {
            updateDraftRouting(data);
            if (requestedVersion === draftChangeVersion) {
                formDirty = false;
            }
        } else if (requestedVersion === draftChangeVersion) {
            formDirty = false;
        }

        setSaveStatus(data.message || 'Черновик сохранён', data.saved === false ? 'idle' : 'saved');
        return true;
    } catch (error) {
        setSaveStatus(error instanceof Error ? error.message : 'Ошибка сохранения черновика', 'error');
        return false;
    } finally {
        autosaveInFlight = false;
    }
}

function hasPrefilledData() {
    const form = document.querySelector('#testCreateForm');
    if (!form) return false;

    const oldQuestions = window.__OLD_QUESTIONS__ || [];
    if (Array.isArray(oldQuestions) && oldQuestions.length > 0) return true;

    const fields = form.querySelectorAll('input, textarea');
    for (const el of fields) {
        if (el.type === 'hidden') continue;
        if (el.type === 'checkbox' || el.type === 'radio') {
            if (el.checked) return true;
        } else if (String(el.value || '').trim() !== '') {
            return true;
        }
    }

    return false;
}

document.addEventListener('DOMContentLoaded', () => {
    formDirty = hasPrefilledData();
    if (isDraftEnabled()) {
        setSaveStatus(formDirty ? 'Есть несохранённые изменения' : 'Черновик ещё не сохранён', formDirty ? 'dirty' : 'idle');
    }

    const saveDraftBtn = document.querySelector('[data-save-draft]');
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', async () => {
            await saveDraft('manual');
        });
    }
});

function updateResultSettingsUi(animate = true) {
    const root = document.querySelector('[data-result-settings]');
    if (!root) return;
    const checked = root.querySelector('input[name="result_mode"]:checked');
    const mode    = checked ? checked.value : 'scale';
    root.dataset.activeMode = mode;

    const panels = [...root.querySelectorAll('.result-settings__panel')];

    if (!animate) {
        panels.forEach(p => {
            p.classList.remove('rs-enter', 'rs-exit');
            p.classList.toggle('is-active', p.dataset.resultPanel === mode);
        });
        return;
    }

    panels.forEach(panel => {
        const isTarget  = panel.dataset.resultPanel === mode;
        const wasActive = panel.classList.contains('is-active');

        if (isTarget && !wasActive) {
            panel.classList.remove('rs-exit');
            panel.classList.add('is-active', 'rs-enter');
            panel.addEventListener('animationend', () => {
                panel.classList.remove('rs-enter');
            }, { once: true });
        } else if (!isTarget && wasActive) {
            panel.classList.remove('is-active', 'rs-enter');
            panel.classList.add('rs-exit');
            panel.addEventListener('animationend', () => {
                panel.classList.remove('rs-exit');
            }, { once: true });
        }
    });
}

function validateResultSettings(showAlert = true) {
    const root = document.querySelector('[data-result-settings]');
    if (!root) return true;

    const errorEl = root.querySelector('[data-result-error]');
    const setError = (message) => {
        if (errorEl) errorEl.textContent = message;
        if (showAlert && message) alert(message);
    };
    setError('');

    const checked = root.querySelector('input[name="result_mode"]:checked');
    const mode = checked ? checked.value : 'scale';

    if (mode === 'pass_fail') {
        const input = root.querySelector('[data-pass-percent]');
        const value = Number.parseInt(input ? input.value : '', 10);
        if (!Number.isInteger(value) || value < 0 || value > 100) {
            setError('Проходной процент должен быть целым числом от 0 до 100');
            if (input) input.focus();
            return false;
        }
        return true;
    }

    const rows = Array.from(root.querySelectorAll('[data-gs-row]'));
    const maxValues = rows.map(row => Number.parseInt(row.querySelector('[data-gs-max]')?.value ?? '', 10));
    const scale = rows.map((row, i) => ({
        label: String(row.querySelector('[data-gs-label]')?.value ?? '').trim(),
        min:   i > 0 ? maxValues[i - 1] + 1 : 0,
        max:   maxValues[i],
        row,
    }));

    if (scale.length === 0) {
        setError('Добавьте шкалу оценок');
        return false;
    }

    let expectedMin = 0;
    for (const item of scale) {
        if (!item.label || !Number.isInteger(item.min) || !Number.isInteger(item.max) || item.min < 0 || item.max > 100 || item.min > item.max) {
            setError('Заполните все названия и границы шкалы оценок от 0 до 100');
            const focusTarget = item.row.querySelector('input');
            if (focusTarget) focusTarget.focus();
            return false;
        }
        if (item.min !== expectedMin) {
            setError('В шкале оценок не должно быть пропусков или пересечений');
            const focusTarget = item.row.querySelector('input[type="number"]');
            if (focusTarget) focusTarget.focus();
            return false;
        }
        expectedMin = item.max + 1;
    }

    if (expectedMin !== 101) {
        setError('Шкала оценок должна покрывать весь диапазон от 0 до 100');
        return false;
    }

    return true;
}

// ─── Custom Time Picker ────────────────────────────────────────────────────────

function initTimePicker() {
    const wrap = document.querySelector('.tc-time-wrap');
    if (!wrap) return;
    const nativeInput = wrap.querySelector('.tc-time-input');
    const openBtn     = wrap.querySelector('[data-time-picker-btn]');
    if (!nativeInput || !openBtn) return;

    const ITEM_H  = 40;
    const VISIBLE = 5;
    const PAD     = ITEM_H * 2;
    const COLS    = [
        { key: 'h', max: 24, label: 'Часы' },
        { key: 'm', max: 60, label: 'Мин'  },
        { key: 's', max: 60, label: 'Сек'  },
    ];

    let picker = null;
    let isOpen = false;

    function fmt(n) { return String(n).padStart(2, '0'); }

    function parseTime(val) {
        const p = (val || '00:00:00').split(':').map(n => parseInt(n, 10));
        return [
            isNaN(p[0]) ? 0 : Math.max(0, Math.min(23, p[0])),
            isNaN(p[1]) ? 0 : Math.max(0, Math.min(59, p[1])),
            isNaN(p[2]) ? 0 : Math.max(0, Math.min(59, p[2])),
        ];
    }

    function isMobile() { return window.matchMedia('(max-width: 720px)').matches; }

    function highlight(list) {
        const idx = Math.round(list.scrollTop / ITEM_H);
        list.querySelectorAll('.tc-time-picker__item').forEach((item, i) => {
            item.classList.toggle('is-selected', i === idx);
        });
    }

    function scrollToIdx(list, idx, smooth) {
        list.scrollTo({ top: idx * ITEM_H, behavior: smooth ? 'smooth' : 'instant' });
    }

    function build() {
        const el = document.createElement('div');
        el.className = 'tc-time-picker';
        el.setAttribute('hidden', '');

        const body = document.createElement('div');
        body.className = 'tc-time-picker__body';

        COLS.forEach((col, ci) => {
            if (ci > 0) {
                const sep = document.createElement('span');
                sep.className = 'tc-time-picker__sep';
                sep.textContent = ':';
                body.appendChild(sep);
            }

            const colEl = document.createElement('div');
            colEl.className = 'tc-time-picker__col';

            const listWrap = document.createElement('div');
            listWrap.className = 'tc-time-picker__list-wrap';

            const sel = document.createElement('div');
            sel.className = 'tc-time-picker__sel';
            listWrap.appendChild(sel);

            const list = document.createElement('div');
            list.className = 'tc-time-picker__list';
            list.dataset.col = col.key;

            const padT = document.createElement('div');
            padT.style.cssText = `height:${PAD}px;flex-shrink:0`;
            list.appendChild(padT);

            for (let v = 0; v < col.max; v++) {
                const item = document.createElement('div');
                item.className = 'tc-time-picker__item';
                item.textContent = fmt(v);
                item.dataset.v = v;
                list.appendChild(item);
            }

            const padB = document.createElement('div');
            padB.style.cssText = `height:${PAD}px;flex-shrink:0`;
            list.appendChild(padB);

            listWrap.appendChild(list);
            colEl.appendChild(listWrap);

            const label = document.createElement('div');
            label.className = 'tc-time-picker__col-label';
            label.textContent = col.label;
            colEl.appendChild(label);

            body.appendChild(colEl);

            // Scroll: debounced snap
            let snapTimer;
            list.addEventListener('scroll', () => {
                highlight(list);
                clearTimeout(snapTimer);
                snapTimer = setTimeout(() => {
                    const idx = Math.round(list.scrollTop / ITEM_H);
                    scrollToIdx(list, idx, true);
                    highlight(list);
                }, 120);
            }, { passive: true });

            // Click item
            list.addEventListener('click', e => {
                const item = e.target.closest('.tc-time-picker__item');
                if (item) scrollToIdx(list, parseInt(item.dataset.v, 10), true);
            });

            // Wheel
            list.addEventListener('wheel', e => {
                e.preventDefault();
                const cur  = Math.round(list.scrollTop / ITEM_H);
                const next = Math.max(0, Math.min(col.max - 1, cur + (e.deltaY > 0 ? 1 : -1)));
                scrollToIdx(list, next, true);
            }, { passive: false });
        });

        el.appendChild(body);

        const footer = document.createElement('div');
        footer.className = 'tc-time-picker__footer';

        const resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'btn btn-ghost btn-sm';
        resetBtn.textContent = 'Сбросить';
        resetBtn.addEventListener('click', () => {
            setVals([0, 0, 0]);
            nativeInput.value = '00:00:00';
            nativeInput.dispatchEvent(new Event('change'));
            close();
        });

        const applyBtn = document.createElement('button');
        applyBtn.type = 'button';
        applyBtn.className = 'btn btn-primary btn-sm';
        applyBtn.textContent = 'Готово';
        applyBtn.addEventListener('click', () => { apply(); close(); });

        footer.append(resetBtn, applyBtn);
        el.appendChild(footer);

        return el;
    }

    function getLists() { return picker ? [...picker.querySelectorAll('.tc-time-picker__list')] : []; }

    function setVals(vals) {
        getLists().forEach((list, i) => {
            scrollToIdx(list, vals[i] ?? 0, false);
            highlight(list);
        });
    }

    function apply() {
        const vals = getLists().map(l => Math.round(l.scrollTop / ITEM_H));
        nativeInput.value = `${fmt(vals[0])}:${fmt(vals[1])}:${fmt(vals[2])}`;
        nativeInput.dispatchEvent(new Event('change'));
    }

    function open() {
        if (isOpen) return;
        if (!picker) { picker = build(); wrap.appendChild(picker); }
        picker.removeAttribute('hidden');
        isOpen = true;
        setVals(parseTime(nativeInput.value));
        setTimeout(() => {
            document.addEventListener('mousedown', onOutside);
            document.addEventListener('keydown', onKey);
        }, 0);
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        picker?.setAttribute('hidden', '');
        document.removeEventListener('mousedown', onOutside);
        document.removeEventListener('keydown', onKey);
    }

    function onOutside(e) { if (!wrap.contains(e.target)) close(); }
    function onKey(e) {
        if (e.key === 'Escape') close();
        if (e.key === 'Enter') { apply(); close(); }
    }

    openBtn.addEventListener('click', () => {
        if (isMobile()) { nativeInput.showPicker?.(); }
        else { isOpen ? close() : open(); }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateResultSettingsUi(false);

    initTimePicker();
});

document.addEventListener('input', (e) => {
    if (e.target.closest('form')) markFormDirty();
});

document.addEventListener('change', (e) => {
    if (e.target && e.target.matches('[data-result-mode]')) {
        updateResultSettingsUi();
    }
    if (e.target.closest('form')) markFormDirty();
});

document.addEventListener('submit', (e) => {
    if (e.target.id === 'testCreateForm') {
        if (!validateResultSettings(true)) {
            e.preventDefault();
            e.stopPropagation();
            isSubmitting = false;
            return;
        }
        isSubmitting = true;
        window.clearTimeout(autosaveTimer);
        formDirty = false;
        sessionHasDraft = false;
    }
}, true);

window.addEventListener('pageshow', () => {
    isSubmitting = false;
    formDirty = hasPrefilledData();
    if (isDraftEnabled()) {
        setSaveStatus(formDirty ? 'Есть несохранённые изменения' : 'Черновик сохранён', formDirty ? 'dirty' : 'saved');
    }
});

window.addEventListener('beforeunload', (e) => {
    if (isSubmitting) return;
    if (!formDirty && !sessionHasDraft) return;
    e.preventDefault();
    /** @type {any} */ (e).returnValue = '';
});
