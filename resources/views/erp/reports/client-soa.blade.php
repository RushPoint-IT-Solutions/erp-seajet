@extends('erp.layout')

@section('title', 'Statement of Account per Client')
@section('page-title', 'Statement of Account per Client')

@push('styles')
<style>
    .client-soa-sheet {
        width: 216mm;
        min-height: 279mm;
        margin: 0 auto;
        padding: 10mm 12mm 8mm;
        background: #fff;
        color: #000;
        border: 1px solid #d8dde6;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .08);
        font-family: Arial, Calibri, sans-serif;
        font-size: 10pt;
        line-height: 1.2;
    }
    .client-soa-header {
        position: relative;
        text-align: center;
        min-height: 96px;
        margin-bottom: 12px;
    }
    .client-soa-logo {
        position: absolute;
        left: 0;
        top: 0;
        width: 72px;
        height: 72px;
        object-fit: contain;
    }
    .client-soa-company {
        font-size: 10pt;
        line-height: 1.25;
        padding-top: 1px;
    }
    .client-soa-company strong {
        display: block;
        font-size: 14pt;
        line-height: 18pt;
        letter-spacing: .02em;
    }
    .client-soa-to {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) 148px;
        gap: 6px;
        min-height: 76px;
        margin-top: 2px;
        margin-bottom: 0;
        font-size: 10pt;
        line-height: 15pt;
    }
    .client-soa-date-box {
        align-self: center;
        border-bottom: 1px solid #000;
        text-align: center;
        padding-bottom: 2px;
        font-weight: 700;
    }
    .client-soa-title {
        text-align: center;
        margin: 0 0 29px;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .client-soa-title h2 {
        margin: 0;
        font-size: 12pt;
        line-height: 15.75pt;
        font-weight: 700;
    }
    .client-soa-title div {
        margin-top: 0;
        font-size: 10pt;
        line-height: 15pt;
        font-weight: 700;
    }
    .client-soa-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 10pt;
        margin-bottom: 0;
    }
    .client-soa-table th,
    .client-soa-table td {
        border: 1px solid #000;
        height: 15.75pt;
        padding: 1px 4px;
        vertical-align: middle;
    }
    .client-soa-table th {
        text-align: center;
        font-weight: 700;
    }
    .client-soa-table .amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .client-soa-table tfoot td {
        font-weight: 700;
    }
    .client-soa-bank {
        margin-top: 20px;
        margin-left: 44px;
        font-size: 10pt;
        font-weight: 700;
        line-height: 15pt;
    }
    .client-soa-bank strong {
        display: block;
        margin-bottom: 0;
    }
    .client-soa-overdue {
        margin-top: 24px;
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
    .client-soa-empty-row td {
        color: transparent;
    }
    @media print {
        @page { size: letter portrait; margin: 0; }
        body { background: #fff !important; }
        .no-print, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-shell, .erp-main, .erp-content, main, section {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .client-soa-sheet {
            border: 0;
            box-shadow: none;
            width: auto;
            min-height: auto;
            margin: 0;
        }
    }
</style>
@endpush

@section('content')
@php
    $companyName = $company && !empty($company->name) ? $company->name : 'SEA-JET INTERNATIONAL FORWARDERS, INC.';
    $address = $company && !empty($company->address) ? $company->address : 'Unit 18 & 19 Bldg. B. Cargo Village Complex';
    $money = function ($amount) {
        return number_format((float) $amount, 2);
    };
    $totalBalance = array_sum($currencyTotals);
    $invoiceRows = $invoices->count();
    $blankRows = max(0, 16 - $invoiceRows);
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

    <div class="client-soa-sheet">
        <div class="client-soa-header">
            <img src="{{ asset('images/gen_off_seajetlogo.webp') }}" class="client-soa-logo" alt="SeaJet logo">
            <div class="client-soa-company">
                <strong>{{ $companyName }}</strong>
                <div>Unit 18 &amp; 19 Bldg. B. Cargo Village Complex</div>
                <div>Ninoy Aquino Ave. Sto. Nino, Paranaque City, 1704, Philippines</div>
                <div>Tel. Nos. (632) 8529001 up to 8529004; 8529006</div>
                <div>Fax No.: (632) 8529007</div>
                <div>E-mail Address: info@seajetintl.ph Website: www.seajetintl.ph</div>
            </div>
        </div>

        <div class="client-soa-to">
            <strong>TO:</strong>
            <div>
                <strong>{{ $customer->name ?? 'No client with unpaid active invoices' }}</strong><br>
                {!! nl2br(e($customer->billing_address ?? '')) !!}
            </div>
            <div class="client-soa-date-box">{{ date('m/d/Y', strtotime($asOfDate)) }}</div>
        </div>

        <div class="client-soa-title">
            <h2>STATEMENT OF ACCOUNT</h2>
            <div>AS OF {{ strtoupper(date('F d, Y', strtotime($asOfDate))) }}</div>
        </div>

        <table class="client-soa-table">
            <colgroup>
                <col style="width: 11.4%">
                <col style="width: 14.6%">
                <col style="width: 12.4%">
                <col style="width: 10.4%">
                <col style="width: 15%">
                <col style="width: 13.6%">
                <col style="width: 22.6%">
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
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->document_date ? date('m/d/Y', strtotime($invoice->document_date)) : '' }}</td>
                    <td>{{ $invoice->document_no }}</td>
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
            <tfoot>
            <tr>
                <td></td>
                <td class="text-end">TOTAL</td>
                <td class="amount">{{ $money($invoices->sum('invoice_amount')) }}</td>
                <td></td>
                <td class="amount">{{ $money($invoices->sum('soa_amount')) }}</td>
                <td class="amount">{{ $money($totalBalance) }}</td>
                <td></td>
            </tr>
            </tfoot>
        </table>

        <div class="client-soa-bank">
            <strong>BANK DETAILS:</strong>
            @foreach($bankDetails as $line)
                <div>{{ $line }}</div>
            @endforeach
        </div>

        <div class="client-soa-overdue">
            <div class="client-soa-overdue-title">OVERDUE ACCOUNTS</div>
            @forelse($currencyTotals as $currency => $amount)
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
    </div>
@endsection
