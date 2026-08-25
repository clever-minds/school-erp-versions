@extends('layouts.master')

@section('title')
    {{ __('academy_master_setup') }}
@endsection

@section('content')
<link href="{{ asset('assets/css/custom/academy-setup.css') }}" rel="stylesheet">

<div class="content-wrapper">
    <div class="academy-master-header {{ $isMasterEnabled ? 'published' : 'draft' }}">
        <div class="header-left">
            @if ($isMasterEnabled)
                <span class="badge-mode published-badge text-uppercase">{{ __('published_to_school') }}</span>
                <h2>{{ __('academy_master_setup') }}</h2>
                <p>{{ __('the_system_is_currently_live_schools_can_now_use_this_data_for_their_academy_configuration') }}</p>
            @else
                <span class="badge-mode draft-badge text-uppercase">{{ __('draft_mode_internal') }}</span>
                <h2>{{ __('academy_master_setup') }}</h2>
                <p>{{ __('the_system_is_in_draft_you_can_add_and_manage_all_categories_below_all_categories_must_have_data_before_publishing') }}</p>
            @endif
        </div>
        
        <div class="header-right">
            <div class="master-status-card">
                <span class="ms-title text-uppercase">{{ __('master_status') }}</span>
                <label class="switch">
                    <input type="checkbox" id="masterStatusToggle" {{ $isMasterEnabled ? 'checked' : '' }}>
                    <span class="slider round">
                        
                    </span>
                </label>
                <span class="ms-status text-uppercase" id="masterStatusText">{{ $isMasterEnabled ? __('enabled') : __('disabled') }}</span>
            </div>

            <div class="stats-card">
                <div class="stat-items">
                    <div class="stat-item">
                        <span class="dot {{ $stats['boards'] > 0 ? 'active' : '' }}" id="header-dot-boards"></span>
                        <span class="stat-name text-uppercase">{{ __('boards') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="dot {{ $stats['mediums'] > 0 ? 'active' : '' }}" id="header-dot-mediums"></span>
                        <span class="stat-name text-uppercase">{{ __('mediums') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="dot {{ $stats['streams'] > 0 ? 'active' : '' }}" id="header-dot-streams"></span>
                        <span class="stat-name text-uppercase">{{ __('streams') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="dot {{ $stats['classes'] > 0 ? 'active' : '' }}" id="header-dot-classes"></span>
                        <span class="stat-name text-uppercase">{{ __('classes') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="dot {{ $stats['sections'] > 0 ? 'active' : '' }}" id="header-dot-sections"></span>
                        <span class="stat-name text-uppercase">{{ __('sections') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="dot {{ $stats['subjects'] > 0 ? 'active' : '' }}" id="header-dot-subjects"></span>
                        <span class="stat-name text-uppercase">{{ __('subjects') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!$isMasterEnabled)
    <div class="alert internal-alert" role="alert">
        <i class="fa fa-info-circle"></i> {{ __('internal_management_mode_schools_cannot_see_these_updates_yet') }}
    </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card academy-card">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs academy-tabs" id="academyTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="boards-tab" data-toggle="tab" href="#boards" role="tab" data-type="boards">
                                <i class="fa fa-university"></i> {{ __('boards') }} <span class="badge-count" id="tab-count-boards">{{ $stats['boards'] }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="mediums-tab" data-toggle="tab" href="#mediums" role="tab" data-type="mediums">
                                <i class="fa fa-globe"></i> {{ __('mediums') }} <span class="badge-count" id="tab-count-mediums">{{ $stats['mediums'] }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="streams-tab" data-toggle="tab" href="#streams" role="tab" data-type="streams">
                                <i class="fa fa-list-ol"></i> {{ __('streams') }} <span class="badge-count" id="tab-count-streams">{{ $stats['streams'] }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="classes-tab" data-toggle="tab" href="#classes" role="tab" data-type="classes">
                                <i class="fa fa-hashtag"></i> {{ __('classes') }} <span class="badge-count" id="tab-count-classes">{{ $stats['classes'] }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="sections-tab" data-toggle="tab" href="#sections" role="tab" data-type="sections">
                                <i class="fa fa-cubes"></i> {{ __('sections') }} <span class="badge-count" id="tab-count-sections">{{ $stats['sections'] }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="subjects-tab" data-toggle="tab" href="#subjects" role="tab" data-type="subjects">
                                <i class="fa fa-book"></i> {{ __('subjects') }} <span class="badge-count" id="tab-count-subjects">{{ $stats['subjects'] }}</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content table-container">
                        <!-- We will use a dynamic table approach -->
                        <div class="tab-pane fade show active" id="dynamic-tab-content">
                            <div class="text-right">
                                <button class="btn btn-primary theme-color add-new-btn text-right" id="addNewBtn">
                                    <i class="fa fa-plus"></i> <span id="addBtnText">{{ __('add_new_board') }}</span>
                                </button>
                            </div>
                            
                            <table id="table_list" data-toggle="table" 
                                data-url="{{ route('academy-setup.show', ['type' => 'boards']) }}"
                                data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50]" data-search="true"
                                data-show-columns="false" data-show-refresh="true"
                                data-trim-on-search="false" data-mobile-responsive="true"
                                data-escape="true" class="table academy-table">
                                <thead>
                                    <tr>
                                        <!-- Dynamic Columns will be generated by JS -->
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="academyModal" tabindex="-1" role="dialog" aria-labelledby="academyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="academyModalLabel">{{ __('add_new') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="academyForm" class="pt-3">
                <div class="modal-body">
                    <input type="hidden" name="type" id="formType" value="boards">
                    <input type="hidden" name="id" id="editId" value="">
                    
                    <div class="form-group">
                        <label for="name">{{ __('name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="Name" required>
                    </div>

                    <div class="form-group code-group">
                        <label for="code">{{ __('code') }} <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" id="code" placeholder="Code">
                    </div>

                    <div class="subject-extra-fields d-none">
                        <div class="form-group">
                            <label for="bg_color">{{ __('background_colour') }} <span class="text-danger">*</span></label>
                            <input type="text" name="bg_color" class="form-control color-picker" id="bg_color">
                        </div>
                        <div class="form-group">
                            <label for="image">{{ __('image') }} <span class="text-danger">*</span></label>
                            <input type="file" name="image" class="file-upload-default" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/svg"/>
                            <div class="input-group col-xs-12">
                                <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}"/>
                                <span class="input-group-append">
                                    <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="subject_type">{{ __('type') }}</label>
                            <select name="subject_type" id="subject_type" class="form-control">
                                <option value="Theory">{{ __('theory') }}</option>
                                <option value="Practical">{{ __('practical') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __('status') }}</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="status" name="status" checked value="1">
                            <label class="custom-control-label" for="status">{{ __('active') }}</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                    <button type="submit" class="btn btn-theme">{{ __('save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    var masterStatusUrl = "{{ route('academy-setup.update-master-status') }}";
</script>
<script src="{{ asset('assets/js/custom/academy-setup.js') }}"></script>
@endsection
