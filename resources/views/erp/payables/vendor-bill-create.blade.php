@extends('erp.layout')

@section('title', 'Enter Bill')
@section('page-title', 'Enter Bill')

@push('styles')
<style>
    .bill-workspace {
        background: #eef0f3;
        margin: -1.5rem;
        padding: 1.5rem;
        min-height: calc(100vh - 74px);
    }
    .bill-toolbar {
        background: #f7f7f4;
        border: 1px solid #d7dce5;
        border-radius: 4px;
        padding: 10px 12px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .06);
    }
    .bill-toolbar .btn {
        min-width: 74px;
    }
    .bill-shell {
        background: #fff;
        border: 1px solid #b6c2d2;
        border-radius: 4px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
    }
    .bill-brand-header {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) 120px;
        align-items: center;
        gap: 18px;
        border: 1px solid #b6c2d2;
        border-radius: 4px;
        background: #fff;
        padding: 14px 18px;
        margin-bottom: 14px;
    }
    .bill-brand-logo {
        width: 112px;
        height: 58px;
        object-fit: contain;
    }
    .bill-company {
        text-align: center;
        color: #111827;
        line-height: 1.2;
    }
    .bill-company-name {
        font-size: 18px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .bill-company-meta {
        font-size: 11px;
        font-weight: 600;
        color: #4b5563;
    }
    .bill-document-title {
        text-align: right;
        color: #991b1b;
        font-size: 22px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .bill-head {
        background: #eef4ff;
        border: 1px solid #b6c2d2;
        border-radius: 4px;
        padding: 16px;
    }
    .bill-title {
        font-size: 44px;
        font-weight: 300;
        color: #485766;
        line-height: 1;
    }
    .bill-field {
        border: 1px solid #c9d1dc;
        border-radius: 2px;
        min-height: 34px;
        background: #fff;
    }
    .bill-field:focus {
        box-shadow: none;
        border-color: #fe790d;
    }
    .bill-lines thead th {
        background: #f4f5f7;
        color: #667085;
        font-size: 12px;
        text-transform: uppercase;
        border-color: #d8dde6;
    }
    .bill-lines td {
        padding: 4px;
    }
    .bill-lines input,
    .bill-lines select {
        border: 0;
        border-bottom: 1px solid #cbd5e1;
        border-radius: 0;
        background: transparent;
    }
    .bill-tabs .nav-link {
        border-radius: 0;
        color: #475569;
        font-weight: 700;
    }
    .bill-tabs .nav-link.active {
        color: #fe790d;
        border-bottom: 2px solid #fe790d;
    }
</style>
@endpush

@section('content')
    <div class="bill-workspace">
        @php($bill = $bill ?? [])
        @php($lines = collect($lines ?? []))
        @php($attachments = collect($attachments ?? []))
        @php($vat12Selected = collect(old('expense_tax', $lines->pluck('tax_category')->all()))->contains('VAT12'))
        @php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the highlighted fields.</div>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="post" action="{{ route('erp.payables.vendor-bills.review') }}" id="billForm" enctype="multipart/form-data">
            {{ csrf_field() }}
            <input type="hidden" name="original_bill_no" value="{{ $bill['bill_no'] ?? '' }}">

            <div class="bill-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 no-print">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('erp.expenses.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search me-1"></i>Find</a>
                    <a href="{{ route('erp.payables.vendor-bills.create') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i>New</a>
                    <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-save me-1"></i>Save</button>
                    @if(!empty($bill['id']))
                        <button type="submit" form="deleteVendorBillForm" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                    @else
                        <a href="{{ route('erp.expenses.index') }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Cancel</a>
                    @endif
                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                    <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-paperclip me-1"></i>Attach File<input type="file" name="attachments[]" multiple class="d-none"></label>
                    <a href="{{ route('erp.suppliers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-building me-1"></i>Select Vendor</a>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="recalculateBill"><i class="bi bi-calculator me-1"></i>Recalculate</button>
                    <a href="{{ route('erp.payables.payments.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-cash-stack me-1"></i>Pay Bill</a>
                </div>
                <a href="{{ route('erp.suppliers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-building me-1"></i>Vendors</a>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-4 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <label class="form-check-label"><input class="form-check-input me-1" type="radio" name="transaction_type" value="Bill" {{ ($bill['transaction_type'] ?? 'Bill') === 'Bill' ? 'checked' : '' }}> Bill</label>
                    <label class="form-check-label"><input class="form-check-input me-1" type="radio" name="transaction_type" value="Credit" {{ ($bill['transaction_type'] ?? 'Bill') === 'Credit' ? 'checked' : '' }}> Credit</label>
                </div>
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 460px">
                    <label class="fw-semibold small text-muted text-nowrap">A/P Account</label>
                    <select name="ap_account" class="form-select form-select-sm bill-field">
                        <option value=""></option>
                        @foreach(['AP-Trade (BDO - USD)', 'Accounts Payable Trade - PHP', 'Operating Expense Payable'] as $account)
                            <option value="{{ $account }}" {{ ($bill['ap_account'] ?? '') === $account ? 'selected' : '' }}>{{ $account }}</option>
                        @endforeach
                    </select>
                </div>
                    <label class="form-check-label ms-auto"><input class="form-check-input me-1" type="checkbox" name="bill_received" value="Yes" {{ ($bill['bill_received'] ?? '') === 'Yes' ? 'checked' : '' }}> Bill Received</label>
            </div>

            <div class="bill-shell p-3">
                <div class="bill-brand-header">
                    <img src="{{ asset('images/gen_off_seajetlogo.webp') }}" class="bill-brand-logo" alt="SeaJet logo">
                    <div class="bill-company">
                        <div class="bill-company-name">Sea-Jet International Forwarders, Inc.</div>
                        <div class="bill-company-meta">Unit 18 & 19 Bldg. B, Cargo Village Complex</div>
                        <div class="bill-company-meta">Paranaque City 1704, Philippines</div>
                    </div>
                    <div class="bill-document-title">Vendor Bill</div>
                </div>
                <div class="bill-head mb-3">
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-4">
                            <div class="bill-title mb-3">Bill</div>
                            <div class="mb-2">
                                <label class="form-label small text-muted">Vendor</label>
                                <select name="supplier_id" id="supplierSelect" class="form-select bill-field erp-select" required>
                                    <option value="">Select vendor...</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier['supplier_id'] }}"
                                                data-name="{{ $supplier['name'] }}"
                                                data-address="{{ $supplier['address'] }}"
                                                data-terms="{{ $supplier['terms'] }}"
                                                data-currency="{{ $supplier['currency'] }}"
                                                data-wht="{{ $supplier['withholding_tax'] }}"
                                                data-expense-account="{{ $supplier['expense_account'] }}"
                                                {{ ($bill['supplier_id'] ?? '') === $supplier['supplier_id'] ? 'selected' : '' }}>
                                            {{ $supplier['supplier_id'] }} - {{ $supplier['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="supplier_name" id="supplierName" value="{{ $bill['supplier_name'] ?? '' }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted">Address</label>
                                <textarea name="supplier_address" id="supplierAddress" class="form-control bill-field" rows="4">{{ $bill['supplier_address'] ?? '' }}</textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-5">
                                    <label class="form-label small text-muted">Terms</label>
                                    <select name="terms" id="termsSelect" class="form-select bill-field">
                                        <option value=""></option>
                                        @foreach(['30 Days', '60 Days', '90 Days'] as $term)
                                            <option {{ ($bill['terms'] ?? '') === $term ? 'selected' : '' }}>{{ $term }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-7">
                                    <label class="form-label small text-muted">Bill No <span class="fw-normal">(Automatic)</span></label>
                                    <input name="bill_no" class="form-control bill-field bg-light" value="{{ $bill['bill_no'] ?? $nextBillNo }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Date</label>
                                    <input name="bill_date" type="date" class="form-control bill-field" value="{{ $bill['bill_date'] ?? date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Ref. No</label>
                                    <input name="reference_no" class="form-control bill-field" value="{{ $bill['reference_no'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Currency</label>
                                    <select name="currency" id="currencySelect" class="form-select bill-field">
                                        <option {{ ($bill['currency'] ?? 'PHP') === 'USD' ? 'selected' : '' }}>USD</option>
                                        <option {{ ($bill['currency'] ?? 'PHP') === 'PHP' ? 'selected' : '' }}>PHP</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Amount Due</label>
                                    <input name="gross_amount" id="grossAmount" class="form-control bill-field text-end" value="{{ $bill['gross_amount'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">PHP Amount</label>
                                    <input name="php_amount" id="phpAmount" class="form-control bill-field text-end" value="{{ $bill['php_amount'] ?? '' }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Bill Due</label>
                                    <input name="due_date" type="date" class="form-control bill-field" value="{{ $bill['due_date'] ?? '' }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-muted">Bill Memo</label>
                                    <input name="memo" class="form-control bill-field" value="{{ $bill['memo'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Shipment Job ID</label>
                                    <input name="shipment_job_id" class="form-control bill-field" value="{{ $bill['shipment_job_id'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Client Tag</label>
                                    <select name="client_customer_id" class="form-select bill-field erp-select">
                                        <option value=""></option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer['customer_id'] }}" {{ ($bill['client_customer_id'] ?? '') === $customer['customer_id'] ? 'selected' : '' }}>{{ $customer['customer_id'] }} - {{ $customer['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="erp-card p-3 bg-white">
                                <div class="small text-muted">Net Payable</div>
                                <div class="h4 fw-bold text-primary" id="netPayableText">USD 0.00</div>
                                <div class="row g-2 mt-2 align-items-end">
                                    <div class="col-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="vat12Toggle" {{ $vat12Selected ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="vat12Toggle">VAT 12%</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted">Input VAT</label>
                                        <input id="inputVatAmount" class="form-control form-control-sm bill-field text-end" value="{{ $bill['input_vat'] ?? '' }}" readonly>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted">WHT %</label>
                                        <select name="withholding_tax_rate" id="whtRate" class="form-select form-select-sm bill-field">
                                            @foreach([0, 2, 5, 10] as $rate)
                                                <option value="{{ $rate }}" {{ (float)($bill['withholding_tax_rate'] ?? 0) === (float)$rate ? 'selected' : '' }}>{{ $rate }}%</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted">WHT Amt</label>
                                        <input name="withholding_tax_amount" id="whtAmount" class="form-control form-control-sm bill-field text-end" value="{{ $bill['withholding_tax_amount'] ?? '' }}" readonly>
                                    </div>
                                </div>
                                <input type="hidden" name="net_amount" id="netAmount" value="{{ $bill['net_amount'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav bill-tabs mb-2" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" type="button">Expenses <span class="ms-2" id="expenseTabTotal">USD 0.00</span></button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" type="button">Items <span class="ms-2">USD 0.00</span></button></li>
                </ul>

                <div class="table-responsive">
                    <table class="table bill-lines align-middle" id="expenseLines">
                        <thead>
                        <tr>
                            <th style="width: 220px">Account</th>
                            <th style="width: 130px">Tax</th>
                            <th style="width: 100px">ATC / WC</th>
                            <th style="width: 90px">WHT %</th>
                            <th class="text-end" style="width: 150px">Amount</th>
                            <th style="width: 220px">Customer:Job</th>
                            <th style="width: 100px">Billable?</th>
                            <th style="width: 140px">Class</th>
                            <th class="text-center no-print" style="width: 52px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $billLines = $lines->count() ? $lines : collect([null]);
                            $expenseAccountLabels = $expenseAccounts->pluck('label')->all();
                        ?>
                        @foreach($billLines as $index => $line)
                        <tr>
                            <td>
                                <select name="expense_account[]" class="form-select expense-account expense-account-search">
                                    <option value=""></option>
                                    <?php
                                        $lineAccount = $line ? ($line->account_name ?? '') : '';
                                        $lineAccountInOptions = in_array($lineAccount, $expenseAccountLabels, true);
                                        $lineAccountProfile = $expenseAccounts->first(function ($account) use ($lineAccount) {
                                            return $account->label === $lineAccount;
                                        });
                                        $lineAtcCode = $line
                                            ? (($line->atc_code ?? '') ?: ($line->account_atc_code ?? '') ?: ($lineAccountProfile->atc_code ?? ''))
                                            : '';
                                        $savedLineRate = $line ? (float) ($line->withholding_tax_rate ?? 0) : 0;
                                        $lineWithholdingRate = $savedLineRate > 0
                                            ? $savedLineRate
                                            : (float) ($lineAccountProfile->withholding_rate ?? 0);
                                        $lineTaxCategory = strtoupper((string) ($line->tax_category ?? ''));
                                        $lineAmount = $line ? (float) ($line->amount ?? $line->line_total ?? 0) : 0;
                                        if ($line && !($bill['line_amounts_exclude_vat'] ?? true) && $lineTaxCategory === 'VAT12') {
                                            $lineAmount /= 1.12;
                                        }
                                        $lineItemMemo = $line ? ($line->memo ?? $line->description ?? '') : '';
                                        $lineClassCode = strtoupper(trim((string) ($line->class_code ?? ''))) ?: 'MANILA';
                                    ?>
                                    @if($lineAccount && !$lineAccountInOptions)
                                        <option value="{{ $lineAccount }}" selected>{{ $lineAccount }}</option>
                                    @endif
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->label }}"
                                                data-atc="{{ $account->atc_code }}"
                                                data-wht-rate="{{ $account->withholding_rate }}"
                                                {{ ($line && ($line->account_name ?? '') === $account->label) ? 'selected' : '' }}>{{ $account->label }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="expense_memo[]" class="expense-item-memo" value="{{ $lineItemMemo }}">
                            </td>
                            <td>
                                <label class="form-check d-flex align-items-center gap-1 mb-1 text-nowrap">
                                    <input class="form-check-input line-vatable mt-0" type="checkbox" {{ $lineTaxCategory === 'VAT12' ? 'checked' : '' }}>
                                    <span class="small fw-semibold">VAT 12%</span>
                                </label>
                                <select name="expense_tax[]" class="form-select form-select-sm line-tax-category" aria-label="Expense line tax classification">
                                    <option value=""></option>
                                    @foreach(['VAT12', 'ZERO', 'EXEMPT'] as $tax)
                                        <option value="{{ $tax }}" {{ $lineTaxCategory === $tax ? 'selected' : '' }}>{{ $tax }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input name="expense_atc_code[]" class="form-control text-uppercase line-atc-code" maxlength="20" value="{{ $lineAtcCode }}" placeholder="WC160"></td>
                            <td><input name="expense_withholding_rate[]" class="form-control text-end line-wht-rate" type="number" min="0" max="100" step="0.01" value="{{ $lineWithholdingRate ?: '' }}"></td>
                            <td><input name="expense_amount[]" class="form-control text-end expense-amount" value="{{ $line ? number_format($lineAmount, 2) : '' }}"></td>
                            <td>
                                <select name="expense_customer_job[]" class="form-select line-customer customer-job-search">
                                    <option value=""></option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer['customer_id'] }}" {{ ($line && ($line->customer_job_id ?? '') === $customer['customer_id']) ? 'selected' : '' }}>{{ $customer['customer_id'] }} - {{ $customer['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center"><input name="expense_billable[{{ $index }}]" class="form-check-input expense-billable" type="checkbox" value="Yes" {{ ($line && !empty($line->is_billable)) ? 'checked' : '' }}></td>
                            <td>
                                <select name="expense_class[]" class="form-select expense-class">
                                    <option value=""></option>
                                    @foreach(['MANILA', 'PAMPANGA', 'CEBU'] as $class)
                                        <option value="{{ $class }}" {{ $lineClassCode === $class ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center no-print"><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm no-print" id="addExpenseLine"><i class="bi bi-plus-circle me-1"></i>Add Expense Line</button>

                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-4">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 small text-muted">Exchange Rate 1 USD =</label>
                        <input name="exchange_rate" id="exchangeRate" class="form-control form-control-sm bill-field text-end" style="width: 110px" value="{{ $bill['exchange_rate'] ?? '61.393' }}">
                        <span class="badge text-bg-light">PHP</span>
                    </div>
                    <div class="d-flex gap-2 no-print">
                        <a href="{{ route('erp.expenses.index') }}" class="btn btn-outline-secondary">Clear</a>
                        <button class="btn btn-outline-secondary" name="save_mode" value="close"><i class="bi bi-save me-1"></i>Save & Close</button>
                        <button class="btn btn-primary" name="save_mode" value="new"><i class="bi bi-save2 me-1"></i>Save & New</button>
                        @if(in_array(strtolower(auth()->user()->role ?? ''), ['administrator', 'admin', 'finance'], true))
                            <button class="btn btn-success" name="save_mode" value="post"><i class="bi bi-journal-check me-1"></i>Approve & Post</button>
                        @endif
                    </div>
                </div>
                @if($attachments->count())
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach($attachments as $attachment)
                            <span class="badge badge-soft py-2 px-3"><i class="bi bi-paperclip me-1"></i>{{ $attachment->original_name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </form>
        @if(!empty($bill['id']))
            <form method="post" action="{{ route('erp.payables.vendor-bills.delete', $bill['id']) }}" id="deleteVendorBillForm" class="d-none" onsubmit="return confirm('Delete this vendor bill? This cannot be undone.');">
                {{ csrf_field() }}
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        function numberValue(value) {
            var parsed = parseFloat(String(value || '').replace(/,/g, ''));
            return isNaN(parsed) ? 0 : parsed;
        }

        function money(value) {
            return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function currency() {
            return $('#currencySelect').val() || 'USD';
        }

        function initExpenseAccountSearch(scope) {
            $(scope || document).find('.expense-account-search').each(function () {
                var select = $(this);
                if (select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                select.select2({
                    width: 'resolve',
                    placeholder: 'Search account',
                    allowClear: true
                });
            });
        }

        function initCustomerJobSearch(scope) {
            $(scope || document).find('.customer-job-search').each(function () {
                var select = $(this);
                if (select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                select.select2({
                    width: 'resolve',
                    placeholder: 'Search customer',
                    allowClear: true
                });
            });
        }

        function syncSupplier() {
            var option = $('#supplierSelect option:selected');
            if (!option.val()) {
                $('#supplierName').val('');
                $('#supplierAddress').val('');
                return;
            }

            $('#supplierName').val(option.data('name') || '');
            $('#supplierAddress').val(option.data('address') || '');
            $('#termsSelect').val(option.data('terms') || '');
            $('#currencySelect').val(option.data('currency') || currency());
            var expenseAccount = option.data('expense-account') || '';
            if (expenseAccount) {
                var firstAccount = $('#expenseLines tbody tr:first .expense-account');
                if (!firstAccount.val()) {
                    firstAccount.val(expenseAccount).trigger('change');
                }
            }
        }

        function recalc() {
            var expenseSubtotal = 0;
            var inputVat = 0;
            var lineWithholding = 0;
            var hasLineWithholding = false;
            $('#expenseLines tbody tr').each(function () {
                var row = $(this);
                var amount = numberValue(row.find('.expense-amount').val());
                expenseSubtotal += amount;

                var category = String(row.find('select[name="expense_tax[]"]').val() || '').toUpperCase();
                row.find('.line-vatable').prop('checked', category === 'VAT12');
                if (category === 'VAT12') {
                    inputVat += amount * 0.12;
                }

                var lineRate = numberValue(row.find('.line-wht-rate').val());
                if (lineRate > 0) {
                    hasLineWithholding = true;
                    lineWithholding += amount * (lineRate / 100);
                }
            });

            var billTotal = expenseSubtotal + inputVat;
            if (!expenseSubtotal) {
                billTotal = numberValue($('#grossAmount').val());
                expenseSubtotal = Math.max(0, billTotal - inputVat);
            } else {
                $('#grossAmount').val(money(billTotal));
            }

            var whtRate = numberValue($('#whtRate').val());
            // BIR EWT is computed on the income payment net of VAT, not the VAT-inclusive gross.
            var whtBase = expenseSubtotal;
            var whtAmount = hasLineWithholding ? lineWithholding : whtBase * (whtRate / 100);
            var net = billTotal - whtAmount;
            var php = currency() === 'USD' ? billTotal * numberValue($('#exchangeRate').val()) : billTotal;

            $('#expenseTabTotal').text(currency() + ' ' + money(expenseSubtotal));
            $('#inputVatAmount').val(inputVat ? money(inputVat) : '');
            $('#whtAmount').val(whtAmount ? money(whtAmount) : '');
            $('#netAmount').val(net ? net.toFixed(2) : '');
            $('#netPayableText').text(currency() + ' ' + money(net));
            $('#phpAmount').val(php ? money(php) : '');
            syncVat12Toggle();
        }

        function syncVat12Toggle() {
            var activeCount = 0;
            var vatCount = 0;

            $('#expenseLines tbody tr').each(function () {
                var row = $(this);
                var category = String(row.find('select[name="expense_tax[]"]').val() || '').toUpperCase();
                var isActive = category || row.find('.expense-account').val() || numberValue(row.find('.expense-amount').val()) > 0;
                if (!isActive) {
                    return;
                }
                activeCount++;
                if (category === 'VAT12') {
                    vatCount++;
                }
            });

            $('#vat12Toggle')
                .prop('checked', activeCount > 0 && vatCount === activeCount)
                .prop('indeterminate', vatCount > 0 && vatCount < activeCount);
        }

        function reindexRows() {
            $('#expenseLines tbody tr').each(function (index) {
                $(this).find('.expense-billable').attr('name', 'expense_billable[' + index + ']');
            });
        }

        function syncLineWithholding(row) {
            var option = row.find('.expense-account option:selected');
            row.find('.line-atc-code').val(option.data('atc') || '');
            row.find('.line-wht-rate').val(option.data('wht-rate') || '');
        }

        $('#supplierSelect').on('change', function () {
            syncSupplier();
            recalc();
        });
        $('#currencySelect, #exchangeRate, #grossAmount, #whtRate').on('input change', recalc);
        $('#expenseLines').on('change', '.expense-account', function () {
            syncLineWithholding($(this).closest('tr'));
            recalc();
        });
        $('#expenseLines').on('change', '.line-vatable', function () {
            var taxSelect = $(this).closest('tr').find('select[name="expense_tax[]"]');
            if (this.checked) {
                taxSelect.val('VAT12');
            } else if (String(taxSelect.val() || '').toUpperCase() === 'VAT12') {
                taxSelect.val('');
            }
            recalc();
        });
        $('#expenseLines').on('input change', '.expense-amount, .line-atc-code, .line-wht-rate, select[name="expense_tax[]"]', recalc);
        $('#vat12Toggle').on('change', function () {
            var checked = this.checked;
            $('#expenseLines select[name="expense_tax[]"]').each(function () {
                var category = String($(this).val() || '').toUpperCase();
                if (checked && category !== 'ZERO' && category !== 'EXEMPT') {
                    $(this).val('VAT12');
                } else if (!checked && category === 'VAT12') {
                    $(this).val('');
                }
            });
            recalc();
        });
        $('#recalculateBill').on('click', recalc);

        $('#addExpenseLine').on('click', function () {
            var source = $('#expenseLines tbody tr:first');
            source.find('.expense-account-search.select2-hidden-accessible').select2('destroy');
            source.find('.customer-job-search.select2-hidden-accessible').select2('destroy');
            source.find('.select2-container').remove();

            var row = source.clone();
            row.find('input[type="text"], input:not([type])').val('');
            row.find('.expense-item-memo').val('');
            row.find('input[type="checkbox"]').prop('checked', false);
            row.find('select').val('');
            row.find('.expense-class').val('MANILA');
            if ($('#vat12Toggle').prop('checked')) {
                row.find('select[name="expense_tax[]"]').val('VAT12');
            }
            $('#expenseLines tbody').append(row);
            initExpenseAccountSearch(source);
            initCustomerJobSearch(source);
            initExpenseAccountSearch(row);
            initCustomerJobSearch(row);
            reindexRows();
        });

        $('#expenseLines').on('click', '.remove-line', function () {
            if ($('#expenseLines tbody tr').length > 1) {
                $(this).closest('tr').remove();
                reindexRows();
                recalc();
            }
        });

        reindexRows();
        initExpenseAccountSearch(document);
        initCustomerJobSearch(document);
        recalc();
    });
</script>
@endpush
