@extends('layouts.generic')

@section('page_title')
    {{ __('Create New Token') }}
@endsection

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    // Fallbacks so the form still renders every option if the controller
    // does not supply these (keeps parity with the previous hard-coded lists).
    $blockchainNetworks = $blockchainNetworks ?? [
        'ethereum'  => 'Ethereum (ETH) - Most Popular',
        'binance'   => 'Binance Smart Chain (BSC) - Low Fees',
        'polygon'   => 'Polygon (MATIC) - Fast & Cheap',
        'solana'    => 'Solana (SOL) - High Performance',
        'avalanche' => 'Avalanche (AVAX) - Fast Finality',
    ];

    $tokenTypes = $tokenTypes ?? [
        'utility'    => 'Utility Token - Platform usage',
        'security'   => 'Security Token - Investment',
        'governance' => 'Governance Token - Voting rights',
        'payment'    => 'Payment Token - Currency',
        'nft'        => 'NFT Collection - Collectibles',
        'defi'       => 'DeFi Token - Finance',
    ];

    $defaults = $defaults ?? [];
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/token-create.css') }}?v=20260715a">
@endsection

@section('content')
<div class="tc-page {{ $isDarkTheme ? 'tc-page--dark' : 'tc-page--light' }}">
<div class="tc-container">

    <header class="tc-header">
        <div>
            <h1 class="tc-header__title">{{ __('Create New Token') }}</h1>
            <p class="tc-header__sub">{{ __('Launch your own cryptocurrency token on the blockchain') }}</p>
        </div>
        <a href="{{ route('cryptocurrency.index') }}" class="tc-back">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ __('Cancel') }}
        </a>
    </header>

    @if (session('success'))
        <div class="tc-alert tc-alert--ok">
            <div class="tc-alert__icon">
                <svg class="tc-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="tc-alert__body">
                <strong>{{ __('Success!') }}</strong>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="tc-alert tc-alert--danger">
            <div class="tc-alert__icon">
                <svg class="tc-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="tc-alert__body">
                <strong>{{ __('Error!') }}</strong>
                <p>{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="tc-alert tc-alert--danger">
            <div class="tc-alert__icon">
                <svg class="tc-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="tc-alert__body">
                <strong>{{ __('Please fix the following errors:') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="tc-grid">
        <form class="tc-main" action="{{ route('cryptocurrency.store') }}" method="POST" enctype="multipart/form-data" id="tokenForm">
            @csrf

            {{-- Basic Information --}}
            <section class="tc-card">
                <h2 class="tc-card__title">
                    <svg class="tc-ic" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    {{ __('Basic Information') }}
                </h2>

                <div class="tc-row">
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="name">{{ __('Token Name') }}<span class="tc-req">*</span></label>
                        </div>
                        <input type="text" name="name" id="name" class="tc-input @error('name') is-invalid @enderror"
                               placeholder="{{ __('e.g., Bitcoin, Ethereum') }}" value="{{ old('name') }}" required>
                        <span class="tc-hint">{{ __('The full name of your cryptocurrency') }}</span>
                        @error('name')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="symbol">{{ __('Token Symbol') }}<span class="tc-req">*</span></label>
                        </div>
                        <input type="text" name="symbol" id="symbol" class="tc-input @error('symbol') is-invalid @enderror"
                               placeholder="{{ __('e.g., BTC, ETH') }}" value="{{ old('symbol') }}" maxlength="10" required>
                        <span class="tc-hint">{{ __('3-10 characters, uppercase only') }}</span>
                        @error('symbol')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="tc-field">
                    <div class="tc-label-row">
                        <label class="tc-label" for="description">{{ __('Description') }}<span class="tc-req">*</span></label>
                        <span class="tc-count" id="charCount">0/2000</span>
                    </div>
                    <textarea name="description" id="description" rows="4" class="tc-textarea @error('description') is-invalid @enderror"
                              placeholder="{{ __('Describe your token\'s purpose and unique features...') }}" required maxlength="2000">{{ old('description') }}</textarea>
                    <span class="tc-hint">{{ __('Minimum 50 characters') }}</span>
                    @error('description')
                        <span class="tc-hint tc-hint--error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tc-row">
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <span class="tc-label">{{ __('Token Logo') }} <span class="tc-optional">({{ __('Optional') }})</span></span>
                        </div>
                        <div class="tc-logo" id="logoUploadArea">
                            <input type="file" name="logo" id="logoInput" class="d-none" accept="image/*">
                            <div class="tc-logo__content">
                                <div class="tc-logo__preview">
                                    <div id="logoPreviewPlaceholder" class="tc-logo__placeholder">
                                        <svg class="tc-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M8 10h8M8 14h8"/></svg>
                                    </div>
                                    <img id="logoPreview" src="" alt="{{ __('Logo Preview') }}" class="tc-logo__img d-none">
                                    <div class="tc-logo__overlay d-none" id="logoOverlay">
                                        <svg class="tc-ic" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    </div>
                                </div>
                                <div class="tc-logo__info">
                                    <button type="button" class="tc-logo__btn" onclick="document.getElementById('logoInput').click()">
                                        <svg class="tc-ic" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        {{ __('Upload Logo') }}
                                    </button>
                                    <span class="tc-hint">{{ __('Max 2MB, JPG/PNG/GIF/SVG') }}</span>
                                    <span class="tc-logo__filename d-none" id="logoFileName"></span>
                                </div>
                            </div>
                            <div class="tc-logo__drop">
                                <svg class="tc-ic" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <p>{{ __('Drag & drop your logo here') }}</p>
                                <span class="tc-hint">{{ __('or click to browse') }}</span>
                            </div>
                        </div>
                        @error('logo')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="website">{{ __('Website URL') }}</label>
                        </div>
                        <input type="url" name="website" id="website" class="tc-input @error('website') is-invalid @enderror"
                               placeholder="https://yourtoken.com" value="{{ old('website') }}">
                        @error('website')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="tc-field">
                    <div class="tc-label-row">
                        <label class="tc-label" for="whitepaper">{{ __('Whitepaper URL') }}</label>
                    </div>
                    <input type="url" name="whitepaper" id="whitepaper" class="tc-input @error('whitepaper') is-invalid @enderror"
                           placeholder="https://yourtoken.com/whitepaper.pdf" value="{{ old('whitepaper') }}">
                    @error('whitepaper')
                        <span class="tc-hint tc-hint--error">{{ $message }}</span>
                    @enderror
                </div>
            </section>

            {{-- Token Configuration --}}
            <section class="tc-card">
                <h2 class="tc-card__title">
                    <svg class="tc-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    {{ __('Token Configuration') }}
                </h2>

                <div class="tc-row">
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="blockchain_network">{{ __('Blockchain Network') }}<span class="tc-req">*</span></label>
                        </div>
                        <select name="blockchain_network" id="blockchain_network" class="tc-select @error('blockchain_network') is-invalid @enderror" required>
                            @foreach($blockchainNetworks as $value => $label)
                                <option value="{{ $value }}" @selected(old('blockchain_network', $defaults['blockchain_network'] ?? 'ethereum') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('blockchain_network')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="token_type">{{ __('Token Type') }}<span class="tc-req">*</span></label>
                        </div>
                        <select name="token_type" id="token_type" class="tc-select @error('token_type') is-invalid @enderror" required>
                            @foreach($tokenTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('token_type', $defaults['token_type'] ?? 'utility') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('token_type')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- Supply & Economics --}}
            <section class="tc-card">
                <h2 class="tc-card__title">
                    <svg class="tc-ic" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    {{ __('Supply & Economics') }}
                </h2>

                <div class="tc-row">
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="initialPrice">{{ __('Initial Price (USD)') }}<span class="tc-req">*</span></label>
                        </div>
                        <div class="tc-input-group">
                            <span class="tc-input-group__affix tc-input-group__affix--start">$</span>
                            <input type="number" name="initial_price" id="initialPrice" class="tc-input @error('initial_price') is-invalid @enderror"
                                   value="{{ old('initial_price', $defaults['initial_price'] ?? '0.001') }}" step="0.00000001" min="0.00000001" required>
                        </div>
                        @error('initial_price')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="totalSupply">{{ __('Total Supply') }}<span class="tc-req">*</span></label>
                        </div>
                        <input type="number" name="total_supply" id="totalSupply" class="tc-input @error('total_supply') is-invalid @enderror"
                               value="{{ old('total_supply', $defaults['total_supply'] ?? '1000000') }}" min="1" required>
                        @error('total_supply')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="tc-row">
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="creatorAllocation">{{ __('Creator Allocation') }}</label>
                        </div>
                        <input type="number" name="creator_allocation" id="creatorAllocation" class="tc-input @error('creator_allocation') is-invalid @enderror"
                               value="{{ old('creator_allocation', $defaults['creator_allocation'] ?? '100000') }}" min="0">
                        <span class="tc-hint">{{ __('Tokens you will receive initially') }}</span>
                        @error('creator_allocation')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="marketCap">{{ __('Market Cap') }}</label>
                        </div>
                        <div class="tc-input-group">
                            <span class="tc-input-group__affix tc-input-group__affix--start">$</span>
                            <input type="text" id="marketCap" class="tc-input" readonly>
                        </div>
                        <span class="tc-hint">{{ __('Total Supply × Initial Price') }}</span>
                    </div>
                </div>
            </section>

            {{-- Fee Structure --}}
            <section class="tc-card">
                <h2 class="tc-card__title">
                    <svg class="tc-ic" viewBox="0 0 24 24"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                    {{ __('Fee Structure') }}
                </h2>

                <div class="tc-row tc-row--3">
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="creator_fee_percentage">{{ __('Creator Fee') }}</label>
                        </div>
                        <div class="tc-input-group">
                            <input type="number" name="creator_fee_percentage" id="creator_fee_percentage" class="tc-input @error('creator_fee_percentage') is-invalid @enderror"
                                   value="{{ old('creator_fee_percentage', $defaults['creator_fee_percentage'] ?? '5') }}" step="0.01" min="0" max="20">
                            <span class="tc-input-group__affix tc-input-group__affix--end">%</span>
                        </div>
                        @error('creator_fee_percentage')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label">{{ __('Platform Fee') }}</label>
                        </div>
                        <div class="tc-input-group">
                            <input type="text" class="tc-input" value="{{ number_format($defaults['platform_fee_percentage'] ?? 2.5, 2) }}" readonly>
                            <span class="tc-input-group__affix tc-input-group__affix--end">%</span>
                        </div>
                    </div>
                    <div class="tc-field">
                        <div class="tc-label-row">
                            <label class="tc-label" for="liquidity_pool_percentage">{{ __('Liquidity Pool') }}</label>
                        </div>
                        <div class="tc-input-group">
                            <input type="number" name="liquidity_pool_percentage" id="liquidity_pool_percentage" class="tc-input @error('liquidity_pool_percentage') is-invalid @enderror"
                                   value="{{ old('liquidity_pool_percentage', $defaults['liquidity_pool_percentage'] ?? '20') }}" step="0.01" min="0" max="100">
                            <span class="tc-input-group__affix tc-input-group__affix--end">%</span>
                        </div>
                        @error('liquidity_pool_percentage')
                            <span class="tc-hint tc-hint--error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- Token Features --}}
            <section class="tc-card">
                <h2 class="tc-card__title">
                    <svg class="tc-ic" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    {{ __('Token Features') }}
                </h2>

                <div class="tc-features">
                    <div class="tc-feature" data-feature>
                        <div class="tc-feature__row">
                            <input type="checkbox" name="enable_burning" value="1" id="burning" @checked(old('enable_burning', $defaults['enable_burning'] ?? false))>
                            <label class="tc-feature__label" for="burning">
                                <svg class="tc-ic" viewBox="0 0 24 24"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                                {{ __('Token Burning') }}
                            </label>
                        </div>
                        <span class="tc-feature__hint">{{ __('Allow permanent token destruction') }}</span>
                    </div>
                    <div class="tc-feature" data-feature>
                        <div class="tc-feature__row">
                            <input type="checkbox" name="enable_minting" value="1" id="minting" @checked(old('enable_minting', $defaults['enable_minting'] ?? false))>
                            <label class="tc-feature__label" for="minting">
                                <svg class="tc-ic" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                {{ __('Token Minting') }}
                            </label>
                        </div>
                        <span class="tc-feature__hint">{{ __('Allow new token creation') }}</span>
                    </div>
                    <div class="tc-feature" data-feature>
                        <div class="tc-feature__row">
                            <input type="checkbox" name="transferable" value="1" id="transfers" @checked(old('transferable', $defaults['transferable'] ?? true))>
                            <label class="tc-feature__label" for="transfers">
                                <svg class="tc-ic" viewBox="0 0 24 24"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                                {{ __('Allow Transfers') }}
                            </label>
                        </div>
                        <span class="tc-feature__hint">{{ __('Enable token transfers') }}</span>
                    </div>
                </div>
            </section>

            {{-- Terms & Submit --}}
            <section class="tc-card">
                <div class="tc-terms">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">
                        {{ __('I agree to the') }} <a href="#">{{ __('Terms of Service') }}</a>
                        {{ __('and understand the risks involved in token creation.') }}
                    </label>
                </div>
                <div class="tc-actions">
                    <button type="submit" class="tc-btn tc-btn--primary" id="submitBtn">
                        <span id="submitSpinner" class="tc-spinner d-none" aria-hidden="true"></span>
                        <svg class="tc-ic" viewBox="0 0 24 24" id="submitIcon"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
                        <span id="submitLabel">{{ __('Create Token') }}</span>
                    </button>
                    <a href="{{ route('cryptocurrency.index') }}" class="tc-btn tc-btn--ghost">{{ __('Cancel') }}</a>
                </div>
            </section>
        </form>

        <aside class="tc-side">
            @if(isset($userTokenCount) && isset($maxTokensPerUser))
            <div class="tc-side-card">
                <h3 class="tc-side-card__title">{{ __('Token Limit') }}</h3>
                <div class="tc-limit__row">
                    <span>{{ __('Created') }}</span>
                    <strong>{{ $userTokenCount }}/{{ $maxTokensPerUser }}</strong>
                </div>
                <div class="tc-progress">
                    <div class="tc-progress__bar" style="width: {{ $maxTokensPerUser > 0 ? min(100, ($userTokenCount / $maxTokensPerUser) * 100) : 0 }}%"></div>
                </div>
                <span class="tc-hint">{{ __(':count tokens remaining', ['count' => max(0, $maxTokensPerUser - $userTokenCount)]) }}</span>
            </div>
            @endif

            <div class="tc-side-card">
                <h3 class="tc-side-card__title">{{ __('Summary') }}</h3>
                <div class="tc-summary">
                    <div class="tc-summary__item">
                        <div class="tc-summary__value" id="summaryMarketCap">$0.00</div>
                        <div class="tc-summary__label">{{ __('Market Cap') }}</div>
                    </div>
                    <div class="tc-summary__item">
                        <div class="tc-summary__value" id="summaryAllocation">0</div>
                        <div class="tc-summary__label">{{ __('Your Tokens') }}</div>
                    </div>
                    <div class="tc-summary__item">
                        <div class="tc-summary__value" id="summaryAvailable">0</div>
                        <div class="tc-summary__label">{{ __('For Sale') }}</div>
                    </div>
                    <div class="tc-summary__item">
                        <div class="tc-summary__value" id="summaryTotal">0</div>
                        <div class="tc-summary__label">{{ __('Total Supply') }}</div>
                    </div>
                </div>
            </div>

            <div class="tc-side-card">
                <h3 class="tc-side-card__title">{{ __('Information') }}</h3>
                <p class="tc-side-card__intro">{{ __('After creating your token:') }}</p>
                <ul class="tc-info-list">
                    <li>
                        <svg class="tc-ic" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        {{ __('List on marketplace') }}
                    </li>
                    <li>
                        <svg class="tc-ic" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        {{ __('Build community') }}
                    </li>
                    <li>
                        <svg class="tc-ic" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        {{ __('Track performance') }}
                    </li>
                    <li>
                        <svg class="tc-ic" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        {{ __('Manage economics') }}
                    </li>
                </ul>
                <div class="tc-tip">
                    <strong>{{ __('Tip:') }}</strong> {{ __('A clear description attracts more users!') }}
                </div>
            </div>
        </aside>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    const symbolInput = document.getElementById('symbol');
    const descriptionInput = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const initialPrice = document.getElementById('initialPrice');
    const totalSupply = document.getElementById('totalSupply');
    const creatorAllocation = document.getElementById('creatorAllocation');
    const marketCap = document.getElementById('marketCap');
    const form = document.getElementById('tokenForm');

    // Summary elements
    const summaryMarketCap = document.getElementById('summaryMarketCap');
    const summaryAllocation = document.getElementById('summaryAllocation');
    const summaryAvailable = document.getElementById('summaryAvailable');
    const summaryTotal = document.getElementById('summaryTotal');

    // Logo upload area elements
    const logoUploadArea = document.getElementById('logoUploadArea');
    const logoPreviewPlaceholder = document.getElementById('logoPreviewPlaceholder');
    const logoOverlay = document.getElementById('logoOverlay');
    const logoFileName = document.getElementById('logoFileName');

    // Logo preview functionality
    function handleLogoPreview(file) {
        if (file) {
            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert({!! json_encode(__('File size must be less than 2MB')) !!});
                logoInput.value = '';
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
            if (!validTypes.includes(file.type)) {
                alert({!! json_encode(__('Please upload a valid image file (JPG, PNG, GIF, or SVG)')) !!});
                logoInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.src = e.target.result;
                logoPreview.classList.remove('d-none');
                logoOverlay.classList.remove('d-none');
                if (logoPreviewPlaceholder) logoPreviewPlaceholder.style.display = 'none';
                if (logoFileName) {
                    logoFileName.textContent = file.name;
                    logoFileName.classList.remove('d-none');
                }
                logoUploadArea.classList.add('has-logo');
            };
            reader.readAsDataURL(file);
        } else {
            logoPreview.classList.add('d-none');
            logoOverlay.classList.add('d-none');
            if (logoPreviewPlaceholder) logoPreviewPlaceholder.style.display = 'flex';
            if (logoFileName) logoFileName.classList.add('d-none');
            logoUploadArea.classList.remove('has-logo');
        }
    }

    // File input change
    logoInput.addEventListener('change', function() {
        handleLogoPreview(this.files[0]);
    });

    // Click to upload
    logoUploadArea.addEventListener('click', function(e) {
        if (!e.target.closest('.tc-logo__overlay') && !e.target.closest('.tc-logo__btn')) {
            logoInput.click();
        }
    });

    // Drag and drop functionality
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
        logoUploadArea.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(function(eventName) {
        logoUploadArea.addEventListener(eventName, function() {
            logoUploadArea.classList.add('drag-over');
        }, false);
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
        logoUploadArea.addEventListener(eventName, function() {
            logoUploadArea.classList.remove('drag-over');
        }, false);
    });

    logoUploadArea.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            logoInput.files = files;
            handleLogoPreview(files[0]);
        }
    }, false);

    // Remove logo functionality
    logoOverlay.addEventListener('click', function(e) {
        e.stopPropagation();
        if (confirm({!! json_encode(__('Remove logo?')) !!})) {
            logoInput.value = '';
            handleLogoPreview(null);
        }
    });

    // Symbol uppercase
    symbolInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Character count with validation
    descriptionInput.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length + '/2000';
        charCount.classList.remove('is-danger', 'is-warning', 'is-ok');
        this.classList.remove('is-valid', 'is-invalid');
        if (length < 50) {
            charCount.classList.add('is-danger');
            this.classList.add('is-invalid');
        } else if (length > 1800) {
            charCount.classList.add('is-warning');
            this.classList.add('is-valid');
        } else {
            charCount.classList.add('is-ok');
            this.classList.add('is-valid');
        }
    });

    // Real-time validation for required fields
    document.querySelectorAll('#tokenForm input[required], #tokenForm textarea[required], #tokenForm select[required]').forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Calculate values
    function updateCalculations() {
        const price = parseFloat(initialPrice.value) || 0;
        const supply = parseFloat(totalSupply.value) || 0;
        const allocation = parseFloat(creatorAllocation.value) || 0;
        const available = supply - allocation;
        const cap = price * supply;
        marketCap.value = cap.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        summaryMarketCap.textContent = '$' + cap.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        summaryAllocation.textContent = allocation.toLocaleString();
        summaryAvailable.textContent = available.toLocaleString();
        summaryTotal.textContent = supply.toLocaleString();
    }

    initialPrice.addEventListener('input', updateCalculations);
    totalSupply.addEventListener('input', updateCalculations);
    creatorAllocation.addEventListener('input', updateCalculations);

    // Feature options
    document.querySelectorAll('[data-feature]').forEach(function(option) {
        const checkbox = option.querySelector('input[type="checkbox"]');
        option.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox' && e.target.tagName !== 'LABEL' && !e.target.closest('label')) {
                checkbox.checked = !checkbox.checked;
            }
            option.classList.toggle('is-selected', checkbox.checked);
        });
        checkbox.addEventListener('change', function() {
            option.classList.toggle('is-selected', this.checked);
        });
        // Initialize
        option.classList.toggle('is-selected', checkbox.checked);
    });

    // Form submission with enhanced feedback
    form.addEventListener('submit', function(e) {
        // Validate description length
        if (descriptionInput.value.length < 50) {
            e.preventDefault();
            alert({!! json_encode(__('Description must be at least 50 characters long.')) !!});
            descriptionInput.focus();
            return false;
        }

        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('submitSpinner');
        const icon = document.getElementById('submitIcon');
        const label = document.getElementById('submitLabel');
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        if (label) label.textContent = {!! json_encode(__('Creating Token...')) !!};

        // Animate button
        submitBtn.style.transform = 'scale(0.98)';
        setTimeout(function() {
            submitBtn.style.transform = '';
        }, 100);
    });

    // Add smooth scroll to errors
    const dangerAlert = document.querySelector('.tc-alert--danger');
    if (dangerAlert) {
        setTimeout(function() {
            dangerAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }

    // Add tooltips if Bootstrap tooltips are available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Initialize
    updateCalculations();
    descriptionInput.dispatchEvent(new Event('input'));
});
</script>
@endsection
