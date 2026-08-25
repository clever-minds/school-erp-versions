<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand brand-logo" href="{{ URL::to('/dashboard') }}">
            <img src="{{ $schoolSettings['horizontal_logo'] ?? '' }}" alt="logo" data-custom-image="{{$systemSettings['horizontal_logo'] ?? asset('/assets/horizontal-logo2.svg')}}" class="custom-default-image">
        </a>
        <a class="navbar-brand brand-logo-mini" href="{{ URL::to('/dashboard') }}">
            <img src="{{ $schoolSettings['vertical_logo'] ?? '' }}" alt="logo" data-custom-image="{{$systemSettings['vertical_logo'] ?? asset('/assets/vertical-logo.svg')}}">
        </a>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="fa fa-bars"></span>
        </button>

        {{-- <div class="align-items-stretch d-none d-md-block d-sm-block cache-clear">
            <a class="btn btn-sm btn-inverse-info align-self-center" href="{{ url('cache-flush') }}">
                {{ __('cache_clear') }}
            </a>
        </div> --}}

        @if ($schoolSettings['school_name'] ?? '')
            <div class="align-items-stretch d-none d-md-block d-sm-block cache-clear">
                <span class="ml-3">{{ $schoolSettings['school_name'] ?? '' }}</span>
            </div>
        @endif  
        @if (isset($systemSettings['email_verified']) && !$systemSettings['email_verified'])
            @can('email-setting-create')
                <div class="mx-auto order-0 d-none d-md-block">
                    <div class="alert alert-fill-danger my-2" role="alert">
                        <i class="fa fa-exclamation"></i>
                        {{ __('Email Configuration is not verified') }} <a href="{{ route('system-settings.email.index') }}" class="alert-link">{{ __('Click here to redirect to email configuration') }}</a>.
                    </div>
                </div>
            @endcan
        @endif
        <ul class="navbar-nav navbar-nav-right">
            @can('class-teacher')
                <li class="nav-item">
                    {{-- TODO :: CLASS TEACHER CLASS NAME --}}
                    {{-- @php $class_section = Auth::user()->teacher->class_section @endphp
                    <div class="text-dark">{{__('Class').' : '.$class_section->class->name.' '.$class_section->section->name.' - '.$class_section->class->medium->name}}</div> --}}
                </li>
            @endcan

            @if (isset($sessionYear) && !Auth::user()->hasRole('Super Admin'))
                <li class="nav-item dropdown d-none d-md-block d-sm-block">
                    <a class="nav-link dropdown-toggle" href="#" id="sessionYearDropdown"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display: flex; align-items: center;">
                        @if($sessionYear->id == $defaultSessionYear->id)
                            <div class="px-2 py-1 font-weight-bold badge-pill-custom badge-academic-year">
                                {{ $sessionYear->name }}
                            </div>
                        @else
                            <div class="px-2 py-1 font-weight-bold badge-pill-custom badge-academic-year-non-default">
                                {{ $sessionYear->name }}
                            </div>
                        @endif

                    </a>
                    <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                        aria-labelledby="sessionYearDropdown">
                        <h6 class="p-3 mb-0">{{ __('select_session_year') }}</h6>
                        <div class="dropdown-divider"></div>
                        @foreach($sessionYears as $year)
                            <a class="dropdown-item preview-item change-session-year {{ $year->id == $sessionYear->id ? 'bg-light' : '' }}"
                                href="#" data-id="{{ $year->id }}" data-default="{{ $year->id == $defaultSessionYear->id ? 1 : 0 }}">
                                <div class="preview-thumbnail">
                                    <div class="preview-icon {{ $year->id == $sessionYear->id ? 'bg-success' : 'bg-inverse-primary' }}">
                                        <i class="fa {{ $year->id == $sessionYear->id ? 'fa-check text-white' : 'fa-calendar text-primary' }}"></i>
                                    </div>
                                </div>
                                <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                                    <h6 class="preview-subject ellipsis mb-1 font-weight-normal {{ $year->id == $sessionYear->id ? 'text-success font-weight-bold' : '' }}">
                                        {{ $year->name }}
                                        @if($year->id == $defaultSessionYear->id)
                                            <span class="badge badge-pill badge-inverse-info ml-2">{{ __('default') }}</span>
                                        @endif
                                    </h6>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </li>

                @if(!empty($semesters) && count($semesters) > 0)
                    <li class="nav-item dropdown d-none d-md-block d-sm-block">
                        <a class="nav-link dropdown-toggle" href="#" id="semesterDropdown"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display: flex; align-items: center;">
                           
                            @if (isset($semester) && $semester->id)
                                <div class="px-2 py-1 font-weight-bold badge-pill-custom badge-semester">
                                    {{ $semester->name }}
                                </div>
                            @else
                                <div class="px-2 py-1 font-weight-bold badge-pill-custom badge-semester-non-default">
                                    {{ __('Select Semester') }}
                                </div>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                            aria-labelledby="semesterDropdown">
                            <h6 class="p-3 mb-0">{{ __('select_semester') }}</h6>
                            <div class="dropdown-divider"></div>
                             {{-- Deselection / Auto Option --}}
                             <a class="dropdown-item preview-item change-viewing-semester {{ !session()->has('semester_id') ? 'bg-light' : '' }}"
                                href="#" data-id="0">
                                <div class="preview-thumbnail">
                                    <div class="preview-icon {{ !session()->has('semester_id') ? 'bg-info' : 'bg-inverse-info' }}">
                                        <i class="fa {{ !session()->has('semester_id') ? 'fa-check text-white' : 'fa-refresh text-info' }}"></i>
                                    </div>
                                </div>
                                <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                                    <h6 class="preview-subject ellipsis mb-1 font-weight-normal {{ !session()->has('semester_id') ? 'font-weight-bold' : '' }}">
                                        {{ __('System Default') }}
                                    </h6>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>

                            @foreach($semesters as $sem)
                                <a class="dropdown-item preview-item change-viewing-semester {{ (isset($semester) && $sem->id == $semester->id) ? 'bg-light' : '' }}"
                                    href="#" data-id="{{ $sem->id }}">
                                    <div class="preview-thumbnail">
                                        <div class="preview-icon {{ (isset($semester) && $sem->id == $semester->id) ? 'bg-info' : 'bg-inverse-info' }}">
                                            <i class="fa {{ (isset($semester) && $sem->id == $semester->id) ? 'fa-check text-white' : 'fa-book text-info' }}"></i>
                                        </div>
                                    </div>
                                    <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                                        <h6 class="preview-subject ellipsis mb-1 font-weight-normal {{ (isset($semester) && $sem->id == $semester->id) ? 'font-weight-bold' : '' }}">
                                            {{ $sem->name }}
                                        </h6>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </li>
                @endif
            @endif

            {{-- <li class="d-none d-md-block d-sm-block nav-item ml-4">
                <div class="text-dark">
                    <span><i class="mdi mdi-weather-sunny fa-2x cursor-pointer theme"></i></span>
                </div>
            </li> --}}

            <li class="nav-item dropdown">
                <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-language"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="messageDropdown">
                    @foreach ($languages as $key => $language)
                        <a class="dropdown-item preview-item" href="{{ url('set-language') . '/' . $language->code }}">
                            <div class="preview-thumbnail">
                                {{-- <img src="../../../assets/images/faces/face3.jpg" alt="image" class="profile-pic"> --}}
                            </div>
                            <div class="preview-item-content d-flex align-items-start flex-column justify-content-center">
                                <h6 class="preview-subject ellipsis mb-1 font-weight-normal">{{ $language->name }}</h6>
                                {{-- <p class="text-gray mb-0"> 18 Minutes ago </p> --}}
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                    @endforeach
                </div>
            </li>
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown" aria-expanded="true">
                    <div class="nav-profile-img">
                        <img src="{{ Auth::user()->image }}" alt="image">
                    </div>
                    <div class="nav-profile-text">
                        <p class="mb-1 text-black">{{ Auth::user()->first_name }}</p>
                    </div>
                </a>
                <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                    {{-- @can('update-admin-profile') --}}
                        <a class="dropdown-item" href="{{ route('auth.profile.edit') }}"><i class="fa fa-user mr-2"></i>{{ __('profile') }}</a>
                        <div class="dropdown-divider"></div>
                    {{-- @endcan --}}
                    <a class="dropdown-item" href="{{ route('auth.change-password.index') }}">
                        <i class="fa fa-refresh mr-2 text-success"></i>{{ __('change_password') }}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ url('cache-flush') }}">
                        <i class="fa fa-eraser mr-2 text-theme"></i> {{ __('cache_clear') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('auth.logout') }}">
                        <i class="fa fa-sign-out mr-2 text-danger"></i> {{ __('signout') }}
                    </a>
                </div>
            </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="fa fa-bars"></span>
        </button>
    </div>
</nav>
