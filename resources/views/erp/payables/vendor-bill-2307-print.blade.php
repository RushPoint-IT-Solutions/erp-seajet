@extends('erp.layout')

@section('title', 'Print BIR Form 2307')
@section('page-title', 'BIR Form 2307')

@php
    $amount = function ($value) {
        return abs((float) $value) < 0.005 ? '' : number_format((float) $value, 2);
    };
@endphp

@push('styles')
<style>
    .bir-2307-wrap { overflow: auto; }
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
    .bir-2307-background {
        position: absolute;
        inset: 0;
        width: 816px;
        height: 1248px;
        object-fit: fill;
        z-index: 0;
    }
    .bir-field {
        position: absolute;
        z-index: 1;
        overflow: hidden;
        white-space: nowrap;
        font-size: 10px;
        line-height: 18px;
    }
    .bir-field-center { text-align: center; }
    .bir-field-right { text-align: right; padding-right: 4px; }
    .bir-period-from { left: 202px; top: 141px; width: 141px; font-size: 12px; letter-spacing: 3px; }
    .bir-period-to { left: 532px; top: 141px; width: 141px; font-size: 12px; letter-spacing: 3px; }
    .bir-payee-tin { left: 276px; top: 181px; width: 307px; font-size: 12px; letter-spacing: 4px; }
    .bir-payee-name { left: 45px; top: 218px; width: 746px; font-size: 11px; }
    .bir-payee-address { left: 45px; top: 256px; width: 668px; font-size: 10px; }
    .bir-payee-zip { left: 721px; top: 257px; width: 68px; font-size: 12px; letter-spacing: 6px; }
    .bir-payer-tin { left: 277px; top: 336px; width: 305px; font-size: 12px; letter-spacing: 4px; }
    .bir-payer-name { left: 45px; top: 372px; width: 746px; font-size: 11px; }
    .bir-payer-address { left: 45px; top: 410px; width: 668px; font-size: 10px; }
    .bir-payer-zip { left: 721px; top: 411px; width: 68px; font-size: 12px; letter-spacing: 6px; }
    .bir-detail { height: 18px; line-height: 18px; font-size: 9px; }
    .bir-description { left: 27px; width: 208px; padding-left: 3px; }
    .bir-atc { left: 236px; width: 57px; text-align: center; font-weight: 600; }
    .bir-month-1 { left: 294px; width: 96px; }
    .bir-month-2 { left: 391px; width: 97px; }
    .bir-month-3 { left: 489px; width: 96px; }
    .bir-row-total { left: 585px; width: 96px; }
    .bir-row-tax { left: 681px; width: 113px; }
    .bir-grand-total { top: 670px; height: 18px; line-height: 18px; font-weight: 700; }
    @media print {
        @page { size: 8.5in 13in; margin: 0; }
        html, body { width: 8.5in; margin: 0 !important; padding: 0 !important; background: #fff !important; }
        .erp-sidebar, .erp-topbar, .no-print { display: none !important; }
        .erp-main { margin: 0 !important; }
        .erp-main > section { padding: 0 !important; }
        .bir-2307-wrap { overflow: visible; }
        .bir-2307-page {
            width: 8.5in;
            height: 13in;
            margin: 0;
            box-shadow: none;
            page-break-after: always;
            break-after: page;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .bir-2307-page:last-child { page-break-after: auto; break-after: auto; }
        .bir-2307-background { width: 8.5in; height: 13in; }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.payables.vendor-bills.view', $form['bill_no']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Bill</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print 2307</button>
    </div>

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
            <div class="bir-2307-page">
                <img src="{{ asset('images/bir_2307_bg.png') }}" class="bir-2307-background" alt="BIR Form 2307">

                <div class="bir-field bir-field-center bir-period-from">{{ $form['period_from'] }}</div>
                <div class="bir-field bir-field-center bir-period-to">{{ $form['period_to'] }}</div>
                <div class="bir-field bir-field-center bir-payee-tin">{{ $form['payee_tin'] }}</div>
                <div class="bir-field bir-payee-name">{{ $form['payee_name'] }}</div>
                <div class="bir-field bir-payee-address">{{ $form['payee_address'] }}</div>
                <div class="bir-field bir-field-center bir-payee-zip">{{ $form['payee_zip'] }}</div>
                <div class="bir-field bir-field-center bir-payer-tin">{{ $form['payer_tin'] }}</div>
                <div class="bir-field bir-payer-name">{{ $form['payer_name'] }}</div>
                <div class="bir-field bir-payer-address">{{ $form['payer_address'] }}</div>
                <div class="bir-field bir-field-center bir-payer-zip">{{ $form['payer_zip'] }}</div>

                @foreach($pageRows as $rowIndex => $row)
                    @php($rowTop = 488 + ($rowIndex * 18.25))
                    <div class="bir-field bir-detail bir-description" style="top: {{ $rowTop }}px">{{ $row['description'] }}</div>
                    <div class="bir-field bir-detail bir-atc" style="top: {{ $rowTop }}px">{{ $row['atc_code'] }}</div>
                    <div class="bir-field bir-detail bir-field-right bir-month-1" style="top: {{ $rowTop }}px">{{ $amount($row['months'][0]) }}</div>
                    <div class="bir-field bir-detail bir-field-right bir-month-2" style="top: {{ $rowTop }}px">{{ $amount($row['months'][1]) }}</div>
                    <div class="bir-field bir-detail bir-field-right bir-month-3" style="top: {{ $rowTop }}px">{{ $amount($row['months'][2]) }}</div>
                    <div class="bir-field bir-detail bir-field-right bir-row-total" style="top: {{ $rowTop }}px">{{ $amount(array_sum($row['months'])) }}</div>
                    <div class="bir-field bir-detail bir-field-right bir-row-tax" style="top: {{ $rowTop }}px">{{ $amount($row['tax_withheld']) }}</div>
                @endforeach

                <div class="bir-field bir-field-right bir-month-1 bir-grand-total">{{ $amount($pageMonths[0]) }}</div>
                <div class="bir-field bir-field-right bir-month-2 bir-grand-total">{{ $amount($pageMonths[1]) }}</div>
                <div class="bir-field bir-field-right bir-month-3 bir-grand-total">{{ $amount($pageMonths[2]) }}</div>
                <div class="bir-field bir-field-right bir-row-total bir-grand-total">{{ $amount(array_sum($pageMonths)) }}</div>
                <div class="bir-field bir-field-right bir-row-tax bir-grand-total">{{ $amount($pageTax) }}</div>
            </div>
        @endforeach
    </div>
@endsection
