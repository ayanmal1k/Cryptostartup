/**
 * Notifications page
 */
'use strict';
/* global app, trans */

function getNotifPageChromeHeight() {
    var shell = document.querySelector('.flex-fill');
    if (shell && shell.getBoundingClientRect) {
        return Math.max(0, shell.getBoundingClientRect().top);
    }
    var appBar = document.querySelector('.mobile-app-bar');
    return appBar && appBar.offsetHeight ? appBar.offsetHeight : 0;
}

function adjustNotifPageHeight() {
    var page = document.querySelector('.notifications-page');
    if (!page) {
        return;
    }

    var available = window.innerHeight - getNotifPageChromeHeight();
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var shell = document.querySelector('.flex-fill');

    if (isMobile && shell) {
        shell.style.height = available + 'px';
        shell.style.maxHeight = available + 'px';
        shell.style.overflow = 'hidden';
        page.style.height = '100%';
        page.style.maxHeight = '100%';
        document.documentElement.classList.add('notif-page-mobile-lock');
        document.body.classList.add('notif-page-mobile-lock');
    } else if (shell) {
        shell.style.height = '';
        shell.style.maxHeight = '';
        shell.style.overflow = '';
        page.style.height = '';
        page.style.maxHeight = '';
        page.style.minHeight = page.classList.contains('notifications-page--empty') ? available + 'px' : '';
        document.documentElement.classList.remove('notif-page-mobile-lock');
        document.body.classList.remove('notif-page-mobile-lock');
    }
}

function scheduleNotifPageHeight() {
    adjustNotifPageHeight();
    window.requestAnimationFrame(adjustNotifPageHeight);
}

function closeNotifFilterMenu() {
    $('#notifications-list-filter-menu').attr('hidden', true).removeClass('is-open');
    $('#notifications-list-filter-btn').attr('aria-expanded', 'false');
}

function applyNotificationFilters() {
    var filter = window.notifListFilter || 'all';
    var visibleCount = 0;

    $('.notifications-feed .notifications-card').each(function () {
        var $card = $(this);
        var isRead = String($card.data('notification-read')) === '1';
        var matchesFilter = filter === 'all'
            || (filter === 'unread' && !isRead)
            || (filter === 'read' && isRead);
        $card.toggle(matchesFilter);
        if (matchesFilter) {
            visibleCount += 1;
        }
    });

    var $emptyFilter = $('.notifications-list-empty-filter');
    if (visibleCount === 0 && $('.notifications-feed .notifications-card').length > 0) {
        if (!$emptyFilter.length) {
            $('.notifications-feed').after(
                '<p class="notifications-list-empty-filter">' + (typeof trans === 'function' ? trans('No notifications match your filters.') : 'No notifications match your filters.') + '</p>'
            );
        }
    } else {
        $emptyFilter.remove();
    }
}

function initNotificationToolbar() {
    window.notifListFilter = 'all';

    $(document).on('click', '#notifications-list-filter-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $menu = $('#notifications-list-filter-menu');
        var isOpen = $menu.hasClass('is-open');
        closeNotifFilterMenu();
        if (!isOpen) {
            $menu.removeAttr('hidden').addClass('is-open');
            $(this).attr('aria-expanded', 'true');
        }
    });

    $(document).on('click', '.notifications-list-filter-option', function (e) {
        e.preventDefault();
        window.notifListFilter = $(this).data('filter');
        $('.notifications-list-filter-option').removeClass('is-active').attr('aria-selected', 'false');
        $(this).addClass('is-active').attr('aria-selected', 'true');
        $('.notifications-list-filter__label').text($(this).text().trim());
        closeNotifFilterMenu();
        applyNotificationFilters();
    });

    $(document).on('click', function () {
        closeNotifFilterMenu();
    });

    $(document).on('click', '.notifications-list-filter-wrap', function (e) {
        e.stopPropagation();
    });

    $(document).on('click', '#notifications-mark-all-read', function () {
        $('.notifications-card--unread').each(function () {
            $(this)
                .removeClass('notifications-card--unread unread')
                .attr('data-notification-read', '1');
        });
        applyNotificationFilters();
    });
}

$(function () {
    initNotificationToolbar();
    scheduleNotifPageHeight();
    window.addEventListener('resize', scheduleNotifPageHeight);
    window.addEventListener('orientationchange', scheduleNotifPageHeight);
});

/**
 * Notifications helper (live updates via pusher)
 */
// eslint-disable-next-line no-unused-vars
var notifications = {
    data: {},

    updateUserNotificationsList: function (activeFilter) {
        $.ajax({
            type: 'GET',
            url: activeFilter !== null
                ? app.baseUrl + '/my/notifications' + activeFilter + '?page=1&list=1'
                : app.baseUrl + '/my/notifications?page=1&list=1',
            success: function (result) {
                $('.notifications-list').replaceWith(result);
                applyNotificationFilters();
                scheduleNotifPageHeight();
            }
        });
    }
};
