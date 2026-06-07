@extends('erp.layout')

@section('title', 'Review AP Payment')
@section('page-title', 'Review AP Payment')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">AP Payment Ready</h1>
            <p class="text-muted mb-0">Payment details captured for approval, voucher printing, and check processing.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.payables.check-voucher') }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Check Voucher</a>
            <a href="{{ route('erp.payables.check-print') }}" class="btn btn-primary"><i class="bi bi-bank me-1"></i> Print Check</a>
        </div>
    </div>
    <div class="erp-card p-4">
        <div class="row g-3">
            @foreach($payment as $label => $value)
                <div class="col-md-4">
                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
