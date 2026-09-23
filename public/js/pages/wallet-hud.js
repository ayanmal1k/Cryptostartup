/*
 * Wallet HUD — counts the money up on arrival.
 *
 * Money that ticks up reads as a score, which is the whole point of the HUD skin.
 * It runs once on load, is skipped entirely under reduced motion, and never
 * touches anything but the text of elements that opted in with [data-count-to].
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function format(value) {
        return '$' + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function run(el) {
        var target = parseFloat(el.getAttribute('data-count-to'));
        if (!isFinite(target) || target <= 0) { return; }

        // Long numbers need longer, but never past ~1s — this is a flourish, not a wait.
        var duration = Math.min(1000, 420 + Math.log10(target + 1) * 170);
        var start = null;

        function frame(now) {
            if (start === null) { start = now; }
            var t = Math.min(1, (now - start) / duration);
            // ease-out-expo: almost all of the count happens immediately.
            var eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
            el.textContent = format(target * eased);
            if (t < 1) {
                requestAnimationFrame(frame);
            } else {
                el.textContent = format(target);
            }
        }

        requestAnimationFrame(frame);
    }

    function start() {
        if (reduceMotion) { return; }
        var els = document.querySelectorAll('[data-count-to]');
        for (var i = 0; i < els.length; i++) {
            // The balance can be hidden behind the eye toggle; leave that alone.
            if (els[i].textContent.indexOf('•') === -1) { run(els[i]); }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
