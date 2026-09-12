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
    .reels-container {
        /* Height of the bottom nav, from the nav's own box: 48px min-height on the
           items plus 6px padding top and bottom, plus the safe-area inset it carries.
           Computed rather than guessed, because the old flat 56px was smaller than the
           real nav on a gesture-bar phone -- which put the seek bar *behind* the menu,
           invisible and untappable, unless the JS measurement landed first. The script
           still refines this; it is no longer required for the bar to be reachable. */
        --reels-nav-h: calc(60px + env(safe-area-inset-bottom));
        --seek-rest: 3px;      /* visible track height at rest  */
        --seek-active: 7px;    /* ...and while hovered/dragging */
        --seek-knob: 12px;     /* knob at rest                  */
        --seek-knob-active: 17px;
    }

    /* The feed is the viewport minus the nav. This is the whole fix for both the
       gap under the bar and the next reel peeking through: previously the item was
       the full viewport height with the nav floating over its bottom, so the video
       continued behind the nav and the seam between items landed in view, and the
       bar had to be pushed up by a measured offset that could only ever be
       approximately right. Now the scroller ends at the nav, one item fills it
       exactly, and the bar sits at the item's own bottom edge -- no arithmetic. */
    .reels-container .video-feed {
        height: calc(100% - var(--reels-nav-h));
    }

    .reels-container .video-item {
        height: 100%;
    }

    /* Desktop: a centred 9:16 column, the shape a short actually is.

       Derived from the feed's height rather than set to a fixed width, so it stays
       9:16 at any window size -- a fixed width only matches one window height and is
       wrong everywhere else. The item is what gets sized, not the wrapper, so the
       action rail, caption and seek bar sit against the column's edges instead of
       the screen's. The scroller stays full width so the wheel still works anywhere
       on the page. */
    @media (min-width: 769px) {
        .reels-container .video-item {
            /* Width from the measured feed height, not aspect-ratio: the item is a
               block-level box with width:auto, which stretches to fill its parent, and
               aspect-ratio does not override that. --reels-feed-h is set from the
               scroller's real height, so this is a true 9:16 at any window size. */
            width: min(100%, calc(var(--reels-feed-h, 100vh) * 9 / 16));
            margin-left: auto;
            margin-right: auto;
        }

        /* Radius on the wrapper, not the item: the item must not clip, or it would
           cut the seek knob in half at the bottom edge. */
        .reels-container .video-wrapper {
            border-radius: 14px;
            overflow: hidden;
        }
    }

    /* Scoped one level deeper than the page's own rules so include order cannot
       matter, and so this can never leak into a page without a reels container. */
    /* Fixed to the viewport, not absolute inside the video.

       The bar lives inside .video-wrapper, which is overflow:hidden -- so anything
       that overhangs the track, the drag knob in particular, was being clipped away.
       Positioning against the viewport takes it out of that box entirely and pins it
       directly above the nav, with no dependency on the feed's height arithmetic
       being right. Only the active reel's bar is shown; the rest stay hidden so the
       eleven of them do not stack on the same spot. */
    .reels-container .progress-bar {
        position: fixed;
        bottom: var(--reels-nav-h);
        left: 5px;
        right: 5px;
        display: none;
        /* Above the nav's 1001 so it is never the thing that loses. */
        z-index: 1002;
        /* Padding on the top edge only, and no negative margin: the box's bottom edge
           is then the track's bottom edge, so setting `bottom` to the nav height puts
           the bar flush against it with no arithmetic. The grab area is the ::before
           below, which is what the padding used to be for. */
        padding: 4px 0 0;
        margin-bottom: 0;
        background: transparent;
        pointer-events: auto;
        cursor: pointer;
        touch-action: none;
        -webkit-tap-highlight-color: transparent;
    }

    .reels-container .video-item.is-active .progress-bar {
        display: block;
    }

    /* If the script never marks anything active -- it failed, or has not run yet --
       fall back to the first reel's bar rather than showing none at all. Drops out
       the moment a real active item exists, so the two cannot both be on screen. */
    .reels-container:not(:has(.video-item.is-active)) .video-item:first-child .progress-bar {
        display: block;
    }

    /* Declared after the base rule on purpose: both are (0,2,0) and a media query
       adds no specificity, so source order is what decides. Sitting above, this was
       being overridden by the left/right insets below it. */
    @media (min-width: 769px) {
        .reels-container .progress-bar {
            left: 50%;
            right: auto;
            transform: translateX(-50%);
            width: min(calc(100% - 10px), calc(var(--reels-feed-h, 100vh) * 9 / 16));
        }
    }

    /* The visible bar is 7px tall. A thumb is about 10mm, so that is not a grabbable
       target -- this extends the hit area without changing how the bar looks.
       Upward only: below the bar is the nav, which sits at a higher z-index and would
       take those taps anyway, so reaching down there would buy nothing. */
    .reels-container .progress-bar::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: -24px;
        bottom: 0;
    }

    .reels-container .progress-bar__track {
        position: relative;
        height: var(--seek-rest);
        border-radius: 99px;
        /* Video is arbitrary content, so the track has to read on both a white beach
           and a black night shot: translucent white over a dark inner shadow holds
           contrast either way. */
        background: rgba(255, 255, 255, 0.28);
        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.18);
        transition: height .18s cubic-bezier(.2,.8,.2,1);
    }

    .reels-container .progress-bar:hover .progress-bar__track,
    .reels-container .progress-bar:focus-visible .progress-bar__track,
    .reels-container .progress-bar.is-scrubbing .progress-bar__track {
        height: var(--seek-active);
    }

    /* How much has downloaded, behind the played fill. Without it a video that is
       still buffering looks identical to one that has stalled. */
    .reels-container .progress-bar__buffer {
        position: absolute;
        inset: 0 auto 0 0;
        width: 0%;
        border-radius: 99px;
        background: rgba(255, 255, 255, 0.34);
        transition: width .25s ease;
    }

    .reels-container .progress {
        position: relative;
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, #ff4d9d 0%, #cb0c9f 55%, #ff8a80 100%);
        box-shadow: 0 0 10px rgba(255, 77, 157, 0.5);
        width: 0%;
        transition: width 0.1s linear;
    }

    /* No transition while dragging, or the fill lags behind the finger. */
    .reels-container .progress-bar.is-scrubbing .progress {
        transition: none;
    }

    /* The knob. Kept visible at rest, small and low-contrast: a bar with no handle
       reads as a progress indicator, and nobody tries to drag it. */
    .reels-container .progress-bar__handle {
        position: absolute;
        top: 50%;
        left: 0;
        width: var(--seek-knob);
        height: var(--seek-knob);
        margin-left: calc(var(--seek-knob) / -2);
        border-radius: 50%;
        background: #fff;
        /* Ring rather than a bare dot, so it survives a white frame behind it. */
        box-shadow: 0 0 0 2px rgba(0, 0, 0, .25), 0 2px 8px rgba(0, 0, 0, .5);
        transform: translateY(-50%) scale(.82);
        opacity: .9;
        transition: transform .18s cubic-bezier(.2,1.4,.4,1), opacity .18s ease,
                    width .18s ease, height .18s ease;
        pointer-events: none;
        z-index: 2;
    }

    .reels-container .progress-bar:hover .progress-bar__handle,
    .reels-container .progress-bar:focus-visible .progress-bar__handle {
        transform: translateY(-50%) scale(1);
        opacity: 1;
    }

    /* While dragging: bigger, fully opaque, with a halo so it stays findable under
       a fingertip that is covering it. */
    .reels-container .progress-bar.is-scrubbing .progress-bar__handle {
        width: var(--seek-knob-active);
        height: var(--seek-knob-active);
        margin-left: calc(var(--seek-knob-active) / -2);
        transform: translateY(-50%) scale(1);
        opacity: 1;
        box-shadow: 0 0 0 2px rgba(0, 0, 0, .25),
                    0 0 0 7px rgba(255, 77, 157, .28),
                    0 3px 12px rgba(0, 0, 0, .55);
    }

    /* Keyboard users get the same affordance as a pointer, and no default outline
       on a control that is a thin bar. */
    .reels-container .progress-bar:focus { outline: none; }
    .reels-container .progress-bar:focus-visible .progress-bar__track {
        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18), 0 0 0 2px rgba(255, 255, 255, .75);
    }

    /* Time readout while scrubbing. Shows position AND duration -- 0:12 reads very
       differently on a fifteen second clip than on a three minute one. */
    .reels-container .progress-bar__time {
        position: absolute;
        bottom: 22px;
        transform: translateX(-50%) translateY(4px);
        padding: 5px 10px;
        border-radius: 8px;
        background: rgba(10, 10, 16, .86);
        border: 1px solid rgba(255, 255, 255, .14);
        backdrop-filter: blur(8px);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
    }

    .reels-container .progress-bar__time b { font-weight: 700; }
    .reels-container .progress-bar__time span { opacity: .6; font-weight: 500; }

    .reels-container .progress-bar.is-scrubbing .progress-bar__time {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* --- responsive ------------------------------------------------------- */

    /* Phones: a slightly chunkier track and knob, since the pointer is a fingertip
       rather than a 1px cursor. */
    /* The nav's box changes at these widths, so the reserved height has to follow.
       Kept in step with elements/reels-bottom-nav: item min-height plus the bar's
       vertical padding, plus the safe-area inset it carries on a phone. */
    @media (max-width: 480px) {
        .reels-container { --reels-nav-h: calc(58px + env(safe-area-inset-bottom)); }
    }

    @media (max-width: 340px) {
        .reels-container { --reels-nav-h: calc(54px + env(safe-area-inset-bottom)); }
    }

    @media (min-width: 769px) {
        .reels-container { --reels-nav-h: 64px; }
    }

    @media (max-width: 768px) {
        .reels-container {
            --seek-rest: 4px;
            --seek-active: 8px;
            --seek-knob: 13px;
            --seek-knob-active: 19px;
        }
        .reels-container .progress-bar::before { top: -28px; bottom: 0; }
        .reels-container .progress-bar__time { font-size: 11px; padding: 4px 9px; }
    }

    /* Desktop: a hairline bar that grows on hover reads as more precise, and the
       cursor does not need the extra grab area. */
    @media (min-width: 1024px) {
        .reels-container {
            --seek-rest: 3px;
            --seek-active: 7px;
            --seek-knob: 11px;
            --seek-knob-active: 15px;
        }
        .reels-container .progress-bar::before { top: -18px; bottom: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .reels-container .progress-bar__track,
        .reels-container .progress-bar__handle,
        .reels-container .progress-bar__time,
        .reels-container .progress-bar__buffer,
        .reels-container .progress {
            transition: none;
        }
    }

    /* The caption gradient has to clear the raised bar, or it covers the control. */
    .reels-container .video-overlay {
        bottom: 14px;
    }

    /* With the comments sheet open the nav is hidden, so the feed can use the
       full height. */
    body.comments-open .reels-container { --reels-nav-h: 0px; }
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
            // A reels feed with no nav (videos/reels) gets the whole viewport.
            container.style.setProperty('--reels-nav-h', '0px');
            measureFeed(container);
            return;
        }

        // The nav's own height, including the safe-area padding it carries. Measuring
        // the element rather than a distance from the viewport bottom means the value
        // cannot drift with scroll position or a collapsing URL bar.
        var h = nav.getBoundingClientRect().height;
        if (!h) return;

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
