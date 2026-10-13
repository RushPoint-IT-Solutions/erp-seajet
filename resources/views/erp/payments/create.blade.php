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
    @if(session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Payments</h1>
            <p class="text-muted mb-0">Receive customer payments, apply them to invoices, and generate receipts.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.accounting.opening-balances.manual') }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i> Manual Opening Balance</a>
            <a href="{{ route('erp.accounting.opening-balances.import') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i> Import Invoices</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="bi bi-plus-lg me-1"></i> Receive Payment
            </button>
        </div>
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
                    <th>Paid For</th>
                    <th class="client-cell">Client</th>
                    <th>BI / SOA Reference</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Date</th>
                    <th class="text-end">For Payment</th>
                    <th class="text-end">Paid Amount</th>
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
                        <td>{{ $payment['paid_for'] }}</td>
                        <td class="client-cell">{{ $payment['client'] }}</td>
                        <td>{{ $payment['document_reference'] ?: '-' }}</td>
                        <td>{{ $payment['payment_method'] ?: '-' }}</td>
                        <td>{{ $payment['reference_no'] ?: '-' }}</td>
                        <td>{{ $payment['date'] ? \Carbon\Carbon::parse($payment['date'])->format('d/M/Y') : '-' }}</td>
                        <td class="text-end">{{ $payment['currency'] === 'PHP' ? 'PHP' : $payment['currency'] }} {{ $payment['for_payment'] }}</td>
                        <td class="text-end">{{ $payment['currency'] === 'PHP' ? 'PHP' : $payment['currency'] }} {{ $payment['amount'] }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary correct-payment"
                                        data-bs-toggle="modal" data-bs-target="#correctPaymentModal"
                                        data-url="{{ route('erp.payments.correct', $payment['id']) }}"
                                        data-receipt="{{ $payment['number'] }}" title="Correct posted payment">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-payment"
                                        data-bs-toggle="modal" data-bs-target="#deletePaymentModal"
                                        data-url="{{ route('erp.payments.delete', $payment['id']) }}"
                                        data-receipt="{{ $payment['number'] }}" title="Delete payment">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @if($payment['can_print_or'])
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-receipt me-1"></i>OR</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('erp.official-receipt.preview', array_merge($receiptQuery, ['tax_print' => 'vat'])) }}">VAT Print</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('erp.official-receipt.preview', array_merge($receiptQuery, ['tax_print' => 'non_vat'])) }}">Non-VAT Print</a></li>
                                    </ul>
                                </div>
                                @endif
                                @if($payment['can_print_ar'])
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-receipt-cutoff me-1"></i>AR</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('erp.acknowledgement-receipt.preview', array_merge($receiptQuery, ['tax_print' => 'vat'])) }}">VAT Print</a></li>
                                        <li><a class="dropdown-item" target="_blank" href="{{ route('erp.acknowledgement-receipt.preview', array_merge($receiptQuery, ['tax_print' => 'non_vat'])) }}">Non-VAT Print</a></li>
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if(count($payments) === 0)
                    <tr>
                        <td colspan="11" class="text-center text-muted py-5">No payment records found.</td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>

    @include('erp.payments._payment-modal', ['paymentModalId' => 'paymentModal', 'paymentModalTitle' => 'Receive Payment'])

    <div class="modal fade" id="correctPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content" id="correctPaymentForm">
                {{ csrf_field() }}
                <div class="modal-header"><h5 class="modal-title">Correct posted payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p>Receipt <strong id="correctReceiptNumber"></strong> will be reversed. Its BI and SOA balances will reopen so you can record the correct payment. The original receipt stays in the accounting audit trail.</p>
                    <label class="form-label fw-semibold" for="correctionReason">Reason for correction</label>
                    <textarea id="correctionReason" name="reason" class="form-control" rows="2" minlength="5" maxlength="1000" required></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Reverse and reopen</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content" id="deletePaymentForm">
                {{ csrf_field() }}
                {{ method_field('DELETE') }}
                <div class="modal-header"><h5 class="modal-title">Delete payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        Receipt <strong id="deleteReceiptNumber"></strong> will be removed from the active payment list. Its journal will be reversed, the invoice balance will reopen, and the audit history will be retained.
                    </div>
                    <label class="form-label fw-semibold" for="deletePaymentReason">Reason for deletion</label>
                    <textarea id="deletePaymentReason" name="reason" class="form-control" rows="2" minlength="5" maxlength="1000" required></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete payment</button></div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        var requestedInvoice = @json(request('invoice', ''));
        var requestedCustomer = @json(request('customer', ''));
        var modalEl = document.getElementById('paymentModal');
        var paymentModal = modalEl ? new bootstrap.Modal(modalEl) : null;

        $('.correct-payment').on('click', function () {
            $('#correctPaymentForm').attr('action', $(this).data('url'));
            $('#correctReceiptNumber').text($(this).data('receipt'));
            $('#correctionReason').val('');
        });

        $('.delete-payment').on('click', function () {
            $('#deletePaymentForm').attr('action', $(this).data('url'));
            $('#deleteReceiptNumber').text($(this).data('receipt'));
            $('#deletePaymentReason').val('');
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
            var requestedPaymentFor = @json(request('payment_for', ''));
            var requestedOption = form.find('.payment-invoice-select option').filter(function () { return this.value === requestedInvoice; });
            if (!requestedPaymentFor && requestedOption.length) {
                var documentType = String(requestedOption.attr('data-document-type') || '').toLowerCase();
                var isSoaA = ['soa', 'soa_a', 'statement_of_account_a'].indexOf(documentType) !== -1;
                requestedPaymentFor = (parseFloat(requestedOption.attr('data-bi-balance')) || 0) > 0 ? 'bi' : (isSoaA ? 'soa_a' : 'soa');
            }
            form.find('.payment-for-select').val(requestedPaymentFor).trigger('change');
            form.find('.payment-invoice-select').val([requestedInvoice]).trigger('change');
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
