{{--
    Comment sheet behaviour, shared by both reels pages.

    The sheet markup and CSS were already shared; the behaviour was not, and the two
    pages had drifted badly apart. pages/feed wired the quick-emoji row, the comment
    like toggle and drag-to-dismiss inline. pages/home wired none of them -- so the
    same sheet, opened from the home feed, had a decorative emoji row that did
    nothing, hearts that could not be tapped, and no way to swipe it away.

    Those handlers now live here and the inline copies are gone, so there is one
    implementation and both pages behave the same.

    Everything is delegated from document, because comment rows are rendered from
    fetch responses long after this runs -- binding to the rows directly is what
    would break on the second video you opened.
--}}
<script>
(function () {
    'use strict';

    // Both reels pages include this; a second copy would bind every handler twice,
    // and a doubled like toggle flips the class back and forth and looks like a
    // button that does nothing at all.
    if (window.__reelsSheetsBehaviour) return;
    window.__reelsSheetsBehaviour = true;

    var sheet = function () { return document.getElementById('comments-sidebar'); };
    var input = function () { return document.getElementById('comment-input'); };

    function isOpen() {
        var el = sheet();
        return !!(el && el.classList.contains('active'));
    }

    /* Closing is the page's job, not ours: each page tears down its own state
       (body class, feed scroll lock, restoring scrollTop) in its own closeComments.
       Clicking its button runs that, instead of reimplementing it here and drifting
       apart all over again. */
    function requestClose() {
        var btn = document.querySelector('.close-comments');
        if (btn) btn.click();
    }

    function syncSend() {
        var box = input();
        var send = document.getElementById('post-comment');
        if (box && send) send.disabled = !box.value.trim();
    }

    /* ------------------------------------------------------- quick reactions */

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.reaction-emoji');
        if (!btn) return;
        e.preventDefault();

        var box = input();
        if (!box) return;

        var emoji = btn.getAttribute('data-emoji') || btn.textContent.trim();
        // Append at the caret rather than the end, so tapping an emoji mid-sentence
        // does not send it to the back of the message.
        var start = box.selectionStart, end = box.selectionEnd;
        if (typeof start === 'number' && typeof end === 'number') {
            box.value = box.value.slice(0, start) + emoji + box.value.slice(end);
            var caret = start + emoji.length;
            box.setSelectionRange(caret, caret);
        } else {
            box.value += emoji;
        }

        box.focus();
        syncSend();

        btn.classList.remove('is-popped');
        // Reflow, or re-adding the class in the same frame never restarts the anim.
        void btn.offsetWidth;
        btn.classList.add('is-popped');
    });

    /* ------------------------------------------------------------ comment likes */

    document.addEventListener('click', function (e) {
        var like = e.target.closest('.comment-like');
        if (!like) return;
        e.preventDefault();

        var liked = !like.classList.contains('liked');
        like.classList.toggle('liked', liked);
        like.setAttribute('aria-pressed', liked ? 'true' : 'false');
        like.setAttribute('aria-label', liked ? 'Unlike comment' : 'Like comment');

        var count = like.querySelector('span');
        if (count) {
            var n = parseInt(count.textContent, 10) || 0;
            count.textContent = liked ? n + 1 : Math.max(0, n - 1);
        }

        if (liked) {
            like.classList.remove('is-bursting');
            void like.offsetWidth;
            like.classList.add('is-bursting');
        }
    });

    /* ------------------------------------------------------------------ reply */

    // Reply was a label with nothing behind it. Addressing the comment in the
    // composer is the whole of what it implies, so do that much rather than leave
    // a control that does nothing when tapped.
    document.addEventListener('click', function (e) {
        var reply = e.target.closest('.comment-reply');
        if (!reply) return;
        e.preventDefault();

        var item = reply.closest('.comment-item');
        var who = item && item.querySelector('.comment-head strong');
        var box = input();
        if (!box) return;

        var handle = who ? who.textContent.trim() : '';
        if (handle && handle.charAt(0) !== '@') handle = '@' + handle;

        if (handle && box.value.indexOf(handle) !== 0) {
            box.value = handle + ' ' + box.value.replace(/^@\S+\s*/, '');
        }
        box.focus();
        var end = box.value.length;
        if (box.setSelectionRange) box.setSelectionRange(end, end);
        syncSend();
    });

    /* -------------------------------------------------------- drag to dismiss */

    (function () {
        var startY = 0, currentY = 0, dragging = false, el = null;

        function start(e) {
            if (!isOpen()) return;
            // A drag that starts on the close button is a press of that button.
            if (e.target.closest('.close-comments')) return;

            el = sheet();
            if (!el) return;

            dragging = true;
            startY = currentY = e.touches ? e.touches[0].clientY : e.clientY;
            el.classList.add('dragging');
        }

        function move(e) {
            if (!dragging || !el) return;
            currentY = e.touches ? e.touches[0].clientY : e.clientY;
            // Downward only: dragging up would lift the sheet off the bottom edge
            // and show the page behind it.
            var delta = Math.max(0, currentY - startY);
            // Published as a custom property rather than written to style.transform:
            // on desktop the sheet is centred with translate(-50%, ...), and an inline
            // translateY would drop the -50% and throw the panel to the left edge.
            el.style.setProperty('--sheet-drag', delta + 'px');
            if (e.cancelable) e.preventDefault();
        }

        function end() {
            if (!dragging || !el) return;
            dragging = false;
            el.classList.remove('dragging');
            el.style.removeProperty('--sheet-drag');

            // Proportional threshold: a quarter of the sheet, capped, so a tall
            // sheet does not demand an unreasonably long drag.
            var threshold = Math.min(140, el.offsetHeight * 0.25);
            if (currentY - startY > threshold) requestClose();
            el = null;
        }

        document.addEventListener('mousedown', function (e) {
            if (e.target.closest('[data-comments-drag]')) start(e);
        });
        document.addEventListener('touchstart', function (e) {
            if (e.target.closest('[data-comments-drag]')) start(e);
        }, { passive: true });

        document.addEventListener('mousemove', move);
        document.addEventListener('touchmove', move, { passive: false });
        document.addEventListener('mouseup', end);
        document.addEventListener('touchend', end);
        document.addEventListener('touchcancel', end);
    })();

    /* ------------------------------------------------------------- composer */

    document.addEventListener('input', function (e) {
        if (e.target && e.target.id === 'comment-input') syncSend();
    });
})();
</script>
