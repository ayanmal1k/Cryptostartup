<?php

namespace App\Http\Controllers;

use App\Model\Attachment;
use App\Models\NFT;
use App\Models\Video;
use App\Services\Nft\MediaNftService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class MediaNftController extends Controller
{
    protected MediaNftService $service;

    public function __construct(MediaNftService $service)
    {
        $this->service = $service;
    }

    /** Gallery of the user's own content that can be turned into NFTs. */
    public function mintable()
    {
        $userId = Auth::id();

        $videos = Video::where('user_id', $userId)->latest()->limit(60)->get();
        $images = Attachment::where('user_id', $userId)->where('type', 'image')->latest()->limit(60)->get();

        // source-key => NFT, so the view can show a "Minted" badge + link.
        $mintedMap = NFT::whereIn('source_type', ['video', 'attachment'])
            ->get(['id', 'source_type', 'source_id'])
            ->keyBy(fn ($n) => $n->source_type . ':' . $n->source_id);

        return view('nft.mintable', compact('videos', 'images', 'mintedMap'));
    }

    /** Confirm screen for minting one item. */
    public function confirm($type, $id)
    {
        try {
            $media = $this->service->resolve($type, $id);
        } catch (\Throwable $e) {
            return redirect()->route('nft.mintable')->with('error', $e->getMessage());
        }

        if ((int) $media->ownerId !== (int) Auth::id()) {
            return redirect()->route('nft.mintable')->with('error', 'You can only mint your own content.');
        }

        if ($existing = NFT::mintedFor($media->sourceType, $media->sourceId)) {
            return redirect()->route('nft.show', $existing->id)->with('info', 'This item is already an NFT.');
        }

        $defaultRoyaltyPercent = (int) config('web3.default_royalty_bps', 1000) / 100;

        return view('nft.mint-from', compact('media', 'type', 'id', 'defaultRoyaltyPercent'));
    }

    /** Perform the mint. */
    public function mint(Request $request, $type, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'royalty_percent' => 'required|numeric|min:0|max:50',
            'price' => 'nullable|numeric|min:0',
            'confirm_original' => 'accepted',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $media = $this->service->resolve($type, $id);
            $this->service->mintFromMedia(Auth::user(), $media, [
                'name' => $request->name,
                'description' => $request->description,
                'royalty_bps' => (int) round((float) $request->royalty_percent * 100),
                'price_hint' => $request->price,
            ]);

            return redirect()->route('nft.my-nfts')
                ->with('success', 'Your content is being minted into an NFT. It will show as owned once confirmed.');
        } catch (\Throwable $e) {
            Log::error('Media NFT mint failed', ['type' => $type, 'id' => $id, 'error' => $e->getMessage()]);

            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
