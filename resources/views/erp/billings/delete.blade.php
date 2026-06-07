@extends('erp.layout')

@section('title', 'Void Billing')
@section('page-title', 'Void Billing')

@section('content')
    <form method="post" action="{{ route('erp.billings.delete.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <h1 class="h4 fw-bold mb-1">Void Billing {{ $invoiceNo }}</h1>
        <p class="text-muted mb-4">Posted billing documents are voided or cancelled instead of hard deleted for audit and tax compliance.</p>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Invoice No.</label><input name="invoice_no" class="form-control" value="{{ $invoiceNo }}" required></div>
            <div class="col-md-3"><label class="form-label">Customer</label><input name="customer" class="form-control" value="North Harbor Trading" required></div>
            <div class="col-md-3"><label class="form-label">Shipment Job ID</label><input name="shipment_job_id" class="form-control" value="JOB-SEA-2026-0422" required></div>
            <div class="col-md-3"><label class="form-label">Action</label><select name="delete_action" class="form-select"><option>Void</option><option>Cancel Draft</option><option>Reverse Posted Billing</option></select></div>
            <div class="col-12"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3">Billing document voided after accounting review.</textarea></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.billings.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-danger">Review Void</button>
        </div>
    </form>
@endsection
