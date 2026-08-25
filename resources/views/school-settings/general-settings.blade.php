@extends('layouts.master')

@section('title')
    {{ __('general_settings') }}
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('general_settings') }}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form class="create-form-without-reset" action="{{ route('school-settings.store') }}" method="POST"
                            novalidate="novalidate" enctype="multipart/form-data"
                            data-success-function="formSuccessFunction">
                            @csrf
                            <div class="border border-secondary rounded-lg mb-2">
                                <div class="row my-4 mx-1">
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="school_name">{{ __('school_name') }} <span
                                                class="text-danger">*</span></label>
                                        <input name="school_name" id="school_name"
                                            value="{{ $settings['school_name'] ?? '' }}" type="text" maxlength="73" required
                                            placeholder="{{ __('school_name') }}" class="form-control" />
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="school_email">{{ __('school_email') }} <span
                                                class="text-danger">*</span></label>
                                        <input name="school_email" id="school_email"
                                            value="{{ $settings['school_email'] ?? '' }}" type="email" required
                                            placeholder="{{ __('school_email') }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="school_code">{{ __('school_code') }}</label>
                                        <input name="school_code" id="school_code" value="{{ Auth::user()->school->code }}"
                                            type="text" disabled placeholder="{{ __('school_code') }}"
                                            class="form-control" />
                                    </div>

                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="school_phone">{{ __('school_phone') }} <span
                                                class="text-danger">*</span></label>
                                        <input name="school_phone" id="school_phone"
                                            value="{{ $settings['school_phone'] ?? '' }}" type="number" required
                                            placeholder="{{ __('school_phone') }}"
                                            class="form-control remove-number-increment" />
                                    </div>

                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="school_phone">{{ __('school_board') }}</label>
                                        <select name="school_board_id" id="school_board" class="form-control">
                                            <option value="">{{ __('select_school_board') }}</option>
                                            @foreach ($schoolBoard as $board)
                                                <option value="{{ $board->id }}" {{ ($board->school_board->sch_board_id ?? null) == $board->id ? 'selected' : '' }}> {{ $board->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="school_tagline">{{ __('school_tagline') }} <span
                                                class="text-danger">*</span></label>
                                        <textarea name="school_tagline" id="school_tagline" required
                                            placeholder="{{ __('school_tagline') }}"
                                            class="form-control">{{ $settings['school_tagline'] ?? '' }}</textarea>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="school_address">{{ __('school_address') }} <span
                                                class="text-danger">*</span></label>
                                        <textarea name="school_address" id="school_address" required
                                            placeholder="{{ __('school_address') }}"
                                            class="form-control">{{ $settings['school_address'] ?? '' }}</textarea>
                                    </div>

                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="date_format">{{ __('date_format') }}</label>
                                        <select name="date_format" id="date_format" required class="form-control">
                                            @foreach ($getDateFormat as $key => $dateformat)
                                                <option value="{{ $key }}" {{ isset($settings['date_format']) && $settings['date_format'] == $key ? 'selected' : '' }}>{{ $dateformat }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="time_format">{{ __('time_format') }}</label>
                                        <select name="time_format" id="time_format" required class="form-control">
                                            @foreach ($getTimeFormat as $key => $timeFormat)
                                                <option value="{{ $key }}" {{ isset($settings['time_format']) && $settings['time_format'] == $key ? 'selected' : '' }}>{{ $timeFormat }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row my-4 mx-1">
                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="favicon">{{ __('favicon') }} <span class="text-danger">*</span> <small
                                                class="text-muted">(32x32 pixels)</small></label>
                                        <input type="file" name="favicon" id="favicon-input" class="file-upload-default"
                                            accept="image/*" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" id="favicon" class="form-control file-upload-info"
                                                disabled="" placeholder="{{ __('favicon') }}" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <img id="favicon-preview" height="32px" width="32px"
                                                src='{{ !empty($settings['favicon']) ? url($settings['favicon']) : asset('assets/no_image_available.jpg') }}'
                                                alt="Favicon" class="">
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="horizontal_logo">{{ __('horizontal_logo') }} <span
                                                class="text-danger">*</span> <small class="text-muted">(250x50
                                                pixels)</small></label>
                                        <input type="file" name="horizontal_logo" id="horizontal-logo-input"
                                            class="file-upload-default" accept="image/*" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" id="horizontal_logo" class="form-control file-upload-info"
                                                disabled="" placeholder="{{ __('horizontal_logo') }}" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <img id="horizontal-logo-preview" height="60px" width="250px"
                                                src='{{ !empty($settings['horizontal_logo']) ? url($settings['horizontal_logo']) : asset('assets/no_image_available.jpg') }}'
                                                alt="Horizontal Logo" class="">
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="vertical_logo">{{ __('vertical_logo') }} <span
                                                class="text-danger">*</span> <small class="text-muted">(100x100
                                                pixels)</small></label>
                                        <input type="file" name="vertical_logo" id="vertical-logo-input"
                                            class="file-upload-default" accept="image/*" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info" id="vertical_logo"
                                                disabled="" placeholder="{{ __('vertical_logo') }}" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <img id="vertical-logo-preview" height="100px" width="100px"
                                                src='{{ !empty($settings['vertical_logo']) ? url($settings['vertical_logo']) : asset('assets/no_image_available.jpg') }}'
                                                alt="Vertical Logo" class="">
                                        </div>
                                    </div>

                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="login_page_logo">{{ __('login_page_image') }} <small
                                                class="text-muted">(920x1080 pixels)</small></label>
                                        <input type="file" name="login_page_logo" id="login-page-logo-input"
                                            class="file-upload-default" accept="image/*" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info" id="login_page_logo"
                                                disabled="" placeholder="{{ __('login_page_logo') }}" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <img id="login-page-logo-preview" height="216px" width="184px"
                                                src='{{ !empty($settings['login_page_logo']) ? url($settings['login_page_logo']) : asset('assets/no_image_available.jpg') }}'
                                                alt="Login Page Logo" class="">
                                        </div>
                                    </div>


                                    {{-- Signature --}}
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="image">{{ __('signature') }} <small class="text-info">({{ __('note_this_signature_image_will_be_used_on_id_cards_and_certificates') }})</small> </label>
                                        <input type="file" name="signature" accept="image/jpg,image/png,image/jpeg,image/svg" class="file-upload-default" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" id="image" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                            </span>
                                        </div>
                                        @if ($settings['signature'] ?? '')
                                            <div id="signature">
                                                <img src="{{ $settings['signature'] }}" class="img-fluid w-25" alt="">
                                            </div>
                                        @endif
                                    </div>
                                    {{-- End signature --}}

                                    <div class="form-group col-md-12 col-sm-12 mt-3">
                                        <label for="school_google_map_link">{{ __('google_map_link')}} <span
                                                class="text-danger">*</span><span
                                                class="text-small text-info">({{ __('html_code') }})</span></label>
                                        <div class="input-group mb-3 w-100 d-flex flex-column">
                                            <input type="text" class="form-control w-100" required name="google_map_link"
                                                placeholder="{{ __('google_map_link') }}"
                                                value="{{ $settings['google_map_link'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        {{-- Fee Reminder Days (Before Due Date) --}}
                                        <label for="fee_reminder_days_before_due">{{ __('fee_reminder_days')}} <span
                                                class="text-danger">*</span> <span
                                                class="text-small text-info">{{ __('before_due_date') }}</span></label>
                                        <div class="input-group mb-3 d-flex flex-column">
                                            <input type="number" class="form-control w-100" required
                                                name="fees_remainder_duration"
                                                placeholder="{{ __('fees_remainder_duration') }}"
                                                value="{{ $settings['fees_remainder_duration'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group col-md-8 col-sm-12">
                                        <label for="student_web_url">{{ __('student_web_url') }}</label>
                                        @php
                                            if (isset($systemSettings['student_web_url']) && !empty($systemSettings['student_web_url'])) {
                                                $studentWebUrl = rtrim($systemSettings['student_web_url'], '/') . '/student/auth/' . (Auth::user()->school->code ?? '');
                                            } else {
                                                $studentWebUrl = '';
                                            }
                                        @endphp
                                        <input name="student_web_url" id="student_web_url" value="{{ $studentWebUrl }}"
                                            type="text" readonly placeholder="{{ __('student_web_url') }}"
                                            class="form-control" />
                                    </div>
                                </div>
                            </div>
                            <div class="border border-secondary rounded-lg my-4 mx-1">
                                <div class="col-md-12 mt-3">
                                    <h4>{{__("Domain Settings")}}</h4>
                                </div>
                                <div class="col-12 mb-3">
                                    <hr class="mt-0">
                                </div>
                                <div class="row my-4 mx-1">
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label>{{ __('domain') . ' ' . __('type') }} <span
                                                class="text-danger">*</span></label><br>
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('domain_type', 'default', false, ['class' => 'default', ($domain_type == "default") ? "checked" : ""]) !!}{{ __('default') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('domain_type', 'custom', false, ['class' => 'custom', ($domain_type == "custom") ? "checked" : ""]) !!}{{ __('custom') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-4 defaultDomain" style="display: none">
                                        <label for="school_domain">{{ __('default_domain')}}</label>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control domain-pattern" name="domain"
                                                placeholder="{{ __('domain') }}" aria-label="Recipient's username"
                                                aria-describedby="basic-addon2" disabled
                                                value="{{ ($domain_type == "default" && ($settings['domain'] ?? null)) ? ($settings['domain'] ?? null) : "" }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text form-control-left-radius-0 text-body"
                                                    id="basic-addon2">.{{ $baseUrlWithoutScheme }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-4 customDomain" style="display: none">
                                        <label for="school_domain">{{ __('custom_domain')}}</label>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control domain-pattern" name="domain"
                                                placeholder="{{ __('domain') }}" aria-label="Recipient's username"
                                                aria-describedby="basic-addon2" disabled
                                                value="{{ ($domain_type == "custom" && ($settings['domain'] ?? null)) ? ($settings['domain'] ?? "") : "" }}">
                                        </div>
                                    </div>
                                    @if (!env('DEMO_MODE'))
                                        <div class="form-group col-sm-12 col-md-4 serverinfo" style="display: none">
                                            <label for="serinfo">{{ __('server_info')}}</label>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" name="server_ip"
                                                    placeholder="{{ __('domain') }}" aria-describedby="basic-addon2" disabled
                                                    value="{{ $_SERVER['SERVER_ADDR'] ?? '127.0.0.1' }}">
                                            </div>
                                        </div>
                                    @endif
                                    <div class="mx-4 text-justify text-uppercase">
                                        <small
                                            class="text-danger">{{ __('Note : If You are using Custom Domain then you have add a dns entry with pointing the server ip address.') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="border border-secondary rounded-lg my-4 mx-1">
                                <div class="col-md-12 mt-3">
                                    <h4>{{__("Roll Number Settings")}}</h4>
                                </div>
                                <div class="col-12 mb-3">
                                    <hr class="mt-0">
                                </div>
                                <div class="form-group col-md-12 col-sm-12">
                                    <label for="roll-number-order">{{__("Roll Number Sorting")}}</label>
                                    <input type="hidden" id="roll-number-sort-column" name="roll_number_sort_column"
                                        value="{{ $settings['roll_number_sort_column'] ?? "" }}">
                                    <input type="hidden" id="roll-number-sort-order" name="roll_number_sort_order"
                                        value="{{ $settings['roll_number_sort_order'] ?? "" }}">
                                    <select name="" id="roll-number-order" class="form-control" required>
                                        <option value="" hidden="">-- {{__('Select')}} --</option>
                                        <option value="first_name,asc">{{__("First Name - Ascending")}}</option>
                                        <option value="first_name,desc">{{__("First Name - Descending")}}</option>
                                        <option value="last_name,asc">{{__("Last Name - Ascending")}}</option>
                                        <option value="last_name,desc">{{__("Last Name - Descending")}}</option>
                                    </select>

                                    <div class="form-check">
                                        <label class="form-check-label"> <input type="checkbox" class="form-check-input"
                                                name="change_roll_number" id="change-roll-ckh-settings" value="1">
                                            {{ __('Change Roll Number for All Classes') }} <i
                                                class="input-helper"></i></label>
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="border border-secondary rounded-lg mb-3">--}}
                                {{-- <h3 class="col-12 page-title mt-3 ">--}}
                                    {{-- {{ __('Currency Settings') }}--}}
                                    {{-- </h3>--}}
                                {{-- <div class="row my-4 mx-1">--}}
                                    {{-- <div class="form-group col-md-3 col-sm-12">--}}
                                        {{-- <label for="currency_code">{{__('currency_code')}} <span
                                                class="text-danger">*</span></label>--}}
                                        {{-- <input name="currency_code" id="currency_code"
                                            value="{{ $settings['currency_code'] ?? ''}}" type="text"
                                            placeholder="{{__('currency_code')}}" class="form-control" required />--}}
                                        {{-- </div>--}}
                                    {{-- <div class="form-group col-md-3 col-sm-12">--}}
                                        {{-- <label for="currency_symbol">{{__('currency_symbol')}} <span
                                                class="text-danger">*</span></label>--}}
                                        {{-- <input name="currency_symbol" id="currency_symbol"
                                            value="{{$settings['currency_symbol'] ??  ''}}" type="text"
                                            placeholder="{{__('currency_symbol')}}" class="form-control" required />--}}
                                        {{-- </div>--}}
                                    {{-- </div>--}}
                                {{-- </div>--}}

                            {{-- <input class="btn btn-theme" type="submit" value="{{ __('submit') }}"> --}}
                            @if (isset($extraFields) && count($extraFields) > 0)
                                <div class="border border-secondary rounded-lg my-4 mx-1">
                                    <div class="col-md-12 mt-3">
                                        <h4>{{ __('required_additional_information') }}</h4>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <hr class="mt-0">
                                    </div>
                                    <div class="row my-4 mx-1">
                                        @foreach ($extraFields as $key => $data)
                                            @php
                                                $fieldName = str_replace(' ', '_', $data->name);
                                                $extraData = $data->extra_school_data;
                                                $value = $extraData ? $extraData->data : '';
                                                $id = $extraData ? $extraData->id : '';
                                            @endphp

                                            {{-- Extra Details ID --}}
                                            <input type="hidden" name="extra_fields[{{ $key }}][id]" value="{{ $id }}">

                                            {{-- Form Field ID --}}
                                            <input type="hidden" name="extra_fields[{{ $key }}][form_field_id]" value="{{ $data->id }}">

                                            {{-- FormFieldType --}}
                                            <input type="hidden" name="extra_fields[{{ $key }}][input_type]" value="{{ $data->type }}">

                                            <div class='form-group col-md-12 col-lg-6 col-xl-4 col-sm-12'>
                                                @if ($data->type != 'radio' && $data->type != 'checkbox')
                                                    <label>{{ $data->name }} @if ($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                @endif

                                                @if ($data->type == 'text')
                                                    <input type="text" name="extra_fields[{{ $key }}][data]"
                                                        value="{{ $value }}" class="form-control"
                                                        placeholder="{{ $data->name }}"
                                                        {{ $data->is_required == 1 ? 'required' : '' }}>
                                                @elseif($data->type == 'number')
                                                    <input type="number" name="extra_fields[{{ $key }}][data]"
                                                        value="{{ $value }}" class="form-control" min="0"
                                                        placeholder="{{ $data->name }}"
                                                        {{ $data->is_required == 1 ? 'required' : '' }}>
                                                @elseif($data->type == 'dropdown')
                                                    <select name="extra_fields[{{ $key }}][data]" class="form-control"
                                                        {{ $data->is_required == 1 ? 'required' : '' }}>
                                                        <option value="">{{ __('select') . ' ' . $data->name }}</option>
                                                        @foreach ($data->default_values as $option)
                                                            <option value="{{ $option }}"
                                                                {{ $value == $option ? 'selected' : '' }}>
                                                                {{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif($data->type == 'radio')
                                                    <label class="d-block">{{ $data->name }} @if ($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    <div class="row ml-1">
                                                        @foreach ($data->default_values as $keyRadio => $option)
                                                            <div class="col-md-12 col-lg-12 col-xl-6 col-sm-12 form-check">
                                                                <label class="form-check-label">
                                                                    <input type="radio"
                                                                        name="extra_fields[{{ $key }}][data]"
                                                                        value="{{ $option }}"
                                                                        {{ $value == $option ? 'checked' : '' }}
                                                                        {{ $data->is_required == 1 ? 'required' : '' }}>
                                                                    {{ $option }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif($data->type == 'checkbox')
                                                    <label class="d-block">{{ $data->name }} @if ($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </label>
                                                    @php $selectedOptions = json_decode($value) ?? []; @endphp
                                                    <div class="row form-check-inline ml-1" style="width: 100%">
                                                        @foreach ($data->default_values as $chkKey => $option)
                                                            <div class="col-lg-12 col-xl-6 col-md-12 col-sm-12 form-check">
                                                                <label class="form-check-label">
                                                                    <input type="checkbox"
                                                                        name="extra_fields[{{ $key }}][data][]"
                                                                        value="{{ $option }}"
                                                                        {{ in_array($option, $selectedOptions) ? 'checked' : '' }}>
                                                                    {{ $option }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif($data->type == 'textarea')
                                                    <textarea name="extra_fields[{{ $key }}][data]" class="form-control" rows="3"
                                                        placeholder="{{ $data->name }}"
                                                        {{ $data->is_required == 1 ? 'required' : '' }}>{{ $value }}</textarea>
                                                @elseif($data->type == 'file')
                                                    <div class="input-group col-xs-12">
                                                        <input type="file" name="extra_fields[{{ $key }}][data]"
                                                            class="file-upload-default">
                                                        <input type="text" class="form-control file-upload-info" disabled
                                                            placeholder="{{ __('upload') . ' ' . $data->name }}">
                                                        <span class="input-group-append">
                                                            <button class="file-upload-browse btn btn-theme"
                                                                type="button">{{ __('upload') }}</button>
                                                        </span>
                                                    </div>
                                                    @if ($value)
                                                        <div class="mt-2">
                                                            <a href="{{ Storage::url($value) }}"
                                                                target="_blank">{{ __('view_file') }}</a>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value={{ __('submit') }}>
                            <input class="btn btn-secondary float-right" type="reset" value={{ __('reset') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        function formSuccessFunction(response) {
            setTimeout(() => {
                window.location.reload();
            }, 4000);
        }

        $(document).ready(function () {
            // Domain settings toggle functionality
            function toggleFields() {
                if ($('.default').is(':checked')) {
                    $('.defaultDomain').show().find('input').prop('disabled', false);
                    $('.customDomain').hide().find('input').prop('disabled', true);
                    $('.serverinfo').hide().find('input');
                } else if ($('.custom').is(':checked')) {
                    $('.customDomain').show().find('input').prop('disabled', false);
                    $('.serverinfo').show().find('input');
                    $('.defaultDomain').hide().find('input').prop('disabled', true);
                }
            }
            $("input[name='domain_type']").on('change', toggleFields);
            toggleFields();

            // Image preview functionality
            $('#favicon-input').on('change', function () {
                previewImage(this, '#favicon-preview');
            });

            $('#horizontal-logo-input').on('change', function () {
                previewImage(this, '#horizontal-logo-preview');
            });

            $('#vertical-logo-input').on('change', function () {
                previewImage(this, '#vertical-logo-preview');
            });

            $('#login-page-logo-input').on('change', function () {
                previewImage(this, '#login-page-logo-preview');
            });

            function previewImage(input, previewSelector) {
                if (input.files && input.files[0]) {
                    var file = input.files[0];

                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $(previewSelector).attr('src', e.target.result);

                        // Update the file name in the input field
                        $(input).closest('.form-group').find('.file-upload-info').val(file.name);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
@endsection