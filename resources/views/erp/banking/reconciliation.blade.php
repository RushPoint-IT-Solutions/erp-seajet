@extends('erp.layout')

@section('title', 'Bank Reconciliation')
@section('page-title', 'Bank Reconciliation')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Bank Reconciliation</h1>
            <p class="text-muted mb-0">Compare bank statement balances against book balances and outstanding transactions.</p>
        </div>
        <a href="{{ route('erp.banking.cashbook') }}" class="btn btn-outline-secondary no-print"><i class="bi bi-journal-text me-1"></i>Cashbook</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <form method="post" action="{{ route('erp.banking.reconciliation.review') }}" class="erp-card p-4 h-100">
                {{ csrf_field() }}
                <h2 class="h6 fw-bold mb-3">Reconciliation Worksheet</h2>
                <div class="mb-3">
                    <label class="form-label">Bank Account</label>
                    <select name="bank_account" class="form-select select2" required>
                        @foreach($accounts as $account)
                            <option value="{{ $account['name'] }}">{{ $account['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Statement Date</label>
                    <input type="date" name="statement_date" value="2026-05-24" class="form-control" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Book Balance</label>
                        <input type="number" step="0.01" name="book_balance" value="945195.00" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Balance</label>
                        <input type="number" step="0.01" name="bank_balance" value="1066700.00" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Outstanding Deposits</label>
                        <input type="number" step="0.01" name="outstanding_deposits" value="0.00" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Outstanding Withdrawals</label>
                        <input type="number" step="0.01" name="outstanding_withdrawals" value="121505.00" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adjusted Balance</label>
                        <input type="number" step="0.01" name="adjusted_balance" value="945195.00" class="form-control" required>
                    </div>
                </div>
                <div class="d-grid mt-4 no-print">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i>Review Reconciliation</button>
                </div>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Open Matching Items</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle reconciliation-table w-100">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Currency</th>
                            <th class="text-end">Amount</th>
                            <th>Notes</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr>
                                @foreach($row as $index => $cell)
                                    <td class="{{ $index === 4 ? 'text-end fw-semibold' : '' }}">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
    $('.reconciliation-table').DataTable({
        pageLength: 8,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search items' }
    });
</script>
@endpush
