@extends('erp.layout')

@section('title', 'Accounts Payable (AP)')
@section('page-title', 'Accounts Payable (AP)')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">C) Accounts Payable (AP)</h1>
            <p class="text-muted mb-0">Vendor bills, shipment-linked client tagging, payment approvals, vouchers, checks, withholding tax, aging, and USD/PHP payables.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.payables.vendor-bills.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Vendor Bill</a>
            <a href="{{ route('erp.payables.approvals') }}" class="btn btn-outline-secondary"><i class="bi bi-check2-square me-1"></i> Approvals</a>
            <a href="{{ route('erp.payables.payments.create') }}" class="btn btn-outline-secondary"><i class="bi bi-credit-card me-1"></i> Pay Bills</a>
            <a href="{{ route('erp.payables.check-voucher') }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Check Voucher</a>
            <a href="{{ route('erp.payables.aging') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i> AP Aging</a>
            <a href="{{ route('erp.export', 'expenses') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($apStats as $stat)
            <div class="col-md-6 col-xl-3">
                <div class="erp-card kpi-card p-3 h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="small text-muted">{{ $stat['label'] }}</div>
                            <div class="h4 fw-bold mb-0">{{ $stat['value'] }}</div>
                        </div>
                        <span class="kpi-icon bg-{{ $stat['tone'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="erp-card p-3 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">AP Register</h2>
                        <small class="text-muted">Vendor bill entry with shipment job ID, client tagging, currency, approval status, and payment action.</small>
                    </div>
                    <span class="badge badge-soft py-2 px-3">Required: USD & PHP</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle module-table w-100">
                        <thead>
                        <tr>
                            <th>Bill / Expense No.</th>
                            <th>Vendor</th>
                            <th>Shipment Job ID</th>
                            <th>Tagged Client</th>
                            <th>Currency</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end no-print">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($expenses as $expense)
                            <tr>
                                <td class="fw-semibold">{{ $expense[0] }}</td>
                                <td>{{ $expense[1] }}</td>
                                <td>{{ $expense[2] }}</td>
                                <td>{{ $expense[3] }}</td>
                                <td><span class="badge badge-soft">{{ $expense[4] }}</span></td>
                                <td class="text-end">{{ $expense[5] }}</td>
                                <td><span class="badge badge-soft">{{ $expense[6] }}</span></td>
                                <td>{{ $expense[7] }}</td>
                                <td class="text-end no-print">
                                    <a href="{{ route('erp.payables.vendor-bills.create', ['bill' => $expense[0]]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit bill"><i class="bi bi-pencil"></i></a>
                                    <a href="{{ route('erp.payables.check-voucher') }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Print check voucher"><i class="bi bi-printer"></i></a>
                                    <a href="{{ route('erp.payables.payments.create', ['bill' => $expense[0]]) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Process payment"><i class="bi bi-cash"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">AP Workflow</h2>
                <div class="list-group list-group-flush mb-3">
                    @foreach($apWorkflows as $workflow)
                        <a href="{{ $workflow['route'] }}" class="list-group-item list-group-item-action px-0 d-flex align-items-center justify-content-between">
                            <span><i class="bi {{ $workflow['icon'] }} me-2 text-primary"></i>{{ $workflow['label'] }}</span>
                            <i class="bi bi-arrow-right-short"></i>
                        </a>
                    @endforeach
                </div>
                <hr>
                <h3 class="h6 fw-bold mb-3">Features</h3>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($apFeatures as $feature)
                        <span class="badge badge-soft py-2 px-3"><i class="bi {{ $feature['icon'] }} me-1"></i>{{ $feature['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search expenses' }
    });
</script>
@endpush
