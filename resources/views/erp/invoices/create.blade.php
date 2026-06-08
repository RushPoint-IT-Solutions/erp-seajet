@extends('erp.layout')

@php
    $isEdit = $isEdit ?? false;
    $editingInvoice = $editingInvoice ?? [];
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
        <form method="post" action="{{ route('erp.invoices.review') }}" id="invoiceForm">
            {{ csrf_field() }}

            <div class="invoice-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3">
                    <h1 class="h5 mb-0">{{ $isEdit ? 'Edit Invoice' : 'New Invoice' }}</h1>
                    <a href="{{ route('erp.billings.index') }}" class="btn btn-sm btn-outline-light"><i class="bi bi-x-circle me-1"></i>Back</a>
                    @unless($isEdit)
                        <button class="btn btn-sm btn-outline-light" type="button" id="sampleInvoiceButton"><i class="bi bi-magic me-1"></i>Sample Invoice</button>
                    @endunless
                    <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-cloud-arrow-up-fill me-1"></i>{{ $isEdit ? 'Update' : 'Save' }}</button>
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
                                        data-tin="{{ $customer['tin'] }}">
                                    {{ $customer['customer_id'] }} - {{ $customer['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="customer_name" id="customerName" value="">
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
                        <label class="form-label">Account</label>
                        <select name="ar_account" id="arAccount" class="form-select">
                            <option value=""></option>
                            <option>A/R-Reimbursable Charges</option>
                            <option>A/Trade-Billing Invoice</option>
                            <option>A/R Trade-SOA A</option>
                            <option>A/R Trade-Debit Note</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Template</label>
                        <select name="invoice_template[]" id="invoiceTemplate" class="form-select erp-select" multiple>
                            @foreach($invoiceTemplates as $template)
                                <option value="{{ $template['key'] }}" data-account="{{ $template['account'] }}">{{ $template['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="input-group input-group-sm mt-2">
                            <input type="text" id="customTemplateName" class="form-control" placeholder="Add custom template">
                            <button type="button" class="btn btn-outline-secondary" id="addInvoiceTemplate" title="Add template">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
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
                                <input name="soa_no" class="form-control invoice-field" value="">
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
                                <input name="memo" class="form-control invoice-field" value="">
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
                                <label class="form-label text-muted">Invoice #</label>
                                <input name="transaction_no" id="transactionNo" class="form-control invoice-field" value="">
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
                                <label class="form-label text-muted">Invoice Number</label>
                                <input name="invoice_no" id="invoiceNo" class="form-control invoice-field" value="" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted">Currency</label>
                                <select name="currency" id="currencySelect" class="form-select invoice-field erp-select">
                                    <option value=""></option>
                                    <option>PHP</option>
                                    <option>USD</option>
                                </select>
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
                            <th>Description</th>
                            <th class="text-end" style="width: 130px">Rate</th>
                            <th class="text-end" style="width: 100px">Qty</th>
                            <th style="width: 150px">Class</th>
                            <th class="text-end" style="width: 150px">Total</th>
                            <th style="width: 140px">Tax</th>
                            <th class="text-center no-print" style="width: 52px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="invoice-line">
                            <td>
                                <select name="item[]" class="form-select item-select">
                                    <option value=""></option>
                                    <option value="Terminal Handling"
                                            data-description="TERMINAL HANDLING CHARGES"
                                            data-rate="321714.25"
                                            data-qty="1"
                                            data-class="MANILA"
                                            data-tax="12%">Terminal Handling</option>
                                    <option value="Doc Stamp VAT"
                                            data-description="DOC STAMP / VAT STAMPS"
                                            data-rate="20.00"
                                            data-qty="1"
                                            data-class="MANILA"
                                            data-tax="12%">Doc Stamp VAT</option>
                                    <option value="Documentation VAT"
                                            data-description="DOC / IDENTIFICATION FEE"
                                            data-rate="4280.60"
                                            data-qty="1"
                                            data-class="MANILA"
                                            data-tax="12%">Documentation VAT</option>
                                    <option value="Ocean Freight"
                                            data-description="OCEAN FREIGHT SERVICE CHARGE"
                                            data-rate="0"
                                            data-qty="1"
                                            data-class="MANILA"
                                            data-tax="zero-rated">Ocean Freight</option>
                                    <option value="Reimbursable Charges"
                                            data-description="REIMBURSABLE CHARGES"
                                            data-rate="0"
                                            data-qty="1"
                                            data-class="MANILA"
                                            data-tax="exempt">Reimbursable Charges</option>
                                </select>
                            </td>
                            <td><textarea name="item_description[]" class="form-control" rows="2"></textarea></td>
                            <td><input name="unit_price[]" class="form-control text-end line-unit" value=""></td>
                            <td><input name="qty[]" class="form-control text-end line-qty" value=""></td>
                            <td>
                                <select name="line_class[]" class="form-select">
                                    <option value=""></option>
                                    <option>MANILA</option>
                                    <option>PAMPANGA</option>
                                    <option>CEBU</option>
                                </select>
                            </td>
                            <td><input name="line_amount[]" class="form-control text-end line-total" value="" readonly></td>
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
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">SOA Total</span>
                            <strong class="text-primary" id="balanceText">PHP 0.00</strong>
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

        function numberValue(value) {
            var parsed = parseFloat(String(value || '').replace(/,/g, ''));
            return isNaN(parsed) ? 0 : parsed;
        }

        function money(value) {
            return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function currency() {
            return $('#currencySelect').val() || 'PHP';
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
                $('#invoiceTemplate').val(null).trigger('change.select2');
                $('.vatable-print-action').removeClass('d-none');
                $('.non-tax-print-action').addClass('d-none');
                return;
            }

            var taxClass = option.data('tax') || '';
            var isVatable = taxClass === '12%';

            $('#customerName').val(option.data('name') || '');
            $('#currencySelect').val(option.data('currency') || '').trigger('change.select2');
            $('#classCode').val(option.data('class') || '');
            $('#vatClassification').val(taxClass);
            $('#customerTin').val(option.data('tin') || '');
            var terms = String(option.data('terms') || '');
            $('#creditTerms').val(terms.replace(/[^0-9]/g, ''));
            $('#customerTaxCode').val(isVatable ? 'VAT12' : (taxClass === 'zero-rated' ? 'ZERO' : 'EXEMPT'));
            $('#arAccount').val(isVatable ? 'A/Trade-Billing Invoice' : 'A/R Trade-SOA A');
            $('#invoiceTemplate').val(isVatable ? ['SOA_B', 'Billing Invoice'] : ['SOA_A']).trigger('change.select2');
            $('.vatable-print-action').toggleClass('d-none', !isVatable);
            $('.non-tax-print-action').toggleClass('d-none', isVatable);
            $('#clientSummary').text((option.data('terms') || '-') + ' | ' + (option.data('currency') || '-') + ' | ' + (taxClass || '-') + ' | ' + (isVatable ? 'Print SOA B + Billing Invoice' : 'Print SOA A'));
        }

        $('#addInvoiceTemplate').on('click', function () {
            var input = $('#customTemplateName');
            var value = input.val().trim();
            var select = $('#invoiceTemplate');
            var selected = select.val() || [];

            if (!value) {
                input.focus();
                return;
            }

            if (!select.find('option').filter(function () { return this.value === value; }).length) {
                select.append(new Option(value, value, true, true));
            }

            if (selected.indexOf(value) === -1) {
                selected.push(value);
            }

            select.val(selected).trigger('change.select2');
            input.val('').focus();
        });

        function syncItem(row) {
            var option = row.find('.item-select option:selected');
            if (!option.val()) {
                return;
            }

            row.find('textarea[name="item_description[]"]').val(option.data('description') || '');
            row.find('.line-unit').val(option.data('rate') || '');
            row.find('.line-qty').val(option.data('qty') || '1');
            row.find('select[name="line_class[]"]').val(option.data('class') || $('#classCode').val() || '');
            row.find('.line-tax').val(option.data('tax') || '');
            recalc();
        }

        function recalc() {
            var billingSubtotal = 0;
            var reimbursement = 0;
            var tax = 0;

            $('#invoiceLines tbody tr').each(function () {
                var row = $(this);
                var unit = numberValue(row.find('.line-unit').val());
                var qty = numberValue(row.find('.line-qty').val());
                var line = unit * qty;
                var category = row.find('.line-tax').val();
                var item = String(row.find('.item-select').val() || '').toLowerCase();
                var description = String(row.find('textarea[name="item_description[]"]').val() || '').toLowerCase();
                var isReimbursement = item.indexOf('reimburs') !== -1 || description.indexOf('reimburs') !== -1;

                row.find('.line-total').val(line ? money(line) : '');

                if (isReimbursement) {
                    reimbursement += line;
                    return;
                }

                billingSubtotal += line;
                if (category === '12%') {
                    tax += line * 0.12;
                }
            });

            var billingTotal = billingSubtotal + tax;
            var soaTotal = billingTotal + reimbursement;
            $('#subtotalText').text(currency() + ' ' + money(billingSubtotal));
            $('#taxText').text(currency() + ' ' + money(tax));
            $('#balanceText').text(currency() + ' ' + money(soaTotal));
            $('#amountInput').val(soaTotal.toFixed(2));
            $('#invoiceAmountInput').val(billingTotal ? money(billingTotal) : '');
        }

        function setLine(index, item, description, rate, qty, lineClass, taxCategory) {
            while ($('#invoiceLines tbody tr').length <= index) {
                $('#addLine').trigger('click');
            }

            var row = $('#invoiceLines tbody tr').eq(index);
            row.find('.item-select').val(item);
            row.find('textarea[name="item_description[]"]').val(description);
            row.find('.line-unit').val(rate);
            row.find('.line-qty').val(qty);
            row.find('select[name="line_class[]"]').val(lineClass);
            row.find('.line-tax').val(taxCategory);
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
            $('input[name="memo"]').val('Sample invoice generated for testing');
            $('input[name="invoice_date"]').val('2026-06-07');
            $('input[name="due_date"]').val('2026-07-07');
            $('#invoiceNo').val('BI-2026-0501');
            $('#transactionNo').val('BI-2026-0501');
            syncInvoiceActionLinks();
            $('textarea[name="invoice_to"]').val('North Harbor Trading\nNorth Harbor Center, Manila\nTIN: 000-345-111-000');
            $('textarea[name="consignee"]').val('North Harbor Trading\nManila Port Warehouse');
            $('input[name="total_payment"]').val('0.00');
            $('textarea[name="description"]').val('Sample billing for shipment-linked services. Includes VATable service charges and reimbursable charges.');
            $('#invoiceTemplate').val(['SOA_B', 'Billing Invoice']).trigger('change.select2');

            $('#invoiceLines tbody tr:gt(0)').remove();
            setLine(0, 'Terminal Handling', 'TERMINAL HANDLING CHARGES', '321714.25', '1', 'MANILA', '12%');
            setLine(1, 'Documentation VAT', 'DOC / IDENTIFICATION FEE', '4280.60', '1', 'MANILA', '12%');
            setLine(2, 'Reimbursable Charges', 'REIMBURSABLE CHARGES - PORT ADVANCES', '753495.10', '1', 'MANILA', 'exempt');

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
            $('#arAccount').val(editInvoice.ar_account || 'A/Trade-Billing Invoice');
            $('#currencySelect').val(editInvoice.currency || 'PHP').trigger('change.select2');
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
            $('input[name="memo"]').val(editInvoice.memo || '');
            $('input[name="invoice_date"]').val(dateValue(editInvoice.invoice_date));
            $('input[name="due_date"]').val(dateValue(editInvoice.due_date));
            $('#invoiceNo').val(editInvoice.invoice_no || '');
            $('#transactionNo').val(editInvoice.transaction_no || editInvoice.invoice_no || '');
            syncInvoiceActionLinks();
            $('textarea[name="invoice_to"]').val(editInvoice.invoice_to || '');
            $('textarea[name="consignee"]').val(editInvoice.consignee || '');
            $('input[name="total_payment"]').val(editInvoice.total_payment || '');
            $('textarea[name="description"]').val(editInvoice.description || '');
            $('#creditTerms').val(String(editInvoice.credit_term_days || '').replace(/[^0-9]/g, ''));
            setTemplateValues(editInvoice.invoice_template || (editInvoice.vat_classification === '12%' ? ['SOA_B', 'Billing Invoice'] : ['SOA_A']));

            $('#invoiceLines tbody tr:gt(0)').remove();
            var items = editInvoice.item || [];
            var descriptions = editInvoice.item_description || [];
            var rates = editInvoice.unit_price || [];
            var quantities = editInvoice.qty || [];
            var classes = editInvoice.line_class || [];
            var taxes = editInvoice.tax_category || [];
            var count = Math.max(items.length, descriptions.length, rates.length, quantities.length, 1);

            for (var i = 0; i < count; i++) {
                setLine(
                    i,
                    items[i] || '',
                    descriptions[i] || '',
                    rates[i] || '',
                    quantities[i] || '1',
                    classes[i] || editInvoice.class_code || '',
                    taxes[i] || editInvoice.vat_classification || ''
                );
            }

            recalc();
            return true;
        }

        $('#customerSelect').on('change', function () {
            syncCustomer();
            recalc();
        });

        $('#currencySelect').on('change', recalc);
        $('#invoiceNo').on('input', function () {
            syncInvoiceNumbers('invoice');
        });
        $('#transactionNo').on('input', function () {
            syncInvoiceNumbers('transaction');
        });
        $('#invoiceForm').on('submit', function () {
            syncInvoiceNumbers($('#invoiceNo').val() ? 'invoice' : 'transaction');
        });
        $('#invoiceLines').on('input change', '.line-unit, .line-qty, .line-tax', recalc);
        $('#invoiceLines').on('change', '.item-select', function () {
            syncItem($(this).closest('tr'));
        });

        $('#addLine').on('click', function () {
            var row = $('#invoiceLines tbody tr:first').clone();
            row.find('select').val('');
            row.find('textarea').val('');
            row.find('.line-unit').val('');
            row.find('.line-qty').val('');
            row.find('.line-total').val('');
            $('#invoiceLines tbody').append(row);
            recalc();
        });

        $('#invoiceLines').on('click', '.remove-line', function () {
            if ($('#invoiceLines tbody tr').length > 1) {
                $(this).closest('tr').remove();
                recalc();
            }
        });

        $('#sampleInvoiceButton').on('click', fillSampleInvoice);

        if (!fillEditInvoice()) {
            syncCustomer();
        }
        recalc();
        syncInvoiceActionLinks();
    });
</script>
@endpush
