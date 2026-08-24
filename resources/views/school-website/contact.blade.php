@extends('layouts.school.master')
@section('title')
    {{ __('contact_us') }}
@endsection
@section('content')
@php
    $dir = Session::get('language')->is_rtl ? 'rtl' : 'ltr';
@endphp
<div class="breadcrumb">
    <div class="container">
        <div class="contentWrapper">
            <span class="title"> {{ $schoolSettings['contact_us_heading'] ?? 'Contact Us' }} </span>
                <span dir="{{ $dir }}" class="path">
                    <a dir="{{ $dir }}" href="{{ url('/') }}" class="home">{{ __('home') }}</a>
                    <span><i class="fa-solid fa-caret-right"></i></span>
                    <span class="page"> {{ $schoolSettings['contact_us_heading'] ?? 'Contact Us' }} </span>
                </span>
                <span class="page"> {{ $schoolSettings['contact_us_description'] ?? 'Contact Us' }} </span>
            </div>
        </div>
    </div>
    

    <section class="contactUs commonMT commonWaveSect">
        <div class="container">
            <div class="row">

                <div class="col-lg-6">

                    <div class="headlines">
                        <span>{{ __('get_in_touch') }}</span>
                        <span>{{ __('have_any_query') }}</span>
                    </div>

                    <div class="formWrapper">
                        <form action="{{ url('school/contact-us') }}" class="create-form-with-captcha" method="post">
                            @csrf
                            <div class="row">
                                <input type="hidden" name="school_email" value="{{ $schoolSettings['school_email'] ?? '' }}">
                                <div class="col-sm-12 col-md-6 col-lg-6">
                                    <div class="d-flex flex-column gap-1">
                                        <label for="First Name">{{ __('name') }}</label>
                                        <input type="text" name="name" required placeholder="Enter First Name"></input>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-6 col-lg-6">
                                    <div class="d-flex flex-column gap-1">
                                        <label for="email">{{ __('email') }}</label>
                                        <input type="email" name="email" required placeholder="Enter Your Email"></input>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex flex-column gap-1">
                                        <label for="Message">{{ __('subject') }}</label>
                                        <input name="subject" id="subject" required placeholder="Subject"></input>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex flex-column gap-1">
                                        <label for="Message">{{ __('message') }}</label>
                                        <textarea name="message" id="message" required cols="30" rows="5"
                                            placeholder="Enter Message"></textarea>
                                    </div>
                                </div>

                                @if ($schoolSettings['SCHOOL_RECAPTCHA_SITE_KEY'] ?? '')
                                    <div class="col-12">
                                        <div class="g-recaptcha mt-4" data-sitekey={{ $schoolSettings['SCHOOL_RECAPTCHA_SITE_KEY'] }}></div>
                                    </div>    
                                @endif

                                <div class="col-4">
                                    <button type="submit" class="commonBtn">
                                        {{ __('send_message') }}
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="col-12 infoContainer">
                        <div class="col-12">
                                <div class="mapWrapper commonMT">
                                    <div class="col-12">
                                        @php
                                            $mapLink = $schoolSettings['google_map_link'] ?? '';
                                            $isIframe = str_contains($mapLink, '<iframe');
                                            $isUrl = filter_var($mapLink, FILTER_VALIDATE_URL) || str_contains($mapLink, 'maps.app.goo.gl') || str_contains($mapLink, 'google.com/maps');
                                        @endphp

                                        @if ($isIframe)
                                            {!! $mapLink !!}
                                        @elseif ($isUrl)
                                            <div class="text-center p-4 border rounded bg-light d-flex flex-column align-items-center justify-content-center" style="min-height: 200px;">
                                                <i class="fa fa-map-marker-alt fa-3x text-danger mb-3"></i>
                                                <h5>{{ __('view_on_google_maps') }}</h5>
                                                <a href="{{ $mapLink }}" target="_blank" class="commonBtn mt-3" style="text-decoration: none; display: inline-block;">
                                                    {{ __('open_maps') }}
                                                </a>
                                            </div>
                                        @else
                                            {!! $mapLink !!}
                                        @endif
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection

@section('script')
    <script async src="https://www.google.com/recaptcha/api.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('/assets/js/custom/common.js') }}"></script>
    <script src="{{ asset('/assets/js/custom/custom.js') }}"></script>
    <script src="{{ asset('/assets/js/custom/validate.js') }}"></script>
    <script src="{{ asset('/assets/js/custom/function.js') }}"></script>
    <script src="{{ asset('/assets/js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>

    <script src="{{ asset('assets/home_page/js/owl.carousel.min.js') }}"></script>
@endsection