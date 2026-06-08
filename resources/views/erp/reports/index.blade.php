@extends('erp.layout')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Reports</h1>
            <p class="text-muted mb-0">AR aging, statements, tax schedules, AP exposure, cash movement, and audit-ready registers.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.reports.financial-statements') }}" class="btn btn-primary"><i class="bi bi-folder-check me-1"></i>Financial Statements</a>
            <a href="{{ route('erp.customer-billing.aging') }}" class="btn btn-primary"><i class="bi bi-bar-chart-line me-1"></i>AR Aging</a>
            <a href="{{ route('erp.statements.profit-loss') }}" class="btn btn-outline-secondary"><i class="bi bi-graph-up me-1"></i>P&L</a>
            <a href="{{ route('erp.export', 'reports') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Export</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($reportStats as $stat)
            <div class="col-md-6 col-xl-3">
                <div class="erp-card kpi-card p-3 h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">{{ $stat['label'] }}</div>
                            <div class="h4 fw-bold mb-0">{{ $stat['value'] }}</div>
                        </div>
                        <span class="kpi-icon bg-{{ $stat['tone'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="erp-card p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Report Library</h2>
                <small class="text-muted">Select a report to review, print, or export.</small>
            </div>
            <div class="btn-group btn-group-sm no-print">
                <a href="{{ route('erp.export', 'reports-excel') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Export Excel"><i class="bi bi-file-earmark-excel"></i></a>
                <a href="{{ route('erp.export', 'reports-pdf') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Export PDF"><i class="bi bi-filetype-pdf"></i></a>
                <button class="btn btn-outline-secondary" onclick="window.print()" data-bs-toggle="tooltip" title="Print"><i class="bi bi-printer"></i></button>
            </div>
        </div>

        @foreach($reportGroups as $group => $reports)
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
                            <th>Description</th>
                            <th>Frequency</th>
                            <th>Owner</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($reports as $report)
                            <tr>
                                <td class="fw-semibold">
                                    <i class="bi {{ $report['icon'] }} me-2 text-primary"></i>{{ $report['name'] }}
                                </td>
                                <td>{{ $report['description'] }}</td>
                                <td><span class="badge badge-soft">{{ $report['frequency'] }}</span></td>
                                <td>{{ $report['owner'] }}</td>
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
