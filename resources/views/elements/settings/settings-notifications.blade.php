<div class="notifications-settings">
    <form class="notifications-settings__form">
        <div class="notifications-settings__card">
            @if(getSetting('profiles.enable_new_post_notification_setting'))
                <div class="notifications-settings__row">
                    <span class="notifications-settings__label" id="notification_email_new_post_created-label">{{ __('New content has been posted') }}</span>
                    <label class="notifications-settings__toggle" for="notification_email_new_post_created" aria-labelledby="notification_email_new_post_created-label">
                        <input type="checkbox"
                               class="notifications-settings__toggle-input notification-checkbox"
                               id="notification_email_new_post_created"
                               name="notification_email_new_post_created"
                            {{ isset(Auth::user()->settings['notification_email_new_post_created']) ? (Auth::user()->settings['notification_email_new_post_created'] == 'true' ? 'checked' : '') : false }}>
                        <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                    </label>
                </div>
            @endif

            <div class="notifications-settings__row">
                <span class="notifications-settings__label" id="notification_email_new_sub-label">{{ __('New subscription registered') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_new_sub" aria-labelledby="notification_email_new_sub-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_new_sub"
                           name="notification_email_new_sub"
                        {{ isset(Auth::user()->settings['notification_email_new_sub']) ? (Auth::user()->settings['notification_email_new_sub'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="notifications-settings__row">
                <span class="notifications-settings__label" id="notification_email_new_tip-label">{{ __('Received a tip') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_new_tip" aria-labelledby="notification_email_new_tip-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_new_tip"
                           name="notification_email_new_tip"
                        {{ isset(Auth::user()->settings['notification_email_new_tip']) ? (Auth::user()->settings['notification_email_new_tip'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="notifications-settings__row">
                <span class="notifications-settings__label" id="notification_email_new_ppv_unlock-label">{{ __('Your PPV content has been unlocked') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_new_ppv_unlock" aria-labelledby="notification_email_new_ppv_unlock-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_new_ppv_unlock"
                           name="notification_email_new_ppv_unlock"
                        {{ isset(Auth::user()->settings['notification_email_new_ppv_unlock']) ? (Auth::user()->settings['notification_email_new_ppv_unlock'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="notifications-settings__row">
                <span class="notifications-settings__label" id="notification_email_new_message-label">{{ __('New message received') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_new_message" aria-labelledby="notification_email_new_message-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_new_message"
                           name="notification_email_new_message"
                        {{ isset(Auth::user()->settings['notification_email_new_message']) ? (Auth::user()->settings['notification_email_new_message'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="notifications-settings__row">
                <span class="notifications-settings__label" id="notification_email_new_comment-label">{{ __('New comment received') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_new_comment" aria-labelledby="notification_email_new_comment-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_new_comment"
                           name="notification_email_new_comment"
                        {{ isset(Auth::user()->settings['notification_email_new_comment']) ? (Auth::user()->settings['notification_email_new_comment'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="notifications-settings__row">
                <span class="notifications-settings__label" id="notification_email_expiring_subs-label">{{ __('Expiring subscriptions') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_expiring_subs" aria-labelledby="notification_email_expiring_subs-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_expiring_subs"
                           name="notification_email_expiring_subs"
                        {{ isset(Auth::user()->settings['notification_email_expiring_subs']) ? (Auth::user()->settings['notification_email_expiring_subs'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="notifications-settings__row">
                <span class="notifications-settings__label" id="notification_email_renewals-label">{{ __('Upcoming renewals') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_renewals" aria-labelledby="notification_email_renewals-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_renewals"
                           name="notification_email_renewals"
                        {{ isset(Auth::user()->settings['notification_email_renewals']) ? (Auth::user()->settings['notification_email_renewals'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="notifications-settings__row notifications-settings__row--last">
                <span class="notifications-settings__label" id="notification_email_creator_went_live-label">{{ __('A user I am following went live') }}</span>
                <label class="notifications-settings__toggle" for="notification_email_creator_went_live" aria-labelledby="notification_email_creator_went_live-label">
                    <input type="checkbox"
                           class="notifications-settings__toggle-input notification-checkbox"
                           id="notification_email_creator_went_live"
                           name="notification_email_creator_went_live"
                        {{ isset(Auth::user()->settings['notification_email_creator_went_live']) ? (Auth::user()->settings['notification_email_creator_went_live'] == 'true' ? 'checked' : '') : false }}>
                    <span class="notifications-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>
        </div>
    </form>
</div>









