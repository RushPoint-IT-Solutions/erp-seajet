@php
    $paymentModalId = $paymentModalId ?? 'paymentModal';
    $paymentModalTitle = $paymentModalTitle ?? 'Receive Payment';
@endphp

@push('styles')
    <style>
        .payment-modal .modal-dialog { max-width: 1180px; }
        .payment-modal .modal-content { border: 0; box-shadow: 0 24px 70px rgba(15, 23, 42, .22); }
        .payment-modal .modal-header { padding: 20px 24px; border-bottom-color: #e8edf3; }
        .payment-modal .modal-body { padding: 22px 24px 24px; background: #f8fafc; }
        .payment-modal .modal-footer { padding: 16px 24px; }
        .payment-section {
            background: #fff;
            border: 1px solid #e3e8ef;
            border-radius: 10px;
            padding: 18px;
        }
        .payment-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .payment-section-title h6 { margin: 0; font-weight: 750; }
        .payment-client-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .payment-summary-item {
            min-height: 72px;
            padding: 12px 14px;
            border: 1px solid #e7ebf0;
            border-radius: 8px;
            background: #fbfcfe;
        }
        .payment-summary-label { color: #718096; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .payment-summary-value { margin-top: 4px; color: #1f2937; font-size: 17px; font-weight: 750; }
        .payment-invoice-table { min-width: 790px; }
        .payment-invoice-table thead th { padding-top: 10px; padding-bottom: 10px; }
        .payment-invoice-table td { vertical-align: middle; }
        .payment-invoice-table .invoice-check-cell { width: 42px; text-align: center; }
        .payment-invoice-table .invoice-no { color: #263449; font-weight: 700; }
        .payment-invoice-table .invoice-cwt-note { color: #7a8798; font-size: 11px; }
        .payment-invoice-row { cursor: pointer; }
        .payment-invoice-row:hover td { background: #fff8f2; }
        .payment-invoice-row.is-selected td { background: #fff4e9; }
        .payment-invoice-row .form-check-input { cursor: pointer; }
        .payment-invoice-empty { padding: 42px 20px !important; }
        .payment-total-row td { background: #f4f7fa !important; border-top: 2px solid #dbe2ea; font-weight: 750; }
        .payment-selection-count { min-width: 112px; text-align: right; }
        .payment-currency-prefix { color: #687588; font-size: 12px; font-weight: 700; margin-right: 3px; }
        @media (max-width: 767.98px) {
            .payment-client-summary { grid-template-columns: 1fr; }
            .payment-modal .modal-body { padding: 16px; }
        }
    </style>
@endpush

<div class="modal fade payment-modal" id="{{ $paymentModalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="post" action="{{ route('erp.payments.receipt') }}" class="modal-content payment-modal-form">
            {{ csrf_field() }}
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">{{ $paymentModalTitle }}</h5>
                    <div class="text-muted small">Record BI, SOA B, and SOA A payments separately. Each payment gets its own receipt number and document reference.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="payment-section mb-3">
                    <div class="payment-section-title">
                        <h6><i class="bi bi-person-check me-2 text-primary"></i>Client and payment details</h6>
                        <span class="badge badge-soft">Step 1</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Payment For</label>
                            <select name="payment_for" class="form-select payment-for-select" required>
                                <option value="" selected>Select BI, SOA B, or SOA A...</option>
                                <option value="bi">Billing Invoice (BI) only</option>
                                <option value="soa">Statement of Account B (SOA B) only</option>
                                <option value="soa_a">Statement of Account A (SOA A) only</option>
                            </select>
                            <div class="form-text">Save a separate payment if the client pays the other portion.</div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Client <span class="text-danger">*</span></label>
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
                            <div class="form-text">Select the client first. Only that client's unpaid invoices will appear below.</div>
                            <input type="hidden" name="customer_name" class="payment-customer-name" value="">
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Amount to Pay <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text payment-amount-currency">PHP</span>
                                <input name="amount" type="number" min="0.01" step="0.01"
                                       class="form-control text-end payment-amount-input" value=""
                                       inputmode="decimal" placeholder="0.00" required>
                            </div>
                            <div class="form-text">Enter the amount actually received. Partial payments leave the remaining BI or SOA balance open.</div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                            <select name="class_code" class="form-select payment-class-select" required>
                                <option value="">Select class...</option>
                                <option>MANILA</option>
                                <option>PAMPANGA</option>
                                <option>CEBU</option>
                            </select>
                            <div class="form-text">Uses this class's OR or AR series.</div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">Payment Date</label>
                            <input name="payment_date" type="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">Next Receipt No.</label>
                            <input class="form-control payment-receipt-preview" readonly
                                   data-series='@json($receiptSeries['by_class'])'
                                   placeholder="Choose class and BI or SOA">
                            <div class="form-text">Confirmed number appears after saving.</div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option>Check</option>
                                <option>Cash</option>
                                <option>Bank Transfer</option>
                                <option>Online</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Reference No.</label>
                            <input name="reference_no" class="form-control" value="">
                        </div>
                        @if(app(\App\Services\Security\RbacService::class)->normalizeRole(auth()->user()->role ?? '') === 'admin')
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Receipt Number (Admin)</label>
                            <input name="receipt_no" class="form-control" maxlength="100" pattern="[A-Za-z0-9-]+" value="{{ old('receipt_no') }}" placeholder="Automatic if blank">
                            <div class="form-text">Enter the full receipt number. Existing numbers, including voided receipts, cannot be reused.</div>
                        </div>
                        @endif
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Deposit Bank Account</label>
                            <select name="bank_account_id" class="form-select">
                                <option value="">Select account...</option>
                                @foreach(($bankAccounts ?? []) as $bank)
                                    <option value="{{ $bank['id'] }}">{{ $bank['bank'] }} - {{ $bank['name'] }} ({{ $bank['currency'] }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Currency</label>
                            <select name="currency" class="form-select payment-currency-select">
                                <option>PHP</option>
                                <option>USD</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Exchange Rate to PHP</label>
                            <input name="exchange_rate" type="number" min="0.000001" step="0.000001" class="form-control text-end payment-rate-input" value="1">
                        </div>
                    </div>
                </div>

                <div class="payment-section mb-3">
                    <div class="payment-section-title">
                        <div>
                            <h6><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Unpaid invoices</h6>
                            <div class="text-muted small mt-1">Tick one or more invoices to include in this payment.</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted payment-selection-count">0 selected</span>
                            <span class="badge badge-soft">Step 2</span>
                        </div>
                    </div>

                    <select name="invoice_no[]" class="payment-invoice-select visually-hidden" multiple tabindex="-1" aria-hidden="true">
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice['document_no'] }}"
                                    data-document-type="{{ $invoice['document_type'] }}"
                                    data-customer-id="{{ $invoice['customer_id'] }}"
                                    data-class-code="{{ $invoice['class_code'] }}"
                                    data-customer-name="{{ $invoice['customer_name'] }}"
                                    data-currency="{{ $invoice['currency'] }}"
                                    data-balance="{{ number_format($invoice['balance'], 2, '.', '') }}"
                                    data-bi-balance="{{ number_format($invoice['bi_balance'], 2, '.', '') }}"
                                    data-soa-balance="{{ number_format($invoice['soa_balance'], 2, '.', '') }}"
                                    data-cwt-balance="{{ number_format($invoice['cwt_balance'], 2, '.', '') }}"
                                    data-settlement-balance="{{ number_format($invoice['settlement_balance'], 2, '.', '') }}">
                                {{ $invoice['document_no'] }} - {{ $invoice['customer_name'] }} - {{ $invoice['currency'] }} {{ number_format($invoice['balance'], 2) }}
                            </option>
                        @endforeach
                    </select>

                    <div class="table-responsive border rounded">
                        <table class="table table-hover mb-0 payment-invoice-table">
                            <thead>
                            <tr>
                                <th class="invoice-check-cell"><input type="checkbox" class="form-check-input payment-select-all" aria-label="Select all visible unpaid invoices" disabled></th>
                                <th>Date</th>
                                <th>BI / SOA Reference</th>
                                <th class="text-end">Invoice Total</th>
                                <th class="text-end">Selected Balance Due</th>
                                <th class="text-end">Cash Due</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($invoices as $invoice)
                                <tr class="payment-invoice-row d-none"
                                    data-invoice="{{ $invoice['document_no'] }}"
                                    data-document-type="{{ $invoice['document_type'] }}"
                                    data-customer-id="{{ $invoice['customer_id'] }}"
                                    data-class-code="{{ $invoice['class_code'] }}"
                                    data-currency="{{ $invoice['currency'] }}"
                                    data-original="{{ number_format($invoice['invoice_total'], 2, '.', '') }}"
                                    data-balance="{{ number_format($invoice['balance'], 2, '.', '') }}"
                                    data-bi-balance="{{ number_format($invoice['bi_balance'], 2, '.', '') }}"
                                    data-soa-balance="{{ number_format($invoice['soa_balance'], 2, '.', '') }}"
                                    data-bi-reference="{{ $invoice['bi_reference'] }}"
                                    data-soa-reference="{{ $invoice['soa_reference'] }}"
                                    data-cwt-balance="{{ number_format($invoice['cwt_balance'], 2, '.', '') }}"
                                    data-settlement-balance="{{ number_format($invoice['settlement_balance'], 2, '.', '') }}">
                                    <td class="invoice-check-cell"><input type="checkbox" class="form-check-input payment-invoice-check" value="{{ $invoice['document_no'] }}" aria-label="Select invoice {{ $invoice['document_no'] }}"></td>
                                    <td>{{ $invoice['document_date'] ? \Carbon\Carbon::parse($invoice['document_date'])->format('d/M/Y') : '-' }}</td>
                                    <td>
                                        <span class="invoice-no payment-document-reference">{{ $invoice['bi_reference'] }}</span>
                                        <div class="small text-muted">
                                            BI: {{ $invoice['bi_reference'] }}
                                            @if($invoice['soa_balance'] > 0.005)
                                                / SOA: {{ $invoice['soa_reference'] }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="payment-currency-prefix">{{ $invoice['currency'] }}</span>{{ number_format($invoice['invoice_total'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="payment-currency-prefix">{{ $invoice['currency'] }}</span><span class="payment-row-due">{{ number_format($invoice['settlement_balance'], 2) }}</span>
                                        @if($invoice['cwt_balance'] > 0.005)
                                            <div class="invoice-cwt-note">Includes CWT {{ number_format($invoice['cwt_balance'], 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold"><span class="payment-currency-prefix">{{ $invoice['currency'] }}</span><span class="payment-row-cash">{{ number_format($invoice['balance'], 2) }}</span></td>
                                </tr>
                            @endforeach
                            <tr class="payment-invoice-placeholder">
                                <td colspan="6" class="text-center text-muted payment-invoice-empty">
                                    <i class="bi bi-person-lines-fill d-block fs-3 mb-2"></i>Select a client to view their unpaid invoices.
                                </td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr class="payment-total-row d-none">
                                <td colspan="3" class="text-end">Selected totals</td>
                                <td class="text-end payment-total-original">0.00</td>
                                <td class="text-end payment-total-due">0.00</td>
                                <td class="text-end payment-total-cash">0.00</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="payment-section h-100">
                            <div class="payment-section-title"><h6>Payment notes</h6></div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Shipment Job ID</label><input name="job_shipment_id" class="form-control" value=""></div>
                                <div class="col-md-6"><label class="form-label">Project</label><input name="project" class="form-control" value=""></div>
                                <div class="col-md-6"><label class="form-label">BIR Form 2307 Reference</label><input name="cwt_certificate_no" class="form-control" value=""></div>
                                <div class="col-md-6"><label class="form-label">Private Notes</label><input name="private_notes" class="form-control" value=""></div>
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-4 rounded border bg-light p-3">
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="send_email" value="Yes" checked><label class="form-check-label">Send email receipt</label></div>
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="convert_currency" value="Yes"><label class="form-check-label">Convert currency</label></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="payment-section h-100">
                            <div class="payment-section-title"><h6>Payment summary</h6><span class="badge badge-soft">Step 3</span></div>
                            <div class="payment-client-summary mb-3">
                                <div class="payment-summary-item"><div class="payment-summary-label">Open invoices</div><div class="payment-summary-value payment-open-count">0</div></div>
                                <div class="payment-summary-item"><div class="payment-summary-label">Client outstanding</div><div class="payment-summary-value payment-client-outstanding">PHP 0.00</div></div>
                                <div class="payment-summary-item"><div class="payment-summary-label">Selected invoices</div><div class="payment-summary-value payment-selected-count">0</div></div>
                            </div>
                            <div class="mb-3 rounded border bg-light p-3">
                                <div class="payment-summary-label">Amount entered</div>
                                <div class="payment-summary-value"><span class="payment-amount-summary-currency">PHP</span> <span class="payment-amount-summary">0.00</span></div>
                                <div class="form-text">This amount will be matched against the selected BI or SOA.</div>
                            </div>
                            <div>
                                <label class="form-label">CWT Amount</label>
                                <input name="cwt_amount" class="form-control text-end payment-cwt-input" value="0.00" inputmode="decimal">
                                <div class="form-text">Creditable tax withheld by the customer (BIR Form 2307).</div>
                            </div>
                            <div class="alert alert-warning d-none mt-3 mb-0 payment-balance-alert" role="alert"></div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="payment_type" value="Customer Payment">
            </div>
            <div class="modal-footer">
                <div class="me-auto small text-muted"><i class="bi bi-info-circle me-1"></i>One receipt will be created for all selected invoices.</div>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Payment</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            function numberValue(value) { return parseFloat(String(value || '0').replace(/,/g, '')) || 0; }
            function money(value) { return numberValue(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            function selectedOptions(form) { return form.find('.payment-invoice-select option:selected'); }
            function applyPaymentFor(form) {
                var mode = form.find('.payment-for-select').val() || 'all';
                form.find('.payment-invoice-select option, .payment-invoice-row').each(function () {
                    var item = $(this);
                    var bi = numberValue(item.attr('data-bi-balance'));
                    var soa = numberValue(item.attr('data-soa-balance'));
                    var totalCwt = numberValue(item.attr('data-cwt-balance-original') || item.attr('data-cwt-balance'));
                    if (!item.attr('data-cwt-balance-original')) { item.attr('data-cwt-balance-original', totalCwt); }
                    var documentType = String(item.attr('data-document-type') || '').toLowerCase();
                    var isSoaA = ['soa', 'soa_a', 'statement_of_account_a'].indexOf(documentType) !== -1;
                    var due = mode === 'bi' ? bi : (mode === 'soa_a' ? (isSoaA ? soa : 0) : (mode === 'soa' ? (isSoaA ? 0 : soa) : 0));
                    var cwt = mode === 'bi' ? Math.min(bi, totalCwt) : 0;
                    item.data('settlement-balance', due).data('cwt-balance', cwt).data('balance', Math.max(0, due - cwt));
                    if (item.is('option') && due <= 0.005) { item.prop('selected', false); }
                    if (item.is('tr')) {
                        item.find('.payment-row-due').text(money(due));
                        item.find('.payment-row-cash').text(money(Math.max(0, due - cwt)));
                        item.find('.invoice-cwt-note').toggleClass('d-none', cwt <= 0.005);
                        var isSoaMode = mode === 'soa' || mode === 'soa_a';
                        var reference = isSoaMode ? item.attr('data-soa-reference') : item.attr('data-bi-reference');
                        var referenceLabel = mode === 'soa_a' ? 'SOA A: ' : (mode === 'soa' ? 'SOA B: ' : 'BI: ');
                        item.find('.payment-document-reference').text(referenceLabel + reference);
                    }
                });
            }

            function setCustomerDetails(form) {
                var option = form.find('.payment-customer-select option:selected');
                form.find('.payment-customer-name').val(option.data('name') || '');
                if (option.data('currency')) { form.find('.payment-currency-select').val(option.data('currency')); }
            }

            function filterInvoices(form) {
                var customerId = String(form.find('.payment-customer-select').val() || '');
                var classCode = String(form.find('.payment-class-select').val() || '');
                var rows = form.find('.payment-invoice-row');
                var visibleRows = rows.filter(function () { return customerId !== '' && String($(this).data('customer-id')) === customerId && (!classCode || String($(this).data('class-code')) === classCode) && numberValue($(this).data('settlement-balance')) > 0.005; });
                rows.addClass('d-none');
                visibleRows.removeClass('d-none');
                form.find('.payment-select-all').prop('disabled', visibleRows.length === 0);
                form.find('.payment-total-row').toggleClass('d-none', visibleRows.length === 0);

                var placeholder = form.find('.payment-invoice-placeholder');
                if (!form.find('.payment-for-select').val()) {
                    placeholder.removeClass('d-none').find('td').html('<i class="bi bi-list-check d-block fs-3 mb-2"></i>Choose BI or SOA to view the amount due.');
                } else if (!customerId) {
                    placeholder.removeClass('d-none').find('td').html('<i class="bi bi-person-lines-fill d-block fs-3 mb-2"></i>Select a client to view their unpaid invoices.');
                } else if (!visibleRows.length) {
                    placeholder.removeClass('d-none').find('td').html('<i class="bi bi-check-circle d-block fs-3 mb-2 text-success"></i>This client has no unpaid invoices.');
                } else {
                    placeholder.addClass('d-none');
                }
                return visibleRows;
            }

            function syncRowsFromSelect(form) {
                var chosen = {};
                selectedOptions(form).each(function () { chosen[String(this.value)] = true; });
                form.find('.payment-invoice-row').each(function () {
                    var row = $(this);
                    var isSelected = !!chosen[String(row.data('invoice'))];
                    row.toggleClass('is-selected', isSelected);
                    row.find('.payment-invoice-check').prop('checked', isSelected);
                });
            }

            function syncTotals(form, fillPaymentAmount) {
                var options = selectedOptions(form);
                var cash = 0, cwt = 0, due = 0, original = 0;
                var customerId = String(form.find('.payment-customer-select').val() || '');
                var visibleRows = filterInvoices(form);

                options.each(function () {
                    cash += numberValue($(this).data('balance'));
                    cwt += numberValue($(this).data('cwt-balance'));
                    due += numberValue($(this).data('settlement-balance'));
                });
                form.find('.payment-invoice-row.is-selected').each(function () { original += numberValue($(this).data('original')); });

                var clientOutstanding = 0;
                visibleRows.each(function () { clientOutstanding += numberValue($(this).data('settlement-balance')); });
                var currency = form.find('.payment-currency-select').val() || 'PHP';
                var count = options.length;

                form.find('.payment-selection-count').text(count + ' selected');
                form.find('.payment-selected-count').text(count);
                form.find('.payment-open-count').text(customerId ? visibleRows.length : 0);
                form.find('.payment-client-outstanding').text(currency + ' ' + money(clientOutstanding));
                form.find('.payment-total-original').text(currency + ' ' + money(original));
                form.find('.payment-total-due').text(currency + ' ' + money(due));
                form.find('.payment-total-cash').text(currency + ' ' + money(cash));
                form.find('.payment-amount-currency').text(currency);
                form.find('.payment-amount-summary-currency').text(currency);
                form.find('.payment-amount-summary').text(money(form.find('.payment-amount-input').val()));
                form.find('.payment-select-all').prop('checked', visibleRows.length > 0 && visibleRows.filter('.is-selected').length === visibleRows.length);
                if (fillPaymentAmount) {
                    form.find('.payment-cwt-input').val(cwt.toFixed(2));
                }
            }

            function checkPaymentBalance(form) {
                var options = selectedOptions(form);
                var alertBox = form.find('.payment-balance-alert');
                var cashBalance = 0, settlementBalance = 0, currencies = {}, customers = {};
                options.each(function () {
                    var option = $(this);
                    cashBalance += numberValue(option.data('balance'));
                    settlementBalance += numberValue(option.data('settlement-balance'));
                    currencies[option.data('currency') || 'PHP'] = true;
                    customers[String(option.data('customer-id') || '')] = true;
                });
                var amount = numberValue(form.find('.payment-amount-input').val());
                var cwt = numberValue(form.find('.payment-cwt-input').val());
                var currency = Object.keys(currencies)[0] || form.find('.payment-currency-select').val() || 'PHP';

                if (!options.length || amount <= 0) {
                    alertBox.addClass('d-none').removeClass('alert-warning alert-success').text('');
                    return;
                }
                if (Object.keys(customers).length > 1 || Object.keys(currencies).length > 1) {
                    alertBox.removeClass('d-none alert-success').addClass('alert-warning').text('All selected invoices must belong to the same client and use the same currency.');
                    return;
                }
                var cashDiff = amount - cashBalance;
                var settlementDiff = amount + cwt - settlementBalance;
                var overpayment = Math.max(cashDiff, settlementDiff);
                if (overpayment > 0.005) {
                    alertBox.removeClass('d-none alert-success').addClass('alert-warning').text('Warning: This overpays the invoice selection by ' + currency + ' ' + money(overpayment) + '.');
                } else if (Math.abs(cashDiff) < 0.005 && Math.abs(settlementDiff) < 0.005) {
                    alertBox.removeClass('d-none alert-warning').addClass('alert-success').text('This payment matches the selected invoices\' combined outstanding balance.');
                } else {
                    alertBox.removeClass('d-none alert-success').addClass('alert-warning').text('This is a partial payment. Remaining invoice balance: ' + currency + ' ' + money(Math.max(0, settlementBalance - amount - cwt)) + '.');
                }
            }

            function refresh(form, fillPaymentAmount) {
                applyPaymentFor(form);
                var firstInvoice = selectedOptions(form).first();
                if (firstInvoice.length && !form.find('.payment-class-select').val()) {
                    form.find('.payment-class-select').val(String(firstInvoice.data('class-code') || 'MANILA'));
                }
                var preview = form.find('.payment-receipt-preview');
                var mode = form.find('.payment-for-select').val();
                var classCode = form.find('.payment-class-select').val();
                var series = preview.data('series') || {};
                preview.val((series[mode] || {})[classCode] || '');
                setCustomerDetails(form);
                syncRowsFromSelect(form);
                syncTotals(form, fillPaymentAmount);
                checkPaymentBalance(form);
            }

            $(document).on('change', '.payment-modal-form .payment-customer-select', function () {
                var form = $(this).closest('.payment-modal-form');
                var customerId = String($(this).val() || '');
                form.find('.payment-invoice-select option:selected').each(function () {
                    if (String($(this).data('customer-id')) !== customerId) { this.selected = false; }
                });
                refresh(form, true);
            });
            $(document).on('change', '.payment-modal-form .payment-for-select', function () {
                refresh($(this).closest('.payment-modal-form'), true);
            });
            $(document).on('change', '.payment-modal-form .payment-class-select', function () {
                var form = $(this).closest('.payment-modal-form');
                var classCode = String($(this).val() || '');
                form.find('.payment-invoice-select option:selected').each(function () {
                    if (classCode && String($(this).data('class-code')) !== classCode) { this.selected = false; }
                });
                refresh(form, true);
            });

            $(document).on('change', '.payment-modal-form .payment-invoice-check', function () {
                var form = $(this).closest('.payment-modal-form');
                var invoiceNo = String($(this).val());
                var checked = this.checked;
                form.find('.payment-invoice-select option').filter(function () { return String(this.value) === invoiceNo; }).prop('selected', checked);
                form.find('.payment-invoice-select').trigger('change');
            });

            $(document).on('click', '.payment-modal-form .payment-invoice-row', function (event) {
                if ($(event.target).is('input, label, a, button')) { return; }
                $(this).find('.payment-invoice-check').trigger('click');
            });

            $(document).on('change', '.payment-modal-form .payment-select-all', function () {
                var form = $(this).closest('.payment-modal-form');
                var checked = this.checked;
                form.find('.payment-invoice-row:not(.d-none) .payment-invoice-check').each(function () {
                    var invoiceNo = String(this.value);
                    this.checked = checked;
                    form.find('.payment-invoice-select option').filter(function () { return String(this.value) === invoiceNo; }).prop('selected', checked);
                });
                form.find('.payment-invoice-select').trigger('change');
            });

            $(document).on('change', '.payment-modal-form .payment-invoice-select', function () {
                var form = $(this).closest('.payment-modal-form');
                var first = selectedOptions(form).first();
                if (first.length && String(form.find('.payment-customer-select').val() || '') !== String(first.data('customer-id'))) {
                    form.find('.payment-customer-select').val(String(first.data('customer-id'))).trigger('change');
                    return;
                }
                if (first.length && String(form.find('.payment-class-select').val() || '') !== String(first.data('class-code'))) {
                    form.find('.payment-class-select').val(String(first.data('class-code'))).trigger('change');
                    return;
                }
                refresh(form, true);
                window.setTimeout(function () { refresh(form, true); }, 0);
            });

            $(document).on('input change', '.payment-modal-form .payment-amount-input, .payment-modal-form .payment-cwt-input', function () {
                var form = $(this).closest('.payment-modal-form');
                form.find('.payment-amount-summary').text(money(form.find('.payment-amount-input').val()));
                checkPaymentBalance(form);
            });
            $(document).on('change', '.payment-modal-form .payment-currency-select', function () { syncTotals($(this).closest('.payment-modal-form'), false); });
            $(document).on('shown.bs.modal', '.payment-modal', function () {
                var form = $(this).find('.payment-modal-form');
                var first = selectedOptions(form).first();
                if (first.length && !form.find('.payment-customer-select').val()) {
                    form.find('.payment-customer-select').val(String(first.data('customer-id'))).trigger('change');
                } else {
                    refresh(form, false);
                }
            });
        })();
    </script>
@endpush
