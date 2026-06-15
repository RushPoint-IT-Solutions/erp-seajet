@extends('erp.layout')

@section('title', 'Trial Balance')
@section('page-title', 'Trial Balance')

@push('styles')
<style>
    .trial-paper {
        background: #fff;
        border: 1px solid #d8dde6;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .08);
        color: #111;
    }
    .trial-header {
        text-align: center;
        line-height: 1.2;
        padding: 22px 18px 12px;
        font-family: "Times New Roman", Times, serif;
    }
    .trial-company {
        font-weight: 700;
        font-size: 14px;
        text-transform: uppercase;
    }
    .trial-title {
        font-weight: 700;
        font-size: 18px;
        text-transform: uppercase;
    }
    .trial-subtitle {
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
    }
    .trial-table {
        min-width: 1080px;
        font-size: 12px;
        font-family: Arial, sans-serif;
    }
    .trial-table th {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }
    .trial-table td {
        border: 1px solid #e2e8f0;
        vertical-align: top;
        white-space: nowrap;
    }
    .trial-table .amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .trial-table tfoot td {
        border-top: 2px solid #111827;
        font-weight: 800;
        background: #fff7ed;
    }
    @media print {
        @page { size: A4 landscape; margin: 8mm; }
        .no-print, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-shell, .erp-main, main, section {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .trial-paper {
            border: 0;
            box-shadow: none;
        }
    }
</style>
@endpush

@section('content')
@php
    $companyName = $company && !empty($company->name) ? $company->name : 'SEA-JET INTERNATIONAL FORWARDERS, INC.';
    $money = function ($value) {
        if (!is_numeric($value)) {
            return $value;
        }
        $amount = (float) $value;
        if (abs($amount) < .005) {
            return '-';
        }
        return $amount < 0 ? '(' . number_format(abs($amount), 2) . ')' : number_format($amount, 2);
    };
    $periodLabel = preg_match('/^\d{4}-\d{2}$/', $period) ? strtoupper(date('F t, Y', strtotime($period . '-01'))) : strtoupper($period);
    $columns = $activeReport['columns'];
@endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
        <div>
            <h1 class="h3 fw-bold mb-1">Trial Balance</h1>
            <p class="text-muted mb-0">Format copied from REPORTS.xlsx, account titles from Chart of Accounts.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Reports</a>
            <a href="{{ route('erp.reports.workbook', ['report' => 'trial-balance', 'period' => $period]) }}" class="btn btn-outline-secondary"><i class="bi bi-folder-check me-1"></i>Workbook</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <form class="erp-card p-3 mb-3 no-print" method="get" action="{{ route('erp.reports.trial-balance') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Period</label>
                <input type="month" name="period" class="form-control" value="{{ $period }}">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </div>
    </form>

    <div class="trial-paper">
        <div class="trial-header">
            <div class="trial-company">{{ $companyName }}</div>
            <div class="trial-title">TRIAL BALANCE</div>
            <div class="trial-subtitle">AS OF {{ $periodLabel }}</div>
        </div>

        <div class="table-responsive">
            <table class="table trial-table mb-0">
                <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @if(count($rows))
                    @foreach($rows as $row)
                        <tr>
                            @foreach($columns as $column)
                                @php($value = $row[$column] ?? '')
                                <td class="{{ is_numeric($value) ? 'amount' : '' }}">{{ is_numeric($value) ? $money($value) : $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">
                            No chart of accounts found.
                        </td>
                    </tr>
                @endif
                </tbody>
                @if(count($rows))
                    <tfoot>
                    <tr>
                        @foreach($columns as $column)
                            @php($value = $totals[$column] ?? '')
                            <td class="{{ is_numeric($value) ? 'amount' : '' }}">{{ is_numeric($value) ? $money($value) : $value }}</td>
                        @endforeach
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
