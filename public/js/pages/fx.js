/*
 * FX money pages: wallet, coin, coin market.
 *
 * - Area charts drawn from real series embedded as JSON, with timeframes and a finger
 *   scrub that reads out the value at any point.
 * - Numbers count up on arrival and update live: the page polls a small JSON endpoint
 *   while it is visible and animates whatever changed.
 * - Hide-balance, asset filters, the Send sheet, local-time greeting.
 *
 * Progressive enhancement: every figure is rendered by the server first.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var DAY = 86400;

    /* ------------------------------------------------------------- money format */
    function money(v, opts) {
        opts = opts || {};
        var n = Math.abs(v);
        var s = (v < 0 ? '-' : (opts.sign && v > 0 ? '+' : '')) + '$' +
            n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return s;
    }

    function pct(v) {
        return (v > 0 ? '+' : '') + v.toFixed(2) + '%';
    }

    /* Render "$1,234.56" with the cents smaller, as the balance card does. */
    function setBig(el, v) {
        var s = money(v);
        var dot = s.lastIndexOf('.');
        el.innerHTML = '';
        el.appendChild(document.createTextNode(s.slice(0, dot)));
        var c = document.createElement('span');
        c.className = 'fx-cents';
        c.textContent = s.slice(dot);
        el.appendChild(c);
    }

    function write(el, v) {
        if (el.hasAttribute('data-fx-big')) setBig(el, v);
        else el.textContent = el.hasAttribute('data-fx-plain') ? Math.round(v).toLocaleString() : money(v, { sign: el.hasAttribute('data-fx-sign') });
    }

    /* ------------------------------------------------------------------ count-up */
    function countTo(el, to, from) {
        from = typeof from === 'number' ? from : 0;
        if (reduceMotion || from === to) { write(el, to); return; }
        var dur = Math.min(1100, 450 + Math.log10(Math.abs(to - from) + 1) * 180);
        var start = null;
        function frame(now) {
            if (start === null) start = now;
            var t = Math.min(1, (now - start) / dur);
            var e = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
            write(el, from + (to - from) * e);
            if (t < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    document.querySelectorAll('[data-fx-count]').forEach(function (el) {
        var to = parseFloat(el.getAttribute('data-fx-count'));
        if (isFinite(to) && to !== 0) countTo(el, to, 0);
    });

    /* ------------------------------------------------------------------ greeting */
    var hello = document.querySelector('[data-fx-greeting]');
    if (hello) {
        var h = new Date().getHours();
        hello.textContent = h < 5 ? 'Up late' : h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
    }

    /* -------------------------------------------------------------- hide balance */
    var eye = document.querySelector('[data-fx-eye]');
    if (eye) {
        var root = document.querySelector('.fx');
        var key = 'fx-hide-money';
        var hidden = false;
        try { hidden = localStorage.getItem(key) === '1'; } catch (e) { /* private mode */ }
        function apply() {
            root.classList.toggle('is-hidden-money', hidden);
            eye.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            eye.querySelector('[data-open]').hidden = hidden;
            eye.querySelector('[data-closed]').hidden = !hidden;
        }
        apply();
        eye.addEventListener('click', function () {
            hidden = !hidden;
            try { localStorage.setItem(key, hidden ? '1' : '0'); } catch (e) { /* ignore */ }
            apply();
        });
    }

    /* ------------------------------------------------------------ asset filters */
    document.querySelectorAll('[data-fx-filter]').forEach(function (group) {
        var target = document.getElementById(group.getAttribute('data-fx-filter'));
        if (!target) return;
        group.addEventListener('click', function (e) {
            var chip = e.target.closest('.fx-chip');
            if (!chip) return;
            group.querySelectorAll('.fx-chip').forEach(function (c) {
                var on = c === chip;
                c.classList.toggle('is-on', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            var kind = chip.getAttribute('data-kind');
            var shown = 0;
            target.querySelectorAll('[data-kind]').forEach(function (row) {
                var show = kind === 'all' || row.getAttribute('data-kind') === kind;
                row.hidden = !show;
                if (show) shown++;
            });
            var empty = target.querySelector('[data-fx-filter-empty]');
            if (empty) empty.hidden = shown > 0;
        });
    });

    /* ----------------------------------------------------------------- sheets */
    function openSheet(id) {
        var sheet = document.getElementById(id);
        if (!sheet) return;
        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
        var first = sheet.querySelector('input:not([type=hidden]), select, button');
        if (first) setTimeout(function () { first.focus(); }, 350);
    }
    function closeSheet(sheet) {
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflow = '';
    }
    document.querySelectorAll('[data-fx-open]').forEach(function (btn) {
        btn.addEventListener('click', function () { openSheet(btn.getAttribute('data-fx-open')); });
    });
    document.querySelectorAll('.fx-sheet').forEach(function (sheet) {
        sheet.addEventListener('click', function (e) {
            if (e.target === sheet || e.target.closest('[data-fx-close]')) closeSheet(sheet);
        });
        if (sheet.hasAttribute('data-fx-autoopen')) openSheet(sheet.id);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.fx-sheet.is-open').forEach(closeSheet);
    });

    /* --------------------------------------------------------- segmented control */
    function moveThumb(seg) {
        var on = seg.querySelector('button.is-on');
        var thumb = seg.querySelector('.fx-seg__thumb');
        if (!on || !thumb) return;
        thumb.style.width = on.offsetWidth + 'px';
        thumb.style.transform = 'translateX(' + (on.offsetLeft - 3) + 'px)';
    }

    /* ------------------------------------------------------------------- charts */
    var NS = 'http://www.w3.org/2000/svg';

    function svgEl(name, attrs) {
        var n = document.createElementNS(NS, name);
        for (var k in attrs) n.setAttribute(k, attrs[k]);
        return n;
    }

    function fmtDate(t) {
        var d = new Date(t * 1000);
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ' · ' +
            d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }

    function Chart(root) {
        var data;
        try { data = JSON.parse(root.querySelector('script[type="application/json"]').textContent); } catch (e) { return; }
        var series = (data.series || []).filter(function (p) { return isFinite(p.t) && isFinite(p.v); });
        var mode = data.mode || 'money';        // 'money' shows $, 'change' shows % from start
        var box = root.querySelector('.fx-chart__box');
        var valueEl = root.querySelector('[data-fx-chart-value]');
        var deltaEl = root.querySelector('[data-fx-chart-delta]');
        var seg = root.querySelector('.fx-seg');
        var range = (seg && seg.querySelector('button.is-on') || {}).value || 'all';
        var svg, pts = [], view = [];

        function slice() {
            if (range === 'all' || !series.length) return series.slice();
            var span = { '1d': DAY, '1w': 7 * DAY, '1m': 30 * DAY, '3m': 90 * DAY, '1y': 365 * DAY }[range] || 0;
            var from = series[series.length - 1].t - span;
            var inRange = series.filter(function (p) { return p.t >= from; });
            // Carry the last value from before the window in, so the line starts where it was.
            var before = series.filter(function (p) { return p.t < from; }).pop();
            if (before) inRange.unshift({ t: from, v: before.v });
            if (inRange.length < 2 && inRange.length) inRange.unshift({ t: from, v: inRange[0].v });
            return inRange;
        }

        function label(v, base) {
            if (mode === 'change') return base > 0 ? pct((v - base) / base * 100) : '0.00%';
            return money(v);
        }

        function header(from, to) {
            if (valueEl && mode === 'money') valueEl.textContent = money(to);
            if (!deltaEl) return;
            var d = to - from;
            var p = from > 0 ? d / from * 100 : 0;
            var cls = Math.abs(d) < 0.005 ? 'fx-flat' : (d > 0 ? 'fx-up' : 'fx-down');
            deltaEl.className = 'fx-chart__delta ' + cls;
            deltaEl.textContent = Math.abs(d) < 0.005
                ? (data.flatText || 'No change')
                : (mode === 'money' ? money(d, { sign: true }) + ' (' + pct(p) + ')' : pct(p));
        }

        function draw() {
            view = slice();
            box.querySelectorAll('svg').forEach(function (n) { n.remove(); });
            var empty = box.querySelector('.fx-chart__empty');
            var w = box.clientWidth, h = box.clientHeight;
            if (view.length < 2 || !w) {
                if (empty) empty.hidden = false;
                return;
            }
            if (empty) empty.hidden = true;

            var t0 = view[0].t, t1 = view[view.length - 1].t;
            var vals = view.map(function (p) { return p.v; });
            var lo = Math.min.apply(null, vals), hi = Math.max.apply(null, vals);
            // A flat line sits a little below centre with a faint glow, instead of a
            // full-height block of fill that reads as a solid slab.
            var flat = hi - lo < 1e-9;
            var pad = flat ? Math.max(Math.abs(hi) * 0.1, 1) : (hi - lo) * 0.18;
            lo -= flat ? pad * 1.3 : pad; hi += flat ? pad * 0.7 : pad;
            var x = function (t) { return t1 === t0 ? 0 : (t - t0) / (t1 - t0) * w; };
            var y = function (v) { return h - 14 - (v - lo) / (hi - lo) * (h - 28); };
            pts = view.map(function (p) { return { x: x(p.t), y: y(p.v), p: p }; });

            var up = view[view.length - 1].v >= view[0].v;
            var line = pts.map(function (q, i) { return (i ? 'L' : 'M') + q.x.toFixed(1) + ' ' + q.y.toFixed(1); }).join(' ');
            var id = 'fxg' + Math.random().toString(36).slice(2, 7);

            svg = svgEl('svg', { viewBox: '0 0 ' + w + ' ' + h, 'aria-hidden': 'true' });
            var defs = svgEl('defs', {});
            var fill = svgEl('linearGradient', { id: id + 'f', x1: 0, y1: 0, x2: 0, y2: 1 });
            fill.appendChild(svgEl('stop', { offset: '0', 'stop-color': up ? '#7c5cff' : '#ff5f7e', 'stop-opacity': flat ? '.14' : '.34' }));
            fill.appendChild(svgEl('stop', { offset: flat ? '.55' : '.9', 'stop-color': up ? '#28d7ff' : '#ff5f7e', 'stop-opacity': '0' }));
            var stroke = svgEl('linearGradient', { id: id + 's', x1: 0, y1: 0, x2: 1, y2: 0 });
            stroke.appendChild(svgEl('stop', { offset: '0', 'stop-color': up ? '#28d7ff' : '#ff9a7e' }));
            stroke.appendChild(svgEl('stop', { offset: '.55', 'stop-color': up ? '#7c5cff' : '#ff5f7e' }));
            stroke.appendChild(svgEl('stop', { offset: '1', 'stop-color': up ? '#34f5a8' : '#ff5f7e' }));
            defs.appendChild(fill);
            defs.appendChild(stroke);
            svg.appendChild(defs);

            // Faint baseline at the starting value, so the eye reads up vs down.
            svg.appendChild(svgEl('line', { x1: 0, x2: w, y1: pts[0].y, y2: pts[0].y, stroke: 'currentColor', 'stroke-opacity': '.12', 'stroke-dasharray': '3 5' }));
            svg.appendChild(svgEl('path', { d: line + ' L' + w + ' ' + h + ' L0 ' + h + ' Z', fill: 'url(#' + id + 'f)' }));
            var path = svgEl('path', { d: line, class: 'fx-chart__line', stroke: 'url(#' + id + 's)' });
            svg.appendChild(path);

            var last = pts[pts.length - 1];
            var endColor = up ? '#34f5a8' : '#ff5f7e';
            svg.appendChild(svgEl('circle', { cx: last.x, cy: last.y, r: 5, fill: endColor, class: 'fx-chart__pulse' }));
            svg.appendChild(svgEl('circle', { cx: last.x, cy: last.y, r: 4.5, fill: endColor, stroke: 'var(--fx-bg)', 'stroke-width': 2 }));

            var cursor = svgEl('g', { class: 'fx-chart__cursor' });
            cursor.appendChild(svgEl('line', { y1: 0, y2: h, stroke: 'currentColor', 'stroke-opacity': '.35', 'stroke-dasharray': '2 4' }));
            cursor.appendChild(svgEl('circle', { r: 6, fill: 'var(--fx-bg)', stroke: 'currentColor', 'stroke-width': 2.5 }));
            svg.appendChild(cursor);
            box.insertBefore(svg, box.firstChild);

            // Draw the line in once.
            if (!reduceMotion && path.getTotalLength) {
                var len = path.getTotalLength();
                path.style.strokeDasharray = len;
                path.style.strokeDashoffset = len;
                path.getBoundingClientRect();
                path.style.transition = 'stroke-dashoffset 1s cubic-bezier(.22,1,.36,1)';
                path.style.strokeDashoffset = '0';
            }

            header(view[0].v, view[view.length - 1].v);
        }

        var tip = box.querySelector('.fx-chart__tip');
        function scrub(clientX) {
            if (!pts.length || !svg) return;
            var r = box.getBoundingClientRect();
            var px = Math.max(0, Math.min(r.width, clientX - r.left));
            var best = pts[0];
            for (var i = 1; i < pts.length; i++) {
                if (Math.abs(pts[i].x - px) < Math.abs(best.x - px)) best = pts[i];
            }
            var g = svg.querySelector('.fx-chart__cursor');
            g.querySelector('line').setAttribute('x1', best.x);
            g.querySelector('line').setAttribute('x2', best.x);
            g.querySelector('circle').setAttribute('cx', best.x);
            g.querySelector('circle').setAttribute('cy', best.y);
            box.classList.add('is-scrubbing');
            if (tip) {
                tip.innerHTML = '';
                tip.appendChild(document.createTextNode(label(best.p.v, view[0].v)));
                var s = document.createElement('small');
                s.textContent = fmtDate(best.p.t);
                tip.appendChild(s);
                tip.style.left = Math.max(50, Math.min(r.width - 50, best.x)) + 'px';
                tip.style.top = best.y + 'px';
            }
            header(view[0].v, best.p.v);
        }
        function endScrub() {
            box.classList.remove('is-scrubbing');
            if (view.length) header(view[0].v, view[view.length - 1].v);
        }

        box.addEventListener('pointermove', function (e) { if (e.pointerType === 'mouse' || box.classList.contains('is-scrubbing')) scrub(e.clientX); });
        box.addEventListener('pointerdown', function (e) { scrub(e.clientX); });
        box.addEventListener('pointerleave', endScrub);
        box.addEventListener('pointerup', function (e) { if (e.pointerType !== 'mouse') endScrub(); });
        box.addEventListener('pointercancel', endScrub);

        if (seg) {
            seg.addEventListener('click', function (e) {
                var b = e.target.closest('button');
                if (!b) return;
                seg.querySelectorAll('button').forEach(function (x) {
                    x.classList.toggle('is-on', x === b);
                    x.setAttribute('aria-pressed', x === b ? 'true' : 'false');
                });
                range = b.value;
                moveThumb(seg);
                draw();
            });
            moveThumb(seg);
        }

        var rt;
        window.addEventListener('resize', function () {
            clearTimeout(rt);
            rt = setTimeout(function () { if (seg) moveThumb(seg); draw(); }, 120);
        });

        draw();

        // Live updates append a new "now" point.
        root._fxPush = function (v) {
            var now = Math.floor(Date.now() / 1000);
            series.push({ t: now, v: v });
            draw();
        };
    }

    document.querySelectorAll('[data-fx-chart]').forEach(function (el) { Chart(el); });

    /* --------------------------------------------------------------------- ring */
    document.querySelectorAll('[data-fx-ring]').forEach(function (ring) {
        var bar = ring.querySelector('.fx-ring__bar');
        var p = Math.max(0, Math.min(100, parseFloat(ring.getAttribute('data-fx-ring')) || 0));
        var len = 2 * Math.PI * parseFloat(bar.getAttribute('r'));
        bar.style.strokeDasharray = len;
        bar.style.strokeDashoffset = len;
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { bar.style.strokeDashoffset = len * (1 - p / 100); });
        });
        ring._fxSet = function (np) {
            bar.style.strokeDashoffset = len * (1 - Math.max(0, Math.min(100, np)) / 100);
        };
    });

    /* ---------------------------------------------------------------- live data */
    var liveRoot = document.querySelector('[data-fx-live]');
    if (liveRoot && window.fetch) {
        var url = liveRoot.getAttribute('data-fx-live');
        var every = parseInt(liveRoot.getAttribute('data-fx-every'), 10) || 30000;
        var last = {};

        function get(obj, path) {
            return path.split('.').reduce(function (o, k) { return o == null ? o : o[k]; }, obj);
        }

        function update(data) {
            document.querySelectorAll('[data-fx-bind]').forEach(function (el) {
                var path = el.getAttribute('data-fx-bind');
                var v = get(data, path);
                if (v == null) return;
                if (typeof v === 'number') {
                    var prev = last[path];
                    if (prev === undefined) {
                        prev = parseFloat(el.getAttribute('data-fx-count') || el.getAttribute('data-fx-value'));
                    }
                    if (isFinite(prev) && Math.abs(prev - v) >= 0.005) {
                        countTo(el, v, prev);
                        el.classList.remove('fx-ticked-up', 'fx-ticked-down');
                        void el.offsetWidth;
                        el.classList.add(v > prev ? 'fx-ticked-up' : 'fx-ticked-down');
                    }
                    last[path] = v;
                } else if (el.textContent !== String(v)) {
                    el.textContent = v;
                }
            });
            (data.holdings || []).forEach(function (hRow) {
                var row = document.querySelector('[data-fx-holding="' + hRow.id + '"] [data-fx-hvalue]');
                if (row) {
                    var prev = parseFloat(row.getAttribute('data-fx-value'));
                    if (isFinite(prev) && Math.abs(prev - hRow.value) >= 0.005) {
                        countTo(row, hRow.value, prev);
                        row.setAttribute('data-fx-value', hRow.value);
                    }
                }
            });
            var ring = document.querySelector('[data-fx-ring]');
            if (ring && ring._fxSet && data.level) ring._fxSet(data.level.percent);
            var chart = document.querySelector('[data-fx-chart][data-fx-live-series]');
            var sv = chart && get(data, chart.getAttribute('data-fx-live-series'));
            if (chart && chart._fxPush && typeof sv === 'number' && last['__series'] !== sv) {
                if (last['__series'] !== undefined) chart._fxPush(sv);
                last['__series'] = sv;
            }
        }

        function poll() {
            if (document.hidden) { setTimeout(poll, every); return; }
            fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) { if (d) update(d); })
                .catch(function () {})
                .then(function () { setTimeout(poll, every); });
        }
        setTimeout(poll, every);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) { if (d) update(d); })
                    .catch(function () {});
            }
        });
    }
})();
