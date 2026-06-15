@extends('erp.layout')

@section('title', 'Clients')
@section('page-title', 'Clients')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Clients</h1>
            <p class="text-muted mb-0">Dashboard, client list, credit profile, billing actions, and AR follow-up.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.customers.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> New Customer</a>
            <a href="{{ route('erp.invoices.create') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-plus me-1"></i> Create Invoice</a>
            <a href="{{ route('erp.customer-billing.aging') }}" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line me-1"></i> AR Aging</a>
            <a href="{{ route('erp.export', 'customers') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Client List</h2>
                        <small class="text-muted">Customer profiles with credit limits, tax classification, class, currency, and default AR account.</small>
                    </div>
                    <a href="{{ route('erp.customers.create') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Create client">
                        <i class="bi bi-plus-lg"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle module-table w-100">
                        <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>TIN</th>
                            <th>Tax Classification</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Terms</th>
                            <th class="text-end">Credit Limit</th>
                            <th>Credit Status</th>
                            <th>Currency</th>
                            <th>Class</th>
                            <th>AR Account</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td class="fw-semibold">{{ $customer['customer_id'] }}</td>
                                <td>{{ $customer['name'] }}</td>
                                <td>{{ $customer['tin'] }}</td>
                                <td>{{ $customer['vat_classification'] }}</td>
                                <td>{{ $customer['contact_person'] }}</td>
                                <td>{{ $customer['email'] }}</td>
                                <td>{{ $customer['phone'] }}</td>
                                <td>{{ $customer['terms'] }}</td>
                                <td class="text-end fw-semibold">{{ $customer['credit_limit'] }}</td>
                                <td><span class="badge badge-soft">{{ $customer['credit_status'] }}</span></td>
                                <td>{{ $customer['currency'] }}</td>
                                <td>{{ $customer['class'] }}</td>
                                <td>{{ $customer['ar_account'] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('erp.customers.show', $customer['id']) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View client"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('erp.customers.edit', $customer['id']) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit client"><i class="bi bi-pencil-square"></i></a>
                                    <a href="{{ route('erp.invoices.create', ['customer' => $customer['customer_id']]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Invoice"><i class="bi bi-file-earmark-plus"></i></a>
                                    <a href="{{ route('erp.payments.create', ['customer' => $customer['customer_id']]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Payment"><i class="bi bi-cash"></i></a>
                                    <a href="{{ route('erp.reports.client-soa', ['customer_id' => $customer['id']]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Print SOA for active invoices"><i class="bi bi-printer"></i></a>
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
                <h2 class="h6 fw-bold mb-3">Client Workflow</h2>
                <div class="list-group list-group-flush">
                    @foreach($workflows as $workflow)
                        <a href="{{ $workflow['route'] }}" class="list-group-item list-group-item-action px-0 d-flex align-items-center justify-content-between">
                            <span><i class="bi {{ $workflow['icon'] }} me-2 text-primary"></i>{{ $workflow['label'] }}</span>
                            <i class="bi bi-arrow-right-short"></i>
                        </a>
                    @endforeach
                </div>
                <hr>
                <h3 class="h6 fw-bold mb-3">Tracked Fields</h3>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge badge-soft py-2 px-3">Tax classification</span>
                    <span class="badge badge-soft py-2 px-3">Credit limit</span>
                    <span class="badge badge-soft py-2 px-3">Payment terms</span>
                    <span class="badge badge-soft py-2 px-3">Branch class</span>
                    <span class="badge badge-soft py-2 px-3">Currency</span>
                    <span class="badge badge-soft py-2 px-3">Default AR account</span>
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
        language: { search: '', searchPlaceholder: 'Search customers' }
    });
</script>
@endpush
