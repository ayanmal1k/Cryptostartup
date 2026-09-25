/*
 * NFT wallet connection (framework-free).
 *
 * Persists the user's on-chain wallet address to their account via /nft/wallet/connect.
 * Connection strategy, in priority order:
 *   1. thirdweb embedded wallet  - when THIRDWEB_CLIENT_ID is configured (email/social login,
 *      gasless). Scaffolded; verify the thirdweb v5 API when wiring real keys.
 *   2. Injected wallet (MetaMask) - when window.ethereum exists.
 *   3. Dev-simulated address      - only when APP_DEBUG, so the mint pipeline is testable
 *      locally before any wallet provider is set up.
 *
 * Config is provided by the Blade partial as window.NFT_WALLET_CONFIG.
 */
(function () {
    'use strict';

    var cfg = window.NFT_WALLET_CONFIG || {};

    function $(id) { return document.getElementById(id); }

    function setStatus(message, kind) {
        var el = $('wallet-status-msg');
        if (!el) return;
        el.textContent = message || '';
        el.className = 'small mt-2 ' + (kind === 'error' ? 'text-danger' : 'text-muted');
    }

    function randomDevAddress() {
        var hex = '0123456789abcdef', a = '0x';
        for (var i = 0; i < 40; i++) a += hex[Math.floor(Math.random() * 16)];
        return a;
    }

    // Persist the address to the user's account, then refresh so the page reflects state.
    function saveAddress(address) {
        setStatus('Linking wallet…');
        return fetch(cfg.connectUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ wallet_address: address })
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
          .then(function (res) {
              if (res.ok && res.body.ok) {
                  window.location.reload();
              } else {
                  setStatus((res.body && res.body.message) || 'Could not link wallet.', 'error');
              }
          }).catch(function (e) { setStatus('Network error: ' + e.message, 'error'); });
    }

    function disconnect() {
        fetch(cfg.disconnectUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' }
        }).then(function () { window.location.reload(); });
    }

    // --- connection strategies ---------------------------------------------------------

    async function connectThirdweb() {
        // thirdweb v5 in-app (embedded) wallet. Untested until THIRDWEB_CLIENT_ID is set;
        // confirm the API shape against your installed thirdweb version when wiring keys.
        var tw = await import('https://esm.sh/thirdweb@5');
        var wallets = await import('https://esm.sh/thirdweb@5/wallets');
        var client = tw.createThirdwebClient({ clientId: cfg.clientId });
        var wallet = wallets.inAppWallet();
        var account = await wallet.connect({ client: client, strategy: 'google' });
        return account.address;
    }

    async function connectInjected() {
        var accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
        if (!accounts || !accounts.length) throw new Error('No account selected.');
        return accounts[0];
    }

    function handleConnectClick() {
        setStatus('Connecting…');
        var attempt;

        if (cfg.clientId) {
            attempt = connectThirdweb();
        } else if (typeof window.ethereum !== 'undefined') {
            attempt = connectInjected();
        } else if (cfg.appDebug) {
            attempt = Promise.resolve(randomDevAddress());
        } else {
            setStatus('No wallet available. Install a wallet (e.g. MetaMask) to continue.', 'error');
            return;
        }

        attempt.then(saveAddress).catch(function (e) {
            // Fall back to dev address in debug if a provider failed.
            if (cfg.appDebug) {
                setStatus('Wallet provider failed (' + e.message + '); using a dev test wallet.');
                saveAddress(randomDevAddress());
            } else {
                setStatus('Connection failed: ' + e.message, 'error');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var connectBtn = $('wallet-connect-btn');
        var disconnectBtn = $('wallet-disconnect-btn');
        var devBtn = $('wallet-dev-btn');
        if (connectBtn) connectBtn.addEventListener('click', handleConnectClick);
        if (disconnectBtn) disconnectBtn.addEventListener('click', disconnect);
        if (devBtn) devBtn.addEventListener('click', function () { saveAddress(randomDevAddress()); });
    });
})();
