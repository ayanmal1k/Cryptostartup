{{--
    Celebration toasts.

    The toast helper and its styles are always emitted, because pages that award
    things over AJAX (the rewards hub) need somewhere to show the result even on a
    page load that carried no flashed events. Only the confetti library and the
    replay of flashed events are conditional -- there is no reason to pull a CDN
    script on every request.
--}}
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
<script>
window.gmToast = function (html) {
    var t = document.createElement('div');
    t.className = 'gam-toast';
    t.innerHTML = html;
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('show'); });
    setTimeout(function () {
        t.classList.remove('show');
        setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 400);
    }, 4500);
};
</script>

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

        function message(e) {
            switch (e.type) {
                case 'level':
                    return '🎉 <strong>Level ' + e.level + '!</strong> You leveled up.';
                case 'achievement':
                    return (e.icon || '🏅') + ' Badge unlocked: <strong>' + (e.name || '') + '</strong>';
                case 'quest':
                    return (e.icon || '🎯') + ' Quest complete: <strong>' + (e.name || '') + '</strong>';
                case 'drop':
                    return (e.icon || '🎁') + ' <strong>' + (e.name || 'Drop') + '</strong> earned — open it in Rewards.';
                case 'sweep':
                    return (e.icon || '🏁') + ' <strong>' + (e.name || '') + '</strong> — bonus and a Gold Drop are yours.';
                case 'freeze':
                    return (e.icon || '🧊') + ' <strong>' + (e.name || 'Streak saved') + '</strong> — a freeze covered your missed day.';
                default:
                    return '';
            }
        }

        function run() {
            // Confetti is for the events that earn it. A drop notification firing the
            // full cannon every time is how a celebration stops meaning anything.
            var loud = events.some(function (e) {
                return e.type === 'level' || e.type === 'achievement' || e.type === 'sweep';
            });
            if (loud) { fireConfetti(); }

            events.map(message).filter(Boolean).forEach(function (m, i) {
                setTimeout(function () { window.gmToast(m); }, i * 700);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
    </script>
@endif
