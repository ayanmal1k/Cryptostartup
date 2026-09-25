{{--
    Reel prefetching.

    The next clips are fetched while the current one plays, so scrolling down lands
    on a video that is already buffered instead of a spinner.

    This is the single owner of `preload` on reel videos. It has to be, because the
    two pages disagreed with each other: pages/home shipped preload="auto" on every
    element, so all ten clips downloaded at once on page load, while pages/feed and
    videos/reels shipped preload="metadata", so nothing was ready and every scroll
    hit the loader. Neither is right -- what you want is a window that travels with
    the viewer.

    Window: one behind (scrolling back up is common), the current one, and two
    ahead. Everything outside it drops to preload="none" so a long feed does not
    quietly pull down every clip in it.
--}}
<script>
(function () {
    'use strict';

    var AHEAD_DEFAULT = 2;
    var BEHIND = 1;

    function conn() {
        return navigator.connection || navigator.mozConnection || navigator.webkitConnection || null;
    }

    /**
     * How many clips to pull down ahead of the viewer.
     *
     * Prefetching is spending someone's data on a guess about what they will watch
     * next. On a metered or slow link that trade stops being worth it, so the window
     * shrinks -- and with data saver on, the user has explicitly asked us not to.
     */
    function aheadCount() {
        var c = conn();
        if (!c) return AHEAD_DEFAULT;
        if (c.saveData) return 0;
        var t = c.effectiveType || '';
        if (t === 'slow-2g' || t === '2g') return 0;
        if (t === '3g') return 1;
        return AHEAD_DEFAULT;
    }

    var items = [];
    var activeIndex = 0;
    var applyTimer = null;

    function refresh() {
        items = Array.prototype.slice.call(document.querySelectorAll('.video-item'));
    }

    function videoOf(item) {
        return item ? item.querySelector('video') : null;
    }

    function setPreload(video, value) {
        if (video.getAttribute('preload') !== value) {
            video.setAttribute('preload', value);
        }
    }

    /**
     * Start pulling bytes for a clip that is not on screen yet.
     *
     * Setting preload after the element has been created does not on its own make a
     * browser go and fetch; load() is what kicks it off. That makes load() dangerous
     * on the wrong element -- it resets playback -- so this only ever touches a video
     * that is paused, unstarted and has nothing buffered.
     */
    function warm(video) {
        if (video.dataset.prefetched === '1') return;
        if (!video.paused || video.currentTime > 0 || video.readyState > 0) return;

        video.dataset.prefetched = '1';
        try {
            video.load();
        } catch (e) {
            delete video.dataset.prefetched;
        }
    }

    function applyWindow() {
        if (!items.length) return;

        var ahead = aheadCount();

        for (var i = 0; i < items.length; i++) {
            var video = videoOf(items[i]);
            if (!video) continue;

            var delta = i - activeIndex;
            var inWindow = delta >= -BEHIND && delta <= ahead;

            if (!inWindow) {
                // Only demote clips that have not started; dropping preload on
                // something already buffered gains nothing and can throw the buffer
                // away in some browsers.
                if (video.paused && video.currentTime === 0 && video.readyState === 0) {
                    setPreload(video, 'none');
                }
                continue;
            }

            setPreload(video, 'auto');

            if (delta > 0) {
                warm(video);
            } else if (delta === 0 && video.paused && video.readyState === 0) {
                // The clips now ship preload="none", so the browser fetches nothing
                // until asked. The page's own observer normally starts the active one,
                // but if it has not yet, kick it here -- otherwise the first reel can
                // sit on a black frame waiting for a play() that already happened.
                // Only while paused: load() on a playing element restarts it.
                warm(video);
            }
        }
    }

    function scheduleApply() {
        clearTimeout(applyTimer);
        // A fast flick can cross several items; only the one it settles on matters.
        applyTimer = setTimeout(applyWindow, 120);
    }

    function setActive(item) {
        var idx = items.indexOf(item);
        if (idx === -1) {
            refresh();
            idx = items.indexOf(item);
        }
        if (idx === -1 || idx === activeIndex) return;
        activeIndex = idx;
        scheduleApply();
    }

    function start() {
        refresh();
        if (!items.length) return;

        // Own observer rather than hooking the page's: all three reels views have
        // their own autoplay observer with different thresholds and callbacks, and
        // reaching into each of them is what left these files inconsistent before.
        var io = new IntersectionObserver(function (entries) {
            var best = null;
            entries.forEach(function (e) {
                if (e.isIntersecting && (!best || e.intersectionRatio > best.intersectionRatio)) {
                    best = e;
                }
            });
            if (best) setActive(best.target);
        }, { threshold: [0.6] });

        items.forEach(function (it) { io.observe(it); });

        // Infinite scroll appends more items; observe those too and re-run.
        if (window.MutationObserver) {
            new MutationObserver(function () {
                var before = items.length;
                refresh();
                if (items.length !== before) {
                    items.forEach(function (it) {
                        if (!it.dataset.prefetchObserved) {
                            it.dataset.prefetchObserved = '1';
                            io.observe(it);
                        }
                    });
                    scheduleApply();
                }
            }).observe(document.body, { childList: true, subtree: true });
        }

        var c = conn();
        if (c && typeof c.addEventListener === 'function') {
            c.addEventListener('change', scheduleApply);
        }

        applyWindow();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
