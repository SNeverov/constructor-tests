<div class="questions-section">
    <div class="questions">
        <div id="questionsList" class="questions__list">
                <div class="question-card" data-question data-index="0">
                    <div class="question-card__head" data-question-toggle>
                        <div class="question-card__title" data-question-title>
                            <span class="question-card__num">1</span>
                            <span class="question-card__preview" data-question-preview>Вопрос</span>
                        </div>
                        <div class="question-card__head-right">
                            <button type="button" class="question-card__collapse-btn" data-question-collapse-btn aria-label="Свернуть">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-question ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить вопрос" data-action="remove-question" aria-label="Удалить вопрос">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                                <span>Удалить</span>
                            </button>
                        </div>
                    </div>

                    <div class="question-card__body-wrap">
                    <div class="question-card__body">
                        <div class="form-row">
                            <label class="form-label">Текст вопроса</label>
                            <div class="question-text-wrap">
                                <textarea
                                    name="questions[0][text]"
                                    class="input question-textarea"
                                    data-question-text
                                    maxlength="1000"
                                    rows="1"
                                    placeholder="Например: Сколько будет 2+2?"
                                ></textarea>
                                <div class="field-char-limit" data-question-text-limit>0/1000</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="img-upload-wrap" data-img-upload="question">
                                <input type="hidden" name="questions[0][image_path]" value="">
                                <div class="img-upload-preview" data-img-preview></div>
                                <div class="img-upload-controls">
                                    <label class="btn btn-ghost btn-sm img-upload-btn ui-tooltip ui-tooltip--bottom" tabindex="0" data-tooltip="Добавить фото" aria-label="Добавить фото">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        <span data-img-btn-label>Добавить фото к вопросу</span>
                                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-img-file-input>
                                    </label>
                                    <button type="button" class="btn btn-danger btn-sm img-upload-remove ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить фото" aria-label="Удалить фото" data-img-remove style="display:none">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                        Удалить
                                    </button>
                                </div>
                                <div class="img-upload-error" data-img-error></div>
                            </div>
                        </div>

                        <div class="question-answers-wrap">
                            <div class="form-row question-type-row">
                                <label class="form-label question-type-label">Тип ответа</label>

                                <select name="questions[0][type]" class="input u-hidden" data-question-type aria-hidden="true" tabindex="-1">
                                    <option value="radio">Один вариант (radio)</option>
                                    <option value="checkbox">Несколько вариантов (checkbox)</option>
                                    <option value="input">Ввод текста (input)</option>
                                    <option value="order">Порядок</option>
                                </select>

                                <div class="segmented segmented--answer-type" data-question-type-ui role="radiogroup" aria-label="Тип ответа">
                                    <label class="segmented__item">
                                        <input type="radio" value="radio" data-question-type-radio checked>
                                        <span class="answer-type-btn">
                                            <i class="answer-type-mark answer-type-mark--radio" aria-hidden="true"></i>
                                            <b>Один</b>
                                        </span>
                                    </label>
                                    <label class="segmented__item">
                                        <input type="radio" value="checkbox" data-question-type-radio>
                                        <span class="answer-type-btn">
                                            <i class="answer-type-mark answer-type-mark--checkbox" aria-hidden="true"></i>
                                            <b>Несколько</b>
                                        </span>
                                    </label>
                                    <label class="segmented__item">
                                        <input type="radio" value="input" data-question-type-radio>
                                        <span class="answer-type-btn">
                                            <img class="answer-type-icon" src="/assets/img/test_create_svg/text.svg" alt="" aria-hidden="true">
                                            <b>Текст</b>
                                        </span>
                                    </label>
                                    <label class="segmented__item">
                                        <input type="radio" value="order" data-question-type-radio>
                                        <span class="answer-type-btn">
                                            <img class="answer-type-icon answer-type-icon--order" src="/assets/img/test_create_svg/sort-list.svg" alt="" aria-hidden="true">
                                            <b>Порядок</b>
                                        </span>
                                    </label>
                                </div>

                                <div class="answer-type-hints" aria-live="polite">
                                    <p class="answer-type-hint answer-type-hint--radio">Один верный вариант ответа.</p>
                                    <p class="answer-type-hint answer-type-hint--checkbox">Один или несколько верных вариантов ответа.</p>
                                    <p class="answer-type-hint answer-type-hint--input">Ответ необходимо написать текстом.</p>
                                    <p class="answer-type-hint answer-type-hint--order">Добавьте варианты в правильном порядке. В тесте они будут перемешаны.</p>
                                </div>
                            </div>

                            <div class="answers-block" data-block="options">
                                <div class="answers">
                                <div class="answer-row" data-option>
                                    <label class="correct-flag" title="Правильный ответ">
                                        <input type="hidden" name="questions[0][options][0][is_correct]" value="0">
                                        <input type="checkbox" name="questions[0][options][0][is_correct]" value="1" class="option-correct" aria-label="Правильный ответ">
                                    </label>

                                    <div class="option-text-wrap">
                                        <textarea
                                            name="questions[0][options][0][text]"
                                            class="input option-textarea"
                                            data-option-text
                                            placeholder="Вариант ответа"
                                            maxlength="1000"
                                            rows="1"
                                        ></textarea>
                                        <div class="field-char-limit" data-option-text-limit>0/1000</div>
                                    </div>

                                    <div class="img-upload-wrap img-upload-wrap--inline" data-img-upload="option">
                                        <input type="hidden" name="questions[0][options][0][image_path]" value="">
                                        <div class="img-upload-preview" data-img-preview></div>
                                        <label class="btn btn-ghost btn-sm btn-icon img-upload-btn ui-tooltip ui-tooltip--bottom" tabindex="0" data-tooltip="Добавить фото" aria-label="Добавить фото">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-img-file-input>
                                        </label>
                                        <button type="button" class="btn btn-danger-soft btn-sm btn-icon img-upload-remove ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить фото" aria-label="Удалить фото" data-img-remove style="display:none">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>

                                    <button type="button" class="btn btn-danger-soft btn-sm btn-icon ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-option aria-label="Удалить ответ">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </div>

                                <div class="answer-row" data-option>
                                    <label class="correct-flag" title="Правильный ответ">
                                        <input type="hidden" name="questions[0][options][1][is_correct]" value="0">
                                        <input type="checkbox" name="questions[0][options][1][is_correct]" value="1" class="option-correct" aria-label="Правильный ответ">
                                    </label>

                                    <div class="option-text-wrap">
                                        <textarea
                                            name="questions[0][options][1][text]"
                                            class="input option-textarea"
                                            data-option-text
                                            placeholder="Вариант ответа"
                                            maxlength="1000"
                                            rows="1"
                                        ></textarea>
                                        <div class="field-char-limit" data-option-text-limit>0/1000</div>
                                    </div>

                                    <div class="img-upload-wrap img-upload-wrap--inline" data-img-upload="option">
                                        <input type="hidden" name="questions[0][options][1][image_path]" value="">
                                        <div class="img-upload-preview" data-img-preview></div>
                                        <label class="btn btn-ghost btn-sm btn-icon img-upload-btn ui-tooltip ui-tooltip--bottom" tabindex="0" data-tooltip="Добавить фото" aria-label="Добавить фото">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-img-file-input>
                                        </label>
                                        <button type="button" class="btn btn-danger-soft btn-sm btn-icon img-upload-remove ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить фото" aria-label="Удалить фото" data-img-remove style="display:none">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>

                                    <button type="button" class="btn btn-danger-soft btn-sm btn-icon ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-option aria-label="Удалить ответ">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </div>
                                </div>
                            </div>

                            <div class="text-answers-block" data-block="text">
                                <div class="answers">
                                <div class="text-answer-row" data-answer>
                                    <div class="answer-text-wrap">
                                        <textarea
                                            name="questions[0][answers][0]"
                                            class="input answer-textarea"
                                            data-answer-text
                                            placeholder="Например: молоко"
                                            maxlength="1000"
                                            rows="1"
                                        ></textarea>
                                        <div class="field-char-limit" data-answer-text-limit>0/1000</div>
                                    </div>
                                    <button type="button" class="btn btn-danger-soft btn-sm btn-icon ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-answer aria-label="Удалить ответ">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </div>

                                <div class="text-answer-row" data-answer>
                                    <div class="answer-text-wrap">
                                        <textarea
                                            name="questions[0][answers][1]"
                                            class="input answer-textarea"
                                            data-answer-text
                                            placeholder="Альтернативный вариант (если нужен)"
                                            maxlength="1000"
                                            rows="1"
                                        ></textarea>
                                        <div class="field-char-limit" data-answer-text-limit>0/1000</div>
                                    </div>
                                    <button type="button" class="btn btn-danger-soft btn-sm btn-icon ui-tooltip ui-tooltip--bottom" data-tooltip="Удалить ответ" data-remove-answer aria-label="Удалить ответ">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </div>
                                </div>
                            </div>

                            <div class="question-actions question-actions--answers">
                                <button type="button" class="btn btn-outline btn-sm btn-add-variant" data-add-option>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                    <span>Добавить вариант</span>
                                </button>
                                <button type="button" class="btn btn-outline btn-sm btn-add-variant" data-add-answer>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                    Добавить правильный ответ
                                </button>
                            </div>

                        </div>

                        <div class="question-actions question-actions--question">
                            <button type="button" class="btn btn-outline btn-sm btn-add-question" data-action="add-question-after" data-add-question>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                <span>Добавить вопрос</span>
                            </button>
                        </div>
                    </div>
                    </div><!-- /.question-card__body-wrap -->
                </div>
        </div>
    </div>
    <div class="questions-add-row">
        <button type="button" class="btn btn-outline btn-md btn-add-question" data-action="add-question-after" data-add-question>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Добавить вопрос
        </button>
    </div>
</div>
