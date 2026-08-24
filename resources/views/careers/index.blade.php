@extends('layouts.home_page.master')

@section('content')
<style>
    .career-section {
        padding: 80px 0;
        background-color: var(--primary-background-color, #f2f5f7);
        min-height: 80vh;
    }
    .career-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        padding: 40px;
    }
    .career-title {
        color: var(--secondary-color, #215679);
        font-weight: 700;
        margin-bottom: 30px;
    }
    .career-text-wrapper {
        padding: 20px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .career-text-wrapper h1 {
        color: var(--secondary-color, #215679);
        font-weight: 700;
        margin-bottom: 20px;
        font-size: 2.5rem;
    }
    .career-text-wrapper p {
        color: var(--text--secondary-color, #5c788c);
        font-size: 1.1rem;
        line-height: 1.8;
        margin-bottom: 30px;
    }
    .career-features {
        list-style: none;
        padding: 0;
    }
    .career-features li {
        margin-bottom: 15px;
        font-size: 1.1rem;
        color: var(--secondary-color, #215679);
        display: flex;
        align-items: center;
    }
    .career-features li i {
        color: var(--primary-color, #56cc99);
        margin-right: 15px;
        font-size: 1.2rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color, #56cc99);
        box-shadow: 0 0 0 0.2rem rgba(86, 204, 153, 0.25);
    }
</style>

<div class="main">
    <section class="career-section">
        <div class="container">
            <div class="row align-items-center">
                <!-- Left Side: Nice Text -->
                <div class="col-md-6 mb-5 mb-md-0">
                    <div class="career-text-wrapper">
                        <h1>{{ __('Build Your Career With Us') }}</h1>
                        <p>{{ __('We are always looking for passionate, talented, and creative individuals to join our team. If you are driven by excellence and want to make a real impact in the education sector, you are in the right place.') }}</p>
                        
                        <ul class="career-features">
                            <li><i class="fa-solid fa-circle-check"></i> {{ __('Innovative Work Environment') }}</li>
                            <li><i class="fa-solid fa-circle-check"></i> {{ __('Career Growth & Development') }}</li>
                            <li><i class="fa-solid fa-circle-check"></i> {{ __('Collaborative Team Culture') }}</li>
                            <li><i class="fa-solid fa-circle-check"></i> {{ __('Make a Difference in Education') }}</li>
                        </ul>
                    </div>
                </div>

                <!-- Right Side: Form -->
                <div class="col-md-6">
                    <div class="career-card">
                        <h3 class="career-title">{{ __('Apply Now') }}</h3>
                        
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ url('/careers') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="school_id" class="form-label">{{ __('Select School') }} <span class="text-danger">*</span></label>
                                    <select name="school_id" id="school_id" class="form-select" required>
                                        <option value="">{{ __('Choose a school...') }}</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                                {{ $school->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="name" class="form-label">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="{{ __('Enter your full name') }}">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="email" class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required placeholder="{{ __('Enter your email') }}">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="phone" class="form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" required placeholder="{{ __('Enter your phone number') }}">
                                </div>

                                <div class="col-md-12 mb-4">
                                    <label for="resume" class="form-label">{{ __('Upload Resume (PDF, DOC, DOCX)') }} <span class="text-danger">*</span></label>
                                    <input type="file" name="resume" id="resume" class="form-control" accept=".pdf,.doc,.docx" required>
                                    <small class="text-muted">{{ __('Max file size: 5MB') }}</small>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="submit" class="commonBtn w-100 py-2" style="border-radius: 8px;">{{ __('Submit Application') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
