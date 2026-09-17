@php
    /**
     * Unlock-price field shared by the upload and edit pages.
     * $value   - current price (string|float|null)
     * $visible - whether "Paid" is the selected visibility
     *
     * Keeps id="priceField" / id="price" / name="price", which the pages' visibility
     * toggle and VideoController (min 0.5, max 500) depend on.
     */
    $symbol = getSetting('payments.currency_symbol') ?: '$';
    $min = 0.5;
    $max = 500;
    $presets = [1.99, 4.99, 9.99, 19.99, 49.99];
    // The only fee between an unlock and the creator's bank is the withdrawal fee, when enabled.
    $fee = getSetting('payments.withdrawal_allow_fees') ? (float) getSetting('payments.withdrawal_default_fee_percentage') : 0;
    $current = is_numeric($value) ? (float) $value : null;
    $money = fn ($n) => $symbol . number_format($n, $n >= 1000 ? 0 : 2);
@endphp
<div class="vu-field vu-price {{ $visible ? '' : 'd-none' }}" id="priceField"
     data-vu-price data-symbol="{{ $symbol }}" data-fee="{{ $fee }}">
    <div class="vu-label-row">
        <label for="price" class="vu-label">{{ __('Unlock price') }}<span class="vu-req">*</span></label>
        <span class="vu-count">{{ __('One-off') }} · {{ $money($min) }}–{{ $symbol }}{{ number_format($max) }}</span>
    </div>

    <div class="vu-price__box @error('price') is-invalid @enderror">
        <span class="vu-price__symbol" aria-hidden="true">{{ $symbol }}</span>
        <input type="number" inputmode="decimal" class="vu-price__input" id="price" name="price"
               step="0.01" min="{{ $min }}" max="{{ $max }}"
               value="{{ $value }}" placeholder="4.99" aria-describedby="priceHelp">
    </div>

    <div class="vu-price__presets" role="group" aria-label="{{ __('Suggested prices') }}">
        @foreach($presets as $p)
            <button type="button" class="vu-price__preset {{ $current !== null && abs($current - $p) < 0.001 ? 'is-active' : '' }}"
                    data-price="{{ number_format($p, 2, '.', '') }}">{{ $money($p) }}</button>
        @endforeach
    </div>

    <div class="vu-price__earn" aria-live="polite">
        <span class="vu-price__earn-title">{{ __('What you could make') }}</span>
        <div class="vu-price__earn-grid">
            @foreach([10, 100, 1000] as $n)
                <div class="vu-price__earn-cell">
                    <strong data-earn="{{ $n }}">{{ $current ? $money($current * $n * (1 - $fee / 100)) : '—' }}</strong>
                    <span>{{ number_format($n) }} {{ __('unlocks') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <p class="vu-hint" id="priceHelp">
        {{ __('Fans pay once to watch.') }}
        @if($fee > 0)
            {{ __('Examples are after the :fee% withdrawal fee.', ['fee' => rtrim(rtrim(number_format($fee, 2), '0'), '.')]) }}
        @else
            {{ __('Examples only — what you actually make depends on how many fans unlock it.') }}
        @endif
    </p>
    @error('price')<span class="vu-error-text">{{ $message }}</span>@enderror
</div>

@once
<script>
/* Presets fill the input; the earnings row follows whatever price is typed. */
document.addEventListener('DOMContentLoaded', function () {
    var field = document.querySelector('[data-vu-price]');
    if (!field) return;
    var input = field.querySelector('#price');
    var presets = field.querySelectorAll('.vu-price__preset');
    var symbol = field.dataset.symbol || '$';
    var keep = 1 - (parseFloat(field.dataset.fee) || 0) / 100;

    var fmt = function (n) {
        return symbol + n.toLocaleString(undefined, n >= 1000
            ? { maximumFractionDigits: 0 }
            : { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    var update = function () {
        var price = parseFloat(input.value);
        var valid = price >= parseFloat(input.min) && price <= parseFloat(input.max);
        field.querySelectorAll('[data-earn]').forEach(function (el) {
            el.textContent = valid ? fmt(price * parseInt(el.dataset.earn, 10) * keep) : '—';
        });
        presets.forEach(function (b) {
            b.classList.toggle('is-active', valid && Math.abs(parseFloat(b.dataset.price) - price) < 0.001);
        });
        field.querySelector('.vu-price__box').classList.toggle('is-out-of-range', input.value !== '' && !valid);
    };

    presets.forEach(function (b) {
        b.addEventListener('click', function () {
            input.value = b.dataset.price;
            update();
        });
    });
    input.addEventListener('input', update);

    // The page's visibility toggle clears the value when leaving "Paid"; keep the row in step.
    document.querySelectorAll('input[name="visibility"]').forEach(function (r) {
        r.addEventListener('change', function () { setTimeout(update, 0); });
    });
    update();
});
</script>
@endonce
