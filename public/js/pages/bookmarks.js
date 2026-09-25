/*
 * Bookmarks page
 */
"use strict";
/* global app, paginatorConfig, initialPostIDs, PostsPaginator, Post, getCookie */

function getBmPageChromeHeight() {
    var shell = document.querySelector('.flex-fill');
    if (shell && shell.getBoundingClientRect) {
        return Math.max(0, shell.getBoundingClientRect().top);
    }
    var appBar = document.querySelector('.mobile-app-bar');
    return appBar && appBar.offsetHeight ? appBar.offsetHeight : 0;
}

function adjustBmPageHeight() {
    var page = document.querySelector('.bookmarks-page');
    if (!page) {
        return;
    }

    var available = window.innerHeight - getBmPageChromeHeight();
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var shell = document.querySelector('.flex-fill');

    if (isMobile && shell) {
        shell.style.height = available + 'px';
        shell.style.maxHeight = available + 'px';
        shell.style.overflow = 'hidden';
        page.style.height = '100%';
        page.style.maxHeight = '100%';
        document.documentElement.classList.add('bm-page-mobile-lock');
        document.body.classList.add('bm-page-mobile-lock');
    } else if (shell) {
        shell.style.height = '';
        shell.style.maxHeight = '';
        shell.style.overflow = '';
        page.style.height = '';
        page.style.maxHeight = '';
        page.style.minHeight = page.classList.contains('bookmarks-page--empty') ? available + 'px' : '';
        document.documentElement.classList.remove('bm-page-mobile-lock');
        document.body.classList.remove('bm-page-mobile-lock');
    }
}

function scheduleBmPageHeight() {
    adjustBmPageHeight();
    window.requestAnimationFrame(adjustBmPageHeight);
}

$(function () {
    if (typeof paginatorConfig !== 'undefined') {
        if ((paginatorConfig.total > 0 && paginatorConfig.total > paginatorConfig.per_page) && paginatorConfig.hasMore) {
            PostsPaginator.initScrollLoad();
        }
        PostsPaginator.init(paginatorConfig.next_page_url, '.posts-wrapper', 'POST');
    } else {
        // eslint-disable-next-line no-console
        console.error('Pagination failed to initialize.');
    }

    PostsPaginator.initPostsGalleries(initialPostIDs);
    Post.setActivePage('feed');
    if (getCookie('app_prev_post') !== null) {
        PostsPaginator.scrollToLastPost(getCookie('app_prev_post'));
    }
    Post.initPostsMediaModule();
    PostsPaginator.initDescriptionTogglers();
    if (app.feedDisableRightClickOnMedia !== null) {
        Post.disablePostsRightClick();
    }

    scheduleBmPageHeight();
    window.addEventListener('resize', scheduleBmPageHeight);
    window.addEventListener('orientationchange', scheduleBmPageHeight);
});

window.onunload = function () {
    window.scrollTo(0, 0);
};

// eslint-disable-next-line no-unused-vars
var Bookmarks = {};
