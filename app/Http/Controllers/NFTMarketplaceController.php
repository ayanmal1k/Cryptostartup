<?php

namespace App\Http\Controllers;

use App\Jobs\MintNft;
use App\Models\NFT;
use App\Models\NFTListing;
use App\Models\NFTTransaction;
use App\Models\Video;
use App\Services\Nft\CreditsMarketplaceService;
use App\Services\Nft\Contracts\MarketplaceService;
use App\Services\Nft\Contracts\MetadataStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NFTMarketplaceController extends Controller
{
    protected MetadataStorageService $storage;
    protected MarketplaceService $marketplace;

    public function __construct(MetadataStorageService $storage, MarketplaceService $marketplace)
    {
        $this->storage = $storage;
        $this->marketplace = $marketplace;
    }

    /**
     * Persist the current user's on-chain wallet address (from thirdweb embedded wallet,
     * an injected wallet like MetaMask, or a dev-simulated address). AJAX endpoint.
     */
    public function connectWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => 'Invalid wallet address.'], 422);
        }

        $user = Auth::user();
        $user->wallet_address = $request->wallet_address;
        $user->save();

        return response()->json(['ok' => true, 'wallet_address' => $user->wallet_address]);
    }

    /**
     * Unlink the current user's wallet address. AJAX endpoint.
     */
    public function disconnectWallet(Request $request)
    {
        $user = Auth::user();
        $user->wallet_address = null;
        $user->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Marketplace browse. `explore` (default) shows every NFT that exists on-chain with
     * for-sale items first; `?view=sale` shows active listings only, sortable by price.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $view = $request->query('view') === 'sale' ? 'sale' : 'explore';
        $sort = (string) $request->query('sort', 'newest');

        $live = [NFT::STATUS_MINTED, NFT::STATUS_LISTED, NFT::STATUS_SOLD, NFT::STATUS_TRANSFERRED];

        $matchesSearch = function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%');
        };

        if ($view === 'sale') {
            $query = NFTListing::where('status', 'active')
                ->whereHas('nft')
                ->with(['nft.creator', 'seller']);

            // Search matches the NFT itself, not the listing row.
            if ($search !== '') {
                $query->whereHas('nft', $matchesSearch);
            }

            // Whitelist sorts so a crafted ?sort= can't reach an arbitrary column.
            switch ($sort) {
                case 'price_low':  $query->orderBy('price', 'asc');  break;
                case 'price_high': $query->orderBy('price', 'desc'); break;
                default:           $query->orderBy('created_at', 'desc'); $sort = 'newest'; break;
            }

            // Normalise to NFTs so both views render the same card.
            $items = $query->paginate(20)->withQueryString();
            $items->setCollection($items->getCollection()->map(function ($listing) {
                $nft = $listing->nft;
                $nft->setRelation('activeListing', $listing);

                return $nft;
            }));
        } else {
            // Explore shows everything that exists on-chain, not only what is for sale — an
            // unlisted 1-of-1 is still something to look at, and a market with nothing listed
            // would otherwise open on an empty page. For-sale items float to the top.
            $query = NFT::whereIn('status', $live)->with(['creator', 'activeListing']);

            if ($search !== '') {
                $query->where($matchesSearch);
            }

            $items = $query
                ->orderByRaw("CASE WHEN status = 'listed' THEN 0 ELSE 1 END")
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString();
        }

        // Spotlight: the newest listing if anything is for sale, else the newest mint.
        $spotlight = null;
        if ($search === '' && $items->currentPage() === 1) {
            $spotlight = NFT::whereIn('status', $live)
                ->whereHas('activeListing')
                ->with(['creator', 'activeListing'])
                ->latest()
                ->first()
                ?? NFT::whereIn('status', $live)->with(['creator', 'activeListing'])->latest()->first();
        }

        // Real figures only; the view hides any that are zero rather than advertising emptiness.
        $stats = [
            'minted' => NFT::whereIn('status', $live)->count(),
            'listed' => NFTListing::where('status', 'active')->count(),
            'floor' => (float) NFTListing::where('status', 'active')->min('price'),
            'owners' => NFT::whereNotNull('owner_address')->distinct('owner_address')->count('owner_address'),
            'volume' => (float) NFTTransaction::whereIn('type', ['sale', 'resale'])->sum('price'),
        ];

        // The viewer's own uploads that are not NFTs yet — the page's way in for creators.
        $mintable = collect();
        if (Auth::check() && $search === '' && $view === 'explore') {
            $minted = NFT::where('source_type', 'video')->pluck('source_id')->map(fn ($id) => (string) $id)->all();
            $mintable = Video::where('user_id', Auth::id())
                ->whereNotIn('id', $minted)
                ->latest()
                ->limit(12)
                ->get();
        }

        $economics = [
            'fee_pct' => (int) config('web3.marketplace_fee_bps', 250) / 100,
            'royalty_pct' => (int) config('web3.default_royalty_bps', 1000) / 100,
            // Dollars when sales settle in platform credits; the chain's token otherwise.
            'currency' => NFT::settlesInCredits() ? '$' : config('web3.currency_symbol', 'POL'),
            'prefix' => NFT::settlesInCredits(),
        ];

        return view('nft.marketplace', compact(
            'items', 'spotlight', 'stats', 'mintable', 'economics', 'search', 'sort', 'view'
        ));
    }

    /**
     * Show the mint form.
     */
    public function create()
    {
        return view('nft.create');
    }

    /**
     * Mint a new NFT to the current user's wallet.
     *
     * Minting establishes OWNERSHIP only — it does not list the token for sale. The asset and
     * metadata are pinned to storage (IPFS in production), an NFT row is created in
     * `pending_mint`, and a MintNft job performs the on-chain mint and mirrors the real token
     * id + owner address back. Listing for sale is a separate marketplace action (later phase).
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'price' => 'nullable|numeric|min:0', // optional asking price hint for later listing
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (empty($user->wallet_address)) {
            return back()
                ->with('error', 'Connect your wallet before minting — an NFT must be owned by a wallet address.')
                ->withInput();
        }

        try {
            // 1. Persist the asset, then pin it to storage (IPFS in prod, public disk locally).
            $imagePath = $request->file('image')->store('nfts', 'public');
            $imageUri = $this->storage->uploadFile($imagePath, 'public');

            // 2. Pin ERC-721 metadata; its URI becomes the tokenURI.
            $metadataUri = $this->storage->uploadMetadata([
                'name' => $request->name,
                'description' => (string) $request->description,
                'image' => $imageUri,
                'attributes' => [],
            ]);

            // 3. Create the pending record (chain is the source of truth once minted).
            $nft = NFT::create([
                'user_id' => $user->id,
                'owner_address' => $user->wallet_address,
                'name' => $request->name,
                'description' => $request->description,
                'token_uri' => $metadataUri,
                'metadata_uri' => $metadataUri,
                'image_url' => $imageUri,
                'chain_id' => config('web3.chain_id'),
                'contract_address' => config('web3.contract_address') ?: null,
                'status' => NFT::STATUS_PENDING_MINT,
                'metadata' => ['price_hint' => $request->price],
            ]);

            // 4. Mint on-chain (async via the configured provider).
            MintNft::dispatch($nft->id, $user->wallet_address);

            return redirect()->route('nft.my-nfts')
                ->with('success', 'Your NFT is being minted. It will show as owned once the blockchain confirms.');
        } catch (\Throwable $e) {
            Log::error('NFT mint failed to start', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Could not start minting: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show a specific NFT, including current owner and provenance.
     */
    public function show($id)
    {
        $nft = NFT::with(['creator', 'owner', 'activeListing'])->findOrFail($id);
        $listing = $nft->activeListing;

        // Sales history in plain terms (who bought it, for how much, when) — no hashes.
        $sales = $nft->transactions()
            ->whereIn('type', ['sale', 'resale'])
            ->where('status', 'confirmed')
            ->latest()
            ->limit(10)
            ->get();
        $buyers = \App\Model\User::whereIn('id', $sales->pluck('buyer_id')->filter()->unique())->get()->keyBy('id');

        $balance = (float) (optional(Auth::user()->wallet)->total ?? 0);
        $feePct = (int) config('web3.marketplace_fee_bps', 250) / 100;

        // Every sale ever (the list above is only the latest 10) for the Details card.
        $saleStats = $nft->transactions()
            ->whereIn('type', ['sale', 'resale'])
            ->where('status', 'confirmed')
            ->selectRaw('COUNT(*) as times, MAX(price) as highest')
            ->first();

        $moreFromCreator = NFT::with(['creator', 'activeListing'])
            ->where('user_id', $nft->user_id)
            ->where('id', '!=', $nft->id)
            ->where('status', '!=', NFT::STATUS_MINT_FAILED)
            ->latest()
            ->limit(4)
            ->get();

        return view('nft.show', compact('nft', 'listing', 'sales', 'buyers', 'balance', 'feePct', 'saleStats', 'moreFromCreator'));
    }

    /**
     * List an owned NFT for sale at a price (native token).
     */
    public function list(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['price' => $this->priceRule()]);
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $nft = NFT::findOrFail($id);
        try {
            $this->marketplace->list($nft, Auth::user(), (float) $request->price);

            return redirect()->route('nft.show', $nft->id)->with('success', 'Listed for sale.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an active listing (seller only).
     */
    public function cancelListing(Request $request, $id)
    {
        $listing = NFTListing::findOrFail($id);
        try {
            $this->marketplace->cancel($listing, Auth::user());

            return redirect()->route('nft.show', $listing->nft_id)->with('success', 'Listing cancelled.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Buy an active listing — ownership transfers to the buyer with the fee/royalty split recorded.
     */
    public function buy(Request $request, $id)
    {
        $listing = NFTListing::where('id', $id)->where('status', 'active')->firstOrFail();
        try {
            $tx = $this->marketplace->buy($listing, Auth::user());

            return redirect()->route('nft.show', $tx->nft_id)->with('success', 'Purchase complete — you now own this NFT.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resell an owned NFT: re-list it for sale (same as listing).
     */
    public function resell(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['price' => $this->priceRule()]);
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $nft = NFT::findOrFail($id);
        try {
            $this->marketplace->list($nft, Auth::user(), (float) $request->price);

            return redirect()->route('nft.my-listings')->with('success', 'Listed for sale.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * NFTs currently owned by the user (mirrored from chain by owner_address; falls back to
     * created-by while wallets are still being linked).
     */
    public function myNFTs()
    {
        $user = Auth::user();

        // Ownership is the account (owner_user_id); a linked wallet's on-chain holdings still count.
        $nfts = NFT::query()
            ->with(['activeListing', 'creator'])
            ->where('status', '!=', NFT::STATUS_MINT_FAILED)
            ->where(function ($q) use ($user) {
                $q->ownedByUser((int) $user->id);
                if (!empty($user->wallet_address)) {
                    $q->orWhere(fn ($w) => $w->ownedByAddress($user->wallet_address));
                }
            })
            ->orderByDesc('created_at')
            ->paginate(24);

        return view('nft.my-nfts', compact('nfts'));
    }

    /** Listing price rule: dollars (at least $1) in credit mode, token units otherwise. */
    private function priceRule(): string
    {
        return NFT::settlesInCredits()
            ? 'required|numeric|min:' . CreditsMarketplaceService::MIN_PRICE . '|max:' . CreditsMarketplaceService::MAX_PRICE
            : 'required|numeric|min:0.000001';
    }

    /**
     * The user's marketplace listings.
     */
    public function myListings()
    {
        $listings = NFTListing::where('seller_id', Auth::id())
            ->with(['nft.creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('nft.my-listings', compact('listings'));
    }

    /**
     * API: contract ABI (for the frontend Connect SDK), if compiled artifacts exist.
     */
    public function getContractAbi()
    {
        $abiPath = base_path('contracts/artifacts/contracts/NFTMarketplace.sol/NFTMarketplace.json');

        if (file_exists($abiPath)) {
            $artifact = json_decode(file_get_contents($abiPath), true);
            return response()->json([
                'abi' => $artifact['abi'] ?? [],
                'contract_address' => config('web3.contract_address'),
                'chain_id' => config('web3.chain_id'),
            ]);
        }

        return response()->json(['error' => 'Contract ABI not found'], 404);
    }

    /**
     * API: informational listing fee.
     */
    public function getListingPrice()
    {
        return response()->json(['listing_price' => config('web3.listing_price')]);
    }
}
