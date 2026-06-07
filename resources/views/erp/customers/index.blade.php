@extends('erp.layout')

@section('title', 'Customer Masterlist')
@section('page-title', 'Customer Masterlist')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Customer Masterlist</h1>
            <p class="text-muted mb-0">Customer profiles with credit limit, payment terms, tax classification, class, currency, and default AR account.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.customers.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> New Customer</a>
            <a href="{{ route('erp.invoices.create') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-plus me-1"></i> Create Invoice</a>
            <a href="{{ route('erp.export', 'customers') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
        </div>
    </div>

    <div class="erp-card p-3">
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
                            <a href="{{ route('erp.invoices.create', ['customer' => $customer['customer_id']]) }}" class="btn btn-sm btn-outline-secondary" title="Invoice"><i class="bi bi-file-earmark-plus"></i></a>
                            <a href="{{ route('erp.payments.create', ['customer' => $customer['customer_id']]) }}" class="btn btn-sm btn-outline-secondary" title="Payment"><i class="bi bi-cash"></i></a>
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
        language: { search: '', searchPlaceholder: 'Search customers' }
    });
</script>
@endpush
