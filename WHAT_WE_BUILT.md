# What We Built — Crypto Layer

This project is a Laravel 9 creator platform (based on JustFans). This document covers the
**crypto/NFT features** that were added, how they work, and their current status.

> **Important — current mode:** everything below runs in **simulated / dev mode**. No real
> blockchain or money is involved yet. It is wired to switch to **real on-chain** (Polygon, via
> thirdweb) by filling credentials into `.env` — **no code changes required**. See "Going live".

---

## Features

### 1. NFT ownership
- Connect a wallet to your account (embedded/thirdweb, MetaMask, or a dev test wallet).
- Mint an NFT from an uploaded image; it is recorded as **owned by your wallet address** with a
  token id and an on-chain-style provenance record.
- Pages: `/nft/create`, `/nft/my/nfts`, `/nft/marketplace`.

### 2. Videos & pictures → NFTs
- Turn your **existing** videos and photos into NFTs ("Mint as NFT").
- Video NFTs include a thumbnail **and** the playable video (standard `animation_url`).
- Creator **royalties** on resales (default 10%).
- Each item can only be minted once. Page: `/nft/mintable`.

### 3. Creator coins (Creator Points)
- Each creator can launch a **loyalty-points currency**.
- Fans **buy points with platform credits**; the creator earns withdrawable credits (minus a
  platform fee). Points are spent on the creator's perks.
- **Non-cashable for fans** (no points → money) — keeps it out of securities/money-transmission
  territory. Pages: `/creator-coins`, `/creator-coins/create`, `/creator-coins/holdings`.

### 4. Integrated crypto wallet hub
- One page (`/wallet`) showing: native balance, platform credits, NFTs owned, creator coins held,
  a **receive** address with QR, a **send** form (signed by the user's own wallet), and a unified
  **activity feed**.
- **Non-custodial**: the platform never holds users' private keys. (The old fake
  `private_key_encrypted` storage was removed.)

### Still to build
- **NFT marketplace** (list / buy / sell / resell with on-chain settlement).
- Going fully live on-chain (needs thirdweb account — see below).

---

## How it's architected (for developers)

- **Wallets:** non-custodial. `users.wallet_address` holds the connected wallet.
- **Minting pipeline:** `App\Services\Nft\*` — a provider interface with a local **Fake** driver
  (dev) and a **thirdweb Engine** driver (real). `App\Jobs\MintNft` performs the mint and mirrors
  the result to the DB. Driver auto-selected via `WEB3_DRIVER=auto`.
- **Storage:** `MetadataStorageService` — local public disk in dev, **IPFS (thirdweb)** in prod.
- **Creator coins:** `App\Services\CreatorCoinService` (DB-transaction + row-locking for safe
  credit/point movements). Platform credits = `wallets.total`.
- **Wallet hub:** `App\Http\Controllers\WalletHubController` aggregates existing data + a live
  RPC balance read.
- Config: `config/web3.php`, `config/creator_coins.php`. Build plan: `CRYPTO_BUILD_PLAN.md`.

---

## Run locally

Requirements: PHP 8.2, MySQL/MariaDB, Composer, Node.js.

```bash
composer install
npm install && npm run prod
cp .env.example .env
php artisan key:generate
# set DB_* in .env, create the database, then:
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Default admin: `admin@admin.com` / `password` (change it).

---

## Going live on-chain (later)

1. Create a **thirdweb** account → API key (Client ID + Secret).
2. Deploy an **NFT Collection** + **Marketplace V3** on Polygon Amoy (testnet).
3. Set up thirdweb **Engine** (gas-sponsored backend minting).
4. Put the values in `.env` (`THIRDWEB_CLIENT_ID`, `WEB3_ENGINE_URL`, `WEB3_ENGINE_ACCESS_TOKEN`,
   `NFT_MARKETPLACE_CONTRACT_ADDRESS`, …). `WEB3_DRIVER=auto` then uses real on-chain automatically.

> **Legal note:** before launching creator coins or any cash-out/trading, get legal counsel.
> Creator coins are built as non-cashable points specifically to minimize that risk.
