@extends('layouts.master')

@section('title')
    {{ __('Conduct School Audit') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Conduct School Audit') }}
            </h3>
            <a href="{{ route('school-audits.index') }}" class="btn btn-theme btn-sm">{{ __('Back') }}</a>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h5><strong>{{ __('School') }}:</strong> {{ $audit->school ? $audit->school->name : '-' }}</h5>
                            </div>
                            <div class="col-md-4">
                                <h5><strong>{{ __('Audit Type') }}:</strong> {{ $audit->audit_type ?? '-' }}</h5>
                            </div>
                            <div class="col-md-4">
                                <h5><strong>{{ __('Audit Date') }}:</strong> {{ date('d M, Y', strtotime($audit->audit_date)) }}</h5>
                            </div>
                        </div>

                        <hr>

                        <form class="pt-3" action="{{ route('school-audits.update', $audit->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <h4 class="card-title mt-4 mb-4">{{ __('Audit Checklist') }}</h4>
                            
                            @if(count($audit->answers) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="40%">{{ __('Question') }}</th>
                                                <th width="30%">{{ __('Answer') }} <span class="text-danger">*</span></th>
                                                <th width="15%">{{ __('Remarks') }}</th>
                                                <th width="10%">{{ __('Image (Opt)') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $index = 0; @endphp
                                            @foreach($audit->answers->groupBy('question.category.name') as $category => $categoryAnswers)
                                                @if($category)
                                                    <tr class="table-secondary">
                                                        <td colspan="5"><strong>{{ $category }}</strong></td>
                                                    </tr>
                                                @endif
                                                @foreach($categoryAnswers as $answer)
                                                    <tr>
                                                        <td>{{ ++$index }}</td>
                                                        <td>
                                                            {{ $answer->question ? $answer->question->question : '-' }}
                                                            <input type="hidden" name="answers[{{ $index }}][id]" value="{{ $answer->id }}">
                                                        </td>
                                                        <td>
                                                            @if($answer->question && $answer->question->type == 'Yes/No')
                                                                <div class="d-flex align-items-center">
                                                                    <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="Yes" required {{ $answer->answer == 'Yes' ? 'checked' : '' }}> {{ __('Yes') }}
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="No" required {{ $answer->answer == 'No' ? 'checked' : '' }}> {{ __('No') }}
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline mt-0 mb-0">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="N/A" required {{ $answer->answer == 'N/A' ? 'checked' : '' }}> {{ __('N/A') }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @elseif($answer->question && $answer->question->type == 'Rating')
                                                                <div class="d-flex align-items-center">
                                                                    <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="Excellent" required {{ $answer->answer == 'Excellent' ? 'checked' : '' }}> {{ __('Excellent') }}
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="Good" required {{ $answer->answer == 'Good' ? 'checked' : '' }}> {{ __('Good') }}
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="Average" required {{ $answer->answer == 'Average' ? 'checked' : '' }}> {{ __('Average') }}
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline mt-0 mb-0">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="Unsatisfactory" required {{ $answer->answer == 'Unsatisfactory' ? 'checked' : '' }}> {{ __('Unsatisfactory') }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @elseif($answer->question && $answer->question->type == 'Conditional')
                                                                @php
                                                                    $conditionalOptions = $answer->question->custom_options ? array_map('trim', explode(',', $answer->question->custom_options)) : ['Excellent', 'Good', 'Average', 'Unsatisfactory'];
                                                                    $targetVisibleOptions = array_merge(['Yes'], $conditionalOptions);
                                                                @endphp
                                                                <div class="d-flex align-items-center mb-2">
                                                                    <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input conditional-trigger" name="answers[{{ $index }}][answer]" value="Yes" required {{ in_array($answer->answer, $targetVisibleOptions) ? 'checked' : '' }} onclick="toggleConditionalRating(this, {{ $index }})"> {{ __('Yes') }}
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                        <label class="form-check-label">
                                                                            <input type="radio" class="form-check-input conditional-trigger" name="answers[{{ $index }}][answer]" value="No" required {{ $answer->answer == 'No' ? 'checked' : '' }} onclick="toggleConditionalRating(this, {{ $index }})"> {{ __('No') }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="align-items-center flex-wrap conditional-target-{{ $index }}" style="display: {{ in_array($answer->answer, $targetVisibleOptions) ? 'flex' : 'none' }}; margin-left: 20px; border-left: 2px solid #ccc; padding-left: 10px;">
                                                                    @foreach($conditionalOptions as $opt)
                                                                        @if($opt)
                                                                            <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                                <label class="form-check-label">
                                                                                    <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="{{ $opt }}" {{ $answer->answer == $opt ? 'checked' : '' }}> {{ $opt }}
                                                                                </label>
                                                                            </div>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            @elseif($answer->question && $answer->question->type == 'Custom')
                                                                @php
                                                                    $options = $answer->question->custom_options ? array_map('trim', explode(',', $answer->question->custom_options)) : [];
                                                                @endphp
                                                                <div class="d-flex align-items-center flex-wrap">
                                                                    @foreach($options as $opt)
                                                                        @if($opt)
                                                                            <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                                <label class="form-check-label">
                                                                                    <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="{{ $opt }}" required {{ $answer->answer == $opt ? 'checked' : '' }}> {{ $opt }}
                                                                                </label>
                                                                            </div>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            @elseif($answer->question && $answer->question->type == 'Text')
                                                                <input type="text" name="answers[{{ $index }}][answer]" class="form-control" required value="{{ $answer->answer !== 'Pending' ? $answer->answer : '' }}">
                                                            @elseif($answer->question && $answer->question->type == 'Paragraph')
                                                                <textarea name="answers[{{ $index }}][answer]" class="form-control" required rows="2">{{ $answer->answer !== 'Pending' ? $answer->answer : '' }}</textarea>
                                                            @elseif($answer->question && $answer->question->type == 'Number')
                                                                <input type="number" name="answers[{{ $index }}][answer]" class="form-control" required value="{{ $answer->answer !== 'Pending' ? $answer->answer : '' }}">
                                                            @elseif($answer->question && $answer->question->type == 'Date')
                                                                <input type="date" name="answers[{{ $index }}][answer]" class="form-control" required value="{{ $answer->answer !== 'Pending' ? $answer->answer : '' }}">
                                                            @else
                                                                {{-- Fallback --}}
                                                                <input type="text" name="answers[{{ $index }}][answer]" class="form-control" required value="{{ $answer->answer !== 'Pending' ? $answer->answer : '' }}">
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <input type="text" name="answers[{{ $index }}][remarks]" class="form-control" placeholder="{{ __('Remarks...') }}" value="{{ $answer->remarks }}">
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center flex-wrap">
                                                                <label for="image-upload-{{ $index }}" class="btn btn-icon btn-theme btn-sm mb-0 mr-2" style="cursor: pointer; padding: 0.25rem 0.5rem;" title="{{ __('Upload Documents') }}">
                                                                    <i class="fa fa-upload"></i>
                                                                </label>
                                                                <input type="file" id="image-upload-{{ $index }}" name="answers[{{ $index }}][images][]" class="d-none image-upload-input" accept="image/*,.pdf,.doc,.docx" multiple onchange="previewFileNames(this, {{ $index }}, 'file')">
                                                                
                                                                <label for="camera-upload-{{ $index }}" class="btn btn-icon btn-info btn-sm mb-0 mr-2" style="cursor: pointer; padding: 0.25rem 0.5rem;" title="{{ __('Take Photo') }}">
                                                                    <i class="fa fa-camera"></i>
                                                                </label>
                                                                <input type="file" id="camera-upload-{{ $index }}" name="answers[{{ $index }}][images][]" class="d-none image-upload-input" accept="image/*" capture="environment" multiple onchange="previewFileNames(this, {{ $index }}, 'camera')">

                                                                <span class="text-muted small" id="file-name-{{ $index }}"></span>
                                                            </div>
                                                            @if($answer->image)
                                                                @php
                                                                    $images = json_decode($answer->image, true);
                                                                    if(!is_array($images)) {
                                                                        $images = [$answer->image];
                                                                    }
                                                                @endphp
                                                                <div class="mt-1 d-flex flex-wrap">
                                                                    @foreach($images as $img)
                                                                        <a href="{{ asset('storage/'.$img) }}" target="_blank" class="badge badge-info mr-1 mb-1"><i class="fa fa-eye"></i> View</a>
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
                                <div class="alert alert-warning">
                                    {{ __('No questions assigned to this audit.') }}
                                </div>
                            @endif

                            <div class="row form-group mt-4">
                                <div class="col-sm-12 text-right">
                                    <a href="{{ route('school-audits.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <input class="btn btn-theme" type="submit" value="{{ __('Submit Audit') }}">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function toggleConditionalRating(el, index) {
            if (el.value === 'Yes') {
                $('.conditional-target-' + index).css('display', 'flex');
            } else if (el.value === 'No') {
                $('.conditional-target-' + index).css('display', 'none');
            }
        }

        function previewFileNames(input, index, source) {
            let fileInput = document.getElementById('image-upload-' + index);
            let cameraInput = document.getElementById('camera-upload-' + index);
            
            let allFiles = [];
            if (fileInput && fileInput.files) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    allFiles.push(fileInput.files[i]);
                }
            }
            if (cameraInput && cameraInput.files) {
                for (let i = 0; i < cameraInput.files.length; i++) {
                    allFiles.push(cameraInput.files[i]);
                }
            }

            if (allFiles.length > 0) {
                let fileNames = [];
                for (let i = 0; i < allFiles.length; i++) {
                    let fileName = allFiles[i].name;
                    if (fileName.length > 15) {
                        fileName = fileName.substring(0, 12) + '...';
                    }
                    fileNames.push(fileName);
                }
                let displayText = fileNames.join(', ');
                if(displayText.length > 30) {
                    displayText = allFiles.length + ' files selected';
                }
                $('#file-name-' + index).text(displayText);
            } else {
                $('#file-name-' + index).text('');
            }
        }
    </script>
@endsection
