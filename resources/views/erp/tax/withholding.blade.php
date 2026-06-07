@extends('erp.layout')

@section('title', 'Withholding Tax')
@section('page-title', 'Withholding Tax')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Withholding Tax</h1>
            <p class="text-muted mb-0">Expanded withholding tax and creditable withholding tax computation list.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.tax.calculator') }}" class="btn btn-primary"><i class="bi bi-calculator me-1"></i>Compute Tax</a>
            <a href="{{ route('erp.tax.audit-trail') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i>Audit Trail</a>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle withholding-table w-100">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Tax Type</th>
                    <th>Party</th>
                    <th>Shipment Job ID</th>
                    <th>Currency</th>
                    <th class="text-end">Tax Base</th>
                    <th>Rate</th>
                    <th class="text-end">WHT Amount</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $index => $cell)
                            <td class="{{ in_array($index, [6, 8]) ? 'text-end fw-semibold' : '' }}">{{ in_array($index, [6, 8]) ? number_format($cell, 2) : $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.withholding-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search withholding' }
    });
</script>
@endpush
