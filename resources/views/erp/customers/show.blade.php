@extends('erp.layout')

@section('title', 'Client Profile')
@section('page-title', 'Client Profile')

@section('content')
@php
    $customerCode = $customer->customer_id ?: $customer->code;
    $money = function ($amount, $currency = 'PHP') {
        return $currency.' '.number_format((float) $amount, 2);
    };
@endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $customer->name }}</h1>
            <p class="text-muted mb-0">{{ $customerCode }} | {{ $customer->tin ?: 'No TIN' }} | {{ $customer->default_class_code ?: 'MANILA' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Clients</a>
            <a href="{{ route('erp.customers.edit', $customer->id) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil-square me-1"></i>Edit Client</a>
            <a href="{{ route('erp.invoices.create', ['customer' => $customerCode]) }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-plus me-1"></i>Create Invoice</a>
            <a href="{{ route('erp.payments.create', ['customer' => $customerCode]) }}" class="btn btn-outline-secondary"><i class="bi bi-cash me-1"></i>Receive Payment</a>
            <a href="{{ route('erp.reports.client-soa', ['customer_id' => $customer->id]) }}" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Print SOA</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">Invoice History</div>
                <div class="h4 fw-bold mb-0">{{ number_format($summary['invoice_count']) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">Active Unpaid</div>
                <div class="h4 fw-bold mb-0">{{ number_format($summary['open_count']) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">Total Billed</div>
                <div class="h4 fw-bold mb-0">{{ $money($summary['billed'], $customer->currency ?: 'PHP') }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">Open Balance</div>
                <div class="h4 fw-bold mb-0">{{ $money($summary['open_balance'], $customer->currency ?: 'PHP') }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Client Details</h2>
                <div class="small text-muted">Billing Address</div>
                <div class="mb-3">{{ $customer->billing_address ?: '-' }}</div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="small text-muted">Tax</div>
                        <div class="fw-semibold">{{ $customer->tax_classification ?: '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Terms</div>
                        <div class="fw-semibold">{{ $customer->payment_terms ?: '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Currency</div>
                        <div class="fw-semibold">{{ $customer->currency ?: 'PHP' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Credit Status</div>
                        <span class="badge badge-soft">{{ $customer->credit_status ?: 'Within Limit' }}</span>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted">Contact</div>
                        <div>{{ $customer->contact_person ?: '-' }}</div>
                        <div>{{ $customer->email ?: '-' }}</div>
                        <div>{{ $customer->phone ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="erp-card p-3 h-100">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Invoice History</h2>
                        <small class="text-muted">All billing invoices, SOA A/B documents, and debit notes for this client.</small>
                    </div>
                    <a href="{{ route('erp.reports.client-soa', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-file-earmark-text me-1"></i>Active Invoice SOA
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle module-table w-100">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>SOA No</th>
                            <th>Job/Shipment</th>
                            <th>VAT</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->document_date }}</td>
                                <td class="fw-semibold">{{ $invoice->document_no }}</td>
                                <td>{{ $invoice->soa_no ?: '-' }}</td>
                                <td>{{ $invoice->job_shipment_id ?: '-' }}</td>
                                <td>{{ $invoice->vat_classification ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $invoice->active_unpaid ? 'text-bg-warning' : 'badge-soft' }}">
                                        {{ ucwords(str_replace('_', ' ', $invoice->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($invoice->grand_total, 2) }}</td>
                                <td class="text-end">{{ number_format($invoice->paid_total, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($invoice->balance, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('erp.billings.edit', $invoice->document_no) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($invoice->active_unpaid)
                                        <a href="{{ route('erp.reports.client-soa', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Print active SOA">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No invoice history found for this client.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
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
        order: [[0, 'desc']],
        language: { search: '', searchPlaceholder: 'Search invoices' }
    });
</script>
@endpush
