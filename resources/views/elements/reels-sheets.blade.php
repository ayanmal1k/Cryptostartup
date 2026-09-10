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
        padding: 10px 2px;
        animation: sheet-comment-in .3s ease both;
    }

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
        font-size: 12px;
        font-weight: 600;
        color: var(--sheet-text-dim);
        cursor: pointer;
        transition: color .15s ease;
    }

    .comment-reply:hover { color: var(--sheet-accent); }

    .comment-like {
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

    .comment-form #comment-input,
    #comment-input {
        flex: 1 1 auto;
        min-width: 0;
        height: 40px;
        padding: 0 15px;
        border: 1px solid var(--sheet-border);
        border-radius: 99px;
        background: var(--sheet-raised);
        color: var(--sheet-text);
        font-size: 14px;
        outline: none;
        transition: border-color .16s ease, background-color .16s ease;
    }

    #comment-input::placeholder { color: var(--sheet-text-faint); }

    #comment-input:focus {
        border-color: var(--sheet-accent);
        background: var(--sheet-surface);
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

    /* ------------------------------------------------------------ profile sheet */

    .profile-modal-content { padding-bottom: env(safe-area-inset-bottom); }

    /* A colour band behind the avatar. The sheet was an avatar and two buttons on a
       flat panel, which is why it read as an alert rather than a profile. */
    .profile-info {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 20px 18px;
        margin-top: 4px;
    }

    .profile-info::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 74px;
        background:
            radial-gradient(120% 130% at 20% 0%, var(--sheet-accent-soft), transparent 65%),
            radial-gradient(110% 130% at 85% 10%, rgba(84, 122, 255, .12), transparent 60%);
    }

    .profile-avatar {
        position: relative;
        margin-top: 30px;
        padding: 3px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--sheet-accent), #7b5cff);
    }

    .profile-avatar-initials {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 30px;
        font-weight: 700;
        color: #fff;
        border: 3px solid var(--sheet-surface);
        text-shadow: 0 1px 3px rgba(0, 0, 0, .28);
    }

    .profile-details {
        text-align: center;
        margin-top: 12px;
    }

    .profile-details h3 {
        margin: 0;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: -.01em;
        color: var(--sheet-text);
    }

    .profile-details p {
        margin: 3px 0 0;
        font-size: 13.5px;
        color: var(--sheet-text-dim);
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
    }
</style>
