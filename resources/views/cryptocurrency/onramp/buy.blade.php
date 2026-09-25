@extends('layouts.generic')

@section('page_title', __('Buy Tokens'))

@section('styles')
<style>
    .onramp-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #0a0a0a 100%);
        padding: 40px 20px;
    }
    
    .onramp-card {
        max-width: 600px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        overflow: hidden;
        backdrop-filter: blur(20px);
    }
    
    .onramp-header {
        padding: 30px;
        background: linear-gradient(135deg, rgba(131, 8, 102, 0.3), rgba(161, 10, 127, 0.2));
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .onramp-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 10px 0;
    }
    
    .onramp-header p {
        color: rgba(255, 255, 255, 0.7);
        margin: 0;
        font-size: 14px;
    }
    
    .onramp-body {
        padding: 30px;
    }
    
    .token-info {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        margin-bottom: 24px;
    }
    
    .token-logo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #830866, #a10a7f);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: bold;
        color: #fff;
    }
    
    .token-details h3 {
        color: #fff;
        margin: 0 0 4px 0;
        font-size: 18px;
    }
    
    .token-details p {
        color: rgba(255, 255, 255, 0.7);
        margin: 0;
        font-size: 14px;
    }
    
    .token-price {
        margin-left: auto;
        text-align: right;
    }
    
    .token-price .price {
        color: #22c55e;
        font-size: 20px;
        font-weight: 700;
    }
    
    .token-price .label {
        color: rgba(255, 255, 255, 0.5);
        font-size: 12px;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
    }
    
    .amount-input-wrapper {
        position: relative;
    }
    
    .amount-input-wrapper .currency-symbol {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255, 255, 255, 0.7);
        font-size: 24px;
        font-weight: 500;
    }
    
    .amount-input {
        width: 100%;
        padding: 20px 20px 20px 50px;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        color: #fff;
        font-size: 28px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .amount-input:focus {
        outline: none;
        border-color: #830866;
        box-shadow: 0 0 0 4px rgba(131, 8, 102, 0.2);
    }
    
    .quick-amounts {
        display: flex;
        gap: 10px;
        margin-top: 12px;
        flex-wrap: wrap;
    }
    
    .quick-amount-btn {
        flex: 1;
        min-width: 70px;
        padding: 10px 16px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .quick-amount-btn:hover {
        background: rgba(131, 8, 102, 0.3);
        border-color: #830866;
    }
    
    .quote-box {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
    }
    
    .quote-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
    }
    
    .quote-row.total {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: 12px;
        padding-top: 16px;
        color: #fff;
        font-size: 18px;
        font-weight: 600;
    }
    
    .quote-row .value {
        color: #22c55e;
        font-weight: 500;
    }
    
    .quote-row.fee .value {
        color: #ef4444;
    }
    
    .payment-methods {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .payment-method {
        padding: 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .payment-method.selected {
        border-color: #830866;
        background: rgba(131, 8, 102, 0.2);
    }
    
    .payment-method:hover {
        border-color: rgba(131, 8, 102, 0.5);
    }
    
    .payment-method i {
        font-size: 24px;
        color: #fff;
        margin-bottom: 8px;
        display: block;
    }
    
    .payment-method .name {
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 4px;
    }
    
    .payment-method .fee {
        color: rgba(255, 255, 255, 0.5);
        font-size: 12px;
    }
    
    .card-input-container {
        margin-top: 16px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .card-element {
        padding: 16px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .btn-purchase {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #830866, #a10a7f);
        border: none;
        border-radius: 16px;
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-purchase:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(131, 8, 102, 0.4);
    }
    
    .btn-purchase:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .limits-info {
        margin-top: 24px;
        padding: 16px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .limits-info h4 {
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        margin: 0 0 12px 0;
    }
    
    .limit-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 13px;
    }
    
    .security-badges {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 24px;
        flex-wrap: wrap;
    }
    
    .security-badge {
        display: flex;
        align-items: center;
        gap: 6px;
        color: rgba(255, 255, 255, 0.6);
        font-size: 12px;
    }
    
    .security-badge i {
        color: #22c55e;
    }
    
    @media (max-width: 600px) {
        .payment-methods {
            grid-template-columns: 1fr;
        }
        
        .amount-input {
            font-size: 24px;
            padding: 16px 16px 16px 40px;
        }
        
        .amount-input-wrapper .currency-symbol {
            font-size: 20px;
        }
    }
</style>
@endsection

@section('content')
<div class="onramp-container">
    <div class="onramp-card">
        <div class="onramp-header">
            <h1><i class="fas fa-coins"></i> {{ __('Add coins') }}</h1>
            <p>{{ __('Purchase tokens with cryptocurrency - Direct to wallet') }}</p>
        </div>
        
        <div class="onramp-body">
            @if(session('error'))
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); color: #fecaca; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    {{ session('error') }}
                </div>
            @endif
            
            @if($platformToken)
                <!-- Token Info -->
                <div class="token-info">
                    <div class="token-logo">
                        {{ strtoupper(substr($platformToken->name, 0, 1)) }}
                    </div>
                    <div class="token-details">
                        <h3>{{ $platformToken->name }}</h3>
                        <p>{{ __('Priced in dollars') }}</p>
                    </div>
                    <div class="token-price">
                        <div class="price">{{ __('From $5') }}</div>
                    </div>
                </div>
                
                <form action="{{ route('cryptocurrency.onramp.purchase') }}" method="POST" id="purchase-form">
                    @csrf
                    
                    <!-- Amount Input -->
                    <div class="form-group">
                        <label>{{ __('Amount to spend (USD)') }}</label>
                        <div class="amount-input-wrapper">
                            <span class="currency-symbol">$</span>
                            <input 
                                type="number" 
                                name="amount_usd" 
                                id="amount-input"
                                class="amount-input" 
                                placeholder="0.00"
                                min="5"
                                max="{{ $limits['per_transaction'] }}"
                                step="0.01"
                                value="{{ old('amount_usd', 50) }}"
                                required
                            >
                        </div>
                        <div class="quick-amounts">
                            <button type="button" class="quick-amount-btn" data-amount="25">$25</button>
                            <button type="button" class="quick-amount-btn" data-amount="50">$50</button>
                            <button type="button" class="quick-amount-btn" data-amount="100">$100</button>
                            <button type="button" class="quick-amount-btn" data-amount="250">$250</button>
                            <button type="button" class="quick-amount-btn" data-amount="500">$500</button>
                        </div>
                    </div>
                    
                    <!-- Quote Box -->
                    <div class="quote-box">
                        <div class="quote-row">
                            <span>{{ __('Amount') }}</span>
                            <span class="value" id="quote-amount">$0.00</span>
                        </div>
                        <div class="quote-row fee">
                            <span>{{ __('Platform Fee (1%)') }}</span>
                            <span class="value" id="quote-platform-fee">-$0.00</span>
                        </div>
                        <div class="quote-row" id="crypto-payment-row" style="display: none;">
                            <span id="crypto-payment-label">{{ __('You will pay') }}</span>
                            <span class="value" id="quote-crypto-amount">0 BTC</span>
                        </div>
                        <div class="quote-row total">
                            <span>{{ __('You will receive') }}</span>
                            <span class="value" id="quote-tokens">$0.00</span>
                        </div>
                    </div>
                    
                    <!-- Payment Methods (Crypto Only) -->
                    <div class="form-group">
                        <label>{{ __('Pay With Cryptocurrency') }}</label>
                        <div class="payment-methods">
                            @foreach($paymentMethods as $key => $method)
                                <label class="payment-method {{ $loop->first ? 'selected' : '' }}" data-method="{{ $key }}">
                                    <input type="radio" name="payment_crypto" value="{{ $key }}" {{ $loop->first ? 'checked' : '' }} style="display: none;">
                                    <i class="fas {{ $method['icon'] }}"></i>
                                    <div class="name">{{ $method['name'] }}</div>
                                    <div class="symbol">{{ $method['symbol'] }}</div>
                                    <div class="fee">Fee: {{ $method['fee'] }}</div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Crypto Payment Details -->
                    <div class="crypto-payment-container" id="crypto-payment-container">
                        <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                            <label style="display: block; color: #fff; font-size: 14px; margin-bottom: 12px;">
                                <i class="fas fa-coins"></i> {{ __('You will pay') }}
                            </label>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <input 
                                    type="number" 
                                    name="crypto_amount" 
                                    id="crypto-amount"
                                    class="amount-input" 
                                    placeholder="0.00000000"
                                    step="0.00000001"
                                    min="0.00000001"
                                    style="flex: 1; padding: 16px; background: rgba(255, 255, 255, 0.1); border: 2px solid rgba(255, 255, 255, 0.2); border-radius: 12px; color: #fff; font-size: 18px; font-weight: 600;"
                                    readonly
                                >
                                <span id="crypto-symbol" style="color: #22c55e; font-size: 18px; font-weight: 700; min-width: 60px; text-align: center;">BTC</span>
                            </div>
                            <p style="color: rgba(255,255,255,0.6); font-size: 12px; margin: 8px 0 0 0;">
                                {{ __('Crypto will be added to your wallet, then tokens will be distributed') }}
                            </p>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 16px;">
                            <label style="display: block; color: #fff; font-size: 14px; margin-bottom: 8px;">
                                {{ __('Transaction Hash (Optional)') }}
                            </label>
                            <input 
                                type="text" 
                                name="transaction_hash" 
                                id="transaction-hash"
                                placeholder="0x..."
                                style="width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: #fff; font-size: 14px; font-family: monospace;"
                            >
                            <p style="color: rgba(255,255,255,0.5); font-size: 11px; margin: 8px 0 0 0;">
                                {{ __('If you have a transaction hash from your crypto payment, enter it here') }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Purchase Button -->
                    <button type="submit" class="btn-purchase" id="submit-btn">
                        <i class="fas fa-lock"></i>
                        <span id="btn-text">{{ __('Complete Purchase') }}</span>
                    </button>
                </form>
                
                <!-- Limits Info -->
                <div class="limits-info">
                    <h4><i class="fas fa-info-circle"></i> {{ __('Your Transaction Limits') }}</h4>
                    <div class="limit-row">
                        <span>{{ __('Per Transaction') }}</span>
                        <span>${{ number_format($limits['per_transaction'], 2) }}</span>
                    </div>
                    <div class="limit-row">
                        <span>{{ __('Daily Limit') }}</span>
                        <span>${{ number_format($limits['daily'], 2) }}</span>
                    </div>
                    <div class="limit-row">
                        <span>{{ __('Monthly Limit') }}</span>
                        <span>${{ number_format($limits['monthly'], 2) }}</span>
                    </div>
                    @if($user->kyc_level < 3)
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <a href="{{ route('my.settings', ['type' => 'verify']) }}" style="color: #830866; font-size: 13px; text-decoration: none;">
                                <i class="fas fa-arrow-up"></i> {{ __('Upgrade verification for higher limits') }}
                            </a>
                        </div>
                    @endif
                </div>
                
                <!-- Security Badges -->
                <div class="security-badges">
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        <span>{{ __('SSL Encrypted') }}</span>
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>{{ __('PCI Compliant') }}</span>
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ __('Secure Payment') }}</span>
                    </div>
                </div>
            @else
                <div style="text-align: center; padding: 40px; color: rgba(255,255,255,0.7);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px; color: #f59e0b;"></i>
                    <h3 style="color: #fff; margin-bottom: 10px;">{{ __('Token Not Available') }}</h3>
                    <p>{{ __('The platform token is not currently available for purchase. Please try again later.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount-input');
    const quickAmountBtns = document.querySelectorAll('.quick-amount-btn');
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    // Update quote when amount changes
    function updateQuote() {
        const amount = parseFloat(amountInput.value) || 0;
        const selectedCrypto = document.querySelector('input[name="payment_crypto"]:checked')?.value || 'bitcoin';
        const platformFee = amount * 0.01;
        const netAmount = amount - platformFee;
        
        document.getElementById('quote-amount').textContent = '$' + amount.toFixed(2);
        document.getElementById('quote-platform-fee').textContent = '-$' + platformFee.toFixed(2);
        // Dollars only: the coin units are worked out on the server.
        document.getElementById('quote-tokens').textContent = '$' + netAmount.toFixed(2) + ' {{ __('of') }} {{ $platformToken->name ?? '' }}';
        
        // Fetch crypto quote
        if (amount > 0) {
            fetch(`{{ route('cryptocurrency.onramp.quote') }}?amount=${amount}&payment_crypto=${selectedCrypto}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('crypto-amount').value = data.crypto_amount.toFixed(8);
                    document.getElementById('quote-crypto-amount').textContent = data.crypto_amount.toFixed(8) + ' ' + data.payment_crypto.toUpperCase().substring(0, 3);
                    document.getElementById('crypto-symbol').textContent = data.payment_crypto.toUpperCase().substring(0, 3);
                    document.getElementById('crypto-payment-row').style.display = 'flex';
                    document.getElementById('crypto-payment-label').textContent = 'You will pay (' + data.payment_crypto.toUpperCase() + ')';
                }
            })
            .catch(error => console.error('Error fetching quote:', error));
        } else {
            document.getElementById('crypto-amount').value = '';
            document.getElementById('crypto-payment-row').style.display = 'none';
        }
    }
    
    amountInput.addEventListener('input', updateQuote);
    updateQuote(); // Initial update
    
    // Quick amount buttons
    quickAmountBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            amountInput.value = this.dataset.amount;
            updateQuote();
        });
    });
    
    // Payment method selection
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            paymentMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
            
            // Update crypto symbol and quote
            const selectedCrypto = this.dataset.method;
            const methodData = {{ json_encode($paymentMethods) }};
            if (methodData[selectedCrypto]) {
                document.getElementById('crypto-symbol').textContent = methodData[selectedCrypto].symbol;
            }
            updateQuote();
        });
    });
    
    // Form submission
    document.getElementById('purchase-form').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        
        submitBtn.disabled = true;
        btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Processing...") }}';
    });
});
</script>
@endsection
