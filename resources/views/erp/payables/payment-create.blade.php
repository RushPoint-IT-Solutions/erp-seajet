@extends('erp.layout')

@section('title', 'AP Payment Processing')
@section('page-title', 'AP Payment Processing')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Payment Processing</h1>
            <p class="text-muted mb-0">Process approved supplier bills by bank, cheque, or online transfer in PHP or USD.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.accounting.opening-balances.manual', ['balance_type' => 'ap_trade']) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i> Manual A/P Opening Balance</a>
            <a href="{{ route('erp.accounting.opening-balances.import') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i> Import Bills</a>
            <a href="{{ route('erp.payables.approvals') }}" class="btn btn-outline-secondary"><i class="bi bi-check2-square me-1"></i> Approvals</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-info"><i class="bi bi-info-circle-fill me-1"></i>{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert" id="paymentCreationErrors">
            <div class="fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Check Preparation was not created. Please correct the following:</div>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php($selectedBill = $selectedBill ?? null)
    @php($selectedPaymentMethod = $selectedPaymentMethod ?? 'Bank')
    <form class="erp-card p-4" method="post" action="{{ route('erp.payables.payments.review') }}" enctype="multipart/form-data" novalidate>
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">CV No</label>
                <input id="checkVoucherNumber" class="form-control bg-light" value="" placeholder="Select a bank account" readonly>
                <input name="payment_no" type="hidden" value="{{ $nextPaymentNo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Payment Date</label>
                <input name="payment_date" type="date" class="form-control" value="{{ old('payment_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Bill No</label>
                <select name="bill_no" id="billSelect" class="form-select erp-select" required>
                    <option value="">Select bill...</option>
                    @foreach($openBills as $bill)
                        <option value="{{ $bill['bill_no'] }}"
                                data-supplier-id="{{ $bill['supplier_id'] }}"
                                data-supplier-name="{{ $bill['supplier_name'] }}"
                                data-job="{{ $bill['shipment_job_id'] }}"
                                data-currency="{{ $bill['currency'] }}"
                                data-amount="{{ number_format($bill['balance'], 2, '.', '') }}"
                                {{ old('bill_no', $selectedBill['bill_no'] ?? '') === $bill['bill_no'] ? 'selected' : '' }}>
                            {{ $bill['bill_no'] }} - {{ $bill['supplier_name'] }} - {{ $bill['balance_display'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Shipment Job ID</label>
                <input name="shipment_job_id" id="shipmentJobId" class="form-control" value="{{ old('shipment_job_id', $selectedBill['shipment_job_id'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Supplier ID</label>
                <select name="supplier_id" id="supplierId" class="form-select erp-select">
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier['supplier_id'] }}" {{ old('supplier_id', $selectedBill['supplier_id'] ?? '') === $supplier['supplier_id'] ? 'selected' : '' }}>{{ $supplier['supplier_id'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Supplier Name</label>
                <select name="supplier_name" id="supplierName" class="form-select erp-select">
                    @foreach($suppliers as $supplier)
                        <option {{ old('supplier_name', $selectedBill['supplier_name'] ?? '') === $supplier['name'] ? 'selected' : '' }}>{{ $supplier['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Payee Name</label>
                <input name="payee_name" id="payeeName" class="form-control" value="{{ old('payee_name', $selectedBill['supplier_name'] ?? '') }}" maxlength="255" required>
                <div class="form-text">Editable when the check must be issued to a name other than the supplier.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" id="paymentMethod" class="form-select erp-select">
                    @foreach(['Bank', 'Cheque', 'Online Transfer'] as $method)
                        <option value="{{ $method }}" {{ old('payment_method', $selectedPaymentMethod) === $method ? 'selected' : '' }}>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Bank Account</label>
                <select name="bank_account" id="bankAccount" class="form-select erp-select" required>
                    <option value="">Select account...</option>
                    @foreach($bankAccounts as $bank)
                        <option value="{{ $bank['account_no'] }}" data-next-check-no="{{ $bank['next_check_no'] ?? '000001' }}" data-next-voucher-no="{{ $bank['next_voucher_no'] ?? '' }}" {{ old('bank_account') === $bank['account_no'] ? 'selected' : '' }}>{{ $bank['bank'] }} - {{ $bank['name'] }} ({{ $bank['currency'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Check No</label>
                <input name="check_no" id="checkNumber" class="form-control" value="{{ old('check_no') }}" {{ old('payment_method', $selectedPaymentMethod) === 'Cheque' ? 'required' : '' }}>
                <div class="form-text">The next number is assigned automatically from this bank account's last check number.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency</label>
                <select name="currency" id="currencySelect" class="form-select erp-select"><option {{ old('currency', $selectedBill['currency'] ?? 'PHP') === 'PHP' ? 'selected' : '' }}>PHP</option><option {{ old('currency', $selectedBill['currency'] ?? 'PHP') === 'USD' ? 'selected' : '' }}>USD</option></select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount</label>
                <input name="amount" id="amountInput" class="form-control" value="{{ old('amount', $selectedBill ? str_replace(',', '', $selectedBill['net_amount']) : '') }}" required>
            </div>
            <div class="col-md-3" id="exchangeRateGroup">
                <label class="form-label">USD to PHP Rate</label>
                <input name="exchange_rate" id="exchangeRateInput" type="number" min="0.000001" step="0.000001" class="form-control" value="{{ old('exchange_rate', '1') }}">
                <div class="form-text">Required for USD payments; PHP payments use 1.000000.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Workflow Status</label>
                <input class="form-control" value="For Approval" readonly>
                <div class="form-text">The preparer cannot approve or release this payment.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Attach Documents</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.modules.show', 'payables') }}" class="btn btn-outline-secondary">Cancel</a>
            <a href="{{ route('erp.payables.payments.create', ['method' => $selectedPaymentMethod]) }}" class="btn btn-outline-secondary" onclick="return confirm('Clear all unsaved CV and payment values?')"><i class="bi bi-arrow-counterclockwise me-1"></i>Clear Form</a>
            <button class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i> Review Payment</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    $(function () {
        var hasOldInput = @json(session()->hasOldInput());

        function syncBill() {
            var option = $('#billSelect option:selected');
            if (!option.val()) {
                return;
            }

            $('#shipmentJobId').val(option.data('job') || '');
            $('#supplierId').val(option.data('supplier-id') || '');
            $('#supplierName').val(option.data('supplier-name') || '');
            $('#payeeName').val(option.data('supplier-name') || '');
            $('#currencySelect').val(option.data('currency') || 'PHP');
            $('#amountInput').val(option.data('amount') || '');
            syncCurrency();
        }

        function syncCurrency() {
            var isPhp = $('#currencySelect').val() === 'PHP';
            $('#exchangeRateInput').prop('readonly', isPhp);
            if (isPhp) {
                $('#exchangeRateInput').val('1');
            }
        }

        function syncCheckRequirements(assignNextNumber) {
            var isCheque = $('#paymentMethod').val() === 'Cheque';
            var nextCheckNo = $('#bankAccount option:selected').data('next-check-no') || '';
            var checkNumber = $('#checkNumber').prop('required', isCheque);
            if (!isCheque) {
                checkNumber.val('');
            } else if (assignNextNumber || !checkNumber.val()) {
                checkNumber.val(nextCheckNo);
            }
        }

        function syncVoucherNumber() {
            var voucherNo = $('#bankAccount option:selected').data('next-voucher-no') || '';
            $('#checkVoucherNumber').val(voucherNo);
        }

        $('#billSelect').on('change', syncBill);
        $('#currencySelect').on('change', syncCurrency);
        $('#paymentMethod').on('change', function () { syncCheckRequirements(true); });
        $('#bankAccount').on('change', function () {
            syncCheckRequirements(true);
            syncVoucherNumber();
        });
        if (!hasOldInput) {
            syncBill();
        }
        syncCurrency();
        syncCheckRequirements(false);
        syncVoucherNumber();
    });
</script>
@endpush
