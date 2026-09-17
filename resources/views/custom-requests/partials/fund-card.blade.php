@php
    /**
     * Funding card for an open challenge.
     * $cr - CustomRequest with `creator` loaded and `backers_count`
     */
    $goal = (float) ($cr->goal_amount ?? 0);
    $raised = (float) ($cr->current_amount ?? 0);
    $pct = $goal > 0 ? (int) min(100, round($raised / $goal * 100)) : 0;
    $left = max(0, $goal - $raised);
    $backers = (int) ($cr->backers_count ?? 0);
    $creator = $cr->creator;
    $url = route('custom-requests.show', $cr->id);
@endphp
<article class="ch-fund">
    <a href="{{ $url }}" class="ch-fund__link" aria-label="{{ $cr->title }}"></a>

    <div class="ch-fund__head">
        @if($creator)
            <img src="{{ $creator->avatar }}" alt="" class="dx-avatar" loading="lazy">
        @endif
        <div class="ch-fund__who">
            <span class="ch-fund__name">{{ $creator->name ?? __('Unknown') }}</span>
            <span class="ch-fund__when">{{ optional($cr->created_at)->diffForHumans() }}</span>
        </div>
        @if($goal > 0 && $pct >= 75 && $pct < 100)
            <span class="ch-fund__hot">{{ __('Almost there') }}</span>
        @endif
    </div>

    <h3 class="ch-fund__title">{{ $cr->title }}</h3>

    @if($goal > 0)
        <div>
            <div class="ch-fund__money">
                <strong>${{ number_format($raised, 0) }}</strong>
                <span>{{ __('of') }} ${{ number_format($goal, 0) }}</span>
                <span class="ch-fund__pct">{{ $pct }}%</span>
            </div>
            <div class="dx-progress" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div class="dx-progress__fill" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    @endif

    <div class="ch-fund__foot">
        <span>
            @if($goal > 0 && $left > 0)
                <strong>${{ number_format($left, 0) }}</strong> {{ __('to go') }}
            @elseif($goal > 0)
                <strong>{{ __('Goal reached') }}</strong>
            @endif
            @if($backers > 0)
                · {{ trans_choice('{1} :count backer|[2,*] :count backers', $backers, ['count' => $backers]) }}
            @endif
        </span>
        <a href="{{ $url }}#contribute" class="dx-btn dx-btn--money dx-btn--sm">{{ __('Chip in') }}</a>
    </div>
</article>
