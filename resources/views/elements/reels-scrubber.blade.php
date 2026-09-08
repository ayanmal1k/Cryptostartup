{{--
    The reels seek bar.

    Lives in one place because it previously did not: pages/home.blade.php had an
    interactive scrubber while pages/feed.blade.php still had the original read-only
    3px indicator, so seeking worked on one reels page and not the other. Both now
    include this.

    Two things it fixes:

      * The bar sat at bottom:0 of a 100dvh video item, and .bottom-nav is a fixed
        floating bar sitting on top of it at a higher z-index -- so the control was
        underneath the menu and could not be touched at all. It is now offset by the
        space the nav actually occupies, measured at runtime rather than hardcoded,
        because that height changes with the breakpoint and with the safe-area inset
        on notched phones.

      * The bar was pointer-events:none, so even where it was reachable it did
        nothing. It is a real control now.
--}}
<style>
    /* Fallback for the first paint, before the measurement below runs. Roughly the
       nav's height plus its bottom offset on a phone. */
    .reels-container { --reels-nav-space: 78px; }

    /* Scoped one level deeper than the page's own rules so include order cannot
       matter, and so this can never leak into a page without a reels container. */
    .reels-container .progress-bar {
        position: absolute;
        bottom: var(--reels-nav-space);
        left: 0;
        right: 0;
        height: 3px;
        /* Padding gives a ~19px touch target while the visible bar stays thin. */
        padding: 8px 0;
        margin-bottom: -8px;
        background: transparent;
        z-index: 16;
        pointer-events: auto;
        cursor: pointer;
        touch-action: none;
        -webkit-tap-highlight-color: transparent;
    }

    .reels-container .progress-bar__track {
        position: relative;
        height: 3px;
        border-radius: 2px;
        background: rgba(255, 255, 255, 0.3);
        transition: height 0.15s ease;
    }

    .reels-container .progress-bar:hover .progress-bar__track,
    .reels-container .progress-bar.is-scrubbing .progress-bar__track {
        height: 6px;
    }

    .reels-container .progress {
        height: 100%;
        border-radius: 2px;
        background: linear-gradient(90deg, #cb0c9f, #ff8a80);
        width: 0%;
        transition: width 0.1s linear;
    }

    /* No transition while dragging, or the fill lags behind the finger. */
    .reels-container .progress-bar.is-scrubbing .progress {
        transition: none;
    }

    .reels-container .progress-bar__handle {
        position: absolute;
        top: 50%;
        left: 0;
        width: 13px;
        height: 13px;
        margin-left: -6.5px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .45);
        transform: translateY(-50%) scale(0);
        transition: transform 0.15s ease;
        pointer-events: none;
    }

    .reels-container .progress-bar:hover .progress-bar__handle,
    .reels-container .progress-bar.is-scrubbing .progress-bar__handle {
        transform: translateY(-50%) scale(1);
    }

    /* Time readout, only while scrubbing. */
    .reels-container .progress-bar__time {
        position: absolute;
        bottom: 18px;
        transform: translateX(-50%);
        padding: 3px 8px;
        border-radius: 6px;
        background: rgba(0, 0, 0, .78);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.15s ease;
    }

    .reels-container .progress-bar.is-scrubbing .progress-bar__time {
        opacity: 1;
    }

    /* The caption gradient has to clear the raised bar, or it covers the control. */
    .reels-container .video-overlay {
        bottom: calc(var(--reels-nav-space) + 14px);
    }

    /* With the comments sheet open the nav is hidden, so the bar can drop back down. */
    body.comments-open .reels-container { --reels-nav-space: 10px; }
</style>

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
            // Pages with a reels feed but no floating nav (videos/reels) want the
            // bar at the bottom edge, where it belongs.
            container.style.setProperty('--reels-nav-space', '10px');
            return;
        }

        var rect = nav.getBoundingClientRect();
        if (!rect.height) return;

        // Distance from the viewport bottom to the top of the nav, plus a small gap
        // so the touch targets do not butt up against each other.
        var space = Math.max(0, window.innerHeight - rect.top) + 8;
        container.style.setProperty('--reels-nav-space', Math.round(space) + 'px');
    }

    function formatTime(seconds) {
        if (!isFinite(seconds) || seconds < 0) seconds = 0;
        var m = Math.floor(seconds / 60);
        var s = Math.floor(seconds % 60);
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    /* Upgrade the older read-only markup in place, so a page that still ships
       `<div class="progress-bar"><div class="progress"></div></div>` gets the
       track, handle and time readout without its Blade having to change. */
    function ensureMarkup(bar) {
        if (bar.querySelector('.progress-bar__track')) return;

        var fill = bar.querySelector('.progress');
        var track = document.createElement('div');
        track.className = 'progress-bar__track';

        if (fill) {
            bar.insertBefore(track, fill);
            track.appendChild(fill);
        } else {
            bar.appendChild(track);
            fill = document.createElement('div');
            fill.className = 'progress';
            track.appendChild(fill);
        }

        var handle = document.createElement('div');
        handle.className = 'progress-bar__handle';
        track.appendChild(handle);

        var label = document.createElement('span');
        label.className = 'progress-bar__time';
        label.textContent = '0:00';
        bar.appendChild(label);

        bar.setAttribute('role', 'slider');
        bar.setAttribute('tabindex', '0');
        bar.setAttribute('aria-label', 'Seek');
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');
        bar.setAttribute('aria-valuenow', '0');
    }

    function initSeekBar(bar) {
        if (!bar || bar.dataset.seekReady === '1') return;

        var item = bar.closest('.video-item');
        var video = item ? item.querySelector('video') : null;
        if (!video) return;

        bar.dataset.seekReady = '1';
        ensureMarkup(bar);

        var fill = bar.querySelector('.progress');
        var handle = bar.querySelector('.progress-bar__handle');
        var label = bar.querySelector('.progress-bar__time');
        var scrubbing = false;
        var wasPlaying = false;

        function ratioFromEvent(e) {
            var rect = bar.getBoundingClientRect();
            var x = (e.clientX !== undefined ? e.clientX : 0) - rect.left;
            return Math.max(0, Math.min(1, rect.width ? x / rect.width : 0));
        }

        function paint(ratio, time) {
            var pct = ratio * 100;
            if (fill) fill.style.width = pct + '%';
            if (handle) handle.style.left = pct + '%';
            bar.setAttribute('aria-valuenow', Math.round(pct));
            if (label) {
                label.textContent = formatTime(time);
                label.style.left = pct + '%';
            }
        }

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
        });
    }

    function scan() {
        document.querySelectorAll('.progress-bar:not([data-seek-ready])').forEach(initSeekBar);
    }

    function start() {
        measureNavSpace();
        scan();

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
