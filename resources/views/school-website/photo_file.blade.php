@extends('layouts.school.master')
@section('title')
    {{ __('photos') }}
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('assets/school/css/photo_file.css') }}">
@endsection
@section('content')
    <div class="breadcrumb">
        <div class="container">
            <div class="contentWrapper">
                <span class="title">
                    {{ __('gallery') }}
                </span>
                <span>
                    <a href="{{ url('/') }}" class="home">{{ __('home') }}</a>
                    <span><i class="fa-solid fa-caret-right"></i></span>
                    <a href="{{ url('school/photos') }}"><span class="home">{{ __('gallery') }}</span></a>
                    <span><i class="fa-solid fa-caret-right"></i></span>
                    <span class="page">{{ __('photos') }}</span>
                </span>
            </div>
        </div>
    </div>

    <section class="photosGallery commonMT commonWaveSect">
        <div class="container">
            <div id="Center">
                @if (count($photos->file))
                    <ul id="waterfall">
                        @foreach ($photos->file as $row)
                            <li>
                                <div class="gallery-card">
                                    <img
                                        class="thumbnail"
                                        src="{{ $row->file_url }}"
                                        alt="{{ __('gallery') }}"
                                        loading="lazy"
                                    >
                                    <div class="detailArr">
                                        <img src="{{ asset('assets/school/images/photosArrIcon.png') }}" alt="">
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="flex_column_center">
                        <span class="commonDesc">{{ __('no_data_found') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div id="lightbox" class="lightbox">
        <div class="lightbox-size">
            <span class="close"><i class="fa fa-close"></i></span>
            <img class="lightbox-content" id="lightbox-img">
            <iframe class="lightbox-content responsive-iframe" id="lightbox-video" allowfullscreen></iframe>
            <div class="caption" id="caption"></div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{ asset('assets/school/js/photo_file.js') }}"></script>
@endsection