<footer class="commonMT">
    <div class="container">
        <div class="row">
            <div class="col-sm-6 col-md-6 col-lg-6">
                <div class="companyInfoWrapper">
                    <div>
                        <a href="{{ url('/') }}">
                            <img src="{{ $systemSettings['horizontal_logo'] ?? asset('assets/landing_page_images/Logo1.svg') }}" class="logo" alt="">
                        </a>
                    </div>
                    <div>
                        <span class="commonDesc">
                            {{ $systemSettings['short_description'] ?? '' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-6 col-lg-2">
                <div class="linksWrapper usefulLinksDiv">
                    <span class="title">{{ __('links') }}</span>
                    <span><a href="{{ url('/') }}">{{ __('home') }}</a></span>
                    <span><a href="{{ url('/#features') }}">{{ __('features') }}</a></span>
                    <span><a href="{{ url('/#pricing') }}">{{ __('pricing') }}</a></span>
                    <span><a href="{{ url('/#faq') }}">{{ __('faqs') }}</a></span>
                </div>
            </div>

            <div class="col-sm-6 col-md-6 col-lg-2">
                <div class="linksWrapper">
                    <span class="title">{{ __('info') }}</span>
                    <span>
                        <a href="{{ url('/#about-us') }}">
                            {{ __('about_us') }}
                        </a>
                    </span>
                    <span>
                        <a href="{{ url('/#contact-us') }}">
                            {{ __('contact') }}
                        </a>
                    </span>
                    <span>
                        <a href="{{ url('/#careers') }}">
                            {{ __('Career') }}
                        </a>
                    </span>

                    <span>
                        <a href="{{ url('page/type/privacy-policy') }}">
                            {{ __('privacy_policy') }}
                        </a>
                    </span>

                    <span>
                        <a href="{{ url('page/type/terms-conditions') }}">
                            {{ __('terms_condition') }}
                        </a>
                    </span>

                    <span>
                        <a href="{{ url('page/type/refund-cancellation') }}">
                            {{ __('refund_cancellation') }}
                        </a>
                    </span>
                </div>
            </div>

            @if (isset($systemSettings['facebook']) || isset($systemSettings['instragram']) || isset($systemSettings['linkedin']))
                    <div class="col-sm-6 col-md-6 col-lg-2">
                        <div class="linksWrapper">
                            <span class="title">{{ __('follow') }}</span>

                            @if (isset($systemSettings['facebook']))
                                <span class="iconsWrapper">
                                    <a href="{{ $systemSettings['facebook'] }}" target="_blank">
                                        <span>
                                            <img src="{{ asset('assets/landing_page_images/facebook.svg') }}" alt="">
                                        </span>
                                        <span>
                                            {{ __('facebook') }}
                                        </span>
                                    </a>
                                </span>    
                            @endif

                            @if (isset($systemSettings['instagram']))
                                <span class="iconsWrapper">
                                    <a href="{{ $systemSettings['instagram'] }}" target="_blank">
                                        <span>
                                            <img src="{{ asset('assets/landing_page_images/instagram.svg') }}" alt="">
                                        </span>
                                        <span>
                                            {{ __('instagram') }}
                                        </span>
                                    </a>
                                </span>    
                            @endif

                            @if (isset($systemSettings['linkedin']))
                                <span class="iconsWrapper">
                                    <a href="{{ $systemSettings['linkedin'] }}" target="_blank">
                                        <span>
                                            <img src="{{ asset('assets/landing_page_images/linkedin1.svg') }}" alt="">
                                        </span>
                                        <span>
                                            {{ __('linkedin') }}
                                        </span>
                                    </a>
                                </span>    
                            @endif
                        </div>
                    </div>
                @endif

            <hr>

            <div class="col-12 copyright">
                @if (isset($systemSettings['footer_text']) && $systemSettings['footer_text'])
                    <span class="copyright footer-text"><span class="me-1">&copy; {{ date('Y') }}</span> {!! $systemSettings['footer_text'] !!}</span>
                @endif
            </div>

        </div>
    </div>
</footer>