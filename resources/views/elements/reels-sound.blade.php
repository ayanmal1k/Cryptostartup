{{--
    Sound for the reels feed.

    pages/home had no control at all -- its videos carry `muted` and line 1905 forced
    video.muted = true, so there was no path to audio on the home feed at any point.
    pages/feed and videos/reels each had their own button, and both tracked mute per
    video element, so unmuting one clip and scrolling to the next gave you silence
    again.

    This is one control with one piece of state, shared by all three.

    On the autoplay rule, which is what makes this awkward: browsers refuse to start
    a video with sound until the page has had a real user gesture. So a reel MUST
    start muted or it will not start at all. The button is therefore not decoration --
    it is the gesture. Once tapped, the preference is remembered, and on later visits
    the first interaction anywhere on the page restores it.
--}}
<style>
    .reels-sound {
        position: absolute;
        top: 74px;
        right: 12px;
        z-index: 17;
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 50%;
        background: rgba(12, 12, 18, .48);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        color: #fff;
        cursor: pointer;
        pointer-events: auto;
        -webkit-tap-highlight-color: transparent;
        transition: transform .14s ease, background-color .18s ease, opacity .2s ease;
    }

    .reels-sound:hover { background: rgba(12, 12, 18, .68); }
    .reels-sound:active { transform: scale(.9); }

    .reels-sound:focus-visible {
        outline: 2px solid rgba(255, 77, 157, .85);
        outline-offset: 2px;
    }

    .reels-sound svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
        filter: drop-shadow(0 1px 3px rgba(0, 0, 0, .55));
    }

    /* Muted is the state worth drawing attention to -- it is the one the viewer
       probably wants to change, and on a silent feed nothing else says why. */
    .reels-sound.is-muted {
        background: rgba(255, 77, 157, .22);
        border-color: rgba(255, 77, 157, .45);
    }

    /* A one-time nudge on the first reel, so the control is noticed at all. */
    .reels-sound__hint {
        position: absolute;
        top: 88px;
        right: 62px;
        z-index: 17;
        padding: 6px 11px;
        border-radius: 99px;
        background: rgba(12, 12, 18, .8);
        border: 1px solid rgba(255, 255, 255, .14);
        backdrop-filter: blur(6px);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity .3s ease;
    }

    .reels-sound__hint.is-visible { opacity: 1; }

    /* The comments sheet covers the feed, so the control goes with the rest of the
       overlay chrome. Declared here rather than per page: only feed had a rule for
       it, so on home and videos/reels it would have floated over the sheet. */
    body.comments-open .reels-sound {
        opacity: 0;
        pointer-events: none;
    }

    @media (max-width: 480px) {
        .reels-sound { top: 66px; right: 10px; width: 38px; height: 38px; }
        .reels-sound svg { width: 18px; height: 18px; }
        .reels-sound__hint { top: 78px; right: 56px; font-size: 11px; padding: 5px 9px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .reels-sound, .reels-sound__hint { transition: none; }
    }
</style>

<script>
(function () {
    'use strict';

    var KEY = 'reelsSoundOn';

    var MUTED_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 5V4L8 9H4zm11.5 3a4.5 4.5 0 0 0-2.3-3.9v2.1l2.2 2.2c.06-.13.1-.27.1-.4zM19 12c0 .94-.2 1.83-.55 2.63l1.47 1.47A8.9 8.9 0 0 0 21 12c0-4.28-3-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM3.3 2 2 3.3 6.7 8H4v8h4l5 5v-6.7l3.2 3.2c-.5.38-1.06.69-1.67.9v2.06a8.9 8.9 0 0 0 3.13-1.55l1.54 1.54 1.3-1.3L3.3 2zM13 4l-2.1 2.1L13 8.2V4z"/></svg>';
    var LOUD_SVG  = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 5V4L8 9H4zm11.5 3a4.5 4.5 0 0 0-2.5-4.03v8.05A4.5 4.5 0 0 0 15.5 12zM13 3.23v2.06A6.99 6.99 0 0 1 18 12a6.99 6.99 0 0 1-5 6.71v2.06A9 9 0 0 0 20 12a9 9 0 0 0-7-8.77z"/></svg>';

    /* Persisted across visits, so a viewer who turns sound on does not have to do it
       again for every clip, or every session. */
    function wanted() {
        try {
            return localStorage.getItem(KEY) === '1';
        } catch (e) {
            return false;   // private mode, blocked storage -- default to silent
        }
    }

    function remember(on) {
        try {
            localStorage.setItem(KEY, on ? '1' : '0');
        } catch (e) {
            // not fatal; the choice just will not survive the page
        }
    }

    // Whether audio is allowed yet. Starts false on every load: the browser will not
    // let a video begin with sound until this page has seen a real gesture.
    var unlocked = false;

    function videos() {
        return Array.prototype.slice.call(document.querySelectorAll('.video-item video'));
    }

    /* Writes only when the state actually changed.
       This is load-bearing, not an optimisation. paint() is reached from scan(),
       which is driven by a MutationObserver watching childList on the whole body --
       so an unconditional `btn.innerHTML = ...` mutated the DOM, woke the observer,
       called scan again, and painted again, forever. That loop pegged the CPU from
       first paint, which is what made the page look broken before the videos
       appeared and left the sound button unresponsive.
       The observer does not watch attributes, so the dataset guard below is safe to
       write every time; it is the innerHTML that must be conditional. */
    function paint() {
        var on = unlocked && wanted();
        var want = on ? 'on' : 'off';
        document.querySelectorAll('.reels-sound').forEach(function (btn) {
            if (btn.dataset.soundState === want) return;
            btn.dataset.soundState = want;
            btn.classList.toggle('is-muted', !on);
            btn.innerHTML = on ? LOUD_SVG : MUTED_SVG;
            btn.setAttribute('aria-label', on ? 'Mute' : 'Unmute');
            btn.setAttribute('aria-pressed', on ? 'false' : 'true');
        });
    }

    function apply() {
        var on = unlocked && wanted();
        videos().forEach(function (v) { v.muted = !on; });
        paint();
    }

    function setSound(on, gesture) {
        if (on && gesture) {
            unlocked = true;
        }
        remember(on);
        apply();

        if (on) {
            // Unmuting a paused video is pointless, and a play() that the policy
            // refuses must not leave the feed silently stopped.
            var active = videos().filter(function (v) { return !v.paused; });
            active.forEach(function (v) {
                var p = v.play();
                if (p && typeof p.catch === 'function') {
                    p.catch(function () {
                        v.muted = true;
                        unlocked = false;
                        remember(false);
                        paint();
                    });
                }
            });
        }
    }

    // Exposed so the existing double-tap-to-toggle handlers can drive the same state
    // instead of keeping their own.
    window.reelsToggleSound = function () {
        setSound(!(unlocked && wanted()), true);
    };

    function build(item) {
        if (item.querySelector('.reels-sound')) return;

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'reels-sound is-muted';
        btn.dataset.soundState = 'off';
        btn.innerHTML = MUTED_SVG;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.reelsToggleSound();
        });

        item.appendChild(btn);
    }

    function scan() {
        document.querySelectorAll('.video-item').forEach(build);
        // Keep every clip on the current setting, including ones added by scrolling.
        var on = unlocked && wanted();
        videos().forEach(function (v) { v.muted = !on; });
        paint();
    }

    function hintOnce() {
        var item = document.querySelector('.video-item');
        var btn = item && item.querySelector('.reels-sound');
        if (!btn) return;

        var hint = document.createElement('span');
        hint.className = 'reels-sound__hint';
        hint.textContent = 'Tap for sound';
        // Appended beside the button rather than inside it: the button's innerHTML is
        // rewritten every time the icon changes, which would wipe a child out.
        btn.insertAdjacentElement('afterend', hint);

        requestAnimationFrame(function () { hint.classList.add('is-visible'); });

        setTimeout(function () {
            hint.classList.remove('is-visible');
            setTimeout(function () {
                if (hint.parentNode) hint.parentNode.removeChild(hint);
            }, 400);
        }, 4000);
    }

    function start() {
        scan();
        hintOnce();

        if (window.MutationObserver) {
            new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
        }

        // If sound was on last time, restore it at the first gesture anywhere -- that
        // gesture is what the autoplay policy is waiting for.
        if (wanted()) {
            var restore = function () {
                document.removeEventListener('pointerdown', restore, true);
                document.removeEventListener('keydown', restore, true);
                setSound(true, true);
            };
            document.addEventListener('pointerdown', restore, true);
            document.addEventListener('keydown', restore, true);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
