@extends('layouts.full-screen')

@section('title')
    {{ __('wizard_settings') }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('assets/css/wizard-horizontal.css') }}">
@endsection

@section('content')
    <div class="wizard-card-body">

        {{-- Setup Progress bar --}}
        <div class="wizard-progress">
            <label class="d-flex justify-content-between">
                <span>{{ __('Setup Progress') }}</span>
                <span id="progress-text">0%</span>
            </label>
            <div class="progress">
                <div id="setup-progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%"
                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        {{-- Horizontal Steps Navigation Bar --}}
        <div class="wizard-steps-topbar">
            <ul class="nav" role="tablist">
                @php
                    $steps = [
                        [
                            'name' => 'system_settings',
                            'key'  => 'system_settings_wizard_checkMark',
                            'icon' => 'mdi mdi-settings',
                        ],
                        [
                            'name' => 'notification_settings',
                            'key'  => 'notification_settings_wizard_checkMark',
                            'icon' => 'mdi mdi-bell',
                        ],
                        [
                            'name' => 'email_settings',
                            'key'  => 'email_settings_wizard_checkMark',
                            'icon' => 'mdi mdi-email',
                        ],
                        [
                            'name' => 'verify_email',
                            'key'  => 'verify_email_wizard_checkMark',
                            'icon' => 'mdi mdi-email-check',
                        ],
                        [
                            'name' => 'email_template_settings',
                            'key'  => 'email_template_settings_wizard_checkMark',
                            'icon' => 'mdi mdi-email-open-outline',
                        ],
                        [
                            'name' => 'payment_settings',
                            'key'  => 'payment_settings_wizard_checkMark',
                            'icon' => 'mdi mdi-cash-multiple',
                        ],
                        [
                            'name' => 'third_party_api_settings',
                            'key'  => 'third_party_api_settings_wizard_checkMark',
                            'icon' => 'mdi mdi-webhook',
                        ],
                    ];
                @endphp

                @foreach ($steps as $index => $step)
                    <li class="nav-item">
                        <a class="step-item {{ $currentStep == $index ? 'active' : '' }} {{ $settings[$step['key']] == 1 ? 'completed' : '' }}"
                            id="steps-{{ $index + 1 }}" data-step="{{ $index }}" href="javascript:void(0)">
                            <i class="{{ $step['icon'] }}"></i>
                            <span class="number">{{ $index + 1 }}.</span> {{ __($step['name']) }}
                            @if ($settings[$step['key']] == 1)
                                <i class="mdi mdi-check-circle text-success"></i>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Content Area (full width) --}}
        <div class="wizard-content-area">

            <!-- Step 1: System Settings -->
            <div id="step-0" class="step-form {{ $currentStep == 0 ? 'active' : '' }}">
                <form id="form-system-settings" class="create-form-without-reset"
                    action="{{ route('system-settings.store') }}" method="POST" novalidate="novalidate"
                    enctype="multipart/form-data">
                    @csrf
                    @include('settings.forms.system-settings-form')
                    <div class="mt-4 border-top pt-3 text-right">
                        <input class="btn btn-theme ml-3 btn-next" id="next-btn-system" type="submit"
                            value="{{ __('Next') }}">
                    </div>
                </form>
            </div>

            <!-- Step 2: Notification Settings -->
            <div id="step-1" class="step-form {{ $currentStep == 1 ? 'active' : '' }}">
                <form id="form-notification-settings" class="edit-form"
                    action="{{ route('notification-setting.update') }}" method="POST" novalidate="novalidate">
                    @csrf
                    @method('PUT')
                    @include('settings.forms.fcm-form')
                    <div class="mt-4 border-top pt-3 text-right">
                        <input class="btn btn-secondary btn-previous" type="button" value="{{ __('Previous') }}">
                        <input class="btn btn-theme ml-3 btn-skip" type="button" value="{{ __('Skip') }}">
                        <input class="btn btn-theme ml-3 btn-next" type="submit" value="{{ __('Next') }}">
                    </div>
                </form>
            </div>

            <!-- Step 3: Email Settings -->
            <div id="step-2" class="step-form {{ $currentStep == 2 ? 'active' : '' }}">
                <h4 class="mb-4">{{ __('email_settings') }}</h4>
                <form id="verify_email" action="{{ route('system-settings.email.update') }}" method="POST"
                    novalidate="novalidate">
                    @csrf
                    @include('settings.forms.email-form')
                    <div class="mt-4 border-top pt-3 text-right">
                        <input class="btn btn-secondary btn-previous" type="button" value="{{ __('Previous') }}">
                        <input class="btn btn-theme ml-3 btn-next" type="button" value="{{ __('Next') }}">
                    </div>
                </form>
            </div>

            <!-- Step 4: Verify Email -->
            <div id="step-3" class="step-form {{ $currentStep == 3 ? 'active' : '' }}">
                <form id="send_verification_email" action="{{ route('system-settings.email.verify') }}"
                    method="POST">
                    @csrf
                    <div class="border border-secondary rounded-lg my-4 mx-1">
                        <div class="col-md-12 mt-3">
                            <h4>{{ __('verify_email') }}</h4>
                        </div>
                        <div class="col-12 mb-3">
                            <hr class="mt-0">
                        </div>
                        <div class="row my-4 mx-1">
                            <div class="form-group col-md-6">
                                <label for="verify_email_address">{{ __('email') }}</label>
                                <input name="verify_email" id="verify_email_address" type="email" required
                                    placeholder="{{ __('email') }}" class="form-control" />
                                <small
                                    class="text-muted">{{ __('NOTE : An email will be sent to test if your email settings are correct') }}</small>
                            </div>
                            <div id="error-div" style="display: none;" class="col-12 mt-3">
                                <h6 class="text-danger">Error : </h6>
                                <pre id="error" class="bg-light p-2"></pre>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3 text-right">
                        <input class="btn btn-secondary btn-previous" type="button" value="{{ __('Previous') }}">
                        <input class="btn btn-theme ml-3 btn-next" type="button" value="{{ __('Next') }}">
                    </div>
                </form>
            </div>

            <!-- Step 5: Email Template Settings -->
            <div id="step-4" class="step-form {{ $currentStep == 4 ? 'active' : '' }}">
                <h4 class="mb-4">{{ __('email_template_settings') }}</h4>
                <form id="form-email-template-settings" class="email-template-setting-form"
                    action="{{ route('system-settings.email-template.update', 1) }}" method="PUT"
                    novalidate="novalidate">
                    @csrf
                    @include('settings.forms.email-template-form')
                    <div class="mt-4 border-top pt-3 text-right">
                        <input class="btn btn-secondary btn-previous" type="button"
                            value="{{ __('Previous') }}">
                        <input class="btn btn-theme ml-3 btn-skip" type="button" value="{{ __('Skip') }}">
                        <input class="btn btn-theme ml-3 btn-next" type="button" value="{{ __('Next') }}">
                    </div>
                </form>
            </div>

            <!-- Step 6: Payment Settings -->
            <div id="step-5" class="step-form {{ $currentStep == 5 ? 'active' : '' }}">
                <h4 class="mb-4">{{ __('payment_settings') }}</h4>
                <form id="form-payment-settings" class="create-form-without-reset"
                    action="{{ route('system-settings.payment.update') }}" method="POST"
                    novalidate="novalidate" enctype="multipart/form-data">
                    @csrf
                    @include('settings.forms.payment-form')
                    <div class="mt-4 border-top pt-3 text-right">
                        <input class="btn btn-secondary btn-previous" type="button"
                            value="{{ __('Previous') }}">
                        <input class="btn btn-theme ml-3 btn-skip" type="button" value="{{ __('Skip') }}">
                        <input class="btn btn-theme ml-3 btn-next" type="button" value="{{ __('Next') }}">
                    </div>
                </form>
            </div>

            <!-- Step 7: Third Party API Settings -->
            <div id="step-6" class="step-form {{ $currentStep == 6 ? 'active' : '' }}">
                <h4 class="mb-4">{{ __('third_party_api_settings') }}</h4>
                <form id="form-third-party-api-settings" class="create-form-without-reset"
                    action="{{ route('system-settings.third-party.update') }}" method="POST"
                    novalidate="novalidate" enctype="multipart/form-data">
                    @csrf
                    @include('settings.forms.third-party-apis-form')
                    <div class="mt-4 border-top pt-3 text-right">
                        <input class="btn btn-secondary btn-previous" type="button"
                            value="{{ __('Previous') }}">
                        <input class="btn btn-theme ml-3 btn-finish" id="next-btn-third-party-api" type="button"
                            value="{{ __('Finish') }}">
                    </div>
                </form>
            </div>

        </div>{{-- /.wizard-content-area --}}
    </div>{{-- /.wizard-card-body --}}
@endsection

@section('js')
    <script>
        window.onload = setTimeout(() => {
            $('.email-template').trigger('change');
        }, 500);

        $('.email-template').change(function(e) {
            e.preventDefault();
            let type = $('input[name="template"]:checked').val();

            if (type == 'school-email-template') {
                $('.school-email-template').show(500);
                $('.school-reject-template').hide(500);
                $('.school-inquiry-template').hide(500);
                // Remove required attribute from hidden form
                $('.school-reject-template :input').prop('required', false);
                // Add required attribute to visible form
                $('.school-email-template :input[data-required]').prop('required', true);
                $('.school-inquiry-template :input').prop('required', false);
            } else if (type == 'school-reject-template') {
                $('.school-reject-template').show(500);
                $('.school-email-template').hide(500);
                $('.school-inquiry-template').hide(500);
                // Remove required attribute from hidden form
                $('.school-email-template :input').prop('required', false);
                // Add required attribute to visible form
                $('.school-reject-template :input[data-required]').prop('required', true);
                $('.school-inquiry-template :input').prop('required', false);
            } else if (type == 'school-inquiry-template') {
                $('.school-inquiry-template').show(500);
                $('.school-email-template').hide(500);
                $('.school-reject-template').hide(500);
                // Remove required attribute from hidden form
                $('.school-email-template :input').prop('required', false);
                // Add required attribute to visible form
                $('.school-inquiry-template :input[data-required]').prop('required', true);
                $('.school-reject-template :input').prop('required', false);
            }
        });
    </script>

    <script>
        $(document).ready(function() {



            // Cache selectors
            const $nextButtons = $('.btn-next');
            const $prevButtons = $('.btn-previous');
            const $skipButtons = $('.btn-skip');
            const $finishButtons = $('.btn-finish');
            const $forms = $('.step-form');
            const $tabs = $('.step-item'); // Changed to target new step items
            const $progressBar = $('#setup-progress-bar');
            const $progressText = $('#progress-text');

            // Get current step from PHP
            let currentFormIndex = {{ $currentStep ?? 0 }};

            // Get completed steps from database
            const completedSteps = {
                'system_settings_wizard_checkMark': {{ $settings['system_settings_wizard_checkMark'] ?? 0 }},
                'notification_settings_wizard_checkMark': {{ $settings['notification_settings_wizard_checkMark'] ?? 0 }},
                'email_settings_wizard_checkMark': {{ $settings['email_settings_wizard_checkMark'] ?? 0 }},
                'verify_email_wizard_checkMark': {{ $settings['verify_email_wizard_checkMark'] ?? 0 }},
                'email_template_settings_wizard_checkMark': {{ $settings['email_template_settings_wizard_checkMark'] ?? 0 }},
                'payment_settings_wizard_checkMark': {{ $settings['payment_settings_wizard_checkMark'] ?? 0 }},
                'third_party_api_settings_wizard_checkMark': {{ $settings['third_party_api_settings_wizard_checkMark'] ?? 0 }}
            };

            // Step names mapping
            const stepNames = [
                'system_settings_wizard_checkMark',
                'notification_settings_wizard_checkMark',
                'email_settings_wizard_checkMark',
                'verify_email_wizard_checkMark',
                'email_template_settings_wizard_checkMark',
                'payment_settings_wizard_checkMark',
                'third_party_api_settings_wizard_checkMark'
            ];

            // Initialize wizard
            initializeWizard();

            function initializeWizard() {
                // Ensure correct initial state without redundant flashes
                $forms.each(function(idx) {
                    if (idx === currentFormIndex) {
                        $(this).show().addClass('active');
                    } else {
                        $(this).hide().removeClass('active');
                    }
                });

                $tabs.removeClass('active').eq(currentFormIndex).addClass('active');
                updateTabStates();
                updateProgressBar();

                // Initialize form validation states
                $forms.each(function() {
                    const $form = $(this).find('form');
                    if ($form.length) {
                        $form.find(':input[required]').attr('data-required', 'true');
                    }
                });

                // Trigger initial template change if exists
                if ($('.email-template:checked').length) {
                    $('.email-template:checked').trigger('change');
                }
            }

            function validateForm($form) {
                let isValid = true;
                $form.find(':input:visible').each(function() {
                    const $input = $(this);
                    if ($input.prop('required') && !$input.val()) {
                        isValid = false;
                        $input.addClass('is-invalid');
                    } else {
                        $input.removeClass('is-invalid');
                    }
                });
                return isValid;
            }

            function updateProgressBar() {
                const totalSteps = stepNames.length;
                const completedCount = Object.values(completedSteps).filter(v => v == 1).length;
                const percentage = Math.round((completedCount / totalSteps) * 100);

                $progressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
                $progressText.text(percentage + '%');
            }

            function handleStepCompletion(stepIndex, formData) {
                const stepName = stepNames[stepIndex];
                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: '/update-wizard-session',
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        name: stepName
                    },
                    success: function(response) {
                        completedSteps[stepName] = 1;
                        $tabs.eq(stepIndex).addClass('completed');
                        if (!$tabs.eq(stepIndex).find('.mdi-check-circle').length) {
                            $tabs.eq(stepIndex).append(
                                '<i class="mdi mdi-check-circle ml-auto text-success"></i>');
                        }

                        updateProgressBar();

                        if (formData) {
                            const $form = $($forms[stepIndex]).find('form');
                            // If it's the verify email step, handle it specifically if needed
                            // But usually, the route handles the verification
                            $.ajax({
                                url: $form.attr('action'),
                                method: $form.attr('method'),
                                data: formData,
                                processData: $form.attr('enctype') === 'multipart/form-data' ?
                                    false : true,
                                contentType: $form.attr('enctype') === 'multipart/form-data' ?
                                    false : 'application/x-www-form-urlencoded; charset=UTF-8',
                                success: function() {
                                    moveToNextStep();
                                },
                                error: function(error) {
                                    console.error('Error submitting form:', error);
                                    if (error.responseJSON && error.responseJSON.message) {
                                        alert(error.responseJSON.message);
                                    } else {
                                        alert('Error saving form data. Please try again.');
                                    }
                                }
                            });
                        } else {
                            moveToNextStep();
                        }
                    },
                    error: function(error) {
                        console.error('Error updating step:', error);
                        alert('Error updating step. Please try again.');
                    }
                });
            }

            function moveToNextStep() {
                const nextStep = findNextIncompleteStep();
                if (nextStep !== currentFormIndex) {
                    currentFormIndex = nextStep;
                    showStep(currentFormIndex);
                } else if (currentFormIndex < stepNames.length - 1) {
                    currentFormIndex++;
                    showStep(currentFormIndex);
                }
            }

            function showStep(index) {
                // Update form visibility
                $forms.hide().removeClass('active');
                $($forms[index]).show().addClass('active');

                // Update tab visibility
                $tabs.removeClass('active').eq(index).addClass('active');

                updateTabStates();
                updateProgressBar();
                $('.wizard-content').scrollTop(0);

                // Auto-scroll mobile tabs to active step
                if (window.innerWidth <= 992) {
                    const $container = $('.wizard-steps-topbar');
                    const $activeTab = $tabs.eq(index);
                    if ($activeTab.length) {
                        const scrollPos = $activeTab.position().left + $container.scrollLeft() - 20; // 20px padding
                        $container.animate({
                            scrollLeft: scrollPos
                        }, 300);
                    }
                }
            }

            function updateTabStates() {
                $tabs.each(function(i) {
                    const $tab = $(this);
                    if (i > currentFormIndex && completedSteps[stepNames[i]] == 0) {
                        $tab.css('opacity', '0.6').css('pointer-events', 'none');
                    } else {
                        $tab.css('opacity', '1').css('pointer-events', 'auto');
                    }
                });
            }

            function findNextIncompleteStep() {
                for (let i = 0; i < stepNames.length; i++) {
                    if (completedSteps[stepNames[i]] === 0) {
                        return i;
                    }
                }
                return currentFormIndex;
            }

            // Next button click handler
            $nextButtons.on('click', function(e) {
                // Trigger TinyMCE save to sync editor content to textareas
                if (typeof tinymce !== 'undefined') {
                    tinymce.triggerSave();
                }
                const $currentForm = $($forms[currentFormIndex]).find('form');
                if ($currentForm.length) {
                    if (validateForm($currentForm)) {
                        // Check if it's a file upload form
                        let formData;
                        if ($currentForm.attr('enctype') === 'multipart/form-data') {
                            formData = new FormData($currentForm[0]);
                        } else {
                            formData = $currentForm.serialize();
                        }
                        handleStepCompletion(currentFormIndex, formData);
                    }
                } else {
                    handleStepCompletion(currentFormIndex);
                }
            });

            // Special handling for Verify & Next button
            $('#step-3 .btn-next').off('click').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const $form = $btn.closest('form');
                if (validateForm($form)) {
                    const formData = $form.serialize();
                    $('#error-div').hide();

                    // Disable button and show loading text
                    $btn.prop('disabled', true).val('{{ __('Please Wait') }}...');

                    $.ajax({
                        url: $form.attr('action'),
                        method: $form.attr('method'),
                        data: formData,
                        success: function(response) {
                            if (response.error) {
                                $('#error-div').show();
                                let errorMessage = response.message || 'Verification failed';
                                if (response.data) {
                                    if (response.data.error) {
                                        errorMessage += "\n\n" + response.data.error;
                                    }
                                    if (response.data.stacktrace) {
                                        errorMessage += "\n\nStacktrace:\n" + response.data
                                            .stacktrace;
                                    }
                                }
                                $('#error').text(errorMessage);
                            } else {
                                handleStepCompletion(3);
                            }
                        },
                        error: function(error) {
                            $('#error-div').show();
                            let errorMessage = error.responseJSON.message ||
                                'Verification failed';
                            if (error.responseJSON.data) {
                                if (error.responseJSON.data.error) {
                                    errorMessage += "\n\n" + error.responseJSON.data.error;
                                }
                                if (error.responseJSON.data.stacktrace) {
                                    errorMessage += "\n\nStacktrace:\n" + error.responseJSON
                                        .data.stacktrace;
                                }
                            }
                            $('#error').text(errorMessage);
                        },
                        complete: function() {
                            // Re-enable button and restore text
                            $btn.prop('disabled', false).val('{{ __('Next') }}');
                        }
                    });
                }
            });

            // Skip button click handler
            $skipButtons.on('click', function(e) {
                e.preventDefault();
                handleStepCompletion(currentFormIndex);
            });

            // Previous button click handler
            $prevButtons.on('click', function(e) {
                e.preventDefault();
                if (currentFormIndex > 0) {
                    currentFormIndex--;
                    showStep(currentFormIndex);
                }
            });

            // Finish button click handler
            $finishButtons.on('click', function(e) {
                e.preventDefault();
                const $currentForm = $($forms[currentFormIndex]).find('form');
                if ($currentForm.length && validateForm($currentForm)) {
                    const formData = $currentForm.serialize();
                    handleStepCompletion(currentFormIndex, formData);
                    // Redirect after a short delay to allow AJAX to complete
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1000);
                } else {
                    window.location.href = '/dashboard';
                }
            });

            // Tab click handler
            $tabs.on('click', function() {
                const index = $(this).data('step');
                if ($(this).css('pointer-events') !== 'none') {
                    currentFormIndex = index;
                    showStep(currentFormIndex);
                }
            });
        });
    </script>
@endsection
