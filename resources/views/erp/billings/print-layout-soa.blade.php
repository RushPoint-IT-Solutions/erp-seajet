@extends('erp.layout')

@section('title', $title.' Print Layout')
@section('page-title', $title.' Print Layout')

@push('styles')
<style>
    .soa-layout-shell { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1rem; align-items: start; }
    .soa-layout-wrap { overflow: auto; padding: 1rem; background: #e9edf3; border: 1px solid #d7dce5; border-radius: 6px; }
    .soa-layout-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        margin: 0 auto;
        background: #fbf7df;
        border: 1px solid #1f2937;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        overflow: hidden;
        font-family: Arial, sans-serif;
        @if($settings['template_url'])
        background-image: url('{{ $settings['template_url'] }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        @endif
    }
    .soa-guide, .soa-guide-line, .soa-guide-vline, .soa-label, .soa-company, .soa-logo, .soa-title { position: absolute; pointer-events: none; }
    .soa-logo { left: 245px; top: 40px; width: 72px; height: 45px; object-fit: contain; }
    .soa-company { left: 320px; top: 36px; width: 310px; text-align: center; font-weight: 700; font-size: 13px; line-height: 1.15; }
    .soa-title { left: 330px; top: 132px; font-size: 17px; font-weight: 800; text-transform: uppercase; }
    .soa-guide { left: 66px; top: {{ $type === 'soa_a' ? 185 : 295 }}px; width: {{ $type === 'soa_a' ? 690 : 710 }}px; height: {{ $type === 'soa_a' ? 588 : 584 }}px; border: 1px solid #111827; }
    .soa-guide-line { height: 1px; background: #111827; }
    .soa-guide-vline { width: 1px; background: #111827; }
    .soa-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: rgba(17, 24, 39, .65); }
    .drag-field {
        position: absolute;
        box-sizing: border-box;
        padding: 0;
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        line-height: .95;
        white-space: pre-line;
        min-height: 18px;
        border: 1px dashed #ff6b00;
        color: #111827;
        background: rgba(255, 255, 255, .28);
        cursor: move;
    }
    .drag-field.amount { justify-content: flex-end; text-align: right; font-weight: 600; }
    .drag-field.serial { color: #991b1b; font-weight: 800; letter-spacing: .12em; }
    .drag-field.active { outline: 2px solid #0d6efd; outline-offset: 1px; background: rgba(13, 110, 253, .08); }
    .field-remove, .field-resize { display: none; position: absolute; z-index: 3; }
    .field-remove { right: -9px; top: -9px; width: 18px; height: 18px; border-radius: 50%; border: 0; background: #dc3545; color: #fff; align-items: center; justify-content: center; font-size: 11px; }
    .field-resize { right: -5px; bottom: -5px; width: 11px; height: 11px; background: #ff6b00; border: 1px solid #fff; cursor: nwse-resize; }
    .drag-field:hover .field-remove, .drag-field.active .field-remove { display: inline-flex; }
    .drag-field:hover .field-resize, .drag-field.active .field-resize { display: block; }
    .standard-hidden-field { display: none !important; }
    .template-muted .soa-guide, .template-muted .soa-guide-line, .template-muted .soa-guide-vline, .template-muted .soa-label, .template-muted .soa-company, .template-muted .soa-logo, .template-muted .soa-title { opacity: .35; }
    @media (max-width: 1199.98px) { .soa-layout-shell { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Drag {{ $title }} Fields</h1>
            <p class="text-muted mb-0">Adjust printable content positions for the {{ $title }} pre-printed form.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $resetRoute }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            <button type="button" id="saveLayout" class="btn btn-success"><i class="bi bi-save me-1"></i>Save Layout</button>
            <button type="submit" form="layoutForm" id="applyPreview" class="btn btn-primary"><i class="bi bi-eye me-1"></i>Apply and Preview</button>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="get" action="{{ $previewRoute }}" id="layoutForm">
        <input type="hidden" name="_token" id="layoutCsrfToken" value="{{ csrf_token() }}" disabled>
        <input type="hidden" name="paper_size" id="paper_size_hidden" value="{{ $settings['paper_size'] }}">
        <input type="hidden" name="paper_width" id="paper_width_hidden" value="{{ $settings['paper_width'] }}">
        <input type="hidden" name="paper_height" id="paper_height_hidden" value="{{ $settings['paper_height'] }}">
        <input type="hidden" name="template_url" id="template_url_hidden" value="{{ $settings['template_url'] }}">
        <input type="hidden" name="print_template" id="print_template_hidden" value="{{ $settings['print_template'] ? '1' : '0' }}">
        <input type="hidden" name="custom_fields_present" value="1">
        <input type="hidden" name="hidden_fields_present" value="1">
        <div id="hiddenStandardFields">
            @foreach($hiddenFields as $hiddenField)
                <input type="hidden" name="hidden_field[]" id="hidden_field_{{ $hiddenField }}" value="{{ $hiddenField }}">
            @endforeach
        </div>
        @foreach($positions as $field => $point)
            <input type="hidden" name="x_{{ $field }}" id="x_{{ $field }}" value="{{ $point[0] }}">
            <input type="hidden" name="y_{{ $field }}" id="y_{{ $field }}" value="{{ $point[1] }}">
            <input type="hidden" name="w_{{ $field }}" id="w_{{ $field }}" value="{{ $fieldStyles[$field]['width'] }}">
            <input type="hidden" name="fs_{{ $field }}" id="fs_{{ $field }}" value="{{ $fieldStyles[$field]['font_size'] }}">
        @endforeach
        <div id="customHiddenInputs">
            @foreach($customFields as $custom)
                <input type="hidden" name="custom_key[]" id="custom_key_{{ $custom['key'] }}" value="{{ $custom['key'] }}">
                <input type="hidden" name="custom_label[]" id="custom_label_{{ $custom['key'] }}" value="{{ $custom['label'] }}">
                <input type="hidden" name="custom_text[]" id="custom_text_{{ $custom['key'] }}" value="{{ $custom['text'] }}">
                <input type="hidden" name="custom_x[]" id="custom_x_{{ $custom['key'] }}" value="{{ $custom['x'] }}">
                <input type="hidden" name="custom_y[]" id="custom_y_{{ $custom['key'] }}" value="{{ $custom['y'] }}">
                <input type="hidden" name="custom_w[]" id="custom_w_{{ $custom['key'] }}" value="{{ $custom['width'] }}">
                <input type="hidden" name="custom_fs[]" id="custom_fs_{{ $custom['key'] }}" value="{{ $custom['font_size'] }}">
            @endforeach
        </div>

        <div class="soa-layout-shell">
            <div class="soa-layout-wrap">
                <div class="soa-layout-sheet" id="layoutSheet">
                    <img src="{{ asset('images/image.png') }}" class="soa-logo" alt="SeaJet logo">
                    <div class="soa-company">SEA-JET INTERNATIONAL FORWARDERS, INC.<br><span style="font-size: 10px; font-weight: 600">Unit 18 & 19 Bldg. B, Cargo Village Complex<br>Paranaque City 1704, Philippines</span></div>
                    <div class="soa-title">Statement of Account</div>
                    <div class="soa-guide"></div>
                    @if($type === 'soa_a')
                        <div class="soa-guide-line" style="left: 66px; top: 213px; width: 690px"></div>
                        <div class="soa-guide-vline" style="left: 625px; top: 185px; height: 588px"></div>
                        <div class="soa-label" style="left: 635px; top: 137px">A No.</div>
                        <div class="soa-label" style="left: 310px; top: 193px">Particulars</div>
                        <div class="soa-label" style="left: 666px; top: 193px">Amount</div>
                    @else
                        <div class="soa-guide-line" style="left: 54px; top: 319px; width: 710px"></div>
                        <div class="soa-guide-vline" style="left: 635px; top: 295px; height: 584px"></div>
                        <div class="soa-label" style="left: 640px; top: 137px">B No.</div>
                        <div class="soa-label" style="left: 82px; top: 190px">Customer:</div>
                        <div class="soa-label" style="left: 82px; top: 220px">Address:</div>
                        <div class="soa-label" style="left: 565px; top: 238px">Date:</div>
                        <div class="soa-label" style="left: 320px; top: 302px">Particulars</div>
                        <div class="soa-label" style="left: 675px; top: 302px">Amount</div>
                    @endif

                    @foreach($positions as $field => $point)
                        @php $sample = $samples[$field]; @endphp
                        <div class="drag-field {{ $sample['class'] }} {{ in_array($field, $hiddenFields) ? 'standard-hidden-field' : '' }}"
                             data-kind="standard"
                             data-field="{{ $field }}"
                             data-label="{{ $sample['label'] }}"
                             style="left: {{ $point[0] }}px; top: {{ $point[1] }}px; width: {{ $fieldStyles[$field]['width'] }}px; font-size: {{ $fieldStyles[$field]['font_size'] }}px">
                            <button type="button" class="field-remove" data-field="{{ $field }}" title="Hide field"><i class="bi bi-x"></i></button>
                            <span class="field-resize" data-field="{{ $field }}"></span>
                            {{ $sample['value'] }}
                        </div>
                    @endforeach
                    @foreach($customFields as $custom)
                        <div class="drag-field custom-drag-field"
                             data-kind="custom"
                             data-field="{{ $custom['key'] }}"
                             data-label="{{ $custom['label'] }}"
                             style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px">
                            <button type="button" class="field-remove" data-field="{{ $custom['key'] }}" title="Delete field"><i class="bi bi-x"></i></button>
                            <span class="field-resize" data-field="{{ $custom['key'] }}"></span>
                            {{ $custom['text'] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="erp-card p-3">
                <h2 class="h6 fw-bold mb-1">Paper & Template</h2>
                <div class="mb-3">
                    <label class="form-label small text-muted">Paper Size</label>
                    <select class="form-select form-select-sm" id="paperSizeControl">
                        <option value="letter" data-width="816" data-height="1056" {{ $settings['paper_size'] === 'letter' ? 'selected' : '' }}>Letter</option>
                        <option value="legal" data-width="816" data-height="1344" {{ $settings['paper_size'] === 'legal' ? 'selected' : '' }}>Legal</option>
                        <option value="a4" data-width="794" data-height="1123" {{ $settings['paper_size'] === 'a4' ? 'selected' : '' }}>A4</option>
                        <option value="custom" data-width="{{ $settings['paper_width'] }}" data-height="{{ $settings['paper_height'] }}" {{ $settings['paper_size'] === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label small text-muted">Width</label><input type="number" class="form-control form-control-sm" id="paperWidthControl" value="{{ $settings['paper_width'] }}"></div>
                    <div class="col-6"><label class="form-label small text-muted">Height</label><input type="number" class="form-control form-control-sm" id="paperHeightControl" value="{{ $settings['paper_height'] }}"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Background Template Image URL</label>
                    <input type="url" class="form-control form-control-sm" id="templateUrlControl" value="{{ $settings['template_url'] }}">
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="printTemplateControl" {{ $settings['print_template'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="printTemplateControl">Print background template too</label>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-3" id="applyTemplateSettings"><i class="bi bi-image me-1"></i>Update Sheet</button>

                <hr>
                <h2 class="h6 fw-bold mb-1">Selected Field</h2>
                <input type="text" class="form-control form-control-sm mb-2" id="selectedFieldLabel" value="None selected" readonly>
                <div class="row g-2 mb-2">
                    <div class="col-6"><label class="form-label small text-muted">Width</label><input type="number" class="form-control form-control-sm" id="fieldWidthControl" value="160"></div>
                    <div class="col-6"><label class="form-label small text-muted">Font</label><input type="number" class="form-control form-control-sm" id="fieldFontControl" value="12"></div>
                    <div class="col-6"><label class="form-label small text-muted">X</label><input type="number" class="form-control form-control-sm" id="fieldXControl" value="0"></div>
                    <div class="col-6"><label class="form-label small text-muted">Y</label><input type="number" class="form-control form-control-sm" id="fieldYControl" value="0"></div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm w-100 mb-3" id="deleteSelectedField"><i class="bi bi-trash me-1"></i>Hide / Delete Selected</button>

                <hr>
                <h2 class="h6 fw-bold mb-1">Add Custom Field</h2>
                <input type="text" class="form-control form-control-sm mb-2" id="customLabelControl" value="Custom Field">
                <textarea class="form-control form-control-sm mb-2" id="customTextControl" rows="2">New Field</textarea>
                <div class="row g-2 mb-2">
                    <div class="col-6"><input type="number" class="form-control form-control-sm" id="customWidthControl" value="160"></div>
                    <div class="col-6"><input type="number" class="form-control form-control-sm" id="customFontControl" value="12"></div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-3" id="addCustomField"><i class="bi bi-plus-circle me-1"></i>Add Field</button>

                <hr>
                <h2 class="h6 fw-bold mb-2">Fields</h2>
                <div class="list-group list-group-flush" id="fieldList">
                    @foreach($positions as $field => $point)
                        @php $sample = $samples[$field]; @endphp
                        <button type="button" class="list-group-item list-group-item-action px-0 field-focus" data-field="{{ $field }}">
                            <span class="fw-semibold">{{ $sample['label'] }}</span>
                            <span class="badge bg-secondary ms-2 {{ in_array($field, $hiddenFields) ? '' : 'd-none' }}" id="hidden_badge_{{ $field }}">Hidden</span>
                            <span class="badge badge-soft float-end" id="pos_{{ $field }}">{{ $point[0] }}, {{ $point[1] }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>
        </div>
    </form>
@endsection

@push('scripts')
<script>
$(function () {
    var selectedField = null;
    var active = null;
    var resizing = null;
    var offsetX = 0;
    var offsetY = 0;
    var sheet = document.getElementById('layoutSheet');
    var customCounter = {{ count($customFields) + 1 }};

    function setHiddenValue(field, key, value, kind) {
        $('#' + (kind === 'custom' ? 'custom_' : '') + key + '_' + field).val(value);
    }
    function setActive(field) {
        $('.drag-field').removeClass('active');
        var el = $('.drag-field[data-field="' + field + '"]');
        if (!el.length) return;
        el.addClass('active');
        selectedField = field;
        $('#selectedFieldLabel').val(el.data('label') || field);
        $('#fieldWidthControl').val(parseInt(el[0].style.width, 10) || el.outerWidth());
        $('#fieldFontControl').val(parseInt(el[0].style.fontSize, 10) || 12);
        $('#fieldXControl').val(parseInt(el[0].style.left, 10) || 0);
        $('#fieldYControl').val(parseInt(el[0].style.top, 10) || 0);
    }
    function updatePosition(el, x, y) {
        x = Math.max(0, Math.min(Math.round(x), sheet.clientWidth - el.offsetWidth));
        y = Math.max(0, Math.min(Math.round(y), sheet.clientHeight - el.offsetHeight));
        el.style.left = x + 'px';
        el.style.top = y + 'px';
        var field = el.getAttribute('data-field');
        var kind = el.getAttribute('data-kind');
        setHiddenValue(field, 'x', x, kind);
        setHiddenValue(field, 'y', y, kind);
        $('#pos_' + field).text(x + ', ' + y);
        $('#fieldXControl').val(x);
        $('#fieldYControl').val(y);
    }
    function updateSize(el, width, fontSize) {
        width = Math.max(20, Math.min(parseInt(width, 10) || 160, 900));
        fontSize = Math.max(6, Math.min(parseInt(fontSize, 10) || 12, 48));
        el.style.width = width + 'px';
        el.style.fontSize = fontSize + 'px';
        var field = el.getAttribute('data-field');
        var kind = el.getAttribute('data-kind');
        setHiddenValue(field, 'w', width, kind);
        setHiddenValue(field, 'fs', fontSize, kind);
        $('#fieldWidthControl').val(width);
        $('#fieldFontControl').val(fontSize);
    }
    function applyPaperAndTemplate() {
        var width = Math.max(400, Math.min(parseInt($('#paperWidthControl').val(), 10) || 816, 1600));
        var height = Math.max(400, Math.min(parseInt($('#paperHeightControl').val(), 10) || 1056, 2200));
        var url = $('#templateUrlControl').val().trim();
        $('#paper_size_hidden').val($('#paperSizeControl').val());
        $('#paper_width_hidden').val(width);
        $('#paper_height_hidden').val(height);
        $('#template_url_hidden').val(url);
        $('#print_template_hidden').val($('#printTemplateControl').is(':checked') ? '1' : '0');
        $('#layoutSheet').css({ width: width + 'px', height: height + 'px', backgroundImage: url ? 'url("' + url + '")' : 'none' });
        $('#layoutSheet').toggleClass('template-muted', !$('#printTemplateControl').is(':checked'));
    }
    function hideOrDelete(field) {
        var el = $('.drag-field[data-field="' + field + '"]');
        if (!el.length) return;
        if (el.data('kind') === 'custom') {
            el.remove();
            $('#custom_key_' + field + ', #custom_label_' + field + ', #custom_text_' + field + ', #custom_x_' + field + ', #custom_y_' + field + ', #custom_w_' + field + ', #custom_fs_' + field).remove();
        } else {
            el.addClass('standard-hidden-field');
            if (!$('#hidden_field_' + field).length) {
                $('#hiddenStandardFields').append('<input type="hidden" name="hidden_field[]" id="hidden_field_' + field + '" value="' + field + '">');
            }
            $('#hidden_badge_' + field).removeClass('d-none');
        }
    }

    $('#saveLayout').on('click', function () {
        applyPaperAndTemplate();
        $('#layoutCsrfToken').prop('disabled', false);
        $('#layoutForm').attr('method', 'post').attr('action', '{{ $saveRoute }}').trigger('submit');
    });
    $('#applyPreview').on('click', function () {
        applyPaperAndTemplate();
        $('#layoutCsrfToken').prop('disabled', true);
        $('#layoutForm').attr('method', 'get').attr('action', '{{ $previewRoute }}');
    });
    $('#paperSizeControl').on('change', function () {
        var option = $(this).find(':selected');
        if ($(this).val() !== 'custom') {
            $('#paperWidthControl').val(option.data('width'));
            $('#paperHeightControl').val(option.data('height'));
        }
        applyPaperAndTemplate();
    });
    $('#applyTemplateSettings, #printTemplateControl').on('click change', applyPaperAndTemplate);
    $('.field-focus').on('click', function () { setActive($(this).data('field')); });
    $('#layoutSheet').on('mousedown', '.drag-field', function (event) {
        if ($(event.target).closest('.field-remove, .field-resize').length) return;
        active = this;
        setActive(active.getAttribute('data-field'));
        offsetX = event.pageX - active.offsetLeft;
        offsetY = event.pageY - active.offsetTop;
        event.preventDefault();
    });
    $('#layoutSheet').on('mousedown', '.field-resize', function (event) {
        resizing = $(this).closest('.drag-field')[0];
        setActive(resizing.getAttribute('data-field'));
        event.preventDefault();
        event.stopPropagation();
    });
    $(document).on('mousemove', function (event) {
        if (active) updatePosition(active, event.pageX - offsetX, event.pageY - offsetY);
        if (resizing) updateSize(resizing, event.pageX - $(resizing).offset().left + 6, parseInt(resizing.style.fontSize, 10) || 12);
    }).on('mouseup', function () {
        active = null;
        resizing = null;
    });
    $('#fieldWidthControl, #fieldFontControl').on('input', function () {
        var el = $('.drag-field[data-field="' + selectedField + '"]')[0];
        if (el) updateSize(el, $('#fieldWidthControl').val(), $('#fieldFontControl').val());
    });
    $('#fieldXControl, #fieldYControl').on('input', function () {
        var el = $('.drag-field[data-field="' + selectedField + '"]')[0];
        if (el) updatePosition(el, $('#fieldXControl').val(), $('#fieldYControl').val());
    });
    $('#layoutSheet').on('click', '.field-remove', function () { hideOrDelete($(this).data('field')); });
    $('#deleteSelectedField').on('click', function () { hideOrDelete(selectedField); });
    $('#addCustomField').on('click', function () {
        var key = 'custom_' + Date.now() + '_' + customCounter++;
        var label = $('#customLabelControl').val().trim() || 'Custom Field';
        var text = $('#customTextControl').val().trim() || 'New Field';
        var width = parseInt($('#customWidthControl').val(), 10) || 160;
        var font = parseInt($('#customFontControl').val(), 10) || 12;
        $('#customHiddenInputs').append(
            '<input type="hidden" name="custom_key[]" id="custom_key_' + key + '" value="' + key + '">' +
            '<input type="hidden" name="custom_label[]" id="custom_label_' + key + '" value="' + $('<div>').text(label).html() + '">' +
            '<input type="hidden" name="custom_text[]" id="custom_text_' + key + '" value="' + $('<div>').text(text).html() + '">' +
            '<input type="hidden" name="custom_x[]" id="custom_x_' + key + '" value="40">' +
            '<input type="hidden" name="custom_y[]" id="custom_y_' + key + '" value="40">' +
            '<input type="hidden" name="custom_w[]" id="custom_w_' + key + '" value="' + width + '">' +
            '<input type="hidden" name="custom_fs[]" id="custom_fs_' + key + '" value="' + font + '">'
        );
        $('#layoutSheet').append('<div class="drag-field custom-drag-field" data-kind="custom" data-field="' + key + '" data-label="' + $('<div>').text(label).html() + '" style="left: 40px; top: 40px; width: ' + width + 'px; font-size: ' + font + 'px"><button type="button" class="field-remove" data-field="' + key + '"><i class="bi bi-x"></i></button><span class="field-resize" data-field="' + key + '"></span>' + $('<div>').text(text).html() + '</div>');
        setActive(key);
    });
    applyPaperAndTemplate();
});
</script>
@endpush
