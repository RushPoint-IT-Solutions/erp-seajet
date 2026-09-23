@extends('erp.layout')

@section('title', 'Manual A/R Opening Balance')
@section('page-title', 'Manual A/R Opening Balance')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Manual A/R Opening Balance</h1>
            <p class="text-muted mb-0">Add an existing customer receivable without preparing an Excel workbook.</p>
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
        Posting creates an open customer invoice and a balanced journal: debit the selected A/R Trade account and credit Opening Balance Equity. The amount will appear in AR Aging, Client SOA, and Receive Payment.
    </div>

    <form method="post" action="{{ route('erp.accounting.opening-balances.manual.store') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-lg-6">
                <label class="form-label fw-semibold">Client <span class="text-danger">*</span></label>
                <select name="customer_id" class="form-select erp-select" required>
                    <option value="">Select client...</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                            {{ $customer->customer_id ?: $customer->code }} - {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6">
                <label class="form-label fw-semibold">BI / SOA Reference <span class="text-danger">*</span></label>
                <input name="reference_no" class="form-control" value="{{ old('reference_no') }}" maxlength="100" placeholder="Original BI or SOA number" required>
                <div class="form-text">This reference must not already exist in Accounts Receivable.</div>
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
                <label class="form-label fw-semibold">A/R Trade Account <span class="text-danger">*</span></label>
                <select name="target_account_id" class="form-select erp-select" required>
                    <option value="">Select receivable account...</option>
                    @foreach($receivableAccounts as $account)
                        <option value="{{ $account->id }}" {{ (string) old('target_account_id', $defaultReceivableId) === (string) $account->id ? 'selected' : '' }}>{{ $account->label }}</option>
                    @endforeach
                </select>
                <div class="form-text">Choose the Metrobank A/R Trade account when applicable.</div>
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
        function syncCurrency() {
            var currency = $('#manualOpeningCurrency').val() || 'PHP';
            $('#manualOpeningCurrencyLabel').text(currency);
            $('#manualExchangeRateGroup').toggleClass('opacity-50', currency === 'PHP');
        }
        $('#manualOpeningCurrency').on('change', syncCurrency);
        syncCurrency();
    });
</script>
@endpush
