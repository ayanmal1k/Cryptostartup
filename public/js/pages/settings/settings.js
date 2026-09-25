/**
 * General settings component
 */
"use strict";
/* global app, trans, launchToast, initStickyComponent */

$(function () {
    var settingsPage = document.querySelector('.settings-page[class*="settings-page--"]');
    var inlineDrawer = document.getElementById('settingsInlineDrawer');
    var inlineToggleBtns = document.querySelectorAll('[data-settings-inline-drawer-toggle]');

    if (!settingsPage || !inlineDrawer || !inlineToggleBtns.length) {
        return;
    }

    function closeInlineSettingsDrawer() {
        inlineDrawer.classList.remove('is-open');
        inlineToggleBtns.forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    function openInlineSettingsDrawer(triggerBtn) {
        inlineDrawer.classList.add('is-open');
        inlineToggleBtns.forEach(function (btn) {
            btn.setAttribute('aria-expanded', btn === triggerBtn ? 'true' : 'false');
        });
    }

    function isInlineDrawerTrigger(target) {
        return Array.prototype.some.call(inlineToggleBtns, function (btn) {
            return btn === target || btn.contains(target);
        });
    }

    inlineToggleBtns.forEach(function (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (inlineDrawer.classList.contains('is-open')) {
                closeInlineSettingsDrawer();
            } else {
                openInlineSettingsDrawer(toggleBtn);
            }
        });
    });

    inlineDrawer.querySelectorAll('.settings-nav__link').forEach(function (link) {
        link.addEventListener('click', closeInlineSettingsDrawer);
    });

    document.addEventListener('click', function (event) {
        if (!inlineDrawer.classList.contains('is-open')) {
            return;
        }

        if (!inlineDrawer.contains(event.target) && !isInlineDrawerTrigger(event.target)) {
            closeInlineSettingsDrawer();
        }
    });
});

$(window).scroll(function () {
    initStickyComponent('.settings-menu-wrapper','sticky-sm');
});

// eslint-disable-next-line no-unused-vars
var GeneralSettings = {

    /**
     * Updates general (whitelisted) settings flags
     * @param key
     * @param value
     */
    updateFlagSetting: function (key,value) {
        $.ajax({
            type: 'POST',
            data: {
                'key': key,
                'value': value
            },
            dataType: 'json',
            url: app.baseUrl+'/my/settings/flags/save',
            success: function (result) {
                if(result.success){
                    launchToast('success',trans('Success'),trans('Setting saved'));
                }
                else{
                    launchToast('danger',trans('Error'),result.message);
                }
            },
            error: function () {
                launchToast('danger',trans('Error'),trans('Setting saving failed.'));
            }
        });
    }
};
