(function () {
    'use strict';

    var sidebar = document.getElementById('mobile-sidebar');
    var searchPanel = document.getElementById('mobile-search');
    var searchInput = document.getElementById('mobile-search-input');
    var searchResults = document.getElementById('mobile-search-results');
    var searchBrowse = document.getElementById('mobile-search-browse');
    var searchSuggestions = document.getElementById('mobile-search-suggestions');
    var searchTimeout;


    var DUMMY_CREATORS = [
        { name: 'Lia Sikora', username: 'lia_sikora', avatar: 'https://i.pravatar.cc/150?u=lia_sikora', verified: true },
        { name: 'sondra blust', username: 'sondrablust', avatar: 'https://i.pravatar.cc/150?u=sondrablust', verified: true },
        { name: 'Abby Berner', username: 'abbyberner', avatar: 'https://i.pravatar.cc/150?u=abbyberner', verified: true },
        { name: 'Mia Rose', username: 'miarose', avatar: 'https://i.pravatar.cc/150?u=miarose', verified: false },
        { name: 'Jade Lane', username: 'jadelane', avatar: 'https://i.pravatar.cc/150?u=jadelane', verified: true },
        { name: 'Ella Fox', username: 'ellafox', avatar: 'https://i.pravatar.cc/150?u=ellafox', verified: false }
    ];

    function unwrapList(payload) {
        if (!payload) return [];
        if (Array.isArray(payload)) return payload;
        if (Array.isArray(payload.data)) return payload.data;
        return [];
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isVerifiedUser(user) {
        if (!user) return false;
        if (user.is_verified || user.verified) return true;
        if (user.user_verify && user.user_verify.status === 'verified') return true;
        if (user.identity_verified_at) return true;
        return false;
    }

    function verifiedBadge() {
        return '<svg class="mobile-search__verified" viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path fill="currentColor" d="M10.5 15.2 7.8 12.5l-1.4 1.4 4.1 4.1 8.8-8.8-1.4-1.4-7.4 7.4z"/><path fill="currentColor" d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8z"/></svg>';
    }

    function lockReelScroll() {
        var feed = document.querySelector('.video-feed');
        if (!feed) return;
        if (feed.dataset.msLockedScrollTop == null) {
            feed.dataset.msLockedScrollTop = String(feed.scrollTop);
        }
        feed.style.overflow = 'hidden';
    }

    function unlockReelScroll() {
        if (document.body.classList.contains('mobile-sidebar-open') ||
            document.body.classList.contains('mobile-search-open') ||
            document.body.classList.contains('comments-open')) {
            return;
        }
        var feed = document.querySelector('.video-feed');
        if (!feed) return;
        feed.style.overflow = '';
        if (feed.dataset.msLockedScrollTop != null) {
            feed.scrollTop = parseFloat(feed.dataset.msLockedScrollTop) || 0;
            delete feed.dataset.msLockedScrollTop;
        }
    }

    function isOverlayOpen() {
        return document.body.classList.contains('mobile-sidebar-open') ||
            document.body.classList.contains('mobile-search-open');
    }

    function blockReelScrollThrough(e) {
        if (!isOverlayOpen()) return;

        // Let the sidebar/search scrollable areas handle their own scroll
        if (e.target.closest && e.target.closest('.mobile-sidebar__nav, .mobile-search__body, .mobile-search__results')) {
            return;
        }

        e.preventDefault();
    }

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('is-open');
        sidebar.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-sidebar-open');
        lockReelScroll();
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('is-open');
        sidebar.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-sidebar-open');
        unlockReelScroll();
    }

    function showSuggestions() {
        if (searchBrowse) searchBrowse.hidden = false;
        if (searchResults) {
            searchResults.hidden = true;
            searchResults.innerHTML = '';
        }
    }


    function renderCreatorChip(user) {
        var name = user.name || user.username || 'Creator';
        var username = user.username || '';
        var avatar = user.avatar || ('https://i.pravatar.cc/150?u=' + encodeURIComponent(username || name));
        var url = username ? '/' + encodeURIComponent(username) : '#';
        var handle = username ? '@' + username : '';

        return '<a href="' + escapeHtml(url) + '" class="mobile-search__creator-chip">' +
            '<div class="mobile-search__creator-avatar-wrap">' +
                '<img class="mobile-search__creator-avatar" src="' + escapeHtml(avatar) + '" alt="">' +
                '<span class="mobile-search__online-dot" aria-hidden="true"></span>' +
            '</div>' +
            '<span class="mobile-search__creator-name">' + escapeHtml(name) + '</span>' +
            '<span class="mobile-search__creator-handle">' + escapeHtml(handle) + (isVerifiedUser(user) ? verifiedBadge() : '') + '</span>' +
        '</a>';
    }

    function renderCreators(users) {
        if (!searchSuggestions) return;
        searchSuggestions.innerHTML = users.map(renderCreatorChip).join('');
    }



    function loadSuggestedCreators() {
        renderCreators(DUMMY_CREATORS);

        fetch('/search/users?query=a&encode_html=0', {
            headers: { Accept: 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var users = unwrapList(data.data).slice(0, 6);
                if (users.length >= 3) {
                    renderCreators(users);
                }
            })
            .catch(function () {});
    }

    function openSearch() {
        closeSidebar();
        if (!searchPanel) return;
        searchPanel.classList.add('is-open');
        searchPanel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-search-open');
        lockReelScroll();
        showSuggestions();
        loadSuggestedCreators();
        setTimeout(function () {
            if (searchInput) searchInput.focus();
        }, 280);
    }

    function closeSearch() {
        if (!searchPanel) return;
        searchPanel.classList.remove('is-open');
        searchPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-search-open');
        unlockReelScroll();
        if (searchInput) searchInput.value = '';
        showSuggestions();
    }

    function renderResultRow(user) {
        var name = user.name || user.username || 'Creator';
        var username = user.username || '';
        var avatar = user.avatar || ('https://i.pravatar.cc/150?u=' + encodeURIComponent(username));
        var url = username ? '/' + encodeURIComponent(username) : '#';

        return '<a href="' + escapeHtml(url) + '" class="mobile-search__result">' +
            '<img src="' + escapeHtml(avatar) + '" alt="">' +
            '<div class="mobile-search__result-meta">' +
                '<strong>' + escapeHtml(name) + (isVerifiedUser(user) ? verifiedBadge() : '') + '</strong>' +
                '<span>@' + escapeHtml(username) + '</span>' +
            '</div>' +
        '</a>';
    }

    function filterDummyCreators(query) {
        var q = query.toLowerCase();
        return DUMMY_CREATORS.filter(function (user) {
            return (user.name && user.name.toLowerCase().indexOf(q) !== -1) ||
                (user.username && user.username.toLowerCase().indexOf(q) !== -1);
        });
    }

    function performSearch(query) {
        if (!searchResults) return;
        if (searchBrowse) searchBrowse.hidden = true;
        searchResults.hidden = false;
        searchResults.innerHTML = '<div class="mobile-search__loading">Searching...</div>';

        fetch('/search/users?query=' + encodeURIComponent(query) + '&encode_html=0', {
            headers: { Accept: 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var users = unwrapList(data.data).slice(0, 20);
                if (!users.length) {
                    users = filterDummyCreators(query);
                }
                if (!users.length) {
                    searchResults.innerHTML = '<div class="mobile-search__placeholder">No results found</div>';
                    return;
                }
                searchResults.innerHTML = users.map(renderResultRow).join('');
            })
            .catch(function () {
                var users = filterDummyCreators(query);
                if (users.length) {
                    searchResults.innerHTML = users.map(renderResultRow).join('');
                } else {
                    searchResults.innerHTML = '<div class="mobile-search__placeholder">Search failed. Try again.</div>';
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('wheel', blockReelScrollThrough, { passive: false, capture: true });
        document.addEventListener('touchmove', blockReelScrollThrough, { passive: false, capture: true });

        document.querySelectorAll('[data-mobile-sidebar-open], #header-menu-btn, #mobile-sidebar-open').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openSidebar();
            });
        });

        document.querySelectorAll('[data-mobile-sidebar-close]').forEach(function (el) {
            el.addEventListener('click', closeSidebar);
        });

        document.querySelectorAll('[data-mobile-search-close]').forEach(function (el) {
            el.addEventListener('click', closeSearch);
        });

        var searchOpenBtn = document.getElementById('mobile-sidebar-search-open');
        var searchCloseBtn = document.getElementById('mobile-search-close');
        var searchBtn = document.getElementById('search-btn');

        document.querySelectorAll('[data-mobile-search-open], #mobile-app-bar-search-open').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openSearch();
            });
        });

        if (searchOpenBtn) searchOpenBtn.addEventListener('click', openSearch);
        if (searchCloseBtn) searchCloseBtn.addEventListener('click', closeSearch);
        if (searchBtn) {
            searchBtn.addEventListener('click', function (e) {
                e.preventDefault();
                openSearch();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                var q = this.value.trim();
                if (q.length < 2) {
                    showSuggestions();
                    return;
                }
                searchTimeout = setTimeout(function () { performSearch(q); }, 300);
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    var q = this.value.trim();
                    // No search page any more: Enter opens the top match.
                    var first = searchResults && searchResults.querySelector('a[href]');
                    if (q && first) window.location.href = first.getAttribute('href');
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSearch();
                closeSidebar();
            }
        });

        var moreToggle = document.getElementById('mobile-sidebar-more-toggle');
        var morePanel = document.getElementById('mobile-sidebar-more-panel');
        if (moreToggle && morePanel) {
            moreToggle.addEventListener('click', function () {
                var open = moreToggle.getAttribute('aria-expanded') === 'true';
                moreToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
                morePanel.hidden = open;
            });
        }

        var langToggle = document.getElementById('mobile-sidebar-lang-toggle');
        var langList = document.getElementById('mobile-sidebar-lang-list');
        if (langToggle && langList) {
            langToggle.addEventListener('click', function () {
                var open = langToggle.getAttribute('aria-expanded') === 'true';
                langToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
                langList.hidden = open;
            });
        }
    });

    window.MobileSidebar = {
        open: openSidebar,
        close: closeSidebar,
        openSearch: openSearch,
        closeSearch: closeSearch
    };
})();
