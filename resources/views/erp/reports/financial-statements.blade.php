@extends('erp.layout')

@section('title', 'Financial Statements')
@section('page-title', 'Financial Statements')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Financial Statements</h1>
            <p class="text-muted mb-0">Complete finance report pack for statements, ledgers, AR, AP, cash, tax, and management review.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
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

    <div class="row g-3 mb-4">
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

    <div class="row g-4 mb-4">
        @foreach($statementSections as $title => $rows)
            <div class="col-xl-4">
                <div class="erp-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h2 class="h6 fw-bold mb-1">{{ $title }}</h2>
                            <small class="text-muted">{{ $period }} · {{ $currency }}</small>
                        </div>
                        <span class="badge badge-soft">Draft</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Line Item</th>
                                <th class="text-end">Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row[0] }}</div>
                                        <div class="text-muted small">{{ $row[2] }}</div>
                                    </td>
                                    <td class="text-end fw-semibold {{ $row[1] < 0 ? 'text-danger' : '' }}">
                                        {{ $row[1] < 0 ? '(' : '' }}{{ number_format(abs($row[1]), 2) }}{{ $row[1] < 0 ? ')' : '' }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="erp-card p-3">
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
