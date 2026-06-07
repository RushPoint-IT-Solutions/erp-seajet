@extends('erp.layout')

@section('title', 'Tax Audit Trail')
@section('page-title', 'Tax Audit Trail')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Tax Audit Trail</h1>
            <p class="text-muted mb-0">Track VAT, EWT, CWT, tax code changes, document references, and shipment traceability.</p>
        </div>
        <a href="{{ route('erp.tax.withholding') }}" class="btn btn-outline-secondary no-print"><i class="bi bi-arrow-left me-1"></i>Withholding</a>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle audit-table w-100">
                <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Reference</th>
                    <th>Shipment Job ID</th>
                    <th>Audit Details</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logs as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
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
    $('.audit-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        order: [[0, 'desc']],
        language: { search: '', searchPlaceholder: 'Search audit trail' }
    });
</script>
@endpush
