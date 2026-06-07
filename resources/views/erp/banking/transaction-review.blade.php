@extends('erp.layout')

@section('title', 'Bank Transaction Review')
@section('page-title', 'Bank Transaction Review')

@section('content')
    <div class="erp-card p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Transaction Ready for Posting</h1>
                <p class="text-muted mb-0">Review the cashbook entry before final database posting is connected.</p>
            </div>
            <span class="badge bg-warning text-dark align-self-start">Draft Review</span>
        </div>

        <div class="row g-3">
            @foreach($transaction as $label => $value)
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                        <div class="fw-semibold">{{ $value ?: '-' }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="alert alert-info mt-4 mb-0">
            <i class="bi bi-info-circle me-1"></i>This entry keeps the required shipment trace through <strong>{{ $transaction['shipment_job_id'] ?? 'Shipment Job ID' }}</strong>.
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 no-print">
            <a href="{{ route('erp.banking.transactions.create') }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('erp.banking.cashbook') }}" class="btn btn-primary">Post to Cashbook</a>
        </div>
    </div>
@endsection
