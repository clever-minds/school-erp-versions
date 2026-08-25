@if (isset($schoolSettings['gallery_status']) && $schoolSettings['gallery_status'] == 1)
    <section class="ourGalleryPhotos commonMT commonWaveSect">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="flex_column_center">
                        <span class="commonTag"> {{ $schoolSettings['gallery_heading'] ?? 'Our Photo Gallery' }} </span>
                        <span class="commonTitle">

                            {{ $schoolSettings['gallery_title'] ?? 'Tiny Scholars Showcase' }}
                        </span>
                        <span class="commonDesc">
                            {{ $schoolSettings['gallery_description'] ?? '' }}
                        </span>
                    </div>
                </div>
                <div class="col-12">
                    @if (count($galleries))
                        <div class="row galleryImgsContainer">
                            <div class="col-sm-12 col-md-6 leftImgs">
                                @foreach ($galleries->take(1) as $row)
                                    <div class="bigImg w-100">
                                        <img src="{{ $row->thumbnail }}" alt="" class="w-100">
                                        <a href="{{ url('school/photos', $row->id) }}">
                                            <div class="detailsCard">
                                                <img src="{{ asset('assets/school/images/bx-plus-circle.png') }}" alt="">
                                                <span>{{ $row->title }}</span>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                                <div class="smallImgs">
                                    <div class="row g-3">
                                        @foreach ($galleries->skip(1)->take(2) as $row)
                                            <div class="col-6">
                                                <div class="leftSmallImg1">
                                                    <img src="{{ $row->thumbnail }}" alt="" class="w-100">
                                                    <a href="{{ url('school/photos', $row->id) }}">
                                                        <div class="detailsCard">
                                                            <img src="{{ asset('assets/school/images/bx-plus-circle.png') }}"
                                                                alt="">
                                                            <span>{{ $row->title }}</span>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>


                            <div class="col-sm-12 col-md-6 rightImgs">
                                <div class="upperImgs">
                                    <div class="row g-3">
                                        @foreach ($galleries->skip(3)->take(2) as $row)
                                            <div class="col-6">
                                                <div class="upperImg1">
                                                    <img src="{{ $row->thumbnail }}" alt="" class="w-100">
                                                    <a href="{{ url('school/photos', $row->id) }}">
                                                        <div class="detailsCard">
                                                            <img src="{{ asset('assets/school/images/bx-plus-circle.png') }}"
                                                                alt="">
                                                            <span>{{ $row->title }}</span>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="lowerImgs mt-3">
                                    <div class="row g-3">
                                        @foreach ($galleries->skip(5)->take(2) as $row)
                                            <div class="col-6">
                                                <div class="upperImg2">
                                                    <img src="{{ $row->thumbnail }}" alt="" class="w-100">
                                                    <a href="{{ url('school/photos', $row->id) }}">
                                                        <div class="detailsCard">
                                                            <img src="{{ asset('assets/school/images/bx-plus-circle.png') }}"
                                                                alt="">
                                                            <span>{{ $row->title }}</span>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    @else
                        <div class="flex_column_center mt-5">
                            <span class="commonDesc">{{ __('no_data_found') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!-- ourGalleryPhotos ends here  -->
@endif