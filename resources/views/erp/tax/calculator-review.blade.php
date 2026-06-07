@extends('erp.layout')

@section('title', 'Tax Computation Review')
@section('page-title', 'Tax Computation Review')

@section('content')
    <div class="erp-card p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Tax Computation Review</h1>
                <p class="text-muted mb-0">Computed VAT and withholding tax are ready for audit trail posting.</p>
            </div>
            <span class="badge bg-warning text-dark align-self-start">Draft Computation</span>
        </div>

        <div class="row g-3 mb-4">
            @foreach($tax as $label => $value)
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                        <div class="fw-semibold">{{ $value ?: '-' }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-md-4"><div class="erp-card p-3 border"><div class="text-muted small">VAT Amount</div><div class="h4 fw-bold mb-0">{{ number_format($vatAmount, 2) }}</div></div></div>
            <div class="col-md-4"><div class="erp-card p-3 border"><div class="text-muted small">Withholding Amount</div><div class="h4 fw-bold mb-0">{{ number_format($withholdingAmount, 2) }}</div></div></div>
            <div class="col-md-4"><div class="erp-card p-3 border"><div class="text-muted small">Net Amount</div><div class="h4 fw-bold mb-0">{{ number_format($netAmount, 2) }}</div></div></div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 no-print">
            <a href="{{ route('erp.tax.calculator') }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('erp.tax.audit-trail') }}" class="btn btn-primary">Post to Audit Trail</a>
        </div>
    </div>
@endsection
