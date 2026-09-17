/* Buy/sell a coin in dollars: quick amounts, "sell all", and a live summary.
   Everything shown is dollars; the server converts to coin units. */
(function () {
    'use strict';

    var form = document.querySelector('[data-tt-form]');
    if (!form) return;

    var input = form.querySelector('[data-tt-amount]');
    var allFlag = form.querySelector('[data-tt-all]');
    var max = parseFloat(form.dataset.max) || 0;
    var feePct = parseFloat(form.dataset.fee) || 0;
    var presets = form.querySelectorAll('[data-tt-preset], [data-tt-sell-all]');

    var money = function (n) {
        return '$' + (Math.round(n * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    function set(selector, text) {
        form.querySelectorAll(selector).forEach(function (el) { el.textContent = text; });
    }

    function update(fromPreset) {
        var usd = parseFloat(input.value);
        var valid = usd > 0 && (!max || usd <= max + 0.005);
        set('[data-tt-echo]', valid ? money(usd) : '—');
        if (form.querySelector('[data-tt-fee]')) {
            var fee = valid ? usd * feePct / 100 : 0;
            set('[data-tt-fee]', valid ? '-' + money(fee) : '—');
            set('[data-tt-net]', valid ? money(usd - fee) : '—');
        }
        if (!fromPreset) {
            if (allFlag) allFlag.value = '0';
            presets.forEach(function (b) {
                var v = parseFloat(b.dataset.ttPreset || b.dataset.ttSellAll);
                b.classList.toggle('is-active', valid && Math.abs(v - usd) < 0.005 && !b.dataset.ttSellAll);
            });
        }
        input.parentElement.classList.toggle('is-invalid', input.value !== '' && !valid);
    }

    presets.forEach(function (b) {
        b.addEventListener('click', function () {
            var all = !!b.dataset.ttSellAll;
            input.value = parseFloat(b.dataset.ttPreset || b.dataset.ttSellAll).toFixed(2);
            if (allFlag) allFlag.value = all ? '1' : '0';
            presets.forEach(function (o) { o.classList.toggle('is-active', o === b); });
            update(true);
        });
    });

    input.addEventListener('input', function () { update(false); });
    update(false);
})();
