<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cryptocurrency;
use App\Models\CryptoWallet;
use App\Models\CryptoTransaction;
use App\Support\TokenMoney;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CryptocurrencyController extends Controller
{
    protected $cryptoService;

    public function __construct()
    {
        $this->middleware('auth');
        // Comment out this line if you don't have the service
        // $this->cryptoService = App::make('cryptocurrency');
    }

    /**
     * Display cryptocurrency page with custom request button.
     */
    public function index(Request $request)
    {
        // This used to render a splash interstitial (three checkmarks plus links onward) that
        // did nothing on its own. The route name is kept because the sidebar, side-menu,
        // app-bar back button and several views all link to it — they now land straight on
        // the token marketplace instead of a dead middle step.
        return redirect()->route('cryptocurrency.marketplace');
    }

    /**
     * Show the form for creating a new cryptocurrency.
     */
 /**
 * Show the form for creating a new cryptocurrency.
 */
public function create()
{
    // Check user limits
    $userTokenCount = Cryptocurrency::where('creator_user_id', Auth::id())->count();
    $maxTokensPerUser = 10;
    
    if ($userTokenCount >= $maxTokensPerUser) {
        return redirect()->route('cryptocurrency.index')
            ->with('error', 'You have reached the maximum limit of ' . $maxTokensPerUser . ' tokens.');
    }

    // Get available blockchain networks
    $blockchainNetworks = [
        'ethereum' => 'Ethereum (ETH) - Most Popular',
        'binance' => 'Binance Smart Chain (BSC) - Low Fees',
        'polygon' => 'Polygon (MATIC) - Fast & Cheap',
        'solana' => 'Solana (SOL) - High Performance',
        'avalanche' => 'Avalanche (AVAX) - Fast Finality',
        'cardano' => 'Cardano (ADA) - Sustainable',
        'arbitrum' => 'Arbitrum (ARB) - Layer 2 Solution',
        'optimism' => 'Optimism (OP) - Optimistic Rollup'
    ];

    // Get available token types
    $tokenTypes = [
        'utility' => 'Utility Token - For platform usage',
        'security' => 'Security Token - Investment token',
        'governance' => 'Governance Token - Voting rights',
        'payment' => 'Payment Token - Digital currency',
        'nft' => 'NFT Collection - Collectibles',
        'defi' => 'DeFi Token - Decentralized Finance',
        'gaming' => 'Gaming Token - In-game currency',
        'social' => 'Social Token - Community rewards'
    ];

    // Default values for form
    $defaults = [
        'initial_price' => 0.001,
        'total_supply' => 1000000,
        'creator_allocation' => 100000,
        'creator_fee_percentage' => 5.0,
        'platform_fee_percentage' => 2.5,
        'liquidity_pool_percentage' => 20.0,
        'token_type' => 'utility',
        'blockchain_network' => 'ethereum',
        'enable_burning' => false,
        'enable_minting' => false,
        'transferable' => true
    ];

    return view('cryptocurrency.create', compact(
        'blockchainNetworks',
        'tokenTypes', 
        'defaults',
        'userTokenCount',
        'maxTokensPerUser'
    ));
}

    /**
     * Store a newly created cryptocurrency in storage.
     */
    // public function store(Request $request)
    // {
    //     // Enhanced validation rules based on your form
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:191|unique:cryptocurrencies,name',
    //         'symbol' => 'required|string|max:10|unique:cryptocurrencies,symbol|regex:/^[A-Z0-9]+$/',
    //         'description' => 'required|string|min:50',
    //         'initial_price' => 'required|numeric|min:0.00000001|max:99999999.99999999',
    //         'total_supply' => 'required|numeric|min:1|max:999999999999999999999999.99',
    //         'creator_allocation' => 'nullable|numeric|min:0',
    //         'blockchain_network' => 'required|string|in:ethereum,binance,polygon,solana',
    //         'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'creator_fee_percentage' => 'nullable|numeric|min:0|max:20',
    //         'liquidity_pool_percentage' => 'nullable|numeric|min:0|max:100',
    //         'token_type' => 'nullable|string|in:utility,security,governance,nft',
    //         'website' => 'nullable|url|max:191',
    //         'whitepaper' => 'nullable|url|max:191',
    //         'enable_burning' => 'nullable|boolean',
    //         'enable_minting' => 'nullable|boolean',
    //         'transferable' => 'nullable|boolean',
    //     ], [
    //         'name.unique' => 'A token with this name already exists.',
    //         'symbol.unique' => 'A token with this symbol already exists.',
    //         'symbol.regex' => 'Token symbol must contain only uppercase letters and numbers.',
    //         'description.min' => 'Description must be at least 50 characters long.',
    //         'initial_price.min' => 'Initial price must be greater than 0.00000001',
    //         'total_supply.min' => 'Total supply must be at least 1',
    //         'creator_fee_percentage.max' => 'Creator fee cannot exceed 20%',
    //         'logo.max' => 'Logo file size cannot exceed 2MB',
    //         'logo.mimes' => 'Logo must be a valid image file (JPEG, PNG, JPG, or GIF)',
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()
    //             ->withErrors($validator)
    //             ->withInput();
    //     }

    //     // Additional custom validations
    //     $totalSupply = (float) $request->input('total_supply');
    //     $creatorAllocation = (float) $request->input('creator_allocation', 0);

    //     if ($creatorAllocation > $totalSupply) {
    //         return redirect()->back()
    //             ->with('error', 'Creator allocation cannot exceed total supply.')
    //             ->withInput();
    //     }

    //     try {
    //         // Prepare data for cryptocurrency creation
    //         $data = [
    //             'creator_user_id' => Auth::id(),
    //             'name' => $request->input('name'),
    //             'symbol' => strtoupper($request->input('symbol')),
    //             'description' => $request->input('description'),
    //             'website' => $request->input('website'),
    //             'whitepaper' => $request->input('whitepaper'),
    //             'initial_price' => $request->input('initial_price'),
    //             'current_price' => $request->input('initial_price'), // Set current price to initial price
    //             'total_supply' => $totalSupply,
    //             'available_supply' => $totalSupply - $creatorAllocation, // Available for sale
    //             'circulating_supply' => $creatorAllocation, // Initially only creator allocation is in circulation
    //             'max_supply' => $totalSupply,
    //             'blockchain_network' => $request->input('blockchain_network'),
    //             'token_type' => $request->input('token_type', 'utility'),
    //             'enable_burning' => $request->has('enable_burning'),
    //             'enable_minting' => $request->has('enable_minting'),
    //             'transferable' => $request->has('transferable') ?: true, // Default to true if not specified
    //             'creator_fee_percentage' => $request->input('creator_fee_percentage', 5),
    //             'platform_fee_percentage' => 2.5, // Fixed platform fee
    //             'liquidity_pool_percentage' => $request->input('liquidity_pool_percentage', 20),
    //             'is_active' => true,
    //             'is_verified' => false, // New tokens need verification
    //             'volume_24h' => 0,
    //             'change_24h' => 0,
    //             'market_cap' => $totalSupply * $request->input('initial_price'),
    //         ];

    //         // Handle logo upload
    //         if ($request->hasFile('logo')) {
    //             try {
    //                 $logo = $request->file('logo');
    //                 $logoName = time() . '_' . $logo->getClientOriginalName();
    //                 $logoPath = $logo->storeAs('crypto-logos', $logoName, 'public');
    //                 $data['logo'] = $logoPath;
    //                 Log::info('Successfully uploaded logo: ' . $logoPath);
    //             } catch (\Exception $e) {
    //                 Log::error('Logo upload exception: ' . $e->getMessage());
    //                 return redirect()->back()
    //                     ->with('error', 'Failed to upload logo. Please try again.')
    //                     ->withInput();
    //             }
    //         }

    //         // Create the cryptocurrency
    //         $cryptocurrency = Cryptocurrency::create($data);

    //         if ($cryptocurrency) {
    //             // Create initial wallet for creator if they have allocation
    //             if ($creatorAllocation > 0) {
    //                 CryptoWallet::updateOrCreate(
    //                     [
    //                         'user_id' => Auth::id(),
    //                         'cryptocurrency_id' => $cryptocurrency->id
    //                     ],
    //                     [
    //                         'balance' => $creatorAllocation
    //                     ]
    //                 );
    //             }

    //             // Create initial transaction record for token creation
    //             CryptoTransaction::create([
    //                 'cryptocurrency_id' => $cryptocurrency->id,
    //                 'buyer_user_id' => Auth::id(),
    //                 'type' => 'create',
    //                 'amount' => $creatorAllocation,
    //                 'price_per_token' => $cryptocurrency->initial_price,
    //                 'total_price' => $creatorAllocation * $cryptocurrency->initial_price,
    //                 'status' => 'completed',
    //                 'metadata' => json_encode([
    //                     'type' => 'token_creation',
    //                     'creator_allocation' => $creatorAllocation,
    //                     'total_supply' => $totalSupply
    //                 ])
    //             ]);

    //             Log::info('Cryptocurrency created successfully', [
    //                 'id' => $cryptocurrency->id,
    //                 'name' => $cryptocurrency->name,
    //                 'symbol' => $cryptocurrency->symbol,
    //                 'creator_id' => Auth::id()
    //             ]);

    //             return redirect()->route('cryptocurrency.show', $cryptocurrency->id)
    //                 ->with('success', 'Congratulations! Your token "' . $cryptocurrency->name . '" has been created successfully.');
    //         }

    //         return redirect()->back()
    //             ->with('error', 'Failed to create cryptocurrency. Please try again.')
    //             ->withInput();

    //     } catch (\Exception $e) {
    //         Log::error('Cryptocurrency creation failed: ' . $e->getMessage(), [
    //             'user_id' => Auth::id(),
    //             'request_data' => $request->except(['logo', '_token'])
    //         ]);

    //         return redirect()->back()
    //             ->with('error', 'An error occurred while creating your token. Please try again.')
    //             ->withInput();
    //     }
    // }

    /**
 * Store a newly created cryptocurrency in storage.
 */
public function store(Request $request)
{
    // Enhanced validation rules - Fixed blockchain networks and token types
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:191|unique:cryptocurrencies,name',
        'symbol' => 'required|string|max:10|unique:cryptocurrencies,symbol|regex:/^[A-Z0-9]+$/',
        'description' => 'required|string|min:50|max:2000',
        'initial_price' => 'required|numeric|min:0.00000001|max:99999999.99999999',
        'total_supply' => 'required|numeric|min:1',
        'creator_allocation' => 'nullable|numeric|min:0',
        // Fixed validation - added all networks from your form
        'blockchain_network' => 'required|string|in:ethereum,binance,polygon,solana,avalanche,cardano,arbitrum,optimism',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'creator_fee_percentage' => 'nullable|numeric|min:0|max:20',
        'liquidity_pool_percentage' => 'nullable|numeric|min:0|max:100',
        // Fixed validation - added all token types from your form
        'token_type' => 'nullable|string|in:utility,security,governance,payment,nft,defi,gaming,social',
        'website' => 'nullable|url|max:191',
        'whitepaper' => 'nullable|url|max:191',
        'enable_burning' => 'nullable|in:1,0',
        'enable_minting' => 'nullable|in:1,0',
        'transferable' => 'nullable|in:1,0',
    ], [
        'name.unique' => 'A token with this name already exists.',
        'symbol.unique' => 'A token with this symbol already exists.',
        'symbol.regex' => 'Token symbol must contain only uppercase letters and numbers.',
        'description.min' => 'Description must be at least 50 characters long.',
        'description.max' => 'Description cannot exceed 2000 characters.',
        'initial_price.min' => 'Initial price must be greater than 0.00000001',
        'total_supply.min' => 'Total supply must be at least 1',
        'creator_fee_percentage.max' => 'Creator fee cannot exceed 20%',
        'logo.max' => 'Logo file size cannot exceed 2MB',
        'logo.mimes' => 'Logo must be a valid image file (JPEG, PNG, JPG, GIF, or SVG)',
        'blockchain_network.in' => 'Please select a valid blockchain network.',
        'token_type.in' => 'Please select a valid token type.',
    ]);

    if ($validator->fails()) {
        Log::error('Validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $request->except(['logo', '_token'])
        ]);
        
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    // Additional custom validations
    $totalSupply = (float) $request->input('total_supply');
    $creatorAllocation = (float) $request->input('creator_allocation', 0);

    if ($creatorAllocation > $totalSupply) {
        return redirect()->back()
            ->with('error', 'Creator allocation cannot exceed total supply.')
            ->withInput();
    }

    // Check user token limit
    $userTokenCount = Cryptocurrency::where('creator_user_id', Auth::id())->count();
    if ($userTokenCount >= 10) {
        return redirect()->back()
            ->with('error', 'You have reached the maximum limit of 10 tokens.')
            ->withInput();
    }

    try {
        // Prepare data for cryptocurrency creation
        $data = [
            'creator_user_id' => Auth::id(),
            'name' => trim($request->input('name')),
            'symbol' => strtoupper(trim($request->input('symbol'))),
            'description' => trim($request->input('description')),
            'website' => $request->input('website') ? trim($request->input('website')) : null,
            'whitepaper' => $request->input('whitepaper') ? trim($request->input('whitepaper')) : null,
            'initial_price' => $request->input('initial_price'),
            'current_price' => $request->input('initial_price'),
            'total_supply' => $totalSupply,
            'available_supply' => $totalSupply - $creatorAllocation,
            'circulating_supply' => $creatorAllocation,
            'max_supply' => $totalSupply,
            'volume_24h' => 0,
            'change_24h' => 0,
            'market_cap' => $totalSupply * $request->input('initial_price'),
            'blockchain_network' => $request->input('blockchain_network'),
            'token_type' => $request->input('token_type', 'utility'),
            // Fixed boolean handling
            'enable_burning' => $request->input('enable_burning') == '1' ? true : false,
            'enable_minting' => $request->input('enable_minting') == '1' ? true : false,
            'transferable' => $request->input('transferable') == '1' ? true : false,
            'creator_fee_percentage' => $request->input('creator_fee_percentage', 5),
            'platform_fee_percentage' => 2.5,
            'liquidity_pool_percentage' => $request->input('liquidity_pool_percentage', 20),
            'is_active' => true,
            'is_verified' => false,
            'price_history' => json_encode([]),
        ];

        Log::info('Attempting to create cryptocurrency', [
            'user_id' => Auth::id(),
            'data' => $data
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            try {
                $logo = $request->file('logo');
                $logoName = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
                $logoPath = $logo->storeAs('crypto-logos', $logoName, 'public');
                $data['logo'] = $logoPath;
                Log::info('Successfully uploaded logo: ' . $logoPath);
            } catch (\Exception $e) {
                Log::error('Logo upload failed: ' . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'Failed to upload logo. Please try again.')
                    ->withInput();
            }
        }

        // Create the cryptocurrency
        $cryptocurrency = Cryptocurrency::create($data);

        if ($cryptocurrency) {
            Log::info('Cryptocurrency created successfully', [
                'id' => $cryptocurrency->id,
                'name' => $cryptocurrency->name,
                'symbol' => $cryptocurrency->symbol,
            ]);

            // Create initial wallet for creator if they have allocation
            if ($creatorAllocation > 0) {
                try {
                    CryptoWallet::updateOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'cryptocurrency_id' => $cryptocurrency->id
                        ],
                        [
                            'balance' => $creatorAllocation
                        ]
                    );
                    Log::info('Creator wallet created with balance: ' . $creatorAllocation);
                } catch (\Exception $e) {
                    Log::error('Failed to create creator wallet: ' . $e->getMessage());
                }
            }

            // Create initial transaction record for token creation
            try {
                CryptoTransaction::create([
                    'cryptocurrency_id' => $cryptocurrency->id,
                    'buyer_user_id' => Auth::id(),
                    'type' => 'create',
                    'amount' => $creatorAllocation,
                    'price_per_token' => $cryptocurrency->initial_price,
                    'total_price' => $creatorAllocation * $cryptocurrency->initial_price,
                    'status' => 'completed',
                    'metadata' => json_encode([
                        'type' => 'token_creation',
                        'creator_allocation' => $creatorAllocation,
                        'total_supply' => $totalSupply
                    ])
                ]);
                Log::info('Creation transaction recorded');
            } catch (\Exception $e) {
                Log::error('Failed to create transaction: ' . $e->getMessage());
            }

            return redirect()->route('cryptocurrency.show', $cryptocurrency->id)
                ->with('success', 'Congratulations! Your token "' . $cryptocurrency->name . '" (' . $cryptocurrency->symbol . ') has been created successfully.');
        }

        Log::error('Cryptocurrency creation returned null');
        return redirect()->back()
            ->with('error', 'Failed to create cryptocurrency. Please try again.')
            ->withInput();

    } catch (\Exception $e) {
        Log::error('Cryptocurrency creation failed with exception', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'user_id' => Auth::id(),
            'request_data' => $request->except(['logo', '_token'])
        ]);

        return redirect()->back()
            ->with('error', 'An error occurred while creating your token: ' . $e->getMessage())
            ->withInput();
    }
}
    /**
     * Display the specified cryptocurrency.
     */
    // public function show($id)
    // {
    //     $cryptocurrency = Cryptocurrency::findOrFail($id);
    //     $marketData = []; // Simple empty array since no service
        
    //     // Get user wallet if exists
    //     $wallet = null;
    //     if (Auth::check()) {
    //         $wallet = CryptoWallet::where('user_id', Auth::id())
    //             ->where('cryptocurrency_id', $id)
    //             ->first();
    //     }
        
    //     return view('cryptocurrency.show', compact('cryptocurrency', 'marketData', 'wallet'));
    // }
    /**
 * Display the specified cryptocurrency - FIXED VERSION
 */
public function show($id)
{
    $cryptocurrency = Cryptocurrency::with(['creator', 'transactions' => function($query) {
        $query->latest()->take(10);
    }])->findOrFail($id);
    
    // Get user wallet if exists
    $wallet = null;
    if (Auth::check()) {
        $wallet = CryptoWallet::where('user_id', Auth::id())
            ->where('cryptocurrency_id', $id)
            ->first();
    }
    
    // Add relationship method to Cryptocurrency model if not exists
    if (!method_exists($cryptocurrency, 'transactions')) {
        // Alternative: get transactions directly
        $transactions = CryptoTransaction::where('cryptocurrency_id', $id)
            ->with(['buyer', 'seller'])
            ->latest()
            ->take(10)
            ->get();
            
        $cryptocurrency->transactions = $transactions;
    }
    
    // Calculate market data
    $marketData = [
        'total_transactions' => CryptoTransaction::where('cryptocurrency_id', $id)->count(),
        'today_transactions' => CryptoTransaction::where('cryptocurrency_id', $id)
            ->whereDate('created_at', today())
            ->count(),
        'total_volume' => CryptoTransaction::where('cryptocurrency_id', $id)
            ->sum('total_price'),
        'avg_price' => CryptoTransaction::where('cryptocurrency_id', $id)
            ->where('price_per_token', '>', 0)
            ->avg('price_per_token') ?? $cryptocurrency->current_price
    ];
    
    return view('cryptocurrency.show', compact('cryptocurrency', 'marketData', 'wallet'));
}

    /**
     * Show the form for buying cryptocurrency.
     */
    public function buyForm($id)
    {
        $cryptocurrency = Cryptocurrency::findOrFail($id);
        return view('cryptocurrency.buy', compact('cryptocurrency'));
    }

    /**
     * Process a buy transaction.
     */
    // public function buy(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'amount' => 'required|integer|min:1',
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()
    //             ->withErrors($validator)
    //             ->withInput();
    //     }

    //     $cryptocurrency = Cryptocurrency::findOrFail($id);
    //     $amount = $request->input('amount');
        
    //     // Check if there's enough supply
    //     if ($amount > $cryptocurrency->available_supply) {
    //         return redirect()->back()
    //             ->with('error', 'Not enough tokens available for purchase.')
    //             ->withInput();
    //     }

    //     // Simple buy logic without service
    //     try {
    //         // Update cryptocurrency supply
    //         $cryptocurrency->available_supply -= $amount;
    //         $cryptocurrency->circulating_supply += $amount;
    //         $cryptocurrency->save();

    //         // Update or create wallet
    //         $wallet = CryptoWallet::updateOrCreate(
    //             [
    //                 'user_id' => Auth::id(),
    //                 'cryptocurrency_id' => $id
    //             ],
    //             [
    //                 'balance' => \DB::raw('balance + ' . $amount)
    //             ]
    //         );

    //         // Create transaction
    //         CryptoTransaction::create([
    //             'cryptocurrency_id' => $id,
    //             'buyer_user_id' => Auth::id(),
    //             'type' => 'buy',
    //             'amount' => $amount,
    //             'price_per_token' => $cryptocurrency->current_price,
    //             'total_price' => $amount * $cryptocurrency->current_price,
    //             'status' => 'completed'
    //         ]);

    //         return redirect()->route('cryptocurrency.wallet')
    //             ->with('success', 'Successfully purchased ' . $amount . ' ' . $cryptocurrency->symbol . ' tokens.');

    //     } catch (\Exception $e) {
    //         Log::error('Buy transaction failed: ' . $e->getMessage());
    //         return redirect()->back()
    //             ->with('error', 'Failed to process purchase. Please try again.')
    //             ->withInput();
    //     }
    // }

    /**
     * Display the user's cryptocurrency wallet.
     */
    public function wallet()
    {
        $wallets = CryptoWallet::where('user_id', Auth::id())
            ->where('balance', '>', 0)
            ->with('cryptocurrency')
            ->get();
            
        $transactions = CryptoTransaction::where(function($query) {
                $query->where('buyer_user_id', Auth::id())
                    ->orWhere('seller_user_id', Auth::id());
            })
            ->with('cryptocurrency')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Calculate balances
        $totalBalance = 0;
        $availableBalance = 0;
        $pendingBalance = 0;
        
        foreach ($wallets as $wallet) {
            $tokenValue = $wallet->balance * $wallet->cryptocurrency->current_price;
            $totalBalance += $tokenValue;
            $availableBalance += $tokenValue;
        }
            
        return view('cryptocurrency.wallet', compact('wallets', 'transactions', 'totalBalance', 'availableBalance', 'pendingBalance'));
    }

    /**
     * Display the cryptocurrency marketplace.
     */
    public function marketplace(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', '');

        // --- Real activity, computed from the ledger ---------------------------------------
        // volume_24h on the row is a lifetime running total (buy() only ever adds to it and
        // nothing decays it), so a genuine 24h figure has to come from the transactions table.
        $volume24h = CryptoTransaction::where('created_at', '>=', now()->subDay())
            ->where('status', 'completed')
            ->groupBy('cryptocurrency_id')
            ->selectRaw('cryptocurrency_id, SUM(total_price) as total')
            ->pluck('total', 'cryptocurrency_id');

        $holders = CryptoWallet::where('balance', '>', 0)
            ->groupBy('cryptocurrency_id')
            ->selectRaw('cryptocurrency_id, COUNT(*) as total')
            ->pluck('total', 'cryptocurrency_id');

        // --- Listing ------------------------------------------------------------------------
        $query = Cryptocurrency::where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('symbol', 'like', '%' . $search . '%');
            });
        }

        // Whitelisted so a crafted ?sort= can never reach an arbitrary column.
        switch ($sort) {
            case 'price_high':  $query->orderBy('current_price', 'desc'); break;
            case 'price_low':   $query->orderBy('current_price', 'asc');  break;
            case 'market_cap':  $query->orderBy('market_cap', 'desc');    break;
            case 'newest':      $query->orderBy('created_at', 'desc');    break;
            default:            $query->orderBy('current_price', 'desc'); $sort = ''; break;
        }

        $cryptocurrencies = $query->paginate(20)->withQueryString();

        // --- Trending -----------------------------------------------------------------------
        // Ordered by REAL 24h traded value. Falls back to newest when nothing has traded, so
        // the strip is never an arbitrary ordering of an all-zero column.
        $active = Cryptocurrency::where('is_active', true)->get();
        $trending = $active
            ->sortByDesc(fn ($c) => (float) ($volume24h[$c->id] ?? 0))
            ->take(3)
            ->values();
        if ($volume24h->isEmpty()) {
            $trending = $active->sortByDesc('created_at')->take(3)->values();
        }

        // Top movers over a real 24h window; only tokens with enough history to say.
        $gainers = $active
            ->filter(fn ($c) => $c->rollingChange(24) !== null && $c->rollingChange(24) > 0)
            ->sortByDesc(fn ($c) => $c->rollingChange(24))
            ->take(3)
            ->values();

        $stats = [
            'tokens' => $active->count(),
            'volume24h' => (float) $volume24h->sum(),
            'holders' => (int) CryptoWallet::where('balance', '>', 0)->distinct('user_id')->count('user_id'),
            'marketCap' => (float) $active->sum('market_cap'),
        ];

        // What the viewer already owns of each coin, so cards can say "You own $X" in dollars.
        $myUnits = CryptoWallet::where('user_id', Auth::id())
            ->where('balance', '>', 0)
            ->pluck('balance', 'cryptocurrency_id');

        return view('cryptocurrency.marketplace', compact(
            'trending', 'cryptocurrencies', 'gainers', 'stats', 'volume24h', 'holders', 'search', 'sort', 'myUnits'
        ));
    }

    /**
     * Display the deposit page.
     */
    public function deposit()
    {
        $wallets = CryptoWallet::where('user_id', Auth::id())
            ->with('cryptocurrency')
            ->get();
        
        return view('cryptocurrency.deposit', compact('wallets'));
    }

    /**
     * Process a deposit request.
     */
    public function processDeposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:5',
            'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer,crypto',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $amount = $request->input('amount');
        
        // Create a transaction record
        $transaction = new CryptoTransaction();
        $transaction->type = 'deposit';
        $transaction->status = 'completed';
        $transaction->buyer_user_id = Auth::id();
        $transaction->amount = $amount;
        $transaction->price_per_token = 1;
        $transaction->total_price = $amount;
        $transaction->save();
        
        return redirect()->route('cryptocurrency.wallet')
            ->with('success', sprintf('Successfully deposited $%s to your wallet.', number_format($amount, 2)));
    }

    /**
     * Display the withdraw page.
     */
    public function withdraw()
    {
        $wallets = CryptoWallet::where('user_id', Auth::id())
            ->with('cryptocurrency')
            ->get();
            
        $totalBalance = 0;
        
        foreach ($wallets as $wallet) {
            $totalBalance += $wallet->balance * $wallet->cryptocurrency->current_price;
        }
        
        return view('cryptocurrency.withdraw', compact('wallets', 'totalBalance'));
    }

    /**
     * Process a withdrawal request.
     */
    public function processWithdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:20',
            'payment_method' => 'required|string|in:paypal,bank_transfer,crypto',
            'withdrawal_address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $amount = $request->input('amount');
        
        // Create a transaction record
        $transaction = new CryptoTransaction();
        $transaction->type = 'withdraw';
        $transaction->status = 'pending';
        $transaction->seller_user_id = Auth::id();
        $transaction->amount = $amount;
        $transaction->price_per_token = 1;
        $transaction->total_price = $amount;
        $transaction->save();
        
        return redirect()->route('cryptocurrency.wallet')
            ->with('success', sprintf('Your withdrawal request for $%s has been submitted and is pending approval.', number_format($amount, 2)));
    }

    /**
     * Display transaction history filtered by type.
     */
    public function transactions($type = 'all')
    {
        $query = CryptoTransaction::where(function($q) {
                $q->where('buyer_user_id', Auth::id())
                  ->orWhere('seller_user_id', Auth::id());
            })
            ->with('cryptocurrency');
            
        if ($type !== 'all') {
            $query->where('type', $type);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('cryptocurrency.transactions', compact('transactions', 'type'));
    }

    /**
     * Show the form for selling cryptocurrency.
     */
    public function sellForm($id)
    {
        $cryptocurrency = Cryptocurrency::findOrFail($id);
        
        $wallet = CryptoWallet::where('user_id', Auth::id())
            ->where('cryptocurrency_id', $id)
            ->first();
            
        if (!$wallet || $wallet->balance <= 0) {
            return redirect()->route('cryptocurrency.wallet')
                ->with('error', 'You do not have any ' . $cryptocurrency->name . ' to sell.');
        }
        
        return view('cryptocurrency.sell', compact('cryptocurrency', 'wallet'));
    }

    /**
     * Process a sell transaction.
     */
    // public function processSell(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'amount' => 'required|integer|min:1',
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()
    //             ->withErrors($validator)
    //             ->withInput();
    //     }

    //     $cryptocurrency = Cryptocurrency::findOrFail($id);
    //     $amount = $request->input('amount');
        
    //     // Get user's wallet
    //     $wallet = CryptoWallet::where('user_id', Auth::id())
    //         ->where('cryptocurrency_id', $id)
    //         ->first();
            
    //     if (!$wallet || $wallet->balance < $amount) {
    //         return redirect()->back()
    //             ->with('error', 'You do not have enough tokens to sell.')
    //             ->withInput();
    //     }
        
    //     // Calculate proceeds
    //     $price = $cryptocurrency->current_price;
    //     $totalProceeds = $price * $amount;
        
    //     // Update wallet balance
    //     $wallet->balance -= $amount;
    //     $wallet->save();
        
    //     // Update cryptocurrency available supply
    //     $cryptocurrency->available_supply += $amount;
    //     $cryptocurrency->save();
        
    //     // Create sell transaction
    //     $transaction = new CryptoTransaction();
    //     $transaction->cryptocurrency_id = $id;
    //     $transaction->seller_user_id = Auth::id();
    //     $transaction->type = 'sell';
    //     $transaction->amount = $amount;
    //     $transaction->price_per_token = $price;
    //     $transaction->total_price = $totalProceeds;
    //     $transaction->status = 'completed';
    //     $transaction->save();
        
    //     return redirect()->route('cryptocurrency.wallet')
    //         ->with('success', 'Successfully sold ' . $amount . ' ' . $cryptocurrency->symbol . ' tokens for $' . number_format($totalProceeds, 2) . '.');
    // }
    /**
 * Process a sell transaction - Quick Fix Version
 */
/**
     * Process a buy transaction.
     */
    public function buy(Request $request, $id)
    {
        // People buy in dollars; the unit amount is worked out here (see App\Support\TokenMoney).
        $validator = Validator::make($request->all(), [
            'amount_usd' => 'required|numeric|min:1|max:10000',
        ], [
            'amount_usd.required' => 'Enter how many dollars you want to buy.',
            'amount_usd.min' => 'The smallest amount is $1.',
            'amount_usd.max' => 'The most you can buy at once is $10,000.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $cryptocurrency = Cryptocurrency::findOrFail($id);
        $usd = round((float) $request->input('amount_usd'), 2);

        try {
            $amount = TokenMoney::toUnits($usd, (float) $cryptocurrency->current_price);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        if ($amount <= 0) {
            return redirect()->back()->with('error', 'That amount is too small to buy.')->withInput();
        }

        // Check if there's enough supply
        if ($amount > $cryptocurrency->available_supply) {
            return redirect()->back()
                ->with('error', 'There is only ' . TokenMoney::format(TokenMoney::toUsd((float) $cryptocurrency->available_supply, (float) $cryptocurrency->current_price)) . ' of ' . $cryptocurrency->name . ' available right now.')
                ->withInput();
        }

        try {
            \DB::beginTransaction();
            
            // Calculate total cost
            $pricePerToken = $cryptocurrency->current_price;
            $totalCost = $amount * $pricePerToken;
            
            // Update cryptocurrency supply
            $cryptocurrency->available_supply -= $amount;
            $cryptocurrency->circulating_supply += $amount;
            $cryptocurrency->volume_24h += $totalCost;
            $cryptocurrency->save();

            // Update or create wallet
            $wallet = CryptoWallet::where('user_id', Auth::id())
                ->where('cryptocurrency_id', $id)
                ->first();
                
            if ($wallet) {
                $wallet->balance += $amount;
                $wallet->save();
            } else {
                CryptoWallet::create([
                    'user_id' => Auth::id(),
                    'cryptocurrency_id' => $id,
                    'balance' => $amount
                ]);
            }

            // Create transaction
            CryptoTransaction::create([
                'cryptocurrency_id' => $id,
                'buyer_user_id' => Auth::id(),
                'type' => 'buy',
                'amount' => $amount,
                'price_per_token' => $pricePerToken,
                'total_price' => $totalCost,
                'status' => 'completed'
            ]);
            
            \DB::commit();
            
            Log::info('Buy transaction completed', [
                'user_id' => Auth::id(),
                'cryptocurrency_id' => $id,
                'amount' => $amount,
                'total_cost' => $totalCost
            ]);

            return redirect()->route('cryptocurrency.show', $cryptocurrency->id)
                ->with('success', 'You bought ' . TokenMoney::format($totalCost) . ' of ' . $cryptocurrency->name . '.');

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Buy transaction failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to process purchase. Please try again.')
                ->withInput();
        }
    }

    /**
     * Process a sell transaction.
     */
   /**
 * Process a sell transaction - FIXED VERSION
 */
public function processSell(Request $request, $id)
{
    // People sell in dollars (or "sell all"); the unit amount is worked out here.
    $validator = Validator::make($request->all(), [
        'amount_usd' => 'required_unless:sell_all,1|nullable|numeric|min:0.01',
        'sell_all' => 'nullable|in:0,1',
    ], [
        'amount_usd.required_unless' => 'Enter how many dollars you want to sell.',
        'amount_usd.min' => 'Enter an amount above $0.',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    $cryptocurrency = Cryptocurrency::findOrFail($id);
    $price = (float) $cryptocurrency->current_price;

    // Get user's wallet
    $wallet = CryptoWallet::where('user_id', Auth::id())
        ->where('cryptocurrency_id', $id)
        ->first();

    if (!$wallet || (float) $wallet->balance <= 0) {
        return redirect()->back()
            ->with('error', 'You do not have any ' . $cryptocurrency->name . ' to sell.')
            ->withInput();
    }

    $holdingUsd = TokenMoney::toUsd((float) $wallet->balance, $price);
    if ($request->input('sell_all') === '1') {
        // Exactly what they hold, so no tiny leftover is stranded by rounding.
        $amount = (float) $wallet->balance;
    } else {
        $usd = round((float) $request->input('amount_usd'), 2);
        if ($usd > $holdingUsd) {
            return redirect()->back()
                ->with('error', 'You only have ' . TokenMoney::format($holdingUsd) . ' of ' . $cryptocurrency->name . '.')
                ->withInput();
        }
        try {
            $amount = min((float) $wallet->balance, TokenMoney::toUnits($usd, $price));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    if ($amount <= 0) {
        return redirect()->back()->with('error', 'That amount is too small to sell.')->withInput();
    }

    try {
        \DB::beginTransaction();
        
        // Calculate proceeds and fees
        $pricePerToken = $cryptocurrency->current_price;
        $grossAmount = $amount * $pricePerToken;
        
        // Calculate fees
        $platformFeePercent = $cryptocurrency->platform_fee_percentage ?? 2.5;
        $creatorFeePercent = $cryptocurrency->creator_fee_percentage ?? 0;
        
        $platformFee = $grossAmount * ($platformFeePercent / 100);
        $creatorFee = $grossAmount * ($creatorFeePercent / 100);
        $netProceeds = $grossAmount - $platformFee - $creatorFee;
        
        // Update wallet balance
        $wallet->balance -= $amount;
        $wallet->save();
        
        // Update cryptocurrency available supply
        $cryptocurrency->available_supply += $amount;
        $cryptocurrency->circulating_supply = max(0, (float) $cryptocurrency->circulating_supply - $amount);
        $cryptocurrency->volume_24h += $grossAmount;
        $cryptocurrency->save();
        
        // Create sell transaction - FIXED: Match database column names
        $transaction = CryptoTransaction::create([
            'cryptocurrency_id' => $id,
            'seller_user_id' => Auth::id(),
            'type' => 'sell',
            'amount' => $amount,
            'price_per_token' => $pricePerToken,  // This matches DB column
            'total_price' => $grossAmount,
            'fee_amount' => $platformFee + $creatorFee,  // Use fee_amount column instead of separate fees
            'status' => 'completed',
            'notes' => json_encode([
                'gross_amount' => $grossAmount,
                'platform_fee' => $platformFee,
                'creator_fee' => $creatorFee,
                'net_proceeds' => $netProceeds,
                'fee_percentages' => [
                    'platform' => $platformFeePercent,
                    'creator' => $creatorFeePercent
                ]
            ]),
            'transaction_hash' => 'sell_' . time() . '_' . substr(md5(uniqid()), 0, 20)
        ]);
        
        \DB::commit();
        
        Log::info('Sell transaction completed', [
            'transaction_id' => $transaction->id,
            'user_id' => Auth::id(),
            'cryptocurrency_id' => $id,
            'amount' => $amount,
            'gross_amount' => $grossAmount,
            'net_proceeds' => $netProceeds
        ]);
        
        return redirect()->route('cryptocurrency.show', $cryptocurrency->id)
            ->with('success', 'You sold ' . TokenMoney::format($grossAmount) . ' of ' . $cryptocurrency->name . '.');
            
    } catch (\Exception $e) {
        \DB::rollBack();
        
        Log::error('Sell transaction failed', [
            'user_id' => Auth::id(),
            'cryptocurrency_id' => $id,
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()
            ->with('error', 'Failed to process sell transaction: ' . $e->getMessage())
            ->withInput();
    }
}
}