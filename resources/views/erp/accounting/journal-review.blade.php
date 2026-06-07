@extends('erp.layout')

@section('title', 'Review Journal Entry')
@section('page-title', 'Review Journal Entry')

@section('content')
    <div class="erp-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Journal Entry Ready</h1>
                <p class="text-muted mb-0">Traceability check passed because Shipment Job ID is present.</p>
            </div>
            <span class="badge badge-soft py-2 px-3">Shipment: {{ $journal['shipment_job_id'] ?? '' }}</span>
        </div>
        <div class="row g-3">
            @foreach($journal as $label => $value)
                <div class="col-md-4">
                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <hr>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.accounting.journals') }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i> New Journal</a>
            <a href="{{ route('erp.accounting.coa') }}" class="btn btn-primary"><i class="bi bi-list-columns me-1"></i> Chart of Accounts</a>
        </div>
    </div>
@endsection
