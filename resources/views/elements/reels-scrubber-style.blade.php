{{--
    The reels seek bar -- styles only.

    Split out from elements/reels-scrubber so it can be pulled into <head>. The
    bar markup (.progress-bar) is emitted inside the feed near the top of the
    page, but this CSS used to arrive with the scripts at the very bottom -- about
    148KB later in the response. Until it landed the bar had no positioning and
    rendered as a plain block in normal flow, so the first paint of the page was
    visibly wrong and then snapped into place. Styles belong in the head; only the
    behaviour belongs at the end of the body.

    Include this from @section('styles'), and elements/reels-scrubber from the
    bottom of the content section.
--}}

<style>
    .reels-container {
        /* Height of the bottom nav, from the nav's own box: 48px min-height on the
           items plus 6px padding top and bottom, plus the safe-area inset it carries.
           Computed rather than guessed, because the old flat 56px was smaller than the
           real nav on a gesture-bar phone -- which put the seek bar *behind* the menu,
           invisible and untappable, unless the JS measurement landed first. The script
           still refines this; it is no longer required for the bar to be reachable. */
        /* Floating nav: ~60px bar + the 8px it floats above the edge + safe area.
           The script measures the real value; this only has to be close. */
        --reels-nav-h: calc(70px + env(safe-area-inset-bottom));
        --seek-rest: 3px;      /* a hairline at rest, so it never competes with the video */
        --seek-active: 8px;    /* ...thick enough to aim at while dragging */
        --seek-knob: 11px;
        --seek-knob-active: 18px;
        --seek-gap: 6px;       /* breathing room between the bar and the nav */
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
        /* The container centres its child vertically, which put the shortened feed
           half a nav-height too low: the bottom of every reel (caption included) ran
           behind the seek bar and the nav. Pin it to the top instead. */
        align-self: flex-start;
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
        bottom: calc(var(--reels-nav-h) + var(--seek-gap));
        left: 14px;
        right: 14px;
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
            width: min(calc(100% - 28px), calc(var(--reels-feed-h, 100vh) * 9 / 16 - 28px));
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
           and a black night shot: a translucent white body, a dark hairline inside it,
           and a soft drop shadow underneath. The shadow is what keeps it visible on a
           pale frame, where the translucent fill alone disappears. */
        background: rgba(255, 255, 255, 0.22);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.45);
        transition: height .2s cubic-bezier(.2,.8,.2,1),
                    background-color .2s ease;
    }

    .reels-container .progress-bar:hover .progress-bar__track,
    .reels-container .progress-bar.is-scrubbing .progress-bar__track {
        background: rgba(255, 255, 255, 0.34);
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
        background: rgba(255, 255, 255, 0.30);
        transition: width .25s ease;
    }

    .reels-container .progress {
        position: relative;
        height: 100%;
        border-radius: 99px;
        /* White while watching (it reads on any footage and stays out of the way),
           brand pink while the viewer is actually dragging it. */
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.35);
        width: 0%;
        transition: width 0.1s linear, background-color .2s ease;
    }

    .reels-container .progress-bar.is-scrubbing .progress,
    .reels-container .progress-bar:focus-visible .progress {
        background: linear-gradient(90deg, #ff2d78 0%, #ff4d9d 60%, #ff8a80 100%);
        box-shadow: 0 0 12px rgba(255, 45, 120, 0.55);
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
        box-shadow:
            0 0 0 1.5px rgba(0, 0, 0, .28),
            0 2px 7px rgba(0, 0, 0, .5);
        transform: translateY(-50%) scale(.8);
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
        box-shadow:
            0 0 0 1.5px rgba(0, 0, 0, .28),
            0 0 0 8px rgba(255, 45, 120, .26),
            0 3px 14px rgba(0, 0, 0, .55);
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
        bottom: 26px;
        transform: translateX(-50%) translateY(4px);
        padding: 7px 12px;
        border-radius: 12px;
        background: rgba(10, 10, 16, .88);
        border: 1px solid rgba(255, 255, 255, .16);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 8px 22px rgba(0, 0, 0, .45);
        color: #fff;
        font-size: 15px;
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
    @media (min-width: 769px) {
        .reels-container { --reels-nav-h: 76px; }
    }
    @media (max-width: 768px) {
        .reels-container {
            --seek-rest: 4px;
            --seek-active: 9px;
            --seek-knob: 12px;
            --seek-knob-active: 20px;
        }
        .reels-container .progress-bar::before { top: -28px; bottom: 0; }
    }

    /* Desktop: a hairline bar that grows on hover reads as more precise, and the
       cursor does not need the extra grab area. */
    @media (min-width: 1024px) {
        .reels-container {
            --seek-rest: 3px;
            --seek-active: 8px;
            --seek-knob: 11px;
            --seek-knob-active: 16px;
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
        /* The bar sits --seek-gap above the nav, inside the bottom of the item, so the
           caption starts above the bar plus its grab area. */
        bottom: calc(var(--seek-gap) + 24px);
    }

    /* With the comments sheet open the nav is hidden, so the feed can use the
       full height. */
    body.comments-open .reels-container { --reels-nav-h: 0px; }
</style>
