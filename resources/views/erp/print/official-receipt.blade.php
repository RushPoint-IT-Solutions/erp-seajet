@extends('erp.layout')

@section('title', 'Official Receipt Preview')
@section('page-title', 'Official Receipt')

@push('styles')
<style>
    .receipt-toolbar {
        max-width: 210mm;
        margin: 0 auto 16px;
    }
    .or-sheet {
        width: 210mm;
        height: 148.5mm;
        margin: 0 auto;
        padding: 10mm 12mm 8mm;
        background: #f7f7f2;
        border: 1px solid #222;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .16);
        color: #1f2933;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        line-height: 1.2;
        position: relative;
        overflow: hidden;
    }
    .receipt-adjustable-field {
        position: relative;
        z-index: 2;
        display: inline-block;
    }
    .receipt-field-block {
        display: block;
    }
    .receipt-field-hidden {
        display: none !important;
    }
    .or-sheet.is-adjusting .receipt-adjustable-field {
        outline: 1px dashed #f97316;
        outline-offset: 2px;
        min-width: 5mm;
        min-height: 3mm;
        cursor: move;
        touch-action: none;
        user-select: none;
    }
    .or-sheet.is-adjusting .receipt-adjustable-field.is-active {
        outline: 2px solid #f97316;
        background: rgba(255, 247, 237, .9);
    }
    .or-sheet.is-adjusting.show-grid {
        background-color: #f7f7f2;
        background-image:
            linear-gradient(rgba(249, 115, 22, .12) 1px, transparent 1px),
            linear-gradient(90deg, rgba(249, 115, 22, .12) 1px, transparent 1px);
        background-size: 5mm 5mm;
    }
    .or-sheet.fields-locked .receipt-adjustable-field {
        cursor: default;
    }
    .receipt-layout-readout {
        min-width: 210mm;
        margin: 0 auto 12px;
    }
    .or-top {
        display: grid;
        grid-template-columns: 1fr 64mm;
        gap: 8mm;
        align-items: start;
    }
    .or-brand-row {
        display: flex;
        align-items: flex-start;
        gap: 7mm;
    }
    .or-logo {
        width: 34mm;
        height: 18mm;
        object-fit: contain;
        object-position: left center;
        filter: grayscale(1) contrast(1.15);
    }
    .or-company-name {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: .2px;
        margin-top: 10mm;
        text-transform: uppercase;
    }
    .or-company-address {
        font-size: 9px;
        font-weight: 700;
        line-height: 1.15;
        margin-top: 1mm;
        text-transform: uppercase;
    }
    .or-title {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: .8px;
        text-align: center;
        text-transform: uppercase;
    }
    .or-number {
        margin: 2mm 0 7mm;
        text-align: center;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 20px;
        letter-spacing: 2px;
    }
    .or-number span {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        letter-spacing: 0;
        margin-right: 5mm;
    }
    .or-meta {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }
    .or-meta td {
        border: 1px solid #222;
        height: 7mm;
        padding: 1mm 2mm;
        vertical-align: middle;
    }
    .or-meta td:first-child {
        width: 27mm;
        font-weight: 700;
    }
    .or-section-title {
        border: 1px solid #222;
        border-bottom: 0;
        font-size: 12px;
        font-weight: 800;
        padding: 1mm 2mm;
        text-transform: uppercase;
    }
    .or-received {
        border: 1px solid #222;
        display: grid;
        grid-template-columns: 35mm 1fr;
        min-height: 25mm;
        padding: 2mm;
        row-gap: 1mm;
    }
    .or-received-label {
        font-weight: 700;
    }
    .or-received-value {
        color: #4b5563;
        font-size: 11px;
        text-transform: uppercase;
    }
    .or-transaction {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
    }
    .or-transaction th,
    .or-transaction td {
        border: 1px solid #222;
    }
    .or-transaction th {
        height: 7mm;
        padding: 1mm 2mm;
        text-align: left;
        font-size: 12px;
        font-weight: 800;
    }
    .or-transaction th:last-child,
    .or-transaction td:last-child {
        width: 58mm;
        text-align: right;
    }
    .or-transaction td {
        height: 30mm;
        padding: 6mm 6mm 3mm;
        vertical-align: top;
        white-space: pre-line;
    }
    .or-amount {
        font-variant-numeric: tabular-nums;
    }
    .or-bottom-grid {
        display: grid;
        grid-template-columns: 95mm 1fr;
        gap: 0;
    }
    .or-methods {
        display: flex;
        align-items: center;
        gap: 12mm;
        height: 9mm;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .or-check {
        display: inline-block;
        width: 5mm;
        height: 5mm;
        border: 1px solid #222;
        margin-right: 2mm;
        position: relative;
        top: 1mm;
    }
    .or-total-row {
        display: grid;
        grid-template-columns: 1fr 58mm;
        min-height: 9mm;
        border: 1px solid #222;
        border-top: 0;
        font-weight: 800;
        text-transform: uppercase;
    }
    .or-total-row div {
        padding: 2mm;
    }
    .or-total-row div + div {
        border-left: 1px solid #222;
        text-align: right;
        font-weight: 500;
        text-transform: none;
    }
    .or-check-lines {
        font-size: 10px;
        line-height: 1.5;
        padding-top: 1mm;
    }
    .or-check-lines .or-line-value {
        display: inline-block;
        min-width: 43mm;
        border-bottom: 1px solid #222;
        margin-left: 2mm;
    }
    .or-printer {
        position: absolute;
        left: 15mm;
        bottom: 10mm;
        width: 65mm;
        font-size: 6px;
        line-height: 1.15;
    }
    .or-tax-note {
        position: absolute;
        left: 74mm;
        bottom: 8mm;
        font-size: 10px;
        font-weight: 800;
        text-align: center;
        text-transform: uppercase;
        width: 62mm;
    }
    .or-signature {
        position: absolute;
        right: 18mm;
        bottom: 8mm;
        width: 58mm;
        text-align: center;
        font-size: 10px;
    }
    .or-signature-line {
        border-top: 1px solid #222;
        height: 8mm;
    }
    .or-printed-by {
        position: absolute;
        right: 18mm;
        bottom: 2mm;
        width: 58mm;
        text-align: center;
        white-space: nowrap;
    }
    @media print {
        @page { size: A4 portrait; margin: 0; }
        body { background: #fff !important; }
        .no-print, .receipt-toolbar, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-main, .erp-content, main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .or-sheet {
            width: 210mm;
            height: 148.5mm;
            margin: 0;
            border: 0;
            box-shadow: none;
            background: transparent !important;
            page-break-after: always;
        }
        .or-template-only {
            visibility: hidden !important;
        }
        .or-meta td,
        .or-received,
        .or-transaction th,
        .or-transaction td,
        .or-check,
        .or-total-row,
        .or-total-row div + div,
        .or-check-lines .or-line-value {
            border-color: transparent !important;
        }
        .or-received-value,
        .or-amount {
            color: #000 !important;
        }
        .or-sheet.is-adjusting .receipt-adjustable-field {
            outline: 0 !important;
            background: transparent !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $receipt = $receipt ?? [];
    $amount = (float) ($receipt['amount'] ?? 0);
    $currency = $receipt['currency'] ?? 'PHP';
    $receiptLayout = $receiptLayout ?? [];
    $receiptFieldStyles = $receiptFieldStyles ?? [];
    $receiptFontDefaults = $receiptFontDefaults ?? [];
    $receiptFontOptions = $receiptFontOptions ?? [];
    $receiptFieldLabels = $receiptFieldLabels ?? [];
    $receiptHiddenFields = $receiptHiddenFields ?? [];
    $adjustLayout = $adjustLayout ?? false;
    $fieldStyle = function ($field) use ($receiptLayout, $receiptFieldStyles, $receiptHiddenFields) {
        $point = $receiptLayout[$field] ?? [0, 0];
        $fontSize = $receiptFieldStyles[$field]['font_size'] ?? 9;
        $fontStack = $receiptFieldStyles[$field]['font_stack'] ?? 'Arial, Helvetica, sans-serif';
        $fontWeight = !empty($receiptFieldStyles[$field]['bold']) ? 700 : 400;
        $hiddenStyle = in_array($field, $receiptHiddenFields, true) ? ' display: none;' : '';
        return 'transform: translate('.((float) ($point[0] ?? 0)).'mm, '.((float) ($point[1] ?? 0)).'mm); font-size: '.((float) $fontSize).'pt; font-family: '.$fontStack.'; font-weight: '.$fontWeight.' !important;'.$hiddenStyle;
    };
@endphp

    <div class="receipt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 no-print">
        @if($adjustLayout)
            <form method="post" action="{{ route('erp.official-receipt.layout.save') }}" id="receiptLayoutForm" class="d-inline">
                @csrf
                <input type="hidden" name="receipt_no" value="{{ $receipt['receipt_no'] ?? '' }}">
                <input type="hidden" name="visibility_present" value="1">
                @foreach($receiptLayout as $field => $point)
                    <input type="hidden" name="layout_x[{{ $field }}]" id="layout_x_{{ $field }}" value="{{ $point[0] }}">
                    <input type="hidden" name="layout_y[{{ $field }}]" id="layout_y_{{ $field }}" value="{{ $point[1] }}">
                    <input type="hidden" name="font_size[{{ $field }}]" id="font_size_{{ $field }}" value="{{ $receiptFieldStyles[$field]['font_size'] }}" data-default="{{ $receiptFontDefaults[$field] }}">
                    <input type="hidden" name="font_family[{{ $field }}]" id="font_family_{{ $field }}" value="{{ $receiptFieldStyles[$field]['font_family'] }}" data-default="arial">
                    <input type="checkbox" name="bold_field[]" id="bold_field_{{ $field }}" value="{{ $field }}" class="d-none" {{ $receiptFieldStyles[$field]['bold'] ? 'checked' : '' }}>
                @endforeach
                <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Save Layout</button>
            </form>
            <label class="small d-flex align-items-center gap-1 mb-0">
                <span id="selectedReceiptField">Select a field</span>
                <input type="number" id="receiptFontSize" class="form-control form-control-sm" min="6" max="36" step="0.25" style="width:74px" disabled>
                pt
            </label>
            <label class="small d-flex align-items-center gap-1 mb-0">
                Font
                <select id="receiptFontFamily" class="form-select form-select-sm" style="width:150px" disabled>
                    @foreach($receiptFontOptions as $fontKey => $fontOption)
                        <option value="{{ $fontKey }}" data-stack="{{ $fontOption['stack'] }}">{{ $fontOption['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="small d-flex align-items-center gap-1 mb-0"><input type="checkbox" id="receiptBold" disabled> Bold</label>
            <label class="small d-flex align-items-center gap-1 mb-0">X <input type="number" id="receiptPositionX" class="form-control form-control-sm" min="-100" max="100" step="0.1" style="width:78px" disabled></label>
            <label class="small d-flex align-items-center gap-1 mb-0">Y <input type="number" id="receiptPositionY" class="form-control form-control-sm" min="-100" max="100" step="0.1" style="width:78px" disabled></label>
            <label class="small d-flex align-items-center gap-1 mb-0"><input type="checkbox" id="toggleReceiptGrid"> 5 mm grid</label>
            <label class="small d-flex align-items-center gap-1 mb-0"><input type="checkbox" id="toggleReceiptLock"> Lock</label>
            <button type="button" class="btn btn-outline-danger" id="deleteReceiptField" disabled><i class="bi bi-trash me-1"></i> Delete Selected</button>
            <button type="button" class="btn btn-outline-secondary" id="resetReceiptPositions"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
            <a href="{{ route('erp.official-receipt.preview', request()->except('adjust')) }}" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Done</a>
        @else
            <a href="{{ route('erp.official-receipt.preview', array_merge(request()->query(), ['adjust' => 1])) }}" class="btn btn-outline-primary"><i class="bi bi-sliders me-1"></i> Adjust Layout</a>
        @endif
        <div class="btn-group" role="group" aria-label="OR tax print type">
            <a href="{{ route('erp.official-receipt.preview', array_merge(request()->query(), ['tax_print' => 'vat'])) }}" class="btn btn-primary">VAT Print</a>
            <a href="{{ route('erp.official-receipt.preview', array_merge(request()->query(), ['tax_print' => 'non_vat'])) }}" class="btn btn-outline-primary">Non-VAT Print</a>
        </div>
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Content Only</button>
        <a href="{{ route('erp.payments.create') }}" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i> Back to Payments</a>
    </div>

    @if(session('status'))
        <div class="receipt-layout-readout alert alert-success py-2 no-print">{{ session('status') }}</div>
    @endif
    @if($adjustLayout)
        <div class="receipt-layout-readout alert alert-info py-2 no-print">
            Select any printable field to change its font, font size, bold style, and X/Y coordinates. Drag it, or use the arrow keys; hold Shift for smaller movements.
            <span id="receiptPositionReadout" class="fw-bold ms-2"></span>
        </div>
        @include('erp.print._receipt-field-visibility')
    @endif

    <div class="or-sheet {{ $adjustLayout ? 'is-adjusting' : '' }}" id="receiptLayoutSheet">
        <div class="or-top">
            <div>
                <div class="or-brand-row or-template-only">
                    <img src="{{ asset('images/image.png') }}" class="or-logo" alt="SeaJet logo">
                    <div>
                        <div class="or-company-name">SEA-JET INTERNATIONAL FORWARDERS, INC.</div>
                        <div class="or-company-address">
                            UNIT 18 &amp; 19 BLDG. B. CARGO VILLAGE COMPLEX<br>
                            NINOY AQUINO AVE., STO. NINO, PARANAQUE CITY 1704, PHILIPPINES<br>
                            TEL. NOS.: (632) 8852-9001, 8852-9002 and 8852-9003<br>
                            VAT REG. TIN: 000-143-896-00000
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="or-title or-template-only">Official Receipt</div>
                <div class="or-number"><span class="or-template-only">No.</span><span class="receipt-adjustable-field" tabindex="0" data-field-key="receipt_no" data-field-label="Receipt number" style="{{ $fieldStyle('receipt_no') }}">{{ $receipt['receipt_no'] ?? '0046043' }}</span></div>
                <table class="or-meta">
                    <tr>
                        <td class="or-template-only">Payment Date</td>
                        <td><span class="receipt-adjustable-field" tabindex="0" data-field-key="payment_date" data-field-label="Payment date" style="{{ $fieldStyle('payment_date') }}">{{ $receipt['payment_date'] ?? date('M d, Y') }}</span></td>
                    </tr>
                    <tr>
                        <td class="or-template-only">Account No.</td>
                        <td><span class="receipt-adjustable-field" tabindex="0" data-field-key="account_no" data-field-label="Account number" style="{{ $fieldStyle('account_no') }}">{{ $receipt['account_no'] ?? '' }}</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="or-section-title mt-2 or-template-only">Received From</div>
        <div class="or-received">
            <div class="or-received-label or-template-only">Registered Name</div>
            <div class="or-received-value receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="registered_name" data-field-label="Registered name" style="{{ $fieldStyle('registered_name') }}">{{ $receipt['registered_name'] ?? '' }}</div>
            <div class="or-received-label or-template-only">TIN</div>
            <div class="or-received-value receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="tin" data-field-label="TIN" style="{{ $fieldStyle('tin') }}">{{ $receipt['tin'] ?? '' }}</div>
            <div class="or-received-label or-template-only">Business Address:</div>
            <div class="or-received-value receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="business_address" data-field-label="Business address" style="{{ $fieldStyle('business_address') }}">{{ $receipt['business_address'] ?? '' }}</div>
        </div>

        <table class="or-transaction">
            <thead>
                <tr class="or-template-only">
                    <th>Description of Transaction / Nature of Service</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><div class="receipt-adjustable-field receipt-field-block" tabindex="0" data-field-key="description" data-field-label="Description" style="{{ $fieldStyle('description') }}">{{ $receipt['description'] ?? '' }}</div></td>
                    <td class="or-amount"><span class="receipt-adjustable-field" tabindex="0" data-field-key="amount" data-field-label="Transaction amount" style="{{ $fieldStyle('amount') }}">{{ number_format($amount, 2) }}</span></td>
                </tr>
            </tbody>
        </table>

        <div class="or-bottom-grid">
            <div>
                <div class="or-methods">
                    <span><span class="or-check"></span><span class="or-template-only">Check</span></span>
                    <span><span class="or-check"></span><span class="or-template-only">Cash</span></span>
                    <span><span class="or-check"></span><span class="or-template-only">Online</span></span>
                </div>
                <div class="or-check-lines">
                    <span class="or-template-only">No.</span> <span class="or-line-value receipt-adjustable-field" tabindex="0" data-field-key="reference_no" data-field-label="Reference number" style="{{ $fieldStyle('reference_no') }}">{{ $receipt['reference_no'] ?? '' }}</span><br>
                    <span class="or-template-only">Bank:</span> <span class="or-line-value receipt-adjustable-field" tabindex="0" data-field-key="bank" data-field-label="Bank" style="{{ $fieldStyle('bank') }}">{{ $receipt['bank'] ?? '' }}</span>
                </div>
            </div>
            <div class="or-total-row">
                <div class="or-template-only">Total Paid Amount</div>
                <div class="or-amount"><span class="receipt-adjustable-field" tabindex="0" data-field-key="total_amount" data-field-label="Total paid amount" style="{{ $fieldStyle('total_amount') }}">{{ $currency }} {{ number_format($amount, 2) }}</span></div>
            </div>
        </div>

        <div class="or-printer or-template-only">
            20 PADS (50X3) &nbsp;&nbsp;&nbsp;&nbsp; 0045251 - 0046250<br>
            BIR ATP NO.: OCN05ZAU20240000010957<br>
            Date Issued: 09/23/2024<br>
            Printer's Accreditation No.: 052NP202400000091<br>
            Accreditation Date: January 22, 2024 Expiry Date: January 21, 2029<br>
            CB-ThisViewer - Brgy. Pinagbarilan, Baliuag, Bulacan<br>
            TIN: 200-589-800000
        </div>
        <div class="or-tax-note or-template-only">"This document is not valid for claim of input tax"</div>
        <div class="or-signature or-template-only">
            <div class="or-signature-line"></div>
            Cashier/Authorized Representative
        </div>
        <div class="or-printed-by receipt-adjustable-field" tabindex="0" data-field-key="printed_by" data-field-label="Printed by" style="{{ $fieldStyle('printed_by') }}">Printed by: {{ $receipt['printed_by'] ?? (auth()->user()->name ?? 'User') }}</div>
    </div>

    @if($adjustLayout)
        @include('erp.print._receipt-layout-editor-script')
    @endif
@endsection
