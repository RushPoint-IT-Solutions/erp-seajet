@extends('erp.layout')

@section('title', 'Customer Billing Templates')
@section('page-title', 'Customer Billing Templates')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Customer Billing Template Setup</h1>
            <p class="text-muted mb-0">BI, SOA, SOA A, Debit Note, OR, and AR form mapping for PHP and USD accounts.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.invoice.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-text me-1"></i> BI</a>
            <a href="{{ route('erp.billings.print-layout.invoice') }}" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i> BI Layout</a>
            <a href="{{ route('erp.billings.print-soa') }}" class="btn btn-outline-secondary"><i class="bi bi-file-text me-1"></i> SOA B</a>
            <a href="{{ route('erp.soa-a.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-layout-text-window me-1"></i> SOA A</a>
            <a href="{{ route('erp.debit-note.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-journal-minus me-1"></i> Debit Note</a>
            <a href="{{ route('erp.acknowledgement-receipt.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-receipt-cutoff me-1"></i> AR</a>
            <a href="{{ route('erp.official-receipt.preview') }}" class="btn btn-primary"><i class="bi bi-receipt me-1"></i> OR</a>
        </div>
    </div>

    <div class="erp-card p-3 mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead>
                <tr>
                    <th>Document</th>
                    <th>Template Code</th>
                    <th>Paper / Form</th>
                    <th>Pre-printed</th>
                    <th>Default AR Account</th>
                    <th>Currency</th>
                </tr>
                </thead>
                <tbody>
                @foreach($templates as $template)
                    <tr>
                        @foreach($template as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold">Credit Terms</h2>
                <div class="d-flex flex-column gap-2">
                    <span class="badge badge-soft text-start py-2">30 Days - standard accounts</span>
                    <span class="badge badge-soft text-start py-2">60 Days - approved strategic accounts</span>
                    <span class="badge badge-soft text-start py-2">90 Days - management approval required</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold">VAT Classification</h2>
                <div class="d-flex flex-column gap-2">
                    <span class="badge text-bg-light text-start py-2">12% VAT</span>
                    <span class="badge text-bg-light text-start py-2">Zero-rated</span>
                    <span class="badge text-bg-light text-start py-2">Exempt</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold">Class</h2>
                <div class="d-flex flex-column gap-2">
                    <span class="badge text-bg-light text-start py-2">MANILA</span>
                    <span class="badge text-bg-light text-start py-2">PAMPANGA</span>
                    <span class="badge text-bg-light text-start py-2">CEBU</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search templates' }
    });
</script>
@endpush
