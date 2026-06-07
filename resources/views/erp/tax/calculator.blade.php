@extends('erp.layout')

@section('title', 'Tax Calculator')
@section('page-title', 'Tax Calculator')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Tax Calculator</h1>
            <p class="text-muted mb-0">Compute 12% VAT, zero-rated export services, VAT-exempt items, EWT, and CWT.</p>
        </div>
        <a href="{{ route('erp.tax.vat-summary') }}" class="btn btn-outline-secondary no-print"><i class="bi bi-percent me-1"></i>VAT Summary</a>
    </div>

    <form method="post" action="{{ route('erp.tax.calculator.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Document Type</label>
                <select name="document_type" class="form-select">
                    <option>Sales Invoice</option>
                    <option>Statement of Account A</option>
                    <option>Debit Note</option>
                    <option>Supplier Bill</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Reference No.</label>
                <input name="reference_no" class="form-control" value="BI-2026-01042" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Shipment Job ID</label>
                <input name="shipment_job_id" class="form-control" value="JOB-SEA-2026-0422" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Customer / Supplier</label>
                <input name="party_name" class="form-control" value="North Harbor Trading" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select"><option>PHP</option><option>USD</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Base Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="98500.00" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">VAT Classification</label>
                <select name="vat_classification" class="form-select">
                    <option>12% VAT</option>
                    <option>Zero-rated export services</option>
                    <option>VAT-exempt</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Withholding Type</label>
                <select name="withholding_type" class="form-select">
                    <option>None</option>
                    <option>Expanded Withholding Tax</option>
                    <option>Creditable Withholding Tax</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Withholding Rate</label>
                <select name="withholding_tax_rate" class="form-select">
                    <option value="0">0%</option>
                    <option value="1">1%</option>
                    <option value="2" selected>2%</option>
                    <option value="5">5%</option>
                    <option value="10">10%</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4 no-print">
            <a href="{{ route('erp.modules.show', 'tax') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Review Computation</button>
        </div>
    </form>
@endsection
