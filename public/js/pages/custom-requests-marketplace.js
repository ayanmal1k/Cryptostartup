/**
 * Custom Requests Marketplace page
 */
'use strict';

function getCrMarketplaceChromeHeight() {
    var shell = document.querySelector('.flex-fill');
    if (shell && shell.getBoundingClientRect) {
        return Math.max(0, shell.getBoundingClientRect().top);
    }
    var appBar = document.querySelector('.mobile-app-bar');
    return appBar && appBar.offsetHeight ? appBar.offsetHeight : 0;
}

function adjustCrMarketplaceHeight() {
    var page = document.querySelector('.custom-requests-marketplace');
    if (!page) {
        return;
    }

    var available = window.innerHeight - getCrMarketplaceChromeHeight();
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var isEmptyLayout = page.classList.contains('custom-requests-marketplace--empty-layout');
    var shell = document.querySelector('.flex-fill');

    if (isMobile && shell) {
        shell.style.height = available + 'px';
        shell.style.maxHeight = available + 'px';
        shell.style.overflow = 'hidden';
        page.style.height = '100%';
        page.style.maxHeight = '100%';
        page.style.minHeight = '';
        document.documentElement.classList.add('cr-marketplace-mobile-lock');
        document.body.classList.add('cr-marketplace-mobile-lock');
    } else if (shell && isEmptyLayout) {
        shell.style.height = '';
        shell.style.maxHeight = '';
        shell.style.overflow = '';
        page.style.height = '';
        page.style.maxHeight = '';
        page.style.minHeight = available + 'px';
        document.documentElement.classList.remove('cr-marketplace-mobile-lock');
        document.body.classList.remove('cr-marketplace-mobile-lock');
    } else if (shell) {
        shell.style.height = '';
        shell.style.maxHeight = '';
        shell.style.overflow = '';
        page.style.height = '';
        page.style.maxHeight = '';
        page.style.minHeight = '';
        document.documentElement.classList.remove('cr-marketplace-mobile-lock');
        document.body.classList.remove('cr-marketplace-mobile-lock');
    }
}

function scheduleCrMarketplaceHeight() {
    window.requestAnimationFrame(function () {
        adjustCrMarketplaceHeight();
        window.requestAnimationFrame(adjustCrMarketplaceHeight);
    });
}

function enhanceMarketplaceSelects() {
    document.querySelectorAll('.filter-field select.filter-select').forEach(function (select) {
        if (select.dataset.crEnhanced === '1') {
            return;
        }

        select.dataset.crEnhanced = '1';

        var field = select.closest('.filter-field');
        if (!field) {
            return;
        }

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'cr-select-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        var menu = document.createElement('ul');
        menu.className = 'cr-select-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;

        function syncTriggerLabel() {
            var selected = select.options[select.selectedIndex];
            trigger.textContent = selected ? selected.textContent : '';
        }

        function closeMenu() {
            menu.hidden = true;
            menu.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            document.removeEventListener('click', onDocumentClick);
        }

        function onDocumentClick(event) {
            if (!field.contains(event.target)) {
                closeMenu();
            }
        }

        Array.from(select.options).forEach(function (option) {
            var item = document.createElement('li');
            item.className = 'cr-select-option' + (option.selected ? ' is-selected' : '');
            item.setAttribute('role', 'option');
            item.dataset.value = option.value;
            item.textContent = option.textContent;

            item.addEventListener('click', function (event) {
                event.stopPropagation();
                select.value = option.value;
                menu.querySelectorAll('.cr-select-option').forEach(function (node) {
                    node.classList.toggle('is-selected', node === item);
                });
                syncTriggerLabel();
                closeMenu();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });

            menu.appendChild(item);
        });

        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = menu.hidden;
            document.querySelectorAll('.cr-select-menu.is-open').forEach(function (openMenu) {
                if (openMenu !== menu) {
                    openMenu.hidden = true;
                    openMenu.classList.remove('is-open');
                }
            });

            if (willOpen) {
                document.querySelectorAll('.cr-select-trigger').forEach(function (otherTrigger) {
                    if (otherTrigger !== trigger) {
                        otherTrigger.setAttribute('aria-expanded', 'false');
                    }
                });
                menu.hidden = false;
                menu.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                document.addEventListener('click', onDocumentClick);
            } else {
                closeMenu();
            }
        });

        select.classList.add('cr-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        syncTriggerLabel();
        field.appendChild(trigger);
        field.appendChild(menu);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    scheduleCrMarketplaceHeight();
    window.addEventListener('resize', scheduleCrMarketplaceHeight);
    window.addEventListener('load', scheduleCrMarketplaceHeight);
    enhanceMarketplaceSelects();

    var i18n = window.crMarketplaceI18n || {};
    var contributeBtns = document.querySelectorAll('.btn-contribute');
    var contributeModalElement = document.getElementById('contributeModal');
    var contributeModal = null;

    if (contributeModalElement) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            contributeModal = new bootstrap.Modal(contributeModalElement);
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            contributeModal = {
                show: function () { $(contributeModalElement).modal('show'); },
                hide: function () { $(contributeModalElement).modal('hide'); }
            };
        }
    }

    var contributeForm = document.getElementById('contributeForm');
    var modalTitle = document.getElementById('modal-request-title');

    contributeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var requestId = this.getAttribute('data-request-id');
            var requestTitle = this.getAttribute('data-title');
            var requestIdInput = document.getElementById('request_id');

            if (requestIdInput) {
                requestIdInput.value = requestId;
            }
            if (modalTitle) {
                modalTitle.textContent = requestTitle;
            }
            if (contributeForm) {
                contributeForm.reset();
                if (requestIdInput) {
                    requestIdInput.value = requestId;
                }
            }
            if (contributeModal) {
                contributeModal.show();
            }
        });
    });

    if (contributeForm) {
        contributeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var requestId = document.getElementById('request_id').value;
            var amount = document.getElementById('amount').value;
            var message = document.getElementById('message').value;
            var submitBtn = contributeForm.querySelector('button[type="submit"]');
            var originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (i18n.processing || 'Processing...');

            fetch('/custom-requests/' + requestId + '/contribute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ amount: amount, message: message })
            })
                .then(function (response) {
                    var contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    }
                    throw { message: 'Invalid response from server' };
                })
                .then(function (data) {
                    if (data.success) {
                        var msg = data.message || i18n.success || 'Contribution added successfully!';
                        if (typeof launchToast !== 'undefined') {
                            launchToast('success', i18n.successTitle || 'Success', msg);
                        } else {
                            alert(msg);
                        }
                        if (contributeModal) {
                            contributeModal.hide();
                        }
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        var errorMsg = data.message || i18n.failed || 'Failed to add contribution';
                        if (typeof launchToast !== 'undefined') {
                            launchToast('danger', i18n.errorTitle || 'Error', errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(function (error) {
                    var errorMsg = error.message || i18n.genericError || 'An error occurred. Please try again.';
                    if (typeof launchToast !== 'undefined') {
                        launchToast('danger', i18n.errorTitle || 'Error', errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    }

    var searchInput = document.getElementById('searchInput');
    var clearSearch = document.getElementById('clearSearch');
    var statusFilter = document.getElementById('statusFilter');
    var sortBy = document.getElementById('sortBy');
    var requestItems = document.querySelectorAll('.request-card');

    if (searchInput && clearSearch) {
        searchInput.addEventListener('input', function () {
            clearSearch.style.display = this.value ? 'flex' : 'none';
        });

        clearSearch.addEventListener('click', function () {
            searchInput.value = '';
            clearSearch.style.display = 'none';
            filterRequests();
        });
    }

    function filterRequests() {
        var searchTerm = (searchInput && searchInput.value ? searchInput.value : '').toLowerCase();
        var statusValue = statusFilter ? statusFilter.value : '';
        var sortValue = sortBy ? sortBy.value : 'newest';
        var visibleCount = 0;

        requestItems.forEach(function (item) {
            var title = item.dataset.title || '';
            var description = item.dataset.description || '';
            var status = item.dataset.status || '';
            var matchesSearch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);
            var matchesStatus = !statusValue || status === statusValue;

            if (matchesSearch && matchesStatus) {
                item.style.display = '';
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.style.display = 'none';
                item.classList.add('hidden');
            }
        });

        var container = document.getElementById('requestsContainer');
        var emptyState = container ? container.querySelector('.empty-state.filter-empty') : null;

        if (container && visibleCount === 0 && requestItems.length > 0 && !emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state filter-empty';
            emptyState.innerHTML =
                '<div class="empty-icon"><i class="fas fa-search"></i></div>' +
                '<h3>' + (i18n.noResults || 'No requests found') + '</h3>' +
                '<p>' + (i18n.tryAdjusting || 'Try adjusting your search or filter criteria') + '</p>';
            container.appendChild(emptyState);
        } else if (emptyState && visibleCount > 0) {
            emptyState.remove();
        }

        sortRequests(sortValue);
    }

    function sortRequests(sortType) {
        var container = document.getElementById('requestsContainer');
        if (!container) {
            return;
        }

        var visibleItems = Array.from(requestItems).filter(function (item) {
            return !item.classList.contains('hidden');
        });

        visibleItems.sort(function (a, b) {
            switch (sortType) {
                case 'most-funded':
                    return parseFloat(b.dataset.amount || 0) - parseFloat(a.dataset.amount || 0);
                case 'closest-goal':
                    return parseFloat(b.dataset.progress || 0) - parseFloat(a.dataset.progress || 0);
                default:
                    return 0;
            }
        });

        visibleItems.forEach(function (item) {
            container.appendChild(item);
        });
    }

    var searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterRequests, 300);
        });
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', filterRequests);
    }

    if (sortBy) {
        sortBy.addEventListener('change', filterRequests);
    }
});
