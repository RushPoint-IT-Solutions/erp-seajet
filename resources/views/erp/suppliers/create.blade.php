@extends('erp.layout')

@section('title', 'New Vendor')
@section('page-title', 'New Vendor')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">New Vendor</h1>
            <p class="text-muted mb-0">Master data form for Accounts Payable, tax, contact, and payment setup.</p>
        </div>
        <a href="{{ route('erp.suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix the highlighted fields.</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="erp-card p-4" method="post" action="{{ route('erp.suppliers.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Vendor ID</label>
                <input name="supplier_id" class="form-control {{ $errors->has('supplier_id') ? 'is-invalid' : '' }}" value="{{ old('supplier_id', $nextSupplierCode) }}" required>
                @if($errors->has('supplier_id'))<div class="invalid-feedback">{{ $errors->first('supplier_id') }}</div>@endif
            </div>
            <div class="col-md-5">
                <label class="form-label">Vendor Name</label>
                <input name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}" placeholder="Registered vendor name" required>
                @if($errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">TIN</label>
                <input name="tin" class="form-control" value="{{ old('tin') }}" placeholder="000-000-000-000">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Classification</label>
                <select name="tax_classification" class="form-select erp-select">
                    @foreach(['12% VAT', 'Zero-rated', 'VAT-exempt'] as $option)
                        <option value="{{ $option }}" {{ old('tax_classification', '12% VAT') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Terms</label>
                <select name="payment_terms" class="form-select erp-select">
                    @foreach(['COD', '30 Days', '60 Days', '90 Days'] as $option)
                        <option value="{{ $option }}" {{ old('payment_terms', '30 Days') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select erp-select">
                    @foreach(['PHP', 'USD'] as $option)
                        <option value="{{ $option }}" {{ old('currency', 'PHP') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Class</label>
                <select name="default_class_code" class="form-select erp-select">
                    @foreach(['MANILA', 'PAMPANGA', 'CEBU'] as $option)
                        <option value="{{ $option }}" {{ old('default_class_code', 'MANILA') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Person</label>
                <input name="contact_person" class="form-control" value="{{ old('contact_person') }}" placeholder="AP contact">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" placeholder="vendor@example.com">
                @if($errors->has('email'))<div class="invalid-feedback">{{ $errors->first('email') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+63">
            </div>
            <div class="col-md-8">
                <label class="form-label">Address</label>
                <input name="address" class="form-control" value="{{ old('address') }}" placeholder="Complete vendor address">
            </div>
            <div class="col-md-4">
                <label class="form-label">Default AP Account</label>
                <select name="default_ap_account" class="form-select erp-select">
                    @foreach(['Accounts Payable Trade', 'Operating Expense Payable', 'Accrued Purchases', 'Input VAT Clearing'] as $option)
                        <option value="{{ $option }}" {{ old('default_ap_account', 'Accounts Payable Trade') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Default Expense Account</label>
                <select name="default_expense_account_id" class="form-select erp-select">
                    <option value=""></option>
                    @foreach($expenseAccounts as $account)
                        <option value="{{ $account->id }}" {{ (string) old('default_expense_account_id') === (string) $account->id ? 'selected' : '' }}>{{ $account->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Preferred Payment Method</label>
                <select name="preferred_payment_method" class="form-select erp-select">
                    @foreach(['Cheque', 'Bank Transfer', 'Online Transfer'] as $option)
                        <option value="{{ $option }}" {{ old('preferred_payment_method', 'Cheque') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Withholding Tax Setup</label>
                <select name="withholding_tax" class="form-select erp-select">
                    @foreach(['0%', '2% EWT', '5% EWT', '10% EWT'] as $option)
                        <option value="{{ $option }}" {{ old('withholding_tax', '0%') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Vendor</button>
        </div>
    </form>
@endsection
