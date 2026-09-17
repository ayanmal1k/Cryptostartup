{{-- The create-request modal only exists for signed-in users (layouts/generic), so guests go to login. --}}
@auth
    <button type="button" class="dx-btn dx-btn--primary" onclick="CustomRequest.showCreateModal()">{{ __('Dare a creator') }}</button>
@else
    <a href="{{ route('login') }}" class="dx-btn dx-btn--primary">{{ __('Dare a creator') }}</a>
@endauth
