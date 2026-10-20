<?php

namespace App\Http\Controllers;

use App\Models\CreatorCoin;
use App\Models\CreatorCoinBalance;
use App\Services\CreatorCoinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreatorCoinController extends Controller
{
    protected CreatorCoinService $service;

    public function __construct(CreatorCoinService $service)
    {
        $this->service = $service;
    }

    /** Browse all live creator coins. */
    public function index()
    {
        $coins = CreatorCoin::active()
            ->with('creator')
            ->withCount(['balances as holders_count' => fn ($q) => $q->where('balance', '>', 0)])
            ->orderByDesc('points_issued')
            ->paginate(24);

        return view('creator-coins.index', compact('coins'));
    }

    /** Show the "launch your coin" form (one coin per creator). */
    public function create()
    {
        $existing = CreatorCoin::where('creator_user_id', Auth::id())->first();
        if ($existing) {
            return redirect()->route('creator-coins.show', $existing->id)
                ->with('info', 'You already have a creator coin.');
        }

        return view('creator-coins.create', ['cfg' => config('creator_coins')]);
    }

    /** Create the current user's coin. */
    public function store(Request $request)
    {
        $cfg = config('creator_coins');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:16|regex:/^[A-Za-z0-9]+$/',
            'description' => 'nullable|string|max:1000',
            'price_per_point' => "required|numeric|min:{$cfg['min_price_per_point']}|max:{$cfg['max_price_per_point']}",
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $logoPath = $request->hasFile('logo')
                ? $request->file('logo')->store('creator-coins', 'public')
                : null;

            $coin = $this->service->createCoin(Auth::user(), [
                'name' => $request->name,
                'symbol' => $request->symbol,
                'description' => $request->description,
                'price_per_point' => $request->price_per_point,
                'logo' => $logoPath,
            ]);

            return redirect()->route('creator-coins.show', $coin->id)
                ->with('success', 'Your creator coin is live!');
        } catch (\Throwable $e) {
            Log::error('Creator coin create failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /** A coin's page: details + the buy widget + the viewer's balance/history. */
    public function show($id)
    {
        $coin = CreatorCoin::with('creator')->findOrFail($id);
        $myBalance = $coin->balanceFor((int) Auth::id());
        $myCredits = optional(Auth::user()->wallet)->total ?? 0;
        $recent = $coin->transactions()
            ->where('user_id', Auth::id())
            ->latest()
            ->limit(10)
            ->get();

        return view('creator-coins.show', compact('coin', 'myBalance', 'myCredits', 'recent'));
    }

    /** Buy points with platform credits. */
    public function buy(Request $request, $id)
    {
        $coin = CreatorCoin::findOrFail($id);
        $cfg = config('creator_coins');

        $validator = Validator::make($request->all(), [
            'points' => "required|integer|min:1|max:{$cfg['max_purchase_points']}",
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $this->service->purchase($coin, Auth::user(), (float) $request->points);

            return redirect()->route('creator-coins.show', $coin->id)
                ->with('success', "Purchased {$request->points} {$coin->symbol}!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** The viewer's points across all creators, plus their own coin if they have one. */
    public function holdings()
    {
        $balances = CreatorCoinBalance::with('coin.creator')
            ->where('user_id', Auth::id())
            ->where('balance', '>', 0)
            ->get();

        $myCoin = CreatorCoin::where('creator_user_id', Auth::id())->first();

        return view('creator-coins.holdings', compact('balances', 'myCoin'));
    }
}
