@extends('erp.layout')

@section('title', 'Print BIR Form 2307')
@section('page-title', 'BIR Form 2307 — SI 2088')

@php
    $amount = function ($value) {
        return abs((float) $value) < 0.005 ? '' : number_format((float) $value, 2);
    };
    $fieldStyle = function ($key) use ($printLayout) {
        $position = $printLayout[$key];
        return 'top:'.$position['top'].'px;left:'.$position['left'].'px;width:'.$position['width'].'px;font-size:'.$position['font_size'].'px';
    };
    $characters = function ($value) {
        return preg_split('//u', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    };
    $dateFieldIndexes = [0, 1, 3, 4, 6, 7, 8, 9];
@endphp

@push('styles')
<style>
    .bir-matrix-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 14px;
        margin-bottom: 10px;
        border: 1px solid #dbe3ed;
        border-radius: 8px;
        background: #fff;
    }
    .bir-matrix-toolbar .bir-toolbar-actions { margin-left: auto; display: flex; gap: 8px; }
    .bir-layout-status { min-height: 20px; color: #52657a; }
    .bir-position-panel { margin-bottom: 12px; border: 1px solid #dbe3ed; border-radius: 8px; background: #fff; }
    .bir-position-panel summary { padding: 10px 14px; cursor: pointer; color: #1967a3; font-weight: 600; }
    .bir-position-table-wrap { max-height: 310px; overflow: auto; padding: 0 14px 14px; }
    .bir-position-table { min-width: 650px; margin-bottom: 0; }
    .bir-position-table input { width: 90px; }
    .bir-2307-wrap { overflow: auto; padding: 14px; background: #e9eef5; border-radius: 8px; }
    .bir-2307-page {
        position: relative;
        width: 816px;
        height: 1248px;
        margin: 0 auto 24px;
        overflow: hidden;
        background: #fff;
        color: #000;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .16);
        font-family: Arial, sans-serif;
    }
    .bir-2307-background { position: absolute; inset: 0; width: 816px; height: 1248px; object-fit: fill; z-index: 0; user-select: none; pointer-events: none; }
    .bir-field {
        position: absolute;
        z-index: 1;
        min-height: 18px;
        overflow: hidden;
        white-space: nowrap;
        line-height: 18px;
        touch-action: none;
        cursor: move;
        user-select: none;
        outline: 1px dashed transparent;
        outline-offset: 1px;
    }
    .bir-field:hover { outline-color: rgba(25, 103, 163, .55); background: rgba(219, 238, 255, .22); }
    .bir-field.is-selected { outline: 2px solid #0d6efd; background: rgba(13, 110, 253, .08); }
    .bir-field.is-dragging { opacity: .72; }
    .bir-2307-locked .bir-field { cursor: default; }
    .bir-2307-locked .bir-field:hover { outline-color: transparent; background: transparent; }
    .bir-field-center { text-align: center; }
    .bir-field-right { text-align: right; padding-right: 4px; }
    .bir-character { text-align: center; }
    .bir-payee-zip, .bir-payer-zip { letter-spacing: 6px; }
    .bir-description { height: 18px; padding-left: 3px; white-space: normal; line-height: 8px; }
    .bir-atc { text-align: center; font-weight: 600; }
    .bir-grand-total { font-weight: 700; }
    #birDragReadout {
        position: fixed;
        z-index: 1080;
        display: none;
        padding: 5px 8px;
        border-radius: 4px;
        background: #1f2937;
        color: #fff;
        font-size: 12px;
        pointer-events: none;
    }
    @media (max-width: 900px) {
        .bir-matrix-toolbar .bir-toolbar-actions { width: 100%; margin-left: 0; }
    }
    @media print {
        @page { size: letter portrait; margin: 0; }
        html, body { width: 8.5in; height: 11in; margin: 0 !important; padding: 0 !important; overflow: hidden; background: #fff !important; }
        .erp-sidebar, .erp-topbar, .no-print { display: none !important; }
        .erp-main { margin: 0 !important; }
        .erp-main > section { padding: 0 !important; }
        .bir-2307-wrap { position: relative; width: 8.5in; height: 11in; overflow: hidden; padding: 0; background: #fff; }
        .bir-2307-page {
            position: absolute;
            inset: 0 auto auto 0;
            width: 8.5in;
            height: 13in;
            margin: 0;
            box-shadow: none;
            transform: scaleY(0.8461538462);
            transform-origin: top left;
            page-break-after: avoid;
            page-break-inside: avoid;
            break-after: avoid-page;
            break-inside: avoid-page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .bir-2307-background { width: 8.5in; height: 13in; }
        .bir-field, .bir-field:hover, .bir-field.is-selected { outline: none !important; background: transparent !important; }
    }
</style>
@endpush

@section('content')
    <div class="bir-matrix-toolbar no-print">
        <div>
            <div class="fw-semibold">BIR Form 2307 — SI 2088</div>
            <div class="small text-muted">Print Matrix Preview — drag values over the actual BIR form image</div>
        </div>
        <label class="small mb-0"><input type="checkbox" id="toggleBirLock" class="me-1"> Lock positions</label>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="resetBirLayout"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
        @if($canSaveLayout)
            <button type="button" class="btn btn-outline-primary btn-sm" id="saveBirLayout"><i class="bi bi-floppy me-1"></i> Save Layout</button>
        @endif
        <div class="bir-toolbar-actions">
            <button type="button" class="btn btn-primary btn-sm" id="printBir2307"><i class="bi bi-printer me-1"></i> Print</button>
            <a href="{{ route('erp.payables.vendor-bills.view', $form['bill_no']) }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>
    <div id="birLayoutStatus" class="bir-layout-status small no-print" role="status">Select a field, then drag it or use the arrow keys to nudge it. Hold Shift for 5 px.</div>

    <details class="bir-position-panel no-print">
        <summary>Field positions (drag fields on the sheet, or edit exact values)</summary>
        <div class="bir-position-table-wrap">
            <table class="table table-sm align-middle bir-position-table">
                <thead><tr><th>Field</th><th>X / Left</th><th>Y / Top</th><th>Width</th><th>Font</th></tr></thead>
                <tbody id="birPositionRows"></tbody>
            </table>
        </div>
    </details>

    <div class="bir-2307-wrap">
        @foreach($pages as $pageRows)
            @php
                $pageMonths = [0.0, 0.0, 0.0];
                $pageTax = 0.0;
                foreach ($pageRows as $pageRow) {
                    foreach ($pageRow['months'] as $monthIndex => $monthAmount) {
                        $pageMonths[$monthIndex] += $monthAmount;
                    }
                    $pageTax += $pageRow['tax_withheld'];
                }
            @endphp
            <div class="bir-2307-page" aria-label="Movable BIR Form 2307 fields">
                <img src="{{ asset('images/bir_2307_bg.png') }}" class="bir-2307-background" alt="BIR Form 2307">

                @foreach($characters($form['period_from']) as $characterIndex => $character)
                    @if(isset($dateFieldIndexes[$characterIndex]))
                        @php($fieldIndex = $dateFieldIndexes[$characterIndex])
                        <div class="bir-field bir-character" tabindex="0" data-field-key="period_from_char_{{ $fieldIndex }}" data-field-label="Period from digit {{ $characterIndex + 1 }}" style="{{ $fieldStyle('period_from_char_'.$fieldIndex) }}">{{ $character }}</div>
                    @endif
                @endforeach
                @foreach($characters($form['period_to']) as $characterIndex => $character)
                    @if(isset($dateFieldIndexes[$characterIndex]))
                        @php($fieldIndex = $dateFieldIndexes[$characterIndex])
                        <div class="bir-field bir-character" tabindex="0" data-field-key="period_to_char_{{ $fieldIndex }}" data-field-label="Period to digit {{ $characterIndex + 1 }}" style="{{ $fieldStyle('period_to_char_'.$fieldIndex) }}">{{ $character }}</div>
                    @endif
                @endforeach
                @foreach($characters($form['payee_tin']) as $characterIndex => $character)
                    @if($characterIndex < 15)
                        <div class="bir-field bir-character" tabindex="0" data-field-key="payee_tin_char_{{ $characterIndex }}" data-field-label="Payee TIN character {{ $characterIndex + 1 }}" style="{{ $fieldStyle('payee_tin_char_'.$characterIndex) }}">@if($character === ' ')&nbsp;@else{{ $character }}@endif</div>
                    @endif
                @endforeach
                <div class="bir-field" tabindex="0" data-field-key="payee_name" data-field-label="Payee name" style="{{ $fieldStyle('payee_name') }}">{{ $form['payee_name'] }}</div>
                @foreach($characters($form['payee_address']) as $characterIndex => $character)
                    @if($characterIndex < 112)
                        <div class="bir-field bir-character" tabindex="0" data-field-key="payee_address_char_{{ $characterIndex }}" data-field-label="Payee address character {{ $characterIndex + 1 }}" style="{{ $fieldStyle('payee_address_char_'.$characterIndex) }}">@if($character === ' ')&nbsp;@else{{ $character }}@endif</div>
                    @endif
                @endforeach
                <div class="bir-field bir-field-center bir-payee-zip" tabindex="0" data-field-key="payee_zip" data-field-label="Payee ZIP" style="{{ $fieldStyle('payee_zip') }}">{{ $form['payee_zip'] }}</div>
                @foreach($characters($form['payer_tin']) as $characterIndex => $character)
                    @if($characterIndex < 17)
                        <div class="bir-field bir-character" tabindex="0" data-field-key="payer_tin_char_{{ $characterIndex }}" data-field-label="Payor TIN character {{ $characterIndex + 1 }}" style="{{ $fieldStyle('payer_tin_char_'.$characterIndex) }}">@if($character === ' ')&nbsp;@else{{ $character }}@endif</div>
                    @endif
                @endforeach
                <div class="bir-field" tabindex="0" data-field-key="payer_name" data-field-label="Payer name" style="{{ $fieldStyle('payer_name') }}">{{ $form['payer_name'] }}</div>
                @foreach($characters($form['payer_address']) as $characterIndex => $character)
                    @if($characterIndex < 112)
                        <div class="bir-field bir-character" tabindex="0" data-field-key="payer_address_char_{{ $characterIndex }}" data-field-label="Payor address character {{ $characterIndex + 1 }}" style="{{ $fieldStyle('payer_address_char_'.$characterIndex) }}">@if($character === ' ')&nbsp;@else{{ $character }}@endif</div>
                    @endif
                @endforeach
                <div class="bir-field bir-field-center bir-payer-zip" tabindex="0" data-field-key="payer_zip" data-field-label="Payer ZIP" style="{{ $fieldStyle('payer_zip') }}">{{ $form['payer_zip'] }}</div>

                @foreach($pageRows as $rowIndex => $row)
                    @php($prefix = 'detail_'.$rowIndex.'_')
                    <div class="bir-field bir-description" tabindex="0" data-field-key="{{ $prefix }}description" data-field-label="Row {{ $rowIndex + 1 }} description" style="{{ $fieldStyle($prefix.'description') }}">{{ $row['description'] }}</div>
                    <div class="bir-field bir-atc" tabindex="0" data-field-key="{{ $prefix }}atc" data-field-label="Row {{ $rowIndex + 1 }} ATC" style="{{ $fieldStyle($prefix.'atc') }}">{{ $row['atc_code'] }}</div>
                    <div class="bir-field bir-field-right" tabindex="0" data-field-key="{{ $prefix }}month_1" data-field-label="Row {{ $rowIndex + 1 }} month 1" style="{{ $fieldStyle($prefix.'month_1') }}">{{ $amount($row['months'][0]) }}</div>
                    <div class="bir-field bir-field-right" tabindex="0" data-field-key="{{ $prefix }}month_2" data-field-label="Row {{ $rowIndex + 1 }} month 2" style="{{ $fieldStyle($prefix.'month_2') }}">{{ $amount($row['months'][1]) }}</div>
                    <div class="bir-field bir-field-right" tabindex="0" data-field-key="{{ $prefix }}month_3" data-field-label="Row {{ $rowIndex + 1 }} month 3" style="{{ $fieldStyle($prefix.'month_3') }}">{{ $amount($row['months'][2]) }}</div>
                    <div class="bir-field bir-field-right" tabindex="0" data-field-key="{{ $prefix }}row_total" data-field-label="Row {{ $rowIndex + 1 }} total" style="{{ $fieldStyle($prefix.'row_total') }}">{{ $amount(array_sum($row['months'])) }}</div>
                    <div class="bir-field bir-field-right" tabindex="0" data-field-key="{{ $prefix }}row_tax" data-field-label="Row {{ $rowIndex + 1 }} tax withheld" style="{{ $fieldStyle($prefix.'row_tax') }}">{{ $amount($row['tax_withheld']) }}</div>
                @endforeach

                <div class="bir-field bir-field-right bir-grand-total" tabindex="0" data-field-key="grand_month_1" data-field-label="Grand total month 1" style="{{ $fieldStyle('grand_month_1') }}">{{ $amount($pageMonths[0]) }}</div>
                <div class="bir-field bir-field-right bir-grand-total" tabindex="0" data-field-key="grand_month_2" data-field-label="Grand total month 2" style="{{ $fieldStyle('grand_month_2') }}">{{ $amount($pageMonths[1]) }}</div>
                <div class="bir-field bir-field-right bir-grand-total" tabindex="0" data-field-key="grand_month_3" data-field-label="Grand total month 3" style="{{ $fieldStyle('grand_month_3') }}">{{ $amount($pageMonths[2]) }}</div>
                <div class="bir-field bir-field-right bir-grand-total" tabindex="0" data-field-key="grand_row_total" data-field-label="Grand payment total" style="{{ $fieldStyle('grand_row_total') }}">{{ $amount(array_sum($pageMonths)) }}</div>
                <div class="bir-field bir-field-right bir-grand-total" tabindex="0" data-field-key="grand_row_tax" data-field-label="Grand tax withheld" style="{{ $fieldStyle('grand_row_tax') }}">{{ $amount($pageTax) }}</div>

                <div class="bir-field bir-field-center" tabindex="0" data-field-key="payer_authorized_name" data-field-label="Payor authorized representative" style="{{ $fieldStyle('payer_authorized_name') }}">{{ $form['payer_authorized_name'] }}</div>
                <div class="bir-field bir-field-center" tabindex="0" data-field-key="payer_authorized_title" data-field-label="Payor representative title" style="{{ $fieldStyle('payer_authorized_title') }}">{{ $form['payer_authorized_title'] }}</div>
                <div class="bir-field bir-field-center" tabindex="0" data-field-key="payer_authorized_tin" data-field-label="Payor representative TIN" style="{{ $fieldStyle('payer_authorized_tin') }}">{{ $form['payer_authorized_tin'] ? 'TIN: '.$form['payer_authorized_tin'] : '' }}</div>
                <div class="bir-field bir-field-center" tabindex="0" data-field-key="payee_authorized_name" data-field-label="Payee authorized representative" style="{{ $fieldStyle('payee_authorized_name') }}">{{ $form['payee_authorized_name'] }}</div>
                <div class="bir-field bir-field-center" tabindex="0" data-field-key="payee_authorized_title" data-field-label="Payee representative title" style="{{ $fieldStyle('payee_authorized_title') }}">{{ $form['payee_authorized_title'] }}</div>
                <div class="bir-field bir-field-center" tabindex="0" data-field-key="payee_authorized_tin" data-field-label="Payee representative TIN" style="{{ $fieldStyle('payee_authorized_tin') }}">{{ $form['payee_authorized_tin'] ? 'TIN: '.$form['payee_authorized_tin'] : '' }}</div>
            </div>
        @endforeach
    </div>
    <div id="birDragReadout" class="no-print"></div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var layout = @json($printLayout);
    var defaults = @json($printLayoutDefaults);
    var fields = Array.prototype.slice.call(document.querySelectorAll('.bir-field[data-field-key]'));
    var status = document.getElementById('birLayoutStatus');
    var readout = document.getElementById('birDragReadout');
    var positionRows = document.getElementById('birPositionRows');
    var dragging = null;
    var selectedKey = null;
    var labels = {};

    fields.forEach(function (field) {
        labels[field.dataset.fieldKey] = field.dataset.fieldLabel;
    });

    function rounded(value) { return Math.round(value * 100) / 100; }
    function activeKeys() {
        return Object.keys(labels).sort(function (a, b) {
            return Object.keys(layout).indexOf(a) - Object.keys(layout).indexOf(b);
        });
    }
    function fieldsFor(key) {
        return fields.filter(function (field) { return field.dataset.fieldKey === key; });
    }
    function render() {
        fields.forEach(function (field) {
            var position = layout[field.dataset.fieldKey];
            field.style.top = position.top + 'px';
            field.style.left = position.left + 'px';
            field.style.width = position.width + 'px';
            field.style.fontSize = position.font_size + 'px';
        });
        positionRows.querySelectorAll('input[data-position-key]').forEach(function (input) {
            var position = layout[input.dataset.positionKey];
            if (document.activeElement !== input) input.value = position[input.dataset.property];
        });
    }
    function buildPositionRows() {
        activeKeys().forEach(function (key) {
            var row = document.createElement('tr');
            var title = document.createElement('td');
            title.textContent = labels[key] || key;
            row.appendChild(title);
            ['left', 'top', 'width', 'font_size'].forEach(function (property) {
                var cell = document.createElement('td');
                var input = document.createElement('input');
                input.type = 'number';
                input.step = property === 'font_size' ? '0.1' : '0.25';
                input.className = 'form-control form-control-sm';
                input.dataset.positionKey = key;
                input.dataset.property = property;
                input.value = layout[key][property];
                input.addEventListener('input', function () {
                    var value = parseFloat(this.value);
                    if (!isNaN(value)) {
                        layout[key][property] = rounded(value);
                        render();
                    }
                });
                cell.appendChild(input);
                row.appendChild(cell);
            });
            positionRows.appendChild(row);
        });
    }
    function selectField(field) {
        selectedKey = field.dataset.fieldKey;
        fields.forEach(function (item) {
            item.classList.toggle('is-selected', item.dataset.fieldKey === selectedKey);
        });
        status.textContent = (labels[selectedKey] || selectedKey) + ' selected — drag it or use arrow keys; Shift + arrow moves 5 px.';
    }
    function pageScale(field) {
        var page = field.closest('.bir-2307-page');
        var rect = page.getBoundingClientRect();
        return { x: 816 / rect.width, y: 1248 / rect.height };
    }
    function beginDrag(event, field) {
        selectField(field);
        if (document.body.classList.contains('bir-2307-locked')) return;
        event.preventDefault();
        field.focus();
        field.setPointerCapture(event.pointerId);
        var key = field.dataset.fieldKey;
        dragging = {
            field: field,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            startTop: layout[key].top,
            startLeft: layout[key].left,
            scale: pageScale(field)
        };
        field.classList.add('is-dragging');
    }
    function moveDrag(event) {
        if (!dragging || event.pointerId !== dragging.pointerId) return;
        var key = dragging.field.dataset.fieldKey;
        layout[key].top = rounded(dragging.startTop + ((event.clientY - dragging.startY) * dragging.scale.y));
        layout[key].left = rounded(dragging.startLeft + ((event.clientX - dragging.startX) * dragging.scale.x));
        render();
        readout.style.display = 'block';
        readout.style.left = (event.clientX + 14) + 'px';
        readout.style.top = (event.clientY + 14) + 'px';
        readout.textContent = 'X ' + layout[key].left.toFixed(2) + ' px, Y ' + layout[key].top.toFixed(2) + ' px';
    }
    function endDrag(event) {
        if (!dragging || event.pointerId !== dragging.pointerId) return;
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
            if (document.body.classList.contains('bir-2307-locked')) return;
            var key = field.dataset.fieldKey;
            var step = event.shiftKey ? 5 : 1;
            if (event.key === 'ArrowUp') layout[key].top = rounded(layout[key].top - step);
            else if (event.key === 'ArrowDown') layout[key].top = rounded(layout[key].top + step);
            else if (event.key === 'ArrowLeft') layout[key].left = rounded(layout[key].left - step);
            else if (event.key === 'ArrowRight') layout[key].left = rounded(layout[key].left + step);
            else return;
            event.preventDefault();
            render();
        });
    });

    document.getElementById('toggleBirLock').addEventListener('change', function () {
        document.body.classList.toggle('bir-2307-locked', this.checked);
        status.textContent = this.checked ? 'Field positions locked.' : 'Field positions unlocked.';
    });
    document.getElementById('resetBirLayout').addEventListener('click', function () {
        layout = JSON.parse(JSON.stringify(defaults));
        render();
        status.textContent = 'Default positions restored in this preview. Save Layout to keep them.';
    });
    document.getElementById('printBir2307').addEventListener('click', function () { window.print(); });

    var saveButton = document.getElementById('saveBirLayout');
    if (saveButton) {
        saveButton.addEventListener('click', function () {
            var changedLayout = {};
            Object.keys(layout).forEach(function (key) {
                var position = layout[key];
                var fallback = defaults[key];
                var changed = !fallback || ['top', 'left', 'width', 'font_size'].some(function (property) {
                    return Number(position[property]) !== Number(fallback[property]);
                });
                if (changed) changedLayout[key] = position;
            });
            saveButton.disabled = true;
            status.textContent = 'Saving layout...';
            fetch('{{ route('erp.payables.vendor-bills.print-2307.layout.save') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ layout: changedLayout })
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () { return {}; }).then(function (data) {
                        throw new Error(data.message || 'Save failed (' + response.status + ')');
                    });
                }
                return response.json();
            }).then(function (data) {
                layout = data.layout;
                render();
                status.textContent = data.status;
            }).catch(function (error) {
                status.textContent = error.message;
            }).then(function () {
                saveButton.disabled = false;
            });
        });
    }

    buildPositionRows();
    render();
}());
</script>
@endpush
