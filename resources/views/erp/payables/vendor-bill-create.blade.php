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
                    <a href="{{ route('erp.expenses.index') }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Delete</a>
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
                                    <label class="form-label small text-muted">Memo</label>
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
                            <th style="width: 230px">Account</th>
                            <th style="width: 120px">Tax</th>
                            <th class="text-end" style="width: 150px">Amount</th>
                            <th>Memo</th>
                            <th style="width: 220px">Customer:Job</th>
                            <th style="width: 100px">Billable?</th>
                            <th style="width: 140px">Class</th>
                            <th class="text-center no-print" style="width: 52px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($lines->count() ? $lines : collect([null]) as $index => $line)
                        <tr>
                            <td>
                                <select name="expense_account[]" class="form-select">
                                    <option value=""></option>
                                    @foreach(['Reimbursable charges', 'Rebates', '5000 - Cost of Services', '5100 - Operating Expenses', '2200 - Input VAT Clearing'] as $account)
                                        <option value="{{ $account }}" {{ ($line && ($line->account_name ?? '') === $account) ? 'selected' : '' }}>{{ $account }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="expense_tax[]" class="form-select">
                                    <option value=""></option>
                                    @foreach(['VAT12', 'ZERO', 'EXEMPT'] as $tax)
                                        <option value="{{ $tax }}" {{ ($line && ($line->tax_category ?? '') === $tax) ? 'selected' : '' }}>{{ $tax }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input name="expense_amount[]" class="form-control text-end expense-amount" value="{{ $line ? number_format((float) ($line->amount ?? $line->line_total ?? 0), 2) : '' }}"></td>
                            <td><input name="expense_memo[]" class="form-control" value="{{ $line ? ($line->memo ?? $line->description ?? '') : '' }}"></td>
                            <td>
                                <select name="expense_customer_job[]" class="form-select line-customer">
                                    <option value=""></option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer['customer_id'] }}" {{ ($line && ($line->customer_job_id ?? '') === $customer['customer_id']) ? 'selected' : '' }}>{{ $customer['customer_id'] }} - {{ $customer['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center"><input name="expense_billable[{{ $index }}]" class="form-check-input expense-billable" type="checkbox" value="Yes" {{ ($line && !empty($line->is_billable)) ? 'checked' : '' }}></td>
                            <td>
                                <select name="expense_class[]" class="form-select">
                                    <option value=""></option>
                                    @foreach(['MANILA', 'PAMPANGA', 'CEBU'] as $class)
                                        <option value="{{ $class }}" {{ ($line && ($line->class_code ?? '') === $class) ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center no-print"><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                        </tr>
                        @empty
                        @endforelse
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
        }

        function recalc() {
            var total = 0;
            $('.expense-amount').each(function () {
                total += numberValue($(this).val());
            });

            if (!total) {
                total = numberValue($('#grossAmount').val());
            } else {
                $('#grossAmount').val(money(total));
            }

            var whtRate = numberValue($('#whtRate').val());
            var whtAmount = total * (whtRate / 100);
            var net = total - whtAmount;
            var php = currency() === 'USD' ? total * numberValue($('#exchangeRate').val()) : total;

            $('#expenseTabTotal').text(currency() + ' ' + money(total));
            $('#whtAmount').val(whtAmount ? money(whtAmount) : '');
            $('#netAmount').val(net ? net.toFixed(2) : '');
            $('#netPayableText').text(currency() + ' ' + money(net));
            $('#phpAmount').val(php ? money(php) : '');
        }

        function reindexRows() {
            $('#expenseLines tbody tr').each(function (index) {
                $(this).find('.expense-billable').attr('name', 'expense_billable[' + index + ']');
            });
        }

        $('#supplierSelect').on('change', function () {
            syncSupplier();
            recalc();
        });
        $('#currencySelect, #exchangeRate, #grossAmount, #whtRate').on('input change', recalc);
        $('#expenseLines').on('input change', '.expense-amount', recalc);
        $('#recalculateBill').on('click', recalc);

        $('#addExpenseLine').on('click', function () {
            var row = $('#expenseLines tbody tr:first').clone();
            row.find('input[type="text"], input:not([type])').val('');
            row.find('input[type="checkbox"]').prop('checked', false);
            row.find('select').val('');
            $('#expenseLines tbody').append(row);
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
        recalc();
    });
</script>
@endpush
