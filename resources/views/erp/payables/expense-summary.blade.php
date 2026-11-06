@extends('erp.layout')
@section('title', 'Expense Summary')
@section('page-title', 'Expense Summary')
@push('styles')
<style>
@media print{.erp-sidebar,.erp-topbar,.no-print{display:none!important}.erp-main,.erp-content{margin:0!important;padding:0!important}.table-responsive{overflow:visible!important}@page{size:A4 landscape;margin:12mm}}
</style>
@endpush
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
    <div><h1 class="h3 fw-bold">Expense Summary</h1><p class="text-muted">Supplier bills with paid amounts and outstanding balances.</p></div>
    <div class="no-print">
        <a href="{{ route('erp.expenses.index') }}" class="btn btn-outline-secondary">Accounts Payable</a>
        <a href="{{ route('erp.expenses.summary', array_merge($filters, ['format' => 'excel'])) }}" class="btn btn-success">Download Excel</a>
        <button class="btn btn-primary" onclick="window.print()">Print / PDF</button>
    </div>
</div>
<p><strong>Bill dates:</strong> {{ $filters['from'] }} to {{ $filters['to'] }} &middot; {{ ucfirst($filters['status']) }} &middot; {{ strtoupper($filters['currency']) }}</p>
<p class="small text-muted">All amounts are in PHP using the bill's recorded conversion rate. Currency identifies the original bill currency. Based on bill totals and current payments; partially paid bills are included under Unpaid. Dates filter the bill date.</p>
<form method="get" action="{{ route('erp.expenses.summary') }}" class="erp-card p-3 mb-3 no-print">
    <div class="row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label" for="expense-from">From</label><input id="expense-from" type="date" name="from" value="{{ $filters['from'] }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label" for="expense-to">To</label><input id="expense-to" type="date" name="to" value="{{ $filters['to'] }}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="expense-status">Status</label><select id="expense-status" name="status" class="form-select">@foreach(['all' => 'Paid and Unpaid', 'paid' => 'Paid', 'unpaid' => 'Unpaid'] as $value => $label)<option value="{{ $value }}" {{ $filters['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="expense-currency">Currency</label><select id="expense-currency" name="currency" class="form-select">@foreach(['all' => 'All Currency', 'PHP' => 'PHP', 'USD' => 'USD'] as $value => $label)<option value="{{ $value }}" {{ $filters['currency'] === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary">Generate Report</button></div>
    </div>
</form>
<div class="erp-card p-3"><div class="table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Supplier</th><th>Currency</th><th class="text-end">Total Bills</th><th class="text-end">Bill Amount</th><th class="text-end">Paid Amount</th><th class="text-end">Unpaid Balance</th></tr></thead>
        <tbody>@forelse($rows as $row)<tr><td>{{ $row['Supplier'] }}</td><td>{{ $row['Currency'] }}</td><td class="text-end">{{ $row['Total Bills'] }}</td>@foreach(['Bill Amount', 'Paid Amount', 'Unpaid Balance'] as $column)<td class="text-end">{{ number_format($row[$column], 2) }}</td>@endforeach</tr>
        @empty<tr><td colspan="6" class="text-center py-4">No bills found for the selected filters.</td></tr>@endforelse</tbody>
        <tfoot>@foreach($totals as $currency => $total)<tr class="fw-bold"><td>Total</td><td>{{ $currency }}</td><td class="text-end">{{ $total['Total Bills'] }}</td>@foreach(['Bill Amount', 'Paid Amount', 'Unpaid Balance'] as $column)<td class="text-end">{{ number_format($total[$column], 2) }}</td>@endforeach</tr>@endforeach</tfoot>
    </table>
</div></div>
@endsection
