@extends('erp.layout')

@section('title', 'Print Vendor Bill')
@section('page-title', 'Print Vendor Bill')

@php
    $money = function ($amount) {
        return number_format((float) $amount, 2);
    };
@endphp

@push('styles')
<style>
    .bill-print-wrap { overflow: auto; }
    .bill-print-sheet {
        width: 816px;
        min-height: 1056px;
        margin: 0 auto;
        padding: 72px 58px 56px;
        background: #fff;
        color: #111827;
        border: 1px solid #d7dce5;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        font-family: Arial, sans-serif;
        font-size: 13px;
    }
    .bill-top-grid {
        display: grid;
        grid-template-columns: 1fr 330px;
        gap: 52px;
        align-items: start;
        min-height: 172px;
    }
    .bill-print-brand {
        display: grid;
        grid-template-columns: 100px 1fr;
        gap: 16px;
        align-items: center;
        padding-top: 2px;
    }
    .bill-print-logo {
        width: 90px;
        height: 56px;
        object-fit: contain;
    }
    .bill-print-company {
        text-align: center;
        line-height: 1.18;
    }
    .bill-print-company-name {
        font-size: 15px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .bill-print-company-meta {
        font-size: 10px;
        font-weight: 600;
    }
    .bill-print-form-title {
        margin-top: 24px;
        text-align: center;
        font-size: 18px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .bill-small-grid {
        display: grid;
        grid-template-columns: 88px 1fr;
        border: 1px solid #111827;
    }
    .bill-small-grid > div {
        min-height: 34px;
        padding: 8px 10px;
        border-right: 1px solid #111827;
        border-bottom: 1px solid #111827;
    }
    .bill-small-grid > div:nth-child(2n) { border-right: 0; text-align: center; }
    .bill-small-grid > div:nth-last-child(-n+2) { border-bottom: 0; }
    .bill-info-row {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 52px;
        margin-top: 18px;
        align-items: start;
    }
    .bill-box {
        border: 1px solid #111827;
        min-height: 126px;
    }
    .bill-box-label {
        border-bottom: 1px solid #111827;
        padding: 7px 8px;
    }
    .bill-box-body {
        padding: 8px;
        white-space: pre-line;
        font-family: "Times New Roman", serif;
        font-size: 17px;
        line-height: 1.12;
    }
    .bill-meta-box {
        border: 1px solid #111827;
        min-height: 142px;
        padding: 8px;
    }
    .bill-meta-line {
        display: grid;
        grid-template-columns: 70px 1fr;
        gap: 14px;
        margin-bottom: 18px;
    }
    .bill-title {
        margin: 30px 0 16px;
        font-size: 31px;
        font-weight: 500;
    }
    .bill-expense-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        border: 1px solid #111827;
    }
    .bill-expense-table th,
    .bill-expense-table td {
        border-right: 1px solid #111827;
        vertical-align: top;
        padding: 7px 8px;
    }
    .bill-expense-table th {
        border-bottom: 1px solid #111827;
        text-align: center;
        font-weight: 400;
    }
    .bill-expense-table th:last-child,
    .bill-expense-table td:last-child { border-right: 0; }
    .bill-expense-table tbody td {
        height: 34px;
    }
    .bill-expense-table tbody tr.filler td {
        height: 430px;
    }
    .bill-num { text-align: right; }
    .bill-total-line {
        margin-top: 10px;
        text-align: right;
        font-size: 12px;
        color: #4b5563;
    }
    .bill-total {
        margin-top: 14px;
        text-align: right;
        font-size: 23px;
        font-weight: 500;
    }
    @media print {
        @page { size: 8.5in 11in; margin: 0.18in; }
        html, body {
            width: 8.5in;
            height: 11in;
            overflow: hidden;
        }
        .erp-sidebar, .erp-topbar, .no-print { display: none !important; }
        .erp-main { margin: 0 !important; }
        .erp-main > section { padding: 0 !important; }
        body {
            background: #fff !important;
            color: #000 !important;
            -webkit-print-color-adjust: economy;
            print-color-adjust: economy;
        }
        .bill-print-wrap { overflow: hidden; }
        .bill-print-sheet {
            width: 8.14in;
            height: 10.64in;
            min-height: 0;
            max-height: 10.64in;
            overflow: hidden;
            border: 0;
            box-shadow: none;
            margin: 0;
            padding: 0;
            font-family: "Courier New", "Lucida Console", monospace;
            font-size: 10px;
            line-height: 1.12;
            color: #000 !important;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        .bill-print-sheet * {
            color: #000 !important;
            box-shadow: none !important;
        }
        .bill-top-grid {
            min-height: 0;
            gap: 22px;
            grid-template-columns: 1fr 250px;
        }
        .bill-print-logo {
            display: none;
        }
        .bill-print-brand {
            display: block;
            padding-top: 0;
        }
        .bill-print-company {
            text-align: left;
            line-height: 1.1;
        }
        .bill-print-company::before {
            content: "SEA-JET";
            display: block;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .bill-print-company-name { font-size: 12px; }
        .bill-print-company-meta { font-size: 9px; }
        .bill-print-form-title {
            margin-top: 8px;
            text-align: left;
            font-size: 13px;
        }
        .bill-small-grid,
        .bill-box,
        .bill-meta-box,
        .bill-expense-table {
            border-color: #000;
        }
        .bill-small-grid > div {
            min-height: 20px;
            padding: 3px 5px;
            border-color: #000;
        }
        .bill-info-row {
            grid-template-columns: 260px 1fr;
            gap: 22px;
            margin-top: 8px;
        }
        .bill-box { min-height: 76px; }
        .bill-box-label {
            padding: 3px 5px;
            border-color: #000;
        }
        .bill-box-body {
            padding: 4px 5px;
            font-family: "Courier New", "Lucida Console", monospace;
            font-size: 10px;
            line-height: 1.08;
        }
        .bill-meta-box {
            min-height: 76px;
            padding: 4px 5px;
        }
        .bill-meta-line {
            margin-bottom: 5px;
            grid-template-columns: 58px 1fr;
            gap: 8px;
        }
        .bill-title {
            margin: 8px 0 5px;
            font-size: 15px;
        }
        .bill-expense-table th,
        .bill-expense-table td {
            padding: 2px 4px;
            border-color: #000;
        }
        .bill-expense-table tbody td {
            height: 18px;
        }
        .bill-expense-table tbody tr.filler td {
            height: 200px;
        }
        .bill-total-line {
            margin-top: 4px;
            font-size: 9px;
        }
        .bill-total {
            margin-top: 5px;
            font-size: 13px;
        }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.payables.vendor-bills.view', $bill['bill_no']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Bill</a>
        <a href="{{ route('erp.payables.vendor-bills.print-2307', $bill['bill_no']) }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-text me-1"></i> Print 2307</a>
        <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Bill</button>
    </div>

    <div class="bill-print-wrap">
        <div class="bill-print-sheet">
            <div class="bill-top-grid">
                <div>
                    <div class="bill-print-brand">
                        <img src="{{ asset('images/gen_off_seajetlogo.webp') }}" class="bill-print-logo" alt="SeaJet logo">
                        <div class="bill-print-company">
                            <div class="bill-print-company-name">Sea-Jet International Forwarders, Inc.</div>
                            <div class="bill-print-company-meta">Unit 18 & 19 Bldg. B, Cargo Village Complex</div>
                            <div class="bill-print-company-meta">Paranaque City 1704, Philippines</div>
                        </div>
                    </div>
                    <div class="bill-print-form-title">Vendor Bill</div>
                </div>
                <div class="bill-small-grid">
                    <div>Date</div>
                    <div>Ref. No.</div>
                    <div>{{ $bill['date'] }}</div>
                    <div>{{ $bill['ref_no'] }}</div>
                </div>
            </div>

            <div class="bill-info-row">
                <div class="bill-box">
                    <div class="bill-box-label">Vendor</div>
                    <div class="bill-box-body">{{ $bill['vendor'] }}@if(!empty($bill['vendor_address'])){{ "\n".$bill['vendor_address'] }}@endif</div>
                </div>
                <div class="bill-meta-box">
                    <div class="bill-meta-line">
                        <div>Bill Due</div>
                        <div>{{ $bill['bill_due'] }}</div>
                    </div>
                    <div class="bill-meta-line">
                        <div>Terms</div>
                        <div>{{ $bill['terms'] }}</div>
                    </div>
                    <div class="bill-meta-line">
                        <div>Memo</div>
                        <div>{{ $bill['memo'] }}</div>
                    </div>
                </div>
            </div>

            <div class="bill-title">Expenses</div>

            <table class="bill-expense-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Account</th>
                        <th style="width: 19%;">Memo</th>
                        <th style="width: 10%;">Tax</th>
                        <th style="width: 9%;">ATC/WC</th>
                        <th style="width: 13%;">Amount</th>
                        <th style="width: 15%;">Customer:Job</th>
                        <th style="width: 7%;">Class</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td>{{ $line->account_name ?? '' }}</td>
                            <td>{{ $line->memo ?? $line->description ?? '' }}</td>
                            <td>{{ $line->tax_category ?? '' }}</td>
                            <td>{{ ($line->atc_code ?? '') ?: ($line->account_atc_code ?? '') }}</td>
                            <td class="bill-num">{{ $money($line->amount ?? $line->line_total ?? 0) }}</td>
                            <td>{{ $line->customer_job_id ? $line->customer_job_id.' - '.($line->customer_job_name ?? '') : '' }}</td>
                            <td>{{ $line->class_code ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>Reimbursable charges</td>
                            <td>{{ $bill['memo'] }}</td>
                            <td></td>
                            <td></td>
                            <td class="bill-num">{{ $money($bill['total']) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforelse
                    <tr class="filler">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="bill-total-line">Gross Amount (VAT Inclusive) : {{ $money($bill['gross_amount']) }}</div>
            @if($bill['input_vat'] > 0)
                <div class="bill-total-line">Input VAT (included above) : {{ $money($bill['input_vat']) }}</div>
            @endif
            @if($bill['withholding_tax_amount'] > 0)
                <div class="bill-total-line">Less: Withholding Tax ({{ rtrim(rtrim(number_format($bill['withholding_tax_rate'], 2), '0'), '.') }}%) : ({{ $money($bill['withholding_tax_amount']) }})</div>
            @endif
            <div class="bill-total">Bill Total :{{ $bill['currency'] }} {{ $money($bill['total']) }}</div>
        </div>
    </div>
@endsection
