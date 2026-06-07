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

    <div class="erp-card p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">As of Date</label>
                <input type="date" class="form-control" value="2026-05-24">
            </div>
            <div class="col-md-3">
                <label class="form-label">Class</label>
                <select class="form-select erp-select">
                    <option>All Classes</option>
                    <option>MANILA</option>
                    <option>PAMPANGA</option>
                    <option>CEBU</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select class="form-select erp-select">
                    <option>All Currency</option>
                    <option>PHP</option>
                    <option>USD</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">VAT</label>
                <select class="form-select erp-select">
                    <option>All VAT</option>
                    <option>12%</option>
                    <option>zero-rated</option>
                    <option>exempt</option>
                </select>
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
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $index => $cell)
                            <td class="{{ $index >= 7 ? 'text-end' : '' }}">{{ $index >= 7 ? number_format($cell, 2) : $cell }}</td>
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
    $('.module-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search aging' }
    });
</script>
@endpush
