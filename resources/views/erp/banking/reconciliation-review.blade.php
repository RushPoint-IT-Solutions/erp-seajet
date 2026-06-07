@extends('erp.layout')

@section('title', 'Reconciliation Review')
@section('page-title', 'Reconciliation Review')

@section('content')
    <div class="erp-card p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Reconciliation Worksheet Review</h1>
                <p class="text-muted mb-0">Balances are staged for approval and audit logging.</p>
            </div>
            <span class="badge bg-success align-self-start">Balanced Review</span>
        </div>

        <div class="row g-3">
            @foreach($recon as $label => $value)
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                        <div class="fw-semibold">{{ $value ?: '-' }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 no-print">
            <a href="{{ route('erp.banking.reconciliation') }}" class="btn btn-outline-secondary">Edit Worksheet</a>
            <a href="{{ route('erp.banking.daily-cash') }}" class="btn btn-primary">View Daily Cash Position</a>
        </div>
    </div>
@endsection
