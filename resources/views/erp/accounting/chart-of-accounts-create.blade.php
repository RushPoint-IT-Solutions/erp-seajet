@extends('erp.layout')

@php
    $isEdit = $isEdit ?? false;
    $account = $account ?? null;
@endphp

@section('title', $isEdit ? 'Edit Account' : 'New Account')
@section('page-title', $isEdit ? 'Edit Account' : 'New Account')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $isEdit ? 'Edit Account' : 'New Account' }}</h1>
            <p class="text-muted mb-0">Define the account code, category, and hierarchy used across journals and reports.</p>
        </div>
        <a href="{{ route('erp.accounting.coa') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
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

    <form class="erp-card p-4" method="post" action="{{ $isEdit ? route('erp.accounting.coa.update', $account->id) : route('erp.accounting.coa.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Account Code</label>
                <input name="code" class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}" value="{{ old('code', $isEdit ? $account->code : '') }}" required>
                @if($errors->has('code'))<div class="invalid-feedback">{{ $errors->first('code') }}</div>@endif
            </div>
            <div class="col-md-5">
                <label class="form-label">Account Name</label>
                <input name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $isEdit ? $account->name : '') }}" placeholder="e.g. Cash in Bank-BDO(PHP)" required>
                @if($errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <input name="type" list="accountTypeOptions" class="form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" value="{{ old('type', $isEdit ? $account->type : '') }}" required>
                <datalist id="accountTypeOptions">
                    @foreach($typeOptions as $option)
                        <option value="{{ $option }}"></option>
                    @endforeach
                </datalist>
                @if($errors->has('type'))<div class="invalid-feedback">{{ $errors->first('type') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="account_category_id" class="form-select erp-select {{ $errors->has('account_category_id') ? 'is-invalid' : '' }}" required>
                    <option value="">Select category...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) old('account_category_id', $isEdit ? $account->account_category_id : '') === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }} ({{ ucfirst($category->normal_balance) }})
                        </option>
                    @endforeach
                </select>
                @if($errors->has('account_category_id'))<div class="invalid-feedback">{{ $errors->first('account_category_id') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Parent Account</label>
                <select name="parent_id" class="form-select erp-select {{ $errors->has('parent_id') ? 'is-invalid' : '' }}">
                    <option value="">None</option>
                    @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}" {{ (string) old('parent_id', $isEdit ? $account->parent_id : '') === (string) $parent->id ? 'selected' : '' }}>
                            {{ $parent->code }} - {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                @if($errors->has('parent_id'))<div class="invalid-feedback">{{ $errors->first('parent_id') }}</div>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Beginning Balance</label>
                <input name="opening_balance" type="number" step="0.01" class="form-control text-end {{ $errors->has('opening_balance') ? 'is-invalid' : '' }}" value="{{ old('opening_balance', $isEdit ? number_format((float) $account->opening_balance, 2, '.', '') : '0.00') }}">
                @if($errors->has('opening_balance'))<div class="invalid-feedback">{{ $errors->first('opening_balance') }}</div>@endif
                <div class="form-text">Enter the accounting sign required for this account. Posted opening-balance journals should be corrected through a reversing or correcting journal, not by changing their display sign.</div>
            </div>
            <div class="col-12">
                <label class="form-check">
                    <input type="checkbox" name="show_in_ap" value="1" class="form-check-input" id="showInAp"
                           {{ old('show_in_ap', $isEdit && !empty($account->show_in_ap) ? '1' : '') ? 'checked' : '' }}>
                    <span class="form-check-label fw-semibold">Show in AP Item Account dropdown</span>
                </label>
                <div class="form-text" id="showInApHelp">Controls whether this account appears for line items in AP vendor bills. Any active account may be enabled.</div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.accounting.coa') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> {{ $isEdit ? 'Update Account' : 'Save Account' }}</button>
        </div>
    </form>
@endsection
