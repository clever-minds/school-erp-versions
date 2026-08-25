@extends('layouts.school.master')
@section('title')
    {{ __('photos') }}
@endsection
@section('content')
    <style>
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
                <span class="title"> {{ __('gallery') }} </span>
                <span dir="{{ $dir }}">
                    <a dir="{{ $dir }}" href="{{ url('/') }}" class="home">{{ __('home') }}</a>
                    <span><i class="fa-solid fa-caret-right"></i></span>
                    <a href="{{ url('school/photos') }}"><span dir="{{ $dir }}" class="home">{{ __('gallery') }}</span></a>
                    <span><i class="fa-solid fa-caret-right"></i></span>
                    <span class="page">{{ __('photos') }}</span>
                </span>
            </div>
        </div>
    </div>




    <section class="photosGallery commonMT commonWaveSect">
        <div class="container">
            <div id="Center">
                @if (count($galleries))
                    <ul id="waterfall"></ul>
                @else
                    <div class="flex_column_center">
                        <span class="commonDesc">{{ __('no_data_found') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

@endsection
@section('js')
    <script type="text/javascript">

        $(document).ready(function () {
            @foreach($galleries as $row)
                var height = 300;
                $("#waterfall").append("<li><a href='{{ url('school/photos', $row->id) }}'><div class='m-2 upperBigImg1' style='height:" + height + "px'><img style='height:" + height + "px;width: 100%;' src='{{ $row->thumbnail }}'><div class='detailArr'> <img src='{{ asset('assets/school/images/bx-plus-circle.png') }}' alt=''> <span>{{ $row->title }}</span></div></div></a></li>");
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