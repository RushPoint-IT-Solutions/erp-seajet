@extends('erp.layout')

@section('title', 'Payments')
@section('page-title', 'Payments')

@push('styles')
<style>
    .payment-workspace {
        background: #eef0f3;
        margin: -1.5rem;
        min-height: calc(100vh - 74px);
        display: grid;
        grid-template-columns: minmax(560px, 1fr) minmax(420px, 660px);
        gap: 0;
    }
    .payment-left {
        border-right: 1px solid #d5d9e1;
        min-width: 0;
    }
    .payment-toolbar {
        min-height: 56px;
        background: #ff6b00;
        color: #fff;
        padding: 7px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .payment-toolbar .btn,
    .payment-entry-toolbar .btn {
        color: #fff;
        border-color: rgba(255, 255, 255, .25);
    }
    .payment-toolbar .btn-success,
    .payment-entry-toolbar .btn-success {
        color: #fff;
    }
    .payment-search {
        position: relative;
        min-width: 260px;
        flex: 1 1 320px;
    }
    .payment-search input {
        height: 42px;
        border: 0;
        border-radius: 2px;
        padding-left: 18px;
        padding-right: 42px;
        background: #e9e9e9;
    }
    .payment-search i {
        position: absolute;
        right: 14px;
        top: 11px;
        color: #111827;
        font-size: 20px;
    }
    .payment-filter {
        min-width: 185px;
        height: 42px;
        color: #fff;
        border-color: rgba(255, 255, 255, .8);
        background: #ff6b00;
    }
    .payment-list-card {
        background: #fff;
        margin: 20px 6px 0;
        border-radius: 4px;
        border: 1px solid #e0e3e8;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
        overflow: hidden;
    }
    .payment-list table {
        margin: 0;
    }
    .payment-list thead th {
        height: 56px;
        background: #fff;
        color: #222;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
        border-bottom: 1px solid #d8d8d8;
    }
    .payment-list tbody td {
        height: 48px;
        color: #333;
        border-color: #ddd;
        vertical-align: middle;
    }
    .payment-list tbody tr.is-selected td {
        background: #fff3e8;
    }
    .payment-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 105px;
        height: 27px;
        border-radius: 2px;
        background: #3f9425;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
    }
    .payment-pager {
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 32px;
        color: #777;
        font-size: 12px;
        padding: 0 28px;
        border-top: 1px solid #e3e3e3;
    }
    .payment-entry-toolbar {
        min-height: 56px;
        background: #ff6b00;
        color: #fff;
        padding: 8px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .payment-panel {
        background: #fff;
        margin: 16px 16px 20px;
        border: 1px solid #e0e3e8;
        border-radius: 3px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .09);
    }
    .payment-panel-body {
        padding: 24px 16px 14px;
    }
    .payment-field {
        border: 0;
        border-bottom: 2px solid #cfcfcf;
        border-radius: 0;
        background: transparent;
        padding-left: 0;
        min-height: 40px;
    }
    .payment-field:focus {
        box-shadow: none;
        border-color: #ff6b00;
    }
    .payment-field-label {
        color: #777;
        margin-bottom: -2px;
    }
    .toggle-line {
        min-height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .form-switch .form-check-input.payment-switch {
        width: 38px;
        height: 20px;
        cursor: pointer;
    }
    .form-switch .form-check-input.payment-switch:checked {
        background-color: #ff7900;
        border-color: #ff7900;
    }
    @media (max-width: 1199.98px) {
        .payment-workspace {
            grid-template-columns: 1fr;
        }
        .payment-left {
            border-right: 0;
        }
    }
</style>
@endpush

@section('content')
    <div class="payment-workspace">
        <section class="payment-left">
            <div class="payment-toolbar no-print">
                <a href="{{ route('erp.payments.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-2"></i>Create</a>
                <button type="button" class="btn btn-link px-2" id="selectPayments" title="Select payments"><i class="bi bi-check-square-fill fs-5"></i></button>
                <div class="payment-search">
                    <input type="search" class="form-control" id="paymentSearch" placeholder="Search {{ count($payments) }} Payments">
                    <i class="bi bi-search"></i>
                </div>
                <select class="form-select payment-filter" id="statusFilter">
                    <option>Active</option>
                    <option>Completed</option>
                    <option>Draft</option>
                    <option>Voided</option>
                </select>
                <select class="form-select payment-filter" id="typeFilter">
                    <option>All</option>
                    <option>Cash</option>
                    <option>Check</option>
                    <option>Bank Transfer</option>
                    <option>Online</option>
                </select>
                <button type="button" class="btn btn-link px-2" id="refreshPayments" title="Refresh"><i class="bi bi-clock-history fs-5"></i></button>
            </div>

            <div class="payment-list-card payment-list">
                <div class="table-responsive">
                    <table class="table align-middle" id="paymentsTable">
                        <thead>
                        <tr>
                            <th style="width: 110px"></th>
                            <th>Status</th>
                            <th>Number <i class="bi bi-arrow-down"></i></th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Invoice Number</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($payments as $payment)
                            <tr data-status="{{ $payment['status'] }}"
                                data-client="{{ $payment['client'] }}"
                                data-amount="{{ str_replace(',', '', $payment['amount']) }}"
                                data-currency="{{ $payment['currency'] }}"
                                data-invoice="{{ $payment['invoice_number'] }}"
                                data-date="{{ $payment['date'] }}"
                                data-number="{{ $payment['number'] }}">
                                <td>
                                    <button type="button" class="btn btn-sm btn-link text-dark edit-payment" title="Edit payment"><i class="bi bi-pencil-circle fs-4"></i></button>
                                    <button type="button" class="btn btn-sm btn-link text-dark more-payment" title="Select payment"><i class="bi bi-three-dots-vertical fs-5"></i></button>
                                </td>
                                <td><span class="payment-status">{{ $payment['status'] }}</span></td>
                                <td>{{ $payment['number'] }}</td>
                                <td>{{ $payment['client'] }}</td>
                                <td>{{ $payment['currency'] === 'PHP' ? 'P' : $payment['currency'] }} {{ $payment['amount'] }}</td>
                                <td>{{ $payment['invoice_number'] ?: '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($payment['date'])->format('d/M/Y') }}</td>
                            </tr>
                        @endforeach
                        @if(count($payments) === 0)
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">No payment records found in the database.</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
                <div class="payment-pager no-print">
                    <span>Rows per page:</span>
                    <span>100 <i class="bi bi-caret-down-fill ms-1"></i></span>
                    <span>1-{{ count($payments) }} of {{ count($payments) }}</span>
                    <span class="text-muted"><i class="bi bi-skip-start-fill"></i></span>
                    <span class="text-muted"><i class="bi bi-chevron-left"></i></span>
                    <span class="text-muted"><i class="bi bi-chevron-right"></i></span>
                    <span class="text-muted"><i class="bi bi-skip-end-fill"></i></span>
                </div>
            </div>
        </section>

        <section class="payment-right">
            <form method="post" action="{{ route('erp.payments.receipt') }}" id="paymentForm">
                {{ csrf_field() }}
                <div class="payment-entry-toolbar">
                    <h1 class="h5 mb-0">Enter Payment</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-light" type="button" id="samplePaymentButton"><i class="bi bi-magic me-1"></i>Sample Payment</button>
                        <a href="{{ route('erp.modules.show', 'collections') }}" class="btn btn-sm btn-outline-light"><i class="bi bi-x-circle me-1"></i>Cancel</a>
                        <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-cloud-arrow-up-fill me-1"></i>Save</button>
                    </div>
                </div>

                <div class="payment-panel">
                    <div class="payment-panel-body">
                        <div class="mb-3">
                            <label class="payment-field-label">Client</label>
                            <select name="customer_id" id="customerSelect" class="form-select payment-field erp-select" required>
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
                            <input type="hidden" name="customer_name" id="customerName" value="">
                        </div>
                        <div class="mb-3">
                            <label class="payment-field-label">Invoice</label>
                            <select name="invoice_no" id="invoiceSelect" class="form-select payment-field">
                                <option value="{{ request('invoice', '') }}">{{ request('invoice', '') }}</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice['document_no'] }}"
                                            data-customer-id="{{ $invoice['customer_id'] }}"
                                            data-currency="{{ $invoice['currency'] }}"
                                            data-balance="{{ number_format($invoice['balance'], 2, '.', '') }}">
                                        {{ $invoice['document_no'] }} - {{ $invoice['customer_name'] }} - {{ $invoice['currency'] }} {{ number_format($invoice['balance'], 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="payment-field-label">Payment Date</label>
                            <input name="payment_date" type="date" class="form-control payment-field" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="payment-field-label">Payment Type</label>
                            <select name="payment_method" class="form-select payment-field">
                                <option>Check</option>
                                <option>Cash</option>
                                <option>Bank Transfer</option>
                                <option>Online</option>
                            </select>
                            <input type="hidden" name="payment_type" value="Customer Payment">
                        </div>
                        <div class="mb-3">
                            <label class="payment-field-label">Transaction Reference</label>
                            <input name="reference_no" class="form-control payment-field" value="">
                        </div>
                        <div class="mb-3">
                            <label class="payment-field-label">Project</label>
                            <input name="project" class="form-control payment-field" value="">
                        </div>
                        <div class="mb-3">
                            <label class="payment-field-label">Shipment Job ID</label>
                            <input name="job_shipment_id" class="form-control payment-field" value="">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="payment-field-label">Currency</label>
                                <select name="currency" id="currencySelect" class="form-select payment-field">
                                    <option>PHP</option>
                                    <option>USD</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="payment-field-label">Amount</label>
                                <input name="amount" class="form-control payment-field text-end" value="" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="payment-field-label">Private Notes</label>
                            <textarea name="private_notes" class="form-control payment-field" rows="3"></textarea>
                        </div>
                        <input type="hidden" name="receipt_type" value="Official Receipt">
                        <input type="hidden" name="receipt_no" value="">
                    </div>
                </div>

                <div class="payment-panel">
                    <div class="payment-panel-body py-3">
                        <div class="toggle-line">
                            <div>
                                <div class="fw-semibold">Send Email</div>
                                <div class="text-muted small">Email payment receipt to the client</div>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input payment-switch" type="checkbox" name="send_email" value="Yes" checked>
                            </div>
                        </div>
                        <div class="toggle-line">
                            <div class="fw-semibold">Convert Currency</div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input payment-switch" type="checkbox" name="convert_currency" value="Yes">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#customerSelect').on('change', function () {
            var option = $('#customerSelect option:selected');
            $('#customerName').val(option.data('name') || '');
            if (option.data('currency')) {
                $('#currencySelect').val(option.data('currency'));
            }
        });

        $('#invoiceSelect').on('change', function () {
            var option = $('#invoiceSelect option:selected');
            if (option.data('customer-id')) {
                $('#customerSelect').val(option.data('customer-id')).trigger('change');
            }
            if (option.data('currency')) {
                $('#currencySelect').val(option.data('currency'));
            }
            if (option.data('balance')) {
                $('input[name="amount"]').val(option.data('balance'));
            }
        });

        $('#paymentSearch').on('input', function () {
            filterPayments();
        });

        $('#statusFilter, #typeFilter').on('change', function () {
            filterPayments();
        });

        $('#refreshPayments').on('click', function () {
            $('#paymentSearch').val('');
            $('#statusFilter').val('Active');
            $('#typeFilter').val('All');
            $('#paymentsTable tbody tr').removeClass('is-selected').show();
        });

        $('#selectPayments').on('click', function () {
            $('#paymentsTable tbody tr:visible').toggleClass('is-selected');
        });

        $('.edit-payment, .more-payment').on('click', function () {
            var row = $(this).closest('tr');
            row.addClass('is-selected').siblings().removeClass('is-selected');
            $('#customerSelect option').filter(function () {
                return ($(this).data('name') || '') === row.data('client');
            }).prop('selected', true);
            $('#customerSelect').trigger('change');
            $('#customerName').val(row.data('client'));
            $('#invoiceSelect').val(row.data('invoice'));
            $('input[name="amount"]').val(row.data('amount'));
            $('#currencySelect').val(row.data('currency'));
            $('input[name="payment_date"]').val(row.data('date'));
            $('input[name="reference_no"]').val('PAY-' + row.data('number'));
        });

        $('#samplePaymentButton').on('click', function () {
            var sampleDate = new Date().toISOString().slice(0, 10);
            var invoiceOption = $('#invoiceSelect option').filter(function () {
                return $(this).val() && $(this).data('balance');
            }).first();
            var customerId = invoiceOption.data('customer-id') || '';
            var customerOption = customerId
                ? $('#customerSelect option[value="' + customerId + '"]')
                : $('#customerSelect option').filter(function () { return $(this).val(); }).first();

            if (!customerOption.length) {
                $('#customerSelect').append(
                    $('<option>', {
                        value: 'sample',
                        text: 'CUST-SAMPLE - Philusa Corporation'
                    }).attr({
                        'data-code': 'CUST-SAMPLE',
                        'data-name': 'Philusa Corporation',
                        'data-currency': 'PHP'
                    })
                );
                customerOption = $('#customerSelect option[value="sample"]');
            }

            customerOption.prop('selected', true);
            $('#customerSelect').trigger('change');

            if (invoiceOption.length) {
                invoiceOption.prop('selected', true);
                $('#invoiceSelect').trigger('change');
            } else {
                $('#invoiceSelect').append(
                    $('<option>', {
                        value: 'BI-2026-01042',
                        text: 'BI-2026-01042 - Philusa Corporation - PHP 98,500.00'
                    }).attr({
                        'data-customer-id': customerOption.val(),
                        'data-currency': 'PHP',
                        'data-balance': '98500.00'
                    })
                );
                $('#invoiceSelect').val('BI-2026-01042').trigger('change');
            }

            $('input[name="payment_date"]').val(sampleDate);
            $('select[name="payment_method"]').val('Bank Transfer');
            $('input[name="reference_no"]').val('BPI-FT-' + sampleDate.replace(/-/g, '') + '-1042');
            $('input[name="project"]').val('Ocean Freight Collection');
            $('input[name="job_shipment_id"]').val('JOB-SEA-2026-0422');
            $('#currencySelect').val($('#invoiceSelect option:selected').data('currency') || 'PHP');
            $('input[name="amount"]').val($('#invoiceSelect option:selected').data('balance') || '98500.00');
            $('textarea[name="private_notes"]').val('Sample customer payment applied to billing invoice. Verify bank reference before posting.');
            $('input[name="receipt_type"]').val('Official Receipt');
            $('input[name="receipt_no"]').val('OR-SAMPLE-0001');
        });

        function filterPayments() {
            var term = $('#paymentSearch').val().toLowerCase();
            var status = $('#statusFilter').val();
            var type = $('#typeFilter').val();

            $('#paymentsTable tbody tr').each(function () {
                var hasText = $(this).text().toLowerCase().indexOf(term) !== -1;
                var hasStatus = status === 'Active' || status === $(this).data('status');
                var hasType = type === 'All' || $(this).text().indexOf(type) !== -1;

                $(this).toggle(hasText && hasStatus && hasType);
            });
        }
    });
</script>
@endpush
