@extends('layouts.master')

@section('title')
    {{ __('KYC Documents') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Upload KYC Documents') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-info py-2">
                             <i class="fa fa-info-circle"></i> {{ __('Please upload all required documents to complete your KYC. Once approved, you will have full access to the system.') }}
                        </div>
                        
                        <div class="row">
                            @php
                                $requiredDocs = [
                                    'id_proof' => 'ID Proof',
                                    'address_proof' => 'Address Proof',
                                    'marksheet' => 'Latest Marksheet',
                                    'degree' => 'Degree',
                                    'experience_letter' => 'Experience Letter'
                                ];
                            @endphp

                            @foreach($requiredDocs as $type => $label)
                                @php
                                    $doc = $user->teacher_documents->where('type', $type)->first();
                                @endphp
                                <div class="col-md-6 mb-4">
                                    <div class="border p-3 rounded">
                                        <h5>{{ $label }}</h5>
                                        <hr>
                                        @if($doc)
                                            <div class="mb-2">
                                                <strong>Status: </strong>
                                                @if($doc->status == 0)
                                                    <span class="badge badge-warning">{{ __('Pending Approval') }}</span>
                                                @elseif($doc->status == 1)
                                                    <span class="badge badge-success">{{ __('Approved') }}</span>
                                                @elseif($doc->status == 2)
                                                    <span class="badge badge-danger">{{ __('Rejected') }}</span>
                                                @endif
                                            </div>
                                            <div class="mb-2">
                                                <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-sm btn-inverse-primary"><i class="fa fa-eye"></i> {{ __('View Uploaded File') }}</a>
                                            </div>
                                            @if($doc->status == 2)
                                                <div class="alert alert-danger py-1 px-2 mt-2">
                                                    <strong>Reason for Rejection:</strong> {{ $doc->rejection_reason }}
                                                </div>
                                            @endif
                                        @endif

                                        @if(!$doc || $doc->status == 2)
                                            <form class="mt-3 upload-form" action="{{ route('teacher.kyc.upload') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="type" value="{{ $type }}">
                                                <div class="form-group mb-2">
                                                    <input type="file" name="file" class="file-upload-default" accept="image/*,application/pdf" required>
                                                    <div class="input-group col-xs-12">
                                                        <input type="text" class="form-control file-upload-info" disabled placeholder="{{ __('Upload File') }}">
                                                        <span class="input-group-append">
                                                                <button class="file-upload-browse btn btn-theme" type="button">{{ __('Select') }}</button>
                                                            </span>
                                                    </div>
                                                    <small class="text-muted">{{ __('Allowed: PDF, JPG, PNG (Max 2MB)') }}</small>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-theme">{{ __('Upload') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $('.upload-form').submit(function(e) {
            e.preventDefault();
            let url = $(this).attr('action');
            let data = new FormData(this);
            ajaxRequest('POST', url, data, null, function(response) {
                showSuccessToast(response.message);
                location.reload();
            }, function(response) {
                showErrorToast(response.message);
            });
        });
    </script>
@endsection
