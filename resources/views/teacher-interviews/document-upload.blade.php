<!DOCTYPE html>
<html lang="en">
@php
    $lang = Session::get('language');
    $cache = app(\App\Services\CachingService::class);
    $systemSettings = $cache->getSystemSettings();
    
    // We try to get school settings if application has school_id, else fallback
    $schoolSettings = [];
    if (isset($application->school_id)) {
        $schoolSettings = $cache->getSchoolSettings('*', $application->school_id);
    }
@endphp
@if($lang)
    @if ($lang->is_rtl)
        <html lang="en" dir="rtl">
    @else
        <html lang="en" dir="ltr">
    @endif
@else
    <html lang="en" dir="ltr">
@endif
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('assets/home_page/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <title>{{ __('Document Upload for Verification') }} || {{ $schoolSettings['school_name'] ?? config('app.name') }}</title>

    <style>
        :root {
            --primary-color: {{ $systemSettings['theme_primary_color'] ?? '#56cc99' }};
            --secondary-color: {{ $systemSettings['theme_secondary_color'] ?? '#215679' }};
            --secondary-color1: {{ $systemSettings['theme_secondary_color_1'] ?? '#38a3a5' }};
            --primary-background-color: {{ $systemSettings['theme_primary_background_color'] ?? '#f2f5f7' }};
            --text--secondary-color: {{ $systemSettings['theme_text_secondary_color'] ?? '#5c788c' }};
        }
        .btn-theme {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        .btn-theme:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        .auth-form-light {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .text-theme {
            color: var(--primary-color) !important;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper login-d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-xl-6 mx-auto auth-form-light p-4 m-4">
                        
                        <div class="rounded-lg text-left p-5">
                            <div class="brand-logo text-center">
                                @if (!empty($schoolSettings['horizontal_logo']))
                                    <img class="img-fluid w-25" src="{{ $schoolSettings['horizontal_logo'] }}" alt="logo">    
                                @elseif (!empty($systemSettings['horizontal_logo']))
                                    <img class="img-fluid w-25" src="{{ $systemSettings['horizontal_logo'] }}" alt="logo">
                                @else
                                    <h2 class="text-theme">{{ $schoolSettings['school_name'] ?? 'School Recruitment' }}</h2>
                                @endif
                            </div>

                            <h4 class="text-center mt-3">{{ __('Document Upload for Verification') }}</h4>
                            <h6 class="font-weight-light text-center">
                                {{ __('Hello') }} <strong>{{ $application->name }}</strong>,<br>
                                {{ __('Please upload your original documents before') }} <strong>{{ \Carbon\Carbon::parse($application->document_verification_date)->subDay()->format('d M, Y') }}</strong>.
                            </h6>

                            @if(session('success'))
                                <div class="alert alert-success mt-4">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger mt-4">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!session('success'))
                                <form action="{{ route('career.document-upload.submit', $token) }}" method="POST" enctype="multipart/form-data" class="pt-3">
                                    @csrf

                                    <div class="form-group mb-3">
                                        <label class="form-label">{{ __('Identity Proof (Aadhaar/PAN)') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="identity_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <small class="form-text text-muted">{{ __('Max size: 2MB. Format: PDF, JPG, PNG') }}</small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label">{{ __('Degree / Final Year Mark Sheet (Higher Education)') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="degree_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <small class="form-text text-muted">{{ __('Max size: 2MB. Format: PDF, JPG, PNG') }}</small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label">{{ __('Experience Letter (Optional)') }}</label>
                                        <input type="file" name="experience_letter" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="form-text text-muted">{{ __('Max size: 2MB. Format: PDF, JPG, PNG') }}</small>
                                    </div>

                                    <div class="mt-4 d-grid gap-2">
                                        <button class="btn btn-theme btn-lg font-weight-medium auth-form-btn" type="submit">
                                            {{ __('UPLOAD DOCUMENTS') }}
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- content-wrapper ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    
</body>
</html>
