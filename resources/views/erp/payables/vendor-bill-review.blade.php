@extends('erp.layout')

@section('title', 'Review Vendor Bill')
@section('page-title', 'Review Vendor Bill')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Vendor Bill Ready for Approval</h1>
            <p class="text-muted mb-0">Shipment-linked AP bill captured with withholding tax and multi-currency details.</p>
        </div>
        <a href="{{ route('erp.payables.approvals') }}" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i> Send for Approval</a>
    </div>
    <div class="erp-card p-4">
        <div class="row g-3">
            @foreach($bill as $label => $value)
                @continue(is_array($value))
                <div class="col-md-4">
                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        @if(isset($bill['expense_account']))
            <hr>
            <h2 class="h6 fw-bold">Expense Lines</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Account</th>
                        <th>Tax</th>
                        <th class="text-end">Amount</th>
                        <th>Memo</th>
                        <th>Customer:Job</th>
                        <th>Billable</th>
                        <th>Class</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($bill['expense_account'] as $index => $account)
                        <tr>
                            <td>{{ $account }}</td>
                            <td>{{ $bill['expense_tax'][$index] ?? '' }}</td>
                            <td class="text-end">{{ $bill['expense_amount'][$index] ?? '' }}</td>
                            <td>{{ $bill['expense_memo'][$index] ?? '' }}</td>
                            <td>{{ $bill['expense_customer_job'][$index] ?? '' }}</td>
                            <td>{{ $bill['expense_billable'][$index] ?? '' }}</td>
                            <td>{{ $bill['expense_class'][$index] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('erp.expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-list-ul me-1"></i> Expense List</a>
            <a href="{{ route('erp.payables.vendor-bills.create') }}" class="btn btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i> Enter Another Bill</a>
        </div>
    </div>
@endsection
