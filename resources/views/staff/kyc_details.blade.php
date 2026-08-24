@extends('layouts.master')

@section('title')
    {{ __('Teacher KYC Details') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Teacher KYC Details') }}: {{ $user->first_name }} {{ $user->last_name }}
            </h3>
            <a href="{{ route('staff.kyc.index') }}" class="btn btn-sm btn-theme">{{ __('Back') }}</a>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
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
                                                    <span class="badge badge-warning">{{ __('Pending') }}</span>
                                                @elseif($doc->status == 1)
                                                    <span class="badge badge-success">{{ __('Approved') }}</span>
                                                @elseif($doc->status == 2)
                                                    <span class="badge badge-danger">{{ __('Rejected') }}</span>
                                                @endif
                                            </div>
                                            <div class="mb-2">
                                                <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-sm btn-inverse-primary"><i class="fa fa-eye"></i> {{ __('View Document') }}</a>
                                            </div>
                                            @if($doc->status == 2)
                                                <div class="alert alert-danger py-1 px-2 mt-2">
                                                    <strong>Reason for Rejection:</strong> {{ $doc->rejection_reason }}
                                                </div>
                                            @endif

                                            @if($doc->status == 0 || $doc->status == 2)
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-success approve-btn" data-id="{{ $doc->id }}">{{ __('Approve') }}</button>
                                                    <button class="btn btn-sm btn-danger reject-btn" data-id="{{ $doc->id }}">{{ __('Reject') }}</button>
                                                </div>
                                            @elseif($doc->status == 1)
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-danger reject-btn" data-id="{{ $doc->id }}">{{ __('Reject') }}</button>
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-muted"><i class="fa fa-info-circle"></i> {{ __('Not uploaded yet') }}</div>
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

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Reject Document') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="reject-form">
                    <div class="modal-body">
                        <input type="hidden" name="document_id" id="reject_document_id">
                        <input type="hidden" name="status" value="2">
                        <div class="form-group">
                            <label>{{ __('Reason for Rejection') }} <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" required placeholder="Enter reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Reject') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).on('click', '.approve-btn', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to approve this document!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = "{{ route('staff.kyc.update-status') }}";
                    let data = {
                        document_id: id,
                        status: 1
                    };
                    ajaxRequest('POST', url, data, null, function(response) {
                        showSuccessToast(response.message);
                        location.reload();
                    }, function(response) {
                        showErrorToast(response.message);
                    });
                }
            })
        });

        $(document).on('click', '.reject-btn', function() {
            $('#reject_document_id').val($(this).data('id'));
            $('#rejectModal').modal('show');
        });

        $('#reject-form').submit(function(e) {
            e.preventDefault();
            let url = "{{ route('staff.kyc.update-status') }}";
            let data = new FormData(this);
            ajaxRequest('POST', url, data, null, function(response) {
                $('#rejectModal').modal('hide');
                showSuccessToast(response.message);
                location.reload();
            }, function(response) {
                showErrorToast(response.message);
            });
        });
    </script>
@endsection
