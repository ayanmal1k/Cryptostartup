@php
    $walletAddress = auth()->check() ? auth()->user()->wallet_address : null;
@endphp

<div class="card border-0 shadow-sm mb-4" id="wallet-connect-widget">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h6 class="mb-1"><i class="fab fa-ethereum"></i> Wallet</h6>
                @if($walletAddress)
                    <span class="text-success"><i class="fas fa-check-circle"></i> Connected</span>
                    <code class="ml-1">{{ substr($walletAddress, 0, 8) }}…{{ substr($walletAddress, -6) }}</code>
                @else
                    <span class="text-muted">No wallet connected — connect one to own NFTs.</span>
                @endif
            </div>
            <div class="mt-2 mt-md-0">
                @if($walletAddress)
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="wallet-disconnect-btn">
                        <i class="fas fa-unlink"></i> Disconnect
                    </button>
                @else
                    <button type="button" class="btn btn-primary btn-sm" id="wallet-connect-btn">
                        <i class="fas fa-wallet"></i> Connect Wallet
                    </button>
                    @if(config('app.debug') && !config('web3.thirdweb_client_id'))
                        <button type="button" class="btn btn-link btn-sm text-muted" id="wallet-dev-btn">
                            Use a dev test wallet
                        </button>
                    @endif
                @endif
            </div>
        </div>
        <div id="wallet-status-msg" class="small mt-2 text-muted"></div>
    </div>
</div>

<script>
    window.NFT_WALLET_CONFIG = {
        connectUrl: @json(route('nft.wallet.connect')),
        disconnectUrl: @json(route('nft.wallet.disconnect')),
        csrf: @json(csrf_token()),
        clientId: @json(config('web3.thirdweb_client_id')),
        chainId: @json((int) config('web3.chain_id')),
        appDebug: @json((bool) config('app.debug')),
        currentAddress: @json($walletAddress)
    };
</script>
<script src="{{ asset('js/nft-wallet.js') }}"></script>
