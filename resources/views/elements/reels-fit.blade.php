{{--
    Makes the reels feed fit whatever phone it is on.

    Two things did not adapt before:

    1. The right-hand action rail was sized in fixed pixels (44px buttons, 16-18px
       gaps, six items plus the avatar). On a tall phone that fits; on a short one
       (an iPhone SE, or any phone with the browser bars showing) the rail ran up
       into the header and collided with other controls. The rail now scales with the
       screen HEIGHT, and on very short screens drops the least-used button.

    2. Every clip was object-fit: cover. For a vertical clip that is right, but a
       landscape clip on a portrait phone was cropped to a sliver of its middle —
       most of the picture was simply off screen. A clip is now letterboxed (shown
       whole) when cover would cut away more than a quarter of it.

    Included by pages/home, pages/feed and videos/reels after the rest of the reels
    partials.
--}}
<style>
    .reels-container .video-actions-left {
        gap: clamp(6px, 1.8vh, 18px);
        bottom: clamp(60px, 11vh, 96px);
    }

    .reels-container .video-actions-left .action-btn {
        width: clamp(32px, 5.3vh, 44px);
        height: clamp(32px, 5.3vh, 44px);
        font-size: clamp(19px, 3.2vh, 28px);
    }

    .reels-container .video-actions-left .action-count {
        font-size: clamp(10px, 1.5vh, 12px);
    }

    /* Short screens: the "more" button is the one nobody misses. */
    @media (max-height: 640px) {
        .reels-container .video-actions-left .action-item:has(.more-btn) {
            display: none;
        }
    }

    /* Shown whole instead of cropped (see note above). */
    .reels-container .video-item video.is-letterboxed {
        object-fit: contain !important;
        background: #000;
    }
</style>
<script>
(function () {
    'use strict';

    // Letterbox when "cover" would hide more than this share of the picture.
    var MAX_CROP = 0.25;

    function fit(video) {
        var w = video.videoWidth;
        var h = video.videoHeight;
        var box = video.getBoundingClientRect();
        if (!w || !h || !box.width || !box.height) return;

        var videoRatio = w / h;
        var boxRatio = box.width / box.height;
        // Share of the picture cover cuts off along the overflowing axis.
        var crop = videoRatio > boxRatio
            ? 1 - boxRatio / videoRatio
            : 1 - videoRatio / boxRatio;
        var letterbox = crop > MAX_CROP;
        if (video.classList.contains('is-letterboxed') !== letterbox) {
            video.classList.toggle('is-letterboxed', letterbox);
        }
    }

    function wire(video) {
        if (video.dataset.fitWired === '1') return;
        video.dataset.fitWired = '1';
        video.addEventListener('loadedmetadata', function () { fit(video); });
        if (video.readyState >= 1) fit(video);
    }

    function scan() {
        document.querySelectorAll('.reels-container .video-item video:not([data-fit-wired])').forEach(wire);
    }

    function refitAll() {
        document.querySelectorAll('.reels-container .video-item video').forEach(fit);
        measureHeader();
    }

    /* The header's real height (it grows with the notch/safe area), so controls placed
       under it — the sound button — clear it on every phone. */
    function measureHeader() {
        var container = document.querySelector('.reels-container');
        var header = document.querySelector('.top-header');
        if (!container || !header) return;
        var bottom = Math.round(header.getBoundingClientRect().bottom);
        if (bottom > 0) container.style.setProperty('--reels-header-h', bottom + 'px');
    }

    function start() {
        measureHeader();
        scan();
        if (window.MutationObserver) {
            // childList only, and wire() writes an attribute, so this cannot loop.
            new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
        }
        var t;
        window.addEventListener('resize', function () {
            clearTimeout(t);
            t = setTimeout(refitAll, 150);
        });
        window.addEventListener('orientationchange', function () { setTimeout(refitAll, 250); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
