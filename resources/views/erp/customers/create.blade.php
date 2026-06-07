@extends('erp.layout')

@section('title', 'New Customer')
@section('page-title', 'New Customer')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">New Customer</h1>
            <p class="text-muted mb-0">Master data form for tax, contact, credit, and billing setup.</p>
        </div>
        <a href="{{ route('erp.customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form class="erp-card p-4" method="get" action="{{ route('erp.customers.index') }}">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Customer ID</label>
                <input class="form-control" value="CUST-004" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Customer Name</label>
                <input class="form-control" placeholder="Registered customer name" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">TIN</label>
                <input class="form-control" placeholder="000-000-000-000">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Classification</label>
                <select name="tax_classification" class="form-select erp-select"><option>12% VAT</option><option>Zero-rated export services</option><option>VAT-exempt</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Credit Terms</label>
                <select name="payment_terms" class="form-select erp-select"><option>30 Days</option><option>60 Days</option><option>90 Days</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select erp-select"><option>PHP</option><option>USD</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Class</label>
                <select class="form-select erp-select"><option>MANILA</option><option>PAMPANGA</option><option>CEBU</option></select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Person</label>
                <input class="form-control" placeholder="Billing contact">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="billing@example.com">
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" placeholder="+63">
            </div>
            <div class="col-md-8">
                <label class="form-label">Billing Address</label>
                <input class="form-control" placeholder="Complete billing address">
            </div>
            <div class="col-md-4">
                <label class="form-label">Credit Limit</label>
                <input name="credit_limit" class="form-control" value="0.00">
            </div>
            <div class="col-md-4">
                <label class="form-label">Credit Status</label>
                <select name="credit_status" class="form-select erp-select"><option>Within Limit</option><option>Watchlist</option><option>On Hold</option></select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Default AR Account</label>
                <select class="form-select erp-select">
                    <option>A/Trade-Billing Invoice</option>
                    <option>A/R Reimbursable Charges</option>
                    <option>A/R Trade-SOA A</option>
                    <option>A/R Trade-Debit Note</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Customer</button>
        </div>
    </form>
@endsection
