@extends('erp.layout')

@section('title', 'Customer Aging Report')
@section('page-title', 'Customer Aging Report')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Aging Report</h1>
            <p class="text-muted mb-0">Current, 30, 60, and 90+ aging by customer, job/shipment, VAT, class, and currency.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.export', 'aging-excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <a href="{{ route('erp.export', 'aging-pdf') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>

    <form class="erp-card p-3 mb-3" method="get" action="{{ route('erp.customer-billing.aging') }}">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">As of Date</label>
                <input type="date" name="as_of" class="form-control" value="{{ $filters['as_of'] ?? date('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Class</label>
                <select name="class" class="form-select erp-select">
                    @foreach(['All Classes', 'MANILA', 'PAMPANGA', 'CEBU'] as $option)
                        <option value="{{ $option }}" {{ ($filters['class'] ?? 'All Classes') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select erp-select">
                    @foreach(['All Currency', 'PHP', 'USD'] as $option)
                        <option value="{{ $option }}" {{ ($filters['currency'] ?? 'All Currency') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">VAT</label>
                <select name="vat" class="form-select erp-select">
                    @foreach(['All VAT', '12%', 'zero-rated', 'exempt'] as $option)
                        <option value="{{ $option }}" {{ ($filters['vat'] ?? 'All VAT') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply Filters</button>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">Current</div>
                <div class="h4 fw-bold mb-0">PHP {{ number_format($summary['current'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">30 Days</div>
                <div class="h4 fw-bold mb-0">PHP {{ number_format($summary['thirty'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">60 Days</div>
                <div class="h4 fw-bold mb-0">PHP {{ number_format($summary['sixty'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="erp-card kpi-card p-3 h-100">
                <div class="text-muted small">90+ Days</div>
                <div class="h4 fw-bold mb-0">PHP {{ number_format($summary['ninety'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Customer</th>
                    <th>Job/Shipment ID</th>
                    <th>Invoice No</th>
                    <th>Currency</th>
                    <th>VAT</th>
                    <th>Class</th>
                    <th class="text-end">Current</th>
                    <th class="text-end">30</th>
                    <th class="text-end">60</th>
                    <th class="text-end">90+</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($row as $index => $cell)
                            <td class="{{ $index >= 7 ? 'text-end' : '' }}">{{ $index >= 7 ? number_format($cell, 2) : $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No unpaid active invoices found for the selected filters.</td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot>
                <tr class="fw-bold">
                    <td colspan="7" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($summary['current'] ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($summary['thirty'] ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($summary['sixty'] ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($summary['ninety'] ?? 0, 2) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search aging' }
    });
</script>
@endpush
