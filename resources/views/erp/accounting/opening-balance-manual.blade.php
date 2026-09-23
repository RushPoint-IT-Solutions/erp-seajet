@extends('erp.layout')

@php($selectedBalanceType = old('balance_type', request('balance_type', 'ar_trade')))

@section('title', 'Manual Trade Opening Balance')
@section('page-title', 'Manual Trade Opening Balance')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Manual Trade Opening Balance</h1>
            <p class="text-muted mb-0">Add an existing customer receivable or vendor payable without preparing an Excel workbook.</p>
        </div>
        <a href="{{ route('erp.payments.create') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Payments</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info">
        <span id="manualOpeningExplanation">Posting creates an open customer invoice and a balanced journal: debit the selected A/R Trade account and credit Opening Balance Equity. The amount will appear in AR Aging, Client SOA, and Receive Payment.</span>
    </div>

    <form method="post" action="{{ route('erp.accounting.opening-balances.manual.store') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold d-block">Balance Type <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-4">
                    <label class="form-check-label"><input class="form-check-input me-1" type="radio" name="balance_type" value="ar_trade" {{ $selectedBalanceType === 'ar_trade' ? 'checked' : '' }}> A/R Trade (Customer)</label>
                    <label class="form-check-label"><input class="form-check-input me-1" type="radio" name="balance_type" value="ap_trade" {{ $selectedBalanceType === 'ap_trade' ? 'checked' : '' }}> A/P Trade (Vendor — BDO / Metro)</label>
                </div>
            </div>
            <div class="col-lg-6" id="manualCustomerGroup">
                <label class="form-label fw-semibold">Client <span class="text-danger">*</span></label>
                <select name="customer_id" id="manualCustomer" class="form-select erp-select">
                    <option value="">Select client...</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                            {{ $customer->customer_id ?: $customer->code }} - {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6 d-none" id="manualSupplierGroup">
                <label class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                <select name="supplier_id" id="manualSupplier" class="form-select erp-select">
                    <option value="">Select vendor...</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->code }} - {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6">
                <label class="form-label fw-semibold"><span id="manualReferenceLabel">BI / SOA Reference</span> <span class="text-danger">*</span></label>
                <input name="reference_no" id="manualReference" class="form-control" value="{{ old('reference_no') }}" maxlength="100" placeholder="Original BI or SOA number" required>
                <div class="form-text" id="manualReferenceHelp">This reference must not already exist in Accounts Receivable.</div>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label fw-semibold">Invoice Date <span class="text-danger">*</span></label>
                <input name="source_date" type="date" class="form-control" value="{{ old('source_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                <input name="due_date" type="date" class="form-control" value="{{ old('due_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label fw-semibold">Branch / Class <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select" required>
                    <option value="">Select branch...</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->code }} - {{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
                <select name="currency" class="form-select" id="manualOpeningCurrency" required>
                    <option value="PHP" {{ old('currency', 'PHP') === 'PHP' ? 'selected' : '' }}>PHP</option>
                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold"><span id="manualTradeAccountLabel">A/R Trade Account</span> <span class="text-danger">*</span></label>
                <select name="target_account_id" id="manualTradeAccount" class="form-select erp-select" required>
                    <option value="">Select trade account...</option>
                    <optgroup label="A/R Trade" data-balance-type="ar_trade">
                        @foreach($receivableAccounts as $account)
                            <option value="{{ $account->id }}" data-balance-type="ar_trade" {{ (string) old('target_account_id', $defaultReceivableId) === (string) $account->id ? 'selected' : '' }}>{{ $account->label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="A/P Trade — BDO / Metro" data-balance-type="ap_trade">
                        @foreach($payableAccounts as $account)
                            <option value="{{ $account->id }}" data-balance-type="ap_trade" {{ (string) old('target_account_id') === (string) $account->id ? 'selected' : '' }}>{{ $account->label }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <div class="form-text" id="manualTradeAccountHelp">Choose the Metrobank A/R Trade account when applicable.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Opening Balance Equity <span class="text-danger">*</span></label>
                <select name="opening_balance_account_id" class="form-select erp-select" required>
                    <option value="">Select offset account...</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ (string) old('opening_balance_account_id', $defaultOpeningAccountId) === (string) $account->id ? 'selected' : '' }}>{{ $account->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Opening Amount <span class="text-danger">*</span></label>
                <div class="input-group"><span class="input-group-text" id="manualOpeningCurrencyLabel">PHP</span><input name="amount" type="number" min="0.01" step="0.01" class="form-control text-end" value="{{ old('amount') }}" placeholder="0.00" required></div>
            </div>
            <div class="col-md-6" id="manualExchangeRateGroup">
                <label class="form-label fw-semibold">Exchange Rate to PHP</label>
                <input name="exchange_rate" type="number" min="0.000001" step="0.000001" class="form-control text-end" value="{{ old('exchange_rate', '1') }}">
                <div class="form-text">Required for USD; PHP entries use 1.00.</div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.payments.create') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" onclick="return confirm('Post this manual A/R opening balance?')"><i class="bi bi-journal-check me-1"></i> Post Opening Balance</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    $(function () {
        var defaultAccounts = {
            ar_trade: @json((string) $defaultReceivableId),
            ap_trade: @json((string) $defaultPayableId)
        };

        function syncBalanceType() {
            var type = $('input[name="balance_type"]:checked').val() || 'ar_trade';
            var isPayable = type === 'ap_trade';
            $('#manualCustomerGroup').toggleClass('d-none', isPayable);
            $('#manualSupplierGroup').toggleClass('d-none', !isPayable);
            $('#manualCustomer').prop('disabled', isPayable).prop('required', !isPayable);
            $('#manualSupplier').prop('disabled', !isPayable).prop('required', isPayable);

            var account = $('#manualTradeAccount');
            account.find('option[data-balance-type]').each(function () {
                $(this).prop('disabled', $(this).data('balance-type') !== type);
            });
            account.find('optgroup[data-balance-type]').each(function () {
                $(this).prop('disabled', $(this).data('balance-type') !== type);
            });
            if (!account.find('option:selected').is(':enabled') || !account.val()) {
                account.val(defaultAccounts[type] || '').trigger('change.select2');
            } else {
                account.trigger('change.select2');
            }

            $('#manualTradeAccountLabel').text(isPayable ? 'A/P Trade Account' : 'A/R Trade Account');
            $('#manualTradeAccountHelp').text(isPayable
                ? 'Choose the BDO or Metro A/P Trade account for this vendor balance.'
                : 'Choose the Metrobank A/R Trade account when applicable.');
            $('#manualReferenceLabel').text(isPayable ? 'Vendor Bill Reference' : 'BI / SOA Reference');
            $('#manualReference').attr('placeholder', isPayable ? 'Original vendor bill number' : 'Original BI or SOA number');
            $('#manualReferenceHelp').text(isPayable
                ? 'This reference must not already exist in Accounts Payable.'
                : 'This reference must not already exist in Accounts Receivable.');
            $('#manualOpeningExplanation').text(isPayable
                ? 'Posting creates an open vendor bill and a balanced journal: debit Opening Balance Equity and credit the selected A/P Trade account. The amount will appear in AP Aging and Payment Processing.'
                : 'Posting creates an open customer invoice and a balanced journal: debit the selected A/R Trade account and credit Opening Balance Equity. The amount will appear in AR Aging, Client SOA, and Receive Payment.');
        }

        function syncCurrency() {
            var currency = $('#manualOpeningCurrency').val() || 'PHP';
            $('#manualOpeningCurrencyLabel').text(currency);
            $('#manualExchangeRateGroup').toggleClass('opacity-50', currency === 'PHP');
        }
        $('input[name="balance_type"]').on('change', syncBalanceType);
        $('#manualOpeningCurrency').on('change', syncCurrency);
        syncBalanceType();
        syncCurrency();
    });
</script>
@endpush
