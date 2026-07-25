@extends('erp.layout')

@section('title', 'Payments')
@section('page-title', 'Payments')

@push('styles')
<style>
    .payments-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .payments-search {
        position: relative;
        min-width: 260px;
        flex: 1 1 360px;
    }
    .payments-search input {
        padding-left: 38px;
    }
    .payments-search i {
        position: absolute;
        left: 13px;
        top: 9px;
        color: #6b7280;
    }
    .payments-table td,
    .payments-table th {
        white-space: nowrap;
    }
    .payments-table .client-cell {
        min-width: 220px;
        white-space: normal;
    }
    .payment-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 88px;
        border-radius: 999px;
        padding: 4px 10px;
        background: #e7f8ee;
        color: #16733a;
        font-weight: 700;
        font-size: 12px;
    }
    .payment-status.is-draft { background: #eef2ff; color: #3730a3; }
    .payment-status.is-voided { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Payments</h1>
            <p class="text-muted mb-0">Receive customer payments, apply them to invoices, and generate receipts.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-plus-lg me-1"></i> Receive Payment
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="erp-card p-3 h-100">
                <div class="text-muted small">Payments</div>
                <div class="h4 fw-bold mb-0">{{ count($payments) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="erp-card p-3 h-100">
                <div class="text-muted small">Open Invoices</div>
                <div class="h4 fw-bold mb-0">{{ count($invoices) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="erp-card p-3 h-100">
                <div class="text-muted small">Total Collected</div>
                <div class="h4 fw-bold mb-0">
                    {{ number_format(array_sum(array_map(function ($payment) { return (float) str_replace(',', '', $payment['amount']); }, $payments)), 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="payments-toolbar mb-3">
            <div class="payments-search">
                <i class="bi bi-search"></i>
                <input type="search" class="form-control" id="paymentSearch" placeholder="Search payments, clients, invoices">
            </div>
            <div class="d-flex flex-wrap gap-2">
                <select class="form-select" id="statusFilter" style="width: 150px">
                    <option value="All">All Status</option>
                    <option value="Posted">Posted</option>
                    <option value="Draft">Draft</option>
                    <option value="Voided">Voided</option>
                </select>
                <select class="form-select" id="typeFilter" style="width: 180px">
                    <option value="All">All Methods</option>
                    <option value="Cash">Cash</option>
                    <option value="Check">Check</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Online">Online</option>
                </select>
                <button type="button" class="btn btn-outline-secondary" id="refreshPayments"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle payments-table mb-0" id="paymentsTable">
                <thead>
                <tr>
                    <th>Status</th>
                    <th>Receipt No.</th>
                    <th class="client-cell">Client</th>
                    <th>Invoice No.</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Date</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($payments as $payment)
                    @php
                        $statusClass = 'is-'.strtolower($payment['status']);
                        $receiptQuery = [
                            'receipt_no' => $payment['number'],
                            'customer' => $payment['client'],
                            'invoice_no' => $payment['invoice_number'],
                            'currency' => $payment['currency'],
                            'amount' => str_replace(',', '', $payment['amount']),
                            'payment_date' => $payment['date'],
                            'payment_method' => $payment['payment_method'],
                            'reference_no' => $payment['reference_no'],
                        ];
                    @endphp
                    <tr data-status="{{ $payment['status'] }}"
                        data-method="{{ $payment['payment_method'] }}"
                        data-client="{{ $payment['client'] }}"
                        data-amount="{{ str_replace(',', '', $payment['amount']) }}"
                        data-currency="{{ $payment['currency'] }}"
                        data-invoice="{{ $payment['invoice_number'] }}"
                        data-date="{{ $payment['date'] }}"
                        data-reference="{{ $payment['reference_no'] }}">
                        <td><span class="payment-status {{ $statusClass }}">{{ $payment['status'] }}</span></td>
                        <td class="fw-semibold">{{ $payment['number'] }}</td>
                        <td class="client-cell">{{ $payment['client'] }}</td>
                        <td>{{ $payment['invoice_number'] ?: '-' }}</td>
                        <td>{{ $payment['payment_method'] ?: '-' }}</td>
                        <td>{{ $payment['reference_no'] ?: '-' }}</td>
                        <td>{{ $payment['date'] ? \Carbon\Carbon::parse($payment['date'])->format('d/M/Y') : '-' }}</td>
                        <td class="text-end">{{ $payment['currency'] === 'PHP' ? 'PHP' : $payment['currency'] }} {{ $payment['amount'] }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-payment" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="{{ route('erp.official-receipt.preview', $receiptQuery) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Official Receipt">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <a href="{{ route('erp.acknowledgement-receipt.preview', $receiptQuery) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Acknowledgement Receipt">
                                    <i class="bi bi-receipt-cutoff"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if(count($payments) === 0)
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">No payment records found.</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>

    @include('erp.payments._payment-modal', ['paymentModalId' => 'paymentModal', 'paymentModalTitle' => 'Receive Payment'])
@endsection

@push('scripts')
<script>
    $(function () {
        var requestedInvoice = @json(request('invoice', ''));
        var requestedCustomer = @json(request('customer', ''));
        var modalEl = document.getElementById('paymentModal');
        var paymentModal = modalEl ? new bootstrap.Modal(modalEl) : null;

        function syncCustomer(form) {
            var option = form.find('.payment-customer-select option:selected');
            form.find('.payment-customer-name').val(option.data('name') || '');
            if (option.data('currency')) {
                form.find('.payment-currency-select').val(option.data('currency'));
            }
        }

        function syncInvoice(form) {
            var option = form.find('.payment-invoice-select option:selected');
            if (option.data('customer-id')) {
                form.find('.payment-customer-select').val(option.data('customer-id')).trigger('change');
            }
            if (option.data('currency')) {
                form.find('.payment-currency-select').val(option.data('currency'));
            }
            if (option.data('balance')) {
                form.find('.payment-amount-input').val(option.data('balance'));
            }
        }

        $('.payment-customer-select').on('change', function () {
            syncCustomer($(this).closest('form'));
        });

        $('.payment-invoice-select').on('change', function () {
            syncInvoice($(this).closest('form'));
        });

        $('.edit-payment').on('click', function () {
            var row = $(this).closest('tr');
            var form = $('#paymentModal').find('form');
            form.find('.payment-invoice-select').val(row.data('invoice')).trigger('change');
            form.find('.payment-amount-input').val(row.data('amount'));
            form.find('.payment-currency-select').val(row.data('currency'));
            form.find('input[name="payment_date"]').val(row.data('date'));
            form.find('input[name="reference_no"]').val(row.data('reference'));
        });

        $('#paymentSearch, #statusFilter, #typeFilter').on('input change', filterPayments);
        $('#refreshPayments').on('click', function () {
            $('#paymentSearch').val('');
            $('#statusFilter').val('All');
            $('#typeFilter').val('All');
            filterPayments();
        });

        function filterPayments() {
            var term = $('#paymentSearch').val().toLowerCase();
            var status = $('#statusFilter').val();
            var method = $('#typeFilter').val();

            $('#paymentsTable tbody tr').each(function () {
                var row = $(this);
                if (!row.data('status')) {
                    return;
                }
                var hasText = row.text().toLowerCase().indexOf(term) !== -1;
                var hasStatus = status === 'All' || status === row.data('status');
                var hasMethod = method === 'All' || method === row.data('method');
                row.toggle(hasText && hasStatus && hasMethod);
            });
        }

        if (requestedInvoice) {
            var form = $('#paymentModal').find('form');
            form.find('.payment-invoice-select').val(requestedInvoice).trigger('change');
            if (paymentModal) {
                paymentModal.show();
            }
        } else if (requestedCustomer) {
            var customerForm = $('#paymentModal').find('form');
            customerForm.find('.payment-customer-select option').filter(function () {
                return ($(this).data('code') || '') === requestedCustomer;
            }).prop('selected', true);
            customerForm.find('.payment-customer-select').trigger('change');
            if (paymentModal) {
                paymentModal.show();
            }
        }
    });
</script>
@endpush
