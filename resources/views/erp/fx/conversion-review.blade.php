@extends('erp.layout')

@section('title', 'FX Conversion Review')
@section('page-title', 'FX Conversion Review')

@section('content')
    <div class="erp-card p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">FX Conversion Review</h1>
                <p class="text-muted mb-0">Review converted book value, settlement value, and realized or unrealized FX amount.</p>
            </div>
            <span class="badge bg-warning text-dark align-self-start">Draft FX Entry</span>
        </div>

        <div class="row g-3 mb-4">
            @foreach($conversion as $label => $value)
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                        <div class="fw-semibold">{{ $value ?: '-' }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-md-4"><div class="erp-card p-3 border"><div class="text-muted small">Book Value PHP</div><div class="h4 fw-bold mb-0">{{ number_format($bookValue, 2) }}</div></div></div>
            <div class="col-md-4"><div class="erp-card p-3 border"><div class="text-muted small">Settlement Value PHP</div><div class="h4 fw-bold mb-0">{{ number_format($settlementValue, 2) }}</div></div></div>
            <div class="col-md-4"><div class="erp-card p-3 border"><div class="text-muted small">FX Gain / Loss</div><div class="h4 fw-bold mb-0">{{ number_format($gainLoss, 2) }}</div></div></div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 no-print">
            <a href="{{ route('erp.fx.conversion') }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('erp.fx.gains-losses') }}" class="btn btn-primary">Post to FX Register</a>
        </div>
    </div>
@endsection
