@extends('erp.layout')
@section('title', 'AP Aging Report')
@section('page-title', 'AP Aging Report')
@push('styles')
<style>
.aging-table{width:100%;border-collapse:collapse;font-size:12px}.aging-table td,.aging-table th{padding:10px;border-bottom:1px solid #e5eaf0}.aging-table thead,.aging-company{background:#f5f8fb}.aging-number{text-align:right;white-space:nowrap}.aging-overdue{color:#dc2626}.aging-company,.aging-table tfoot{font-weight:700}.aging-print-heading{display:none}
@media print{
@page{size:A4 landscape;margin:12mm}.erp-sidebar,.erp-topbar,.aging-controls,.no-print{display:none!important}.erp-main,.erp-content,main{margin:0!important;padding:0!important;width:100%!important}.aging-print-heading{display:block}.erp-card{box-shadow:none!important}.table-responsive{overflow:visible!important}.aging-table{font-size:9pt}.aging-table td,.aging-table th{padding:5px}.aging-table thead{display:table-header-group}.aging-table tfoot{display:table-row-group}.aging-table tr{break-inside:avoid}.aging-company{break-after:avoid}.aging-table a{color:inherit;text-decoration:none}body[data-aging-print="summary"] .aging-detail{display:none!important}body[data-aging-print="detail"] .aging-summary{display:none!important}
}
</style>
@endpush
@section('content')
@php($bucketLabels = ['1–30 Days', '31–60 Days', '61–90 Days', 'Over 90 Days'])
<div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
<div><h1 class="h3 fw-bold">AP Aging Report</h1><p class="text-muted">Outstanding bills by supplier, aged from bill date.</p></div>
<div class="aging-controls">
<button class="btn btn-outline-primary" onclick="printAging('summary')">Print Summary</button>
<button class="btn btn-outline-primary" onclick="printAging('detail')">Print Detail</button>
<button class="btn btn-primary" onclick="printAging('all')">Print All</button>
</div></div>
<div class="aging-print-heading"><strong>SEA-JET INTERNATIONAL FORWARDERS, INC.</strong></div>
<p>As of: <strong>{{ $filters['as_of'] ?: date('Y-m-d') }}</strong> · {{ $filters['currency'] }} · {{ $selectedAccount ? $selectedAccount->name : 'All A/P Accounts' }} · Amounts in PHP</p>
<p class="small text-muted">All amounts are in PHP using the bill's recorded conversion rate. Currency identifies the original bill currency. Balances reflect current payments. The selected date controls bill inclusion and age; this is not a historical balance reconstruction. Same-day bills are included in 1–30 days.</p>
<form method="get" class="erp-card p-3 mb-3 aging-controls" action="{{ route('erp.payables.aging') }}">
<div class="row g-3 align-items-end"><div class="col-md-3"><label class="form-label">As of Date</label><input type="date" name="as_of" class="form-control" value="{{ $filters['as_of'] }}"></div>
@foreach(['currency' => ['All Currency','PHP','USD']] as $field => $options)
<div class="col-md-2"><label class="form-label">{{ $field === 'vat' ? 'VAT' : ucfirst($field) }}</label><select name="{{ $field }}" class="form-select">@foreach($options as $option)<option value="{{ $option }}" {{ $filters[$field] === $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach</select></div>
@endforeach
<div class="col-md-4"><label class="form-label">A/P Account</label><select name="ap_account_id" class="form-select"><option value="">All A/P Accounts</option>@foreach($apAccounts as $account)<option value="{{ $account->id }}" {{ (string) $filters['ap_account_id'] === (string) $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></div>
<div class="col-md-3"><button class="btn btn-primary">Apply Filters</button> <button class="btn btn-outline-success" name="format" value="excel">Export Excel</button></div></div></form>
@foreach($currencyTotals as $currency => $totals)
<div class="row g-2 mb-3">@foreach($bucketLabels as $index => $label)<div class="col"><div class="erp-card p-3"><div class="small text-muted">{{ $label }} (PHP, {{ $currency }} bills)</div><strong>{{ number_format($totals['buckets'][$index],2) }}</strong></div></div>@endforeach
<div class="col"><div class="erp-card p-3"><div class="small text-muted">Total Payable (PHP, {{ $currency }} bills)</div><strong>{{ number_format($totals['balance'],2) }}</strong></div></div></div>
@endforeach
<section class="erp-card p-3 mb-4 aging-summary"><h2 class="h6 fw-bold mb-3">Summary by Supplier</h2><div class="table-responsive"><table class="aging-table">
<thead><tr><th>Supplier</th><th>Currency</th>@foreach($bucketLabels as $label)<th class="aging-number">{{ $label }}</th>@endforeach<th class="aging-number">Total</th></tr></thead><tbody>
@forelse($companies as $company)
<tr><td class="fw-bold">{{ $company['name'] }}</td><td>{{ $company['currency'] }}</td>@foreach($company['buckets'] as $index => $amount)<td class="aging-number {{ $index === 3 && $amount > 0 ? 'aging-overdue' : '' }}">{{ $amount > 0 ? number_format($amount,2) : '—' }}</td>@endforeach<td class="aging-number fw-bold">{{ number_format($company['balance'],2) }}</td></tr>
@empty<tr><td colspan="7" class="text-center py-4">No unpaid active bills found for the selected filters.</td></tr>@endforelse
</tbody><tfoot>@foreach($currencyTotals as $currency => $totals)<tr><td>Total</td><td>{{ $currency }}</td>@foreach($totals['buckets'] as $amount)<td class="aging-number">{{ number_format($amount,2) }}</td>@endforeach<td class="aging-number">{{ number_format($totals['balance'],2) }}</td></tr>@endforeach</tfoot></table></div></section>
<section class="erp-card p-3 mb-4 aging-detail"><h2 class="h6 fw-bold mb-3">Detail by Supplier / Bill</h2><div class="table-responsive"><table class="aging-table">
<thead><tr><th>Bill No.</th><th>Bill Date</th><th class="aging-number">Total Due</th><th class="aging-number">Paid</th><th class="aging-number">Balance</th>@foreach($bucketLabels as $label)<th class="aging-number">{{ $label }}</th>@endforeach</tr></thead><tbody>
@forelse($companies as $company)
<tr class="aging-company"><td colspan="2">{{ $company['name'] }} ({{ $company['currency'] }})</td><td class="aging-number">{{ number_format($company['due'],2) }}</td><td class="aging-number">{{ number_format($company['paid'],2) }}</td><td class="aging-number">{{ number_format($company['balance'],2) }}</td>@foreach($company['buckets'] as $amount)<td class="aging-number">{{ number_format($amount,2) }}</td>@endforeach</tr>
@foreach($company['invoices']->sortBy('date') as $invoice)
<tr><td>{{ $invoice['number'] }}</td><td>{{ $invoice['date'] ? \Carbon\Carbon::parse($invoice['date'])->format('M d, Y') : '—' }}</td><td class="aging-number">{{ number_format($invoice['due'],2) }}</td><td class="aging-number">{{ number_format($invoice['paid'],2) }}</td><td class="aging-number">{{ number_format($invoice['balance'],2) }}</td>@foreach($invoice['buckets'] as $index => $amount)<td class="aging-number {{ $index === 3 && $amount > 0 ? 'aging-overdue' : '' }}">{{ $amount > 0 ? number_format($amount,2) : '—' }}</td>@endforeach</tr>
@endforeach
@empty<tr><td colspan="9" class="text-center py-4">No unpaid active bills found for the selected filters.</td></tr>@endforelse
</tbody><tfoot>@foreach($currencyTotals as $currency => $totals)<tr><td colspan="4">Total ({{ $currency }})</td><td class="aging-number">{{ number_format($totals['balance'],2) }}</td>@foreach($totals['buckets'] as $amount)<td class="aging-number">{{ number_format($amount,2) }}</td>@endforeach</tr>@endforeach</tfoot></table></div></section>
@endsection
@push('scripts')
<script>
function printAging(section){document.body.dataset.agingPrint=section;window.print();}
window.addEventListener('afterprint',function(){delete document.body.dataset.agingPrint;});
</script>
@endpush
