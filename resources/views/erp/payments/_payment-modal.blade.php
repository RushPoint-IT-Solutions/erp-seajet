@php
    $paymentModalId = $paymentModalId ?? 'paymentModal';
    $paymentModalTitle = $paymentModalTitle ?? 'Receive Payment';
@endphp

<div class="modal fade payment-modal" id="{{ $paymentModalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="post" action="{{ route('erp.payments.receipt') }}" class="modal-content payment-modal-form">
            {{ csrf_field() }}
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">{{ $paymentModalTitle }}</h5>
                    <div class="text-muted small">Apply a customer payment directly to an open invoice.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <label class="form-label">Invoice</label>
                        <select name="invoice_no" class="form-select payment-invoice-select erp-select">
                            <option value=""></option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice['document_no'] }}"
                                        data-customer-id="{{ $invoice['customer_id'] }}"
                                        data-customer-name="{{ $invoice['customer_name'] }}"
                                        data-currency="{{ $invoice['currency'] }}"
                                        data-balance="{{ number_format($invoice['balance'], 2, '.', '') }}">
                                    {{ $invoice['document_no'] }} - {{ $invoice['customer_name'] }} - {{ $invoice['currency'] }} {{ number_format($invoice['balance'], 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <label class="form-label">Client</label>
                        <select name="customer_id" class="form-select payment-customer-select erp-select" required>
                            <option value=""></option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer['id'] }}"
                                        data-code="{{ $customer['customer_id'] }}"
                                        data-name="{{ $customer['name'] }}"
                                        data-currency="{{ $customer['currency'] }}">
                                    {{ $customer['customer_id'] }} - {{ $customer['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="customer_name" class="payment-customer-name" value="">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Date</label>
                        <input name="payment_date" type="date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option>Check</option>
                            <option>Cash</option>
                            <option>Bank Transfer</option>
                            <option>Online</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reference No.</label>
                        <input name="reference_no" class="form-control" value="">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select payment-currency-select">
                            <option>PHP</option>
                            <option>USD</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input name="amount" class="form-control text-end payment-amount-input" value="" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shipment Job ID</label>
                        <input name="job_shipment_id" class="form-control" value="">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Project</label>
                        <input name="project" class="form-control" value="">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Private Notes</label>
                        <input name="private_notes" class="form-control" value="">
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-4 rounded border bg-light p-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="send_email" value="Yes" checked>
                                <label class="form-check-label">Send email receipt</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="convert_currency" value="Yes">
                                <label class="form-check-label">Convert currency</label>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="payment_type" value="Customer Payment">
                <input type="hidden" name="receipt_type" value="Official Receipt">
                <input type="hidden" name="receipt_no" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Payment</button>
            </div>
        </form>
    </div>
</div>

