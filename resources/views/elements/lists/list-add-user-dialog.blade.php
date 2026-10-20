@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp
<div class="modal fade pf-modal-root" tabindex="-1" role="dialog" id="list-add-user-dialog">
    <div class="modal-dialog modal-dialog-centered pf-modal-dialog" role="document">
        <div class="modal-content pf-modal {{ $isDarkTheme ? 'pf-modal--dark' : 'pf-modal--light' }}">
            <div class="pf-modal__header">
                <div class="pf-modal__header-text">
                    <h5 class="pf-modal__title">{{__('Add user to list')}}</h5>
                    <p class="pf-modal__sub">{{__('Chose the list you want to add the user into')}}</p>
                </div>
                <button type="button" class="pf-modal__close" data-dismiss="modal" aria-label="{{__('Close')}}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
                </button>
            </div>
            <div class="pf-modal__body">
                <div class="add-user-lists-wrapper pf-list-options">
                    @forelse($lists as $list)
                        <label class="pf-list-option" for="list-{{$list->id}}">
                            <input
                                class="pf-list-option__check"
                                data-listID="{{$list->id}}"
                                type="checkbox"
                                value=""
                                {{ListsHelper::isMemberList($list->members, $user_id) ? 'checked' : ''}}
                                id="list-{{$list->id}}"
                            >
                            <span class="pf-list-option__box" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <span class="pf-list-option__meta">
                                <span class="pf-list-option__name">{{__($list->name)}}</span>
                                <span class="pf-list-option__sub">{{trans_choice('members', count($list->members), ['number'=>count($list->members),])}} · {{trans_choice('posts', $list->posts_count, ['number'=>$list->posts_count])}}</span>
                            </span>
                        </label>
                    @empty
                        <div class="pf-modal__empty">{{__('No lists available.')}}</div>
                    @endforelse
                </div>
            </div>
            <div class="pf-modal__footer">
                <button type="button" class="pf-btn pf-btn--ghost" data-dismiss="modal">{{__('Close')}}</button>
            </div>
        </div>
    </div>
</div>
