@extends('erp.layout')

@section('title', 'FX Conversion Tracking')
@section('page-title', 'FX Conversion Tracking')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">FX Conversion Tracking</h1>
            <p class="text-muted mb-0">Monitor USD and PHP bank activity with exchange rates and base currency impact.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.banking.transactions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Record FX</a>
            <a href="{{ route('erp.export', 'excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="erp-card p-3"><div class="text-muted small">USD Inflow</div><div class="h4 fw-bold mb-0">6,840.00</div></div></div>
        <div class="col-md-4"><div class="erp-card p-3"><div class="text-muted small">PHP Equivalent</div><div class="h4 fw-bold mb-0">397,220.00</div></div></div>
        <div class="col-md-4"><div class="erp-card p-3"><div class="text-muted small">Tracked FX Variance</div><div class="h4 fw-bold mb-0">1,025.00</div></div></div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle fx-table w-100">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Currency</th>
                    <th class="text-end">Foreign Amount</th>
                    <th class="text-end">Exchange Rate</th>
                    <th class="text-end">PHP Base Amount</th>
                    <th>Notes</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                        <td>{{ $row[2] }}</td>
                        <td class="text-end">{{ number_format($row[3], 2) }}</td>
                        <td class="text-end">{{ number_format($row[4], 6) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row[5], 2) }}</td>
                        <td>{{ $row[6] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.fx-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search FX records' }
    });
</script>
@endpush
