# Crypto / NFT Build Plan

> Status of the codebase as audited (2026-06-24): the crypto/NFT layer is **~90% mockup**.
> All "blockchain" actions are simulated in MySQL. `Web3Service` returns fake transaction
> hashes, the Solidity contract is never compiled/deployed, the frontend Web3 code is not
> wired into any view, and several admin controllers referenced by routes do not exist.
> This document is the plan to turn it into something real.

---

## 1. Recommended Architecture: Hybrid, provider-backed

Split by feature instead of committing the whole app to one model.

| Feature | Approach | Rationale |
|---|---|---|
| NFT ownership + media→NFT | **Real on-chain (Polygon)** | Ownership must be real; lowest legal risk; existing contract targets this; cheap gas; EVM. |
| Crypto wallets | **Non-custodial via wallet provider** (thirdweb / Privy / Web3Auth) | Avoid holding private keys; avoids money-transmitter licensing; gives email→wallet UX. |
| Fiat on-ramp | **Third-party** (MoonPay / Transak / Stripe Crypto) | Provider owns KYC/AML + licensing. |
| Creator coins | **Non-cashable "creator points" first**, ERC-20 later only with legal sign-off | Tradeable/cashable creator coins are likely securities + money transmission. |

**Chain:** Polygon (testnet: Amoy). **Metadata/media:** IPFS via Pinata or nft.storage.
**Strongly consider [thirdweb](https://thirdweb.com)** for contracts + wallet + minting SDK to
cut custom Web3 code and reduce audit surface.

### Non-negotiables
- **Smart-contract security audit** before mainnet.
- **Legal counsel** for creator coins and any cash-out path — start in parallel with dev.
- **Never** store user private keys in the app DB.

---

## 2. Current State — what to keep, fix, or discard

**Keep (good skeleton):** Blade views under `resources/views/nft/`, the models
(`NFT`, `NFTListing`, `NFTTransaction`, `Cryptocurrency`, `CryptoWallet`, `CryptoRevenueShare`),
DB tables, and `contracts/NFTMarketplace.sol` (real ERC-721, just undeployed).

**Fix / replace:**
- `app/Services/Web3Service.php` — returns mock data; replace with a real provider SDK.
- `public/js/web3.js` — real code but **not included in any view**; wire it in (or replace with provider SDK).
- `app/Http/Controllers/CryptoOnRampController.php` — wrong namespace `App\Model` vs `App\Models`; will crash.
- Missing controllers referenced in `routes/web.php`: `Admin\TokenController`,
  `Admin\CreatorTokenController`, `Admin\AuditController` — routes crash on access.
- `private_key_encrypted` field — labeled encrypted but never encrypted. Remove custodial key storage entirely (go non-custodial).

**Discard / rewrite:** hardcoded price feeds (BTC=$45k, ETH=$2.8k); hardcoded `token_id = '0'`.

---

## 3. Phased Plan

### Phase 0 — Stabilize (≈1 week)
- Fix the namespace crash in `CryptoOnRampController`.
- Create or remove the three missing admin controllers so routes stop crashing.
- Disable/guard all stub crypto endpoints so nothing silently fakes a transaction.
- Remove custodial private-key storage.
- Stand up the contract toolchain: `cd contracts && npm install`, compile, run tests locally (Hardhat).
- **Exit criteria:** app boots with no crashing routes; no endpoint pretends to do a real chain action.

### Phase 1 — Foundations & decisions (≈1–2 weeks)
- Lock chain (Polygon) + testnet (Amoy); create project on an RPC provider (Alchemy/Infura).
- Choose wallet provider (thirdweb / Privy / Web3Auth) and integrate connect/login.
- Set up IPFS (Pinata/nft.storage) and a `MediaStorageService`.
- Replace `Web3Service` stub with the real SDK; add config in `config/web3.php` + `.env`.
- Kick off legal review for creator coins + on-ramp.
- **Exit criteria:** a user can connect a wallet; backend can read chain state for real.

### Phase 2 — Real NFT ownership + marketplace (≈3–5 weeks)
- Deploy `NFTMarketplace.sol` (or a thirdweb contract) to Amoy; store address in config.
- Wire mint / list / buy / resell to real transactions; store metadata on IPFS (ERC-2981 royalties).
- Build a chain→DB sync (event indexer or provider webhooks) so `token_id`, owner, and tx hashes are real.
- Show true ownership + provenance in `resources/views/nft/`.
- **Exit criteria:** mint → list → buy → resell works end-to-end on testnet with on-chain proof.

### Phase 3 — Media → NFT pipeline (≈2–4 weeks)  ← the client's differentiator, currently 0% built
- "Mint this video/photo as an NFT" action on existing posts/videos.
- Pipeline: existing upload → IPFS → mint → link NFT to the post/video → show badge + marketplace link.
- Creator royalties on secondary sales.
- **Exit criteria:** a creator turns an existing upload into an owned, tradeable NFT in one flow.

### Phase 4 — Creator coins (≈4–6 weeks + legal gate)
- **Path A (recommended first):** non-cashable creator *points* — perks/unlocks only, no redemption for money. Off-chain ledger, low legal risk.
- **Path B (needs legal sign-off):** real per-creator ERC-20 with on-chain trading.
- Build the missing `CreatorTokenController` + admin approval/supply workflows here.
- **Exit criteria:** creators issue coins/points; fans earn/spend; admin oversight; legal cleared for the chosen path.

### Phase 5 — Creator economy glue (≈2–3 weeks)
- Auto-distribute revenue shares (`CryptoRevenueShare`) via queued jobs (currently nothing distributes).
- Marketplace fees, payout flows, analytics dashboards.
- Integrate third-party fiat on-ramp.
- **Exit criteria:** money/value flows correctly between fans, creators, and platform with an audit trail.

### Cross-cutting (throughout)
- Security: contract audit, provider-managed keys, rate limiting, replay protection.
- Compliance: KYC/AML via on-ramp provider; geofencing if needed.
- Testing: contract tests, integration tests on testnet, load tests before mainnet.

---

## 4. Rough effort & sequencing
- One experienced **Web3 + Laravel** engineer: **~4–6 months** to a solid testnet→mainnet launch.
- Order of value: **Phase 0 → 2 → 3** delivers the "real NFT + media→NFT" story fastest.
  **Phase 4 (creator coins)** is highest legal risk — do last and gate on counsel.

## 5. Recurring costs to budget
RPC provider, IPFS pinning, wallet/on-ramp provider fees, gas (relayer if you sponsor it),
one-time **smart-contract audit**, and **legal fees**.
