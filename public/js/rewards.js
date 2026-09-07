/* --------------------------------------------------------------------------
   Rewards hub interactions: claim a quest, open a drop, buy a drop.
   Plain jQuery to match the rest of the app -- no build step on this host.
   -------------------------------------------------------------------------- */
(function ($) {
    'use strict';

    if (!window.rwRoutes) {
        return;
    }

    var TEXT = window.rwText || {};
    var csrf = $('meta[name="csrf-token"]').attr('content');

    function post(url, data) {
        return $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            data: data || {},
            dataType: 'json'
        });
    }

    /** Refresh the header numbers from a response, so nothing needs a reload. */
    function applyBalance(b) {
        if (!b) {
            return;
        }
        $('#rw-sparks').text(Number(b.sparks).toLocaleString());
        $('#rw-streak').text(b.streak);
        $('#rw-freezes').text(b.freezes);
        $('#rw-xp-into').text(Number(b.xp % 100).toLocaleString());
        $('.rw-ring').css('--pct', b.percent);
        $('.rw-ring__value').text(b.level);

        // Re-enable or disable the shop buttons against the new balance.
        $('.js-buy-drop').each(function () {
            var cost = parseInt($(this).data('cost'), 10);
            $(this).prop('disabled', Number(b.sparks) < cost);
        });
    }

    function toast(message) {
        // Reuse the celebration toast styling if the partial is on the page,
        // otherwise fall back to alert so a failure is never silent.
        if (window.gmToast) {
            window.gmToast(message);
        } else {
            alert(message);
        }
    }

    /* --------------------------------------------------------------- quests -- */

    $(document).on('click', '.js-claim-quest', function () {
        var $btn = $(this);
        var $card = $btn.closest('.rw-quest');
        var id = $card.data('quest-id');

        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);

        post(window.rwRoutes.claim + '/' + id + '/claim')
            .done(function (res) {
                if (!res || !res.success) {
                    $btn.prop('disabled', false);
                    return toast((res && res.message) || TEXT.error);
                }

                $card.addClass('is-claimed').removeClass('is-ready');
                $btn.replaceWith(
                    $('<span class="rw-chip rw-chip--done"></span>').text('✓ ' + (TEXT.claimed || 'Claimed'))
                );
                applyBalance(res.balance);

                if (res.reward && res.reward.swept) {
                    $('.rw-sweep').addClass('is-done');
                    // The sweep hands out a Gold Drop; reload so it appears in the list.
                    setTimeout(function () { window.location.reload(); }, 900);
                }
            })
            .fail(function (xhr) {
                $btn.prop('disabled', false);
                var msg = TEXT.error;
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toast(msg);
            });
    });

    /* ---------------------------------------------------------------- drops -- */

    function showReveal(reward) {
        var $overlay = $('#rw-reveal');
        $('#rw-reveal-burst').text(reward.icon || '🎁');
        $('#rw-reveal-rarity').text(reward.rarity || '').attr('data-rarity', reward.rarity || 'common');
        $('#rw-reveal-name').text(reward.name || '');
        $overlay.prop('hidden', false);

        // Confetti only for the rarities that earn it -- firing it on every common
        // reward is how a celebration stops registering as one.
        if (window.confetti && (reward.rarity === 'epic' || reward.rarity === 'legendary')) {
            window.confetti({
                particleCount: reward.rarity === 'legendary' ? 160 : 90,
                spread: 75,
                origin: { y: 0.6 }
            });
        }
    }

    $(document).on('click', '#rw-reveal-close', function () {
        $('#rw-reveal').prop('hidden', true);
    });

    $(document).on('click', '#rw-reveal', function (e) {
        if (e.target === this) {
            $('#rw-reveal').prop('hidden', true);
        }
    });

    $(document).on('click', '.js-open-drop', function () {
        var $drop = $(this);
        var id = $drop.data('drop-id');

        if ($drop.prop('disabled')) {
            return;
        }
        $drop.prop('disabled', true);

        post(window.rwRoutes.open + '/' + id + '/open')
            .done(function (res) {
                if (!res || !res.success) {
                    $drop.prop('disabled', false);
                    return toast((res && res.message) || TEXT.error);
                }

                showReveal(res.reward);
                applyBalance(res.balance);

                $drop.fadeOut(220, function () {
                    $(this).remove();
                    var left = $('#rw-drops .rw-drop').length;
                    $('#rw-drop-count').text(left + ' ' + (TEXT.unopened || 'unopened'));
                    if (left === 0 && !$('#rw-drops-empty').length) {
                        window.location.reload();
                    }
                });

                if (typeof res.pity !== 'undefined') {
                    var pct = Math.round((9 - res.pity) / 9 * 100);
                    $('.rw-pity__fill').css('width', pct + '%');
                }
            })
            .fail(function (xhr) {
                $drop.prop('disabled', false);
                var msg = TEXT.error;
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toast(msg);
            });
    });

    /* ----------------------------------------------------------------- shop -- */

    $(document).on('click', '.js-buy-drop', function () {
        var $btn = $(this);
        var tier = $btn.data('tier');

        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);

        post(window.rwRoutes.buy, { tier: tier })
            .done(function (res) {
                if (!res || !res.success) {
                    $btn.prop('disabled', false);
                    return toast((res && res.message) || TEXT.error);
                }
                applyBalance(res.balance);
                // The new drop belongs in the drops list; a reload is the simplest way
                // to render it with the same markup the server produces.
                window.location.reload();
            })
            .fail(function (xhr) {
                $btn.prop('disabled', false);
                var msg = TEXT.error;
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toast(msg);
            });
    });

})(jQuery);
