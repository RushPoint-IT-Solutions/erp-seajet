@extends('erp.layout')

@section('title', 'New Supplier')
@section('page-title', 'New Supplier')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">New Supplier</h1>
            <p class="text-muted mb-0">Master data form for AP, tax, contact, and purchasing setup.</p>
        </div>
        <a href="{{ route('erp.suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <form class="erp-card p-4" method="get" action="{{ route('erp.suppliers.index') }}">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Supplier ID</label>
                <input class="form-control" value="SUP-004" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Supplier Name</label>
                <input class="form-control" placeholder="Registered supplier name" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">TIN</label>
                <input class="form-control" placeholder="000-000-000-000">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Classification</label>
                <select name="tax_classification" class="form-select erp-select"><option>12% VAT</option><option>Zero-rated</option><option>VAT-exempt</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Terms</label>
                <select name="payment_terms" class="form-select erp-select"><option>COD</option><option>30 Days</option><option>60 Days</option><option>90 Days</option></select>
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
                <input class="form-control" placeholder="AP or sales contact">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="supplier@example.com">
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" placeholder="+63">
            </div>
            <div class="col-md-8">
                <label class="form-label">Address</label>
                <input class="form-control" placeholder="Complete supplier address">
            </div>
            <div class="col-md-4">
                <label class="form-label">Default AP Account</label>
                <select class="form-select erp-select">
                    <option>Accounts Payable Trade</option>
                    <option>Operating Expense Payable</option>
                    <option>Accrued Purchases</option>
                    <option>Input VAT Clearing</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Preferred Payment Method</label>
                <select name="preferred_payment_method" class="form-select erp-select"><option>Cheque</option><option>Bank Transfer</option><option>Online Transfer</option></select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Withholding Tax Setup</label>
                <select name="withholding_tax" class="form-select erp-select"><option>0%</option><option>2% EWT</option><option>5% EWT</option><option>10% EWT</option></select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Supplier</button>
        </div>
    </form>
@endsection
