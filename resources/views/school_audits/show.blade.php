@extends('layouts.master')

@section('title')
    {{ __('View School Audit') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('View School Audit') }}
            </h3>
            <div>
                @if($audit->status == 1)
                    <a href="{{ route('school-audits.download-pdf', $audit->id) }}" class="btn btn-success btn-sm mr-2"><i class="fa fa-download"></i> {{ __('Download PDF') }}</a>
                    @if(Auth::user()->hasRole('Super Admin') && $audit->school && $audit->school->support_email)
                        <a href="{{ route('school-audits.email-pdf', $audit->id) }}" class="btn btn-info btn-sm mr-2"><i class="fa fa-envelope"></i> {{ __('Email PDF to School') }}</a>
                    @endif
                @elseif($audit->status == 0 && (Auth::user()->can('school-audit-edit') || $audit->auditor_id == Auth::id()))
                    <a href="{{ route('school-audits.edit', $audit->id) }}" class="btn btn-primary btn-sm mr-2"><i class="fa fa-edit"></i> {{ __('Conduct Audit') }}</a>
                @endif
                <a href="{{ route('school-audits.index') }}" class="btn btn-theme btn-sm">{{ __('Back') }}</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h5><strong>{{ __('School') }}:</strong> {{ $audit->school ? $audit->school->name : '-' }}</h5>
                            </div>
                            <div class="col-md-2">
                                <h5><strong>{{ __('Auditor') }}:</strong> {{ $audit->auditor ? $audit->auditor->first_name . ' ' . $audit->auditor->last_name : '-' }}</h5>
                            </div>
                            <div class="col-md-2">
                                <h5><strong>{{ __('Status') }}:</strong> 
                                    @if($audit->status == 1)
                                        <span class="badge badge-success">{{ __('Completed') }}</span>
                                    @else
                                        <span class="badge badge-warning">{{ __('Pending') }}</span>
                                    @endif
                                </h5>
                            </div>
                            <div class="col-md-2">
                                <h5><strong>{{ __('Audit Type') }}:</strong> {{ $audit->audit_type ?? '-' }}</h5>
                            </div>
                            <div class="col-md-3">
                                <h5><strong>{{ __('Audit Date') }}:</strong> {{ date('d M, Y', strtotime($audit->audit_date)) }}</h5>
                            </div>
                        </div>
                        @if($audit->status == 1)
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card text-white bg-primary">
                                    <div class="card-body text-center p-3">
                                        <h4 class="card-title text-white mb-2">{{ __('Overall Score') }}</h4>
                                        <h2 class="mb-0">{{ number_format($audit->percentage_score, 2) }}%</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5><strong>{{ __('General Remarks') }}:</strong></h5>
                                <p>{{ $audit->remarks ?? '-' }}</p>
                            </div>
                        </div>

                        <hr>
                        <h4 class="card-title mt-4 mb-4">{{ __('Audit Answers') }}</h4>
                        
                        @if(count($audit->answers) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="50%">{{ __('Question') }}</th>
                                            <th width="15%">{{ __('Answer') }}</th>
                                            <th width="30%">{{ __('Remarks') }}</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                            @php $index = 0; @endphp
                                            @foreach($audit->answers->groupBy('question.category.name') as $category => $categoryAnswers)
                                                @if($category)
                                                    <tr class="table-secondary">
                                                        <td colspan="4"><strong>{{ $category }}</strong></td>
                                                    </tr>
                                                @endif
                                                @foreach($categoryAnswers as $answer)
                                                    <tr>
                                                        <td>{{ ++$index }}</td>
                                                        <td>{{ $answer->question ? $answer->question->question : '-' }}</td>
                                                        <td>
                                                            @if($answer->answer == 'Pending')
                                                                <span class="badge badge-warning">{{ __('Pending') }}</span>
                                                            @elseif(in_array($answer->answer, ['Yes', 'No', 'N/A']))
                                                                @if($answer->answer == 'Yes')
                                                                    <span class="badge badge-success">{{ __('Yes') }}</span>
                                                                @elseif($answer->answer == 'No')
                                                                    <span class="badge badge-danger">{{ __('No') }}</span>
                                                                @else
                                                                    <span class="badge badge-secondary">{{ __('N/A') }}</span>
                                                                @endif
                                                            @else
                                                                <strong>{{ $answer->answer }}</strong>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{ $answer->remarks ?? '-' }}
                                                            @if($answer->image)
                                                                @php
                                                                    $images = json_decode($answer->image, true);
                                                                    if(!is_array($images)) {
                                                                        $images = [$answer->image];
                                                                    }
                                                                @endphp
                                                                <div class="mt-2 d-flex flex-wrap">
                                                                    @foreach($images as $img)
                                                                        <a href="{{ asset('storage/'.$img) }}" target="_blank" class="badge badge-info mr-1 mb-1"><i class="fa fa-eye"></i> {{ __('View') }}</a>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                {{ __('No answers found for this audit.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
