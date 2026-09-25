@extends('layouts.generic')

@section('page_title', __('DMCA & Copyright Policy'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/dmca.css') }}?v=20260712a">
@stop

@section('content')
@php
    $dmcaDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp
<div class="dmca-page dmca-page--{{ $dmcaDark ? 'dark' : 'light' }}">
    <div class="dmca-page__glow dmca-page__glow--tl" aria-hidden="true"></div>
    <div class="dmca-page__glow dmca-page__glow--br" aria-hidden="true"></div>

    <div class="dmca-page__scroll">
        <div class="dmca-page__inner">
            <header class="dmca-page__hero">
                <div class="dmca-page__hero-icon" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'shield-checkmark-outline', 'variant' => 'medium', 'centered' => true])
                </div>
                <h1 class="dmca-page__title">{{ __('DMCA & Copyright Policy') }}</h1>
                <p class="dmca-page__subtitle">{{ __('Digital Millennium Copyright Act Notice & Takedown Policy') }}</p>
            </header>

            <article class="dmca-page__content">
                <section class="dmca-section">
                    <h2 class="dmca-section__title">{{ __('Introduction') }}</h2>
                    <p class="dmca-section__text">{{ getSetting('site.name') }} ("we", "us", or "our") respects the intellectual property rights of others and expects its users to do the same. In accordance with the Digital Millennium Copyright Act of 1998 ("DMCA"), we will respond expeditiously to claims of copyright infringement committed using our service.</p>
                </section>

                <section class="dmca-section">
                    <h2 class="dmca-section__title">{{ __('DMCA Designated Agent') }}</h2>
                    <div class="dmca-agent-card">
                        <h3 class="dmca-agent-card__title">
                            @include('elements.icon', ['icon' => 'person-outline', 'variant' => 'small', 'centered' => true])
                            {{ __('Designated Agent for DMCA Notices') }}
                        </h3>
                        <p class="dmca-agent-card__row"><strong>{{ __('Name') }}:</strong> {{ getSetting('dmca.agent_name') ?? getSetting('site.name') . ' Legal Team' }}</p>
                        <p class="dmca-agent-card__row"><strong>{{ __('Email') }}:</strong> {{ getSetting('dmca.agent_email') ?? 'dmca@' . parse_url(config('app.url'), PHP_URL_HOST) }}</p>
                        <p class="dmca-agent-card__row"><strong>{{ __('Address') }}:</strong> {{ getSetting('dmca.agent_address') ?? getSetting('site.address') ?? __('Contact us for address') }}</p>
                    </div>
                </section>

                <section class="dmca-section">
                    <h2 class="dmca-section__title">{{ __('Filing a DMCA Takedown Notice') }}</h2>
                    <p class="dmca-section__text">{{ __('If you believe that your copyrighted work has been copied in a way that constitutes copyright infringement, please provide our designated agent with the following information:') }}</p>
                    <ol class="dmca-section__list">
                        <li>{{ __('A physical or electronic signature of a person authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.') }}</li>
                        <li>{{ __('Identification of the copyrighted work claimed to have been infringed, or if multiple copyrighted works are covered by a single notification, a representative list of such works.') }}</li>
                        <li>{{ __('Identification of the material that is claimed to be infringing or to be the subject of infringing activity and that is to be removed or access to which is to be disabled, and information reasonably sufficient to permit us to locate the material.') }}</li>
                        <li>{{ __('Information reasonably sufficient to permit us to contact you, such as an address, telephone number, and email address.') }}</li>
                        <li>{{ __('A statement that you have a good faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law.') }}</li>
                        <li>{{ __('A statement that the information in the notification is accurate, and under penalty of perjury, that you are authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.') }}</li>
                    </ol>

                    <div class="dmca-callout dmca-callout--warning">
                        <h4 class="dmca-callout__title">
                            @include('elements.icon', ['icon' => 'alert-circle-outline', 'variant' => 'small', 'centered' => true])
                            {{ __('Important Warning') }}
                        </h4>
                        <p class="dmca-callout__text">{{ __('Under Section 512(f) of the DMCA, any person who knowingly materially misrepresents that material is infringing may be subject to liability for damages.') }}</p>
                    </div>

                    <div class="dmca-actions">
                        <a href="{{ route('dmca.takedown-form') }}" class="dmca-btn dmca-btn--primary">
                            @include('elements.icon', ['icon' => 'document-text-outline', 'variant' => 'small', 'centered' => true])
                            {{ __('File a Takedown Request') }}
                        </a>
                    </div>
                </section>

                <section class="dmca-section">
                    <h2 class="dmca-section__title">{{ __('Counter-Notification') }}</h2>
                    <p class="dmca-section__text">{{ __('If you believe that your content was removed or disabled by mistake or misidentification, you may file a counter-notification with the following information:') }}</p>
                    <ol class="dmca-section__list">
                        <li>{{ __('Your physical or electronic signature.') }}</li>
                        <li>{{ __('Identification of the material that has been removed or to which access has been disabled and the location at which the material appeared before it was removed or disabled.') }}</li>
                        <li>{{ __('A statement under penalty of perjury that you have a good faith belief that the material was removed or disabled as a result of mistake or misidentification.') }}</li>
                        <li>{{ __('Your name, address, and telephone number, and a statement that you consent to the jurisdiction of the Federal District Court and that you will accept service of process.') }}</li>
                    </ol>

                    <div class="dmca-actions">
                        <a href="{{ route('dmca.counter-notification') }}" class="dmca-btn dmca-btn--secondary">
                            @include('elements.icon', ['icon' => 'return-down-back-outline', 'variant' => 'small', 'centered' => true])
                            {{ __('File a Counter-Notification') }}
                        </a>
                    </div>
                </section>

                <section class="dmca-section">
                    <h2 class="dmca-section__title">{{ __('Repeat Infringers Policy') }}</h2>
                    <p class="dmca-section__text">{{ __('In accordance with the DMCA and other applicable law, we have adopted a policy of terminating, in appropriate circumstances, users who are deemed to be repeat infringers. We may also, at our sole discretion, limit access to our service and/or terminate the accounts of any users who infringe any intellectual property rights of others, whether or not there is any repeat infringement.') }}</p>

                    <div class="dmca-callout dmca-callout--info">
                        <h4 class="dmca-callout__title">
                            @include('elements.icon', ['icon' => 'information-circle-outline', 'variant' => 'small', 'centered' => true])
                            {{ __('Response Time') }}
                        </h4>
                        <p class="dmca-callout__text">{{ __('We strive to process all valid DMCA takedown requests within 24-48 hours. However, complex cases may require additional time for proper review.') }}</p>
                    </div>
                </section>

                <section class="dmca-section">
                    <h2 class="dmca-section__title">{{ __('Contact Us') }}</h2>
                    <p class="dmca-section__text">{{ __('If you have any questions about our DMCA policy, please contact us at:') }}</p>
                    <p class="dmca-section__text"><strong>{{ __('Email') }}:</strong> {{ getSetting('dmca.agent_email') ?? 'dmca@' . parse_url(config('app.url'), PHP_URL_HOST) }}</p>
                </section>
            </article>

            <footer class="dmca-page__footer">
                {{ __('Last updated') }}: {{ date('F j, Y') }}
            </footer>
        </div>
    </div>
</div>
@stop
