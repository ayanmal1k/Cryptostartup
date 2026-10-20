@extends('voyager::master')

@section('page_title', 'Create Token')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-plus"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Create Token</h1>
                    <p class="jf-dash-page-header__desc">Add a new cryptocurrency to the platform registry</p>
                </div>
            </div>
            <div class="jf-dash-page-header__actions">
                <a href="{{ route('voyager.tokens.index') }}" class="jf-dash-btn jf-dash-btn--amber">
                    <i class="voyager-list"></i>
                    <span class="jf-pill-label">Back to Tokens</span>
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content edit-add container-fluid jf-dash-page jf-tokens-form-page">
        @include('voyager::alerts')

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-tokens-form-card">
                    <div class="panel-body jf-dash-card__body">
                        <form role="form"
                              action="{{ route('voyager.tokens.store') }}"
                              method="POST"
                              enctype="multipart/form-data"
                              class="jf-tokens-form">
                            @csrf

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="panel panel-bordered jf-dash-card jf-tokens-form-section">
                                        <div class="panel-heading jf-dash-card__head">
                                            <h3 class="panel-title jf-dash-card__title">
                                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-edit"></i></span>
                                                <span>Basic Information</span>
                                            </h3>
                                        </div>
                                        <div class="panel-body jf-dash-card__body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Token Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Symbol <span class="text-danger">*</span></label>
                                                        <input type="text" name="symbol" class="form-control" value="{{ old('symbol') }}" maxlength="10" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Website</label>
                                                        <input type="url" name="website" class="form-control" value="{{ old('website') }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Whitepaper URL</label>
                                                        <input type="url" name="whitepaper" class="form-control" value="{{ old('whitepaper') }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-bordered jf-dash-card jf-tokens-form-section">
                                        <div class="panel-heading jf-dash-card__head">
                                            <h3 class="panel-title jf-dash-card__title">
                                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--green"><i class="voyager-dollar"></i></span>
                                                <span>Price &amp; Market Data</span>
                                            </h3>
                                        </div>
                                        <div class="panel-body jf-dash-card__body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Initial Price <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-addon">$</span>
                                                            <input type="number" name="initial_price" class="form-control" value="{{ old('initial_price', 0) }}" step="0.00000001" min="0" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Current Price <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-addon">$</span>
                                                            <input type="number" name="current_price" class="form-control" value="{{ old('current_price', old('initial_price', 0)) }}" step="0.00000001" min="0" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Market Cap</label>
                                                        <div class="input-group">
                                                            <span class="input-group-addon">$</span>
                                                            <input type="number" name="market_cap" class="form-control" value="{{ old('market_cap', 0) }}" step="0.01" min="0">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>24h Volume</label>
                                                        <div class="input-group">
                                                            <span class="input-group-addon">$</span>
                                                            <input type="number" name="volume_24h" class="form-control" value="{{ old('volume_24h', 0) }}" step="0.01" min="0">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-bordered jf-dash-card jf-tokens-form-section">
                                        <div class="panel-heading jf-dash-card__head">
                                            <h3 class="panel-title jf-dash-card__title">
                                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--amber"><i class="voyager-data"></i></span>
                                                <span>Supply Information</span>
                                            </h3>
                                        </div>
                                        <div class="panel-body jf-dash-card__body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Total Supply <span class="text-danger">*</span></label>
                                                        <input type="number" name="total_supply" class="form-control" value="{{ old('total_supply', 0) }}" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Available Supply <span class="text-danger">*</span></label>
                                                        <input type="number" name="available_supply" class="form-control" value="{{ old('available_supply', 0) }}" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Circulating Supply <span class="text-danger">*</span></label>
                                                        <input type="number" name="circulating_supply" class="form-control" value="{{ old('circulating_supply', 0) }}" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Max Supply</label>
                                                        <input type="number" name="max_supply" class="form-control" value="{{ old('max_supply') }}" step="0.01" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-bordered jf-dash-card jf-tokens-form-section">
                                        <div class="panel-heading jf-dash-card__head">
                                            <h3 class="panel-title jf-dash-card__title">
                                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--purple"><i class="voyager-world"></i></span>
                                                <span>Blockchain &amp; Contract</span>
                                            </h3>
                                        </div>
                                        <div class="panel-body jf-dash-card__body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Blockchain Network <span class="text-danger">*</span></label>
                                                        <select name="blockchain_network" class="form-control" required>
                                                            @foreach($networks as $key => $value)
                                                                <option value="{{ $key }}" {{ old('blockchain_network', 'ETH') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Token Type <span class="text-danger">*</span></label>
                                                        <select name="token_type" class="form-control" required>
                                                            @foreach($tokenTypes as $key => $value)
                                                                <option value="{{ $key }}" {{ old('token_type', 'utility') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Contract Address</label>
                                                <input type="text" name="contract_address" class="form-control" value="{{ old('contract_address') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="panel panel-bordered jf-dash-card jf-tokens-form-section">
                                        <div class="panel-heading jf-dash-card__head">
                                            <h3 class="panel-title jf-dash-card__title">
                                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--rose"><i class="voyager-upload"></i></span>
                                                <span>Logo &amp; Settings</span>
                                            </h3>
                                        </div>
                                        <div class="panel-body jf-dash-card__body">
                                            <div class="form-group">
                                                <label>Upload Logo</label>
                                                <div class="jf-file-upload">
                                                    <input type="file" name="logo" id="token-logo-upload" class="jf-file-upload__input" accept="image/*">
                                                    <label for="token-logo-upload" class="jf-file-upload__btn">
                                                        <i class="voyager-upload"></i>
                                                        <span>Choose File</span>
                                                    </label>
                                                    <span class="jf-file-upload__name" data-placeholder="No file chosen">No file chosen</span>
                                                </div>
                                                <small class="help-block">JPG, PNG, GIF, SVG. Max 2MB.</small>
                                            </div>

                                            <div class="form-group">
                                                <label>Creator</label>
                                                <select name="creator_user_id" class="form-control">
                                                    <option value="">Select Creator</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}" {{ old('creator_user_id') == $user->id ? 'selected' : '' }}>
                                                            {{ $user->name }} ({{ $user->email }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-bordered jf-dash-card jf-tokens-form-section">
                                        <div class="panel-heading jf-dash-card__head">
                                            <h3 class="panel-title jf-dash-card__title">
                                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-receipt"></i></span>
                                                <span>Fee Structure</span>
                                            </h3>
                                        </div>
                                        <div class="panel-body jf-dash-card__body">
                                            <div class="form-group">
                                                <label>Creator Fee % <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" name="creator_fee_percentage" class="form-control" value="{{ old('creator_fee_percentage', 5) }}" step="0.01" min="0" max="100" required>
                                                    <span class="input-group-addon">%</span>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Platform Fee % <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" name="platform_fee_percentage" class="form-control" value="{{ old('platform_fee_percentage', 2.5) }}" step="0.01" min="0" max="100" required>
                                                    <span class="input-group-addon">%</span>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Liquidity Pool % <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" name="liquidity_pool_percentage" class="form-control" value="{{ old('liquidity_pool_percentage', 10) }}" step="0.01" min="0" max="100" required>
                                                    <span class="input-group-addon">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-bordered jf-dash-card jf-tokens-form-section">
                                        <div class="panel-heading jf-dash-card__head">
                                            <h3 class="panel-title jf-dash-card__title">
                                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--green"><i class="voyager-check"></i></span>
                                                <span>Token Features</span>
                                            </h3>
                                        </div>
                                        <div class="panel-body jf-dash-card__body">
                                            <div class="form-group jf-tokens-form__checkbox">
                                                <label>
                                                    <input type="checkbox" name="enable_burning" value="1" {{ old('enable_burning') ? 'checked' : '' }}>
                                                    Enable Token Burning
                                                </label>
                                            </div>

                                            <div class="form-group jf-tokens-form__checkbox">
                                                <label>
                                                    <input type="checkbox" name="enable_minting" value="1" {{ old('enable_minting') ? 'checked' : '' }}>
                                                    Enable Token Minting
                                                </label>
                                            </div>

                                            <div class="form-group jf-tokens-form__checkbox">
                                                <label>
                                                    <input type="checkbox" name="transferable" value="1" {{ old('transferable', true) ? 'checked' : '' }}>
                                                    Transferable
                                                </label>
                                            </div>

                                            <div class="form-group jf-tokens-form__checkbox">
                                                <label>
                                                    <input type="checkbox" name="is_verified" value="1" {{ old('is_verified') ? 'checked' : '' }}>
                                                    Verified Token
                                                </label>
                                            </div>

                                            <div class="form-group jf-tokens-form__checkbox">
                                                <label>
                                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                                    Active Token
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="panel-footer jf-tokens-form__footer">
                                <button type="submit" class="jf-dash-btn jf-dash-btn--green">
                                    <i class="voyager-check"></i>
                                    <span class="jf-pill-label">Create Token</span>
                                </button>
                                <a href="{{ route('voyager.tokens.index') }}" class="jf-dash-btn jf-dash-btn--blue">
                                    <i class="voyager-x"></i>
                                    <span class="jf-pill-label">Cancel</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
$(document).ready(function() {
    $('input[name="current_price"], input[name="circulating_supply"]').on('input', function() {
        var price = parseFloat($('input[name="current_price"]').val()) || 0;
        var supply = parseFloat($('input[name="circulating_supply"]').val()) || 0;
        var marketCap = price * supply;
        $('input[name="market_cap"]').val(marketCap.toFixed(2));
    });

    $('input[name="initial_price"]').on('input', function() {
        var currentPrice = $('input[name="current_price"]');
        if (!currentPrice.val() || parseFloat(currentPrice.val()) === 0) {
            currentPrice.val($(this).val());
        }
    });

    $('#token-logo-upload').on('change', function() {
        var $wrap = $(this).closest('.jf-file-upload');
        var $name = $wrap.find('.jf-file-upload__name');
        var placeholder = $name.data('placeholder') || 'No file chosen';
        var fileName = this.files && this.files.length ? this.files[0].name : '';

        $name.text(fileName || placeholder);
        $name.toggleClass('is-selected', !!fileName);
    });
});
</script>
@stop
