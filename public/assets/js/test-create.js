const MAX_OPTIONS = 10;
const MAX_INPUT_ANSWERS = 10;
const QUESTION_TEXT_MAX = 1000;
const QUESTION_TEXT_MIN_HEIGHT = 36;
const TEST_DESCRIPTION_MAX = 500;
const TEST_DESCRIPTION_MIN_HEIGHT = 76;
const DRAFT_AUTOSAVE_DELAY = 3000;

// ─── Lightbox ────────────────────────────────────────────────────────────────

function openLightbox(src, fullSize) {
    const lb = document.getElementById('imgLightbox');
    const img = document.getElementById('imgLightboxImg');
    if (!lb || !img) return;

    img.style.width = '';
    img.style.height = '';
    img.src = '';
    lb.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

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
        document.body.style.overflow = '';
    }, { once: true });
}

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-lightbox-close]')) closeLightbox();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
});

// ─── Image upload widget ──────────────────────────────────────────────────────

function resetImgUploadWrap(wrap) {
    const preview = wrap.querySelector('[data-img-preview]');
    const hidden  = wrap.querySelector('input[type="hidden"]');
    const remove  = wrap.querySelector('[data-img-remove]');
    const btnLabel = wrap.querySelector('[data-img-btn-label]');
    const err     = wrap.querySelector('[data-img-error]');
    const fileInput = wrap.querySelector('[data-img-file-input]');

    if (preview) {
        preview.innerHTML = '';
        preview.classList.remove('has-image');
    }
    if (hidden) hidden.value = '';
    if (remove) remove.style.display = 'none';
    if (err) err.textContent = '';
    if (fileInput) fileInput.value = '';
    if (btnLabel) {
        const type = wrap.dataset.imgUpload;
        btnLabel.textContent = type === 'cover' ? 'Загрузить обложку' : 'Добавить фото к вопросу';
    }
}

function setImgUploadValue(wrap, path) {
    if (!path) { resetImgUploadWrap(wrap); return; }

    const preview  = wrap.querySelector('[data-img-preview]');
    const hidden   = wrap.querySelector('input[type="hidden"]');
    const remove   = wrap.querySelector('[data-img-remove]');
    const btnLabel = wrap.querySelector('[data-img-btn-label]');

    if (hidden) hidden.value = path;

    if (preview) {
        preview.innerHTML = `
            <img src="${path}" alt="" class="img-upload-thumb">
            <button type="button" class="img-upload-zoom" data-img-zoom aria-label="Увеличить">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>`;
        preview.classList.add('has-image');
    }
    if (remove) remove.style.display = '';
    if (btnLabel) btnLabel.textContent = 'Заменить фото';
}

function initImgUpload(wrap) {
    if (wrap.dataset.imgInited === '1') return;
    wrap.dataset.imgInited = '1';

    const type      = wrap.dataset.imgUpload;
    const fileInput = wrap.querySelector('[data-img-file-input]');
    const remove    = wrap.querySelector('[data-img-remove]');
    const err       = wrap.querySelector('[data-img-error]');
    const csrfInput = document.querySelector('input[name="csrf_token"]');

    if (!fileInput) return;

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        if (err) err.textContent = '';

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
            if (hidden && hidden.value) openLightbox(hidden.value);
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
        const label = checked.closest('.segmented__item');
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
    const questionTextLimit = q.querySelector('[data-question-text-limit]');

    function syncQuestionTextUI() {
        if (!questionText) return;
        questionText.style.height = 'auto';
        questionText.style.height = `${Math.max(questionText.scrollHeight, QUESTION_TEXT_MIN_HEIGHT)}px`;
        if (questionTextLimit) {
            questionTextLimit.textContent = `${questionText.value.length}/${QUESTION_TEXT_MAX}`;
        }
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
        q.classList.toggle('is-type-input', isInput);

        if (optionsBlock) optionsBlock.style.display = isInput ? 'none' : '';
        if (textBlock) textBlock.style.display = isInput ? '' : 'none';
        if (addOptionBtn) addOptionBtn.style.display = isInput ? 'none' : '';

        // скрываем img-upload на вариантах если input-тип
        q.querySelectorAll('[data-option] [data-img-upload="option"]').forEach((w) => {
            w.style.display = isInput ? 'none' : '';
        });

        const correctInputs = q.querySelectorAll('.option-correct');
        correctInputs.forEach((el) => {
            el.type = type === 'radio' ? 'radio' : 'checkbox';
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
    const testDescription = document.querySelector('[data-test-description]');
    const testDescriptionLimit = document.querySelector('[data-test-description-limit]');

    function syncTestDescriptionUI() {
        if (!testDescription) return;
        testDescription.style.height = 'auto';
        testDescription.style.height = `${Math.max(testDescription.scrollHeight, TEST_DESCRIPTION_MIN_HEIGHT)}px`;
        if (testDescriptionLimit) {
            testDescriptionLimit.textContent = `${testDescription.value.length}/${TEST_DESCRIPTION_MAX}`;
        }
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

                        const textInput = row.querySelector(`input[name="questions[${idx}][options][${i}][text]"]`);
                        if (textInput) textInput.value = opt.text ?? '';

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
                        const inp = row.querySelector(`input[name="questions[${idx}][answers][${i}]"]`);
                        if (inp) inp.value = val ?? '';
                    });
                }

                wrap.appendChild(q);
                initQuestion(q);
            });
        }
    }

    document.querySelectorAll('[data-question]').forEach(initQuestion);
    reindexQuestions();

    document.querySelectorAll('.form-section .segmented').forEach(initSegmentedSlider);

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

function getTestCreateForm() {
    return document.querySelector('#testCreateForm');
}

function getSaveStatusEl() {
    return document.querySelector('[data-save-status]');
}

function setSaveStatus(text) {
    const el = getSaveStatusEl();
    if (el) el.textContent = text;
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
        setSaveStatus('Есть несохранённые изменения');
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
}

async function parseResponsePayload(response) {
    const contentType = String(response.headers.get('content-type') || '').toLowerCase();

    if (contentType.includes('application/json')) {
        return await response.json();
    }

    const text = await response.text();
    const compactText = text.replace(/\s+/g, ' ').trim();
    return {
        ok: false,
        message: compactText !== '' ? compactText.slice(0, 300) : `HTTP ${response.status}`
    };
}

async function saveDraft(reason = 'manual') {
    const form = getTestCreateForm();
    if (!form || !isDraftEnabled() || autosaveInFlight) return false;
    if (!formDirty && reason === 'auto') return true;
    const requestedVersion = draftChangeVersion;

    autosaveInFlight = true;
    window.clearTimeout(autosaveTimer);
    setSaveStatus(reason === 'auto' ? 'Автосохранение…' : 'Сохранение черновика…');

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

        setSaveStatus(data.message || 'Черновик сохранён');
        return true;
    } catch (error) {
        setSaveStatus(error instanceof Error ? error.message : 'Ошибка сохранения черновика');
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
        setSaveStatus(formDirty ? 'Есть несохранённые изменения' : 'Черновик ещё не сохранён');
    }

    const saveDraftBtn = document.querySelector('[data-save-draft]');
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', async () => {
            await saveDraft('manual');
        });
    }
});

document.addEventListener('input', (e) => {
    if (e.target.closest('form')) markFormDirty();
});

document.addEventListener('change', (e) => {
    if (e.target.closest('form')) markFormDirty();
});

document.addEventListener('submit', (e) => {
    if (e.target.id === 'testCreateForm') {
        isSubmitting = true;
        window.clearTimeout(autosaveTimer);
        formDirty = false;
    }
}, true);

window.addEventListener('pageshow', () => {
    isSubmitting = false;
    formDirty = hasPrefilledData();
    if (isDraftEnabled()) {
        setSaveStatus(formDirty ? 'Есть несохранённые изменения' : 'Черновик сохранён');
    }
});

window.addEventListener('beforeunload', (e) => {
    if (isSubmitting) return;
    if (!formDirty) return;
    e.preventDefault();
    /** @type {any} */ (e).returnValue = '';
});
