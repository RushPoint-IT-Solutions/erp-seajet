@extends('erp.layout')

@section('title', 'Payment Receipt')
@section('page-title', 'Payment Receipt')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Payment Recorded</h1>
            <p class="text-muted mb-0">Receipt details are ready for printing or customer email.</p>
        </div>
        <a href="{{ route('erp.official-receipt.preview') }}" class="btn btn-primary"><i class="bi bi-printer me-1"></i> Print Receipt</a>
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
        <hr>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.payments.create') }}" class="btn btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i> New Payment</a>
            <a href="{{ route('erp.modules.show', 'collections') }}" class="btn btn-outline-secondary">Collections</a>
        </div>
    </div>
@endsection
