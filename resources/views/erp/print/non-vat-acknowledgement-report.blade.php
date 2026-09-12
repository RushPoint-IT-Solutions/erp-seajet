@extends('erp.layout')

@section('title', 'Non-VAT Acknowledgement Report')
@section('page-title', 'Non-VAT Acknowledgement Report')

@push('styles')
<style>
    .nonvat-toolbar, .nonvat-editor-note, .nonvat-content-editor { max-width: 215.9mm; margin-left: auto; margin-right: auto; }
    .nonvat-sheet {
        width: 215.9mm;
        min-height: 279.4mm;
        margin: 0 auto;
        padding: 20mm 18mm;
        background: #fff;
        border: 1px solid #cbd5e1;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .14);
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        position: relative;
        overflow: hidden;
    }
    .nonvat-header { text-align: center; margin-bottom: 18mm; }
    .nonvat-header .receipt-adjustable-field { text-transform: uppercase; letter-spacing: .8px; }
    .nonvat-intro { margin-bottom: 10mm; line-height: 1.6; }
    .nonvat-details { width: 100%; border-collapse: collapse; margin-bottom: 12mm; }
    .nonvat-details td { border: 1px solid #334155; padding: 3mm; vertical-align: top; }
    .nonvat-details td:first-child { width: 42mm; font-weight: 700; }
    .nonvat-description { min-height: 35mm; white-space: pre-line; }
    .nonvat-amount { font-size: 16px; text-align: right; white-space: nowrap; }
    .nonvat-footer { margin-top: 18mm; line-height: 1.6; }
    .nonvat-signature { width: 72mm; margin: 28mm 0 0 auto; text-align: center; }
    .nonvat-signature-line { border-top: 1px solid #111827; padding-top: 2mm; }
    .nonvat-printed-by { position: absolute; left: 18mm; bottom: 14mm; }
    .receipt-adjustable-field { position: relative; z-index: 2; display: inline-block; }
    .receipt-field-block { display: block; }
    .receipt-field-hidden { display: none !important; }
    .nonvat-sheet.is-adjusting .receipt-adjustable-field {
        outline: 1px dashed #2563eb;
        outline-offset: 2px;
        min-width: 5mm;
        min-height: 3mm;
        cursor: move;
        touch-action: none;
        user-select: none;
    }
    .nonvat-sheet.is-adjusting .receipt-adjustable-field.is-active { outline: 2px solid #2563eb; background: rgba(239, 246, 255, .92); }
    .nonvat-sheet.is-adjusting.show-grid {
        background-image: linear-gradient(rgba(37, 99, 235, .1) 1px, transparent 1px), linear-gradient(90deg, rgba(37, 99, 235, .1) 1px, transparent 1px);
        background-size: 5mm 5mm;
    }
    .nonvat-sheet.fields-locked .receipt-adjustable-field { cursor: default; }
    @media print {
        @page { size: Letter portrait; margin: 0; }
        body { background: #fff !important; }
        .no-print, .nonvat-toolbar, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-main, .erp-content, main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .nonvat-sheet { width: 215.9mm; height: 279.4mm; min-height: 279.4mm; margin: 0; border: 0; box-shadow: none; }
        .nonvat-sheet.is-adjusting .receipt-adjustable-field { outline: 0 !important; background: transparent !important; }
    }
</style>
@endpush

@section('content')
@php
    $amount = (float) ($receipt['amount'] ?? 0);
    $currency = $receipt['currency'] ?? 'PHP';
    $fieldStyle = function ($field) use ($receiptLayout, $receiptFieldStyles, $receiptHiddenFields) {
        $point = $receiptLayout[$field] ?? [0, 0];
        $style = $receiptFieldStyles[$field] ?? ['font_size' => 9, 'font_stack' => 'Arial, Helvetica, sans-serif', 'bold' => false];
        $hidden = in_array($field, $receiptHiddenFields, true) ? ' display: none;' : '';
        return 'transform: translate('.((float) ($point[0] ?? 0)).'mm, '.((float) ($point[1] ?? 0)).'mm); font-size: '.((float) $style['font_size']).'pt; font-family: '.$style['font_stack'].'; font-weight: '.(!empty($style['bold']) ? 700 : 400).' !important;'.$hidden;
    };
@endphp

<div class="nonvat-toolbar d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3 no-print">
    @if($adjustLayout)
        <form method="post" action="{{ route('erp.non-vat-acknowledgement.layout.save') }}" id="receiptLayoutForm" class="d-inline">
            @csrf
            <input type="hidden" name="receipt_no" value="{{ $receipt['receipt_no'] ?? '' }}">
            <input type="hidden" name="source_document" value="{{ $sourceDocument }}">
            <input type="hidden" name="visibility_present" value="1">
            @foreach($receiptLayout as $field => $point)
                <input type="hidden" name="layout_x[{{ $field }}]" id="layout_x_{{ $field }}" value="{{ $point[0] }}">
                <input type="hidden" name="layout_y[{{ $field }}]" id="layout_y_{{ $field }}" value="{{ $point[1] }}">
                <input type="hidden" name="font_size[{{ $field }}]" id="font_size_{{ $field }}" value="{{ $receiptFieldStyles[$field]['font_size'] }}" data-default="{{ $receiptFontDefaults[$field] }}">
                <input type="hidden" name="font_family[{{ $field }}]" id="font_family_{{ $field }}" value="{{ $receiptFieldStyles[$field]['font_family'] }}" data-default="arial">
                <input type="checkbox" name="bold_field[]" id="bold_field_{{ $field }}" value="{{ $field }}" class="d-none" {{ $receiptFieldStyles[$field]['bold'] ? 'checked' : '' }}>
            @endforeach
            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Save Content &amp; Layout</button>
        </form>
        <label class="small d-flex align-items-center gap-1 mb-0"><span id="selectedReceiptField">Select a field</span><input type="number" id="receiptFontSize" class="form-control form-control-sm" min="6" max="36" step="0.25" style="width:74px" disabled> pt</label>
        <label class="small d-flex align-items-center gap-1 mb-0">Font <select id="receiptFontFamily" class="form-select form-select-sm" style="width:150px" disabled>@foreach($receiptFontOptions as $fontKey => $fontOption)<option value="{{ $fontKey }}" data-stack="{{ $fontOption['stack'] }}">{{ $fontOption['label'] }}</option>@endforeach</select></label>
        <label class="small d-flex align-items-center gap-1 mb-0"><input type="checkbox" id="receiptBold" disabled> Bold</label>
        <label class="small d-flex align-items-center gap-1 mb-0">X <input type="number" id="receiptPositionX" class="form-control form-control-sm" min="-100" max="100" step="0.1" style="width:78px" disabled></label>
        <label class="small d-flex align-items-center gap-1 mb-0">Y <input type="number" id="receiptPositionY" class="form-control form-control-sm" min="-100" max="100" step="0.1" style="width:78px" disabled></label>
        <label class="small d-flex align-items-center gap-1 mb-0"><input type="checkbox" id="toggleReceiptGrid"> 5 mm grid</label>
        <label class="small d-flex align-items-center gap-1 mb-0"><input type="checkbox" id="toggleReceiptLock"> Lock</label>
        <button type="button" class="btn btn-outline-danger" id="deleteReceiptField" disabled>Delete Selected</button>
        <button type="button" class="btn btn-outline-secondary" id="resetReceiptPositions">Reset</button>
        <a href="{{ route('erp.non-vat-acknowledgement.preview', array_merge(request()->except('adjust'), ['source_document' => $sourceDocument])) }}" class="btn btn-primary">Done</a>
    @else
        <a href="{{ route('erp.non-vat-acknowledgement.preview', array_merge(request()->query(), ['adjust' => 1, 'source_document' => $sourceDocument])) }}" class="btn btn-outline-primary"><i class="bi bi-sliders me-1"></i> Edit Content &amp; Layout</a>
    @endif
    <div class="btn-group" role="group" aria-label="{{ strtoupper($sourceDocument) }} tax print type">
        <a href="{{ route($sourceDocument === 'or' ? 'erp.official-receipt.preview' : 'erp.acknowledgement-receipt.preview', array_merge(request()->except(['adjust', 'source_document']), ['tax_print' => 'vat'])) }}" class="btn btn-outline-primary">VAT Print</a>
        <a href="{{ route('erp.non-vat-acknowledgement.preview', array_merge(request()->query(), ['source_document' => $sourceDocument, 'tax_print' => 'non_vat'])) }}" class="btn btn-primary">Non-VAT Print</a>
    </div>
    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Letter</button>
    <a href="{{ route('erp.payments.create') }}" class="btn btn-primary">Back to Payments</a>
</div>

@if(session('status'))<div class="alert alert-success nonvat-editor-note no-print">{{ session('status') }}</div>@endif
@if($adjustLayout)
    <div class="alert alert-info nonvat-editor-note no-print">Edit the report wording below. Select or drag any printable field to change its position, font, size, bold style, or visibility.</div>
    <div class="erp-card p-3 mb-3 nonvat-content-editor no-print">
        <div class="row g-3">
            @foreach(['report_header' => 'Centered header', 'intro_text' => 'Introduction', 'footer_text' => 'Footer note'] as $field => $label)
                <div class="col-md-4"><label class="form-label">{{ $label }}</label><textarea class="form-control nonvat-content-input" rows="2" name="field_content[{{ $field }}]" form="receiptLayoutForm" data-content-target="{{ $field }}">{{ $receiptContent[$field] }}</textarea></div>
            @endforeach
        </div>
    </div>
    @include('erp.print._receipt-field-visibility')
@endif

<div class="nonvat-sheet {{ $adjustLayout ? 'is-adjusting' : '' }}" id="receiptLayoutSheet" data-sheet-width-mm="215.9">
    <div class="nonvat-header"><span class="receipt-adjustable-field" tabindex="0" data-field-key="report_header" data-field-label="Report header" style="{{ $fieldStyle('report_header') }}">{{ $receiptContent['report_header'] }}</span></div>
    <div class="nonvat-intro"><span class="receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="intro_text" data-field-label="Introduction text" style="{{ $fieldStyle('intro_text') }}">{{ $receiptContent['intro_text'] }}</span></div>

    <table class="nonvat-details">
        <tr><td>Report No.</td><td><span class="receipt-adjustable-field" tabindex="0" data-field-key="receipt_no" data-field-label="Receipt number" style="{{ $fieldStyle('receipt_no') }}">{{ $receipt['receipt_no'] ?? '' }}</span></td></tr>
        <tr><td>Date</td><td><span class="receipt-adjustable-field" tabindex="0" data-field-key="payment_date" data-field-label="Payment date" style="{{ $fieldStyle('payment_date') }}">{{ $receipt['payment_date'] ?? '' }}</span></td></tr>
        <tr><td>Account No.</td><td><span class="receipt-adjustable-field" tabindex="0" data-field-key="account_no" data-field-label="Account number" style="{{ $fieldStyle('account_no') }}">{{ $receipt['account_no'] ?? '' }}</span></td></tr>
        <tr><td>Received From</td><td><span class="receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="registered_name" data-field-label="Registered name" style="{{ $fieldStyle('registered_name') }}">{{ $receipt['registered_name'] ?? '' }}</span></td></tr>
        <tr><td>TIN</td><td><span class="receipt-adjustable-field" tabindex="0" data-field-key="tin" data-field-label="TIN" style="{{ $fieldStyle('tin') }}">{{ $receipt['tin'] ?? '' }}</span></td></tr>
        <tr><td>Address</td><td><span class="receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="business_address" data-field-label="Business address" style="{{ $fieldStyle('business_address') }}">{{ $receipt['business_address'] ?? '' }}</span></td></tr>
        <tr><td>Description</td><td class="nonvat-description"><span class="receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="description" data-field-label="Description" style="{{ $fieldStyle('description') }}">{{ $receipt['description'] ?? '' }}</span></td></tr>
        <tr><td>Amount Received</td><td class="nonvat-amount"><span class="receipt-adjustable-field" tabindex="0" data-field-key="amount" data-field-label="Transaction amount" style="{{ $fieldStyle('amount') }}">{{ $currency }} {{ number_format($amount, 2) }}</span></td></tr>
        <tr><td>Payment Method</td><td>{{ $receipt['payment_method'] ?? '' }}</td></tr>
        <tr><td>Reference No.</td><td><span class="receipt-adjustable-field" tabindex="0" data-field-key="reference_no" data-field-label="Reference number" style="{{ $fieldStyle('reference_no') }}">{{ $receipt['reference_no'] ?? '' }}</span></td></tr>
        <tr><td>Bank</td><td><span class="receipt-adjustable-field" tabindex="0" data-field-key="bank" data-field-label="Bank" style="{{ $fieldStyle('bank') }}">{{ $receipt['bank'] ?? '' }}</span></td></tr>
        <tr><td>Total</td><td class="nonvat-amount"><span class="receipt-adjustable-field" tabindex="0" data-field-key="total_amount" data-field-label="Total paid amount" style="{{ $fieldStyle('total_amount') }}">{{ $currency }} {{ number_format($amount, 2) }}</span></td></tr>
    </table>

    <div class="nonvat-footer"><span class="receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="footer_text" data-field-label="Footer text" style="{{ $fieldStyle('footer_text') }}">{{ $receiptContent['footer_text'] }}</span></div>
    <div class="nonvat-signature"><div class="nonvat-signature-line">Authorized Representative</div></div>
    <div class="nonvat-printed-by receipt-adjustable-field" tabindex="0" data-field-key="printed_by" data-field-label="Printed by" style="{{ $fieldStyle('printed_by') }}">Printed by: {{ $receipt['printed_by'] ?? (auth()->user()->name ?? 'User') }}</div>
</div>

@if($adjustLayout)
    @include('erp.print._receipt-layout-editor-script')
    <script>
        document.querySelectorAll('.nonvat-content-input').forEach(function (input) {
            input.addEventListener('input', function () {
                var target = document.querySelector('[data-field-key="' + input.dataset.contentTarget + '"]');
                if (target) target.textContent = input.value;
            });
        });
    </script>
@endif
@endsection
