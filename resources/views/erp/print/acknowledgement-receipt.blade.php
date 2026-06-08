@extends('erp.layout')

@section('title', 'Acknowledgement Receipt Preview')
@section('page-title', 'Acknowledgement Receipt')

@push('styles')
<style>
    .receipt-toolbar {
        max-width: 210mm;
        margin: 0 auto 16px;
    }
    .ar-sheet {
        width: 210mm;
        height: 148.5mm;
        margin: 0 auto;
        padding: 10mm 14mm 8mm;
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
    .ar-top {
        display: grid;
        grid-template-columns: 1fr 62mm;
        gap: 8mm;
        align-items: start;
    }
    .ar-brand-row {
        display: flex;
        align-items: flex-start;
        gap: 7mm;
    }
    .ar-logo {
        width: 34mm;
        height: 18mm;
        object-fit: contain;
        object-position: left center;
        filter: grayscale(1) contrast(1.15);
    }
    .ar-company-name {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: .2px;
        margin-top: 10mm;
        text-transform: uppercase;
    }
    .ar-company-address {
        font-size: 9px;
        font-weight: 700;
        line-height: 1.15;
        margin-top: 1mm;
        text-transform: uppercase;
    }
    .ar-title {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: .2px;
        text-align: center;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .ar-number {
        margin: 3mm 0 6mm;
        text-align: center;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 20px;
        letter-spacing: 2px;
    }
    .ar-number span {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        letter-spacing: 0;
        margin-right: 5mm;
    }
    .ar-meta {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }
    .ar-meta td {
        border: 1px solid #222;
        height: 7mm;
        padding: 1mm 2mm;
        vertical-align: middle;
    }
    .ar-meta td:first-child {
        width: 27mm;
        font-weight: 700;
    }
    .ar-section-title {
        border: 1px solid #222;
        border-bottom: 0;
        font-size: 12px;
        font-weight: 800;
        padding: 1mm 2mm;
        text-transform: uppercase;
    }
    .ar-received {
        border: 1px solid #222;
        display: grid;
        grid-template-columns: 35mm 1fr;
        min-height: 25mm;
        padding: 2mm;
        row-gap: 1mm;
    }
    .ar-received-label {
        font-weight: 700;
    }
    .ar-received-value {
        color: #4b5563;
        font-size: 11px;
        text-transform: uppercase;
    }
    .ar-transaction {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
    }
    .ar-transaction th,
    .ar-transaction td {
        border: 1px solid #222;
    }
    .ar-transaction th {
        height: 7mm;
        padding: 1mm 2mm;
        text-align: left;
        font-size: 12px;
        font-weight: 800;
    }
    .ar-transaction th:last-child,
    .ar-transaction td:last-child {
        width: 58mm;
        text-align: right;
    }
    .ar-transaction td {
        height: 28mm;
        padding: 6mm 6mm 3mm;
        vertical-align: top;
        white-space: pre-line;
    }
    .ar-amount {
        font-variant-numeric: tabular-nums;
    }
    .ar-bottom-grid {
        display: grid;
        grid-template-columns: 95mm 1fr;
        gap: 0;
    }
    .ar-methods {
        display: flex;
        align-items: center;
        gap: 12mm;
        height: 9mm;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .ar-check {
        display: inline-block;
        width: 5mm;
        height: 5mm;
        border: 1px solid #222;
        margin-right: 2mm;
        position: relative;
        top: 1mm;
    }
    .ar-check.checked:after {
        content: "";
        position: absolute;
        left: 1mm;
        top: 0;
        width: 2mm;
        height: 4mm;
        border: solid #111;
        border-width: 0 1px 1px 0;
        transform: rotate(45deg);
    }
    .ar-total-row {
        display: grid;
        grid-template-columns: 1fr 58mm;
        min-height: 9mm;
        border: 1px solid #222;
        border-top: 0;
        font-weight: 800;
        text-transform: uppercase;
    }
    .ar-total-row div {
        padding: 2mm;
    }
    .ar-total-row div + div {
        border-left: 1px solid #222;
        text-align: right;
        font-weight: 500;
        text-transform: none;
    }
    .ar-check-lines {
        font-size: 10px;
        line-height: 1.5;
        padding-top: 1mm;
    }
    .ar-check-lines span {
        display: inline-block;
        min-width: 43mm;
        border-bottom: 1px solid #222;
        margin-left: 2mm;
    }
    .ar-printer {
        position: absolute;
        left: 18mm;
        bottom: 10mm;
        width: 63mm;
        font-size: 6px;
        line-height: 1.15;
    }
    .ar-tax-note {
        position: absolute;
        left: 75mm;
        bottom: 8mm;
        font-size: 10px;
        font-weight: 800;
        text-align: center;
        text-transform: uppercase;
        width: 62mm;
    }
    .ar-signature {
        position: absolute;
        right: 18mm;
        bottom: 8mm;
        width: 58mm;
        text-align: center;
        font-size: 10px;
    }
    .ar-signature-line {
        border-top: 1px solid #222;
        height: 8mm;
    }
    @media print {
        @page { size: A4 portrait; margin: 0; }
        body { background: #fff !important; }
        .no-print, .receipt-toolbar, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-main, .erp-content, main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .ar-sheet {
            width: 210mm;
            height: 148.5mm;
            margin: 0;
            border: 0;
            box-shadow: none;
            background: #fff;
            page-break-after: always;
        }
    }
</style>
@endpush

@section('content')
@php
    $receipt = $receipt ?? [];
    $amount = (float) ($receipt['amount'] ?? 0);
    $currency = $receipt['currency'] ?? 'PHP';
    $paymentMethod = strtolower($receipt['payment_method'] ?? '');
    $isCheck = strpos($paymentMethod, 'check') !== false || strpos($paymentMethod, 'cheque') !== false;
    $isCash = strpos($paymentMethod, 'cash') !== false;
    $isOnline = strpos($paymentMethod, 'online') !== false || strpos($paymentMethod, 'bank') !== false || strpos($paymentMethod, 'transfer') !== false;
@endphp

    <div class="receipt-toolbar d-flex justify-content-end gap-2 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Acknowledgement Receipt</button>
        <a href="{{ route('erp.payments.create') }}" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i> Back to Payments</a>
    </div>

    <div class="ar-sheet">
        <div class="ar-top">
            <div>
                <div class="ar-brand-row">
                    <img src="{{ asset('images/image.png') }}" class="ar-logo" alt="SeaJet logo">
                    <div>
                        <div class="ar-company-name">SEA-JET INTERNATIONAL FORWARDERS, INC.</div>
                        <div class="ar-company-address">
                            UNIT 18 &amp; 19 BLDG. B. CARGO VILLAGE COMPLEX<br>
                            NINOY AQUINO AVE., STO. NINO, PARANAQUE CITY 1704, PHILIPPINES<br>
                            TEL. NOS.: (632) 8852-9001, 8852-9002 and 8852-9003<br>
                            VAT REG. TIN: 000-143-896-00000
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="ar-title">Acknowledgement Receipt</div>
                <div class="ar-number"><span>No.</span>{{ $receipt['receipt_no'] ?? '0007494' }}</div>
                <table class="ar-meta">
                    <tr>
                        <td>Payment Date</td>
                        <td>{{ $receipt['payment_date'] ?? date('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td>Account No.</td>
                        <td>{{ $receipt['account_no'] ?? '' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="ar-section-title mt-1">Received From</div>
        <div class="ar-received">
            <div class="ar-received-label">Registered Name</div>
            <div class="ar-received-value">{{ $receipt['registered_name'] ?? '' }}</div>
            <div class="ar-received-label">TIN</div>
            <div class="ar-received-value">{{ $receipt['tin'] ?? '' }}</div>
            <div class="ar-received-label">Business Address:</div>
            <div class="ar-received-value">{{ $receipt['business_address'] ?? '' }}</div>
        </div>

        <table class="ar-transaction">
            <thead>
                <tr>
                    <th>Description of Transaction / Nature of Service</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $receipt['description'] ?? '' }}</td>
                    <td class="ar-amount">{{ number_format($amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="ar-bottom-grid">
            <div>
                <div class="ar-methods">
                    <span><span class="ar-check {{ $isCheck ? 'checked' : '' }}"></span>Check</span>
                    <span><span class="ar-check {{ $isCash ? 'checked' : '' }}"></span>Cash</span>
                    <span><span class="ar-check {{ $isOnline ? 'checked' : '' }}"></span>Online</span>
                </div>
                <div class="ar-check-lines">
                    No. <span>{{ $receipt['reference_no'] ?? '' }}</span><br>
                    Bank: <span>{{ $receipt['bank'] ?? '' }}</span>
                </div>
            </div>
            <div class="ar-total-row">
                <div>Total Paid Amount</div>
                <div class="ar-amount">{{ $currency }} {{ number_format($amount, 2) }}</div>
            </div>
        </div>

        <div class="ar-printer">
            20 PADS (50X3) &nbsp;&nbsp;&nbsp;&nbsp; 0007001 - 0008000<br>
            BIR ATP NO.: OCN05ZAU20240000010957<br>
            Date Issued: 09/23/2024<br>
            Printer's Accreditation No.: 052NP202400000091<br>
            Accreditation Date: January 22, 2024 Expiry Date: January 21, 2029<br>
            CB-ThisViewer - Brgy. Pinagbarilan, Baliuag, Bulacan<br>
            TIN: 200-589-800000
        </div>
        <div class="ar-tax-note">"This document is not valid for claim of input tax"</div>
        <div class="ar-signature">
            <div class="ar-signature-line"></div>
            Cashier/Authorized Representative
        </div>
    </div>
@endsection
