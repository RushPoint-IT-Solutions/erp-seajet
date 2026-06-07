@extends('erp.layout')

@section('title', 'VAT Summary')
@section('page-title', 'VAT Summary')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">VAT Summary</h1>
            <p class="text-muted mb-0">Output VAT, input VAT, zero-rated export services, and VAT-exempt transactions.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.tax.calculator') }}" class="btn btn-primary"><i class="bi bi-calculator me-1"></i>Tax Calculator</a>
            <a href="{{ route('erp.export', 'excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="erp-card p-3"><div class="text-muted small">Output VAT</div><div class="h4 fw-bold mb-0">{{ number_format($outputVat, 2) }}</div></div></div>
        <div class="col-md-4"><div class="erp-card p-3"><div class="text-muted small">Input VAT</div><div class="h4 fw-bold mb-0">{{ number_format($inputVat, 2) }}</div></div></div>
        <div class="col-md-4"><div class="erp-card p-3"><div class="text-muted small">VAT Payable</div><div class="h4 fw-bold mb-0">{{ number_format($vatPayable, 2) }}</div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Sales VAT Summary</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle tax-table w-100">
                        <thead>
                        <tr>
                            <th>Date</th><th>Document</th><th>Customer</th><th>Shipment</th><th>VAT Class</th><th>Currency</th><th class="text-end">Base</th><th class="text-end">VAT</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sales as $row)
                            <tr>
                                @for($i = 0; $i < 8; $i++)
                                    <td class="{{ $i >= 6 ? 'text-end fw-semibold' : '' }}">{{ $i >= 6 ? number_format($row[$i], 2) : $row[$i] }}</td>
                                @endfor
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Purchase VAT Summary</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle tax-table w-100">
                        <thead>
                        <tr>
                            <th>Date</th><th>Bill</th><th>Supplier</th><th>Shipment</th><th>VAT Class</th><th>Currency</th><th class="text-end">Base</th><th class="text-end">VAT</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($purchases as $row)
                            <tr>
                                @for($i = 0; $i < 8; $i++)
                                    <td class="{{ $i >= 6 ? 'text-end fw-semibold' : '' }}">{{ $i >= 6 ? number_format($row[$i], 2) : $row[$i] }}</td>
                                @endfor
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
    $('.tax-table').DataTable({
        pageLength: 8,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search tax records' }
    });
</script>
@endpush
