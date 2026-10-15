@extends('erp.layout')

@php
    $isEdit = $isEdit ?? false;
    $editingInvoice = $editingInvoice ?? [];
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

@section('title', $isEdit ? 'Edit Invoice' : 'Create Invoice')
@section('page-title', $isEdit ? 'Edit Invoice' : 'Create Invoice')

@push('styles')
<style>
    .invoice-workspace {
        background: #eef0f3;
        margin: -1.5rem;
        padding: 1.5rem;
        min-height: calc(100vh - 74px);
    }
    .invoice-toolbar {
        background: #ff6b00;
        color: #fff;
        margin: -1.5rem -1.5rem 1.25rem;
        padding: 10px 16px;
    }
    .invoice-toolbar .btn {
        border-color: rgba(255, 255, 255, .25);
        color: #fff;
    }
    .invoice-toolbar .btn-success {
        color: #fff;
    }
    .invoice-panel {
        background: #fff;
        border: 1px solid #e1e4ea;
        border-radius: 4px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .08);
    }
    .invoice-field {
        border: 0;
        border-bottom: 2px solid #c8c8c8;
        border-radius: 0;
        padding: 2px 0 3px;
        background: transparent;
        min-height: 30px;
        font-size: 12px;
    }
    .invoice-field:focus {
        box-shadow: none;
        border-color: #ff6b00;
    }
    .invoice-lines thead th {
        background: #777674;
        color: #fff;
        text-transform: none;
        letter-spacing: 0;
        font-size: 13px;
        border-color: #777674;
        padding: 6px 8px;
    }
    .invoice-lines textarea,
    .invoice-lines input,
    .invoice-lines select {
        border: 0;
        border-bottom: 2px solid #c8c8c8;
        border-radius: 0;
        background: transparent;
        min-height: 30px;
        padding: 2px 4px;
        font-size: 12px;
    }
    .invoice-lines td {
        padding: 5px 6px;
    }
    .invoice-lines textarea:focus,
    .invoice-lines input:focus,
    .invoice-lines select:focus {
        box-shadow: none;
        border-color: #ff6b00;
    }
    .invoice-total-box {
        max-width: 420px;
        margin-left: auto;
    }
    .invoice-qb-strip {
        background: #547197;
        color: #fff;
        border-radius: 4px;
        padding: 12px;
    }
    .invoice-qb-strip label {
        color: rgba(255, 255, 255, .86);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .02em;
    }
    .invoice-qb-strip .form-control,
    .invoice-qb-strip .form-select {
        background-color: #f8fafc;
        border: 1px solid rgba(15, 23, 42, .28);
        min-height: 34px;
        font-size: 12px;
        padding-top: 4px;
        padding-bottom: 4px;
    }
    .invoice-big-title {
        font-size: 36px;
        font-weight: 300;
        color: #5f6b76;
        letter-spacing: 0;
    }
    .invoice-panel {
        font-size: 12px;
    }
    .invoice-panel .form-label {
        font-size: 11px;
        margin-bottom: 2px;
    }
    .invoice-panel textarea.invoice-field {
        min-height: 56px;
    }
    .invoice-panel .row {
        --bs-gutter-y: .65rem;
    }
</style>
@endpush

@section('content')
    <div class="invoice-workspace">
        @if($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the invoice before saving.</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('erp.invoices.review') }}" id="invoiceForm">
            {{ csrf_field() }}
            <input type="hidden" name="original_invoice_no" value="{{ $isEdit && !empty($editingInvoice['invoice_no']) ? $editingInvoice['invoice_no'] : '' }}">

            <div class="invoice-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3">
                    <h1 class="h5 mb-0">{{ $isEdit ? 'Edit Invoice' : 'New Invoice' }}</h1>
                    <a href="{{ route('erp.billings.index') }}" class="btn btn-sm btn-outline-light"><i class="bi bi-x-circle me-1"></i>Back</a>
                    @unless($isEdit)
                        <button class="btn btn-sm btn-outline-light" type="button" id="sampleInvoiceButton"><i class="bi bi-magic me-1"></i>Sample Invoice</button>
                        <a href="{{ route('erp.invoices.create') }}" class="btn btn-sm btn-outline-light" onclick="return confirm('Clear all unsaved Billing Invoice values?')"><i class="bi bi-arrow-counterclockwise me-1"></i>Clear Form</a>
                    @endunless
                    <button class="btn btn-sm btn-outline-light" type="submit" name="save_mode" value="draft"><i class="bi bi-save me-1"></i>{{ $isEdit ? 'Update Draft' : 'Save Draft' }}</button>
                    @if(in_array(strtolower(auth()->user()->role ?? ''), ['administrator', 'admin', 'finance'], true))
                        <button class="btn btn-sm btn-success" type="submit" name="save_mode" value="post"><i class="bi bi-journal-check me-1"></i>{{ $isEdit ? 'Update & Post' : 'Save & Post' }}</button>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('erp.billings.print-invoice', $isEdit && !empty($editingInvoice['invoice_no']) ? ['invoice' => $editingInvoice['invoice_no']] : []) }}" class="btn btn-sm btn-outline-light vatable-print-action invoice-linked-action" data-base-url="{{ route('erp.billings.print-invoice') }}"><i class="bi bi-filetype-pdf me-1"></i>Billing Invoice</a>
                    <a href="{{ route('erp.billings.print-soa', $isEdit && !empty($editingInvoice['invoice_no']) ? ['invoice' => $editingInvoice['invoice_no']] : []) }}" class="btn btn-sm btn-outline-light vatable-print-action invoice-linked-action" data-base-url="{{ route('erp.billings.print-soa') }}"><i class="bi bi-file-earmark-text me-1"></i>SOA B</a>
                    <a href="{{ route('erp.soa-a.preview', $isEdit && !empty($editingInvoice['invoice_no']) ? ['invoice' => $editingInvoice['invoice_no']] : []) }}" class="btn btn-sm btn-outline-light non-tax-print-action d-none invoice-linked-action" data-base-url="{{ route('erp.soa-a.preview') }}"><i class="bi bi-file-earmark-text me-1"></i>SOA A</a>
                    <a href="{{ route('erp.billings.print-layout.invoice', $isEdit && !empty($editingInvoice['invoice_no']) ? ['invoice' => $editingInvoice['invoice_no']] : []) }}" class="btn btn-sm btn-outline-light invoice-linked-action" data-base-url="{{ route('erp.billings.print-layout.invoice') }}"><i class="bi bi-sliders me-1"></i>BI Layout</a>
                    <a href="{{ route('erp.billings.print-layout.soa-b', $isEdit && !empty($editingInvoice['invoice_no']) ? ['invoice' => $editingInvoice['invoice_no']] : []) }}" class="btn btn-sm btn-outline-light vatable-print-action invoice-linked-action" data-base-url="{{ route('erp.billings.print-layout.soa-b') }}"><i class="bi bi-sliders me-1"></i>SOA B Layout</a>
                    <a href="{{ route('erp.billings.print-layout.soa-a', $isEdit && !empty($editingInvoice['invoice_no']) ? ['invoice' => $editingInvoice['invoice_no']] : []) }}" class="btn btn-sm btn-outline-light non-tax-print-action d-none invoice-linked-action" data-base-url="{{ route('erp.billings.print-layout.soa-a') }}"><i class="bi bi-sliders me-1"></i>SOA A Layout</a>
                    <button class="btn btn-sm btn-outline-light" type="button" onclick="window.print()"><i class="bi bi-printer-fill me-1"></i>Print</button>
                </div>
            </div>

            <div class="invoice-qb-strip mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label">Customer:Job</label>
                        <select name="customer_id" id="customerSelect" class="form-select erp-select" required>
                            <option value="">Select customer/job...</option>
                            <option value="GUEST"
                                    data-name="Guest / Non-tax Client"
                                    data-terms="Due on Receipt"
                                    data-currency="PHP"
                                    data-class="MANILA"
                                    data-tax="exempt"
                                    data-tin="">
                                GUEST - Guest / Non-tax Client
                            </option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer['customer_id'] }}"
                                        data-name="{{ $customer['name'] }}"
                                        data-terms="{{ $customer['terms'] }}"
                                        data-currency="{{ $customer['currency'] }}"
                                        data-class="{{ $customer['class'] }}"
                                        data-tax="{{ $customer['vat_classification'] }}"
                                        data-tin="{{ $customer['tin'] }}"
                                        data-address="{{ $customer['address'] }}">
                                    {{ $customer['customer_id'] }} - {{ $customer['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="customer_name" id="customerName" value="">
                        <input type="hidden" name="ar_account" id="arAccount" value="">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Class</label>
                        <select name="class_code" id="classCode" class="form-select">
                            <option value=""></option>
                            <option>MANILA</option>
                            <option>PAMPANGA</option>
                            <option>CEBU</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Template</label>
                        <select name="invoice_template[]" id="invoiceTemplate" class="form-select erp-select" multiple>
                            @foreach($invoiceTemplates as $template)
                                <option value="{{ $template['key'] }}" data-account="{{ $template['account'] }}">{{ $template['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="invoice-panel p-4 mb-4">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="invoice-big-title mb-4">Invoice</div>
                        <div class="small text-muted mb-2">Connected client details</div>
                        <div class="fw-semibold mb-4" id="clientSummary">No customer selected</div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Shipment Job ID</label>
                                <input name="job_shipment_id" class="form-control invoice-field" value="" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">SOA Number</label>
                                <input name="soa_no" id="soaNo" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">MAWB</label>
                                <input name="mawb_no" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">HAWB</label>
                                <input name="hawb_no" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Origin / Destination</label>
                                <input name="origin_dest" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Date of Shipout</label>
                                <input name="date_shipped" type="date" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">TIN</label>
                                <input name="tin" id="customerTin" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Commodity</label>
                                <input name="commodity" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted">Customer Message</label>
                                <select name="customer_message" class="form-select invoice-field">
                                    <option value=""></option>
                                    <option>Please issue check payable to Sea-Jet International Forwarders, Inc.</option>
                                    <option>Thank you for your business.</option>
                                    <option>Payment is due based on agreed credit terms.</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Memo</label>
                                <textarea name="memo" class="form-control invoice-field" rows="4">{{ old('memo', '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Customer Tax Code</label>
                                <select name="customer_tax_code" id="customerTaxCode" class="form-select invoice-field">
                                    <option value=""></option>
                                    <option>VAT12</option>
                                    <option>ZERO</option>
                                    <option>EXEMPT</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted">Date</label>
                                <input name="invoice_date" type="date" class="form-control invoice-field" value="" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">Document No.</label>
                                <input name="transaction_no" id="transactionNo" class="form-control invoice-field" value="" placeholder="Auto-generated if blank">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">Due Date</label>
                                <input name="due_date" type="date" class="form-control invoice-field" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Invoice To</label>
                                <textarea name="invoice_to" class="form-control invoice-field" rows="4"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Consignee</label>
                                <textarea name="consignee" class="form-control invoice-field" rows="4"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Total Payment</label>
                                <input name="total_payment" class="form-control invoice-field text-end" value="">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Invoice Amt</label>
                                <input name="invoice_amount" id="invoiceAmountInput" class="form-control invoice-field text-end" value="" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Terms</label>
                                <select name="credit_term_days" id="creditTerms" class="form-select invoice-field">
                                    <option value=""></option>
                                    <option value="30">30 Days</option>
                                    <option value="60">60 Days</option>
                                    <option value="90">90 Days</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Invoice No. #</label>
                                <input name="invoice_no" id="invoiceNo" class="form-control invoice-field" value="" placeholder="Select a class for the next number">
                                <div class="form-text">Leave blank to assign the next number for this class when saving.</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted">Currency</label>
                                <select name="currency" id="currencySelect" class="form-select invoice-field erp-select">
                                    <option value=""></option>
                                    <option>PHP</option>
                                    <option>USD</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted">Forex Rate</label>
                                <input name="exchange_rate" id="exchangeRateInput" class="form-control invoice-field text-end" value="1.000000">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted">Default WHT %</label>
                                <div class="input-group">
                                    <input name="withholding_tax_rate" id="withholdingTaxRate" type="number" min="0" max="100" step="0.01" class="form-control invoice-field text-end" value="0">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Used only when an item's WHT % is blank; reimbursements excluded.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-center gap-2 mb-2 text-muted">
                <i class="bi bi-box-seam-fill"></i>
                <span class="fw-semibold">Items</span>
            </div>

            <div class="invoice-panel p-3 mb-3">
                <div class="table-responsive">
                    <table class="table invoice-lines align-middle mb-0" id="invoiceLines">
                        <thead>
                        <tr>
                            <th style="width: 170px">Item</th>
                            <th style="width: 210px">Account</th>
                            <th>Description</th>
                            <th class="text-end" style="width: 130px">Rate</th>
                            <th class="text-end" style="width: 100px">Qty</th>
                            <th class="text-end" style="width: 150px">Total</th>
                            <th class="text-end" style="width: 105px">WHT %</th>
                            <th style="width: 140px">Tax</th>
                            <th class="text-center no-print" style="width: 52px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="invoice-line">
                            <td>
                                <select name="item[]" class="form-select item-select">
                                    <option value=""></option>
                                    @foreach($items ?? [] as $item)
                                        <option value="{{ $item->name }}"
                                                data-description="{{ $item->description }}"
                                                data-account-tag="{{ $item->account_tag }}"
                                                data-rate="{{ $item->rate }}"
                                                data-qty="{{ $item->qty }}"
                                                data-account="{{ $item->account }}"
                                                data-tax="{{ $item->tax }}">{{ $item->name }} [{{ $item->account_tag }}]</option>
                                    @endforeach
                                </select>
                                <span class="badge badge-soft item-account-tag d-none mt-1"></span>
                            </td>
                            <td>
                                <select name="line_ar_account[]" class="form-select line-account">
                                    <option value=""></option>
                                    <option>A/R-Reimbursable Charges</option>
                                    <option>A/Trade-Billing Invoice</option>
                                    <option>A/R Trade Metro</option>
                                    <option>A/R Trade-Debit Note</option>
                                </select>
                            </td>
                            <td><textarea name="item_description[]" class="form-control" rows="2"></textarea></td>
                            <td><input name="unit_price[]" class="form-control text-end line-unit" value=""></td>
                            <td><input name="qty[]" class="form-control text-end line-qty" value=""></td>
                            <td><input name="line_amount[]" class="form-control text-end line-total" value="" readonly></td>
                            <td><input name="line_withholding_rate[]" type="number" min="0" max="100" step="0.01" class="form-control text-end line-wht-rate" value=""></td>
                            <td>
                                <select name="tax_category[]" class="form-select line-tax">
                                    <option value=""></option>
                                    <option value="12%">12% VAT</option>
                                    <option value="zero-rated">Zero-rated</option>
                                    <option value="exempt">VAT Exempt</option>
                                </select>
                            </td>
                            <td class="text-center no-print"><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 no-print">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addLine"><i class="bi bi-plus-circle me-1"></i>Add Line</button>
                    <span class="small text-muted">Multiple invoice lines are supported.</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="invoice-panel p-3 h-100">
                        <label class="form-label text-muted">Terms / Notes</label>
                        <textarea name="description" class="form-control invoice-field" rows="4"></textarea>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="invoice-panel p-3 invoice-total-box">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Subtotal</span>
                            <strong id="subtotalText">PHP 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">VAT</span>
                            <strong id="taxText">PHP 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Invoice / SOA Total</span>
                            <strong id="balanceText">PHP 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Less Withholding Tax (<span id="withholdingRateText">0%</span>)</span>
                            <strong id="withholdingTaxText">PHP 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="fw-semibold">Please Pay This Amount</span>
                            <strong class="text-primary" id="netAmountDueText">PHP 0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-top" id="phpEquivalentRow">
                            <span class="text-muted">PHP Equivalent</span>
                            <strong id="phpEquivalentText">PHP 0.00</strong>
                        </div>
                        <input type="hidden" name="amount" id="amountInput" value="0.00">
                    </div>
                </div>
            </div>

            <input type="hidden" name="vat_classification" id="vatClassification" value="">
        </form>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        var isEdit = @json($isEdit);
        var editInvoice = @json($editingInvoice);
        var invoiceSeries = @json($invoiceSeries);
        var soaASeries = @json($soaASeries);

        function updateInvoiceNumberPreview() {
            var classCode = $('#classCode').val();
            var templates = $('#invoiceTemplate').val() || [];
            var isSoaA = templates.indexOf('SOA_A') !== -1;
            var isSoaBOnly = templates.indexOf('SOA_B') !== -1 && templates.indexOf('Billing Invoice') === -1;
            var soaNumber = ($('#soaNo').val() || '').trim();
            $('#invoiceNo, #transactionNo').prop('readonly', isSoaBOnly);
            if (isSoaBOnly) {
                $('#invoiceNo, #transactionNo').val(soaNumber);
                syncInvoiceActionLinks();
            }
            $('#invoiceNo').attr('placeholder', isSoaBOnly ? 'Uses SOA number' : (isSoaA ? soaASeries + ' (assigned on save)' : (invoiceSeries[classCode] || 'Select a class for the next number')));
            $('#transactionNo').attr('placeholder', isSoaBOnly ? 'Uses SOA number' : 'Auto-generated if blank');
            $('input[name="soa_no"]').attr('placeholder', isSoaA ? soaASeries + ' (assigned on save)' : 'SOA number');
        }

        function numberValue(value) {
            var parsed = parseFloat(String(value || '').replace(/,/g, ''));
            return isNaN(parsed) ? 0 : parsed;
        }

        function money(value) {
            return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function roundMoney(value) {
            return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
        }

        function currency() {
            return $('#currencySelect').val() || 'PHP';
        }

        function syncExchangeRate(forceDefault) {
            var rate = $('#exchangeRateInput');
            var selectedCurrency = currency();

            if (selectedCurrency === 'PHP') {
                rate.val('1.000000').prop('readonly', true);
                return;
            }

            rate.prop('readonly', false);
            if (forceDefault && !numberValue(rate.val())) {
                rate.val('1.000000');
            }
        }

        function syncInvoiceNumbers(source) {
            var invoiceNo = $('#invoiceNo');
            var transactionNo = $('#transactionNo');
            var value = source === 'transaction' ? transactionNo.val() : invoiceNo.val();

            if (source === 'transaction') {
                invoiceNo.val(value);
                syncInvoiceActionLinks();
                return;
            }

            transactionNo.val(value);
            syncInvoiceActionLinks();
        }

        function syncInvoiceActionLinks() {
            var invoiceNo = ($('#invoiceNo').val() || $('#transactionNo').val() || '').trim();

            $('.invoice-linked-action').each(function () {
                var baseUrl = $(this).data('base-url');
                if (!baseUrl) {
                    return;
                }

                if (!invoiceNo) {
                    $(this).attr('href', baseUrl);
                    return;
                }

                $(this).attr('href', baseUrl + '?invoice=' + encodeURIComponent(invoiceNo));
            });
        }

        function syncCustomer() {
            var option = $('#customerSelect option:selected');
            if (!option.val()) {
                $('#customerName').val('');
                $('#clientSummary').text('No customer selected');
                $('#arAccount').val('');
                $('.line-account').val('');
                $('#invoiceTemplate').val(null).trigger('change.select2');
                $('.vatable-print-action').removeClass('d-none');
                $('.non-tax-print-action').addClass('d-none');
                return;
            }

            var taxClass = option.data('tax') || '';
            var isVatable = taxClass === '12%';

            $('#customerName').val(option.data('name') || '');
            $('#currencySelect').val(option.data('currency') || '').trigger('change.select2');
            syncExchangeRate(true);
            $('#classCode').val(option.data('class') || '');
            updateInvoiceNumberPreview();
            $('#vatClassification').val(taxClass);
            $('#customerTin').val(option.data('tin') || '');
            $('textarea[name="invoice_to"]').val(option.data('address') || '');
            var terms = String(option.data('terms') || '');
            $('#creditTerms').val(terms.replace(/[^0-9]/g, ''));
            $('#customerTaxCode').val(isVatable ? 'VAT12' : (taxClass === 'zero-rated' ? 'ZERO' : 'EXEMPT'));
            $('#arAccount').val(isVatable ? 'A/Trade-Billing Invoice' : 'A/R Trade Metro');
            $('.line-account').each(function () {
                if (!$(this).val()) {
                    $(this).val($('#arAccount').val());
                }
            });
            $('#invoiceTemplate').val(isVatable ? ['SOA_B', 'Billing Invoice'] : ['SOA_A']).trigger('change.select2');
            $('.vatable-print-action').toggleClass('d-none', !isVatable);
            $('.non-tax-print-action').toggleClass('d-none', isVatable);
            $('#clientSummary').text((option.data('terms') || '-') + ' | ' + (option.data('currency') || '-') + ' | ' + (taxClass || '-') + ' | ' + (isVatable ? 'Print SOA B + Billing Invoice' : 'Print SOA A'));
        }

        function syncDocumentAccount() {
            var account = '';
            $('.line-account').each(function () {
                if (!account && $(this).val()) {
                    account = $(this).val();
                }
            });
            $('#arAccount').val(account);
        }

        function initItemSelects(scope) {
            var container = scope ? $(scope) : $('#invoiceLines');
            container.find('.item-select').each(function () {
                var select = $(this);
                if (select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                select.select2({
                    width: '100%',
                    placeholder: 'Search item',
                    allowClear: true
                });
            });
        }

        function syncItem(row) {
            var option = row.find('.item-select option:selected');
            if (!option.val()) {
                row.find('.item-account-tag').text('').addClass('d-none');
                return;
            }

            row.find('.item-account-tag').text(option.data('account-tag') || '').toggleClass('d-none', !option.data('account-tag'));
            row.find('textarea[name="item_description[]"]').val(option.data('description') || '');
            row.find('.line-unit').val(option.data('rate') || '');
            row.find('.line-qty').val(option.data('qty') || '1');
            row.find('.line-account').val(option.data('account') || $('#arAccount').val() || '');
            row.find('.line-tax').val(option.data('tax') || '');
            syncDocumentAccount();
            recalc();
        }

        function recalc() {
            var billingSubtotal = 0;
            var reimbursement = 0;
            var tax = 0;
            var defaultWithholdingRate = Math.min(100, Math.max(0, numberValue($('#withholdingTaxRate').val())));
            var withholdingTax = 0;

            $('#invoiceLines tbody tr').each(function () {
                var row = $(this);
                var unit = numberValue(row.find('.line-unit').val());
                var qty = numberValue(row.find('.line-qty').val());
                var line = roundMoney(unit * qty);
                var category = row.find('.line-tax').val();
                var item = String(row.find('.item-select').val() || '').toLowerCase();
                var description = String(row.find('textarea[name="item_description[]"]').val() || '').toLowerCase();
                var account = String(row.find('.line-account').val() || '').toLowerCase();
                var isReimbursement = account.indexOf('reimburs') !== -1 || item.indexOf('reimburs') !== -1 || description.indexOf('reimburs') !== -1;

                row.find('.line-total').val(line ? money(line) : '');

                if (isReimbursement) {
                    reimbursement += line;
                    return;
                }

                billingSubtotal += line;
                var lineRateValue = row.find('.line-wht-rate').val();
                var lineWithholdingRate = lineRateValue === ''
                    ? defaultWithholdingRate
                    : Math.min(100, Math.max(0, numberValue(lineRateValue)));
                withholdingTax += line * (lineWithholdingRate / 100);
                if (category === '12%') {
                    tax += line * 0.12;
                }
            });

            billingSubtotal = roundMoney(billingSubtotal);
            reimbursement = roundMoney(reimbursement);
            tax = roundMoney(tax);
            withholdingTax = roundMoney(withholdingTax);
            var billingTotal = roundMoney(billingSubtotal + tax);
            var soaTotal = roundMoney(billingTotal + reimbursement);
            var netAmountDue = Math.max(0, roundMoney(soaTotal - withholdingTax));
            $('#subtotalText').text(currency() + ' ' + money(billingSubtotal));
            $('#taxText').text(currency() + ' ' + money(tax));
            $('#balanceText').text(currency() + ' ' + money(soaTotal));
            $('#withholdingRateText').text('per item');
            $('#withholdingTaxText').text(currency() + ' ' + money(withholdingTax));
            $('#netAmountDueText').text(currency() + ' ' + money(netAmountDue));
            $('#amountInput').val(soaTotal.toFixed(2));
            $('#invoiceAmountInput').val(billingTotal ? money(billingTotal) : '');
            var exchangeRate = numberValue($('#exchangeRateInput').val()) || 1;
            var showPhpEquivalent = currency() !== 'PHP';
            $('#phpEquivalentRow').toggleClass('d-none', !showPhpEquivalent);
            $('#phpEquivalentText').text('PHP ' + money(soaTotal * exchangeRate));
        }

        function setLine(index, item, description, rate, qty, taxCategory, account, withholdingRate) {
            while ($('#invoiceLines tbody tr').length <= index) {
                $('#addLine').trigger('click');
            }

            var row = $('#invoiceLines tbody tr').eq(index);
            row.find('.item-select').val(item).trigger('change.select2');
            var accountTag = row.find('.item-select option:selected').data('account-tag') || '';
            row.find('.item-account-tag').text(accountTag).toggleClass('d-none', !accountTag);
            row.find('textarea[name="item_description[]"]').val(description);
            row.find('.line-unit').val(rate);
            row.find('.line-qty').val(qty);
            row.find('.line-tax').val(taxCategory);
            row.find('.line-account').val(account || $('#arAccount').val() || '');
            row.find('.line-wht-rate').val(withholdingRate === undefined || withholdingRate === null ? '' : withholdingRate);
            syncDocumentAccount();
        }

        function fillSampleInvoice() {
            $('#customerSelect').val('CUST-001').trigger('change').trigger('change.select2');

            $('input[name="job_shipment_id"]').val('JOB-SEA-2026-0501');
            $('input[name="soa_no"]').val('SOA-2026-0501');
            $('input[name="mawb_no"]').val('MAWB-7842-SEA');
            $('input[name="hawb_no"]').val('HAWB-501-A');
            $('input[name="origin_dest"]').val('SINGAPORE / MANILA');
            $('input[name="date_shipped"]').val('2026-06-07');
            $('input[name="commodity"]').val('Consolidated sea freight cargo');
            $('select[name="customer_message"]').val('Payment is due based on agreed credit terms.');
            $('textarea[name="memo"]').val('Sample invoice generated for testing');
            $('input[name="invoice_date"]').val('2026-06-07');
            $('input[name="due_date"]').val('2026-07-07');
            $('#invoiceNo').val('BI-2026-0501');
            $('#transactionNo').val('BI-2026-0501');
            syncInvoiceActionLinks();
            $('textarea[name="invoice_to"]').val('North Harbor Trading\nNorth Harbor Center, Manila\nTIN: 000-345-111-000');
            $('textarea[name="consignee"]').val('North Harbor Trading\nManila Port Warehouse');
            $('input[name="total_payment"]').val('0.00');
            $('#withholdingTaxRate').val('2.00');
            $('textarea[name="description"]').val('Sample billing for shipment-linked services. Includes VATable service charges and reimbursable charges.');
            $('#invoiceTemplate').val(['SOA_B', 'Billing Invoice']).trigger('change.select2');

            $('#invoiceLines tbody tr:gt(0)').remove();
            setLine(0, 'Terminal Handling', 'TERMINAL HANDLING CHARGES', '321714.25', '1', '12%', 'A/Trade-Billing Invoice');
            setLine(1, 'Documentation VAT', 'DOC / IDENTIFICATION FEE', '4280.60', '1', '12%', 'A/Trade-Billing Invoice');
            setLine(2, 'Reimbursable Charges', 'REIMBURSABLE CHARGES - PORT ADVANCES', '753495.10', '1', 'exempt', 'A/R-Reimbursable Charges');

            recalc();
        }

        function dateValue(value) {
            if (!value) {
                return '';
            }

            return String(value).substring(0, 10);
        }

        function setTemplateValues(values) {
            var select = $('#invoiceTemplate');
            values = values || [];

            values.forEach(function (value) {
                if (value && !select.find('option').filter(function () { return this.value === value; }).length) {
                    select.append(new Option(value, value, true, true));
                }
            });

            select.val(values).trigger('change.select2');
        }

        function fillEditInvoice() {
            if (!editInvoice || !editInvoice.invoice_no) {
                return false;
            }

            var customerId = editInvoice.customer_id || '';
            if (!customerId && editInvoice.customer) {
                $('#customerSelect option').each(function () {
                    if ($(this).data('name') === editInvoice.customer) {
                        customerId = this.value;
                    }
                });
            }

            if (customerId) {
                $('#customerSelect').val(customerId).trigger('change').trigger('change.select2');
            }

            $('#customerName').val(editInvoice.customer || '');
            $('#clientSummary').text([
                editInvoice.customer || 'Selected invoice',
                editInvoice.currency || '',
                editInvoice.invoice_no || ''
            ].filter(Boolean).join(' | '));
            $('#classCode').val(editInvoice.class_code || $('#classCode').val() || '');
            updateInvoiceNumberPreview();
            $('#arAccount').val(editInvoice.ar_account || 'A/Trade-Billing Invoice');
            $('#currencySelect').val(editInvoice.currency || 'PHP').trigger('change.select2');
            $('#exchangeRateInput').val(editInvoice.exchange_rate || '1.000000');
            syncExchangeRate(false);
            $('#vatClassification').val(editInvoice.vat_classification || '');
            $('#customerTaxCode').val(editInvoice.customer_tax_code || (editInvoice.vat_classification === '12%' ? 'VAT12' : ''));
            $('#customerTin').val(editInvoice.tin || '');

            $('input[name="job_shipment_id"]').val(editInvoice.job_shipment_id || '');
            $('input[name="soa_no"]').val(editInvoice.soa_no || '');
            $('input[name="mawb_no"]').val(editInvoice.mawb_no || '');
            $('input[name="hawb_no"]').val(editInvoice.hawb_no || '');
            $('input[name="origin_dest"]').val(editInvoice.origin_dest || '');
            $('input[name="date_shipped"]').val(dateValue(editInvoice.date_shipped));
            $('input[name="commodity"]').val(editInvoice.commodity || '');
            $('select[name="customer_message"]').val(editInvoice.customer_message || '');
            $('textarea[name="memo"]').val(editInvoice.memo || '');
            $('input[name="invoice_date"]').val(dateValue(editInvoice.invoice_date));
            $('input[name="due_date"]').val(dateValue(editInvoice.due_date));
            $('#invoiceNo').val(editInvoice.invoice_no || '');
            $('#transactionNo').val(editInvoice.transaction_no || editInvoice.invoice_no || '');
            syncInvoiceActionLinks();
            $('textarea[name="invoice_to"]').val(editInvoice.invoice_to || '');
            $('textarea[name="consignee"]').val(editInvoice.consignee || '');
            $('input[name="total_payment"]').val(editInvoice.total_payment || '');
            $('#withholdingTaxRate').val(editInvoice.withholding_tax_rate || '0');
            $('textarea[name="description"]').val(editInvoice.description || '');
            $('#creditTerms').val(String(editInvoice.credit_term_days || '').replace(/[^0-9]/g, ''));
            setTemplateValues(editInvoice.invoice_template || (editInvoice.vat_classification === '12%' ? ['SOA_B', 'Billing Invoice'] : ['SOA_A']));

            $('#invoiceLines tbody tr:gt(0)').remove();
            var items = editInvoice.item || [];
            var descriptions = editInvoice.item_description || [];
            var rates = editInvoice.unit_price || [];
            var quantities = editInvoice.qty || [];
            var taxes = editInvoice.tax_category || [];
            var accounts = editInvoice.line_ar_account || [];
            var withholdingRates = editInvoice.line_withholding_rate || [];
            var count = Math.max(items.length, descriptions.length, rates.length, quantities.length, 1);

            for (var i = 0; i < count; i++) {
                setLine(
                    i,
                    items[i] || '',
                    descriptions[i] || '',
                    rates[i] || '',
                    quantities[i] || '1',
                    taxes[i] || editInvoice.vat_classification || '',
                    accounts[i] || editInvoice.ar_account || '',
                    withholdingRates[i] === undefined ? editInvoice.withholding_tax_rate || '' : withholdingRates[i]
                );
            }

            recalc();
            return true;
        }

        $('#customerSelect').on('change', function () {
            syncCustomer();
            recalc();
        });

        $('#classCode, #invoiceTemplate').on('change', updateInvoiceNumberPreview);

        $('#currencySelect').on('change', function () {
            syncExchangeRate(true);
            recalc();
        });
        $('#invoiceNo').on('input', function () {
            syncInvoiceNumbers('invoice');
        });
        $('#transactionNo').on('input', function () {
            syncInvoiceNumbers('transaction');
        });
        $('#soaNo').on('input', updateInvoiceNumberPreview);
        $('#invoiceForm').on('submit', function () {
            syncDocumentAccount();
            syncInvoiceNumbers($('#invoiceNo').val() ? 'invoice' : 'transaction');
        });
        $('#invoiceLines').on('input change', '.line-unit, .line-qty, .line-tax, .line-wht-rate', recalc);
        $('#exchangeRateInput').on('input', recalc);
        $('#withholdingTaxRate').on('input change', recalc);
        $('#invoiceLines').on('change', '.line-account', syncDocumentAccount);
        $('#invoiceLines').on('change', '.item-select', function () {
            syncItem($(this).closest('tr'));
        });

        $('#addLine').on('click', function () {
            var row = $('#invoiceLines tbody tr:first').clone();
            row.find('.select2-container').remove();
            row.find('.item-select')
                .removeClass('select2-hidden-accessible')
                .removeAttr('data-select2-id')
                .removeAttr('aria-hidden')
                .removeAttr('tabindex');
            row.find('.item-select option').removeAttr('data-select2-id');
            row.find('select').val('');
            row.find('textarea').val('');
            row.find('.line-unit').val('');
            row.find('.line-qty').val('');
            row.find('.line-total').val('');
            row.find('.line-wht-rate').val('');
            row.find('.line-account').val($('#arAccount').val() || '');
            row.find('.item-account-tag').text('').addClass('d-none');
            $('#invoiceLines tbody').append(row);
            initItemSelects(row);
            syncDocumentAccount();
            recalc();
        });

        $('#invoiceLines').on('click', '.remove-line', function () {
            if ($('#invoiceLines tbody tr').length > 1) {
                $(this).closest('tr').remove();
                syncDocumentAccount();
                recalc();
            }
        });

        $('#sampleInvoiceButton').on('click', fillSampleInvoice);

        if (!fillEditInvoice()) {
            syncCustomer();
        }
        updateInvoiceNumberPreview();
        initItemSelects();
        syncExchangeRate(true);
        recalc();
        syncInvoiceActionLinks();
        syncDocumentAccount();
    });
</script>
@endpush
