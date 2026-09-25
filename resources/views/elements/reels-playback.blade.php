{{--
    Safe play/pause for the reels feeds.

    Fixes "AbortError: The play() request was interrupted by a call to pause()".

    play() is asynchronous and returns a promise. In a snapping feed the
    IntersectionObserver plays a video as it scrolls in and pauses it as it scrolls
    out, and a quick swipe does both before the play promise has settled -- which
    aborts it and rejects. Every call site in this codebase calls play() and pause()
    as if they were synchronous, so the rejection surfaced as a console error on
    every fast scroll.

    Patching the two methods once is deliberate. The alternative is editing ~27 call
    sites across three reels views plus inline onmouseover="this.play()" attributes,
    and any new call site would reintroduce the bug. The change is only ever to make
    the pair behave correctly: a pause issued while a play is still starting waits
    for it and is then applied, instead of tearing it up mid-flight.

    Safe here because nothing in these views reads .paused in the same tick as a
    pause() call -- the reads are all in tap-to-toggle handlers and rAF loops.
--}}
<script>
(function () {
    'use strict';

    if (typeof HTMLMediaElement === 'undefined') return;

    var proto = HTMLMediaElement.prototype;
    if (proto.__reelsSafePlayback) return;
    proto.__reelsSafePlayback = true;

    var rawPlay = proto.play;
    var rawPause = proto.pause;

    proto.play = function () {
        var el = this;

        // Record intent first, so a pause that lands while this play is still
        // starting can be reconciled rather than racing it.
        el.__reelsWant = 'play';

        if (el.__reelsPending) {
            return el.__reelsPending;
        }

        var p;
        try {
            p = rawPlay.call(el);
        } catch (e) {
            return Promise.reject(e);
        }

        // Older browsers return undefined rather than a promise.
        if (!p || typeof p.then !== 'function') {
            return p;
        }

        el.__reelsPending = p.then(function () {
            el.__reelsPending = null;
            // A pause arrived while we were starting -- apply it now.
            if (el.__reelsWant === 'pause') {
                rawPause.call(el);
            }
        }, function (err) {
            el.__reelsPending = null;
            // AbortError is the very thing this wrapper exists to absorb; it means
            // the user scrolled on, which is not a failure worth reporting.
            if (err && err.name === 'AbortError') {
                return;
            }
            // Anything else (NotAllowedError from autoplay policy, decode errors)
            // still reaches the call site's own .catch, which is a real diagnostic.
            throw err;
        });

        return el.__reelsPending;
    };

    proto.pause = function () {
        var el = this;
        el.__reelsWant = 'pause';

        // Deferred: the play handler above applies it once the promise settles.
        if (el.__reelsPending) {
            return;
        }

        return rawPause.call(el);
    };
})();
</script>
