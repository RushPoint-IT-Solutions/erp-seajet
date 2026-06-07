@extends('erp.layout')

@section('title', 'Cashbook Management')
@section('page-title', 'Cashbook Management')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Cashbook Management</h1>
            <p class="text-muted mb-0">Track deposits, withdrawals, running balances, and reconciliation status across bank accounts.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.banking.transactions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Record Transaction</a>
            <a href="{{ route('erp.banking.reconciliation') }}" class="btn btn-outline-secondary"><i class="bi bi-check2-square me-1"></i>Reconcile</a>
            <a href="{{ route('erp.export', 'excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($accounts as $account)
            <div class="col-md-4">
                <div class="erp-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-muted small">{{ $account['code'] }}</div>
                            <h2 class="h6 fw-bold mb-1">{{ $account['name'] }}</h2>
                            <div class="small text-muted">{{ $account['bank'] }} · {{ $account['account_no'] }}</div>
                        </div>
                        <span class="badge badge-soft">{{ $account['currency'] }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Book Balance</span>
                        <strong>{{ $account['book_balance'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">Bank Balance</span>
                        <strong>{{ $account['bank_balance'] }}</strong>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle cashbook-table w-100">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Currency</th>
                    <th class="text-end">Deposit</th>
                    <th class="text-end">Withdrawal</th>
                    <th class="text-end">Running Balance</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($transactions as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                        <td><span class="badge {{ $row[2] === 'Deposit' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $row[2] }}</span></td>
                        <td>{{ $row[3] }}</td>
                        <td>{{ $row[4] }}</td>
                        <td class="text-end">{{ number_format($row[5], 2) }}</td>
                        <td class="text-end">{{ number_format($row[6], 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row[7], 2) }}</td>
                        <td>{{ $row[8] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.cashbook-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search cashbook' }
    });
</script>
@endpush
