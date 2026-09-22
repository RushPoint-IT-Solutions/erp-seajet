@extends('erp.layout')

@section('title', 'View Invoice')
@section('page-title', 'View Invoice')

@section('content')
@php
    $currency = $invoice['currency'] ?? 'PHP';
    $money = function ($amount) use ($currency) {
        return trim($currency.' '.number_format((float) $amount, 2));
    };
    $status = $record ? ucwords(str_replace('_', ' ', $record->status ?: 'draft')) : 'Draft';
    $paidTotal = $record ? (float) $record->paid_total : 0;
    $grandTotal = $record ? (float) $record->grand_total : (float) ($invoice['soa_total'] ?? 0);
    $withholdingTaxRate = $record ? (float) $record->withholding_tax_rate : (float) ($invoice['withholding_tax_rate'] ?? 0);
    $withholdingTaxAmount = $record ? (float) $record->withholding_tax_amount : (float) ($invoice['withholding_tax_amount'] ?? 0);
    $balance = max(0, $grandTotal - $paidTotal);
    $netAmountDue = max(0, $balance - $withholdingTaxAmount);
    $isFinal = $record && in_array(strtolower((string) $record->status), ['cancelled', 'voided'], true);
@endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="h3 fw-bold mb-0">{{ $invoice['invoice_no'] ?? 'Invoice' }}</h1>
                <span class="badge badge-soft">{{ $status }}</span>
            </div>
            <p class="text-muted mb-0">View invoice details, print templates, update status, or create payment.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.invoices.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Invoices
            </a>
            @unless($isFinal)
                <a href="{{ route('erp.billings.edit', $record ? $record->id : ($invoice['invoice_no'] ?? '')) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <button type="button"
                        class="btn btn-primary invoice-payment-button"
                        data-bs-toggle="modal"
                        data-bs-target="#invoiceViewPaymentModal"
                        data-invoice="{{ $invoice['invoice_no'] ?? '' }}">
                    <i class="bi bi-credit-card me-1"></i> Receive Payment
                </button>
            @endunless
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('portal_url'))
        <div class="alert alert-success">
            Secure client link (expires in 30 days):
            <a href="{{ session('portal_url') }}" target="_blank" rel="noopener">{{ session('portal_url') }}</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="erp-card p-3 h-100">
                <div class="text-muted small">Amount</div>
                <div class="h5 fw-bold mb-0">{{ $money($totals['billing_total'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="erp-card p-3 h-100">
                <div class="text-muted small">Tax</div>
                <div class="h5 fw-bold mb-0">{{ $money($totals['vat_amount'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="erp-card p-3 h-100">
                <div class="text-muted small">Total</div>
                <div class="h5 fw-bold mb-0">{{ $money($grandTotal) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="erp-card p-3 h-100">
                <div class="text-muted small">Balance</div>
                <div class="h5 fw-bold mb-0">{{ $money($balance) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="erp-card p-4 h-100">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Invoice Details</h2>
                        <div class="text-muted small">This is a viewing page only. Use print actions for templates.</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">Invoice Date</div>
                        <div class="fw-semibold">{{ !empty($invoice['invoice_date']) ? date('M d, Y', strtotime($invoice['invoice_date'])) : '-' }}</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="text-muted small">Client</div>
                        <div class="fw-semibold">{{ $invoice['customer'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">SOA No.</div>
                        <div class="fw-semibold">{{ $invoice['soa_no'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Currency</div>
                        <div class="fw-semibold">{{ $currency }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Billing Address</div>
                        <div style="white-space: pre-line">{{ $invoice['invoice_to'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">TIN</div>
                        <div>{{ $invoice['tin'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Due Date</div>
                        <div>{{ !empty($invoice['due_date']) ? date('M d, Y', strtotime($invoice['due_date'])) : '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Shipment Job ID</div>
                        <div>{{ $invoice['job_shipment_id'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Class</div>
                        <div>{{ $invoice['class_code'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">AR Account</div>
                        <div>{{ $invoice['ar_account'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Withholding Tax %</div>
                        <div>{{ rtrim(rtrim(number_format($withholdingTaxRate, 2), '0'), '.') }}%</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Less Withholding Tax</div>
                        <div>{{ $money($withholdingTaxAmount) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Please Pay This Amount</div>
                        <div class="fw-semibold text-primary">{{ $money($netAmountDue) }}</div>
                    </div>
                </div>

                <h3 class="h6 fw-bold mb-2">Line Items</h3>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th>Tax</th>
                            <th class="text-end">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse((array) ($invoice['item_description'] ?? []) as $index => $description)
                            <tr>
                                <td>{{ $invoice['item'][$index] ?? 'Service' }}</td>
                                <td style="white-space: pre-line">{{ $description }}</td>
                                <td class="text-end">{{ $invoice['qty'][$index] ?? '' }}</td>
                                <td class="text-end">{{ number_format((float) ($invoice['unit_price'][$index] ?? 0), 2) }}</td>
                                <td>{{ $invoice['tax_category'][$index] ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) ($invoice['line_amount'][$index] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No line items found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="erp-card p-4 mb-3">
                <h2 class="h6 fw-bold mb-3">Print Templates</h2>
                <div class="d-grid gap-2">
                    @foreach($printDocuments as $document)
                        <a href="{{ $document['route'] }}" class="btn btn-outline-secondary text-start">
                            <i class="bi {{ $document['icon'] }} me-1"></i>{{ $document['label'] }}
                        </a>
                    @endforeach
                    <a href="{{ route('erp.billings.print-layout.invoice') }}" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-sliders me-1"></i>Adjust Print Layout
                    </a>
                </div>
            </div>

            <div class="erp-card p-4">
                <h2 class="h6 fw-bold mb-3">More Actions</h2>
                <div class="d-grid gap-2">
                    @if($record && !$isFinal)
                        <form method="post" action="{{ route('erp.invoices.portal-link', $record->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary text-start w-100">
                                <i class="bi bi-link-45deg me-1"></i>Create Secure Client Link
                            </button>
                        </form>
                    @endif
                    @unless($isFinal)
                        <a href="{{ route('erp.billings.status') }}" class="btn btn-outline-secondary text-start">
                            <i class="bi bi-toggle-on me-1"></i>Update Status
                        </a>
                        <a href="{{ route('erp.billings.void', $record ? $record->id : ($invoice['invoice_no'] ?? '')) }}" class="btn btn-outline-danger text-start">
                            <i class="bi bi-x-circle me-1"></i>{{ $record && (in_array(strtolower((string) $record->status), ['posted', 'approved'], true) || ($record->posting_status ?? 'unposted') === 'posted') ? 'Void Invoice' : 'Cancel Invoice' }}
                        </a>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    @include('erp.payments._payment-modal', [
        'customers' => $paymentCustomers,
        'invoices' => $paymentInvoices,
        'paymentModalId' => 'invoiceViewPaymentModal',
        'paymentModalTitle' => 'Receive Invoice Payment',
    ])
@endsection

@push('scripts')
<script>
    $(function () {
        $('.invoice-payment-button').on('click', function () {
            var form = $('#invoiceViewPaymentModal').find('form');
            form[0].reset();
            var invoiceNo = String($(this).data('invoice'));
            var option = form.find('.payment-invoice-select option').filter(function () { return this.value === invoiceNo; });
            var mode = (parseFloat(option.attr('data-bi-balance')) || 0) > 0 ? 'bi' : 'soa';
            form.find('.payment-for-select').val(mode).trigger('change');
            form.find('.payment-invoice-select').val([invoiceNo]).trigger('change');
        });
    });
</script>
@endpush
