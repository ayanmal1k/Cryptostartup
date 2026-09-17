/* Discover pages (NFT marketplace, Challenges).
   Progressive enhancement only: every page renders and links correctly without this file. */
(function () {
    'use strict';

    var mq = function (q) { return !!(window.matchMedia && window.matchMedia(q).matches); };
    var reduceMotion = mq('(prefers-reduced-motion: reduce)');
    var saveData = !!(navigator.connection && navigator.connection.saveData);
    var quietPlay = function (v) { var p = v.play(); if (p && p.catch) p.catch(function () {}); };

    /* Stick the tab bar directly under the mobile app bar, at whatever height it renders. */
    function syncTop() {
        var bar = document.querySelector('.mobile-app-bar');
        var h = bar && getComputedStyle(bar).display !== 'none' ? bar.offsetHeight : 0;
        document.querySelectorAll('.dx').forEach(function (el) {
            el.style.setProperty('--dx-top', h + 'px');
        });
    }
    syncTop();
    window.addEventListener('resize', syncTop);

    /* Search drawer */
    document.querySelectorAll('[data-dx-subnav]').forEach(function (nav) {
        var toggle = nav.querySelector('[data-dx-search-toggle]');
        var input = nav.querySelector('[data-dx-search-input]');
        if (!toggle || !input) return;

        var setOpen = function (open) {
            nav.classList.toggle('is-searching', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', function () {
            var open = !nav.classList.contains('is-searching');
            setOpen(open);
            if (open) input.focus();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !input.value) {
                setOpen(false);
                toggle.focus();
            }
        });
    });

    /* Featured media plays muted while it is on screen. Its src is only attached when it
       first scrolls into view, so a visitor who never sees it never downloads it. */
    var autoplay = document.querySelectorAll('video[data-dx-autoplay]');
    if (autoplay.length && 'IntersectionObserver' in window && !reduceMotion && !saveData) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var v = entry.target;
                if (entry.isIntersecting) {
                    if (!v.getAttribute('src') && v.dataset.src) v.src = v.dataset.src;
                    quietPlay(v);
                } else if (!v.paused) {
                    v.pause();
                }
            });
        }, { threshold: 0.5 });

        autoplay.forEach(function (v) {
            var scope = v.closest('[data-dx-media]');
            v.addEventListener('playing', function () { if (scope) scope.classList.add('is-playing'); });
            io.observe(v);
        });
    }

    /* Sound toggle on featured media */
    document.querySelectorAll('[data-dx-sound]').forEach(function (btn) {
        var scope = btn.closest('[data-dx-media]');
        var v = scope && scope.querySelector('video');
        if (!v) return;

        btn.hidden = false;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!v.getAttribute('src') && v.dataset.src) v.src = v.dataset.src;
            v.muted = !v.muted;
            if (v.paused) quietPlay(v);
            btn.classList.toggle('is-on', !v.muted);
            btn.setAttribute('aria-pressed', v.muted ? 'false' : 'true');
        });
    });

    /* Hover preview on video tiles — hover pointers only, so phones keep their posters and
       never spend data on clips nobody asked to watch. */
    if (mq('(hover: hover)') && !reduceMotion && !saveData) {
        document.querySelectorAll('[data-dx-preview]').forEach(function (media) {
            var video = null;

            media.addEventListener('mouseenter', function () {
                if (!video) {
                    video = document.createElement('video');
                    video.muted = true;
                    video.loop = true;
                    video.playsInline = true;
                    video.preload = 'auto';
                    video.className = 'dx-preview-video';
                    video.setAttribute('aria-hidden', 'true');
                    video.src = media.getAttribute('data-dx-preview');
                    video.addEventListener('playing', function () { media.classList.add('is-previewing'); });
                    media.appendChild(video);
                } else {
                    video.currentTime = 0;
                }
                quietPlay(video);
            });

            media.addEventListener('mouseleave', function () {
                if (!video) return;
                video.pause();
                media.classList.remove('is-previewing');
            });
        });
    }

    /* Creator earnings calculator (NFT marketplace). The server renders the default figures,
       this only keeps them in step with the slider. Maths mirrors the marketplace service:
       first sale = price - platform fee; every resale pays the creator the royalty. */
    document.querySelectorAll('[data-dx-earn]').forEach(function (calc) {
        var range = calc.querySelector('input[type="range"]');
        if (!range) return;

        var steps = (calc.dataset.steps || '').split(',').map(Number);
        var fee = parseFloat(calc.dataset.fee) / 100;
        var royalty = parseFloat(calc.dataset.royalty) / 100;
        var resales = parseInt(calc.dataset.resales, 10) || 0;
        var currency = calc.dataset.currency || '';

        var fmt = function (n) {
            return (Math.round(n * 100) / 100).toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' ' + currency;
        };
        var put = function (name, text) {
            var el = calc.querySelector('[data-out="' + name + '"]');
            if (el) el.textContent = text;
        };

        var update = function () {
            var i = parseInt(range.value, 10) || 0;
            var price = steps[i] || 0;
            var first = price * (1 - fee);
            var each = price * royalty;

            put('price', fmt(price));
            put('first', fmt(first));
            put('resale', fmt(each));
            put('total', fmt(first + each * resales));
            range.style.setProperty('--fill', (i / Math.max(1, steps.length - 1) * 100) + '%');
            range.setAttribute('aria-valuetext', fmt(price));
        };

        range.addEventListener('input', update);
        update();
    });
})();
