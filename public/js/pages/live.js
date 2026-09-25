/*
 * Livestream pages: chat polling, the creator's studio (camera preview, go live, end),
 * the live timer and the cover picker on the setup form.
 *
 * No video service is connected yet, so the studio camera is a local preview only:
 * nothing leaves the creator's device. Chat is real and goes through the server.
 */
(function () {
    'use strict';

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function send(url, method, body) {
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) {
                if (!r.ok) { throw data; }
                return data;
            });
        });
    }

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }

    /* ---------------------------------------------------------------- live timer */
    document.querySelectorAll('[data-lv-timer]').forEach(function (node) {
        var started = Date.parse(node.getAttribute('data-lv-timer'));
        if (!started) return;
        function tick() {
            var s = Math.max(0, Math.floor((Date.now() - started) / 1000));
            var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
            node.textContent = (h ? h + ':' + String(m).padStart(2, '0') : m) + ':' + String(sec).padStart(2, '0');
        }
        tick();
        node._lvTimer = setInterval(tick, 1000);
    });

    /* ---------------------------------------------------------------------- chat */
    document.querySelectorAll('[data-lv-chat]').forEach(function (chat) {
        var list = chat.querySelector('[data-lv-chat-list]');
        var form = chat.querySelector('[data-lv-chat-form]');
        var input = form && form.querySelector('input');
        var empty = chat.querySelector('[data-lv-chat-empty]');
        var count = document.querySelector('[data-lv-viewers]');
        var fetchUrl = chat.getAttribute('data-fetch');
        var postUrl = chat.getAttribute('data-post');
        var lastId = 0;
        var seen = {};
        var wasLive = chat.getAttribute('data-live') === '1';

        function render(m) {
            if (seen[m.id]) return;
            seen[m.id] = true;
            lastId = Math.max(lastId, m.id);
            if (empty) empty.hidden = true;
            var row = el('div', 'lv-msg');
            var img = el('img');
            img.alt = '';
            img.loading = 'lazy';
            if (m.avatar) img.src = m.avatar;
            var body = el('div');
            body.appendChild(el('span', 'lv-msg__name' + (m.is_creator ? ' is-creator' : ''), m.name));
            body.appendChild(el('span', 'lv-msg__text', m.text));
            row.appendChild(img);
            row.appendChild(body);
            var atEnd = list.scrollHeight - list.scrollTop - list.clientHeight < 60;
            list.appendChild(row);
            if (atEnd) list.scrollTop = list.scrollHeight;
        }

        function poll() {
            fetch(fetchUrl + '?after=' + lastId, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    (data.messages || []).forEach(render);
                    if (count && typeof data.viewers === 'number') count.textContent = data.viewers.toLocaleString();
                    // A viewer's page notices the stream ending without a reload.
                    if (wasLive && data.is_live === false) {
                        wasLive = false;
                        document.dispatchEvent(new CustomEvent('lv:ended'));
                    }
                })
                .catch(function () {})
                .then(function () { setTimeout(poll, document.hidden ? 10000 : 3000); });
        }
        poll();

        if (form && input) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var text = input.value.trim();
                if (!text) return;
                var btn = form.querySelector('button');
                btn.disabled = true;
                send(postUrl, 'POST', { message: text })
                    .then(function (data) {
                        input.value = '';
                        if (data.message) render(data.message);
                        list.scrollTop = list.scrollHeight;
                    })
                    .catch(function (err) {
                        if (window.launchToast) window.launchToast('danger', 'Chat', (err && err.message) || 'Could not send that.');
                    })
                    .then(function () { btn.disabled = false; input.focus(); });
            });
        }
    });

    /* ------------------------------------------------------ viewer: stream ended */
    document.addEventListener('lv:ended', function () {
        var live = document.querySelector('[data-lv-state="live"]');
        var ended = document.querySelector('[data-lv-state="ended"]');
        if (live) live.hidden = true;
        if (ended) ended.hidden = false;
        document.querySelectorAll('.lv-badge[data-lv-live-badge]').forEach(function (b) { b.remove(); });
        var form = document.querySelector('[data-lv-chat-form]');
        if (form) form.hidden = true;
    });

    /* -------------------------------------------------------------------- studio */
    var studio = document.querySelector('[data-lv-studio]');
    if (studio) {
        var video = studio.querySelector('[data-lv-camera]');
        var msg = studio.querySelector('[data-lv-camera-msg]');
        var camBtn = studio.querySelector('[data-lv-toggle="video"]');
        var micBtn = studio.querySelector('[data-lv-toggle="audio"]');
        var flipBtn = studio.querySelector('[data-lv-flip]');
        var startBtn = document.querySelector('[data-lv-start]');
        var endBtn = document.querySelector('[data-lv-end]');
        var stream = null;
        var facing = 'user';

        function stop() {
            if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }

        function openCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                msg.querySelector('p').textContent = 'This browser can’t open a camera. Try Chrome or Safari.';
                return;
            }
            stop();
            navigator.mediaDevices.getUserMedia({ video: { facingMode: facing }, audio: true })
                .then(function (s) {
                    stream = s;
                    video.srcObject = s;
                    video.hidden = false;
                    video.classList.toggle('is-mirrored', facing === 'user');
                    msg.hidden = true;
                    studio.classList.add('has-camera');
                    [camBtn, micBtn, flipBtn].forEach(function (b) { if (b) b.hidden = false; });
                })
                .catch(function () {
                    msg.querySelector('p').textContent = 'Camera blocked. Allow camera and microphone in your browser settings, then try again.';
                });
        }

        var openBtn = studio.querySelector('[data-lv-open-camera]');
        if (openBtn) openBtn.addEventListener('click', openCamera);

        function toggle(btn, kind) {
            if (!btn) return;
            btn.addEventListener('click', function () {
                if (!stream) return;
                var tracks = kind === 'video' ? stream.getVideoTracks() : stream.getAudioTracks();
                var on = !(tracks[0] && tracks[0].enabled);
                tracks.forEach(function (t) { t.enabled = on; });
                btn.classList.toggle('is-off', !on);
                btn.setAttribute('aria-pressed', on ? 'false' : 'true');
            });
        }
        toggle(camBtn, 'video');
        toggle(micBtn, 'audio');
        if (flipBtn) flipBtn.addEventListener('click', function () {
            facing = facing === 'user' ? 'environment' : 'user';
            openCamera();
        });

        window.addEventListener('pagehide', stop);

        if (startBtn) startBtn.addEventListener('click', function () {
            startBtn.disabled = true;
            send(startBtn.getAttribute('data-lv-start'), 'POST')
                .then(function () { window.location.reload(); })
                .catch(function () {
                    startBtn.disabled = false;
                    if (window.launchToast) window.launchToast('danger', 'Live', 'Could not go live. Try again.');
                });
        });

        if (endBtn) endBtn.addEventListener('click', function () {
            if (!window.confirm('End this stream for everyone?')) return;
            endBtn.disabled = true;
            send(endBtn.getAttribute('data-lv-end'), 'POST')
                .then(function () { stop(); window.location.href = endBtn.getAttribute('data-after'); })
                .catch(function () { endBtn.disabled = false; });
        });
    }

    /* --------------------------------------------------------- copy + share */
    document.querySelectorAll('[data-lv-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-lv-copy'));
            var text = target ? (target.value || target.textContent) : btn.getAttribute('data-text');
            var done = function () {
                var old = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = old; }, 1400);
            };
            if (navigator.share && btn.hasAttribute('data-lv-share')) {
                navigator.share({ url: text }).catch(function () {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(done);
            }
        });
    });

    /* ----------------------------------------------------- setup: cover picker */
    var cover = document.querySelector('[data-lv-cover]');
    if (cover) {
        var file = cover.querySelector('input[type="file"]');
        var preview = cover.querySelector('img');
        var emptyState = cover.querySelector('.lv-cover__empty');
        file.addEventListener('change', function () {
            var f = file.files && file.files[0];
            if (!f) return;
            preview.src = URL.createObjectURL(f);
            preview.hidden = false;
            if (emptyState) emptyState.hidden = true;
        });
    }
})();
