{{--
    The comments and profile bottom sheets.

    One copy for both reels pages, which each carried their own near-identical CSS
    for these (68 rules in feed, 30 in home).

    Everything here is written against theme tokens rather than fixed colours. Until
    now nothing in the DOM said which theme was active -- the theme was expressed
    only by which bootstrap build got loaded -- so every bespoke component had no way
    to adapt and was simply hardcoded dark. The layouts now carry data-theme on
    <html>, and these sheets read it.

    The slide-up transition is kept deliberately; it was the part worth keeping.
--}}
<style>
    /* ------------------------------------------------------------------ tokens */

    :root {
        --sheet-surface:      #ffffff;
        --sheet-raised:       #f4f5f9;
        --sheet-sunken:       #eceef4;
        --sheet-border:       rgba(17, 20, 34, .10);
        --sheet-border-soft:  rgba(17, 20, 34, .06);
        --sheet-text:         #10131f;
        --sheet-text-dim:     #5b6176;
        --sheet-text-faint:   #868da3;
        --sheet-scrim:        rgba(14, 16, 26, .42);
        --sheet-shadow:       0 -18px 48px rgba(16, 19, 31, .18);
        --sheet-accent:       #e0157f;
        --sheet-accent-soft:  rgba(224, 21, 127, .10);
        --sheet-on-accent:    #ffffff;
        --sheet-like:         #e0245e;
    }

    [data-theme="dark"] {
        --sheet-surface:      #16181f;
        --sheet-raised:       #1e212b;
        --sheet-sunken:       #242834;
        --sheet-border:       rgba(255, 255, 255, .10);
        --sheet-border-soft:  rgba(255, 255, 255, .06);
        --sheet-text:         #f2f4fa;
        --sheet-text-dim:     #a2a9bd;
        --sheet-text-faint:   #767d92;
        --sheet-scrim:        rgba(0, 0, 0, .58);
        --sheet-shadow:       0 -18px 48px rgba(0, 0, 0, .55);
        --sheet-accent:       #ff4d9d;
        --sheet-accent-soft:  rgba(255, 77, 157, .14);
        --sheet-on-accent:    #ffffff;
        --sheet-like:         #ff3b6b;
    }

    /* ------------------------------------------------------- shared sheet shell */

    .comments-overlay,
    .profile-modal {
        position: fixed;
        inset: 0;
        z-index: 1500;
        background: var(--sheet-scrim);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        opacity: 0;
        visibility: hidden;
        transition: opacity .28s ease, visibility .28s ease;
    }

    .comments-overlay.active,
    .profile-modal.active {
        opacity: 1;
        visibility: visible;
    }

    .comments-sidebar,
    .profile-modal-content {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1501;
        display: flex;
        flex-direction: column;
        background: var(--sheet-surface);
        color: var(--sheet-text);
        border-radius: 22px 22px 0 0;
        box-shadow: var(--sheet-shadow);
        transform: translateY(100%);
        /* The slide is the part that makes this feel native, so it gets a real
           easing curve rather than a linear ramp. */
        transition: transform .34s cubic-bezier(.22, 1, .30, 1);
        will-change: transform;
        overflow: hidden;
    }

    .comments-sidebar { height: 72vh; max-height: 86vh; }

    .comments-sidebar.active,
    .profile-modal.active .profile-modal-content { transform: translateY(0); }

    /* Grab handle. Sized as a real target, not a decorative line -- it is what the
       drag-to-dismiss gesture is aimed at. */
    .comments-grabber,
    .profile-grabber {
        flex: 0 0 auto;
        padding: 10px 0 6px;
        display: flex;
        justify-content: center;
        cursor: grab;
        touch-action: none;
    }

    .comments-grabber::before,
    .profile-grabber::before {
        content: '';
        width: 40px;
        height: 4px;
        border-radius: 99px;
        background: var(--sheet-text-faint);
        opacity: .5;
    }

    .comments-grabber:active { cursor: grabbing; }

    /* ------------------------------------------------------------- sheet header */

    .comments-header,
    .profile-modal-header {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 4px 16px 12px;
        border-bottom: 1px solid var(--sheet-border-soft);
    }

    .comments-header h3,
    .profile-modal-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: -.01em;
        color: var(--sheet-text);
    }

    /* Thread size, next to the title. Without it the only way to know whether a
       thread is 3 comments or 300 is to scroll to the end of it. */
    .comments-count {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 8px;
        border-radius: 99px;
        background: var(--sheet-accent-soft);
        color: var(--sheet-accent);
        font-size: 12px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        vertical-align: 1px;
    }

    .close-comments,
    .profile-modal-close {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        flex: 0 0 auto;
        border: 0;
        border-radius: 50%;
        background: var(--sheet-raised);
        color: var(--sheet-text-dim);
        cursor: pointer;
        transition: background-color .16s ease, color .16s ease, transform .12s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .close-comments:hover,
    .profile-modal-close:hover { background: var(--sheet-sunken); color: var(--sheet-text); }
    .close-comments:active,
    .profile-modal-close:active { transform: scale(.92); }

    /* These icons are <line>/<polyline>, which paint nothing without a stroke. The
       markup sets one, but nothing here did -- so a page that shipped a bare <svg>
       rendered an empty circle, which is exactly how the close button looked. */
    .close-comments svg,
    .profile-modal-close svg,
    #post-comment svg {
        width: 20px;
        height: 20px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2.2;
        stroke-linecap: round;
        stroke-linejoin: round;
        pointer-events: none;
    }

    .close-comments svg { width: 19px; height: 19px; }

    /* -------------------------------------------------------------- comment list */

    .comments-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding: 8px 14px 12px;
    }

    .comment-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 8px;
        margin: 0 -6px;
        border-radius: 14px;
        animation: sheet-comment-in .3s ease both;
        transition: background-color .16s ease;
    }

    /* Gives each row an edge to sit against on hover, so a long thread reads as a
       list of separate comments rather than one block of running text. */
    .comment-item:hover { background: var(--sheet-raised); }

    @keyframes sheet-comment-in {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: none; }
    }

    .comment-item.is-reply { padding-left: 44px; }

    .comment-avatar-initials {
        flex: 0 0 auto;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        /* The inline background comes from JS; this keeps it legible on either theme. */
        text-shadow: 0 1px 2px rgba(0, 0, 0, .25);
    }

    .comment-content { flex: 1 1 auto; min-width: 0; }

    .comment-head {
        display: flex;
        align-items: baseline;
        gap: 8px;
        flex-wrap: wrap;
    }

    .comment-content strong {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--sheet-text);
    }

    .comment-time {
        font-size: 11.5px;
        color: var(--sheet-text-faint);
        font-variant-numeric: tabular-nums;
    }

    .comment-content p {
        margin: 3px 0 0;
        font-size: 14px;
        line-height: 1.45;
        color: var(--sheet-text);
        /* Comments are user text: a long unbroken string must not widen the sheet. */
        overflow-wrap: anywhere;
    }

    .comment-actions {
        display: flex;
        gap: 14px;
        margin-top: 5px;
    }

    .comment-reply {
        /* A <button> now rather than a <span>, so it is focusable and announced --
           but it has to lose the UA chrome to look the same. */
        appearance: none;
        -webkit-appearance: none;
        border: 0;
        background: none;
        padding: 0;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        color: var(--sheet-text-dim);
        cursor: pointer;
        transition: color .15s ease;
    }

    .comment-reply:hover { color: var(--sheet-accent); }

    .comment-like {
        appearance: none;
        -webkit-appearance: none;
        border: 0;
        background: none;
        padding: 0;
        font-family: inherit;
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding-top: 4px;
        color: var(--sheet-text-faint);
        cursor: pointer;
        transition: color .15s ease, transform .12s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .comment-like svg { width: 17px; height: 17px; }
    .comment-like span { font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .comment-like:active { transform: scale(.88); }

    .comment-like.liked,
    .comment-like.active {
        color: var(--sheet-like);
    }

    .comment-like.liked svg,
    .comment-like.active svg { fill: currentColor; }

    /* A like is the smallest possible action in the sheet, so it gets the only
       piece of motion -- otherwise tapping a 17px heart gives no feedback that
       anything registered. */
    .comment-like.is-bursting svg { animation: sheet-like-pop .32s cubic-bezier(.2, 1.5, .4, 1); }

    @keyframes sheet-like-pop {
        0%   { transform: scale(1); }
        40%  { transform: scale(1.35); }
        100% { transform: scale(1); }
    }

    .comment-like:focus-visible,
    .comment-reply:focus-visible,
    .reaction-emoji:focus-visible,
    .close-comments:focus-visible,
    #post-comment:focus-visible {
        outline: 2px solid var(--sheet-accent);
        outline-offset: 2px;
        border-radius: 8px;
    }

    /* Empty state. The old one was a bare sentence floating at the top of a large
       blank sheet, which reads as a page that failed to load. */
    .no-comments {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 190px;
        text-align: center;
        color: var(--sheet-text-dim);
    }

    .no-comments::before {
        content: '💬';
        font-size: 30px;
        opacity: .55;
    }

    .no-comments__hint { color: var(--sheet-text-faint); font-size: 12.5px !important; }

    .no-comments p {
        margin: 0;
        font-size: 13.5px;
        max-width: 22ch;
        line-height: 1.45;
    }

    /* ---------------------------------------------------------- emoji quick row */

    .comment-reactions {
        flex: 0 0 auto;
        display: flex;
        gap: 4px;
        padding: 8px 12px;
        border-top: 1px solid var(--sheet-border-soft);
        overflow-x: auto;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }

    .comment-reactions::-webkit-scrollbar { display: none; }

    .reaction-emoji {
        flex: 0 0 auto;
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        font-size: 21px;
        line-height: 1;
        border: 0;
        border-radius: 50%;
        background: transparent;
        cursor: pointer;
        transition: transform .16s cubic-bezier(.2, 1.4, .4, 1), background-color .16s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .reaction-emoji:hover { background: var(--sheet-raised); transform: scale(1.18); }
    .reaction-emoji:active { transform: scale(.9); }

    /* Confirms the tap landed. The emoji goes into the composer, which is below the
       row and easy to miss on a phone. */
    .reaction-emoji.is-popped { animation: sheet-emoji-pop .34s cubic-bezier(.2, 1.5, .4, 1); }

    @keyframes sheet-emoji-pop {
        0%   { transform: scale(1); }
        45%  { transform: scale(1.42); }
        100% { transform: scale(1); }
    }

    /* ---------------------------------------------------------------- composer */

    .comment-form {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
        border-top: 1px solid var(--sheet-border);
        background: var(--sheet-surface);
    }

    .comment-form__avatar {
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 12px;
        font-weight: 700;
        color: var(--sheet-on-accent);
        background: var(--sheet-accent);
    }

    /* Every one of these carries !important on purpose.
       public/css/app.css has a blanket rule -- .form-control, input[type="text"],
       select, ... -- that forces background #ffffff, colour #212529, a 1.5px grey
       border and an 8px radius, all !important. It is written for a light page and
       takes no notice of the theme, so without matching it here the composer is a
       white box with dark text sitting in a dark sheet, and the pill shape is gone.
       An ID beats that rule on specificity, but only once the flag matches. */
    .comment-form #comment-input,
    #comment-input {
        flex: 1 1 auto;
        min-width: 0;
        height: 40px;
        padding: 0 15px !important;
        border: 1px solid var(--sheet-border) !important;
        border-radius: 99px !important;
        background-color: var(--sheet-raised) !important;
        color: var(--sheet-text) !important;
        font-size: 14px !important;
        box-shadow: none !important;
        outline: none;
        transition: border-color .16s ease, background-color .16s ease;
    }

    #comment-input::placeholder { color: var(--sheet-text-faint) !important; opacity: 1; }

    #comment-input:focus {
        border-color: var(--sheet-accent) !important;
        background-color: var(--sheet-surface) !important;
        color: var(--sheet-text) !important;
        box-shadow: 0 0 0 3px var(--sheet-accent-soft) !important;
    }

    #post-comment {
        flex: 0 0 auto;
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        border: 0;
        border-radius: 50%;
        background: var(--sheet-accent);
        color: var(--sheet-on-accent);
        cursor: pointer;
        transition: transform .12s ease, opacity .16s ease;
        -webkit-tap-highlight-color: transparent;
    }

    #post-comment:active { transform: scale(.9); }
    #post-comment:disabled { opacity: .45; cursor: default; }

    /* While the POST is in flight. The old code swapped the button's text to
       "Posting...", which deleted the icon inside it and never put it back. */
    #post-comment.is-busy svg { opacity: 0; }

    #post-comment.is-busy::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid currentColor;
        border-top-color: transparent;
        animation: sheet-send-spin .7s linear infinite;
    }

    #post-comment { position: relative; }

    @keyframes sheet-send-spin { to { transform: rotate(360deg); } }

    /* ------------------------------------------------------------ drag state */

    /* While a finger is on the sheet it must track the finger exactly, so the
       easing that makes the open/close slide feel good has to come off -- with it
       on, the sheet lags behind the drag. The offset arrives as a custom property
       rather than an inline transform so the desktop centring below survives. */
    .comments-sidebar.dragging,
    .profile-modal-content.dragging {
        transition: none;
        transform: translateY(var(--sheet-drag, 0px));
    }

    /* ------------------------------------------------------------ profile sheet */

    .profile-modal-content { padding-bottom: env(safe-area-inset-bottom); }

    /* The sheet opens on the person's cover photo, with their picture sitting on
       its edge -- the same shape as the profile page it links to. */
    .ps-cover {
        --ps-tint: #cb0c9f;
        position: relative;
        height: 150px;
        flex: 0 0 auto;
        overflow: hidden;
        background:
            radial-gradient(120% 140% at 15% 0%, var(--ps-tint), transparent 70%),
            radial-gradient(100% 140% at 90% 10%, rgba(123, 92, 255, .55), transparent 65%),
            #14141c;
    }

    .ps-cover__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ps-cover__img.is-blurred {
        transform: scale(1.35);
        filter: blur(22px) saturate(1.3);
        opacity: .8;
    }

    .ps-cover__img[hidden] { display: none; }

    .ps-cover__scrim {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(0, 0, 0, .35) 0%, transparent 40%),
            linear-gradient(180deg, transparent 45%, var(--sheet-surface) 100%);
    }

    /* Grabber and close ride on top of the photo. */
    .ps-grabber {
        position: relative;
        z-index: 2;
    }

    .ps-grabber::before {
        background: #fff;
        opacity: .7;
    }

    .profile-modal-close.ps-close {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 3;
        background: rgba(0, 0, 0, .4);
        border: 1px solid rgba(255, 255, 255, .18);
        color: #fff;
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
    }

    .profile-modal-close.ps-close:hover {
        background: rgba(0, 0, 0, .6);
        color: #fff;
    }

    .ps-body {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 22px 18px;
        margin-top: -50px;
        text-align: center;
    }

    .ps-avatar {
        position: relative;
        width: 96px;
        height: 96px;
        border-radius: 50%;
        padding: 3px;
        background: linear-gradient(135deg, var(--sheet-accent), #7b5cff);
        box-shadow: 0 10px 28px rgba(0, 0, 0, .45);
    }

    .ps-avatar img,
    .ps-avatar .profile-avatar-initials {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 3px solid var(--sheet-surface);
    }

    .ps-avatar .profile-avatar-initials {
        display: grid;
        place-items: center;
        font-size: 32px;
        font-weight: 700;
        color: #fff;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .28);
    }

    .ps-avatar img {
        display: block;
        object-fit: cover;
        background: var(--sheet-raised);
    }

    .ps-avatar img[hidden],
    .ps-avatar .profile-avatar-initials[hidden] { display: none; }

    .ps-id { margin-top: 12px; }

    .ps-id h3 {
        margin: 0;
        font-size: 21px;
        font-weight: 800;
        letter-spacing: -.01em;
        color: var(--sheet-text);
    }

    .ps-id p {
        margin: 3px 0 0;
        font-size: 14px;
        color: var(--sheet-text-dim);
    }

    .ps-bio {
        margin: 12px 0 0;
        max-width: 360px;
        font-size: 14px;
        line-height: 1.45;
        color: var(--sheet-text-dim);
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ps-bio[hidden] { display: none; }

    /* View Profile reads as the primary action: solid white on the dark sheet. */
    .profile-actions .view-profile-btn {
        background: #fff;
        color: #0b0b0e;
    }

    .profile-actions {
        display: flex;
        gap: 10px;
        padding: 0 20px 20px;
    }

    .profile-action-btn {
        flex: 1 1 0;
        min-height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 14px;
        border: 1px solid transparent;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 650;
        cursor: pointer;
        transition: transform .12s ease, background-color .16s ease, border-color .16s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .profile-action-btn:active { transform: scale(.97); }

    .view-profile-btn {
        background: var(--sheet-accent);
        color: var(--sheet-on-accent);
    }

    .share-profile-btn {
        background: var(--sheet-raised);
        border-color: var(--sheet-border);
        color: var(--sheet-text);
    }

    .share-profile-btn:hover { background: var(--sheet-sunken); }

    /* ------------------------------------------------------------- responsive -- */

    /* Wide screens: the sheets stop being bottom sheets and become centred panels,
       because a full-width drawer on a 1400px monitor is just a stripe. */
    @media (min-width: 769px) {
        .comments-sidebar,
        .profile-modal-content {
            left: 50%;
            right: auto;
            width: 440px;
            transform: translate(-50%, 100%);
            border-radius: 20px 20px 0 0;
        }

        .comments-sidebar.active,
        .profile-modal.active .profile-modal-content {
            transform: translate(-50%, 0);
        }

        /* Keep the -50% while dragging, or the panel jumps to the left edge. */
        .comments-sidebar.dragging,
        .profile-modal-content.dragging {
            transform: translate(-50%, var(--sheet-drag, 0px));
        }

        .comments-sidebar { height: min(70vh, 640px); }
    }

    @media (max-width: 360px) {
        .reaction-emoji { width: 36px; height: 36px; font-size: 19px; }
        .profile-avatar-initials { width: 74px; height: 74px; font-size: 27px; }
        .profile-action-btn { font-size: 13px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .comments-sidebar,
        .profile-modal-content,
        .comments-overlay,
        .profile-modal { transition-duration: .01ms; }
        .comment-item { animation: none; }
        .comment-like.is-bursting svg,
        .reaction-emoji.is-popped { animation: none; }
    }
</style>
