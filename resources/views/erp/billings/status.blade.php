@extends('erp.layout')

@section('title', 'Update Billing Status')
@section('page-title', 'Update Billing Status')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Billing Status</h1>
            <p class="text-muted mb-0">Billing documents use a simple posted status for accounting records.</p>
        </div>
        <a href="{{ route('erp.billings.index') }}" class="btn btn-outline-secondary">Billing CRUD</a>
    </div>
    <form method="post" action="{{ route('erp.billings.status.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Invoice / Document No.</label><select name="invoice_no" class="form-select erp-select">@foreach($billings as $billing)<option>{{ $billing[0] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Customer</label><select name="customer" class="form-select erp-select">@foreach($billings as $billing)<option>{{ $billing[1] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Shipment Job ID</label><select name="shipment_job_id" class="form-select erp-select">@foreach($billings as $billing)<option>{{ $billing[2] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Current Status</label><select name="current_status" class="form-select erp-select"><option>Posted</option></select></div>
            <div class="col-md-4"><label class="form-label">New Status</label><select name="new_status" class="form-select erp-select"><option>Posted</option></select></div>
            <div class="col-md-4"><label class="form-label">Remarks</label><input name="remarks" class="form-control" value="Document is posted"></div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary">Review Posted Status</button></div>
    </form>
@endsection
