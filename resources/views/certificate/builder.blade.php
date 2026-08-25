@php
    $settings        = app(\App\Services\CachingService::class)->getSchoolSettings() ?? [];
    $horizontal_logo = $settings['horizontal_logo'] ?? url('assets/dummy_logo.jpg');
    $vertical_logo   = $settings['vertical_logo']   ?? url('assets/dummy_logo.jpg');
    $signature       = $settings['signature']        ?? url('assets/dummy_logo.jpg');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($certificateTemplate) ? __('edit_certificate_template') : __('create_certificate_template') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --theme-color: <?=$systemSettings['theme_color'] ?? "#22577A" ?> !important;
            --primary: <?=$systemSettings['theme_color'] ?? "#22577A" ?> !important;
        }
    </style>

    <!-- Builder CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/certificate-builder.css') }}">
</head>
<body>

<form id="save-form" action="{{ isset($certificateTemplate) ? route('certificate-template.update', $certificateTemplate->id) : route('certificate-template.store') }}" method="POST" enctype="multipart/form-data" style="display:none;">
    @csrf
    @if(isset($certificateTemplate)) @method('PUT') @endif
    <input type="hidden" name="name"         id="hidden_name">
    <input type="hidden" name="layout"       id="hidden_layout">
    <input type="hidden" name="width"        id="hidden_width">
    <input type="hidden" name="height"       id="hidden_height">
    <input type="hidden" name="type"         id="hidden_type">
    <input type="hidden" name="config_json"  id="config_json">
    <input type="hidden" name="design_json"  id="design_json">
    <input type="file"   name="background_image" id="hidden_bg_input">
</form>

<div class="top-nav">
    <div class="nav-left">
        <div class="nav-logo"><i class="fa fa-graduation-cap"></i></div>
        {{ __('certificate_designer') }}
    </div>
    <div class="nav-center">
        <input type="text" id="cert_name" value="{{ $certificateTemplate->name ?? 'Untitled Certificate' }}" placeholder="{{ __('certificate_name') }}">
    </div>
    <div class="nav-right">
        <button class="btn btn-outline" onclick="window.location.href='{{ route('certificate-template.index') }}'">
            <i class="fa fa-arrow-left"></i> {{ __('go_back') }}
        </button>
        <button class="btn btn-primary" id="btn-save"><i class="fa fa-download"></i> {{ __('save_template') }}</button>
    </div>
</div>

<div class="workspace">
    <!-- Left Sidebar -->
    <div class="sidebar">
        <div class="tabs">
            <div class="tab active" data-target="tab-fields">{{ __('fields') }}</div>
            <div class="tab" data-target="tab-images">{{ __('images') }}</div>
            <div class="tab" data-target="tab-settings">{{ __('settings') }}</div>
        </div>

        <!-- FIELDS -->
        <div class="tab-content active" id="tab-fields">
            <div id="dynamic-fields-container"></div>
            <div class="field-group">
                <button class="field-btn open-richtext-modal" style="border: 1px solid var(--primary); background: #f0fdf4;">{{ __('rich_text') }} / {{ __('notes') }} <i class="fa fa-edit" style="color:var(--primary);"></i></button>
            </div>
        </div>

        <!-- IMAGES -->
        <div class="tab-content" id="tab-images">
            <div class="field-group">
                <div class="field-title">{{ __('system_images') }}</div>
                <button class="field-btn add-element" data-val="{horizontal_logo}" data-type="image">{{ __('school_logo_horizontal') }} <i class="fa fa-plus"></i></button>
                <button class="field-btn add-element" data-val="{vertical_logo}"   data-type="image">{{ __('school_logo_vertical') }} <i class="fa fa-plus"></i></button>
                <button class="field-btn add-element" data-val="{signature}"       data-type="image">{{ __('signature') }} <i class="fa fa-plus"></i></button>
                <button class="field-btn add-element" data-val="{user_image}"      data-type="image">{{ __('user_image') }} <i class="fa fa-plus"></i></button>
            </div>
        </div>

        <!-- SETTINGS -->
        <div class="tab-content" id="tab-settings">
            <div class="field-group">
                <div class="field-title">{{ __('canvas_settings') }}</div>
                <select id="cert_type" class="form-control" style="margin-bottom: 10px;">
                    <option value="Student" {{ (isset($certificateTemplate) && $certificateTemplate->type == 'Student') ? 'selected' : '' }}>{{ __('student_certificate') }}</option>
                    <option value="Staff"   {{ (isset($certificateTemplate) && $certificateTemplate->type == 'Staff')   ? 'selected' : '' }}>{{ __('staff_certificate') }}</option>
                </select>

                <select id="cert_layout" class="form-control" style="margin-bottom: 10px;">
                    <option value="A4 Landscape" {{ (isset($certificateTemplate) && $certificateTemplate->layout == 'A4 Landscape') ? 'selected' : '' }}>A4 {{ __('landscape') }}</option>
                    <option value="A4 Portrait"  {{ (isset($certificateTemplate) && $certificateTemplate->layout == 'A4 Portrait')  ? 'selected' : '' }}>A4 {{ __('portrait') }}</option>
                    <option value="Custom"        {{ (isset($certificateTemplate) && $certificateTemplate->layout == 'Custom')        ? 'selected' : '' }}>{{ __('custom_dimensions') }}</option>
                </select>

                <div class="control-group">
                    <div style="flex:1">
                        <label style="font-size:11px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">{{ __('width') }} (mm)</label>
                        <input type="number" id="cert_w" class="form-control" placeholder="{{ __('width') }}"  value="{{ $certificateTemplate->width  ?? 297 }}">
                    </div>
                    <div style="flex:1">
                        <label style="font-size:11px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">{{ __('height') }} (mm)</label>
                        <input type="number" id="cert_h" class="form-control" placeholder="{{ __('height') }}" value="{{ $certificateTemplate->height ?? 210 }}">
                    </div>
                </div>

                <div class="field-title" style="margin-top:20px;">{{ __('background_image') }}</div>
                <button class="btn btn-outline" style="width:100%; justify-content:center;" onclick="document.getElementById('hidden_bg_input').click()">{{ __('upload_background') }}</button>
            </div>
        </div>
    </div>

    <!-- Canvas -->
    <div class="canvas-wrapper">
        <div id="canvas-mouser">
            <!-- Elements injected here -->

            <!-- Selection Bounds -->
            <div id="selection-overlay">
                <div class="handle handle-tl" data-dir="tl"></div>
                <div class="handle handle-tr" data-dir="tr"></div>
                <div class="handle handle-bl" data-dir="bl"></div>
                <div class="handle handle-br" data-dir="br"></div>
                <div class="delete-overlay-btn" onclick="deleteSelected()"><i class="fa fa-times"></i></div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="properties-panel" id="prop-panel">
        <div class="prop-section" id="prop-text-group">
            <div class="prop-title">{{ __('text_content') }}</div>
            <textarea id="prop_text" class="form-control" rows="2" style="resize:none; display:none;"></textarea>
            <button id="btn-edit-richtext" class="btn btn-outline mt-2" style="width:100%; justify-content:center; display:none;"><i class="fa fa-edit"></i> {{ __('edit_rich_style_content') }}</button>
        </div>

        <div class="prop-section">
            <div class="prop-title">{{ __('typography') }}</div>
            <select id="prop_font" class="form-control" style="margin-bottom:10px;">
                <option value="Inter">{{ __('inter') }}</option>
                <option value="Pinyon Script">{{ __('pinyon_script') }}</option>
                <option value="Arial">{{ __('arial') }}</option>
                <option value="Times New Roman">{{ __('times_new_roman') }}</option>
                <option value="Courier New">{{ __('courier_new') }}</option>
            </select>
            <div class="control-group">
                <input type="number" id="prop_size" class="form-control" style="width:80px;" min="8" max="200" value="32">
                <div class="btn-group">
                    <button class="toggle-btn" id="btn-bold"><b>B</b></button>
                    <button class="toggle-btn" id="btn-italic"><i>I</i></button>
                    <button class="toggle-btn" id="btn-underline"><u>U</u></button>
                </div>
            </div>

            <div class="prop-title" style="margin-top:15px;">{{ __('text_color') }}</div>
            <div class="control-group mb-0">
                <input type="color" id="prop_color" class="form-control" style="height:38px; padding:2px;">
            </div>
        </div>

        <div class="prop-section">
            <div class="prop-title">{{ __('alignment') }}</div>
            <div class="btn-group">
                <button class="toggle-btn" id="btn-align-left"><i class="fa fa-align-left"></i></button>
                <button class="toggle-btn" id="btn-align-center"><i class="fa fa-align-center"></i></button>
                <button class="toggle-btn" id="btn-align-right"><i class="fa fa-align-right"></i></button>
            </div>
        </div>

        <div class="prop-section">
            <div class="prop-title">{{ __('transform') }}</div>
            <div class="input-row"><label>{{ __('x_offset') }}</label> <input type="number" id="prop_x" class="form-control"></div>
            <div class="input-row"><label>{{ __('y_offset') }}</label> <input type="number" id="prop_y" class="form-control"></div>
            <div class="input-row"><label>{{ __('width') }}</label>    <input type="number" id="prop_w" class="form-control"></div>
            <div class="input-row"><label>{{ __('height') }}</label>   <input type="number" id="prop_h" class="form-control"></div>
        </div>
    </div>

    <div class="properties-panel active" id="empty-panel">
        <div class="empty-selection">
            <i class="fa fa-mouse-pointer" style="font-size:30px; margin-bottom:15px; color:#d1d5db;"></i>
            <p>{{ __('select_an_element_to_edit_properties') }}</p>
        </div>
    </div>
</div>

<!-- Rich Text Editor Modal (enlarged) -->
<div id="richtext-modal">
    <div class="richtext-modal-inner">
        <div class="richtext-modal-header">
            <h3>{{ __('add_edit_rich_text') }}</h3>
            <button class="btn btn-outline" style="border:none; padding:5px;" onclick="$('#richtext-modal').hide()"><i class="fa fa-times"></i></button>
        </div>
        <div class="richtext-modal-body">
            <textarea id="tinymce-editor"></textarea>
            <div class="tag-chips-wrapper">
                <div class="tag-chips-label">{{ __('click_to_insert_data_tags') }}</div>
                <div id="tag-chips">
                    <!-- Injected Dynamically -->
                </div>
            </div>
        </div>
        <div class="richtext-modal-footer">
            <button id="cancel-modal" class="btn btn-outline">{{ __('cancel') }}</button>
            <button id="save-modal"   class="btn btn-primary"><i class="fa fa-check"></i> {{ __('save_to_canvas') }}</button>
        </div>
    </div>
</div>

<!-- Blade → JS config bridge (PHP values only, no business logic) -->
<script>
window.BUILDER_CONFIG = {
    initDesign:      {!! json_encode(isset($certificateTemplate) && $certificateTemplate->design_json ? $certificateTemplate->design_json : null) !!},
    backgroundImage: {!! json_encode(isset($certificateTemplate) && $certificateTemplate->background_image ? $certificateTemplate->background_image : null) !!},
    horizontalLogo:  {!! json_encode($horizontal_logo) !!},
    verticalLogo:    {!! json_encode($vertical_logo) !!},
    signature:       {!! json_encode($signature) !!},
    dummyLogo:       "{{ asset('assets/dummy_logo.jpg') }}"
};
</script>

<!-- External Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>

<!-- Builder JS -->
<script src="{{ asset('assets/js/certificate-builder.js') }}"></script>
</body>
</html>
