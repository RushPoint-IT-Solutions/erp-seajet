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
                    <div class="text-muted small">Apply one customer payment to one or more open invoices.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <label class="form-label">Invoices</label>
                        <select name="invoice_no[]" class="form-select payment-invoice-select erp-select" multiple>
                            <option value=""></option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice['document_no'] }}"
                                        data-customer-id="{{ $invoice['customer_id'] }}"
                                        data-customer-name="{{ $invoice['customer_name'] }}"
                                        data-currency="{{ $invoice['currency'] }}"
                                        data-balance="{{ number_format($invoice['balance'], 2, '.', '') }}"
                                        data-cwt-balance="{{ number_format($invoice['cwt_balance'], 2, '.', '') }}"
                                        data-settlement-balance="{{ number_format($invoice['settlement_balance'], 2, '.', '') }}">
                                    {{ $invoice['document_no'] }} - {{ $invoice['customer_name'] }} - {{ $invoice['currency'] }} {{ number_format($invoice['balance'], 2) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Select invoices for the same client and currency. Payment is applied in the order shown.</div>
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
                        <label class="form-label">Deposit Bank Account</label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">Select account...</option>
                            @foreach(($bankAccounts ?? []) as $bank)
                                <option value="{{ $bank['id'] }}">{{ $bank['bank'] }} - {{ $bank['name'] }} ({{ $bank['currency'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select payment-currency-select">
                            <option>PHP</option>
                            <option>USD</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payable Amount</label>
                        <input name="amount" class="form-control text-end payment-amount-input" value="" required>
                        <div class="form-text">Billing Invoice plus SOA B, less withholding tax.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Exchange Rate to PHP</label>
                        <input name="exchange_rate" type="number" min="0.000001" step="0.000001" class="form-control text-end payment-rate-input" value="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">CWT Amount</label>
                        <input name="cwt_amount" class="form-control text-end payment-cwt-input" value="0">
                        <div class="form-text">Creditable tax withheld by the customer (BIR Form 2307).</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BIR Form 2307 Reference</label>
                        <input name="cwt_certificate_no" class="form-control" value="">
                    </div>
                    <div class="col-12">
                        <div class="alert alert-warning d-none mb-0 payment-balance-alert" role="alert"></div>
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

@push('scripts')
<script>
    (function () {
        function money(value) {
            return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function checkPaymentBalance(form) {
            var options = form.find('.payment-invoice-select option:selected');
            var alertBox = form.find('.payment-balance-alert');
            var balance = 0;
            var settlementBalance = 0;
            var currencies = {};
            var customers = {};

            options.each(function () {
                var option = $(this);
                balance += parseFloat(option.data('balance')) || 0;
                settlementBalance += parseFloat(option.data('settlement-balance')) || 0;
                currencies[option.data('currency') || 'PHP'] = true;
                customers[String(option.data('customer-id') || '')] = true;
            });

            var amount = parseFloat(String(form.find('.payment-amount-input').val() || '').replace(/,/g, ''));
            var cwt = parseFloat(String(form.find('.payment-cwt-input').val() || '0').replace(/,/g, '')) || 0;
            var currency = Object.keys(currencies)[0] || form.find('.payment-currency-select').val() || 'PHP';

            if (!options.length || isNaN(amount) || amount <= 0) {
                alertBox.addClass('d-none').removeClass('alert-warning alert-success').text('');
                return;
            }

            if (Object.keys(customers).length > 1 || Object.keys(currencies).length > 1) {
                alertBox.removeClass('d-none alert-success').addClass('alert-warning')
                    .text('All selected invoices must belong to the same client and use the same currency.');
                return;
            }

            var diff = amount - balance;
            var settlementDiff = amount + cwt - settlementBalance;
            var overpayment = Math.max(diff, settlementDiff);

            if (overpayment > 0.005) {
                alertBox.removeClass('d-none alert-success').addClass('alert-warning')
                    .text('Warning: This overpays the invoice by ' + currency + ' ' + money(overpayment) + '; for payment is ' + currency + ' ' + money(balance) + '.');
            } else if (Math.abs(diff) < 0.005) {
                alertBox.removeClass('d-none alert-warning').addClass('alert-success')
                    .text('This matches the outstanding balance of ' + currency + ' ' + money(balance) + '.');
            } else if (diff > 0) {
                alertBox.removeClass('d-none alert-success').addClass('alert-warning')
                    .text('This overpays the invoice by ' + currency + ' ' + money(diff) + ' — outstanding balance is ' + currency + ' ' + money(balance) + '.');
            } else {
                alertBox.removeClass('d-none alert-success').addClass('alert-warning')
                    .text('This is short by ' + currency + ' ' + money(Math.abs(diff)) + ' of the outstanding balance (' + currency + ' ' + money(balance) + ').');
            }
        }

        $(document).on('input change', '.payment-modal-form .payment-amount-input, .payment-modal-form .payment-cwt-input, .payment-modal-form .payment-invoice-select', function () {
            checkPaymentBalance($(this).closest('.payment-modal-form'));
        });

        $(document).on('shown.bs.modal', '.payment-modal', function () {
            checkPaymentBalance($(this).find('.payment-modal-form'));
        });
    })();
</script>
@endpush
