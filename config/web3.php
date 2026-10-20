<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web3 / NFT Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for on-chain NFT ownership. Defaults target the Polygon Amoy
    | testnet (chain id 80002). Until thirdweb credentials are supplied the app
    | runs with the local "fake" drivers so the full pipeline works offline.
    |
    */

    // Minting driver: 'fake' | 'thirdweb' | 'auto' (auto = thirdweb when configured).
    'driver' => env('WEB3_DRIVER', 'auto'),

    // Storage driver for IPFS/metadata: 'fake' | 'thirdweb' | 'auto'.
    'storage_driver' => env('WEB3_STORAGE_DRIVER', 'auto'),

    // Chain the NFT contract lives on. 80002 = Polygon Amoy testnet, 137 = Polygon mainnet.
    'chain_id' => (int) env('WEB3_CHAIN_ID', 80002),

    'network' => env('WEB3_NETWORK', 'amoy'),

    // Deployed thirdweb NFT Collection (ERC-721) contract address.
    'contract_address' => env('NFT_MARKETPLACE_CONTRACT_ADDRESS', ''),

    // Optional read-only RPC endpoint (used for direct reads / receipt checks).
    'rpc_url' => env('WEB3_RPC_URL', 'https://rpc-amoy.polygon.technology'),

    // thirdweb Engine: HTTP API that signs + sends gas-sponsored txs from a backend wallet.
    'engine' => [
        'url' => env('WEB3_ENGINE_URL', ''),
        'access_token' => env('WEB3_ENGINE_ACCESS_TOKEN', ''),
        'backend_wallet' => env('WEB3_ENGINE_BACKEND_WALLET', ''),
    ],

    // thirdweb client id (used by the frontend Connect SDK for embedded/gasless wallets).
    'thirdweb_client_id' => env('THIRDWEB_CLIENT_ID', ''),

    // Marketplace V3 contract (used in a later phase for buy/sell listings).
    'marketplace_address' => env('NFT_MARKETPLACE_V3_ADDRESS', ''),

    // Listing fee shown in the UI (in the chain's native token), informational only.
    'listing_price' => env('NFT_LISTING_PRICE', 0.0025),

    // Default creator royalty on resales, in basis points (1000 = 10%).
    'default_royalty_bps' => (int) env('NFT_DEFAULT_ROYALTY_BPS', 1000),

    // Marketplace platform fee on each sale, in basis points (250 = 2.5%).
    'marketplace_fee_bps' => (int) env('NFT_MARKETPLACE_FEE_BPS', 250),
];
