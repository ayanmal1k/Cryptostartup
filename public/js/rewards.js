/* --------------------------------------------------------------------------
   Rewards hub: tabs, daily spin, skill games, quests, drops and the perk shop.
   Plain jQuery to match the rest of the app -- no build step on this host.
   Every result (spin prize, game payout, purchase) is decided by the server;
   this file only asks for it and animates what comes back.
   -------------------------------------------------------------------------- */
(function ($) {
    'use strict';

    if (!window.rwRoutes) {
        return;
    }

    var R = window.rwRoutes;
    var TEXT = window.rwText || {};
    var csrf = $('meta[name="csrf-token"]').attr('content');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function post(url, data) {
        return $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            data: data || {},
            dataType: 'json'
        });
    }

    function errorFrom(xhr) {
        return (xhr && xhr.responseJSON && xhr.responseJSON.message) || TEXT.error;
    }

    function toast(message) {
        var $t = $('<div class="rw-toast" role="status"></div>').text(message).appendTo('body');
        setTimeout(function () { $t.addClass('is-in'); }, 10);
        setTimeout(function () { $t.removeClass('is-in'); setTimeout(function () { $t.remove(); }, 300); }, 3200);
    }

    /** Refresh the hero numbers from a response, so nothing needs a reload. */
    function applyBalance(b) {
        if (!b) return;
        $('#rw-sparks').text(Number(b.sparks).toLocaleString());
        $('#rw-streak').text(b.streak);
        $('#rw-freezes').text(b.freezes);
        $('#rw-xp-into').text(Number(b.xp % 100).toLocaleString());
        $('.js-xp-fill').css('width', b.percent + '%');
        $('.js-level').text(b.level);
        $('.js-buy-drop').each(function () {
            $(this).prop('disabled', Number(b.sparks) < parseInt($(this).data('cost'), 10));
        });
    }

    /* ------------------------------------------------------------------ tabs -- */

    var panels = ['today', 'games', 'drops', 'shop'];

    function activate(name, push) {
        if (panels.indexOf(name) === -1) name = 'today';
        $('[data-rw-panel]').each(function () {
            this.hidden = $(this).data('rw-panel') !== name;
        });
        $('.dx-tab').each(function () {
            var on = $(this).attr('href') === '#' + name;
            $(this).toggleClass('is-active', on).attr('aria-current', on ? 'page' : null);
        });
        if (push && history.replaceState) history.replaceState(null, '', '#' + name);
    }

    $(document).on('click', '.dx-tab, .js-tab-link', function (e) {
        var href = $(this).attr('href') || '';
        if (href.charAt(0) !== '#') return;
        e.preventDefault();
        activate(href.slice(1), true);
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });

    activate((window.location.hash || '#today').slice(1), false);

    /* ----------------------------------------------------------- countdown -- */

    function startCountdown($el, seconds) {
        var end = Date.now() + seconds * 1000;
        function tick() {
            var left = Math.max(0, Math.round((end - Date.now()) / 1000));
            var h = Math.floor(left / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
            $el.text(h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s);
            if (left > 0) setTimeout(tick, 1000);
        }
        tick();
    }

    function secondsToMidnight() {
        var now = new Date();
        var midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        return Math.round((midnight - now) / 1000);
    }

    $('.js-countdown').each(function () {
        startCountdown($(this), parseInt($(this).data('left'), 10) || secondsToMidnight());
    });

    /* --------------------------------------------------------------- reveal -- */

    function showReveal(reward) {
        $('#rw-reveal-burst').text(reward.icon || '🎁');
        $('#rw-reveal-rarity').text(reward.rarity || '').attr('data-rarity', reward.rarity || 'common');
        $('#rw-reveal-name').text(reward.name || '');
        $('#rw-reveal').prop('hidden', false);

        // Confetti only for the rewards that earn it, or it stops meaning anything.
        if (window.confetti && !reduceMotion && (reward.rarity === 'epic' || reward.rarity === 'legendary')) {
            window.confetti({ particleCount: reward.rarity === 'legendary' ? 160 : 90, spread: 75, origin: { y: 0.6 } });
        }
    }

    $(document).on('click', '#rw-reveal-close', function () { $('#rw-reveal').prop('hidden', true); });
    $(document).on('click', '#rw-reveal', function (e) { if (e.target === this) $(this).prop('hidden', true); });

    /* ------------------------------------------------------------- airdrop -- */

    /* When the crate bursts open, in ms from the start of the sequence. Must match
       the burst delay in supply-drop.css -- confetti lands with the flash. */
    var AIR_BURST_MS = 1980;

    /**
     * Open a drop as a supply drop: the crate falls in, lands, and bursts.
     * `stencil` is the tier icon printed on the crate (🥉 🥈 🥇).
     */
    function playAirdrop(reward, stencil) {
        var air = document.getElementById('rw-air');
        if (!air) { return showReveal(reward); }

        $('#rw-air-stencil').text(stencil || '📦');
        $('#rw-air-icon').text(reward.icon || '🎁');
        $('#rw-air-rarity').text(reward.rarity || '');
        $('#rw-air-name').text(reward.name || '');
        air.setAttribute('data-rarity', reward.rarity || 'common');
        air.classList.remove('is-done', 'is-playing');
        air.hidden = false;

        // Re-trigger the CSS timeline: without a reflow between removing and adding
        // the class, the browser never sees the animations restart.
        void air.offsetWidth;
        air.classList.add('is-playing');

        var burst = null;
        function celebrate() {
            if (window.confetti && !reduceMotion && (reward.rarity === 'epic' || reward.rarity === 'legendary')) {
                window.confetti({
                    particleCount: reward.rarity === 'legendary' ? 170 : 90,
                    spread: 80,
                    startVelocity: 42,
                    origin: { y: 0.62 }
                });
            }
        }

        var settle = null;
        if (reduceMotion) {
            air.classList.add('is-done');
        } else {
            burst = setTimeout(celebrate, AIR_BURST_MS);
            // Once the reward is on screen there is nothing left to skip.
            settle = setTimeout(function () { air.classList.add('is-settled'); }, 2600);
        }

        // Skip jumps to the end state rather than rewinding -- someone opening ten
        // drops should not have to sit through the fall each time.
        $('#rw-air-skip').off('click.air').on('click.air', function () {
            clearTimeout(burst);
            clearTimeout(settle);
            air.classList.add('is-done');
            celebrate();
        });
    }

    function closeAirdrop() {
        var air = document.getElementById('rw-air');
        if (!air) { return; }
        air.hidden = true;
        air.classList.remove('is-playing', 'is-done', 'is-settled');
    }

    $(document).on('click', '#rw-air-close', closeAirdrop);
    $(document).on('click', '#rw-air', function (e) { if (e.target === this) { closeAirdrop(); } });

    /* ----------------------------------------------------------------- spin -- */

    $(document).on('click', '.js-spin', function () {
        var $hub = $(this);
        if ($hub.prop('disabled')) return;
        $hub.prop('disabled', true);

        post(R.spin).done(function (res) {
            if (!res || !res.success) {
                return toast((res && res.message) || TEXT.error);
            }

            var count = parseInt($hub.data('segments'), 10) || 8;
            var slice = 360 / count;
            // Land the chosen segment's middle under the pointer, a little off-centre so
            // it does not look mechanical, after several full turns.
            var jitter = (Math.random() - 0.5) * slice * 0.5;
            var turn = 360 * 6 + (360 - (res.index + 0.5) * slice) + jitter;
            var wheel = document.getElementById('rw-wheel');
            wheel.style.transform = 'rotate(' + turn + 'deg)';

            var done = false;
            function finish() {
                if (done) return;
                done = true;
                $hub.text('✓');
                $('#rw-spin').addClass('is-spent').find('.rw-spin__title').text(TEXT.spent);
                $('#rw-spin-sub').empty().append(document.createTextNode((TEXT.nextSpin || '') + ' '))
                    .append($('<strong class="js-countdown"></strong>'));
                startCountdown($('#rw-spin-sub .js-countdown'), secondsToMidnight());
                applyBalance(res.balance);
                showReveal({
                    icon: res.reward.icon,
                    name: res.reward.label,
                    rarity: res.reward.type === 'drop' ? 'epic' : (res.reward.type === 'freeze' ? 'rare' : 'common')
                });
                if (res.reward.type === 'drop') {
                    // The new drop belongs in the Drops tab.
                    $('#rw-reveal-close').one('click', function () { window.location.hash = '#drops'; window.location.reload(); });
                }
            }
            wheel.addEventListener('transitionend', finish, { once: true });
            setTimeout(finish, reduceMotion ? 50 : 4600);
        }).fail(function (xhr) {
            toast(errorFrom(xhr));
        });
    });

    /* ---------------------------------------------------------------- games -- */

    var stage = {
        $root: $('#rw-stage'),
        $arena: $('#rw-stage-arena'),
        $hud: $('#rw-stage-hud'),
        $timer: $('#rw-stage-timer span'),
        running: null
    };

    function openStage(game) {
        stage.$root.prop('hidden', false);
        $('#rw-stage-title').text((TEXT.games || {})[game] || '');
        stage.$arena.empty();
        stage.$hud.text('');
        stage.$timer.css({ transform: 'scaleX(1)', transition: 'none' });
    }

    function closeStage() {
        if (stage.running && stage.running.stop) stage.running.stop();
        stage.running = null;
        stage.$root.prop('hidden', true);
        stage.$arena.empty();
    }

    $('#rw-stage-close').on('click', closeStage);

    function updatePlaysLeft(game, left) {
        var $chip = $('.js-plays-left[data-game="' + game + '"]');
        $chip.text(left > 0 ? (TEXT.playsLeft || ':count left').replace(':count', left) : TEXT.noPlays);
        $('.js-play[data-game="' + game + '"]').prop('disabled', left <= 0);
    }

    function center(html) {
        return $('<div class="rw-stage__center"></div>').append(html);
    }

    function startGame(game) {
        post(R.games + '/' + game + '/start').done(function (res) {
            if (!res || !res.success) return toast((res && res.message) || TEXT.error);
            updatePlaysLeft(game, res.left);
            openStage(game);
            var run = game === 'tap' ? tapRush : memoryFlip;
            stage.running = run(function (result) {
                submit(game, res.play, res.token, result, res.left);
            });
        }).fail(function (xhr) {
            toast(errorFrom(xhr));
        });
    }

    function submit(game, play, token, result, left) {
        post(R.games + '/' + game + '/finish', { play: play, token: token, result: result }).done(function (res) {
            stage.running = null;
            applyBalance(res.balance);
            updatePlaysLeft(game, res.left);
            var $box = $('<div></div>');
            $box.append($('<div class="rw-stage__big"></div>').text((game === 'tap' ? TEXT.score : TEXT.moves) + ' ' + res.payout.score));
            $box.append($('<div class="rw-stage__payout"></div>').text('+' + res.payout.xp + ' XP · +' + res.payout.sparks + ' ✨'));
            var $again = $('<button type="button" class="dx-btn dx-btn--block"></button>');
            if (res.left > 0) {
                $again.text(TEXT.playAgain + ' (' + res.left + ')').on('click', function () { startGame(game); });
            } else {
                $again.text(TEXT.close).on('click', closeStage);
            }
            $box.append($again);
            stage.$arena.empty().append(center($box));
            if (window.confetti && !reduceMotion && res.payout.sparks >= 10) {
                window.confetti({ particleCount: 70, spread: 60, origin: { y: 0.55 } });
            }
        }).fail(function (xhr) {
            stage.$arena.empty().append(center($('<p></p>').text(errorFrom(xhr))));
        });
    }

    $(document).on('click', '.js-play', function () {
        if ($(this).prop('disabled')) return;
        startGame($(this).data('game'));
    });

    /** Tap Rush: 20 seconds, one target at a time, every hit moves it. */
    function tapRush(done) {
        var DURATION = 20000;
        var score = 0, timer = null, raf = null, stopped = false;
        var $arena = stage.$arena;

        function countdown(n) {
            if (stopped) return;
            $arena.empty().append(center($('<div class="rw-stage__big"></div>').text(n > 0 ? n : TEXT.tapGo)));
            if (n > 0) {
                timer = setTimeout(function () { countdown(n - 1); }, 700);
            } else {
                timer = setTimeout(play, 500);
            }
        }

        function place($t) {
            var w = $arena.width(), h = $arena.height();
            $t.css({ left: 40 + Math.random() * Math.max(1, w - 80), top: 40 + Math.random() * Math.max(1, h - 80) });
        }

        function play() {
            if (stopped) return;
            $arena.empty();
            var $target = $('<button type="button" class="rw-target" aria-label="Target"></button>').appendTo($arena);
            place($target);
            $target.on('pointerdown', function (e) {
                e.preventDefault();
                score++;
                stage.$hud.text(TEXT.score + ' ' + score);
                place($target);
            });
            stage.$hud.text(TEXT.score + ' 0');

            var start = performance.now();
            (function frame(now) {
                if (stopped) return;
                var left = Math.max(0, 1 - (now - start) / DURATION);
                stage.$timer.css('transform', 'scaleX(' + left + ')');
                if (left > 0) {
                    raf = requestAnimationFrame(frame);
                } else {
                    stopped = true;
                    $target.remove();
                    done(score);
                }
            })(start);
        }

        countdown(3);
        return { stop: function () { stopped = true; clearTimeout(timer); cancelAnimationFrame(raf); } };
    }

    /** Memory Flip: 8 pairs, count the moves. */
    function memoryFlip(done) {
        var faces = ['🔥', '💎', '🎧', '🌈', '🍒', '🚀', '👑', '🎲'];
        var deck = faces.concat(faces).sort(function () { return Math.random() - 0.5; });
        var $grid = $('<div class="rw-memory"></div>');
        var open = [], moves = 0, matched = 0, lock = false, stopped = false;

        stage.$hud.text(TEXT.moves + ' 0');
        stage.$timer.css('transform', 'scaleX(0)');

        deck.forEach(function (face) {
            $('<button type="button" class="rw-card"></button>').text(face).data('face', face).appendTo($grid);
        });
        stage.$arena.empty().append($grid);

        $grid.on('click', '.rw-card', function () {
            var $c = $(this);
            if (stopped || lock || $c.hasClass('is-up') || $c.hasClass('is-matched')) return;
            $c.addClass('is-up');
            open.push($c);
            if (open.length < 2) return;

            moves++;
            stage.$hud.text(TEXT.moves + ' ' + moves);
            var a = open[0], b = open[1];
            open = [];
            if (a.data('face') === b.data('face')) {
                a.add(b).removeClass('is-up').addClass('is-matched');
                matched++;
                stage.$timer.css('transform', 'scaleX(' + (matched / faces.length) + ')');
                if (matched === faces.length) {
                    stopped = true;
                    setTimeout(function () { done(moves); }, 400);
                }
            } else {
                lock = true;
                setTimeout(function () { a.add(b).removeClass('is-up'); lock = false; }, 700);
            }
        });

        return { stop: function () { stopped = true; } };
    }

    /* --------------------------------------------------------------- quests -- */

    $(document).on('click', '.js-claim-quest', function () {
        var $btn = $(this);
        var $card = $btn.closest('.rw-quest');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);

        post(R.claim + '/' + $card.data('quest-id') + '/claim').done(function (res) {
            if (!res || !res.success) {
                $btn.prop('disabled', false);
                return toast((res && res.message) || TEXT.error);
            }
            $card.addClass('is-claimed').removeClass('is-ready');
            $btn.replaceWith($('<span class="rw-chip rw-chip--done"></span>').text('✓ ' + (TEXT.claimed || 'Claimed')));
            applyBalance(res.balance);
            if (res.reward && res.reward.swept) {
                $('.rw-sweep').addClass('is-done');
                setTimeout(function () { window.location.hash = '#drops'; window.location.reload(); }, 900);
            }
        }).fail(function (xhr) {
            $btn.prop('disabled', false);
            toast(errorFrom(xhr));
        });
    });

    /* ---------------------------------------------------------------- drops -- */

    $(document).on('click', '.js-open-drop', function () {
        var $drop = $(this);
        if ($drop.prop('disabled')) return;
        $drop.prop('disabled', true);

        post(R.open + '/' + $drop.data('drop-id') + '/open').done(function (res) {
            if (!res || !res.success) {
                $drop.prop('disabled', false);
                return toast((res && res.message) || TEXT.error);
            }
            playAirdrop(res.reward, $drop.find('.rw-drop__icon').text().trim());
            applyBalance(res.balance);
            $drop.fadeOut(220, function () {
                $(this).remove();
                var left = $('#rw-drops .rw-drop').length;
                $('#rw-drop-count').text(left + ' ' + (TEXT.unopened || 'unopened'));
            });
            if (typeof res.pity !== 'undefined') {
                $('.rw-pity__fill').css('width', Math.round((9 - res.pity) / 9 * 100) + '%');
            }
        }).fail(function (xhr) {
            $drop.prop('disabled', false);
            toast(errorFrom(xhr));
        });
    });

    $(document).on('click', '.js-buy-drop', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);

        post(R.buy, { tier: $btn.data('tier') }).done(function (res) {
            if (!res || !res.success) {
                $btn.prop('disabled', false);
                return toast((res && res.message) || TEXT.error);
            }
            window.location.hash = '#drops';
            window.location.reload();
        }).fail(function (xhr) {
            $btn.prop('disabled', false);
            toast(errorFrom(xhr));
        });
    });

    /* ----------------------------------------------------------------- shop -- */

    $(document).on('click', '.js-buy-perk', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        if ($btn.data('confirm') && !window.confirm($btn.data('confirm'))) return;
        $btn.prop('disabled', true);

        post(R.shop + '/' + $btn.data('perk') + '/buy').done(function (res) {
            if (!res || !res.success) {
                $btn.prop('disabled', false);
                return toast((res && res.message) || TEXT.error);
            }
            toast(res.message);
            applyBalance(res.balance);
            $('#rw-credits').text('$' + Number(res.credits).toFixed(2));
            $btn.prop('disabled', false);
            if (String($btn.data('perk')).indexOf('frame_') === 0) {
                setTimeout(function () { window.location.hash = '#shop'; window.location.reload(); }, 900);
            }
        }).fail(function (xhr) {
            $btn.prop('disabled', false);
            toast(errorFrom(xhr));
        });
    });

    $(document).on('click', '.js-equip', function () {
        post(R.frame, { frame: $(this).data('frame') || '' }).done(function () {
            window.location.hash = '#shop';
            window.location.reload();
        }).fail(function (xhr) {
            toast(errorFrom(xhr));
        });
    });

})(jQuery);
