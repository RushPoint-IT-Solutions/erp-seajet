@extends('erp.layout')

@php
    $isEdit = $isEdit ?? false;
    $customer = $customer ?? null;
@endphp

@section('title', $isEdit ? 'Edit Customer' : 'New Customer')
@section('page-title', $isEdit ? 'Edit Customer' : 'New Customer')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $isEdit ? 'Edit Customer' : 'New Customer' }}</h1>
            <p class="text-muted mb-0">Master data form for tax, contact, credit, and billing setup.</p>
        </div>
        <a href="{{ $isEdit ? route('erp.customers.show', $customer->id) : route('erp.customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
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

    <form class="erp-card p-4" method="post" action="{{ $isEdit ? route('erp.customers.update', $customer->id) : route('erp.customers.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Customer ID</label>
                <input name="customer_id" class="form-control {{ $errors->has('customer_id') ? 'is-invalid' : '' }}" value="{{ old('customer_id', $isEdit ? ($customer->customer_id ?: $customer->code) : $nextCustomerCode) }}" required>
                @if($errors->has('customer_id'))<div class="invalid-feedback">{{ $errors->first('customer_id') }}</div>@endif
            </div>
            <div class="col-md-5">
                <label class="form-label">Customer Name</label>
                <input name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $isEdit ? $customer->name : '') }}" placeholder="Registered customer name" required>
                @if($errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">TIN</label>
                <input name="tin" class="form-control" value="{{ old('tin', $isEdit ? $customer->tin : '') }}" placeholder="000-000-000-000">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Classification</label>
                <select name="tax_classification" class="form-select erp-select">
                    @foreach(['12% VAT', 'Zero-rated export services', 'VAT-exempt'] as $option)
                        <option value="{{ $option }}" {{ old('tax_classification', $isEdit ? $customer->tax_classification : '12% VAT') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Credit Terms</label>
                <select name="payment_terms" class="form-select erp-select">
                    @foreach(['30 Days', '60 Days', '90 Days'] as $option)
                        <option value="{{ $option }}" {{ old('payment_terms', $isEdit ? $customer->payment_terms : '30 Days') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select erp-select">
                    @foreach(['PHP', 'USD'] as $option)
                        <option value="{{ $option }}" {{ old('currency', $isEdit ? $customer->currency : 'PHP') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Class</label>
                <select name="default_class_code" class="form-select erp-select">
                    @foreach(['MANILA', 'PAMPANGA', 'CEBU'] as $option)
                        <option value="{{ $option }}" {{ old('default_class_code', $isEdit ? $customer->default_class_code : 'MANILA') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Person</label>
                <input name="contact_person" class="form-control" value="{{ old('contact_person', $isEdit ? $customer->contact_person : '') }}" placeholder="Billing contact">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email', $isEdit ? $customer->email : '') }}" placeholder="billing@example.com">
                @if($errors->has('email'))<div class="invalid-feedback">{{ $errors->first('email') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input name="phone" class="form-control" value="{{ old('phone', $isEdit ? $customer->phone : '') }}" placeholder="+63">
            </div>
            <div class="col-md-8">
                <label class="form-label">Billing Address</label>
                <input name="billing_address" class="form-control" value="{{ old('billing_address', $isEdit ? $customer->billing_address : '') }}" placeholder="Complete billing address">
            </div>
            <div class="col-md-4">
                <label class="form-label">Credit Limit</label>
                <input name="credit_limit" class="form-control {{ $errors->has('credit_limit') ? 'is-invalid' : '' }}" value="{{ old('credit_limit', $isEdit ? $customer->credit_limit : '0.00') }}">
                @if($errors->has('credit_limit'))<div class="invalid-feedback">{{ $errors->first('credit_limit') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Credit Status</label>
                <select name="credit_status" class="form-select erp-select">
                    @foreach(['Within Limit', 'Watchlist', 'On Hold'] as $option)
                        <option value="{{ $option }}" {{ old('credit_status', $isEdit ? $customer->credit_status : 'Within Limit') === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Default AR Account</label>
                <select name="default_ar_account_code" class="form-select erp-select">
                    @foreach([
                        'AR_TRADE_BI' => 'A/Trade-Billing Invoice',
                        'AR_REIMBURSABLE' => 'A/R Reimbursable Charges',
                        'AR_TRADE_SOAA' => 'A/R Trade Metro',
                        'AR_TRADE_DN' => 'A/R Trade-Debit Note',
                    ] as $code => $label)
                        <option value="{{ $code }}" {{ old('default_ar_account_code', $isEdit ? $customer->default_ar_account_code : 'AR_TRADE_BI') === $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ $isEdit ? route('erp.customers.show', $customer->id) : route('erp.customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> {{ $isEdit ? 'Update Customer' : 'Save Customer' }}</button>
        </div>
    </form>
@endsection
