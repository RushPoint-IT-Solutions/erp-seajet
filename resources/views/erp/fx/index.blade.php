@extends('erp.layout')

@section('title', 'Multi-Currency & FX')
@section('page-title', 'Multi-Currency & FX')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Multi-Currency & FX</h1>
            <p class="text-muted mb-0">USD and PHP conversion, realized gains/losses, unrealized revaluation, and shipment-linked FX audit.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.fx.conversion') }}" class="btn btn-primary"><i class="bi bi-currency-exchange me-1"></i>Convert Currency</a>
            <a href="{{ route('erp.fx.gains-losses') }}" class="btn btn-outline-secondary"><i class="bi bi-graph-up-arrow me-1"></i>Gains / Losses</a>
            <a href="{{ route('erp.banking.fx') }}" class="btn btn-outline-secondary"><i class="bi bi-bank me-1"></i>Bank FX</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">Base Currency</div><div class="h4 fw-bold mb-0">PHP</div></div></div>
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">USD Exposure</div><div class="h4 fw-bold mb-0">5,515.00</div></div></div>
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">Realized FX Gain</div><div class="h4 fw-bold mb-0">316.50</div></div></div>
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">Unrealized FX Gain</div><div class="h4 fw-bold mb-0">526.00</div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Currency Rates</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle fx-rates-table w-100">
                        <thead><tr><th>Date</th><th>From</th><th>To</th><th class="text-end">Rate</th><th>Source</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($rates as $row)
                            <tr>
                                <td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row[3], 6) }}</td><td>{{ $row[4] }}</td><td>{{ $row[5] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Open FX Exposure</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle fx-exposure-table w-100">
                        <thead><tr><th>Module</th><th>Party / Account</th><th>Reference</th><th>Shipment</th><th>Currency</th><th class="text-end">Foreign</th><th class="text-end">Current Rate</th><th class="text-end">Unrealized</th></tr></thead>
                        <tbody>
                        @foreach($exposures as $row)
                            <tr>
                                <td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td>{{ $row[2] }}</td><td>{{ $row[3] }}</td><td>{{ $row[4] }}</td>
                                <td class="text-end">{{ number_format($row[5], 2) }}</td><td class="text-end">{{ number_format($row[7], 6) }}</td><td class="text-end fw-semibold">{{ number_format($row[8], 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.fx-rates-table, .fx-exposure-table').DataTable({
        pageLength: 8,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search FX' }
    });
</script>
@endpush
