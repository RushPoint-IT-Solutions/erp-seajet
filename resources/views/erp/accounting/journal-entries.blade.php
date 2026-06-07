@extends('erp.layout')

@section('title', 'Journal Entry')
@section('page-title', 'Journal Entry')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Create Journal Entry</h1>
            <p class="text-muted mb-0">Every journal entry must be traceable to a Shipment Job ID.</p>
        </div>
        <a href="{{ route('erp.accounting.coa') }}" class="btn btn-outline-secondary"><i class="bi bi-list-columns me-1"></i> Chart of Accounts</a>
    </div>

    <form class="erp-card p-4" method="post" action="{{ route('erp.accounting.journals.review') }}">
        {{ csrf_field() }}
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <div>Shipment Job ID is required for audit trail, invoices, expenses, and manual journal entries.</div>
        </div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Journal No</label>
                <input name="journal_no" class="form-control" value="JV-2026-00218" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Journal Date</label>
                <input name="journal_date" type="date" class="form-control" value="2026-05-24" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Shipment Job ID</label>
                <input name="shipment_job_id" class="form-control" value="JOB-SEA-2026-0423" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Reference No</label>
                <input name="reference_no" class="form-control" value="BI-2026-01043">
            </div>
            <div class="col-md-4">
                <label class="form-label">Debit Account</label>
                <select name="debit_account" class="form-select erp-select">
                    <option>1200 - Accounts Receivable</option>
                    <option>1210 - A/Trade-Billing Invoice</option>
                    <option>5100 - Operating Expenses</option>
                    <option>1000 - Cash on Hand</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Credit Account</label>
                <select name="credit_account" class="form-select erp-select">
                    <option>4000 - Service Revenue</option>
                    <option>2200 - Output VAT Payable</option>
                    <option>2100 - Accounts Payable</option>
                    <option>1010 - Bank - Operating Account</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Amount</label>
                <input name="amount" class="form-control" value="98500.00" required>
            </div>
            <div class="col-12">
                <label class="form-label">Memo</label>
                <textarea name="memo" class="form-control" rows="3">Billing entry linked to shipment job.</textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.modules.show', 'accounting') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Review Journal</button>
        </div>
    </form>
@endsection
