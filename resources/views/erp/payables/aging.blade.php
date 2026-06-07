@extends('erp.layout')

@section('title', 'AP Aging')
@section('page-title', 'AP Aging')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">AP Aging Report</h1>
            <p class="text-muted mb-0">Outstanding supplier bills by shipment, supplier, and currency.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.export', 'ap-aging-excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <a href="{{ route('erp.export', 'ap-aging-pdf') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
        </div>
    </div>
    <div class="erp-card p-3">
        <table class="table table-hover align-middle module-table w-100">
            <thead><tr><th>Supplier ID</th><th>Supplier</th><th>Shipment Job ID</th><th>Bill No</th><th>Currency</th><th class="text-end">Current</th><th class="text-end">30</th><th class="text-end">60</th><th class="text-end">90+</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $index => $cell)
                        <td class="{{ $index >= 5 ? 'text-end' : '' }}">{{ $index >= 5 ? number_format($cell, 2) : $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({ pageLength: 10, lengthChange: false });
</script>
@endpush
