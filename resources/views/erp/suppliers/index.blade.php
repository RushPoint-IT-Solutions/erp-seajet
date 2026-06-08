@extends('erp.layout')

@section('title', 'Vendors')
@section('page-title', 'Vendors')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Vendor Dashboard</h1>
            <p class="text-muted mb-0">Vendor master data, open AP exposure, recent bills, payment terms, tax setup, and AP actions.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.suppliers.create') }}" class="btn btn-primary"><i class="bi bi-building-add me-1"></i> New Vendor</a>
            <a href="{{ route('erp.payables.vendor-bills.create') }}" class="btn btn-outline-secondary"><i class="bi bi-receipt me-1"></i> Vendor Bill</a>
            <a href="{{ route('erp.expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-credit-card-2-front me-1"></i> Accounts Payable</a>
            <a href="{{ route('erp.export', 'suppliers') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        @foreach($stats as $stat)
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

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="erp-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Recent Vendor Bills</h2>
                        <small class="text-muted">Latest AP documents connected to vendor records.</small>
                    </div>
                    <a href="{{ route('erp.expenses.index') }}" class="btn btn-sm btn-outline-secondary">AP Register</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Bill No.</th>
                            <th>Vendor</th>
                            <th>Shipment Job ID</th>
                            <th>Currency</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recentBills as $bill)
                            <tr>
                                <td class="fw-semibold">{{ $bill['bill_no'] }}</td>
                                <td>{{ $bill['supplier'] }}</td>
                                <td>{{ $bill['shipment_job_id'] }}</td>
                                <td>{{ $bill['currency'] }}</td>
                                <td class="text-end">{{ $bill['amount'] }}</td>
                                <td class="text-end">{{ $bill['balance'] }}</td>
                                <td><span class="badge badge-soft">{{ $bill['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No vendor bills recorded yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">AP Aging Snapshot</h2>
                <div class="d-flex flex-column gap-2">
                    @foreach($agingBuckets as $bucket)
                        <div class="d-flex justify-content-between border rounded p-2">
                            <span>{{ $bucket['label'] }}</span>
                            <strong>{{ $bucket['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('erp.payables.aging') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock-history me-1"></i> AP Aging</a>
                    <a href="{{ route('erp.payables.payments.create') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cash-stack me-1"></i> Pay Bills</a>
                </div>
            </div>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Vendor Masterlist</h2>
                <small class="text-muted">Database-connected vendor profiles for Accounts Payable.</small>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead>
                <tr>
                    <th>Supplier ID</th>
                    <th>Name</th>
                    <th>TIN</th>
                    <th>Tax Classification</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Terms</th>
                    <th>Payment Method</th>
                    <th>WHT Setup</th>
                    <th>Currency</th>
                    <th>Class</th>
                    <th>AP Account</th>
                    <th class="text-end">Open Bills</th>
                    <th class="text-end">Open AP</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($suppliers as $supplier)
                    <tr>
                        <td class="fw-semibold">{{ $supplier['supplier_id'] }}</td>
                        <td>{{ $supplier['name'] }}</td>
                        <td>{{ $supplier['tin'] }}</td>
                        <td>{{ $supplier['vat_classification'] }}</td>
                        <td>{{ $supplier['contact_person'] }}</td>
                        <td>{{ $supplier['email'] }}</td>
                        <td>{{ $supplier['phone'] }}</td>
                        <td>{{ $supplier['terms'] }}</td>
                        <td>{{ $supplier['payment_method'] }}</td>
                        <td>{{ $supplier['withholding_tax'] }}</td>
                        <td>{{ $supplier['currency'] }}</td>
                        <td>{{ $supplier['class'] }}</td>
                        <td>{{ $supplier['ap_account'] }}</td>
                        <td class="text-end">{{ $supplier['open_bills'] }}</td>
                        <td class="text-end fw-semibold">{{ $supplier['open_ap_display'] }}</td>
                        <td class="text-end">
                            <a href="{{ route('erp.payables.vendor-bills.create', ['supplier' => $supplier['supplier_id']]) }}" class="btn btn-sm btn-outline-secondary" title="Vendor Bill"><i class="bi bi-receipt"></i></a>
                            <a href="{{ route('erp.payables.payments.create', ['supplier' => $supplier['supplier_id']]) }}" class="btn btn-sm btn-outline-secondary" title="Pay Bills"><i class="bi bi-cash"></i></a>
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
        language: { search: '', searchPlaceholder: 'Search suppliers' }
    });
</script>
@endpush
