@extends('erp.layout')

@section('title', 'Sales & Purchases Summary')
@section('page-title', 'Sales & Purchases Summary')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Summary Lists of Sales and Purchases</h1>
            <p class="text-muted mb-0">Tax-ready listing for sales, purchases, VAT classes, withholding, and shipment traceability.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.tax.vat-summary') }}" class="btn btn-primary"><i class="bi bi-percent me-1"></i>VAT Summary</a>
            <a href="{{ route('erp.export', 'excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Summary List of Sales</h2>
                @include('erp.tax.partials.tax-list', ['rows' => $sales, 'partyLabel' => 'Customer'])
            </div>
        </div>
        <div class="col-xl-6">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Summary List of Purchases</h2>
                @include('erp.tax.partials.tax-list', ['rows' => $purchases, 'partyLabel' => 'Supplier'])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.summary-tax-table').DataTable({
        pageLength: 8,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search summary' }
    });
</script>
@endpush
