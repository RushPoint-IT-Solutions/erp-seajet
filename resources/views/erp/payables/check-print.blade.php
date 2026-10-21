@extends('erp.layout')

@section('title', 'Print Check')
@section('page-title', 'Check Print Alignment')

@php
    $checkDate = preg_replace('/\D/', '', (string) $voucher['document_date']);
    $checkDate = str_pad(substr($checkDate, 0, 8), 8, ' ', STR_PAD_RIGHT);
    $dateChars = [
        'date_m1' => substr($checkDate, 0, 1),
        'date_m2' => substr($checkDate, 1, 1),
        'date_d1' => substr($checkDate, 2, 1),
        'date_d2' => substr($checkDate, 3, 1),
        'date_y1' => substr($checkDate, 4, 1),
        'date_y2' => substr($checkDate, 5, 1),
        'date_y3' => substr($checkDate, 6, 1),
        'date_y4' => substr($checkDate, 7, 1),
    ];
    $paperWidth = (float) $checkProfile['paper_width_mm'];
    $paperHeight = (float) $checkProfile['paper_height_mm'];
    $offsetX = (float) $checkProfile['offset_x_mm'];
    $offsetY = (float) $checkProfile['offset_y_mm'];
    $printPageWidth = max(210, $paperWidth);
@endphp

@push('styles')
<style>
    .check-editor-toolbar {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
    }
    .check-editor-toolbar label {
        align-items: center;
        display: inline-flex;
        gap: 6px;
        margin: 0;
    }
    .check-preview-scroll {
        overflow-x: auto;
        padding: 18px;
    }
    .check-sheet {
        background: #fff;
        box-shadow: 0 4px 18px rgba(15, 23, 42, .14);
        height: {{ $paperHeight }}mm;
        margin: 0 auto;
        min-width: {{ $paperWidth }}mm;
        overflow: visible;
        position: relative;
        width: {{ $paperWidth }}mm;
    }
    .check-sheet.show-guide::before {
        background-image:
            linear-gradient(to right, rgba(47, 111, 178, .13) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(47, 111, 178, .13) 1px, transparent 1px);
        background-size: 5mm 5mm;
        border: 1px solid rgba(47, 111, 178, .45);
        content: '';
        inset: 0;
        pointer-events: none;
        position: absolute;
        z-index: 1;
    }
    .check-sheet.show-guide::after {
        background: rgba(255, 255, 255, .9);
        color: #526274;
        content: 'Screen alignment grid: 5 mm';
        font: 10px Arial, sans-serif;
        left: 3mm;
        padding: 2px 4px;
        position: absolute;
        top: 2mm;
        z-index: 1;
    }
    .check-field {
        color: #000;
        font-family: Calibri, Arial, Helvetica, sans-serif;
        font-size: 12pt;
        font-weight: 700;
        line-height: 1.15;
        position: absolute;
        touch-action: none;
        white-space: nowrap;
        z-index: 2;
    }
    .check-field[data-font-key="amount_words"] {
        letter-spacing: 1.5px;
        max-width: 170mm;
        white-space: normal;
    }
    .check-field[data-font-key="payee"] {
        letter-spacing: 1.5px;
    }
    body:not(.check-fields-locked) .check-field {
        cursor: move;
        outline: 1px dashed transparent;
    }
    body:not(.check-fields-locked) .check-field:hover,
    body:not(.check-fields-locked) .check-field:focus,
    body:not(.check-fields-locked) .check-field.is-selected {
        background: rgba(47, 111, 178, .09);
        outline: 1px solid #2f6fb2;
    }
    .check-field.is-dragging {
        background: rgba(47, 111, 178, .18) !important;
        outline: 1px solid #2f6fb2 !important;
    }
    #checkDragReadout {
        background: #172033;
        border-radius: 3px;
        color: #fff;
        display: none;
        font: 11px Consolas, monospace;
        padding: 4px 7px;
        pointer-events: none;
        position: fixed;
        z-index: 1090;
    }
    .position-output {
        background: #172033;
        border: 0;
        color: #dce7f5;
        font: 11px Consolas, monospace;
        min-height: 120px;
        resize: vertical;
    }
    /* Keep the existing A4 feed, expanding it for wider bank profiles. */
    @page {
        margin: 0;
        size: {{ $printPageWidth }}mm 297mm;
    }
</style>
<style id="checkPrintPageSize"></style>
<style>
    @media print {
        html,
        body {
            background: #fff !important;
            height: var(--check-paper-height, {{ $paperHeight }}mm);
            margin: 0 !important;
            padding: 0 !important;
            width: var(--check-paper-width, {{ $paperWidth }}mm);
        }
        .erp-main > section {
            margin: 0 !important;
            padding: 0 !important;
        }
        .check-print-page,
        .check-preview-scroll {
            margin: 0 !important;
            overflow: visible !important;
            padding: 0 !important;
        }
        .check-sheet {
            box-shadow: none !important;
            margin: 0 !important;
        }
        .check-sheet::before,
        .check-sheet::after {
            display: none !important;
        }
        .check-field,
        .check-field:hover,
        .check-field:focus,
        .check-field.is-selected,
        .check-field.is-dragging {
            background: transparent !important;
            outline: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="check-print-page">
    <div class="erp-card p-3 mb-3 no-print">
        <div class="check-editor-toolbar">
            <div class="me-auto">
                <div class="fw-semibold">Check No. {{ $voucher['check_no'] }}</div>
                <div class="small text-muted">
                    {{ ucfirst($checkIssuance->status) }} · Print count {{ $checkIssuance->print_count }} ·
                    Drag a value, or focus it and use arrow keys. Shift + arrow moves 1 mm.
                </div>
            </div>
            @if(count($checkProfiles))
                <label class="small">
                    Profile
                    <select class="form-select form-select-sm" onchange="window.location=this.value">
                        @foreach($checkProfiles as $savedProfile)
                            <option value="{{ route('erp.payables.check-print', ['payment' => $voucher['id'], 'profile' => $savedProfile['id']]) }}" {{ $checkProfile['id'] === $savedProfile['id'] ? 'selected' : '' }}>
                                {{ $savedProfile['profile_name'] }} v{{ $savedProfile['version'] }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="small">
                <input type="checkbox" id="toggleCheckGuide" checked>
                5 mm grid
            </label>
            <label class="small">
                <input type="checkbox" id="toggleCheckLock">
                Lock positions
            </label>
            <label class="small">
                <span id="selectedCheckField">Payee</span> font
                <input type="number" id="checkFontSize" class="form-control form-control-sm" min="8" max="24" step="1" style="width:68px">
                pt
            </label>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetCheckLayout">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Agenture Defaults
            </button>
            @if($canSaveLayout)
                <button type="button" class="btn btn-outline-primary btn-sm" id="saveCheckLayout">
                    <i class="bi bi-floppy me-1"></i> Save Layout
                </button>
            @endif
            <button type="button" class="btn btn-primary btn-sm" id="printCheckButton" {{ $canPrintCheck ? '' : 'disabled' }}>
                <i class="bi bi-printer me-1"></i> Print Check
            </button>
            <a href="{{ route('erp.payables.check-register') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-journal-check me-1"></i> Check Register
            </a>
            @if(!empty($voucher['id']))
                <a href="{{ route('erp.payables.check-voucher', $voucher['id']) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-receipt me-1"></i> Voucher
                </a>
            @endif
        </div>
        <div id="checkSaveStatus" class="small text-muted mt-2" role="status">
            @if(!$canPrintCheck)
                Only Finance or an Administrator may initiate check printing.
            @elseif((int) $checkIssuance->print_count > 0)
                This is a reprint. A reason will be required and recorded before the print dialog opens.
            @else
                The first print request will be recorded before the print dialog opens.
            @endif
        </div>
        @if($canPrintCheck && in_array($checkIssuance->status, ['assigned', 'printed'], true))
            <form method="post" action="{{ route('erp.payables.check-print.payee.update', $voucher['id']) }}" class="row g-2 align-items-end mt-2">
                {{ csrf_field() }}
                <div class="col-md-6">
                    <label for="checkPayeeName" class="form-label small mb-1">Payee Name</label>
                    <input type="text" name="payee_name" id="checkPayeeName" class="form-control form-control-sm" value="{{ old('payee_name', $voucher['supplier']) }}" maxlength="255" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-floppy me-1"></i> Save Payee</button>
                </div>
            </form>
        @endif
    </div>

    @if($canSaveLayout)
        <details class="erp-card p-3 mb-3 no-print">
            <summary class="fw-semibold text-primary" style="cursor:pointer">Bank and printer profile settings</summary>
            <div class="row g-2 mt-2">
                <div class="col-md-3"><label class="form-label small">Profile name</label><input id="profileName" class="form-control form-control-sm" value="{{ $checkProfile['profile_name'] }}"></div>
                <div class="col-md-3"><label class="form-label small">Printer name</label><input id="printerName" class="form-control form-control-sm" value="{{ $checkProfile['printer_name'] }}"></div>
                <div class="col-md-2"><label class="form-label small">Width (mm)</label><input id="paperWidth" type="number" min="150" max="260" step="0.1" class="form-control form-control-sm" value="{{ $paperWidth }}"></div>
                <div class="col-md-2"><label class="form-label small">Height (mm)</label><input id="paperHeight" type="number" min="50" max="150" step="0.1" class="form-control form-control-sm" value="{{ $paperHeight }}"></div>
                <div class="col-md-2"><label class="form-label small">Feed</label><select id="feedOrientation" class="form-select form-select-sm"><option value="top" {{ $checkProfile['feed_orientation'] === 'top' ? 'selected' : '' }}>Top</option><option value="left" {{ $checkProfile['feed_orientation'] === 'left' ? 'selected' : '' }}>Left</option><option value="right" {{ $checkProfile['feed_orientation'] === 'right' ? 'selected' : '' }}>Right</option></select></div>
                <div class="col-md-2"><label class="form-label small">Global X offset</label><input id="offsetX" type="number" min="-50" max="50" step="0.1" class="form-control form-control-sm" value="{{ $offsetX }}"></div>
                <div class="col-md-2"><label class="form-label small">Global Y offset</label><input id="offsetY" type="number" min="-50" max="50" step="0.1" class="form-control form-control-sm" value="{{ $offsetY }}"></div>
                <div class="col-md-8 d-flex align-items-end"><div class="small text-muted">Saving creates a new version; prior versions remain in the audit history. Global offsets move every field together.</div></div>
            </div>
        </details>
    @endif

    <details class="erp-card p-3 mb-3 no-print">
        <summary class="fw-semibold text-primary" style="cursor:pointer">Current field coordinates</summary>
        <div class="small text-muted my-2">Coordinates are millimetres from the check's top-left corner. Fine nudge: 0.2 mm; Shift + arrow: 1 mm.</div>
        <textarea id="checkPositionOutput" class="form-control position-output" readonly></textarea>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="copyCheckPositions">Copy coordinates</button>
    </details>

    <div class="check-preview-scroll">
        <main class="check-sheet show-guide" id="checkSheet" aria-label="Movable check print fields">
            @foreach($dateChars as $key => $char)
                <div class="check-field" tabindex="0" data-font-key="{{ $key }}" style="top:{{ $checkLayout[$key]['top'] + $offsetY }}mm;left:{{ $checkLayout[$key]['left'] + $offsetX }}mm;font-size:{{ $checkLayout['font_sizes'][$key] }}pt">{{ $char }}</div>
            @endforeach
            <div class="check-field" tabindex="0" data-font-key="payee" style="top:{{ $checkLayout['payee']['top'] + $offsetY }}mm;left:{{ $checkLayout['payee']['left'] + $offsetX }}mm;font-size:{{ $checkLayout['font_sizes']['payee'] }}pt">***{{ $voucher['supplier'] }}***</div>
            <div class="check-field" tabindex="0" data-font-key="amount_words" style="top:{{ $checkLayout['amount_words']['top'] + $offsetY }}mm;left:{{ $checkLayout['amount_words']['left'] + $offsetX }}mm;font-size:{{ $checkLayout['font_sizes']['amount_words'] }}pt">***{{ $voucher['amount_words'] }}***</div>
            <div class="check-field" tabindex="0" data-font-key="amount_figures" style="top:{{ $checkLayout['amount_figures']['top'] + $offsetY }}mm;left:{{ $checkLayout['amount_figures']['left'] + $offsetX }}mm;font-size:{{ $checkLayout['font_sizes']['amount_figures'] }}pt">{{ number_format($voucher['net'], 2) }}</div>
        </main>
    </div>
</div>
<div id="checkDragReadout" class="no-print"></div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var layout = @json($checkLayout);
    var defaults = @json($checkLayoutDefaults);
    var profile = @json($checkProfile);
    var printCount = {{ (int) $checkIssuance->print_count }};
    var sheet = document.getElementById('checkSheet');
    var fields = Array.prototype.slice.call(document.querySelectorAll('.check-field[data-font-key]'));
    var readout = document.getElementById('checkDragReadout');
    var output = document.getElementById('checkPositionOutput');
    var fontInput = document.getElementById('checkFontSize');
    var selectedLabel = document.getElementById('selectedCheckField');
    var status = document.getElementById('checkSaveStatus');
    var selectedField = null;
    var dragging = null;
    var payeeInput = document.getElementById('checkPayeeName');
    var payeeField = document.querySelector('.check-field[data-font-key="payee"]');

    if (payeeInput && payeeField) {
        payeeInput.addEventListener('input', function () {
            payeeField.textContent = '***' + this.value + '***';
        });
    }

    var labels = {
        date_m1: 'Month digit 1', date_m2: 'Month digit 2',
        date_d1: 'Day digit 1', date_d2: 'Day digit 2',
        date_y1: 'Year digit 1', date_y2: 'Year digit 2',
        date_y3: 'Year digit 3', date_y4: 'Year digit 4',
        payee: 'Payee', amount_words: 'Amount in words', amount_figures: 'Amount figure'
    };

    function rounded(value) {
        return Math.round(value * 10) / 10;
    }

    function render() {
        fields.forEach(function (field) {
            var key = field.dataset.fontKey;
            field.style.top = (layout[key].top + parseFloat(profile.offset_y_mm || 0)) + 'mm';
            field.style.left = (layout[key].left + parseFloat(profile.offset_x_mm || 0)) + 'mm';
            field.style.fontSize = layout.font_sizes[key] + 'pt';
        });
        sheet.style.width = profile.paper_width_mm + 'mm';
        sheet.style.minWidth = profile.paper_width_mm + 'mm';
        sheet.style.height = profile.paper_height_mm + 'mm';
        document.documentElement.style.setProperty('--check-paper-width', profile.paper_width_mm + 'mm');
        document.documentElement.style.setProperty('--check-paper-height', profile.paper_height_mm + 'mm');
        document.getElementById('checkPrintPageSize').textContent =
            '@page { margin: 0; size: ' + Math.max(210, parseFloat(profile.paper_width_mm)) + 'mm 297mm; }';
        output.value = JSON.stringify(layout, null, 2);
    }

    function selectField(field) {
        fields.forEach(function (item) { item.classList.remove('is-selected'); });
        selectedField = field;
        selectedField.classList.add('is-selected');
        selectedLabel.textContent = labels[field.dataset.fontKey] || 'Selected field';
        fontInput.value = layout.font_sizes[field.dataset.fontKey];
    }

    function mmScale() {
        var rect = sheet.getBoundingClientRect();
        return { x: parseFloat(profile.paper_width_mm) / rect.width, y: parseFloat(profile.paper_height_mm) / rect.height };
    }

    function beginDrag(event, field) {
        selectField(field);
        if (document.body.classList.contains('check-fields-locked')) {
            return;
        }
        event.preventDefault();
        field.focus();
        field.setPointerCapture(event.pointerId);
        var key = field.dataset.fontKey;
        dragging = {
            field: field,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            startTop: layout[key].top,
            startLeft: layout[key].left,
            scale: mmScale()
        };
        field.classList.add('is-dragging');
    }

    function moveDrag(event) {
        if (!dragging || event.pointerId !== dragging.pointerId) {
            return;
        }
        var key = dragging.field.dataset.fontKey;
        layout[key].top = rounded(dragging.startTop + ((event.clientY - dragging.startY) * dragging.scale.y));
        layout[key].left = rounded(dragging.startLeft + ((event.clientX - dragging.startX) * dragging.scale.x));
        render();
        readout.style.display = 'block';
        readout.style.left = (event.clientX + 14) + 'px';
        readout.style.top = (event.clientY + 14) + 'px';
        readout.textContent = 'top ' + layout[key].top.toFixed(1) + ' mm, left ' + layout[key].left.toFixed(1) + ' mm';
    }

    function endDrag(event) {
        if (!dragging || event.pointerId !== dragging.pointerId) {
            return;
        }
        dragging.field.classList.remove('is-dragging');
        dragging = null;
        readout.style.display = 'none';
    }

    fields.forEach(function (field) {
        field.addEventListener('focus', function () { selectField(field); });
        field.addEventListener('pointerdown', function (event) { beginDrag(event, field); });
        field.addEventListener('pointermove', moveDrag);
        field.addEventListener('pointerup', endDrag);
        field.addEventListener('pointercancel', endDrag);
        field.addEventListener('keydown', function (event) {
            if (document.body.classList.contains('check-fields-locked')) {
                return;
            }
            var key = field.dataset.fontKey;
            var step = event.shiftKey ? 1 : 0.2;
            if (event.key === 'ArrowUp') layout[key].top = rounded(layout[key].top - step);
            else if (event.key === 'ArrowDown') layout[key].top = rounded(layout[key].top + step);
            else if (event.key === 'ArrowLeft') layout[key].left = rounded(layout[key].left - step);
            else if (event.key === 'ArrowRight') layout[key].left = rounded(layout[key].left + step);
            else return;
            event.preventDefault();
            render();
        });
    });

    document.getElementById('toggleCheckGuide').addEventListener('change', function () {
        sheet.classList.toggle('show-guide', this.checked);
    });

    document.getElementById('toggleCheckLock').addEventListener('change', function () {
        document.body.classList.toggle('check-fields-locked', this.checked);
    });

    fontInput.addEventListener('input', function () {
        var size = parseFloat(this.value);
        if (!selectedField || isNaN(size) || size < 8 || size > 24) {
            return;
        }
        layout.font_sizes[selectedField.dataset.fontKey] = rounded(size);
        render();
    });

    document.getElementById('resetCheckLayout').addEventListener('click', function () {
        layout = JSON.parse(JSON.stringify(defaults));
        render();
        selectField(document.querySelector('.check-field[data-font-key="payee"]'));
        status.textContent = 'Agenture default positions restored in the preview. Save the layout to keep them.';
    });

    document.getElementById('copyCheckPositions').addEventListener('click', function () {
        var button = this;
        var done = function () {
            var original = button.textContent;
            button.textContent = 'Copied';
            window.setTimeout(function () { button.textContent = original; }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(output.value).then(done);
            return;
        }
        output.select();
        document.execCommand('copy');
        done();
    });

    var saveButton = document.getElementById('saveCheckLayout');
    if (saveButton) {
        ['offsetX', 'offsetY', 'paperWidth', 'paperHeight'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', function () {
                profile.offset_x_mm = parseFloat(document.getElementById('offsetX').value || 0);
                profile.offset_y_mm = parseFloat(document.getElementById('offsetY').value || 0);
                profile.paper_width_mm = parseFloat(document.getElementById('paperWidth').value || 210);
                profile.paper_height_mm = parseFloat(document.getElementById('paperHeight').value || 88.9);
                render();
            });
        });

        saveButton.addEventListener('click', function () {
            saveButton.disabled = true;
            status.textContent = 'Saving layout...';
            fetch('{{ route('erp.payables.check-print.layout.save') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    layout: layout,
                    bank_account_id: {{ (int) $checkProfile['bank_account_id'] }},
                    profile_name: document.getElementById('profileName').value,
                    printer_name: document.getElementById('printerName').value,
                    paper_width_mm: document.getElementById('paperWidth').value,
                    paper_height_mm: document.getElementById('paperHeight').value,
                    feed_orientation: document.getElementById('feedOrientation').value,
                    offset_x_mm: document.getElementById('offsetX').value,
                    offset_y_mm: document.getElementById('offsetY').value
                })
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () { return {}; }).then(function (data) {
                        throw new Error(data.message || 'Save failed (' + response.status + ')');
                    });
                }
                return response.json();
            }).then(function (data) {
                layout = data.layout;
                profile = data.profile;
                render();
                status.textContent = data.status + ' Profile v' + data.profile.version + '.';
            }).catch(function (error) {
                status.textContent = error.message;
            }).then(function () {
                saveButton.disabled = false;
            });
        });
    }

    var printButton = document.getElementById('printCheckButton');
    if (printButton && !printButton.disabled) {
        printButton.addEventListener('click', function () {
            var reason = '';
            if (printCount > 0) {
                reason = window.prompt('This is a controlled reprint. Enter the reason:') || '';
                if (reason.trim().length < 5) {
                    status.textContent = 'Reprint cancelled: enter a reason of at least five characters.';
                    return;
                }
            }

            printButton.disabled = true;
            status.textContent = 'Recording print request...';
            fetch('{{ route('erp.payables.check-print.event', $voucher['id']) }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ reason: reason, profile_id: profile.id })
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () { return {}; }).then(function (data) {
                        throw new Error(data.message || 'Print authorization failed (' + response.status + ')');
                    });
                }
                return response.json();
            }).then(function (data) {
                printCount = data.event.print_count;
                status.textContent = data.status;
                window.print();
            }).catch(function (error) {
                status.textContent = error.message;
            }).then(function () {
                printButton.disabled = false;
            });
        });
    }

    selectField(document.querySelector('.check-field[data-font-key="payee"]'));
    render();
}());
</script>
@endpush
