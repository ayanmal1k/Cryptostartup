/*
 * Share / Copy link for the challenge and NFT detail pages.
 *
 *   <button data-share data-share-title="…">   phone share sheet, or copies the link
 *   <button data-copy-link>                    always copies the link
 *
 * Both confirm with a small "Link copied" toast (.cd-toast in challenge-detail.css).
 */
(function () {
    var toastEl;

    function toast(text) {
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.className = 'cd-toast';
            toastEl.setAttribute('role', 'status');
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = text;
        toastEl.classList.add('is-on');
        clearTimeout(toastEl._t);
        toastEl._t = setTimeout(function () { toastEl.classList.remove('is-on'); }, 1800);
    }

    function copy(url, done) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function () { toast(done); }, function () { fallback(url, done); });
        } else {
            fallback(url, done);
        }
    }

    function fallback(url, done) {
        var input = document.createElement('input');
        input.value = url;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        try { document.execCommand('copy'); toast(done); } catch (e) { window.prompt('', url); }
        document.body.removeChild(input);
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-share], [data-copy-link]');
        if (!btn) return;
        var url = btn.getAttribute('data-url') || window.location.href.split('#')[0];
        var done = btn.getAttribute('data-copied') || 'Link copied';

        if (btn.hasAttribute('data-share') && navigator.share) {
            navigator.share({ title: btn.getAttribute('data-share-title') || document.title, url: url })
                .catch(function () { /* closed the sheet — nothing to do */ });
            return;
        }
        copy(url, done);
    });
})();
