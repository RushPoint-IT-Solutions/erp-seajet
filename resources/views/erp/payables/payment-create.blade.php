@extends('erp.layout')

@section('title', 'AP Payment Processing')
@section('page-title', 'AP Payment Processing')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Payment Processing</h1>
            <p class="text-muted mb-0">Process approved supplier bills by bank, cheque, or online transfer in PHP or USD.</p>
        </div>
        <a href="{{ route('erp.payables.approvals') }}" class="btn btn-outline-secondary"><i class="bi bi-check2-square me-1"></i> Approvals</a>
    </div>

    <form class="erp-card p-4" method="post" action="{{ route('erp.payables.payments.review') }}">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Payment No</label>
                <input name="payment_no" class="form-control" value="PV-2026-00313" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment Date</label>
                <input name="payment_date" type="date" class="form-control" value="2026-05-24" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Bill No</label>
                <input name="bill_no" class="form-control" value="BILL-2026-00492" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Shipment Job ID</label>
                <input name="shipment_job_id" class="form-control" value="JOB-SEA-2026-0423" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Supplier ID</label>
                <select name="supplier_id" class="form-select erp-select">
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier['supplier_id'] }}">{{ $supplier['supplier_id'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Supplier Name</label>
                <select name="supplier_name" class="form-select erp-select">
                    @foreach($suppliers as $supplier)
                        <option>{{ $supplier['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select erp-select"><option>Bank</option><option>Cheque</option><option>Online Transfer</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Bank Account</label>
                <select name="bank_account" class="form-select erp-select"><option>BPI Operating - PHP</option><option>Metrobank USD Collections - USD</option><option>BDO Payroll - PHP</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Check No</label>
                <input name="check_no" class="form-control" value="CHK-883121">
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select erp-select"><option>PHP</option><option>USD</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount</label>
                <input name="amount" class="form-control" value="121505.00" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Approval Status</label>
                <select name="approval_status" class="form-select erp-select"><option>For Approval</option><option>Approved</option><option>Released</option></select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.modules.show', 'payables') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i> Review Payment</button>
        </div>
    </form>
@endsection
