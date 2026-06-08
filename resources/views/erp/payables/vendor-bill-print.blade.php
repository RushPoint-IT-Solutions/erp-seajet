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
        .erp-sidebar, .erp-topbar, .no-print { display: none !important; }
        .erp-main { margin: 0 !important; }
        .erp-main > section { padding: 0 !important; }
        body { background: #fff !important; }
        .bill-print-sheet {
            width: auto;
            min-height: auto;
            border: 0;
            box-shadow: none;
            margin: 0;
            padding: 72px 58px 56px;
        }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.payables.vendor-bills.view', $bill['bill_no']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Bill</a>
        <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Bill</button>
    </div>

    <div class="bill-print-wrap">
        <div class="bill-print-sheet">
            <div class="bill-top-grid">
                <div></div>
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
                        <th style="width: 24%;">Account</th>
                        <th style="width: 24%;">Memo</th>
                        <th style="width: 15%;">Amount</th>
                        <th style="width: 24%;">Customer:Job</th>
                        <th style="width: 13%;">Class</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td>{{ $line->account_name ?? '' }}</td>
                            <td>{{ $line->memo ?? $line->description ?? '' }}</td>
                            <td class="bill-num">{{ $money($line->amount ?? $line->line_total ?? 0) }}</td>
                            <td>{{ $line->customer_job_id ?? '' }}</td>
                            <td>{{ $line->class_code ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>Reimbursable charges</td>
                            <td>{{ $bill['memo'] }}</td>
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
                    </tr>
                </tbody>
            </table>

            <div class="bill-total-line">Expense Total : {{ $money($bill['total']) }}</div>
            <div class="bill-total">Bill Total :{{ $bill['currency'] }} {{ $money($bill['total']) }}</div>
        </div>
    </div>
@endsection
