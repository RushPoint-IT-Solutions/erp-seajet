@extends('erp.layout')

@section('title', 'Check Voucher Print Layout')
@section('page-title', 'Check Voucher Print Layout')

@php
    $settings = $layout['settings'];
    $positions = $layout['positions'];
    $fieldStyles = $layout['field_styles'];
    $customFields = $layout['custom_fields'];
    $formattedAmount = number_format($sample['net'], 2);
    $sampleValues = [
        'company_name' => strtoupper($sample['company']),
        'form_title' => 'CHECK VOUCHER',
        'payee_label' => 'PAYEE',
        'payee' => $sample['vendor'],
        'check_no_label' => 'Check No:',
        'check_no' => $sample['check_no'],
        'document_no_label' => 'Document No:',
        'document_no' => $sample['document_no'],
        'document_date_label' => 'Document Date:',
        'document_date' => $sample['document_date'],
        'payment_info_title' => 'PAYMENT INFORMATION',
        'amount_header' => 'Amount :',
        'account_name_label' => 'Account Name:',
        'account_name' => $sample['account_name'],
        'amount' => $formattedAmount,
        'account_no_label' => 'Account No:',
        'account_no' => $sample['account_no'],
        'amount_words' => $sample['amount_words'],
        'description_label' => 'DESCRIPTION',
        'description' => $sample['description'],
        'account_title_header' => 'Account Title',
        'debit_header' => 'Debit',
        'credit_header' => 'Credit',
        'debit_account_title' => $sample['debit_account_title'],
        'debit_amount' => $formattedAmount,
        'credit_account_title' => $sample['credit_account_title'],
        'credit_amount' => $formattedAmount,
        'total_label' => 'Total',
        'total_debit' => $formattedAmount,
        'total_credit' => $formattedAmount,
        'prepared_by_label' => 'Prepared by:',
        'prepared_by' => 'MLP',
        'prepared_date_label' => 'Date:',
        'checked_by_label' => 'Checked by:',
        'checked_by' => 'GCB',
        'checked_date_label' => 'Date:',
        'approved_by_label' => 'Approved by:',
        'approved_by' => 'ASA/BCA/CAC',
        'approved_date_label' => 'Date:',
        'received_by_label' => 'Received by:',
        'received_by' => "Signature over Printed Name\nID No Presented",
        'received_date_label' => 'Date:',
    ];
@endphp

@push('styles')
<style>
    .cv-layout-shell { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1rem; align-items: start; }
    .cv-layout-wrap { overflow: auto; padding: 1rem; background: #e9edf3; border: 1px solid #d7dce5; border-radius: 6px; }
    .cv-layout-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        margin: 0 auto;
        background: #fff;
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
    .cv-template-muted { background-color: #fbf7df; }
    .cv-guide-box { position: absolute; pointer-events: none; border: 1px solid rgba(17, 24, 39, .5); }
    .cv-guide-line { position: absolute; pointer-events: none; border-top: 1px solid rgba(17, 24, 39, .5); }
    .cv-guide-vline { position: absolute; pointer-events: none; border-left: 1px solid rgba(17, 24, 39, .5); }
    .cv-guide-muted { position: absolute; pointer-events: none; border-top: 1px dashed rgba(17, 24, 39, .45); }
    .drag-field {
        position: absolute;
        box-sizing: border-box;
        min-height: 18px;
        padding: 1px 2px;
        border: 1px dashed #fe790d;
        color: #111827;
        background: rgba(255, 255, 255, .58);
        cursor: move;
        white-space: pre-line;
        line-height: 1.05;
    }
    .drag-field.amount { text-align: right; }
    .drag-field.header-text { text-align: center; font-weight: 700; letter-spacing: .02em; }
    .drag-field.logo-mark { height: 66px; text-align: center; }
    .drag-field.logo-mark img { display: block; width: 100%; height: 60px; object-fit: contain; pointer-events: none; }
    .drag-field.label { color: #374151; }
    .drag-field.signature { text-align: center; border-top: 1px solid #111827; border-left-color: transparent; border-right-color: transparent; border-bottom-color: transparent; padding-top: 6px; }
    .drag-field.active { outline: 2px solid #0d6efd; outline-offset: 1px; background: rgba(13, 110, 253, .08); }
    .field-remove, .field-resize { display: none; position: absolute; z-index: 3; }
    .field-remove { right: -9px; top: -9px; width: 18px; height: 18px; border-radius: 50%; border: 0; background: #dc3545; color: #fff; align-items: center; justify-content: center; font-size: 11px; }
    .field-resize { right: -5px; bottom: -5px; width: 11px; height: 11px; background: #fe790d; border: 1px solid #fff; cursor: nwse-resize; }
    .drag-field:hover .field-remove, .drag-field.active .field-remove { display: inline-flex; }
    .drag-field:hover .field-resize, .drag-field.active .field-resize { display: block; }
    .coordinate-pill { min-width: 68px; }
    @media (max-width: 1199.98px) { .cv-layout-shell { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Drag Check Voucher Fields</h1>
            <p class="text-muted mb-0">Adjust printable field positions for check voucher printing.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.payables.check-voucher.layout') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            <button type="submit" form="layoutForm" class="btn btn-success"><i class="bi bi-save me-1"></i>Save Layout</button>
            <a href="{{ route('erp.payables.check-voucher') }}" class="btn btn-primary"><i class="bi bi-eye me-1"></i>Preview Voucher</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('erp.payables.check-voucher.layout.save') }}" id="layoutForm" enctype="multipart/form-data">
        {{ csrf_field() }}
        <input type="hidden" name="paper_size" id="paper_size_hidden" value="{{ $settings['paper_size'] }}">
        <input type="hidden" name="paper_width" id="paper_width_hidden" value="{{ $settings['paper_width'] }}">
        <input type="hidden" name="paper_height" id="paper_height_hidden" value="{{ $settings['paper_height'] }}">
        <input type="hidden" name="template_url" id="template_url_hidden" value="{{ $settings['template_url'] }}">
        <input type="hidden" name="print_template" id="print_template_hidden" value="{{ $settings['print_template'] ? '1' : '0' }}">
        <input type="hidden" name="custom_fields_present" value="1">
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

        <div class="cv-layout-shell">
            <div class="cv-layout-wrap">
                <div class="cv-layout-sheet {{ $settings['print_template'] ? '' : 'cv-template-muted' }}" id="layoutSheet">
                    <div class="cv-guide-box" style="left: 46px; top: 190px; width: 292px; height: 126px;"></div>
                    <div class="cv-guide-line" style="left: 46px; top: 236px; width: 292px;"></div>
                    <div class="cv-guide-muted" style="left: 640px; top: 218px; width: 174px;"></div>
                    <div class="cv-guide-muted" style="left: 640px; top: 258px; width: 174px;"></div>
                    <div class="cv-guide-muted" style="left: 640px; top: 298px; width: 174px;"></div>
                    <div class="cv-guide-muted" style="left: 384px; top: 502px; width: 288px;"></div>
                    <div class="cv-guide-box" style="left: 46px; top: 522px; width: 748px; height: 104px;"></div>
                    <div class="cv-guide-box" style="left: 48px; top: 650px; width: 744px; height: 176px;"></div>
                    <div class="cv-guide-line" style="left: 48px; top: 678px; width: 744px;"></div>
                    <div class="cv-guide-line" style="left: 48px; top: 790px; width: 744px;"></div>
                    <div class="cv-guide-vline" style="left: 504px; top: 650px; height: 176px;"></div>
                    <div class="cv-guide-vline" style="left: 650px; top: 650px; height: 176px;"></div>
                    <div class="cv-guide-box" style="left: 48px; top: 840px; width: 744px; height: 150px;"></div>
                    <div class="cv-guide-vline" style="left: 214px; top: 840px; height: 150px;"></div>
                    <div class="cv-guide-vline" style="left: 386px; top: 840px; height: 150px;"></div>
                    <div class="cv-guide-vline" style="left: 592px; top: 840px; height: 150px;"></div>
                    <div class="cv-guide-line" style="left: 48px; top: 876px; width: 744px;"></div>
                    <div class="cv-guide-line" style="left: 48px; top: 962px; width: 744px;"></div>

                    @foreach($positions as $field => $point)
                        @php($classes = (in_array($field, ['company_name', 'form_title']) ? ' header-text' : '').($field === 'logo_mark' ? ' logo-mark' : '').(in_array($field, ['amount', 'debit_amount', 'credit_amount', 'total_debit', 'total_credit']) ? ' amount' : '').(strpos($field, 'label') !== false || strpos($field, 'header') !== false ? ' label' : ''))
                        <div class="drag-field{{ $classes }}" data-kind="standard" data-field="{{ $field }}" style="left: {{ $point[0] }}px; top: {{ $point[1] }}px; width: {{ $fieldStyles[$field]['width'] }}px; font-size: {{ $fieldStyles[$field]['font_size'] }}px;">
                            <button type="button" class="field-remove" title="Hide"><i class="bi bi-x"></i></button>
                            <span class="field-resize" title="Resize"></span>
                            @if($field === 'logo_mark')
                                <img src="{{ asset('images/gen_off_seajetlogo.webp') }}" alt="SeaJet logo">
                            @else
                                {{ $sampleValues[$field] ?? $fields[$field] }}
                            @endif
                        </div>
                    @endforeach

                    @foreach($customFields as $custom)
                        <div class="drag-field custom-field" data-kind="custom" data-field="{{ $custom['key'] }}" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px;">
                            <button type="button" class="field-remove" title="Remove"><i class="bi bi-x"></i></button>
                            <span class="field-resize" title="Resize"></span>
                            {{ $custom['text'] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="erp-card p-3">
                <h2 class="h6 fw-bold mb-3">Layout Controls</h2>
                <div class="mb-3">
                    <label class="form-label small text-muted">Paper Size</label>
                    <select class="form-select form-select-sm" id="paperSizeControl">
                        <option value="letter" data-width="816" data-height="1056" {{ $settings['paper_size'] === 'letter' ? 'selected' : '' }}>Letter</option>
                        <option value="a4" data-width="794" data-height="1123" {{ $settings['paper_size'] === 'a4' ? 'selected' : '' }}>A4</option>
                        <option value="custom" data-width="{{ $settings['paper_width'] }}" data-height="{{ $settings['paper_height'] }}" {{ $settings['paper_size'] === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-muted">Width</label>
                        <input type="number" class="form-control form-control-sm" id="paperWidthControl" value="{{ $settings['paper_width'] }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Height</label>
                        <input type="number" class="form-control form-control-sm" id="paperHeightControl" value="{{ $settings['paper_height'] }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Template URL</label>
                    <input type="text" class="form-control form-control-sm" id="templateUrlControl" value="{{ $settings['template_url'] }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Template File</label>
                    <input type="file" class="form-control form-control-sm" name="template_file" id="templateFileControl" accept="image/*">
                </div>
                <label class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="printTemplateControl" {{ $settings['print_template'] ? 'checked' : '' }}>
                    <span class="form-check-label">Print template background</span>
                </label>

                <hr>
                <h3 class="h6 fw-bold mb-2">Selected Field</h3>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-muted">X</label>
                        <input type="number" class="form-control form-control-sm" id="fieldXControl">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Y</label>
                        <input type="number" class="form-control form-control-sm" id="fieldYControl">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Width</label>
                        <input type="number" class="form-control form-control-sm" id="fieldWidthControl">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Font</label>
                        <input type="number" class="form-control form-control-sm" id="fieldFontControl">
                    </div>
                </div>

                <hr>
                <h3 class="h6 fw-bold mb-2">Add Text</h3>
                <input type="text" class="form-control form-control-sm mb-2" id="customLabelControl" placeholder="Label">
                <textarea class="form-control form-control-sm mb-2" id="customTextControl" rows="2" placeholder="Text to print"></textarea>
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="addCustomField"><i class="bi bi-plus-circle me-1"></i>Add Custom Text</button>

                <hr>
                <h3 class="h6 fw-bold mb-2">Fields</h3>
                <div class="list-group list-group-flush" style="max-height: 260px; overflow: auto;">
                    @foreach($fields as $field => $label)
                        <button type="button" class="list-group-item list-group-item-action px-0 field-focus" data-field="{{ $field }}">
                            <span class="fw-semibold">{{ $label }}</span>
                            <span class="badge badge-soft coordinate-pill float-end" id="pos_{{ $field }}">{{ $positions[$field][0] }}, {{ $positions[$field][1] }}</span>
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
        var selectedField = '';
        var uploadedTemplatePreviewUrl = '';
        var customCounter = {{ count($customFields) + 1 }};

        function selectedElement() {
            return selectedField ? $('[data-field="' + selectedField + '"].drag-field')[0] : null;
        }

        function syncControls(el) {
            if (!el) return;
            $('#fieldXControl').val(parseInt(el.style.left, 10) || 0);
            $('#fieldYControl').val(parseInt(el.style.top, 10) || 0);
            $('#fieldWidthControl').val(parseInt(el.style.width, 10) || 120);
            $('#fieldFontControl').val(parseInt(el.style.fontSize, 10) || 11);
        }

        function setActive(field) {
            selectedField = field;
            $('.drag-field').removeClass('active');
            var el = selectedElement();
            if (el) {
                $(el).addClass('active');
                syncControls(el);
            }
        }

        function updatePosition(el, x, y) {
            x = Math.max(0, parseInt(x, 10) || 0);
            y = Math.max(0, parseInt(y, 10) || 0);
            el.style.left = x + 'px';
            el.style.top = y + 'px';
            var field = $(el).data('field');
            if ($(el).data('kind') === 'custom') {
                $('#custom_x_' + field).val(x);
                $('#custom_y_' + field).val(y);
            } else {
                $('#x_' + field).val(x);
                $('#y_' + field).val(y);
                $('#pos_' + field).text(x + ', ' + y);
            }
            syncControls(el);
        }

        function updateSize(el, width, fontSize) {
            width = Math.max(30, parseInt(width, 10) || 120);
            fontSize = Math.max(7, parseInt(fontSize, 10) || 11);
            el.style.width = width + 'px';
            el.style.fontSize = fontSize + 'px';
            var field = $(el).data('field');
            if ($(el).data('kind') === 'custom') {
                $('#custom_w_' + field).val(width);
                $('#custom_fs_' + field).val(fontSize);
            } else {
                $('#w_' + field).val(width);
                $('#fs_' + field).val(fontSize);
            }
            syncControls(el);
        }

        function bindDrag(el) {
            var drag = null;
            el.addEventListener('pointerdown', function (event) {
                if ($(event.target).closest('.field-remove, .field-resize').length) return;
                event.preventDefault();
                setActive($(el).data('field'));
                drag = {
                    pointerId: event.pointerId,
                    startX: event.clientX,
                    startY: event.clientY,
                    left: parseInt(el.style.left, 10) || 0,
                    top: parseInt(el.style.top, 10) || 0
                };
                el.setPointerCapture(event.pointerId);
            });
            el.addEventListener('pointermove', function (event) {
                if (!drag || drag.pointerId !== event.pointerId) return;
                updatePosition(el, drag.left + event.clientX - drag.startX, drag.top + event.clientY - drag.startY);
            });
            el.addEventListener('pointerup', function (event) {
                if (drag && drag.pointerId === event.pointerId) drag = null;
            });
        }

        function bindResize(handle) {
            var resize = null;
            handle.addEventListener('pointerdown', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var el = $(handle).closest('.drag-field')[0];
                setActive($(el).data('field'));
                resize = { pointerId: event.pointerId, startX: event.clientX, width: parseInt(el.style.width, 10) || 120 };
                handle.setPointerCapture(event.pointerId);
            });
            handle.addEventListener('pointermove', function (event) {
                if (!resize || resize.pointerId !== event.pointerId) return;
                updateSize(selectedElement(), resize.width + event.clientX - resize.startX, $('#fieldFontControl').val());
            });
            handle.addEventListener('pointerup', function (event) {
                if (resize && resize.pointerId === event.pointerId) resize = null;
            });
        }

        function applyPaperAndTemplate() {
            var selected = $('#paperSizeControl option:selected');
            if ($('#paperSizeControl').val() !== 'custom') {
                $('#paperWidthControl').val(selected.data('width'));
                $('#paperHeightControl').val(selected.data('height'));
            }
            $('#paper_size_hidden').val($('#paperSizeControl').val());
            $('#paper_width_hidden').val($('#paperWidthControl').val());
            $('#paper_height_hidden').val($('#paperHeightControl').val());
            $('#template_url_hidden').val($('#templateUrlControl').val());
            $('#print_template_hidden').val($('#printTemplateControl').is(':checked') ? '1' : '0');
            $('#layoutSheet').css({
                width: $('#paperWidthControl').val() + 'px',
                height: $('#paperHeightControl').val() + 'px',
                backgroundImage: $('#templateUrlControl').val() ? 'url("' + $('#templateUrlControl').val() + '")' : 'none'
            }).toggleClass('cv-template-muted', !$('#printTemplateControl').is(':checked'));
        }

        $('.drag-field').each(function () { bindDrag(this); });
        $('.field-resize').each(function () { bindResize(this); });
        $('.field-focus').on('click', function () { setActive($(this).data('field')); });
        $('#fieldXControl, #fieldYControl').on('input', function () {
            var el = selectedElement();
            if (el) updatePosition(el, $('#fieldXControl').val(), $('#fieldYControl').val());
        });
        $('#fieldWidthControl, #fieldFontControl').on('input', function () {
            var el = selectedElement();
            if (el) updateSize(el, $('#fieldWidthControl').val(), $('#fieldFontControl').val());
        });
        $('#paperSizeControl, #paperWidthControl, #paperHeightControl, #templateUrlControl, #printTemplateControl').on('input change', applyPaperAndTemplate);
        $('#templateFileControl').on('change', function () {
            var file = this.files && this.files[0] ? this.files[0] : null;
            if (uploadedTemplatePreviewUrl) URL.revokeObjectURL(uploadedTemplatePreviewUrl);
            if (file) {
                uploadedTemplatePreviewUrl = URL.createObjectURL(file);
                $('#templateUrlControl').val(uploadedTemplatePreviewUrl);
                $('#printTemplateControl').prop('checked', true);
            }
            applyPaperAndTemplate();
        });

        $('#addCustomField').on('click', function () {
            var key = 'custom_' + Date.now() + '_' + customCounter++;
            var label = $('#customLabelControl').val() || 'Custom Text';
            var text = $('#customTextControl').val() || 'Custom Text';
            var x = 60;
            var y = 620;
            var width = 180;
            var font = 11;
            $('#customHiddenInputs').append(
                '<input type="hidden" name="custom_key[]" id="custom_key_' + key + '" value="' + key + '">' +
                '<input type="hidden" name="custom_label[]" id="custom_label_' + key + '">' +
                '<input type="hidden" name="custom_text[]" id="custom_text_' + key + '">' +
                '<input type="hidden" name="custom_x[]" id="custom_x_' + key + '" value="' + x + '">' +
                '<input type="hidden" name="custom_y[]" id="custom_y_' + key + '" value="' + y + '">' +
                '<input type="hidden" name="custom_w[]" id="custom_w_' + key + '" value="' + width + '">' +
                '<input type="hidden" name="custom_fs[]" id="custom_fs_' + key + '" value="' + font + '">'
            );
            $('#custom_label_' + key).val(label);
            $('#custom_text_' + key).val(text);
            var field = $('<div class="drag-field custom-field" data-kind="custom"></div>');
            field.attr('data-field', key).css({ left: x + 'px', top: y + 'px', width: width + 'px', fontSize: font + 'px' });
            field.append('<button type="button" class="field-remove" title="Remove"><i class="bi bi-x"></i></button><span class="field-resize" title="Resize"></span>');
            field.append(document.createTextNode(text));
            $('#layoutSheet').append(field);
            bindDrag(field[0]);
            bindResize(field.find('.field-resize')[0]);
            setActive(key);
        });

        $(document).on('click', '.field-remove', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var el = $(this).closest('.drag-field');
            var field = el.data('field');
            if (el.data('kind') === 'custom') {
                $('#custom_key_' + field + ', #custom_label_' + field + ', #custom_text_' + field + ', #custom_x_' + field + ', #custom_y_' + field + ', #custom_w_' + field + ', #custom_fs_' + field).remove();
                el.remove();
            } else {
                el.hide();
                $('#x_' + field).val(-9999);
                $('#y_' + field).val(-9999);
            }
        });

        setActive('title');
        applyPaperAndTemplate();
    });
</script>
@endpush
