@if(session()->has('gamification_celebrations'))
    @php($gamCelebrations = session('gamification_celebrations'))
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <script>
    (function () {
        var events = @json($gamCelebrations);
        if (!events || !events.length) { return; }

        function fireConfetti() {
            if (typeof confetti !== 'function') { return; }
            var colors = ['#830866', '#a10a7f', '#ffd700', '#ffffff'];
            confetti({ particleCount: 130, spread: 75, origin: { y: 0.6 }, colors: colors });
            setTimeout(function () { confetti({ particleCount: 60, angle: 60, spread: 55, origin: { x: 0 }, colors: colors }); }, 200);
            setTimeout(function () { confetti({ particleCount: 60, angle: 120, spread: 55, origin: { x: 1 }, colors: colors }); }, 400);
        }

        function showToast(html) {
            var t = document.createElement('div');
            t.className = 'gam-toast';
            t.innerHTML = html;
            document.body.appendChild(t);
            requestAnimationFrame(function () { t.classList.add('show'); });
            setTimeout(function () {
                t.classList.remove('show');
                setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 400);
            }, 4500);
        }

        function run() {
            fireConfetti();
            var msgs = events.map(function (e) {
                if (e.type === 'level') { return '🎉 <strong>Level ' + e.level + '!</strong> You leveled up.'; }
                if (e.type === 'achievement') { return (e.icon || '🏅') + ' Badge unlocked: <strong>' + (e.name || '') + '</strong>'; }
                return '';
            }).filter(Boolean);
            msgs.forEach(function (m, i) { setTimeout(function () { showToast(m); }, i * 700); });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
    </script>
    <style>
    .gam-toast {
        position: fixed; left: 50%; top: 22px;
        transform: translate(-50%, -24px);
        background: #fff; color: #222;
        border-radius: 14px; padding: 14px 20px;
        box-shadow: 0 10px 30px rgba(131, 8, 102, .28);
        border: 1px solid rgba(131, 8, 102, .2);
        font-weight: 600; z-index: 100000; max-width: 90vw;
        opacity: 0; transition: all .35s cubic-bezier(.2, .8, .2, 1);
    }
    .gam-toast.show { opacity: 1; transform: translate(-50%, 0); }
    .gam-toast strong { color: #830866; }
    </style>
@endif
