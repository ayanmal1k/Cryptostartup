<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Stream;
use App\Providers\PostsHelperServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Livestreams: browse, set up, the creator's studio, and the viewer page with chat.
 *
 * No video service is connected yet (hls_url is never set), so a stream can go "live"
 * -- it shows in the browse list, on the profile and with working chat -- while the
 * player explains that video is still being switched on. When a provider is chosen,
 * it only has to fill in hls_url (and hand the studio an ingest URL); nothing else
 * here changes.
 */
class StreamController extends Controller
{
    /** Live now, plus streams that ended in the last week. */
    public function index()
    {
        $live = Stream::with('user')
            ->where('is_live', true)
            ->where('is_public', true)
            ->orderByDesc('viewer_count')
            ->orderByDesc('started_at')
            ->get();

        $recent = Stream::with('user')
            ->where('is_live', false)
            ->where('status', 'ended')
            ->where('is_public', true)
            ->where('ended_at', '>=', now()->subDays(7))
            ->orderByDesc('ended_at')
            ->take(12)
            ->get();

        $mine = Stream::where('user_id', Auth::id())->where('is_live', true)->first();

        return view('streams.index', compact('live', 'recent', 'mine'));
    }

    public function create()
    {
        if ($redirect = $this->requireVerified()) {
            return $redirect;
        }

        return view('streams.create');
    }

    public function store(Request $request)
    {
        if ($redirect = $this->requireVerified()) {
            return $redirect;
        }

        $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'audience' => 'nullable|in:everyone,subscribers',
        ]);

        $stream = new Stream();
        $stream->user_id = Auth::id();
        $stream->title = $request->title;
        $stream->description = $request->description;
        $stream->slug = Str::slug($request->title) . '-' . Str::random(5);
        $stream->requires_subscription = $request->input('audience') === 'subscribers';
        $stream->is_public = true;
        $stream->price = 0;
        $stream->status = 'pending';
        $stream->is_live = false;

        if ($request->hasFile('thumbnail')) {
            $stream->thumbnail = $request->file('thumbnail')->store('thumbnails/streams', 'public');
        }

        $stream->save();
        $stream->generateStreamKey();

        return redirect()->route('streams.broadcast', $stream);
    }

    /** Old links land here: the owner goes to their studio, everyone else to the viewer page. */
    public function show(Stream $stream)
    {
        return (int) $stream->user_id === (int) Auth::id()
            ? redirect()->route('streams.broadcast', $stream)
            : redirect()->route('streams.watch', $stream);
    }

    public function broadcast(Stream $stream)
    {
        if ((int) $stream->user_id !== (int) Auth::id()) {
            abort(403, 'You are not authorized to broadcast this stream.');
        }

        $stream->load('user');

        return view('streams.broadcast', compact('stream'));
    }

    /** The viewer page. Renders an ended / not-started state instead of bouncing away. */
    public function watch(Stream $stream)
    {
        $stream->load('user');
        $isOwner = (int) $stream->user_id === (int) Auth::id();
        $locked = !$isOwner && $stream->requires_subscription
            && !PostsHelperServiceProvider::hasActiveSub(Auth::id(), $stream->user_id);

        if ($stream->is_live && !$isOwner && !$locked) {
            $stream->increment('viewer_count');
            if ($stream->viewer_count > (int) $stream->peak_viewer_count) {
                $stream->forceFill(['peak_viewer_count' => $stream->viewer_count])->save();
            }
        }

        return view('streams.watch', compact('stream', 'isOwner', 'locked'));
    }

    /** Studio "Go live". One live stream per creator: any other one is ended first. */
    public function start(Request $request, Stream $stream)
    {
        $this->authorizeOwner($stream);

        Stream::where('user_id', $stream->user_id)
            ->where('id', '!=', $stream->id)
            ->where('is_live', true)
            ->get()
            ->each->end();

        $stream->viewer_count = 0;
        $stream->start();

        return $request->expectsJson()
            ? response()->json(['success' => true, 'started_at' => $stream->started_at->toIso8601String()])
            : redirect()->route('streams.broadcast', $stream);
    }

    public function end(Request $request, Stream $stream)
    {
        if ((int) $stream->user_id !== (int) Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'You are not authorized to end this stream.');
        }

        $stream->end();

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : redirect()->route('creator.streams')->with('success', __('Stream ended.'));
    }

    /**
     * Chat, polled by the studio and the viewer page. Returns messages newer than
     * ?after=<id>, plus the live state so a viewer's page notices the stream ending.
     */
    public function messages(Request $request, Stream $stream)
    {
        $after = (int) $request->query('after', 0);

        $messages = ChatMessage::with('user:id,name,username,avatar')
            ->where('stream_id', $stream->id)
            ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->orderByDesc('id')
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => $this->presentMessage($m, $stream->user_id));

        return response()->json([
            'messages' => $messages,
            'is_live' => (bool) $stream->fresh()->is_live,
            'viewers' => (int) $stream->viewer_count,
        ]);
    }

    public function addMessage(Request $request, Stream $stream)
    {
        $request->validate(['message' => 'required|string|max:500']);

        if (!$stream->is_live) {
            return response()->json(['success' => false, 'message' => __('This stream is not live.')], 422);
        }
        if ((int) $stream->user_id !== (int) Auth::id() && $stream->requires_subscription
            && !PostsHelperServiceProvider::hasActiveSub(Auth::id(), $stream->user_id)) {
            return response()->json(['success' => false, 'message' => __('Subscribe to chat in this stream.')], 403);
        }

        $message = ChatMessage::create([
            'stream_id' => $stream->id,
            'user_id' => Auth::id(),
            'message' => trim($request->message),
        ]);
        $message->load('user:id,name,username,avatar');

        return response()->json(['success' => true, 'message' => $this->presentMessage($message, $stream->user_id)]);
    }

    private function presentMessage(ChatMessage $m, $creatorId): array
    {
        return [
            'id' => $m->id,
            'text' => $m->message,
            'name' => optional($m->user)->name ?? __('Someone'),
            'username' => optional($m->user)->username,
            'avatar' => optional($m->user)->avatar,
            'is_creator' => (int) $creatorId === (int) $m->user_id,
            'at' => optional($m->created_at)->toIso8601String(),
        ];
    }

    private function authorizeOwner(Stream $stream): void
    {
        if ((int) $stream->user_id !== (int) Auth::id()) {
            abort(403, 'You are not authorized to manage this stream.');
        }
    }

    /** Going live needs a verified ID. */
    private function requireVerified()
    {
        $verification = Auth::user()->verification;
        if (!$verification || $verification->status !== 'verified') {
            return redirect()->route('my.settings', ['type' => 'verify'])
                ->with('error', __('Verify your ID to go live. It only takes a couple of minutes.'));
        }

        return null;
    }
}
