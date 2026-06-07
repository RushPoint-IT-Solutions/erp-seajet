@extends('erp.layout')

@section('title', 'Record Bank Transaction')
@section('page-title', 'Record Bank Transaction')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Record Deposit / Withdrawal</h1>
            <p class="text-muted mb-0">Capture cashbook entries with shipment tagging, currency, and FX conversion details.</p>
        </div>
        <a href="{{ route('erp.banking.cashbook') }}" class="btn btn-outline-secondary no-print"><i class="bi bi-journal-text me-1"></i>Cashbook</a>
    </div>

    <form method="post" action="{{ route('erp.banking.transactions.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Transaction No.</label>
                <input type="text" name="transaction_no" value="BT-2026-00021" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Bank Account</label>
                <select name="bank_account" class="form-select select2" required>
                    @foreach($accounts as $account)
                        <option value="{{ $account['name'] }}">{{ $account['name'] }} - {{ $account['account_no'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Transaction Date</label>
                <input type="date" name="transaction_date" value="2026-05-24" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="transaction_type" class="form-select" required>
                    <option>Deposit</option>
                    <option>Withdrawal</option>
                    <option>Fund Transfer</option>
                    <option>FX Conversion</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Reference No.</label>
                <input type="text" name="reference_no" value="OR-2026-00619" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Shipment Job ID</label>
                <input type="text" name="shipment_job_id" value="JOB-SEA-2026-0423" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-select" required>
                    <option>PHP</option>
                    <option>USD</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Exchange Rate</label>
                <input type="number" step="0.000001" name="exchange_rate" value="1.000000" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Foreign Amount</label>
                <input type="number" step="0.01" name="foreign_amount" value="43200.00" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">PHP Base Amount</label>
                <input type="number" step="0.01" name="base_amount" value="43200.00" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">Customer deposit applied to shipment collection.</textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4 no-print">
            <a href="{{ route('erp.banking.cashbook') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Review Transaction</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
</script>
@endpush
