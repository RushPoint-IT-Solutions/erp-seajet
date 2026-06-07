@extends('erp.layout')

@section('title', 'Expenses')
@section('page-title', 'Expenses')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Expenses / Vendor Bills</h1>
            <p class="text-muted mb-0">List of AP bills, shipment-linked expenses, recurring expenses, and payable status.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.payables.vendor-bills.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Enter Bill</a>
            <a href="{{ route('erp.payables.approvals') }}" class="btn btn-outline-secondary"><i class="bi bi-check2-square me-1"></i> Approvals</a>
            <a href="{{ route('erp.payables.aging') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i> AP Aging</a>
            <a href="{{ route('erp.export', 'expenses') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="erp-card p-3">
                <div class="small text-muted">Unpaid Bills</div>
                <div class="h4 fw-bold mb-0">PHP 267,210.00</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="erp-card p-3">
                <div class="small text-muted">USD Payables</div>
                <div class="h4 fw-bold mb-0">USD 1,325.00</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="erp-card p-3">
                <div class="small text-muted">For Approval</div>
                <div class="h4 fw-bold mb-0">2</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="erp-card p-3">
                <div class="small text-muted">WHT Tracked</div>
                <div class="h4 fw-bold mb-0">Yes</div>
            </div>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead>
                <tr>
                    <th>Bill / Expense No.</th>
                    <th>Vendor</th>
                    <th>Shipment Job ID</th>
                    <th>Customer:Job</th>
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
                        <td>{{ $expense[4] }}</td>
                        <td class="text-end">{{ $expense[5] }}</td>
                        <td><span class="badge badge-soft">{{ $expense[6] }}</span></td>
                        <td>{{ $expense[7] }}</td>
                        <td class="text-end no-print">
                            <a href="{{ route('erp.payables.vendor-bills.create', ['bill' => $expense[0]]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('erp.payables.check-voucher') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a>
                            <a href="{{ route('erp.payables.payments.create', ['bill' => $expense[0]]) }}" class="btn btn-sm btn-primary"><i class="bi bi-cash"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
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
