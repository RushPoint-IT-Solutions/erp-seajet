@extends('erp.layout')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Invoices</h1>
            <p class="text-muted mb-0">Dashboard, invoice list, billing actions, invoice review, and collection follow-up.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.accounting.opening-balances.import') }}" class="btn btn-outline-primary" title="Import opening invoices">
                <i class="bi bi-upload me-1"></i> Import Invoices
            </a>
            <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary" title="Create new invoice">
                <i class="bi bi-plus-lg me-1"></i> New Invoice
            </a>
            <a href="{{ route('erp.invoices.index', ['tab' => 'aging']) }}" class="btn btn-outline-secondary" title="Aging report">
                <i class="bi bi-bar-chart-line me-1"></i> Aging
            </a>
            <a href="{{ route('erp.export', 'invoices') }}" class="btn btn-outline-secondary" title="Export invoices">
                <i class="bi bi-file-earmark-excel me-1"></i> Export
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        @foreach($stats as $stat)
            <div class="col-md-6 col-xl-3">
                <div class="erp-card kpi-card p-3 h-100">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">{{ $stat['label'] }}</div>
                            <div class="h4 fw-bold mb-0">{{ $stat['value'] }}</div>
                        </div>
                        <span class="kpi-icon bg-{{ $stat['tone'] }}"><i class="bi {{ $stat['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="erp-card p-3 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Invoice List</h2>
                        <small class="text-muted">Active, voided, cancelled, and aging views in one table workspace.</small>
                    </div>
                    <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Create new">
                        <i class="bi bi-plus-lg"></i>
                    </a>
                </div>
                <ul class="nav nav-tabs mb-3 no-print" aria-label="Invoice list tabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $selectedInvoiceTab === 'active' ? 'active' : '' }}"
                           href="{{ route('erp.invoices.index', ['tab' => 'active']) }}">
                            Active <span class="badge rounded-pill text-bg-light ms-1">{{ $invoiceTabCounts['active'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $selectedInvoiceTab === 'voided' ? 'active' : '' }}"
                           href="{{ route('erp.invoices.index', ['tab' => 'voided']) }}">
                            Voided / Cancelled <span class="badge rounded-pill text-bg-light ms-1">{{ $invoiceTabCounts['voided'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $selectedInvoiceTab === 'aging' ? 'active' : '' }}"
                           href="{{ route('erp.invoices.index', ['tab' => 'aging']) }}">
                            Aging Report
                        </a>
                    </li>
                </ul>
                @if($selectedInvoiceTab === 'aging')
                    <form class="border rounded p-3 mb-3 no-print" method="get" action="{{ route('erp.invoices.index') }}">
                        <input type="hidden" name="tab" value="aging">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">As of Date</label>
                                <input type="date" name="as_of" class="form-control" value="{{ $agingFilters['as_of'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select name="class" class="form-select erp-select">
                                    @foreach(['All Classes', 'MANILA', 'PAMPANGA', 'CEBU'] as $option)
                                        <option value="{{ $option }}" {{ $agingFilters['class'] === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-select erp-select">
                                    @foreach(['All Currency', 'PHP', 'USD'] as $option)
                                        <option value="{{ $option }}" {{ $agingFilters['currency'] === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">VAT</label>
                                <select name="vat" class="form-select erp-select">
                                    @foreach(['All VAT', '12%', 'zero-rated', 'exempt'] as $option)
                                        <option value="{{ $option }}" {{ $agingFilters['vat'] === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                                <a href="{{ route('erp.export', 'aging-excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                                <a href="{{ route('erp.export', 'aging-pdf') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
                                <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply Filters</button>
                            </div>
                        </div>
                    </form>

                    <div class="row g-3 mb-3">
                        @foreach([
                            ['label' => 'Current', 'key' => 'current'],
                            ['label' => '30 Days', 'key' => 'thirty'],
                            ['label' => '60 Days', 'key' => 'sixty'],
                            ['label' => '90+ Days', 'key' => 'ninety'],
                        ] as $bucket)
                            <div class="col-md-6 col-xl-3">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="text-muted small">{{ $bucket['label'] }}</div>
                                    <div class="h5 fw-bold mb-0">PHP {{ number_format($agingSummary[$bucket['key']] ?? 0, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle invoice-aging-table w-100">
                            <thead>
                            <tr>
                                <th>Customer ID</th><th>Customer</th><th>Job/Shipment ID</th><th>Invoice No.</th><th>Currency</th><th>VAT</th><th>Class</th>
                                <th class="text-end">Current</th><th class="text-end">30</th><th class="text-end">60</th><th class="text-end">90+</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($agingReportRows as $row)
                                <tr>
                                    @foreach($row as $index => $cell)
                                        <td class="{{ $index >= 7 ? 'text-end' : '' }}">{{ $index >= 7 ? number_format($cell, 2) : $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center text-muted py-4">No unpaid active invoices found for the selected filters.</td></tr>
                            @endforelse
                            </tbody>
                            <tfoot>
                            <tr class="fw-bold">
                                <td colspan="7" class="text-end">Total</td>
                                <td class="text-end">{{ number_format($agingSummary['current'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($agingSummary['thirty'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($agingSummary['sixty'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($agingSummary['ninety'] ?? 0, 2) }}</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle invoices-table w-100">
                        <thead>
                        <tr>
                            <th>Document No.</th>
                            <th>Customer</th>
                            <th>Shipment Job ID</th>
                            <th>Currency</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($invoices as $invoice)
                            @php $isFinal = in_array(strtolower((string) $invoice[7]), ['cancelled', 'voided'], true); @endphp
                            <tr>
                                <td class="fw-semibold">{{ $invoice[0] }}</td>
                                <td>{{ $invoice[1] }}</td>
                                <td>{{ $invoice[2] }}</td>
                                <td>{{ $invoice[3] }}</td>
                                <td class="text-end">{{ $invoice[4] }}</td>
                                <td class="text-end">{{ $invoice[5] }}</td>
                                <td class="text-end fw-semibold">{{ $invoice[6] }}</td>
                                <td><span class="badge badge-soft">{{ $invoice[7] }}</span></td>
                                <td>{{ $invoice[8] }}</td>
                                <td class="text-end">
                                    <a href="{{ route('erp.invoices.show', $invoice[9]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View"><i class="bi bi-eye"></i></a>
                                    @unless($isFinal)
                                        <a href="{{ route('erp.billings.edit', $invoice[9]) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="{{ route('erp.billings.void', $invoice[9]) }}" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Void or cancel"><i class="bi bi-x-circle"></i></a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary invoice-payment-button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#invoicePaymentModal"
                                                data-invoice="{{ $invoice[0] }}"
                                                title="Payment">
                                            <i class="bi bi-credit-card"></i>
                                        </button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    @include('erp.payments._payment-modal', [
        'customers' => $paymentCustomers,
        'invoices' => $paymentInvoices,
        'paymentModalId' => 'invoicePaymentModal',
        'paymentModalTitle' => 'Receive Invoice Payment',
    ])
@endsection

@push('scripts')
<script>
    $('.invoices-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search invoices' }
    });

    @if(!empty($agingReportRows))
        $('.invoice-aging-table').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: { search: '', searchPlaceholder: 'Search aging' }
        });
    @endif

    $(function () {
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
            form.find('.payment-cwt-input').val(option.data('cwt-balance') || '0.00');
        }

        $('#invoicePaymentModal .payment-customer-select').on('change', function () {
            syncCustomer($(this).closest('form'));
        });

        $('#invoicePaymentModal .payment-invoice-select').on('change', function () {
            syncInvoice($(this).closest('form'));
        });

        $(document).on('click', '.invoice-payment-button', function () {
            var form = $('#invoicePaymentModal').find('form');
            form[0].reset();
            form.find('.payment-invoice-select').val($(this).data('invoice')).trigger('change');
        });
    });
</script>
@endpush
