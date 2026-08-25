/* =============================================
   Certificate Builder - JavaScript
   All builder logic is self-contained here.
   Blade-injected config is read from window.BUILDER_CONFIG
   ============================================= */

const DATA_TAGS = {
    Student: {
        "Student Data": {
            "{full_name}": "Full Name",
            "{first_name}": "First Name",
            "{last_name}": "Last Name",
            "{class_section}": "Class Section",
            "{student_mobile}": "Student Mobile",
            "{dob}": "Date of Birth",
            "{roll_no}": "Roll Number",
            "{admission_no}": "Admission Number",
            "{current_address}": "Current Address",
            "{permanent_address}": "Permanent Address",
            "{gender}": "Gender",
            "{admission_date}": "Admission Date",
            "{guardian_name}": "Guardian Name",
            "{guardian_mobile}": "Guardian Mobile",
            "{guardian_email}": "Guardian Email"
        },
        "Result Fields": {
            "{exam}": "Exam",
            "{total_marks}": "Total Marks",
            "{obtain_marks}": "Obtained Marks",
            "{grade}": "Grade",
            "{percentage}": "Percentage",
            "{result_status}": "Result Status"
        },
        "School & Custom": {
            "{school_name}": "School Name",
            "{issue_date}": "Issue Date",
            "{session_year}": "Session Year"
        }
    },
    Staff: {
        "Staff Data": {
            "{full_name}": "Full Name",
            "{first_name}": "First Name",
            "{last_name}": "Last Name",
            "{mobile}": "Mobile",
            "{dob}": "Date of Birth",
            "{current_address}": "Current Address",
            "{permanent_address}": "Permanent Address",
            "{gender}": "Gender",
            "{email}": "Email",
            "{joining_date}": "Joining Date",
            "{role}": "Role",
            "{qualification}": "Qualification",
            "{experience}": "Experience"
        },
        "School & Custom": {
            "{school_name}": "School Name",
            "{issue_date}": "Issue Date",
            "{session_year}": "Session Year"
        }
    }
};

$(document).ready(function () {

    // ---- Read Blade-injected Config ----
    const CFG = window.BUILDER_CONFIG || {};

    // ---- Tabs logic ----
    $('.tab').click(function () {
        $('.tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#' + $(this).data('target')).addClass('active');
    });

    // ---- Canvas Scaling and Init ----
    const MM_TO_PX = 3.7795275591;
    let canvasW = 297 * MM_TO_PX;
    let canvasH = 210 * MM_TO_PX;

    const $canvas = $('#canvas-mouser');
    const $overlay = $('#selection-overlay');

    // ---- Elements data array ----
    let elements = [];
    let selectedId = null;

    if (CFG.initDesign && CFG.initDesign.elements) {
        elements = CFG.initDesign.elements.map(e => ({
            id: e.id, type: e.type, content: e.content, value: e.value,
            x: (e.style != null && e.style.left != null) ? parseFloat(e.style.left) : 50,
            y: (e.style != null && e.style.top  != null) ? parseFloat(e.style.top)  : 50,
            w: (e.style != null && e.style.width  != null) ? parseFloat(e.style.width)  : (e.type === 'image' ? 150 : 300),
            h: (e.style != null && e.style.height != null) ? parseFloat(e.style.height) : (e.type === 'image' ? 150 : 60),
            fontSize:       (e.style != null && e.style.fontSize       != null) ? parseFloat(e.style.fontSize) : 32,
            fontFamily:     (e.style != null && e.style.fontFamily     != null) ? e.style.fontFamily     : 'Inter',
            color:          (e.style != null && e.style.color          != null) ? e.style.color          : '#000000',
            fontWeight:     (e.style != null && e.style.fontWeight     != null) ? e.style.fontWeight     : 'normal',
            fontStyle:      (e.style != null && e.style.fontStyle      != null) ? e.style.fontStyle      : 'normal',
            textDecoration: (e.style != null && e.style.textDecoration != null) ? e.style.textDecoration : 'none',
            textAlign:      (e.style != null && e.style.textAlign      != null) ? e.style.textAlign      : 'center'
        }));
    }

    if (CFG.backgroundImage) {
        $canvas.css('background-image', `url(${CFG.backgroundImage})`);
        $canvas.css('background-size', '100% 100%');
    }

    // ---- Canvas Sizing ----
    function updateCanvasSize() {
        let w = parseFloat($('#cert_w').val()) || 297;
        let h = parseFloat($('#cert_h').val()) || 210;
        canvasW = Math.round(w * MM_TO_PX);
        canvasH = Math.round(h * MM_TO_PX);

        let wrapperW = $('.canvas-wrapper').width();
        let wrapperH = $('.canvas-wrapper').height();

        let padding = 60;
        let availableW = wrapperW - padding;
        let availableH = wrapperH - padding;

        let scale = Math.min(availableW / canvasW, availableH / canvasH);
        if (scale > 1) scale = 1;

        let scaledW = canvasW * scale;
        let scaledH = canvasH * scale;

        let leftPost = (wrapperW - scaledW) / 2;
        let topPost = (wrapperH - scaledH) / 2;

        $canvas.css({
            width: canvasW + 'px',
            height: canvasH + 'px',
            transform: `scale(${scale})`,
            left: Math.max(0, leftPost) + 'px',
            top: Math.max(0, topPost) + 'px'
        });

        updateOverlay();
    }

    $('#cert_layout').change(function () {
        let val = $(this).val();
        if (val === 'A4 Landscape') { $('#cert_w').val(297); $('#cert_h').val(210); $('#cert_w, #cert_h').prop('readonly', true); }
        else if (val === 'A4 Portrait') { $('#cert_w').val(210); $('#cert_h').val(297); $('#cert_w, #cert_h').prop('readonly', true); }
        else { $('#cert_w, #cert_h').prop('readonly', false); }
        updateCanvasSize();
    });

    $('#cert_w, #cert_h').on('input', function () {
        if ($('#cert_layout').val() === 'Custom') {
            updateCanvasSize();
        }
    });

    // ---- Certificate Type → Re-render Data Tags ----
    $('#cert_type').change(function () {
        renderDataTags();
    });

    function renderDataTags() {
        let type = $('#cert_type').val() === 'Staff' ? 'Staff' : 'Student';
        let tagsObj = DATA_TAGS[type];

        let sidebarHtml = '';
        let chipHtml = '';

        for (let group in tagsObj) {
            sidebarHtml += `<div class="field-group"><div class="field-title">${group}</div>`;
            for (let tagKey in tagsObj[group]) {
                let tagLabel = tagsObj[group][tagKey];
                sidebarHtml += `<button class="field-btn add-element" data-val="${tagKey}" data-type="text" title="${tagLabel}">${tagLabel} <i class="fa fa-plus"></i></button>`;
                chipHtml += `<span class="tag-chip" data-tag="${tagKey}" title="${tagLabel}">{ ${tagLabel} }</span>`;
            }
            sidebarHtml += `</div>`;
        }

        $('#dynamic-fields-container').html(sidebarHtml);
        $('#tag-chips').html(chipHtml);
    }

    // ---- Background Image Handler ----
    $('#hidden_bg_input').change(function (e) {
        if (e.target.files && e.target.files[0]) {
            let reader = new FileReader();
            reader.onload = function (ev) {
                $canvas.css('background-image', `url(${ev.target.result})`);
                $canvas.css('background-size', '100% 100%');
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // ---- Core Rendering ----
    function renderElements() {
        $('.cert-element').remove();
        elements.forEach(el => {
            let html = '';
            if (el.type === 'text') {
                html = `<div class="cert-element" id="${el.id}">${el.content}</div>`;
            } else {
                let src = CFG.dummyLogo;
                if (el.value === '{horizontal_logo}') src = CFG.horizontalLogo;
                else if (el.value === '{vertical_logo}' || el.value === '{school_logo}') src = CFG.verticalLogo;
                else if (el.value === '{signature}') src = CFG.signature;
                else if (el.value === '{user_image}') src = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

                html = `<div class="cert-element" id="${el.id}"><img src="${src}" alt="img" draggable="false"></div>`;
            }
            let $el = $(html);
            $el.css({
                left: el.x + 'px', top: el.y + 'px', width: el.w + 'px', height: el.h + 'px',
                fontSize: el.fontSize + 'px', fontFamily: el.fontFamily, color: el.color,
                fontWeight: el.fontWeight, fontStyle: el.fontStyle,
                textDecoration: el.textDecoration, textAlign: el.textAlign
            });
            $canvas.append($el);
        });
        updateOverlay();
    }

    // ---- Selection and Properties ----
    function selectElement(id) {
        selectedId = id;
        updateOverlay();
        if (id) {
            $('#prop-panel').addClass('active');
            $('#empty-panel').removeClass('active');
            let el = elements.find(e => e.id === id);

            if (el.type === 'image') {
                $('#prop-text-group').hide();
            } else if (el.value) {
                $('#prop-text-group').hide();
            } else {
                $('#prop-text-group').show();
                $('#prop_text').hide();
                $('#btn-edit-richtext').show().off('click').on('click', function () {
                    editingRichTextId = el.id;
                    tinymce.get('tinymce-editor').setContent(el.content);
                    $('#richtext-modal').css('display', 'flex');
                });
            }

            $('#prop_font').val(el.fontFamily || 'Inter');
            $('#prop_size').val(el.fontSize);
            $('#prop_color').val(el.color || '#000000');

            $('#btn-bold').toggleClass('active', el.fontWeight === 'bold');
            $('#btn-italic').toggleClass('active', el.fontStyle === 'italic');
            $('#btn-underline').toggleClass('active', el.textDecoration === 'underline');
            $('#btn-align-left').toggleClass('active', el.textAlign === 'left');
            $('#btn-align-center').toggleClass('active', el.textAlign === 'center');
            $('#btn-align-right').toggleClass('active', el.textAlign === 'right');

            updatePropInputs(el);
        } else {
            $('#prop-panel').removeClass('active');
            $('#empty-panel').addClass('active');
        }
    }

    function updatePropInputs(el) {
        $('#prop_x').val(Math.round(el.x));
        $('#prop_y').val(Math.round(el.y));
        $('#prop_w').val(Math.round(el.w));
        $('#prop_h').val(Math.round(el.h));
    }

    window.deleteSelected = function () {
        if (!selectedId) return;
        elements = elements.filter(e => e.id !== selectedId);
        selectElement(null);
        renderElements();
    };

    function updateOverlay() {
        if (!selectedId) { $overlay.hide(); return; }
        let el = elements.find(e => e.id === selectedId);
        if (!el) { $overlay.hide(); return; }
        $overlay.css({
            left: el.x + 'px', top: el.y + 'px', width: el.w + 'px', height: el.h + 'px'
        }).show();
    }

    // ---- Property Panel Listeners ----
    $('#prop_font').on('change', function () {
        if (selectedId) { elements.find(e => e.id === selectedId).fontFamily = $(this).val(); renderElements(); }
    });
    $('#prop_size').on('input', function () {
        if (selectedId) { elements.find(e => e.id === selectedId).fontSize = $(this).val(); renderElements(); updateOverlay(); }
    });
    $('#prop_color').on('input', function () {
        if (selectedId) { elements.find(e => e.id === selectedId).color = $(this).val(); renderElements(); }
    });

    $('#btn-bold').click(function () { if (selectedId) { let el = elements.find(e => e.id === selectedId); el.fontWeight = el.fontWeight === 'bold' ? 'normal' : 'bold'; renderElements(); selectElement(selectedId); } });
    $('#btn-italic').click(function () { if (selectedId) { let el = elements.find(e => e.id === selectedId); el.fontStyle = el.fontStyle === 'italic' ? 'normal' : 'italic'; renderElements(); selectElement(selectedId); } });
    $('#btn-underline').click(function () { if (selectedId) { let el = elements.find(e => e.id === selectedId); el.textDecoration = el.textDecoration === 'underline' ? 'none' : 'underline'; renderElements(); selectElement(selectedId); } });

    $('#btn-align-left').click(function () { if (selectedId) { elements.find(e => e.id === selectedId).textAlign = 'left'; renderElements(); selectElement(selectedId); } });
    $('#btn-align-center').click(function () { if (selectedId) { elements.find(e => e.id === selectedId).textAlign = 'center'; renderElements(); selectElement(selectedId); } });
    $('#btn-align-right').click(function () { if (selectedId) { elements.find(e => e.id === selectedId).textAlign = 'right'; renderElements(); selectElement(selectedId); } });

    // ---- Transform Inputs ----
    $('#prop_x, #prop_y, #prop_w, #prop_h').on('input', function () {
        if (!selectedId) return;
        let el = elements.find(e => e.id === selectedId);
        let newX = parseFloat($('#prop_x').val()) || 0;
        let newY = parseFloat($('#prop_y').val()) || 0;
        let newW = parseFloat($('#prop_w').val()) || 100;
        let newH = Math.max(20, parseFloat($('#prop_h').val()) || 20);

        if (newX < 0) newX = 0;
        if (newY < 0) newY = 0;
        if (newX + 20 > canvasW) newX = canvasW - 20;
        if (newY + 20 > canvasH) newY = canvasH - 20;
        if (newX + newW > canvasW) newW = canvasW - newX;
        if (newY + newH > canvasH) newH = canvasH - newY;

        el.x = newX; el.y = newY; el.w = newW; el.h = newH;
        renderElements();
    });

    // ---- Add Elements from Sidebar ----
    $(document).on('click', '.add-element', function () {
        let type = $(this).data('type');
        let val = $(this).data('val');

        let newEl = {
            id: 'el_' + Date.now(),
            type: type,
            content: val,
            value: val,
            x: 50, y: 50,
            w: type === 'image' ? 150 : 300,
            h: type === 'image' ? 150 : 60,
            fontSize: 32, fontFamily: 'Inter', color: '#000000',
            fontWeight: 'bold', fontStyle: 'normal', textDecoration: 'none', textAlign: 'center'
        };
        elements.push(newEl);
        renderElements();
        selectElement(newEl.id);
    });

    // ---- Rich Text Editor ----
    let editingRichTextId = null;

    tinymce.init({
        selector: '#tinymce-editor',
        height: 420,
        menubar: true,
        plugins: 'lists link',
        toolbar: [
            'styleselect fontselect fontsizeselect',
            'undo redo | cut copy paste | bold italic | alignleft aligncenter alignright alignjustify | table | image | fullscreen',
            'bullist numlist | outdent indent | blockquote autolink | lists | fontfamily | fontsize | code | preview'
        ],
        font_family_formats: 'Inter=Inter; Pinyon Script=Pinyon Script; Arial=Arial; Times New Roman=Times New Roman; Courier New=Courier New',
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Pinyon+Script&display=swap'); body { font-family: Inter, sans-serif; }",
        branding: false,
        promotion: false
    });

    $(document).on('click', '.tag-chip', function () {
        let tag = $(this).data('tag');
        tinymce.get('tinymce-editor').insertContent(tag);
    });

    $('.open-richtext-modal').click(function (e) {
        e.stopPropagation();
        editingRichTextId = null;
        tinymce.get('tinymce-editor').setContent('');
        $('#richtext-modal').css('display', 'flex');
    });

    $('#cancel-modal').click(function () {
        $('#richtext-modal').hide();
    });

    $('#save-modal').click(function () {
        let content = tinymce.get('tinymce-editor').getContent();
        if (editingRichTextId) {
            let el = elements.find(e => e.id === editingRichTextId);
            if (el) { el.content = content; renderElements(); selectElement(el.id); }
        } else {
            let newEl = {
                id: 'el_' + Date.now(), type: 'text', content: content, value: null,
                x: 50, y: 50, w: 400, h: 200,
                fontSize: 16, fontFamily: 'Inter', color: '#000000',
                fontWeight: 'normal', fontStyle: 'normal', textDecoration: 'none', textAlign: 'left'
            };
            elements.push(newEl);
            renderElements();
            selectElement(newEl.id);
        }
        $('#richtext-modal').hide();
    });

    // ---- Drag & Resize Engine ----
    let action = null, resizeDir = null, startX, startY, startElCoords = {};

    $canvas.on('mousedown', '.cert-element', function (e) {
        if (e.button !== 0) return;
        selectElement(this.id);
        action = 'drag';
        startX = e.clientX; startY = e.clientY;
        let el = elements.find(ex => ex.id === selectedId);
        startElCoords = { x: el.x, y: el.y };
        e.stopPropagation();
    });

    $overlay.on('mousedown', '.handle', function (e) {
        if (e.button !== 0) return;
        action = 'resize';
        resizeDir = $(this).data('dir');
        startX = e.clientX; startY = e.clientY;
        let el = elements.find(ex => ex.id === selectedId);
        startElCoords = { x: el.x, y: el.y, w: el.w, h: el.h };
        e.stopPropagation();
    });

    $canvas.on('mousedown', function (e) {
        if (e.target === this) { selectElement(null); }
    });

    $(document).on('mousemove', function (e) {
        if (!action || !selectedId) return;
        let scale = parseFloat($canvas.css('transform').split(',')[3]) || 1;
        let dx = (e.clientX - startX) / scale;
        let dy = (e.clientY - startY) / scale;
        let el = elements.find(ex => ex.id === selectedId);

        if (action === 'drag') {
            el.x = startElCoords.x + dx;
            el.y = startElCoords.y + dy;
            if (el.x < 0) el.x = 0;
            if (el.y < 0) el.y = 0;
            if (el.x + el.w > canvasW) el.x = canvasW - el.w;
            if (el.y + el.h > canvasH) el.y = canvasH - el.h;
            $(`#${el.id}`).css({ left: el.x + 'px', top: el.y + 'px' });
            updateOverlay();
            updatePropInputs(el);
        } else if (action === 'resize') {
            let newX = startElCoords.x, newY = startElCoords.y;
            let newW = startElCoords.w, newH = startElCoords.h;

            if (resizeDir.includes('r')) { newW += dx; if (newX + newW > canvasW) newW = canvasW - newX; }
            if (resizeDir.includes('l')) { newX += dx; if (newX < 0) newX = 0; newW = (startElCoords.x + startElCoords.w) - newX; }
            if (resizeDir.includes('b')) { newH += dy; if (newY + newH > canvasH) newH = canvasH - newY; }
            if (resizeDir.includes('t')) { newY += dy; if (newY < 0) newY = 0; newH = (startElCoords.y + startElCoords.h) - newY; }

            if (newW < 20) { newW = 20; if (resizeDir.includes('l')) newX = (startElCoords.x + startElCoords.w) - 20; }
            if (newH < 20) { newH = 20; if (resizeDir.includes('t')) newY = (startElCoords.y + startElCoords.h) - 20; }

            el.x = newX; el.y = newY; el.w = newW; el.h = newH;
            $(`#${el.id}`).css({ left: el.x + 'px', top: el.y + 'px', width: el.w + 'px', height: el.h + 'px' });
            updateOverlay();
            updatePropInputs(el);
        }
    });

    $(document).on('mouseup', function () { action = null; resizeDir = null; });

    // ---- Save Hook ----
    $('#btn-save').click(function () {
        $('#hidden_name').val($('#cert_name').val());
        $('#hidden_layout').val($('#cert_layout').val());
        $('#hidden_width').val($('#cert_w').val());
        $('#hidden_height').val($('#cert_h').val());
        $('#hidden_type').val($('#cert_type').val());

        let outElements = elements.map(e => ({
            id: e.id, type: e.type, content: e.content, value: e.value,
            style: {
                top: e.y, left: e.x, width: e.w, height: e.h,
                fontSize: e.fontSize, fontFamily: e.fontFamily,
                fontWeight: e.fontWeight, fontStyle: e.fontStyle,
                textDecoration: e.textDecoration, textAlign: e.textAlign, color: e.color
            }
        }));

        $('#config_json').val(JSON.stringify({
            layout: $('#cert_layout').val(), width: $('#cert_w').val(), height: $('#cert_h').val(), type: $('#cert_type').val()
        }));
        $('#design_json').val(JSON.stringify({ elements: outElements }));
        $('#save-form').submit();
    });

    // ---- Initialization ----
    renderDataTags();
    $('#cert_layout').trigger('change');
    renderElements();
    $(window).resize(updateCanvasSize);
});
