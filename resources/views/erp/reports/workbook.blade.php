@extends('erp.layout')

@section('title', 'Workbook Reports')
@section('page-title', 'Workbook Reports')

@push('styles')
<style>
    .report-tabs {
        overflow-x: auto;
        white-space: nowrap;
    }
    .report-tabs .nav-link {
        border-radius: 6px;
        color: #334155;
        font-weight: 600;
    }
    .report-tabs .nav-link.active {
        background: #1d4ed8;
        color: #fff;
    }
    .workbook-paper {
        background: #fff;
        border: 1px solid #d8dde6;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .08);
        color: #111;
    }
    .workbook-header {
        text-align: center;
        line-height: 1.2;
        padding: 22px 18px 12px;
        font-family: "Times New Roman", Times, serif;
    }
    .workbook-company {
        font-weight: 700;
        font-size: 14px;
        text-transform: uppercase;
    }
    .workbook-title {
        font-weight: 700;
        font-size: 18px;
        text-transform: uppercase;
    }
    .workbook-subtitle {
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
    }
    .workbook-table {
        min-width: 980px;
        font-size: 12px;
        font-family: Arial, sans-serif;
    }
    .workbook-table th {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
    }
    .workbook-table td {
        border: 1px solid #e2e8f0;
        vertical-align: top;
        white-space: nowrap;
    }
    .workbook-table .amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .sales-history-link { color: #1d4ed8; text-decoration: underline; }
    @media print {
        .sales-history-link { color: inherit; text-decoration: none; }
        @page { size: A4 landscape; margin: 8mm; }
        .no-print, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-shell, .erp-main, .erp-content, main, section {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .workbook-paper {
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
@endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
        <div>
            <h1 class="h3 fw-bold mb-1">Workbook Reports</h1>
            <p class="text-muted mb-0">Report tabs copied from REPORTS.xlsx and connected to live ERP data.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Reports</a>
            <a href="{{ route('erp.reports.workbook-excel', ['report' => $report, 'period' => $period]) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Download Excel</a>
            <a href="{{ route('erp.reports.workbook-excel', ['report' => 'all', 'period' => $period]) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Download All</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="row g-3 mb-4 no-print">
        @foreach($summaryCards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="erp-card kpi-card p-3 h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h4 fw-bold mb-0">{{ $card['value'] }}</div>
                        </div>
                        <span class="kpi-icon bg-{{ $card['tone'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <form class="erp-card p-3 mb-3 no-print" method="get" action="{{ route('erp.reports.workbook', $report) }}">
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

    <div class="erp-card p-2 mb-3 no-print">
        <div class="report-tabs">
            <ul class="nav nav-pills flex-nowrap gap-1">
                @foreach($reports as $slug => $item)
                    <li class="nav-item">
                        <a class="nav-link {{ $slug === $report ? 'active' : '' }}" href="{{ route('erp.reports.workbook', ['report' => $slug, 'period' => $period]) }}">
                            <i class="bi {{ $item['icon'] }} me-1"></i>{{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="workbook-paper">
        <div class="workbook-header">
            <div class="workbook-company">{{ $companyName }}</div>
            <div class="workbook-title">{{ $activeReport['title'] }}</div>
            <div class="workbook-subtitle">
                @if(in_array($report, ['bank-recon', 'trial-balance'], true))
                    {{ $activeReport['subtitle'] }}
                @else
                    FOR THE PERIOD ENDED {{ $periodLabel }}
                    @if(!empty($activeReport['subtitle']))
                        - {{ $activeReport['subtitle'] }}
                    @endif
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="table workbook-table mb-0">
                <thead>
                <tr>
                    @foreach($activeReport['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($activeReport['columns'] as $column)
                            @php $value = $row[$column] ?? ''; @endphp
                            <td class="{{ is_numeric($value) ? 'amount' : '' }}">
                                @if(!empty($row['_history_url']) && in_array($column, ['BILLING INVOICE', 'STATEMENT OF ACCOUNT NUMBER'], true) && (string) $value !== '')
                                    <a class="sales-history-link" href="{{ $row['_history_url'] }}" data-bs-toggle="modal" data-bs-target="#salesHistoryModal" data-history-url="{{ $row['_history_url'] }}">{{ $value }}</a>
                                @else
                                    {{ is_numeric($value) ? $money($value) : $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($activeReport['columns']) }}" class="text-center text-muted py-4">
                            No database records found for this report and period.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(strpos($report, 'sales-') === 0)
        <div class="modal fade no-print" id="salesHistoryModal" tabindex="-1" aria-labelledby="salesHistoryTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"><h2 class="modal-title fs-5" id="salesHistoryTitle">Document and Payment History</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body" id="salesHistoryBody" aria-live="polite"></div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('salesHistoryModal');
    if (!modal) return;
    var requestNumber = 0;
    modal.addEventListener('show.bs.modal', function (event) {
        var url = event.relatedTarget && event.relatedTarget.getAttribute('data-history-url');
        if (!url) return;
        var current = ++requestNumber;
        var body = document.getElementById('salesHistoryBody');
        body.textContent = 'Loading history...';
        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) {
                if (!response.ok || response.redirected) throw new Error('History could not be loaded. Please refresh and try again.');
                return response.text();
            }).then(function (html) {
                if (current === requestNumber) body.innerHTML = html;
            }).catch(function (error) {
                if (current === requestNumber) body.textContent = error.message;
            });
    });
    modal.addEventListener('hidden.bs.modal', function () { requestNumber++; });
}());
</script>
@endpush
