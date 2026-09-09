{{--
    Reels player HUD: play/pause indicator, buffering spinner, and connection-aware
    loading.

    Injected by script rather than added to each view's Blade, because the same reels
    markup exists in three files (pages/home, pages/feed, videos/reels) and adding it
    by hand to each is how they drifted apart in the first place.
--}}
<style>
    .reels-hud {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        /* Taps must reach the video underneath -- this is an indicator, not a button. */
        pointer-events: none;
        z-index: 14;
    }

    /* --- play / pause indicator ------------------------------------------- */

    .reels-hud__icon {
        width: 74px;
        height: 74px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: rgba(12, 12, 18, .5);
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, .16);
        box-shadow: 0 6px 26px rgba(0, 0, 0, .45);
        opacity: 0;
        transform: scale(.7);
        transition: opacity .18s ease, transform .22s cubic-bezier(.2,1.3,.4,1);
    }

    .reels-hud__icon.is-visible {
        opacity: 1;
        transform: scale(1);
    }

    /* The flash on resume scales up as it fades, so it reads as "acknowledged" rather
       than as a control that is still there to press. */
    .reels-hud__icon.is-flash {
        opacity: 0;
        transform: scale(1.25);
        transition: opacity .45s ease, transform .45s ease;
    }

    .reels-hud__icon svg {
        width: 32px;
        height: 32px;
        fill: #fff;
        /* Optical centring: a play triangle looks left-heavy in a circle. */
        margin-left: 0;
    }

    .reels-hud__icon[data-state="play"] svg { margin-left: 4px; }

    /* --- buffering spinner ------------------------------------------------- */

    .reels-hud__spinner {
        position: absolute;
        width: 46px;
        height: 46px;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, .22);
        border-top-color: #ff4d9d;
        opacity: 0;
        transition: opacity .18s ease;
        animation: reels-spin .8s linear infinite;
    }

    .reels-hud__spinner.is-visible { opacity: 1; }

    @keyframes reels-spin { to { transform: rotate(360deg); } }

    /* --- slow connection notice -------------------------------------------- */

    .reels-hud__saver {
        position: absolute;
        top: 14px;
        left: 50%;
        transform: translateX(-50%);
        padding: 5px 11px;
        border-radius: 99px;
        background: rgba(12, 12, 18, .72);
        border: 1px solid rgba(255, 255, 255, .14);
        backdrop-filter: blur(6px);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .02em;
        opacity: 0;
        transition: opacity .25s ease;
        white-space: nowrap;
    }

    .reels-hud__saver.is-visible { opacity: 1; }

    @media (max-width: 768px) {
        .reels-hud__icon { width: 64px; height: 64px; }
        .reels-hud__icon svg { width: 27px; height: 27px; }
        .reels-hud__spinner { width: 40px; height: 40px; border-width: 3px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .reels-hud__spinner { animation-duration: 2s; }
        .reels-hud__icon { transition: opacity .15s ease; }
    }
</style>

<script>
(function () {
    'use strict';

    var PLAY_SVG  = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
    var PAUSE_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>';

    /* ----------------------------------------------------------------------
       Connection awareness.

       navigator.connection is Chromium-only, so this is a progressive
       enhancement: where it is missing everything behaves exactly as before.
    ---------------------------------------------------------------------- */
    function conn() {
        return navigator.connection || navigator.mozConnection || navigator.webkitConnection || null;
    }

    function isSlow() {
        var c = conn();
        if (!c) return false;
        if (c.saveData) return true;                       // user asked for less data
        var t = c.effectiveType || '';
        return t === 'slow-2g' || t === '2g' || t === '3g';
    }

    /**
     * Apply the current connection quality to one video.
     *
     * On a slow link the win available here is loading *less*, not loading smaller:
     * there is one encode per video, so there is no lower-quality rendition to switch
     * to. `data-src-low` is honoured if a page ever provides one -- that is the hook
     * for real adaptive quality once the videos are transcoded into renditions.
     */
    function applyQuality(video) {
        var slow = isSlow();

        // Do not fight a video that is already playing; changing preload or src
        // mid-playback would stall it.
        if (!video.paused || video.currentTime > 0) return;

        var low = video.getAttribute('data-src-low');
        if (slow && low && video.getAttribute('src') !== low) {
            video.setAttribute('data-src-high', video.getAttribute('src'));
            video.setAttribute('src', low);
            video.load();
        } else if (!slow && video.hasAttribute('data-src-high')) {
            video.setAttribute('src', video.getAttribute('data-src-high'));
            video.removeAttribute('data-src-high');
            video.load();
        }

        // Only fetch bytes for a clip once it is actually wanted.
        video.preload = slow ? 'none' : 'metadata';
    }

    function buildHud(item, video) {
        if (item.querySelector('.reels-hud')) return item.querySelector('.reels-hud');

        var hud = document.createElement('div');
        hud.className = 'reels-hud';

        var spinner = document.createElement('div');
        spinner.className = 'reels-hud__spinner';

        var icon = document.createElement('div');
        icon.className = 'reels-hud__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = PAUSE_SVG;
        icon.dataset.state = 'pause';

        var saver = document.createElement('div');
        saver.className = 'reels-hud__saver';
        saver.textContent = 'Data saver — loading less';

        hud.appendChild(spinner);
        hud.appendChild(icon);
        hud.appendChild(saver);
        item.appendChild(hud);

        return hud;
    }

    function initHud(item) {
        if (!item || item.dataset.hudReady === '1') return;

        var video = item.querySelector('video');
        if (!video) return;

        item.dataset.hudReady = '1';

        var hud     = buildHud(item, video);
        var icon    = hud.querySelector('.reels-hud__icon');
        var spinner = hud.querySelector('.reels-hud__spinner');
        var saver   = hud.querySelector('.reels-hud__saver');
        var flashTimer = null;

        function showIcon(kind, transient) {
            clearTimeout(flashTimer);
            icon.innerHTML = kind === 'play' ? PLAY_SVG : PAUSE_SVG;
            icon.dataset.state = kind;
            icon.classList.remove('is-flash');
            // Force a reflow so removing and re-adding the class actually restarts
            // the transition rather than being collapsed into one style recalc.
            void icon.offsetWidth;
            icon.classList.add('is-visible');

            if (transient) {
                flashTimer = setTimeout(function () {
                    icon.classList.remove('is-visible');
                    icon.classList.add('is-flash');
                }, 1000);
            }
        }

        function hideIcon() {
            clearTimeout(flashTimer);
            icon.classList.remove('is-visible', 'is-flash');
        }

        // Paused: the pause icon stays up, so it is obvious the video is stopped
        // rather than broken.
        video.addEventListener('pause', function () {
            if (video.ended) return;
            showIcon('pause', false);
        });

        // Playing: flash the play icon for a second, then fade it away.
        video.addEventListener('play', function () {
            showIcon('play', true);
        });

        video.addEventListener('ended', hideIcon);

        /* --- buffering ---------------------------------------------------- */

        function showSpinner() { spinner.classList.add('is-visible'); }
        function hideSpinner() { spinner.classList.remove('is-visible'); }

        video.addEventListener('waiting', showSpinner);
        video.addEventListener('seeking', function () {
            // Only if the data is not already there -- otherwise every scrub flickers
            // a spinner for a frame.
            if (video.readyState < 3) showSpinner();
        });
        video.addEventListener('stalled', showSpinner);

        ['playing', 'canplay', 'canplaythrough', 'seeked', 'error', 'suspend']
            .forEach(function (e) { video.addEventListener(e, hideSpinner); });

        // A video that is asked to play but has nothing buffered yet should show the
        // spinner immediately rather than sitting on a black frame.
        video.addEventListener('loadstart', function () {
            if (video.readyState < 2) showSpinner();
        });

        /* --- connection --------------------------------------------------- */

        applyQuality(video);

        if (isSlow()) {
            saver.classList.add('is-visible');
            setTimeout(function () { saver.classList.remove('is-visible'); }, 3500);
        }

        var c = conn();
        if (c && typeof c.addEventListener === 'function') {
            c.addEventListener('change', function () { applyQuality(video); });
        }
    }

    function scan() {
        document.querySelectorAll('.video-item:not([data-hud-ready])').forEach(initHud);
    }

    function start() {
        scan();
        if (window.MutationObserver) {
            new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
