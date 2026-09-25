<?php

namespace App\Http\Controllers;

use App\Models\CreatorCoinBalance;
use App\Models\CreatorCoinTransaction;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\NFT;
use App\Models\NFTTransaction;
use App\Services\WalletInsights;
use App\Support\TokenMoney;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * The single, unified wallet: one place where a user sees every form of value they hold on the
 * platform — platform credits, creator tokens, creator coins (points), NFTs, and their
 * non-custodial on-chain wallet — plus one merged activity timeline across all of them.
 *
 * This replaces the three wallet surfaces that used to exist (/wallet, /cryptocurrency/wallet
 * and /settings/wallet). It only AGGREGATES data the app already owns, plus an optional live
 * RPC balance read — the app never holds the user's keys.
 */
class WalletHubController extends Controller
{
    public function index()
    {
        $data = $this->snapshot(Auth::user());
        $data['activity'] = $this->activityFeed($data['user']->id, $data['address']);
        $data['nativeBalance'] = $data['address'] ? $this->readNativeBalance($data['address']) : null;
        $data['series'] = WalletInsights::portfolioSeries($data['user']->id, $data['tokenWallets']);
        $data['levelCard'] = WalletInsights::level($data['user']);

        return view('wallet.index', $data);
    }

    /**
     * The numbers the wallet polls while it is open, so balances, the day's move and
     * level progress update without a reload.
     */
    public function live()
    {
        $data = $this->snapshot(Auth::user());
        $level = WalletInsights::level($data['user']);

        return response()->json([
            'total' => round($data['totalBalance'], 2),
            'credits' => round($data['credits'], 2),
            'coins' => round($data['tokenValue'], 2),
            'perks' => round($data['coinValue'], 2),
            'day_move' => round($data['dayMove'], 2),
            'nfts' => $data['nftCount'],
            'holdings' => $data['holdings']->map(fn ($h) => [
                'id' => $h->id, 'value' => $h->value, 'move' => $h->move, 'dir' => $h->dir,
            ])->values(),
            'level' => [
                'level' => $level['level'], 'percent' => $level['percent'], 'into' => $level['into'],
                'to_next' => $level['to_next'], 'today_xp' => $level['today_xp'],
                'streak' => $level['streak'], 'sparks' => $level['sparks'], 'tier' => $level['tier']['name'],
            ],
        ]);
    }

    /** Balances and holdings, shared by the page and the live endpoint. */
    private function snapshot($user): array
    {
        $address = $user->wallet_address;

        // --- Holdings -------------------------------------------------------------------
        $credits = (float) (optional($user->wallet)->total ?? 0);

        $tokenWallets = CryptoWallet::where('user_id', $user->id)
            ->where('balance', '>', 0)
            ->with('cryptocurrency')
            ->get()
            // A token row whose cryptocurrency was deleted would fatal in the view.
            ->filter(fn ($w) => $w->cryptocurrency !== null)
            ->values();

        $tokenValue = $tokenWallets->sum(
            fn ($w) => (float) $w->balance * (float) $w->cryptocurrency->current_price
        );

        $coinBalances = CreatorCoinBalance::with('coin')
            ->where('user_id', $user->id)
            ->where('balance', '>', 0)
            ->get()
            ->filter(fn ($b) => $b->coin !== null)
            ->values();

        // Creator points are non-cashable for fans, so they are shown as an indicative value
        // only — deliberately NOT added into the headline spendable balance.
        $coinValue = $coinBalances->sum(
            fn ($b) => (float) $b->balance * (float) $b->coin->price_per_point
        );

        $nfts = $this->ownedNfts($user->id, $address);
        $nftCount = $nfts->count();

        // Headline figure = what the user can actually spend/withdraw on-platform.
        $totalBalance = $credits + $tokenValue;

        $holdings = WalletInsights::holdings($tokenWallets);
        // How much the coins moved today, in dollars (credits don't move).
        $dayMove = (float) $holdings->sum('move');

        return compact(
            'user',
            'address',
            'credits',
            'tokenWallets',
            'tokenValue',
            'coinBalances',
            'coinValue',
            'nfts',
            'nftCount',
            'totalBalance',
            'holdings',
            'dayMove'
        );
    }

    /**
     * NFTs the user owns: matched by on-chain owner address when a wallet is connected, and
     * always including rows the app itself recorded against the user.
     */
    private function ownedNfts(int $userId, ?string $address)
    {
        return NFT::query()
            ->with(['activeListing', 'creator'])
            ->where('status', '!=', NFT::STATUS_MINT_FAILED)
            ->where(function ($q) use ($userId, $address) {
                // The account owns it, or a linked wallet holds it on-chain.
                $q->ownedByUser($userId);
                if ($address) {
                    $q->orWhere(fn ($w) => $w->ownedByAddress($address));
                }
            })
            ->latest()
            ->get();
    }

    /**
     * Best-effort on-chain native balance via JSON-RPC. Returns a float (token units) or null.
     * Wrapped so a dev environment / unreachable RPC never breaks the page.
     */
    private function readNativeBalance(string $address): ?float
    {
        $rpc = config('web3.rpc_url');
        if (!$rpc) {
            return null;
        }

        try {
            $resp = Http::timeout(5)->post($rpc, [
                'jsonrpc' => '2.0',
                'method' => 'eth_getBalance',
                'params' => [$address, 'latest'],
                'id' => 1,
            ]);
            $hex = data_get($resp->json(), 'result');
            if (!is_string($hex) || !str_starts_with($hex, '0x')) {
                return null;
            }

            return (float) (hexdec(substr($hex, 2)) / 1e18);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Merge token, creator-coin and NFT activity into one normalized, time-sorted timeline.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function activityFeed(int $userId, ?string $address)
    {
        $tokenRows = CryptoTransaction::with(['cryptocurrency', 'buyer', 'seller'])
            ->where(fn ($q) => $q->where('buyer_user_id', $userId)->orWhere('seller_user_id', $userId))
            ->latest()
            ->limit(25)
            ->get()
            ->map(function ($t) use ($userId) {
                // Dollars and names only, never a ticker or a unit count.
                $coin = optional($t->cryptocurrency)->name ?? 'coins';

                if ($t->type === CryptoTransaction::TYPE_TRANSFER) {
                    // One row, two readings: the recipient is the buyer, the sender the seller.
                    $in = (int) $t->buyer_user_id === $userId;
                    $other = optional($in ? $t->seller : $t->buyer)->username;
                    $label = ($in ? __('Received') : __('Sent')) . ' ' . $coin
                        . ($other ? ' ' . ($in ? __('from') : __('to')) . ' @' . $other : '');
                } else {
                    $in = $t->type === CryptoTransaction::TYPE_BUY;
                    $label = (['buy' => __('Bought'), 'sell' => __('Sold')][$t->type] ?? ucfirst((string) $t->type)) . ' ' . $coin;
                }

                return [
                    'kind' => 'token',
                    'type' => $t->type,
                    'label' => $label,
                    'amount' => TokenMoney::format((float) $t->total_price),
                    'in' => $in,
                    'at' => $t->created_at,
                ];
            });

        $coinRows = CreatorCoinTransaction::with('coin')
            ->where('user_id', $userId)
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn ($t) => [
                'kind' => 'coin',
                'type' => $t->type,
                'label' => ucfirst((string) $t->type) . ' · ' . (optional($t->coin)->name ?? 'creator coin'),
                'amount' => TokenMoney::format((float) $t->credits_amount > 0
                    ? (float) $t->credits_amount
                    : (float) $t->points * (float) optional($t->coin)->price_per_point),
                'in' => $t->type !== CreatorCoinTransaction::TYPE_SPEND,
                'at' => $t->created_at,
            ]);

        $nftRows = NFTTransaction::with('nft')
            ->whereHas('nft', fn ($q) => $q->where('user_id', $userId))
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn ($t) => [
                'kind' => 'nft',
                'type' => $t->type,
                'label' => ucfirst((string) $t->type) . ' · ' . (optional($t->nft)->name ?? 'NFT'),
                'amount' => null,
                'in' => $address ? (strtolower((string) $t->to_address) === strtolower($address)) : true,
                'at' => $t->created_at,
            ]);

        return $tokenRows->concat($coinRows)->concat($nftRows)
            ->sortByDesc(fn ($r) => optional($r['at'])->timestamp ?? 0)
            ->take(30)
            ->values();
    }

    /** Format a float without trailing zeros (0.5000 -> 0.5, 12.0000 -> 12). */
    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
