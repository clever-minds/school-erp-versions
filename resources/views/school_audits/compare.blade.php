@extends('layouts.master')

@section('title')
    {{ __('Compare School Audits') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Compare School Audits') }}
            </h3>
            <a href="{{ route('school-audits.index') }}" class="btn btn-theme btn-sm">{{ __('Back') }}</a>
        </div>

        <div class="row mb-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('school-audits.compare') }}" method="GET">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label>{{ __('Select School') }}</label>
                                    <select name="school_id" id="compare_school_id" class="form-control" required onchange="this.form.submit()">
                                        <option value="">{{ __('Select School') }}</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('First Audit (Older)') }}</label>
                                    <select name="audit1_id" class="form-control" required>
                                        <option value="">{{ __('Select Audit') }}</option>
                                        @if(isset($audits))
                                            @foreach($audits as $a)
                                                <option value="{{ $a->id }}" {{ request('audit1_id') == $a->id ? 'selected' : '' }}>{{ $a->name }} ({{ date('d M, Y', strtotime($a->audit_date)) }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('Second Audit (Newer)') }}</label>
                                    <select name="audit2_id" class="form-control" required>
                                        <option value="">{{ __('Select Audit') }}</option>
                                        @if(isset($audits))
                                            @foreach($audits as $a)
                                                <option value="{{ $a->id }}" {{ request('audit2_id') == $a->id ? 'selected' : '' }}>{{ $a->name }} ({{ date('d M, Y', strtotime($a->audit_date)) }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">{{ __('Compare') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($audit1) && isset($audit2))
            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title text-center mb-4">{{ __('Comparison Report') }}</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 text-center border-right">
                                    <h5 class="text-muted">{{ __('First Audit') }}</h5>
                                    <h3>{{ $audit1->name }}</h3>
                                    <p>{{ date('d M, Y', strtotime($audit1->audit_date)) }}</p>
                                    <div class="card text-white bg-secondary d-inline-block mt-2">
                                        <div class="card-body p-3 text-center">
                                            <h4>{{ number_format($audit1->percentage_score, 2) }}%</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-center">
                                    <h5 class="text-muted">{{ __('Second Audit') }}</h5>
                                    <h3>{{ $audit2->name }}</h3>
                                    <p>{{ date('d M, Y', strtotime($audit2->audit_date)) }}</p>
                                    <div class="card text-white {{ $audit2->percentage_score > $audit1->percentage_score ? 'bg-success' : ($audit2->percentage_score < $audit1->percentage_score ? 'bg-danger' : 'bg-info') }} d-inline-block mt-2">
                                        <div class="card-body p-3 text-center">
                                            <h4>{{ number_format($audit2->percentage_score, 2) }}%</h4>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        @if($audit2->percentage_score > $audit1->percentage_score)
                                            <span class="badge badge-success"><i class="fa fa-arrow-up"></i> {{ __('Improved') }}</span>
                                        @elseif($audit2->percentage_score < $audit1->percentage_score)
                                            <span class="badge badge-danger"><i class="fa fa-arrow-down"></i> {{ __('Declined') }}</span>
                                        @else
                                            <span class="badge badge-info"><i class="fa fa-minus"></i> {{ __('No Change') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5 class="mb-3">{{ __('Detailed Comparison (Answer Changes)') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="40%">{{ __('Question') }}</th>
                                            <th width="30%">{{ __('First Audit Answer') }}</th>
                                            <th width="30%">{{ __('Second Audit Answer') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Pre-map answers for audit 2 to easily find them by question id
                                            $audit2AnswersMap = [];
                                            foreach($audit2->answers as $a2_ans) {
                                                $audit2AnswersMap[$a2_ans->audit_question_id] = $a2_ans->answer;
                                            }
                                        @endphp

                                        @foreach($audit1->answers as $ans1)
                                            @php
                                                $q_id = $ans1->audit_question_id;
                                                $ans2_val = $audit2AnswersMap[$q_id] ?? __('Not Answered / Missing');
                                                
                                                // Highlight if different
                                                $isDifferent = $ans1->answer !== $ans2_val;
                                            @endphp
                                            <tr class="{{ $isDifferent ? 'table-warning' : '' }}">
                                                <td>{{ $ans1->question ? $ans1->question->question : '-' }}</td>
                                                <td>{{ $ans1->answer }}</td>
                                                <td>
                                                    {{ $ans2_val }}
                                                    @if($isDifferent)
                                                        <span class="badge badge-warning float-right">{{ __('Changed') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
