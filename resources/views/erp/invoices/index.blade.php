@extends('erp.layout')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Invoices</h1>
            <p class="text-muted mb-0">Dashboard, invoice list, billing actions, invoice review, and collection follow-up.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary" title="Create new invoice">
                <i class="bi bi-plus-lg me-1"></i> New Invoice
            </a>
            <a href="{{ route('erp.customer-billing.aging') }}" class="btn btn-outline-secondary" title="Aging report">
                <i class="bi bi-bar-chart-line me-1"></i> Aging
            </a>
            <a href="{{ route('erp.export', 'invoices') }}" class="btn btn-outline-secondary" title="Export invoices">
                <i class="bi bi-file-earmark-excel me-1"></i> Export
            </a>
        </div>
    </div>

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

    <div class="erp-card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Aging Report</h2>
                <small class="text-muted">Open invoices by aging bucket: current, 30, 60, and 90+ days.</small>
            </div>
            <a href="{{ route('erp.customer-billing.aging') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-right-short me-1"></i>Full AR Aging
            </a>
        </div>

        <div class="row g-3 mb-3">
            @foreach($agingBuckets as $bucket)
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="text-muted small">{{ $bucket['label'] }}</div>
                                <div class="h5 fw-bold mb-0">{{ $bucket['value'] }}</div>
                            </div>
                            <span class="kpi-icon bg-{{ $bucket['tone'] }}"><i class="bi {{ $bucket['icon'] }}"></i></span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Customer</th>
                    <th>Invoice No.</th>
                    <th>Currency</th>
                    <th class="text-end">Current</th>
                    <th class="text-end">30</th>
                    <th class="text-end">60</th>
                    <th class="text-end">90+</th>
                </tr>
                </thead>
                <tbody>
                @foreach($agingRows as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                        <td>{{ $row[2] }}</td>
                        <td class="text-end">{{ $row[3] }}</td>
                        <td class="text-end">{{ $row[4] }}</td>
                        <td class="text-end">{{ $row[5] }}</td>
                        <td class="text-end">{{ $row[6] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="erp-card p-3 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Invoice List</h2>
                        <small class="text-muted">Billing invoices, SOA A documents, debit notes, and payment actions.</small>
                    </div>
                    <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Create new">
                        <i class="bi bi-plus-lg"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle invoices-table w-100">
                        <thead>
                        <tr>
                            <th>Document No.</th>
                            <th>Customer</th>
                            <th>Shipment Job ID</th>
                            <th>Currency</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td class="fw-semibold">{{ $invoice[0] }}</td>
                                <td>{{ $invoice[1] }}</td>
                                <td>{{ $invoice[2] }}</td>
                                <td>{{ $invoice[3] }}</td>
                                <td class="text-end">{{ $invoice[4] }}</td>
                                <td><span class="badge badge-soft">{{ $invoice[5] }}</span></td>
                                <td>{{ $invoice[6] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('erp.billings.print-invoice', ['invoice' => $invoice[0]]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('erp.billings.edit', $invoice[0]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="{{ route('erp.payments.create', ['invoice' => $invoice[0]]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Payment"><i class="bi bi-credit-card"></i></a>
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
                <h2 class="h6 fw-bold mb-3">Invoice Workflow</h2>
                <div class="list-group list-group-flush">
                    @foreach($workflows as $workflow)
                        <a href="{{ $workflow['route'] }}" class="list-group-item list-group-item-action px-0 d-flex align-items-center justify-content-between">
                            <span><i class="bi {{ $workflow['icon'] }} me-2 text-primary"></i>{{ $workflow['label'] }}</span>
                            <i class="bi bi-arrow-right-short"></i>
                        </a>
                    @endforeach
                </div>
                <hr>
                <h3 class="h6 fw-bold mb-3">Included</h3>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge badge-soft py-2 px-3">Dashboard</span>
                    <span class="badge badge-soft py-2 px-3">Invoice list</span>
                    <span class="badge badge-soft py-2 px-3">Create action</span>
                    <span class="badge badge-soft py-2 px-3">Posted status</span>
                    <span class="badge badge-soft py-2 px-3">View to print</span>
                    <span class="badge badge-soft py-2 px-3">Payment link</span>
                    <span class="badge badge-soft py-2 px-3">Aging report</span>
                    <span class="badge badge-soft py-2 px-3">Export</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.invoices-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search invoices' }
    });
</script>
@endpush
