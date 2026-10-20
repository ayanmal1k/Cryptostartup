<footer class="d-none d-lg-block site-footer-compact">
    <!-- A grey container -->
    <div class="greycontainer">
        <!-- A black container -->
        <div class="blackcontainer">
            <!-- Container to indent the content -->
            <div class="container">
                <div class="copyRightInfo d-flex flex-column-reverse flex-md-row d-md-flex justify-content-md-between py-3">
                    <div class="d-flex align-items-center">
                        <p class="mb-0">&copy; {{date('Y')}} {{getSetting('site.name')}}. {{__('All rights reserved.')}}</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <ul class="d-flex flex-row nav mb-0 footer-social-links">
                            @if(getSetting('social-links.facebook_url'))
                                <li class="nav-item">
                                    <a class="nav-link pe-1 ml-2" href="{{getSetting('social-links.facebook_url')}}" target="_blank">
                                        @include('elements.icon',['icon'=>'logo-facebook','variant'=>'medium','classes' => 'text-lg opacity-8'])
                                    </a>
                                </li>
                            @endif
                            @if(getSetting('social-links.twitter_url'))
                                <li class="nav-item">
                                    <a class="nav-link pe-1 ml-2" href="{{getSetting('social-links.twitter_url')}}" target="_blank">
                                        @include('elements.icon',['icon'=>'logo-twitter','variant'=>'medium','classes' => 'text-lg opacity-8'])
                                    </a>
                                </li>
                            @endif
                            @if(getSetting('social-links.instagram_url'))
                                <li class="nav-item">
                                    <a class="nav-link pe-1 ml-2" href="{{getSetting('social-links.instagram_url')}}" target="_blank">
                                        @include('elements.icon',['icon'=>'logo-instagram','variant'=>'medium','classes' => 'text-lg opacity-8'])
                                    </a>
                                </li>
                            @endif
                            @if(getSetting('social-links.whatsapp_url'))
                                <li class="nav-item">
                                    <a class="nav-link pe-1 ml-2" href="{{getSetting('social-links.whatsapp_url')}}" target="_blank">
                                        @include('elements.icon',['icon'=>'logo-whatsapp','variant'=>'medium','classes' => 'text-lg opacity-8'])
                                    </a>
                                </li>
                            @endif
                            @if(getSetting('social-links.tiktok_url'))
                                <li class="nav-item">
                                    <a class="nav-link pe-1 ml-2" href="{{getSetting('social-links.tiktok_url')}}" target="_blank">
                                        @include('elements.icon',['icon'=>'logo-tiktok','variant'=>'medium','classes' => 'text-lg opacity-8'])
                                    </a>
                                </li>
                            @endif
                            @if(getSetting('social-links.youtube_url'))
                                <li class="nav-item">
                                    <a class="nav-link pe-1 ml-2" href="{{getSetting('social-links.youtube_url')}}" target="_blank">
                                        @include('elements.icon',['icon'=>'logo-youtube','variant'=>'medium','classes' => 'text-lg opacity-8'])
                                    </a>
                                </li>
                            @endif
                            @if(getSetting('social-links.telegram_link'))
                                <li class="nav-item">
                                    <a class="nav-link pe-1 ml-2" href="{{getSetting('social-links.telegram_link')}}" target="_blank">
                                        @include('elements.icon',['icon'=>'paper-plane','variant'=>'medium','classes' => 'text-lg opacity-8'])
                                    </a>
                                </li>
                            @endif
                        </ul>



                    </div>

                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* Fix social icon sizes in compact footer - prevent Tailwind CSS conflicts */
footer .footer-social-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

footer .footer-social-links .nav-link {
    padding: 0.25rem 0.5rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: auto !important;
    height: auto !important;
    line-height: 1 !important;
}

/* Force icon wrapper to correct size - override Tailwind and all other styles */
footer .footer-social-links .nav-link .ion-icon-wrapper,
footer .footer-social-links .nav-link .ion-icon-wrapper.icon-medium {
    width: 24px !important;
    height: 24px !important;
    font-size: 24px !important;
    max-width: 24px !important;
    max-height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    display: inline-block !important;
    flex-shrink: 0 !important;
    box-sizing: content-box !important;
}

footer .footer-social-links .nav-link .ion-icon-inner,
footer .footer-social-links .nav-link .ion-icon-inner svg {
    width: 24px !important;
    height: 24px !important;
    max-width: 24px !important;
    max-height: 24px !important;
    display: block !important;
    flex-shrink: 0 !important;
}

/* Override any Tailwind width/height classes that might affect icons */
footer .footer-social-links .nav-link .ion-icon-wrapper[class*="w-"],
footer .footer-social-links .nav-link .ion-icon-wrapper[class*="h-"],
footer .footer-social-links .nav-link .ion-icon-wrapper[class*="size"] {
    width: 24px !important;
    height: 24px !important;
}
</style>
