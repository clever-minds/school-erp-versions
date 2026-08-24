<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ __('Teacher Registration') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; padding-top: 40px; }
        .reg-card { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 40px; }
        .header { text-align: center; border-bottom: 2px solid #56cc99; padding-bottom: 20px; margin-bottom: 30px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <div class="reg-card">
                <div class="header">
                    @php
                        $systemSettings = app(\App\Services\CachingService::class)->getSystemSettings();
                    @endphp
                    @if(!empty($systemSettings['horizontal_logo']))
                        <img src="{{ $systemSettings['horizontal_logo'] }}" alt="Logo" style="max-height: 60px; margin-bottom: 20px;">
                    @else
                        <h2>{{ __('Account Setup') }}</h2>
                    @endif
                    <h5 class="text-muted">{{ __('Create Your Teacher Portal Account') }}</h5>
                </div>

                @if(isset($success_message))
                    <div class="alert alert-success text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle text-success mb-3" viewBox="0 0 16 16">
                          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                          <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                        </svg>
                        <h3 class="text-success mb-3">{{ $success_message }}</h3>
                        <p class="text-muted">{{ __('You can now close this window or proceed to login to your teacher portal.') }}</p>
                        <a href="{{ route('login') }}" class="btn btn-primary mt-3">{{ __('Go to Login') }}</a>
                    </div>
                @else
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p>Welcome, <strong>{{ $application->name }}</strong>! Please complete your profile and set a password to finish registration.</p>

                <form action="{{ route('career.teacher-registration.submit', $token) }}" method="POST" class="mt-4" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('First Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ explode(' ', $application->name)[0] }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Last Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ count(explode(' ', $application->name)) > 1 ? explode(' ', $application->name)[1] : '' }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Email Address') }}</label>
                            <input type="email" class="form-control" value="{{ $application->email }}" disabled>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Mobile') }} <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control" value="{{ $application->phone }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Gender') }} <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control" required>
                                <option value="male">{{ __('Male') }}</option>
                                <option value="female">{{ __('Female') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Date of Birth') }} <span class="text-danger">*</span></label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Qualification') }} <span class="text-danger">*</span></label>
                            <input type="text" name="qualification" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Current Address') }} <span class="text-danger">*</span></label>
                            <textarea name="current_address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Permanent Address') }} <span class="text-danger">*</span></label>
                            <textarea name="permanent_address" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Profile Image') }}</label>
                            <input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/jpg">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">{{ __('Complete Registration') }}</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
