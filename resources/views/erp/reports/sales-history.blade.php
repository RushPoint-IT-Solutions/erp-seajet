@php
    $money = function ($value) use ($document) { return $document->currency.' '.number_format((float) $value, 2); };
    $reimbursement = min((float) $document->grand_total, max(0, (float) $document->freight_total));
@endphp
<div class="mb-3">
    <div class="fw-bold fs-5">{{ $document->document_no }}</div>
    <div>{{ $customer }}</div>
    <div class="small text-muted">{{ $document->document_date }} &middot; {{ ucwords(str_replace('_', ' ', $document->status)) }}</div>
</div>
<dl class="row mb-3">
    <dt class="col-sm-4">SOA number</dt><dd class="col-sm-8">{{ $document->soa_no ?: (in_array($document->document_type, ['soa', 'soa_a', 'statement_of_account', 'statement_of_account_a'], true) ? $document->document_no : 'Not recorded') }}</dd>
    <dt class="col-sm-4">A/R Trade</dt><dd class="col-sm-8">{{ $money(max(0, (float) $document->grand_total - $reimbursement)) }}</dd>
    <dt class="col-sm-4">A/R Reimbursable Charges</dt><dd class="col-sm-8">{{ $money($reimbursement) }}</dd>
    <dt class="col-sm-4">Document total</dt><dd class="col-sm-8">{{ $money($document->grand_total) }}</dd>
    <dt class="col-sm-4">Applied payments</dt><dd class="col-sm-8">{{ $money($document->paid_total) }}</dd>
    <dt class="col-sm-4">Balance</dt><dd class="col-sm-8">{{ $money($amounts['settlement_balance']) }}</dd>
</dl>
<h3 class="h6">Payment history</h3>
<div class="table-responsive mb-3">
    <table class="table table-sm table-bordered">
        <thead><tr><th>Date</th><th>Receipt</th><th>Applied to</th><th>Method / Reference</th><th>Status</th><th class="text-end">Applied amount</th></tr></thead>
        <tbody>
        @forelse($applications as $application)
            <tr class="{{ in_array($application->status, ['voided', 'cancelled'], true) ? 'text-muted' : '' }}">
                <td>{{ $application->receipt_date }}</td><td>{{ $application->receipt_no }}</td>
                <td>{{ $application->component === 'bi' ? 'Invoice' : ($application->component === 'soa' ? 'SOA' : 'Legacy payment') }}</td>
                <td>{{ $application->payment_method }}@if($application->reference_no)<br>{{ $application->reference_no }}@endif</td>
                <td>{{ ucwords(str_replace('_', ' ', $application->status)) }}</td>
                <td class="text-end">{{ $application->currency }} {{ number_format((float) $application->amount_applied, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted text-center">No payment applications recorded.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<h3 class="h6">Document history</h3>
<ul class="list-group list-group-flush mb-3">
    <li class="list-group-item px-0"><span class="text-muted">{{ $document->created_at }}</span> &mdash; Document recorded</li>
    @foreach($history as $event)
        @php
            $changes = json_decode($event->new_values ?: '{}', true) ?: [];
            $previous = json_decode($event->old_values ?: '{}', true) ?: [];
        @endphp
        <li class="list-group-item px-0">
            <span class="text-muted">{{ $event->created_at }}</span> &mdash; {{ ucwords(str_replace('_', ' ', $event->action)) }}
            @if(isset($changes['status']))<div class="small">Status: {{ $previous['status'] ?? '-' }} &rarr; {{ $changes['status'] }}</div>@endif
            @if(!empty($changes['reason']))<div class="small">{{ $changes['reason'] }}</div>@endif
        </li>
    @endforeach
</ul>
<a href="{{ route('erp.invoices.show', $document->id) }}" class="btn btn-outline-primary btn-sm">Open document</a>
