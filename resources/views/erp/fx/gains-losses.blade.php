@extends('erp.layout')

@section('title', 'FX Gains / Losses')
@section('page-title', 'FX Gains / Losses')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Realized and Unrealized FX Gains / Losses</h1>
            <p class="text-muted mb-0">Track settlement gains/losses and month-end unrealized revaluation by reference and shipment.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.fx.conversion') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New FX Entry</a>
            <a href="{{ route('erp.accounting.journals') }}" class="btn btn-outline-secondary"><i class="bi bi-journal-text me-1"></i>Journal Entry</a>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle fx-gl-table w-100">
                <thead>
                <tr>
                    <th>Date</th><th>Type</th><th>Reference</th><th>Shipment</th><th>Currency</th><th class="text-end">Foreign</th><th class="text-end">Doc Rate</th><th class="text-end">Current Rate</th><th class="text-end">Gain/Loss</th><th>Result</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td><td>{{ $row[3] }}</td><td>{{ $row[4] }}</td>
                        <td class="text-end">{{ number_format($row[5], 2) }}</td>
                        <td class="text-end">{{ number_format($row[6], 6) }}</td>
                        <td class="text-end">{{ number_format($row[7], 6) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row[8], 2) }}</td>
                        <td><span class="badge {{ $row[9] === 'Gain' ? 'bg-success' : 'bg-danger' }}">{{ $row[9] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.fx-gl-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search gains/losses' }
    });
</script>
@endpush
