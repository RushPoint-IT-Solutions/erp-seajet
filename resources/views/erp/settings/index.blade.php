@extends('erp.layout')

@section('title', 'Finance Settings')
@section('page-title', 'Finance Settings')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Finance Settings</h1>
            <p class="text-muted mb-0">Cost centers, item list, accounts, tax, currency, numbering, banking, approvals, and finance controls.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.accounting.coa') }}" class="btn btn-primary"><i class="bi bi-journal-text me-1"></i>Chart of Accounts</a>
            <a href="{{ route('erp.fx.index') }}" class="btn btn-outline-secondary"><i class="bi bi-currency-exchange me-1"></i>Currency</a>
            <a href="{{ route('erp.export', 'finance-settings') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Export</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($settingStats as $stat)
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

    <div class="erp-card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Finance Setup Checklist</h2>
                <small class="text-muted">Core configuration needed before AR, AP, payments, reports, and tax posting.</small>
            </div>
            <a href="{{ route('erp.approval-matrix') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2-square me-1"></i>Approvals</a>
        </div>

        @foreach($settingGroups as $group => $settings)
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-folder2-open text-primary"></i>
                    <h3 class="h6 fw-bold mb-0">{{ $group }}</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Setting</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($settings as $setting)
                            <tr>
                                <td class="fw-semibold"><i class="bi {{ $setting['icon'] }} me-2 text-primary"></i>{{ $setting['name'] }}</td>
                                <td>{{ $setting['description'] }}</td>
                                <td><span class="badge badge-soft">{{ $setting['status'] }}</span></td>
                                <td class="text-end">
                                    <a href="{{ $setting['route'] }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Open">
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

    <div class="row g-3 mb-4">
        <div class="col-xl-6" id="cost-centers">
            <div class="erp-card p-3 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Cost Centers</h2>
                        <small class="text-muted">Use for department, branch, shipment, and profit tracking.</small>
                    </div>
                    <a href="{{ route('erp.action', 'new-cost-center') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Branch</th>
                            <th>Company</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($costCenters as $costCenter)
                            <tr>
                                @foreach($costCenter as $index => $value)
                                    <td class="{{ $index === 0 ? 'fw-semibold' : '' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6" id="item-list">
            <div class="erp-card p-3 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Item List</h2>
                        <small class="text-muted">Default items for invoicing, AP bills, tax treatment, and accounting mapping.</small>
                    </div>
                    <a href="{{ route('erp.action', 'new-item') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Name</th>
                            <th>Account</th>
                            <th>Tax</th>
                            <th>Currency</th>
                            <th>Type</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($items as $item)
                            <tr>
                                @foreach($item as $index => $value)
                                    <td class="{{ $index === 0 ? 'fw-semibold' : '' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Number Series</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Document</th>
                            <th>Prefix</th>
                            <th>Next No.</th>
                            <th>Format</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($numberSeries as $series)
                            <tr>
                                @foreach($series as $index => $value)
                                    <td class="{{ $index === 0 ? 'fw-semibold' : '' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Tax Codes</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Rate</th>
                            <th>Type</th>
                            <th>Use</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($taxCodes as $taxCode)
                            <tr>
                                @foreach($taxCode as $index => $value)
                                    <td class="{{ $index === 0 ? 'fw-semibold' : '' }}">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
