<div class="test-create">
    <?php
        $viewErrors   = isset($errors) && is_array($errors) ? $errors : [];
        $testId       = (int)($test_id ?? 0);
        $testTitle    = (string)($test_title ?? '');
        $isDraft      = (bool)($is_draft ?? false);
        $lastSavedAt  = trim((string)($last_saved_at ?? ''));
        $submitLabel  = (string)($submit_label ?? 'Опубликовать');
        $formAction   = (string)($form_action ?? '/my/tests/' . $testId . '/questions');
        $settingsUrl  = (string)($settings_url ?? '/my/tests/' . $testId . '/edit');
        $draftUrl     = (string)($draft_url ?? '/my/tests/' . $testId . '/draft');
        $oldQuestions = isset($old_questions) && is_array($old_questions) ? $old_questions : [];

        $testCreateJs = '/assets/js/test-create.js';
        $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $testCreateJsVersion = is_file($documentRoot . $testCreateJs) ? (string)filemtime($documentRoot . $testCreateJs) : '1';
    ?>

    <div class="page-head">
        <h1>Вопросы теста</h1>
        <?php if ($isDraft): ?>
            <div class="badge badge--warn">Черновик</div>
        <?php endif; ?>
    </div>

    <?php if ($testTitle !== ''): ?>
        <p class="tc-questions-subtitle">
            <a href="<?= htmlspecialchars($settingsUrl, ENT_QUOTES, 'UTF-8') ?>" class="tc-questions-back">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Настройки
            </a>
            <span class="tc-questions-test-title"><?= htmlspecialchars($testTitle, ENT_QUOTES, 'UTF-8') ?></span>
        </p>
    <?php endif; ?>

    <?php if (!empty($viewErrors)): ?>
        <div class="form-errors">
            <div class="form-errors__title">Проверьте вопросы перед публикацией</div>
            <ul>
                <?php foreach ($viewErrors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?= form_open($formAction, 'post', [
        'class'              => 'form',
        'id'                 => 'testCreateForm',
        'data-draft-enabled' => '1',
        'data-draft-url'     => $draftUrl,
        'data-edit-url'      => $settingsUrl,
        'data-test-id'       => (string)$testId,
        'data-test-status'   => $isDraft ? 'draft' : 'published',
    ]) ?>
        <input type="hidden" name="draft_test_id" value="<?= $testId ?>" data-draft-test-id>

        <?php include __DIR__ . '/partials/test-questions.php'; ?>

        <div class="test-create-submit">
            <button type="button" class="btn btn-ghost btn-draft" data-save-draft>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 9V17.8C19 18.9201 19 19.4802 18.782 19.908C18.5903 20.2843 18.2843 20.5903 17.908 20.782C17.4802 21 16.9201 21 15.8 21H8.2C7.07989 21 6.51984 21 6.09202 20.782C5.71569 20.5903 5.40973 20.2843 5.21799 19.908C5 19.4802 5 18.9201 5 17.8V6.2C5 5.07989 5 4.51984 5.21799 4.09202C5.40973 3.71569 5.71569 3.40973 6.09202 3.21799C6.51984 3 7.0799 3 8.2 3H13M19 9L13 3M19 9H14C13.4477 9 13 8.55228 13 8V3"/></svg>
                Сохранить черновик
            </button>

            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?>
            </button>

            <?php $initialSaveState = ($isDraft && $lastSavedAt !== '') ? 'saved' : 'idle'; ?>
            <div class="test-create-save-status" data-save-status data-save-state="<?= htmlspecialchars($initialSaveState, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isDraft && $lastSavedAt !== ''): ?>
                    Черновик сохранён
                <?php else: ?>
                    Черновик ещё не сохранён
                <?php endif; ?>
            </div>
        </div>
    </form>

    <script>
        window.__OLD_QUESTIONS__ = <?= json_encode(array_values($oldQuestions), JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <div id="imgLightbox" class="img-lightbox" aria-hidden="true" role="dialog" aria-label="Просмотр изображения">
        <div class="img-lightbox__backdrop" data-lightbox-close></div>
        <div class="img-lightbox__content">
            <button type="button" class="img-lightbox__close" data-lightbox-close aria-label="Закрыть">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <img src="" alt="" class="img-lightbox__img" id="imgLightboxImg">
        </div>
    </div>

    <script src="<?= htmlspecialchars($testCreateJs . '?v=' . rawurlencode($testCreateJsVersion), ENT_QUOTES, 'UTF-8') ?>"></script>
</div>
