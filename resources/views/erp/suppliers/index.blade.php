@extends('erp.layout')

@section('title', 'Supplier Masterlist')
@section('page-title', 'Supplier Masterlist')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Supplier Masterlist</h1>
            <p class="text-muted mb-0">Vendor profiles with payment terms, tax classification, payment method, AP account, class, and currency.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.suppliers.create') }}" class="btn btn-primary"><i class="bi bi-building-add me-1"></i> New Supplier</a>
            <a href="{{ route('erp.modules.show', 'purchasing') }}" class="btn btn-outline-secondary"><i class="bi bi-cart-plus me-1"></i> Purchasing</a>
            <a href="{{ route('erp.export', 'suppliers') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
        </div>
    </div>

    <div class="erp-card p-3">
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
                        <td class="text-end">
                            <a href="{{ route('erp.action', 'create-po') }}" class="btn btn-sm btn-outline-secondary" title="Create PO"><i class="bi bi-cart-plus"></i></a>
                            <a href="{{ route('erp.action', 'record-supplier-bill') }}" class="btn btn-sm btn-outline-secondary" title="Bill"><i class="bi bi-receipt"></i></a>
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
