@extends('layouts.school.master')
@section('title')
    {{ __('videos') }}
@endsection
@section('content')
    <style>
        ol,
        ul {
            padding-left: unset !important;
        }
    </style>
    @php
        $dir = Session::get('language')->is_rtl ? 'rtl' : 'ltr';
    @endphp
    <div class="breadcrumb">
        <div class="container">
            <div class="contentWrapper">
                <span class="title">
                    {{ __('gallery') }}
                </span>
                <span dir="{{ $dir }}">
                    <a dir="{{ $dir }}" href="{{ url('/') }}" class="home">{{ __('home') }}</a>
                    <span><i class="fa-solid fa-caret-right"></i></span>
                    <a href="{{ url('school/videos') }}"><span dir="{{ $dir }}" class="home">{{ __('gallery') }}</span></a>
                    <span><i class="fa-solid fa-caret-right"></i></span>
                    <span class="page">{{ __('videos') }}</span>
                </span>
            </div>
        </div>
    </div>


    <section class="videosGallery commonMT commonWaveSect">
        <div class="container">
            <div id="Center">
                @if (count($videos->file))
                    <ul id="waterfall"></ul>
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
            <iframe class="lightbox-content responsive-iframe" width="560" height="315" id="lightbox-video"
                allowfullscreen></iframe>
            <div class="caption" id="caption"></div>
        </div>

    </div>


@endsection
@section('js')
    <script type="text/javascript">
        $(document).ready(function () {
            @foreach ($videos->file as $row)
                var height = 300;
                $("#waterfall").append("<li><div class='m-2 video1 videos' style='height:" + height +
                    "px'><img class='thumbnail video-thumbnail' data-video='{{ $row->youtube_url_action->embed_url }}' style='height:" + height +
                    "px;width: 100%;' src='{{ $row->youtube_url_action->img }}' alt=''><div class='detailArr'> <img src='{{ asset('assets/school/images/videoPlayIcon.png') }}' alt=''></div></div></li>"
                );
            @endforeach

            $('#waterfall').NewWaterfall({
                width: 360,
                delay: 100,
            });

            // Force layout recalculation
            setTimeout(function () {
                window.dispatchEvent(new Event('resize'));
            }, 500);
        });

        function random(min, max) {
            return min + Math.floor(Math.random() * (max - min + 1))
        }

    </script>
@endsection