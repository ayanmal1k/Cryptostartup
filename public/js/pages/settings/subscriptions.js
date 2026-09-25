/**
 * Subscription settings component
 */
"use strict";
/* global app, updateButtonState, trans */

function getSettingsSubsChromeHeight() {
    var shell = document.querySelector('.flex-fill');
    if (shell && shell.getBoundingClientRect) {
        return Math.max(0, shell.getBoundingClientRect().top);
    }
    return 0;
}

function adjustSettingsSubsHeight() {
    var page = document.querySelector('.settings-page--subs-empty');
    if (!page) {
        return;
    }

    var available = window.innerHeight - getSettingsSubsChromeHeight();
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var shell = document.querySelector('.flex-fill');

    if (isMobile && shell) {
        shell.style.height = available + 'px';
        shell.style.maxHeight = available + 'px';
        shell.style.overflow = 'hidden';
        page.style.height = '100%';
        page.style.maxHeight = '100%';
        document.documentElement.classList.add('st-subs-mobile-lock');
        document.body.classList.add('st-subs-mobile-lock');
    } else if (shell) {
        shell.style.height = '';
        shell.style.maxHeight = '';
        shell.style.overflow = '';
        page.style.height = '';
        page.style.maxHeight = '';
        document.documentElement.classList.remove('st-subs-mobile-lock');
        document.body.classList.remove('st-subs-mobile-lock');
    }
}

function scheduleSettingsSubsHeight() {
    adjustSettingsSubsHeight();
    window.requestAnimationFrame(adjustSettingsSubsHeight);
}

$(function () {
    if (document.querySelector('.settings-page--subs-empty')) {
        scheduleSettingsSubsHeight();
        window.addEventListener('resize', scheduleSettingsSubsHeight);
        window.addEventListener('orientationchange', scheduleSettingsSubsHeight);
    }
});

var SubscriptionsSettings = {
    selectedSubID: null,
    redirectTo: 'subscriptions',
    confirmSubCancelation: function (subIDToCancel, redirectTo = 'subscriptions') {
        SubscriptionsSettings.redirectTo = redirectTo;
        SubscriptionsSettings.selectedSubID = subIDToCancel;
        $('#subscription-cancel-dialog').modal('show');
    },
    cancelSubscription: function () {
        updateButtonState('loading',$('#subscription-cancel-dialog .btn'), trans('Confirm'));
        window.location.href = app.baseUrl + '/subscriptions/'+SubscriptionsSettings.selectedSubID+'/cancel/'+SubscriptionsSettings.redirectTo;
    }
};
