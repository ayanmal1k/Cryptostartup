{{--
    The reels seek bar -- behaviour only.

    The CSS for this lives in elements/reels-scrubber-style and is included from
    the page head; see the note there for why they are separate.
--}}

<script>
(function () {
    'use strict';

    /* ----------------------------------------------------------------------
       How much room the floating bottom nav takes up.

       Hardcoding this was the original mistake: .bottom-nav changes padding and
       offset at the 768px breakpoint and adds env(safe-area-inset-bottom) on
       notched phones, so any fixed number is wrong on some device. Measure it.
    ---------------------------------------------------------------------- */
    function measureNavSpace() {
        var container = document.querySelector('.reels-container');
        if (!container) return;

        var nav = document.querySelector('.bottom-nav');
        if (!nav) {
            // A reels feed with no nav (videos/reels) gets the whole viewport.
            container.style.setProperty('--reels-nav-h', '0px');
            measureFeed(container);
            return;
        }

        // Everything from the top of the nav to the bottom of the viewport: the bar's
        // own height plus the gap under it, since the nav floats above the screen edge.
        // It is position:fixed, so its rect is relative to the viewport and does not
        // drift with scrolling.
        var rect = nav.getBoundingClientRect();
        if (!rect.height) return;
        var viewport = window.innerHeight || document.documentElement.clientHeight;
        var h = Math.max(rect.height, viewport - rect.top);

        container.style.setProperty('--reels-nav-h', Math.round(h) + 'px');
        measureFeed(container);
    }

    /* The scroller's own height, published so the desktop column can be sized from
       it. Read after the nav height is applied, since that is what determines it. */
    function measureFeed(container) {
        var feed = (container || document).querySelector('.video-feed');
        if (!feed) return;
        var h = feed.clientHeight;
        if (h) {
            (container || document.querySelector('.reels-container'))
                .style.setProperty('--reels-feed-h', Math.round(h) + 'px');
        }
    }

    function formatTime(seconds) {
        if (!isFinite(seconds) || seconds < 0) seconds = 0;
        var m = Math.floor(seconds / 60);
        var s = Math.floor(seconds % 60);
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    /* Bring any bar up to the full structure, whichever markup the page shipped.
       home.blade.php renders the track/handle/time itself; feed.blade.php still
       ships `<div class="progress-bar"><div class="progress"></div></div>`. Both
       end up identical here, so neither Blade has to know about the other. */
    function ensureMarkup(bar) {
        var track = bar.querySelector('.progress-bar__track');
        var fill = bar.querySelector('.progress');

        if (!track) {
            track = document.createElement('div');
            track.className = 'progress-bar__track';
            if (fill) {
                bar.insertBefore(track, fill);
                track.appendChild(fill);
            } else {
                bar.appendChild(track);
            }
        }

        if (!fill) {
            fill = document.createElement('div');
            fill.className = 'progress';
            track.appendChild(fill);
        }

        // Buffered range sits behind the played fill, so insert it first.
        if (!bar.querySelector('.progress-bar__buffer')) {
            var buffer = document.createElement('div');
            buffer.className = 'progress-bar__buffer';
            track.insertBefore(buffer, track.firstChild);
        }

        if (!bar.querySelector('.progress-bar__handle')) {
            var handle = document.createElement('div');
            handle.className = 'progress-bar__handle';
            track.appendChild(handle);
        }

        if (!bar.querySelector('.progress-bar__time')) {
            var label = document.createElement('span');
            label.className = 'progress-bar__time';
            label.innerHTML = '<b>0:00</b>';
            bar.appendChild(label);
        }

        bar.setAttribute('role', 'slider');
        if (!bar.hasAttribute('tabindex')) bar.setAttribute('tabindex', '0');
        if (!bar.hasAttribute('aria-label')) bar.setAttribute('aria-label', 'Seek');
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');
        if (!bar.hasAttribute('aria-valuenow')) bar.setAttribute('aria-valuenow', '0');
    }

    function initSeekBar(bar) {
        if (!bar || bar.dataset.seekReady === '1') return;

        var item = bar.closest('.video-item');
        var video = item ? item.querySelector('video') : null;
        if (!video) return;

        bar.dataset.seekReady = '1';
        ensureMarkup(bar);

        var fill = bar.querySelector('.progress');
        var buffer = bar.querySelector('.progress-bar__buffer');
        var handle = bar.querySelector('.progress-bar__handle');
        var label = bar.querySelector('.progress-bar__time');
        var scrubbing = false;
        var wasPlaying = false;

        function ratioFromEvent(e) {
            // Measure against the track, not the bar: the bar's padding is grab area
            // and counting it would offset every seek by the padding width.
            var target = bar.querySelector('.progress-bar__track') || bar;
            var rect = target.getBoundingClientRect();
            var x = (e.clientX !== undefined ? e.clientX : 0) - rect.left;
            return Math.max(0, Math.min(1, rect.width ? x / rect.width : 0));
        }

        function paint(ratio, time) {
            var pct = ratio * 100;
            if (fill) fill.style.width = pct + '%';
            if (handle) handle.style.left = pct + '%';
            bar.setAttribute('aria-valuenow', Math.round(pct));
            if (label) {
                label.style.left = pct + '%';

                // Only rewrite the text while it is actually on screen. paint() runs on
                // every timeupdate -- roughly four times a second -- and each innerHTML
                // write wakes three body-wide MutationObservers that then re-query the
                // document for nothing. The label is invisible except while scrubbing,
                // so the rest of the time that is pure cost on a phone.
                if (scrubbing) {
                    // Position without the duration is not much use -- 0:12 reads very
                    // differently on a 15 second clip than on a three minute one.
                    label.innerHTML = '<b>' + formatTime(time) + '</b>' +
                        (isFinite(video.duration) && video.duration > 0
                            ? ' <span>/ ' + formatTime(video.duration) + '</span>'
                            : '');
                }
            }
        }

        // How much has downloaded. Without this a still-buffering video looks
        // exactly like a stalled one.
        function paintBuffer() {
            if (!buffer || !isFinite(video.duration) || video.duration <= 0) return;
            try {
                if (!video.buffered || !video.buffered.length) return;
                var end = video.buffered.end(video.buffered.length - 1);
                buffer.style.width = Math.min(100, end / video.duration * 100) + '%';
            } catch (e) {
                // buffered can throw on some browsers before metadata is ready
            }
        }

        video.addEventListener('progress', paintBuffer);
        video.addEventListener('loadedmetadata', paintBuffer);

        function seekTo(ratio) {
            if (!isFinite(video.duration) || video.duration <= 0) return;
            var time = ratio * video.duration;
            video.currentTime = time;
            paint(ratio, time);
        }

        bar.addEventListener('pointerdown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            scrubbing = true;
            wasPlaying = !video.paused;
            // Pause while dragging so the playhead does not fight the finger.
            if (wasPlaying) video.pause();
            bar.classList.add('is-scrubbing');
            if (bar.setPointerCapture) bar.setPointerCapture(e.pointerId);
            seekTo(ratioFromEvent(e));
        });

        bar.addEventListener('pointermove', function (e) {
            if (!scrubbing) return;
            e.preventDefault();
            e.stopPropagation();
            seekTo(ratioFromEvent(e));
        });

        function endScrub(e) {
            if (!scrubbing) return;
            if (e) e.stopPropagation();
            scrubbing = false;
            bar.classList.remove('is-scrubbing');
            if (wasPlaying) video.play().catch(function () {});
        }

        bar.addEventListener('pointerup', endScrub);
        bar.addEventListener('pointercancel', endScrub);

        // Swallow the click so it never reaches the video's play/pause toggle.
        bar.addEventListener('click', function (e) { e.stopPropagation(); });

        // The feed swipes vertically between videos; without this a horizontal drag
        // on the bar scrolls the feed instead of seeking.
        bar.addEventListener('touchmove', function (e) {
            if (scrubbing) e.preventDefault();
        }, { passive: false });

        bar.addEventListener('keydown', function (e) {
            if (!isFinite(video.duration) || video.duration <= 0) return;
            var delta = 0;
            if (e.key === 'ArrowRight') delta = 5;
            else if (e.key === 'ArrowLeft') delta = -5;
            else return;
            e.preventDefault();
            e.stopPropagation();
            var t = Math.max(0, Math.min(video.duration, video.currentTime + delta));
            video.currentTime = t;
            paint(t / video.duration, t);
        });

        // Keep the bar in sync when the video advances or is seeked elsewhere.
        video.addEventListener('timeupdate', function () {
            if (scrubbing || !isFinite(video.duration) || video.duration <= 0) return;
            paint(video.currentTime / video.duration, video.currentTime);
            paintBuffer();
        });

        // Metadata may already be loaded by the time this runs (the observer wires up
        // bars that were appended after the video started), so do one pass now.
        paintBuffer();
        if (isFinite(video.duration) && video.duration > 0) {
            paint(video.currentTime / video.duration, video.currentTime);
        }
    }

    function scan() {
        document.querySelectorAll('.progress-bar:not([data-seek-ready])').forEach(initSeekBar);
    }

    /* Mark which reel is on screen.

       The bar is one fixed element per item now, all stacked at the same place, so
       exactly one must be visible. Nothing in the page marked an active item, so this
       observes them directly rather than reaching into the three different autoplay
       observers the reels views each have. */
    function trackActive() {
        var items = Array.prototype.slice.call(document.querySelectorAll('.video-item'));
        if (!items.length) return;

        function setActive(el) {
            items.forEach(function (i) { i.classList.toggle('is-active', i === el); });
        }

        // Something must be active before the observer first fires, or the opening
        // reel has no bar at all.
        setActive(items[0]);

        if (!window.IntersectionObserver) {
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            var best = null;
            entries.forEach(function (e) {
                if (e.isIntersecting && (!best || e.intersectionRatio > best.intersectionRatio)) {
                    best = e;
                }
            });
            if (best) setActive(best.target);
        }, { threshold: [0.6] });

        items.forEach(function (i) { io.observe(i); });

        // Infinite scroll appends more; observe and re-evaluate those too.
        if (window.MutationObserver) {
            new MutationObserver(function () {
                document.querySelectorAll('.video-item:not([data-active-watched])')
                    .forEach(function (i) {
                        i.dataset.activeWatched = '1';
                        items.push(i);
                        io.observe(i);
                    });
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    function start() {
        measureNavSpace();
        scan();
        trackActive();

        // Feed items are appended by infinite scroll, so wire new bars up too.
        if (window.MutationObserver) {
            new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
        }

        window.addEventListener('resize', measureNavSpace);
        window.addEventListener('orientationchange', function () {
            setTimeout(measureNavSpace, 250);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
