@extends('erp.layout')

@section('title', 'Financial Statements')
@section('page-title', 'Financial Statements')

@push('styles')
<style>
    .statement-page {
        max-width: 216mm;
        margin: 0 auto;
    }
    .statement-paper {
        background: #fff;
        border: 1px solid #d8dde6;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .08);
        padding: 18mm 20mm;
        color: #111;
        font-family: "Times New Roman", Times, serif;
    }
    .statement-header {
        text-align: center;
        line-height: 1.15;
        margin-bottom: 7mm;
    }
    .statement-company {
        font-weight: 700;
        font-size: 14px;
    }
    .statement-title {
        font-weight: 700;
        font-size: 18px;
    }
    .statement-subtitle {
        font-weight: 700;
        font-size: 14px;
    }
    .statement-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .statement-table th {
        font-weight: 700;
        text-align: right;
        text-decoration: underline;
        padding: 0 0 4mm 7mm;
        width: 27mm;
        white-space: nowrap;
    }
    .statement-table th:first-child {
        text-align: left;
        text-decoration: none;
        width: auto;
        padding-left: 0;
    }
    .statement-table td {
        padding: .7mm 0 .7mm 7mm;
        vertical-align: top;
    }
    .statement-table td:first-child {
        padding-left: 0;
    }
    .statement-section td {
        padding-top: 4mm;
        padding-bottom: 2mm;
        font-weight: 700;
    }
    .statement-amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .statement-total td {
        border-top: 1px solid #111;
        font-weight: 700;
        padding-top: 1.5mm;
    }
    .statement-result td {
        border-top: 1px solid #111;
        font-weight: 700;
        padding-top: 1.5mm;
    }
    .statement-final td {
        border-top: 3px double #111;
        font-weight: 700;
        padding-top: 1.5mm;
    }
    .report-library {
        font-family: Inter, "Segoe UI", Arial, sans-serif;
    }
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body { background: #fff !important; }
        .no-print, .erp-sidebar, .erp-topbar, .report-library { display: none !important; }
        .erp-shell, .erp-main, .erp-content, main, section {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .statement-page {
            max-width: none;
            margin: 0;
        }
        .statement-paper {
            border: 0;
            box-shadow: none;
            padding: 0;
        }
    }
</style>
@endpush

@section('content')
@php
    $money = function ($amount) {
        $amount = (float) $amount;
        return $amount < 0 ? '(' . number_format(abs($amount), 2) . ')' : number_format($amount, 2);
    };
    $companyName = $company && !empty($company->name) ? $company->name : 'Sea-Jet International Forwarders, Inc.';
@endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
        <div>
            <h1 class="h3 fw-bold mb-1">Financial Statements</h1>
            <p class="text-muted mb-0">Printable income statement summary with budgets, actuals, and variances.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Reports</a>
            <a href="{{ route('erp.export', 'financial-statements-excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            <a href="{{ route('erp.export', 'financial-statements-pdf') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <form class="erp-card p-3 mb-4 no-print" method="get" action="{{ route('erp.reports.financial-statements') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Period</label>
                <select name="period" class="form-select">
                    @foreach(['May 2026', 'April 2026', 'Q2 2026', 'YTD 2026'] as $option)
                        <option value="{{ $option }}" {{ $period === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select">
                    @foreach(['PHP', 'USD', 'PHP Consolidated'] as $option)
                        <option value="{{ $option }}" {{ $currency === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Branch / Class</label>
                <select name="branch" class="form-select">
                    <option>All Branches</option>
                    <option>MANILA</option>
                    <option>CEBU</option>
                    <option>PAMPANGA</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply Filters</button>
            </div>
        </div>
    </form>

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

    <div class="statement-page mb-4">
        <div class="statement-paper">
            <div class="statement-header">
                <div class="statement-company">{{ $companyName }}</div>
                <div class="statement-title">{{ $incomeStatement['title'] }}</div>
                <div class="statement-subtitle">{{ $incomeStatement['subtitle'] }}</div>
            </div>

            <table class="statement-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Year to Date<br>Actual</th>
                        <th>Year to Date<br>Budget</th>
                        <th>Year to Date<br>Variance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="statement-section"><td colspan="4">Operating Revenue</td></tr>
                    @foreach($incomeStatement['sections']['Operating Revenue'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="statement-amount">{{ $money($row['actual']) }}</td>
                            <td class="statement-amount">{{ $money($row['budget']) }}</td>
                            <td class="statement-amount">{{ $money($row['variance']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="statement-total">
                        <td>Total Operating Revenue</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['operating_revenue']['actual']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['operating_revenue']['budget']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['operating_revenue']['variance']) }}</td>
                    </tr>

                    <tr class="statement-section"><td colspan="4">Operating Expenses</td></tr>
                    @foreach($incomeStatement['sections']['Operating Expenses'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="statement-amount">{{ $money($row['actual']) }}</td>
                            <td class="statement-amount">{{ $money($row['budget']) }}</td>
                            <td class="statement-amount">{{ $money($row['variance']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="statement-total">
                        <td>Total Operating Expenses</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['operating_expenses']['actual']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['operating_expenses']['budget']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['operating_expenses']['variance']) }}</td>
                    </tr>
                    <tr class="statement-result">
                        <td>Net Operating Revenue (Loss)</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['net_operating']['actual']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['net_operating']['budget']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['net_operating']['variance']) }}</td>
                    </tr>

                    <tr class="statement-section"><td colspan="4">Non-Operating Revenue</td></tr>
                    @foreach($incomeStatement['sections']['Non-Operating Revenue'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="statement-amount">{{ $money($row['actual']) }}</td>
                            <td class="statement-amount">{{ $money($row['budget']) }}</td>
                            <td class="statement-amount">{{ $money($row['variance']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="statement-total">
                        <td>Total Non-Operating Revenue</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['non_operating_revenue']['actual']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['non_operating_revenue']['budget']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['non_operating_revenue']['variance']) }}</td>
                    </tr>

                    <tr class="statement-section"><td colspan="4">Non-Operating Expenses</td></tr>
                    @foreach($incomeStatement['sections']['Non-Operating Expenses'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="statement-amount">{{ $money($row['actual']) }}</td>
                            <td class="statement-amount">{{ $money($row['budget']) }}</td>
                            <td class="statement-amount">{{ $money($row['variance']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="statement-total">
                        <td>Total Non-Operating Expenses</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['non_operating_expenses']['actual']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['non_operating_expenses']['budget']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['non_operating_expenses']['variance']) }}</td>
                    </tr>
                    <tr class="statement-result">
                        <td>Net Non-Operating Revenue (Loss)</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['net_non_operating']['actual']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['net_non_operating']['budget']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['net_non_operating']['variance']) }}</td>
                    </tr>
                    <tr class="statement-final">
                        <td>Total Net Revenue (Loss)</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['total_net']['actual']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['total_net']['budget']) }}</td>
                        <td class="statement-amount">{{ $money($incomeStatement['totals']['total_net']['variance']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="erp-card p-3 report-library">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Required Finance Reports</h2>
                <small class="text-muted">Reports needed for accounting close, management review, tax compliance, and audit support.</small>
            </div>
            <span class="badge badge-soft">{{ array_sum(array_map('count', $reportCatalog)) }} reports</span>
        </div>

        @foreach($reportCatalog as $group => $reports)
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-folder2-open text-primary"></i>
                    <h3 class="h6 fw-bold mb-0">{{ $group }}</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Report</th>
                            <th>Purpose</th>
                            <th>Frequency</th>
                            <th class="text-end">Open</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($reports as $report)
                            <tr>
                                <td class="fw-semibold">{{ $report['name'] }}</td>
                                <td>{{ $report['purpose'] }}</td>
                                <td><span class="badge badge-soft">{{ $report['frequency'] }}</span></td>
                                <td class="text-end">
                                    <a href="{{ $report['route'] }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Open report">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
