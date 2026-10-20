/**
 * Live Streams index page
 */
"use strict";

function getStreamsChromeHeight() {
    var shell = document.querySelector('.flex-fill');
    if (shell && shell.getBoundingClientRect) {
        return Math.max(0, shell.getBoundingClientRect().top);
    }
    var appBar = document.querySelector('.mobile-app-bar');
    return appBar && appBar.offsetHeight ? appBar.offsetHeight : 0;
}

function adjustStreamsHeight() {
    var $page = $('.streams-page');
    if (!$page.length) {
        return;
    }

    var available = window.innerHeight - getStreamsChromeHeight();
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var $shell = $('.flex-fill');

    if (isMobile) {
        $shell.css({ height: available + 'px', maxHeight: available + 'px', overflow: 'hidden' });
        $page.css({ height: '100%', maxHeight: '100%' });
        document.documentElement.classList.add('streams-mobile-lock');
        document.body.classList.add('streams-mobile-lock');
    } else {
        $shell.css({ height: '', maxHeight: '', overflow: '' });
        $page.css({ height: '', maxHeight: '' });
        document.documentElement.classList.remove('streams-mobile-lock');
        document.body.classList.remove('streams-mobile-lock');
    }
}

$(function () {
    adjustStreamsHeight();
    $(window).on('resize', adjustStreamsHeight);
});
