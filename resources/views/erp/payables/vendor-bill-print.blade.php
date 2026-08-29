@extends('erp.layout')

@section('title', 'Print Vendor Bill')
@section('page-title', 'Print Vendor Bill')

@php
    $money = function ($amount) {
        return number_format((float) $amount, 2);
    };
    $printLines = collect($lines);
    $expenseAmount = function ($line) use ($bill) {
        $amount = (float) ($line->amount ?? $line->line_total ?? 0);
        $taxCategory = strtoupper(trim((string) ($line->tax_category ?? '')));

        return empty($bill['line_amounts_exclude_vat']) && in_array($taxCategory, ['VAT12', '12%'], true)
            ? $amount / 1.12
            : $amount;
    };
    $accountName = function ($line) {
        if (trim((string) ($line->account_master_name ?? '')) !== '') {
            return $line->account_master_name;
        }

        return preg_replace('/^[^\s]+\s+-\s+/', '', (string) ($line->account_name ?? ''), 1);
    };
    $expenseTotal = $printLines->isEmpty()
        ? max(0, (float) $bill['gross_amount'] - (float) $bill['input_vat'] - (float) $bill['withholding_tax_amount'])
        : $printLines->sum($expenseAmount) - (float) $bill['withholding_tax_amount'];
    $billTotal = $expenseTotal + (float) $bill['input_vat'];
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
        min-height: 190px;
        text-align: center;
    }
    .bill-print-brand {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: center;
        padding-top: 2px;
    }
    .bill-print-logo {
        width: 250px;
        height: 110px;
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
    .bill-meta-stack {
        display: grid;
        gap: 10px;
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
    .bill-meta-line > div:first-child {
        white-space: nowrap;
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
    .bill-num { text-align: right; }
    .bill-class {
        font-size: 10px;
        overflow-wrap: anywhere;
    }
    .bill-total-line {
        margin-top: 10px;
        text-align: right;
        font-size: 12px;
        color: #4b5563;
    }
    .bill-expense-total,
    .bill-vat-total {
        margin-top: 8px;
        text-align: right;
        font-size: 12px;
    }
    .bill-vat-total { margin-top: 4px; }
    .bill-total {
        margin-top: 14px;
        text-align: right;
        font-size: 23px;
        font-weight: 700;
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
            width: calc(8.14in - 28px);
            height: calc(10.64in - 28px);
            min-height: 0;
            max-height: calc(10.64in - 28px);
            overflow: hidden;
            border: 0;
            box-shadow: none;
            margin: 28px 0 0 28px;
            padding: 0;
            font-family: "Courier New", "Lucida Console", monospace;
            font-size: 14px;
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
            min-height: 150px;
            text-align: center;
        }
        .bill-print-logo {
            display: block;
            width: 320px;
            height: 115px;
            margin: 0 auto;
        }
        .bill-print-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            padding-top: 0;
        }
        .bill-print-company {
            text-align: center;
            line-height: 1.1;
        }
        .bill-print-company-name { font-size: 16px; }
        .bill-print-company-meta { font-size: 13px; }
        .bill-print-form-title {
            margin-top: 8px;
            text-align: center;
            font-size: 17px;
        }
        .bill-small-grid,
        .bill-box,
        .bill-meta-box,
        .bill-expense-table {
            border-color: #000;
        }
        .bill-small-grid {
            grid-template-columns: 44% 56%;
            font-size: 12px;
        }
        .bill-small-grid > div {
            min-height: 28px;
            padding: 5px 6px;
            border-color: #000;
            line-height: 1.2;
            white-space: nowrap;
        }
        .bill-meta-stack > .bill-small-grid {
            width: 50%;
            margin: 0 0 0 auto;
        }
        .bill-info-row {
            grid-template-columns: 260px 1fr;
            gap: 22px;
            margin-top: 8px;
            align-items: stretch;
        }
        .bill-meta-stack { gap: 6px; }
        .bill-box {
            height: 100%;
            min-height: 0;
        }
        .bill-box-label {
            padding: 3px 5px;
            border-color: #000;
        }
        .bill-box-body {
            padding: 4px 5px;
            font-family: "Courier New", "Lucida Console", monospace;
            font-size: 14px;
            line-height: 1.08;
        }
        .bill-meta-box {
            min-height: 76px;
            padding: 4px 5px;
        }
        .bill-meta-line {
            margin-bottom: 5px;
            grid-template-columns: 76px 1fr;
            gap: 8px;
        }
        .bill-title {
            margin: 8px 0 5px;
            font-size: 19px;
        }
        .bill-expense-table th,
        .bill-expense-table td {
            padding: 2px 4px;
            border-color: #000;
        }
        .bill-expense-table tbody td {
            height: 18px;
        }
        .bill-class { font-size: 12px; }
        .bill-expense-total,
        .bill-vat-total {
            margin-top: 4px;
            font-size: 13px;
        }
        .bill-total {
            margin-top: 5px;
            font-size: 17px;
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

            <div class="bill-info-row">
                <div class="bill-box">
                    <div class="bill-box-label">Vendor</div>
                    <div class="bill-box-body">{{ $bill['vendor'] }}@if(!empty($bill['vendor_address'])){{ "\n".$bill['vendor_address'] }}@endif</div>
                </div>
                <div class="bill-meta-stack">
                    <div class="bill-small-grid">
                        <div>Date</div>
                        <div>Ref. No.</div>
                        <div>{{ $bill['date'] }}</div>
                        <div>{{ $bill['ref_no'] }}</div>
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
            </div>

            <div class="bill-title">Expenses</div>

            <table class="bill-expense-table">
                <thead>
                    <tr>
                        <th style="width: 31%;">Account</th>
                        <th style="width: 18%;">Amount</th>
                        <th style="width: 36%;">Customer:Job</th>
                        <th style="width: 15%;">Class</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td>{{ $accountName($line) }}</td>
                            <td class="bill-num">{{ $money($expenseAmount($line)) }}</td>
                            <td>{{ $line->customer_job_name ?? '' }}</td>
                            <td class="bill-class">{{ $line->class_code ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>Reimbursable charges</td>
                            <td class="bill-num">{{ $money($expenseTotal) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforelse
                    @if($bill['withholding_tax_amount'] > 0)
                        <tr>
                            <td>Expanded Withholding Tax Payable</td>
                            <td class="bill-num">-{{ $money($bill['withholding_tax_amount']) }}</td>
                            <td></td>
                            <td class="bill-class"></td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div class="bill-expense-total">Expense Total : {{ $money($expenseTotal) }}</div>
            <div class="bill-vat-total">VAT 12% : {{ $money($bill['input_vat']) }}</div>
            <div class="bill-total">Bill Total :{{ $bill['currency'] }} {{ $money($billTotal) }}</div>
        </div>
    </div>
@endsection
