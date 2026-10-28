@extends('erp.layout')

@section('title', 'Statement of Account per Client')
@section('page-title', 'Statement of Account per Client')

@push('styles')
<style>
    @page { size: letter portrait; margin: 0; }
    .client-soa-sheet {
        position: relative;
        width: 215.9mm;
        min-height: 279.4mm;
        margin: 0 auto;
        padding: 51mm 25mm 39mm;
        background: #fff url('{{ asset('images/background.jpg') }}') center / 100% 100% no-repeat;
        color: #000;
        border: 1px solid #d8dde6;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .08);
        font-family: Arial, Calibri, sans-serif;
        font-size: 10pt;
        line-height: 1.2;
        box-sizing: border-box;
        margin-bottom: 24px;
    }
    .client-soa-letterhead {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: fill;
        pointer-events: none;
        user-select: none;
        z-index: 0;
    }
    .client-soa-sheet > :not(.client-soa-letterhead) {
        position: relative;
        z-index: 1;
    }
    .client-soa-sheet::before {
        content: '';
        position: absolute;
        inset: 40.5mm 0 55mm;
        background: #fff;
        z-index: 1;
    }
    .client-soa-to {
        display: grid;
        grid-template-columns: 9mm minmax(0, 1fr) 45mm;
        gap: 6px;
        min-height: 21mm;
        margin-top: 2px;
        margin-bottom: 0;
        font-size: 9pt;
        line-height: 11pt;
    }
    .client-soa-date-box {
        align-self: center;
        display: flex;
        gap: 5mm;
        text-align: center;
        padding-bottom: 2px;
        font-weight: 700;
    }
    .client-soa-date-box span { flex: 1; border-bottom: 1px solid #000; }
    .client-soa-title {
        text-align: center;
        margin: 0 0 7mm;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .client-soa-title h2 {
        margin: 0;
        font-size: 10pt;
        line-height: 15.75pt;
        font-weight: 700;
    }
    .client-soa-title div {
        margin-top: 0;
        font-size: 9pt;
        line-height: 15pt;
        font-weight: 700;
    }
    .client-soa-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 9pt;
        border: 1px solid #000;
        margin-bottom: 0;
    }
    .client-soa-table th,
    .client-soa-table td {
        border: 1px solid #000;
        height: 7.8mm;
        padding: 1px 4px;
        vertical-align: middle;
    }
    .client-soa-table th {
        text-align: center;
        font-weight: 700;
        height: 5mm;
    }
    .client-soa-table tbody td { border-top: 0; border-bottom: 0; }
    .client-soa-table td:last-child { text-align: center; }
    .client-soa-table .amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .client-soa-table tfoot td {
        font-weight: 700;
    }
    .client-soa-bank {
        margin-top: 12px;
        margin-left: 0;
        font-size: 9pt;
        font-weight: 400;
        line-height: 11pt;
    }
    .client-soa-bank strong {
        display: block;
        margin-bottom: 0;
    }
    .client-soa-overdue {
        margin-top: 12px;
        width: 236px;
        font-size: 10pt;
        line-height: 15.75pt;
    }
    .client-soa-overdue-title {
        font-weight: 700;
        margin-bottom: 0;
    }
    .client-soa-overdue-row {
        display: grid;
        grid-template-columns: 74px 1fr;
        gap: 10px;
        min-height: 15.75pt;
        padding: 0;
    }
    .client-soa-page-number { position: absolute !important; right: 25mm; bottom: 43mm; font-size: 8pt; }
    .client-soa-preview { overflow-x: auto; }
    .client-soa-empty-row td {
        color: transparent;
    }
    @media print {
        html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
        .no-print, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-shell, .erp-main, .erp-content, .erp-main > section {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .client-soa-sheet {
            border: 0;
            box-shadow: none;
            width: 215.9mm;
            min-height: 279.4mm;
            height: 279.4mm;
            margin: 0 !important;
            padding-top: 51mm;
            background: #fff url('{{ asset('images/background.jpg') }}') center / 100% 100% no-repeat !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .client-soa-sheet::before { inset: 40.5mm 0 55mm; }
        .client-soa-sheet { break-after: page; page-break-after: always; }
        .client-soa-sheet:last-child { break-after: auto; page-break-after: auto; }
        .client-soa-preview { overflow: visible; }
    }
</style>
@endpush

@section('content')
@php
    $money = function ($amount) {
        return number_format((float) $amount, 2);
    };
    $totalBalance = array_sum($currencyTotals);
    $invoiceRows = $invoices->count();
    $pages = $invoices->isEmpty() ? collect([collect()]) : $invoices->chunk(12);
@endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
        <div>
            <h1 class="h3 fw-bold mb-1">Statement of Account per Client</h1>
            <p class="text-muted mb-0">Active unpaid invoices grouped into a printable client SOA.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Reports</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <form class="erp-card p-3 mb-4 no-print" method="get" action="{{ route('erp.reports.client-soa') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Client</label>
                <select name="customer_id" class="form-select erp-select">
                    @foreach($customers as $option)
                        <option value="{{ $option->id }}" {{ (string) $customerId === (string) $option->id ? 'selected' : '' }}>
                            {{ $option->name }} - {{ $option->open_count }} open / PHP {{ number_format((float) $option->open_balance, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">As of Date</label>
                <input type="date" name="as_of" class="form-control" value="{{ $asOfDate }}">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Generate SOA</button>
            </div>
        </div>
    </form>

    <div class="client-soa-preview">
    @foreach($pages as $pageIndex => $pageInvoices)
    @php $blankRows = max(0, 12 - max(1, $pageInvoices->count())); @endphp
    <div class="client-soa-sheet">
        <img src="{{ asset('images/background.jpg') }}" class="client-soa-letterhead" alt="" aria-hidden="true">

        <div class="client-soa-to">
            <strong>TO:</strong>
            <div>
                <strong>{{ $customer->name ?? 'No client with unpaid active invoices' }}</strong><br>
                {!! nl2br(e($customer->billing_address ?? '')) !!}
            </div>
            <div class="client-soa-date-box"><strong>DATE:</strong><span>{{ date('j-M-y', strtotime($asOfDate)) }}</span></div>
        </div>

        <div class="client-soa-title">
            <h2>STATEMENT OF ACCOUNT</h2>
            <div>AS OF {{ strtoupper(date('F d, Y', strtotime($asOfDate))) }}</div>
        </div>

        <table class="client-soa-table">
            <colgroup>
                <col style="width: 11.4%">
                <col style="width: 11%">
                <col style="width: 17%">
                <col style="width: 9%">
                <col style="width: 15%">
                <col style="width: 17%">
                <col style="width: 19.6%">
            </colgroup>
            <thead>
            <tr>
                <th>DATE</th>
                <th>INV#</th>
                <th>INV AMT</th>
                <th>SOA#</th>
                <th>SOA AMT</th>
                <th>TOTAL</th>
                <th>AGING</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pageInvoices as $invoice)
                <tr>
                    <td>{{ $invoice->document_date ? date('j M y', strtotime($invoice->document_date)) : '' }}</td>
                    <td>{{ $invoice->invoice_amount > 0 ? preg_replace('/^BI(?:-(?:MNL|PAM|CEB))?-(?=\d+$)/', '', $invoice->document_no) : '' }}</td>
                    <td class="amount">{{ $invoice->invoice_amount > 0 ? $money($invoice->invoice_amount) : '-' }}</td>
                    <td>{{ $invoice->soa_no }}</td>
                    <td class="amount">{{ $invoice->soa_amount > 0 ? $money($invoice->soa_amount) : '-' }}</td>
                    <td class="amount">{{ $money($invoice->total) }}</td>
                    <td>{{ $invoice->aging }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No active unpaid invoices found.</td>
                </tr>
            @endforelse
            @for($i = 0; $i < $blankRows; $i++)
                <tr class="client-soa-empty-row">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
            </tbody>
            @if($pageIndex === $pages->count() - 1)
            <tfoot>
            <tr>
                <td colspan="2" class="text-center">TOTAL</td>
                <td class="amount">{{ $money($invoices->sum('invoice_amount')) }}</td>
                <td></td>
                <td class="amount">{{ $money($invoices->sum('soa_amount')) }}</td>
                <td class="amount">{{ $money($invoices->sum('total')) }}</td>
                <td></td>
            </tr>
            </tfoot>
            @endif
        </table>

        @if($pageIndex === $pages->count() - 1)
        <div class="client-soa-bank">
            <strong>BANK DETAILS:</strong>
            @foreach($bankDetails as $line)
                <div>{{ $line }}</div>
            @endforeach
        </div>
        @endif

        @if($pageIndex === $pages->count() - 1)
        <div class="client-soa-overdue">
            <div class="client-soa-overdue-title">OVERDUE ACCOUNTS</div>
            @forelse($overdueCurrencyTotals as $currency => $amount)
                <div class="client-soa-overdue-row">
                    <strong>{{ $currency }}</strong>
                    <span class="text-end">{{ $money($amount) }}</span>
                </div>
            @empty
                <div class="client-soa-overdue-row">
                    <strong>PHP</strong>
                    <span class="text-end">0.00</span>
                </div>
            @endforelse
        </div>
        @endif
        @if($pages->count() > 1)<div class="client-soa-page-number">Page {{ $pageIndex + 1 }} of {{ $pages->count() }}</div>@endif
    </div>
    @endforeach
    </div>
@endsection
