@extends('erp.layout')

@section('title', 'Currency Conversion')
@section('page-title', 'Currency Conversion')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Currency Conversion</h1>
            <p class="text-muted mb-0">Convert USD and PHP using document and settlement rates for realized FX gain or loss.</p>
        </div>
        <a href="{{ route('erp.fx.index') }}" class="btn btn-outline-secondary no-print"><i class="bi bi-arrow-left me-1"></i>FX Dashboard</a>
    </div>

    <form method="post" action="{{ route('erp.fx.conversion.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Reference No.</label>
                <input name="reference_no" class="form-control" value="OR-2026-00618" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Shipment Job ID</label>
                <input name="shipment_job_id" class="form-control" value="JOB-SEA-2026-0398" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Party / Account</label>
                <input name="party_name" class="form-control" value="Blue Anchor Logistics" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Source Currency</label>
                <select name="source_currency" class="form-select"><option>USD</option><option>PHP</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Target Currency</label>
                <select name="target_currency" class="form-select"><option>PHP</option><option>USD</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Conversion Type</label>
                <select name="conversion_type" class="form-select"><option>Realized</option><option>Unrealized Revaluation</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Conversion Date</label>
                <input type="date" name="conversion_date" class="form-control" value="2026-05-24" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Foreign Amount</label>
                <input type="number" step="0.01" name="foreign_amount" class="form-control" value="1840.00" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Document Rate</label>
                <input type="number" step="0.000001" name="document_rate" class="form-control" value="57.900000" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Settlement / Revaluation Rate</label>
                <input type="number" step="0.000001" name="settlement_rate" class="form-control" value="58.000000" required>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4 no-print">
            <a href="{{ route('erp.fx.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Review FX</button>
        </div>
    </form>
@endsection
