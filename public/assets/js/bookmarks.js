(function () {
    'use strict';

    var forms = Array.prototype.slice.call(document.querySelectorAll('form[data-bookmark-toggle]'));
    var headerBookmarksBadge = document.querySelector('[data-header-bookmarks-badge]');
    var headerTrashBadge = document.querySelector('[data-header-trash-badge]');
    var bookmarksPageMeta = document.querySelector('[data-bookmarks-page-meta]');
    var bookmarksPageCount = document.querySelector('[data-bookmarks-page-count]');
    var bookmarksPageEmpty = document.querySelector('[data-bookmarks-empty]');
    var bookmarksPageListWrap = document.querySelector('[data-bookmarks-list-wrap]');
    var bookmarksPageList = document.querySelector('[data-bookmarks-list]');
    var channel = null;

    try {
        if ('BroadcastChannel' in window) {
            channel = new BroadcastChannel('qp-header-counters');
        }
    } catch (e) {
        channel = null;
    }

    function setBadgeValue(badge, value, labelPrefix) {
        if (!badge) return;
        var safe = Number.isFinite(value) && value > 0 ? Math.floor(value) : 0;
        badge.textContent = String(safe);
        badge.setAttribute('aria-label', labelPrefix + ': ' + safe);
        badge.classList.toggle('is-hidden', safe <= 0);
    }

    function setHeaderCounters(bookmarksCount, trashCount) {
        if (bookmarksCount !== undefined) {
            setBadgeValue(headerBookmarksBadge, Number(bookmarksCount), 'В закладках');
        }
        if (trashCount !== undefined) {
            setBadgeValue(headerTrashBadge, Number(trashCount), 'В корзине');
        }
    }

    function isBookmarksPage() {
        return /^\/my\/bookmarks(?:\/|$)/.test(window.location.pathname);
    }

    function setBookmarksPageCounters(total) {
        if (!isBookmarksPage()) return;
        var safe = Number.isFinite(total) && total > 0 ? Math.floor(total) : 0;

        if (bookmarksPageCount) {
            bookmarksPageCount.textContent = String(safe);
        }
        if (bookmarksPageMeta) {
            bookmarksPageMeta.classList.toggle('is-hidden', safe <= 0);
        }

        if (safe <= 0) {
            if (bookmarksPageEmpty) bookmarksPageEmpty.classList.remove('is-hidden');
            if (bookmarksPageListWrap) bookmarksPageListWrap.classList.add('is-hidden');
            if (bookmarksPageList) bookmarksPageList.innerHTML = '';
        } else {
            if (bookmarksPageEmpty) bookmarksPageEmpty.classList.add('is-hidden');
            if (bookmarksPageListWrap) bookmarksPageListWrap.classList.remove('is-hidden');
        }
    }

    function syncHeaderCountersFromServer() {
        if (!headerBookmarksBadge && !headerTrashBadge) return;
        fetch('/my/header/counters', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.ok) return;
                setHeaderCounters(payload.bookmarks_count, payload.trash_count);
            })
            .catch(function () {});
    }

    function updateBookmarkButton(button, isBookmarked) {
        if (!button) return;
        var addText = button.getAttribute('data-tooltip-add') || 'В закладки';
        var removeText = button.getAttribute('data-tooltip-remove') || 'Убрать из закладок';
        var tooltip = isBookmarked ? removeText : addText;
        button.classList.toggle('is-active', !!isBookmarked);
        button.setAttribute('data-tooltip', tooltip);
        button.setAttribute('aria-label', tooltip);
    }

    function updateButtonsForTest(testId, isBookmarked) {
        var selector = 'form[data-bookmark-toggle][data-test-id="' + String(testId) + '"] [data-bookmark-button]';
        var buttons = document.querySelectorAll(selector);
        buttons.forEach(function (button) {
            updateBookmarkButton(button, isBookmarked);
        });
    }

    function removeCardFromBookmarksPageByTestId(testId) {
        if (!isBookmarksPage()) return;
        var card = document.querySelector('.test-card[data-test-card-id="' + String(testId) + '"]');
        if (card) {
            card.remove();
        }
    }

    function bindForm(form) {
        if (!form || form.__bookmarkBound) return;
        form.__bookmarkBound = true;
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitBookmarkForm(form);
        });
    }

    function submitBookmarkForm(form) {
        var button = form.querySelector('[data-bookmark-button]');
        if (!button) {
            form.submit();
            return;
        }

        if (button.disabled) {
            return;
        }

        button.disabled = true;
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.ok) {
                    throw new Error((payload && payload.message) || 'toggle failed');
                }

                var testId = form.getAttribute('data-test-id') || '';
                var isBookmarked = !!payload.is_bookmarked;
                updateBookmarkButton(button, isBookmarked);
                if (testId !== '') {
                    updateButtonsForTest(testId, isBookmarked);
                }

                var bookmarksCount = Number(payload.user_bookmarks_count || 0);
                var trashCount = Number(payload.trash_count || 0);
                setHeaderCounters(bookmarksCount, trashCount);
                setBookmarksPageCounters(bookmarksCount);

                if (channel) {
                    channel.postMessage({
                        bookmarks_count: bookmarksCount,
                        trash_count: trashCount,
                        test_id: Number(testId || 0),
                        is_bookmarked: isBookmarked
                    });
                }

                if (testId !== '') {
                    if (!isBookmarked) {
                        removeCardFromBookmarksPageByTestId(testId);
                    }
                }
            })
            .catch(function () {
                form.submit();
            })
            .finally(function () {
                button.disabled = false;
            });
    }

    if (channel) {
        channel.onmessage = function (event) {
            var data = event && event.data ? event.data : null;
            if (!data) return;
            if (data.type === 'test-soft-deleted') {
                setHeaderCounters(data.bookmarks_count, data.trash_count);
                setBookmarksPageCounters(Number(data.bookmarks_count || 0));
                return;
            }

            setHeaderCounters(data.bookmarks_count, data.trash_count);
        };
    }

    syncHeaderCountersFromServer();

    forms.forEach(function (form) { bindForm(form); });
})();
