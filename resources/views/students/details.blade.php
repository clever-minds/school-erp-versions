@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('students') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('students') }}
                        </h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label class="filter-menu">{{ __('Class Section') }} <span class="text-danger">*</span></label>
                                <select name="filter_class_section_id" id="filter_class_section_id" class="form-control">
                                    <option value="">{{ __('select_class_section') }}</option>
                                    @foreach ($class_sections as $class_section)
                                        <option value={{ $class_section->id }}>{{$class_section->full_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-4">
                                <label class="filter-menu">{{ __('Session Year') }} <span class="text-danger">*</span></label>
                                <select name="filter_session_year_id" id="filter_session_year_id" class="form-control">
                                    @foreach ($sessionYears as $sessionYear)
                                        <option value={{ $sessionYear->id }} {{$sessionYear->default==1?"selected":""}}>{{$sessionYear->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if(in_array(Auth::user()->school_id, [10, 58]))
                                <div class="form-group col-sm-12 col-md-4">
                                    <label class="filter-menu">{{ __('Campus') }}</label>
                                    {!! Form::select('filter_campus', ['JIAM' => 'JIAM', 'Tandalja' => 'Tandalja'], null, ['class' => 'form-control', 'id' => 'filter_campus', 'placeholder' => __('Select Campus')]) !!}
                                </div>
                            @endif
                                <div class="form-group col-12">
                                    <button id="update-status" class="btn btn-secondary" disabled><span class="update-status-btn-name">{{ __('Inactive') }}</span></button>
                                </div>
                        </div>

                            <div class="col-12 mt-4 text-right">
                                <b><a href="#" class="table-list-type active mr-2" data-id="0">{{__('active')}}</a></b> | <a href="#" class="ml-2 table-list-type" data-id="1">{{__("Inactive")}}</a>
                            </div>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('students.show',[1]) }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[10, 20, 50, 100, 200]" data-search="true"
                                       data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                                       data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                       data-sort-order="desc" data-maintain-selected="true" data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']" data-show-export="true"
                                       data-export-options='{ "fileName": "students-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}' data-query-params="studentDetailsQueryParams"
                                       data-check-on-init="true" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                        <th scope="col" data-field="rte_status" data-sortable="true" data-visible="false">{{ __('RTE/NON Rte') }}</th>
                                        <th scope="col" data-field="no" data-formatter="indexFormatter">{{ __('no.') }}</th>
                                        <th scope="col" data-field="user.id" data-visible="false">{{ __('User Id') }}</th>
                                        <th scope="col" data-field="user.full_name">{{ __('name') }}</th>
                                        <th scope="col" data-field="cast" data-sortable="true" data-visible="false">{{ __('Cast') }}</th>
                                        <th scope="col" data-field="user.email" data-visible="true">{{ __('GR NO.') }}</th>
                                        <th scope="col" data-field="user.dob" >{{ __('dob') }}</th>
                                        <th scope="col" data-field="class_section.full_name" data-formatter="classSectionFormatter">{{ __('class_section') }}</th>
                                        @if(in_array(Auth::user()->school_id, [10, 58]))
                                            <th scope="col" data-field="campus">{{ __('Campus') }}</th>
                                        @endif
                                        <th scope="col" data-field="roll_number">{{ __('roll_no') }}</th>
                                        <th scope="col" data-field="user.gender">{{ __('gender') }}</th>
                                        <th scope="col" data-field="admission_date" >{{ __('admission_date') }}</th>
                                        <th scope="col" data-field="guardian.email">{{ __('guardian') . ' ' . __('email') }}</th>
                                        <th scope="col" data-field="guardian.full_name">{{ __('guardian') . ' ' . __('name') }}</th>
                                        <th scope="col" data-field="guardian.mobile">{{ __('guardian') . ' ' . __('mobile') }}</th>
                                        <th scope="col" data-field="guardian.gender">{{ __('guardian') . ' ' . __('gender') }}</th>
                                        <th scope="col" data-field="uid_no">{{ __('UID NO.') }}</th>
                                        <th scope="col" data-field="pen_no">{{ __('Pen NO.') }}</th>

                                        {{-- Admission form fields --}}
                                        @foreach ($extraFields as $field)
                                            <th scope="col" data-visible="false" data-escape="false" data-field="{{ $field->name }}">{{ $field->name }}</th>
                                        @endforeach
                                        {{-- End admission form fields --}}

                                        @canany(['student-edit','student-delete'])
                                            <th data-events="studentEvents" class="align-button text-center" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                        @endcanany
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('student-edit')
        <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('students') }}</h4><br>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fa fa-close"></i></span>
                        </button>
                    </div>
                    <form id="edit-form" class="edit-student-registration-form" novalidate="novalidate" action="{{ url('students') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('GR Number') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('admission_no', null, ['placeholder' => __('GR Number'), 'class' => 'form-control', 'id' => 'edit_admission_no' ,'readonly'=>false]) !!}
                                </div>
                                 <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Pen Number') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('pen_no', null, ['placeholder' => __('Pen Number'), 'class' => 'form-control', 'id' => 'edit_pen_no' ,'readonly'=>false]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Session Year') }} <span class="text-danger">*</span></label>
                                    <select required name="session_year_id" class="form-control" id="session_year_id">
                                        @foreach ($sessionYears as $sessionYear)
                                            <option value="{{ $sessionYear->id }}">{{$sessionYear->name}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Class Section') }} <span class="text-danger">*</span></label>
                                    <select required name="class_section_id" class="form-control" id="edit_student_class_section_id">
                                        <option value="">{{ __('select_class_section') }}</option>
                                        @foreach ($class_sections as $class_section)
                                            <option value={{ $class_section->id }}>{{$class_section->full_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('RTE Status') }} <span class="text-danger">*</span></label>
                                    <select name="rte_status" id="edit_rte_status" class="form-control">
                                        <option value="">{{ __('Select RTE Status') }}</option>
                                        <option value="RTE">{{ __('RTE') }}</option>
                                        <option value="NON_RTE">{{ __('NON_RTE') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('CAST') }} <span class="text-danger">*</span></label>
                                    <select name="cast" id="edit_cast" class="form-control">
                                        <option value="">{{ __('Select CAST') }}</option>
                                        <option value="GENERAL">{{ __('GENERAL') }}</option>
                                        <option value="OBC">{{ __('OBC') }}</option>
                                        <option value="SC">{{ __('SC') }}</option>
                                        <option value="ST">{{ __('ST') }}</option>
                                        <option value="EWS">{{ __('EWS') }}</option>
                                    </select>
                                </div>
                                  <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                        <label>{{ __('Nationality') }} <span class="text-danger">*</span></label>

                                        {!! Form::select(
                                            'nationality',
                                            [
                                                '' => __('Select Nationality'),
                                                'Indian' => 'Indian',
                                                'American' => 'American',
                                                'British' => 'British',
                                                'Canadian' => 'Canadian',
                                                'Australian' => 'Australian',
                                                'Other' => 'Other',
                                            ],
                                            null,
                                            [
                                                "id"  =>      "edit_nationality",
                                                'class' => 'form-control',
                                                'required' => true
                                            ]
                                        ) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                        <label>{{ __('Last School Attended') }} <span class="text-danger">*</span></label>

                                        {!! Form::text(
                                            'last_school',
                                            null,
                                            [
                                                "id"  =>      "edit_last_school",
                                                'placeholder' => __('Enter Last School Attended'),
                                                'class' => 'form-control',
                                                'required' => true,
                                                'autocomplete' => 'off'
                                            ]
                                        ) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                        <label>{{ __('Last Cleared Class') }} <span class="text-danger">*</span></label>

                                        {!! Form::text(
                                            'last_cleared_class',
                                            null,
                                            [
                                                "id"  =>      "edit_last_cleared_class",
                                                'placeholder' => __('Enter Last Cleared Class'),
                                                'class' => 'form-control',
                                                'required' => true,
                                                'autocomplete' => 'off'
                                            ]
                                        ) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                        <label>{{ __('Education Board of Last Cleared Class') }} <span class="text-danger">*</span></label>

                                        {!! Form::text(
                                            'education_board',
                                            null,
                                            [
                                                 "id"  =>      "edit_education_board",
                                                'placeholder' => __('Enter Education Board'),
                                                'class' => 'form-control',
                                                'required' => true,
                                                'autocomplete' => 'off'
                                            ]
                                        ) !!}
                                    </div>
                                    <div class="form-group col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                        <label>{{ __('Remarks') }} <span class="text-danger">*</span></label>

                                        {!! Form::textarea(
                                            'remarks',
                                            null,
                                            [
                                                 "id"  =>      "edit_remarks",
                                                'placeholder' => __('Enter Remarks'),
                                                'class' => 'form-control',
                                                'rows' => 3,
                                                'required' => true
                                            ]
                                        ) !!}
                                    </div>
                                    @if(in_array(Auth::user()->school_id, [10, 58]))
                                        <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                            <label>{{ __('Campus') }} <span class="text-danger">*</span></label>
                                            {!! Form::select('campus', ['JIAM' => 'JIAM', 'Tandalja' => 'Tandalja'], null, ['class' => 'form-control', 'id' => 'edit_campus', 'placeholder' => __('Select Campus'), 'required' => true]) !!}
                                        </div>
                                    @endif
                            </div>
                            
                            <hr>
                            <div class="row mt-5">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('first_name', null, ['placeholder' => __('first_name'), 'class' => 'form-control', 'id' => 'edit_first_name']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('middle_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('middle_name', null, ['placeholder' => __('middle_name'), 'class' => 'form-control', 'id' => 'edit_middle_name']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('last_name', null, ['placeholder' => __('last_name'), 'class' => 'form-control', 'id' => 'edit_last_name']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('dob', null, ['placeholder' => __('dob'), 'class' => 'datepicker-popup-no-future form-control', 'id' => 'edit_dob']) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                  <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Birth Place') }} <span class="text-danger">*</span></label>

                                    {!! Form::text(
                                        'birth_place',
                                        null,
                                        [
                                            'id' => 'edit_birth_place', 
                                            'placeholder' => __('Enter Birth Place'),
                                            'class' => 'form-control',
                                            'required' => true,
                                            'autocomplete' => 'off'
                                        ]
                                    ) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                        <label>{{ __('blood_group') }} <span class="text-danger">*</span></label>

                                        {!! Form::select(
                                            'blood_group',
                                            [
                                                '' => __('Select Blood Group'),
                                                'A+' => 'A+',
                                                'A-' => 'A-',
                                                'B+' => 'B+',
                                                'B-' => 'B-',
                                                'AB+' => 'AB+',
                                                'AB-' => 'AB-',
                                                'O+' => 'O+',
                                                'O-' => 'O-',
                                            ],
                                            null,
                                            ['id' => 'edit_blood_group','class' => 'form-control']
                                        ) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'male', false ,['id' => 'male']) !!}
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'female', false , ['id' => 'female']) !!}
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('image') }} </label>
                                    <input type="file" name="image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" required="required" id="edit_image"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    <div style="width: 100px;">
                                        <img src="" id="edit-student-image-tag" class="img-fluid w-100" alt=""/>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('mobile') }}</label>
                                    {!! Form::number('mobile', null, ['placeholder' => __('mobile'), 'min' => 1 , 'class' => 'form-control remove-number-increment', 'id' => 'edit_mobile']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('current_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('current_address', null, ['required', 'placeholder' => __('current_address'), 'class' => 'form-control', 'rows' => 3,'id'=>'edit-current-address']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('permanent_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('permanent_address', null, ['required', 'placeholder' => __('permanent_address'), 'class' => 'form-control', 'rows' => 3,'id'=>'edit-permanent-address']) !!}
                                </div>
                            </div>

                            @if(!empty($extraFields))
                                <div class="row other-details">

                                    {{-- Loop the FormData --}}
                                    @foreach ($extraFields as $key => $data)
                                        @if($data->user_type == 1)
                                            @php $fieldName = str_replace(' ', '_', $data->name) @endphp
                                            {{-- Edit Extra Details ID --}}
                                            {{ Form::hidden('extra_fields['.$key.'][id]', '', ['id' => $fieldName.'_id']) }}

                                            {{-- Form Field ID --}}
                                            {{ Form::hidden('extra_fields['.$key.'][form_field_id]', $data->id) }}

                                            {{-- FormFieldType --}}
                                            {{ Form::hidden('extra_fields['.$key.'][input_type]', $data->type) }}

                                            <div class='form-group col-md-12 col-lg-6 col-xl-4 col-sm-12'>

                                                {{-- Add lable to all the elements excluding checkbox --}}
                                                @if($data->type != 'radio' && $data->type != 'checkbox')
                                                    <label>{{$data->name}} @if($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif</label>
                                                @endif

                                                {{-- Text Field --}}
                                                @if($data->type == 'text')
                                                    {{ Form::text('extra_fields['.$key.'][data]', '', ['class' => 'form-control text-fields', 'id' => $fieldName, 'placeholder' => $data->name, ($data->is_required == 1 ? 'required' : '')]) }}
                                                    {{-- Number Field --}}
                                                @elseif($data->type == 'number')
                                                    {{ Form::number('extra_fields['.$key.'][data]', '', ['min' => 0, 'class' => 'form-control number-fields', 'id' => $fieldName, 'placeholder' => $data->name, ($data->is_required == 1 ? 'required' : '')]) }}

                                                    {{-- Dropdown Field --}}
                                                @elseif($data->type == 'dropdown')
                                                    {{ Form::select(
                                                        'extra_fields['.$key.'][data]',$data->default_values,
                                                        null,
                                                        [
                                                            'id' => $fieldName,
                                                            'class' => 'form-control select-fields',
                                                            ($data->is_required == 1 ? 'required' : ''),
                                                            'placeholder' => 'Select '.$data->name
                                                        ]
                                                    )}}

                                                    {{-- Radio Field --}}
                                                @elseif($data->type == 'radio')
                                                    <label class="d-block">{{$data->name}} @if($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif</label>
                                                    <div class="row form-check-inline ml-1">
                                                        @foreach ($data->default_values as $keyRadio => $value)
                                                            <div class="col-md-12 col-lg-12 col-xl-6 col-sm-12 form-check">
                                                                <label class="form-check-label">
                                                                    {{ Form::radio('extra_fields['.$key.'][data]', $value, null, ['id' => $fieldName.'_'.$keyRadio, 'class' => 'radio-fields',($data->is_required == 1 ? 'required' : '')]) }}
                                                                    {{$value}}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    {{-- Checkbox Field --}}
                                                @elseif($data->type == 'checkbox')
                                                    <label class="d-block">{{$data->name}} @if($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif</label>
                                                    <div class="row form-check-inline ml-1">
                                                        @foreach ($data->default_values as $chkKey => $value)
                                                            <div class="col-lg-12 col-xl-6 col-md-12 col-sm-12 form-check">
                                                                <label class="form-check-label">
                                                                    {{ Form::checkbox('extra_fields['.$key.'][data][]', $value, null, ['id' => $fieldName.'_'.$chkKey, 'class' => 'form-check-input chkclass checkbox-fields',($data->is_required == 1 ? 'required' : '')]) }} {{ $value }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    {{-- Textarea Field --}}
                                                @elseif($data->type == 'textarea')
                                                    {{ Form::textarea('extra_fields['.$key.'][data]', '', ['placeholder' => $data->name, 'id' => $fieldName, 'class' => 'form-control textarea-fields', ($data->is_required ? 'required' : '') , 'rows' => 3]) }}

                                                    {{-- File Upload Field --}}
                                                @elseif($data->type == 'file')
                                                    <div class="input-group col-xs-12">
                                                        {{ Form::file('extra_fields['.$key.'][data]', ['class' => 'file-upload-default', 'id' => $fieldName]) }}
                                                        {{ Form::text('', '', ['class' => 'form-control file-upload-info', 'disabled' => '', 'placeholder' => __('image')]) }}
                                                        <span class="input-group-append">
                                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                                        </span>
                                                    </div>
                                                    <div id="file_div_{{$fieldName}}" class="mt-2 d-none file-div">
                                                        <a href="" id="file_link_{{$fieldName}}" target="_blank">{{$data->name}}</a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="d-flex">
                                        <div class="form-check w-fit-content">
                                            <label class="form-check-label ml-4">
                                                <input type="checkbox" class="form-check-input" name="reset_password" value="1">{{ __('reset_password') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            

                            <hr>
                            {{-- Guardian Details --}}
                            <div class="row mt-5">
                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('Father') . ' ' . __('mobile') }} <span class="text-danger">*</span></label>
                                    <select class="edit-guardian-search form-control" name="guardian_id"></select>
                                    <input type="hidden" id="edit_guardian_mobile" name="guardian_mobile">
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Father') . ' ' . __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('guardian_first_name', null, ['placeholder' => __('guardian') . ' ' . __('first_name'), 'class' => 'form-control', 'id' => 'edit_guardian_first_name']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Father') . ' ' . __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('guardian_last_name', null, ['placeholder' => __('guardian') . ' ' . __('last_name'), 'class' => 'form-control', 'id' => 'edit_guardian_last_name']) !!}
                                </div>
                                 <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Mother') . ' ' . __('Name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('student_mother_name', null, ['placeholder' => __('Mother Name') , 'class' => 'form-control', 'id' => 'edit_student_mother_name']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Father') . ' ' . __('email') }} </label>
                                    {!! Form::email('guardian_email', null, [
                                        'placeholder' => __('guardian') . ' ' . __('email'),
                                        'class' => 'form-control',
                                        'id' => 'edit_guardian_email'
                                    ]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12" style="display:none;">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('guardian_gender', 'male', false , ['id' =>"edit-guardian-male"]) !!}
                                                {{-- <input type="radio" name="guardian_gender" value="male" id="edit_guardian_male"  disabled> --}}
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('guardian_gender', 'female', false , ['id' =>"edit-guardian-female"]) !!}
                                                {{-- <input type="radio" name="guardian_gender" value="female" id="edit_guardian_female" disabled> --}}
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('image') }} </label>
                                    <input type="file" name="guardian_image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" required="required" id="edit_image"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    <div style="width: 100px;">
                                        <img src="" id="edit-guardian-image-tag" class="img-fluid w-100" alt=""/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="d-flex">
                                        <div class="form-check w-fit-content">
                                            <label class="form-check-label ml-4">
                                                <input type="checkbox" class="form-check-input" name="parent_reset_password" value="1">{{ __('reset_password') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection
@section('script')
    <script>
        let userIds;
        $('.table-list-type').on('click', function (e) {
            let value = $(this).data('id');
            let ActiveLang = window.trans['Active'];
            let DeactiveLang = window.trans['Inactive'];
            if (value === "" || value === 0 || value == null) {
                $("#update-status").data("id")
                $('.update-status-btn-name').html(DeactiveLang);
            } else {
                $('.update-status-btn-name').html(ActiveLang);
            }
        })


        function updateUserStatus(tableId, buttonClass) {
            let selectedRows = $(tableId).bootstrapTable('getSelections');
            let selectedRowsValues = selectedRows.map(function (row) {
                return row.user_id;
            });
            userIds = JSON.stringify(selectedRowsValues);

            if (buttonClass != null) {
                if (selectedRowsValues.length) {
                    $(buttonClass).prop('disabled', false);
                } else {
                    $(buttonClass).prop('disabled', true);
                }
            }
        }

        $('#table_list').bootstrapTable({
            onCheck: function (row) {
                updateUserStatus("#table_list", '#update-status');
            },
            onUncheck: function (row) {
                updateUserStatus("#table_list", '#update-status');
            },
            onCheckAll: function (rows) {
                updateUserStatus("#table_list", '#update-status');
            },
            onUncheckAll: function (rows) {
                updateUserStatus("#table_list", '#update-status');
            }
        });
        $("#update-status").on('click', function (e) {
            Swal.fire({
                title: window.trans["Are you sure"],
                text: window.trans["Change Status For Selected Users"],
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: window.trans["Yes, Change it"],
                cancelButtonText: window.trans["Cancel"]
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = baseUrl + '/students/change-status-bulk';
                    let data = new FormData();
                    data.append("ids", userIds)

                    function successCallback(response) {
                        $('#table_list').bootstrapTable('refresh');
                        $('#update-status').prop('disabled', true);
                        userIds = null;
                        showSuccessToast(response.message);
                    }

                    function errorCallback(response) {
                        showErrorToast(response.message);
                    }

                    ajaxRequest('POST', url, data, null, successCallback, errorCallback);
                }
            })
        })
        function indexFormatter(value, row, index) {
            const table = $('#table_list');

            const options = table.bootstrapTable('getOptions');
            const pageSize = options.pageSize;
            const pageNumber = options.pageNumber;

            return (pageSize * (pageNumber - 1)) + (index + 1);
        }
        function classSectionFormatter(value, row, index) {
            if (!value) return '';

            // Force Excel to treat the value as TEXT
             return value.replace(" ", "\u200B");
        }

    </script>
@endsection
