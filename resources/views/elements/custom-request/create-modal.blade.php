@php
    $crModalDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

<link rel="stylesheet" href="{{ asset('css/pages/custom-request-modal.css') }}?v=20260713b">

<div class="modal fade" tabindex="-1" role="dialog" id="createCustomRequestModal" aria-labelledby="createCustomRequestTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable cr-modal-dialog" role="document">
        <div class="modal-content cr-modal {{ $crModalDark ? 'cr-modal--dark' : 'cr-modal--light' }}">
            <div class="cr-modal__header">
                <div class="cr-modal__header-text">
                    <h5 class="cr-modal__title" id="createCustomRequestTitle">{{ __('Create Custom Request') }}</h5>
                    <p class="cr-modal__sub">{{ __('Request custom content from your favorite creators') }}</p>
                </div>
                <button type="button" class="cr-modal__close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="{{ __('Close') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                </button>
            </div>

            <form id="createCustomRequestForm">
                <div class="modal-body cr-modal__body">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    {{-- Creator --}}
                    <div class="cr-field">
                        <label for="request_creator_username" class="cr-label">
                            {{ __('Select Creator') }} <span class="cr-req">*</span>
                        </label>
                        <div class="cr-search">
                            <span class="cr-search__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                            </span>
                            <input type="text"
                                   class="cr-input cr-search__input"
                                   id="request_creator_username"
                                   name="creator_username"
                                   placeholder="{{ __('Search for a creator...') }}"
                                   required
                                   autocomplete="off">
                            <input type="hidden" id="request_creator_id" name="creator_id" required>
                        </div>
                        <p class="cr-hint">{{ __('Type a username and select from the results below') }}</p>

                        <div id="creator_selected_indicator" class="cr-selected" style="display: none;">
                            <span class="cr-selected__check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <div class="cr-selected__text">
                                <span class="cr-selected__label">{{ __('Selected') }}</span>
                                <strong id="selected_creator_name"></strong>
                            </div>
                            <button type="button" class="cr-selected__clear" onclick="clearCreatorSelection()" aria-label="{{ __('Clear') }}">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                            </button>
                        </div>

                        <div id="creator_search_results" class="cr-results" style="display: none;"></div>
                    </div>

                    {{-- Type --}}
                    <div class="cr-field">
                        <span class="cr-label">{{ __('Request Type') }} <span class="cr-req">*</span></span>
                        <div class="cr-types">
                            <label class="cr-type">
                                <input type="radio" name="type" id="type_private" value="private" required>
                                <span class="cr-type__card">
                                    <span class="cr-type__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="M8 11V8a4 4 0 018 0v3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                                    </span>
                                    <span class="cr-type__text">
                                        <strong>{{ __('Private') }}</strong>
                                        <small>{{ __('Via private message') }}</small>
                                    </span>
                                </span>
                            </label>
                            <label class="cr-type">
                                <input type="radio" name="type" id="type_public" value="public" required>
                                <span class="cr-type__card">
                                    <span class="cr-type__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18" stroke="currentColor" stroke-width="1.75"/></svg>
                                    </span>
                                    <span class="cr-type__text">
                                        <strong>{{ __('Public') }}</strong>
                                        <small>{{ __('On creator profile') }}</small>
                                    </span>
                                </span>
                            </label>
                            <label class="cr-type">
                                <input type="radio" name="type" id="type_marketplace" value="marketplace" required>
                                <span class="cr-type__card">
                                    <span class="cr-type__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 9h16l-1.5 11H5.5L4 9z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M9 9V7a3 3 0 016 0v2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                                    </span>
                                    <span class="cr-type__text">
                                        <strong>{{ __('Marketplace') }}</strong>
                                        <small>{{ __('Crowdfunded') }}</small>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Details --}}
                    <div class="cr-field">
                        <div class="cr-label-row">
                            <label for="request_title" class="cr-label">
                                {{ __('Title') }} <span class="cr-req">*</span>
                            </label>
                            <span class="cr-count"><span id="title_count">0</span>/255</span>
                        </div>
                        <input type="text"
                               class="cr-input"
                               id="request_title"
                               name="title"
                               placeholder="{{ __('e.g., Shave My Head, Sing Happy Birthday…') }}"
                               required
                               maxlength="255">
                    </div>

                    <div class="cr-field">
                        <div class="cr-label-row">
                            <label for="request_description" class="cr-label">
                                {{ __('Description') }} <span class="cr-req">*</span>
                            </label>
                            <span class="cr-count"><span id="description_count">0</span>/2000</span>
                        </div>
                        <textarea class="cr-input cr-textarea"
                                  id="request_description"
                                  name="description"
                                  rows="4"
                                  placeholder="{{ __('Describe your custom request in detail…') }}"
                                  required
                                  maxlength="2000"></textarea>
                        <p class="cr-hint">{{ __('Be specific — clear requests get accepted faster.') }}</p>
                    </div>

                    {{-- Pricing --}}
                    <div id="price_field" class="cr-field" style="display: none;">
                        <label for="request_price" class="cr-label">
                            {{ __('Price') }} <span class="cr-req">*</span>
                        </label>
                        <div class="cr-money">
                            <span class="cr-money__symbol">$</span>
                            <input type="number"
                                   class="cr-input cr-money__input"
                                   id="request_price"
                                   name="price"
                                   step="0.01"
                                   min="1"
                                   placeholder="0.00">
                        </div>
                        <p class="cr-hint">{{ __('Fixed price for this request (minimum $1)') }}</p>
                        <div class="cr-note" id="upfront_payment_info" style="display: none;">
                            <strong>{{ __('Upfront payment') }}:</strong>
                            <span id="upfront_payment_amount">$0.00</span>
                            <span class="cr-note__sub">{{ __('Charged when you create the request') }}</span>
                        </div>
                    </div>

                    {{-- Goal --}}
                    <div id="goal_amount_field" class="cr-field" style="display: none;">
                        <label for="request_goal_amount" class="cr-label">
                            {{ __('Funding Goal') }} <span class="cr-req">*</span>
                        </label>
                        <div class="cr-money">
                            <span class="cr-money__symbol">$</span>
                            <input type="number"
                                   class="cr-input cr-money__input"
                                   id="request_goal_amount"
                                   name="goal_amount"
                                   step="0.01"
                                   min="1"
                                   placeholder="1000.00">
                        </div>
                        <p class="cr-hint">{{ __('Total amount needed for this request') }}</p>
                        <div class="cr-note" id="upfront_payment_marketplace" style="display: none;">
                            <strong>{{ __('Upfront payment') }}:</strong>
                            <span id="upfront_payment_marketplace_amount">$0.00</span>
                            <span class="cr-note__sub">{{ __('10% of goal or $1 minimum') }}</span>
                        </div>
                    </div>

                    {{-- Deadline --}}
                    <div class="cr-field">
                        <label for="request_deadline" class="cr-label">
                            {{ __('Deadline') }} <span class="cr-optional">{{ __('Optional') }}</span>
                        </label>
                        <input type="date"
                               class="cr-input"
                               id="request_deadline"
                               name="deadline"
                               min="{{ date('Y-m-d') }}">
                    </div>

                    <div id="message_id_field" class="cr-field" style="display: none;">
                        <label for="request_message_id" class="cr-label">
                            {{ __('Message ID') }} <span class="cr-optional">{{ __('Optional') }}</span>
                        </label>
                        <input type="number"
                               class="cr-input"
                               id="request_message_id"
                               name="message_id"
                               placeholder="{{ __('Message ID if sent via private message') }}">
                    </div>
                </div>

                <div class="cr-modal__footer">
                    <button type="button" class="cr-btn cr-btn--ghost" data-dismiss="modal" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="cr-btn cr-btn--primary">{{ __('Create Request') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceField = document.getElementById('price_field');
    const goalAmountField = document.getElementById('goal_amount_field');
    const messageIdField = document.getElementById('message_id_field');
    const priceInput = document.getElementById('request_price');
    const goalAmountInput = document.getElementById('request_goal_amount');
    const titleInput = document.getElementById('request_title');
    const descriptionInput = document.getElementById('request_description');
    const titleCount = document.getElementById('title_count');
    const descriptionCount = document.getElementById('description_count');

    if (titleInput && titleCount) {
        titleInput.addEventListener('input', function() {
            titleCount.textContent = this.value.length;
        });
    }

    if (descriptionInput && descriptionCount) {
        descriptionInput.addEventListener('input', function() {
            descriptionCount.textContent = this.value.length;
        });
    }

    function getSelectedType() {
        const checked = document.querySelector('#createCustomRequestModal input[name="type"]:checked');
        return checked ? checked.value : null;
    }

    function updateUpfrontPayment() {
        const price = parseFloat(priceInput && priceInput.value) || 0;
        const upfrontInfo = document.getElementById('upfront_payment_info');
        const upfrontAmount = document.getElementById('upfront_payment_amount');
        if (upfrontInfo && upfrontAmount && price > 0) {
            upfrontAmount.textContent = '$' + Math.max(1, price).toFixed(2);
            upfrontInfo.style.display = 'block';
        } else if (upfrontInfo) {
            upfrontInfo.style.display = 'none';
        }
    }

    function updateUpfrontPaymentMarketplace() {
        const goal = parseFloat(goalAmountInput && goalAmountInput.value) || 0;
        const upfrontInfo = document.getElementById('upfront_payment_marketplace');
        const upfrontAmount = document.getElementById('upfront_payment_marketplace_amount');
        if (upfrontInfo && upfrontAmount && goal > 0) {
            upfrontAmount.textContent = '$' + Math.max(1, goal * 0.10).toFixed(2);
            upfrontInfo.style.display = 'block';
        } else if (upfrontInfo) {
            upfrontInfo.style.display = 'none';
        }
    }

    function handleTypeChange() {
        const type = getSelectedType();

        if (priceField) priceField.style.display = 'none';
        if (goalAmountField) goalAmountField.style.display = 'none';
        if (messageIdField) messageIdField.style.display = 'none';
        priceInput && priceInput.removeAttribute('required');
        goalAmountInput && goalAmountInput.removeAttribute('required');

        const upfrontInfo = document.getElementById('upfront_payment_info');
        const upfrontMarket = document.getElementById('upfront_payment_marketplace');
        if (upfrontInfo) upfrontInfo.style.display = 'none';
        if (upfrontMarket) upfrontMarket.style.display = 'none';

        if (type === 'private' || type === 'public') {
            if (priceField) priceField.style.display = 'block';
            priceInput && priceInput.setAttribute('required', 'required');
            if (type === 'private' && messageIdField) messageIdField.style.display = 'block';
            updateUpfrontPayment();
        } else if (type === 'marketplace') {
            if (goalAmountField) goalAmountField.style.display = 'block';
            goalAmountInput && goalAmountInput.setAttribute('required', 'required');
            updateUpfrontPaymentMarketplace();
        }
    }

    if (priceInput) priceInput.addEventListener('input', updateUpfrontPayment);
    if (goalAmountInput) goalAmountInput.addEventListener('input', updateUpfrontPaymentMarketplace);

    document.querySelectorAll('#createCustomRequestModal input[name="type"]').forEach(function(radio) {
        radio.addEventListener('change', handleTypeChange);
    });

    // Creator search
    const creatorUsernameInput = document.getElementById('request_creator_username');
    const creatorIdInput = document.getElementById('request_creator_id');
    const creatorResults = document.getElementById('creator_search_results');
    let searchTimeout;

    if (creatorUsernameInput && creatorResults && creatorIdInput) {
        creatorUsernameInput.addEventListener('input', function() {
            const username = this.value.trim();
            clearTimeout(searchTimeout);

            if (username.length < 2) {
                creatorResults.style.display = 'none';
                creatorIdInput.value = '';
                return;
            }

            searchTimeout = setTimeout(function() {
                fetch('{{ route("search.users") }}?query=' + encodeURIComponent(username) + '&encode_html=false', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) { return response.json(); })
                .then(function(responseData) {
                    creatorResults.innerHTML = '';
                    var users = [];
                    if (responseData.success && responseData.data) {
                        if (responseData.data.data && Array.isArray(responseData.data.data)) users = responseData.data.data;
                        else if (responseData.data.users && Array.isArray(responseData.data.users)) users = responseData.data.users;
                        else if (Array.isArray(responseData.data)) users = responseData.data;
                    } else if (responseData.users && Array.isArray(responseData.users)) {
                        users = responseData.users;
                    } else if (Array.isArray(responseData)) {
                        users = responseData;
                    }

                    if (!users.length) {
                        creatorResults.style.display = 'none';
                        return;
                    }

                    creatorResults.style.display = 'block';
                    var list = document.createElement('div');
                    list.className = 'cr-results__list';

                    users.slice(0, 5).forEach(function(user) {
                        var userId = user.id || user.user_id;
                        var userName = user.name || user.user_name || 'Unknown';
                        var userUsername = user.username || user.user_username || '';
                        var userAvatar = user.avatar || '';
                        if (!userId) return;

                        var item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'cr-results__item';
                        item.innerHTML =
                            (userAvatar
                                ? '<img src="' + userAvatar + '" alt="" class="cr-results__avatar">'
                                : '<span class="cr-results__avatar cr-results__avatar--fallback">' + (userName.charAt(0) || '?') + '</span>') +
                            '<span class="cr-results__meta"><strong>' + userName + '</strong><small>@' + userUsername + '</small></span>';

                        item.addEventListener('click', function() {
                            creatorUsernameInput.value = userUsername;
                            creatorIdInput.value = userId;
                            creatorResults.style.display = 'none';
                            var indicator = document.getElementById('creator_selected_indicator');
                            var selectedName = document.getElementById('selected_creator_name');
                            if (indicator && selectedName) {
                                selectedName.textContent = userName + ' (@' + userUsername + ')';
                                indicator.style.display = 'flex';
                            }
                        });
                        list.appendChild(item);
                    });

                    if (list.children.length) creatorResults.appendChild(list);
                    else creatorResults.style.display = 'none';
                })
                .catch(function() {
                    creatorResults.style.display = 'none';
                });
            }, 400);
        });
    }
});

function clearCreatorSelection() {
    var username = document.getElementById('request_creator_username');
    var id = document.getElementById('request_creator_id');
    var indicator = document.getElementById('creator_selected_indicator');
    var results = document.getElementById('creator_search_results');
    if (username) username.value = '';
    if (id) id.value = '';
    if (indicator) indicator.style.display = 'none';
    if (results) results.style.display = 'none';
}
</script>
