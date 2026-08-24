@extends('layouts.master')

@section('title')
    {{ __('student_uid') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('student_uid') . ' / ' . __('Pen No') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('update') . ' ' . __('student_uid') . ' / ' . __('Pen No') }}
                        </h4>
                        <form class="pt-3 student-registration-form" id="create-form" data-success-function="formSuccessFunction" enctype="multipart/form-data" action="{{ route('students.updateUniNo') }}" method="POST" novalidate="novalidate">
                            @csrf
                           
                            <div class="row mt-5">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label for="gr_no">{{ __('Gr Number') }}  <span class="text-danger">*</span></label>
                                    <select class="grno-search form-control " id="gr_no"></select>
                                    <input type="hidden" id="student_id" class="student_id" name="student_id">
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('student_uid') }}</label>
                                    {!! Form::text('student_uni', null, [
                                        'id' => 'student_uni',
                                        'placeholder' => __('student_uid'),
                                        'class' => 'form-control'
                                    ]) !!}
                                </div>     
                                 <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Student Pen No.') }}</label>
                                    {!! Form::text('student_pen_no', null, [ 'id' => 'student_pen_no','placeholder' => __('Student Pen No.'),'class' => 'form-control ']) !!}
                                </div>           
                            </div>

                    
                            {{-- Guardian Details --}}
                                                           
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
        function formSuccessFunction() {
            setTimeout(() => {
                window.location.reload()
            }, 3000);
        }
    </script>
@endsection
