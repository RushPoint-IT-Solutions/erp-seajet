@extends('erp.layout')

@section('title', $module['title'])
@section('page-title', $module['title'])

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $module['title'] }}</h1>
            <p class="text-muted mb-0">{{ $module['subtitle'] }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            @foreach($module['actions'] as $index => $action)
                @php
                    $actionRoutes = [
                        'Billing Invoice' => route('erp.invoice.preview'),
                        'Customer Masterlist' => route('erp.customers.index'),
                        'Create Invoice' => route('erp.invoices.create'),
                        'Billing CRUD' => route('erp.invoices.index'),
                        'Receive Payment' => route('erp.payments.create'),
                        'Open Public Invoice' => route('erp.public.invoice', 'demo-invoice-token'),
                        'Open Checkout' => route('erp.public.checkout', 'demo-invoice-token'),
                        'Stripe Setup' => route('erp.modules.show', 'online-checkout'),
                        'Webhook Monitor' => route('erp.modules.show', 'api-webhooks'),
                        'Invoice Preview' => route('erp.invoice.preview'),
                        'New BI' => route('erp.invoice.preview'),
                        'SOA A' => route('erp.soa-a.preview'),
                        'New SOA A' => route('erp.soa-a.preview'),
                        'Debit Note' => route('erp.debit-note.preview'),
                        'New Debit Note' => route('erp.debit-note.preview'),
                        'Official Receipt' => route('erp.official-receipt.preview'),
                        'Acknowledgement Receipt' => route('erp.acknowledgement-receipt.preview'),
                        'Templates' => route('erp.customer-billing.templates'),
                        'Aging Report' => route('erp.customer-billing.aging'),
                        'SOA A Collection' => route('erp.payments.create', ['type' => 'soa-a']),
                        'Deposit Collection' => route('erp.action', 'deposit-collection'),
                        'New Journal Entry' => route('erp.accounting.journals'),
                        'Journal Entries' => route('erp.accounting.journals'),
                        'Chart of Accounts' => route('erp.accounting.coa'),
                        'Import Opening Balances' => route('erp.accounting.opening-balances.import'),
                        'Run Trial Balance' => route('erp.action', 'run-trial-balance'),
                        'Lock Posting Period' => route('erp.action', 'lock-posting-period'),
                        'Record Supplier Bill' => route('erp.payables.vendor-bills.create'),
                        'Vendor Bill Entry' => route('erp.payables.vendor-bills.create'),
                        'Pay Bills' => route('erp.payables.payments.create'),
                        'Payment Processing' => route('erp.payables.payments.create'),
                        'Payment Approvals' => route('erp.payables.approvals'),
                        'Print Check Voucher' => route('erp.payables.check-voucher'),
                        'AP Aging' => route('erp.payables.aging'),
                        'Print Checks' => route('erp.payables.check-print'),
                        'New Item' => route('erp.action', 'new-item'),
                        'Create PR' => route('erp.action', 'create-pr'),
                        'Review Vendor Bill Support' => route('erp.action', 'create-po'),
                        'Receive Goods / Services' => route('erp.action', 'receive-goods-services'),
                        'Supplier Performance' => route('erp.action', 'supplier-performance'),
                        'Cashbook' => route('erp.banking.cashbook'),
                        'Record Bank Transaction' => route('erp.banking.transactions.create'),
                        'Record Deposit' => route('erp.banking.transactions.create'),
                        'Fund Transfer' => route('erp.action', 'fund-transfer'),
                        'Bank Reconciliation' => route('erp.banking.reconciliation'),
                        'Daily Cash Position' => route('erp.banking.daily-cash'),
                        'Check Monitoring' => route('erp.action', 'check-monitoring'),
                        'Currency Conversion' => route('erp.fx.conversion'),
                        'FX Gains / Losses' => route('erp.fx.gains-losses'),
                        'Bank FX Tracking' => route('erp.banking.fx'),
                        'Profit & Loss' => route('erp.statements.profit-loss'),
                        'Balance Sheet' => route('erp.statements.balance-sheet'),
                        'Cash Flow' => route('erp.statements.cash-flow'),
                        'Export Excel' => route('erp.export', 'excel'),
                        'CSV Export' => route('erp.export', 'csv'),
                        'VAT Summary' => route('erp.tax.vat-summary'),
                        'Tax Calculator' => route('erp.tax.calculator'),
                        'Withholding Tax' => route('erp.tax.withholding'),
                        'Sales & Purchases' => route('erp.tax.sales-purchases'),
                        'Sales Tax Report' => route('erp.tax.sales-purchases'),
                        'EWT Report' => route('erp.tax.withholding'),
                        'Tax Setup' => route('erp.modules.show', 'tax'),
                        'Invite User' => route('erp.users.create'),
                        'Create Role' => route('erp.action', 'create-role'),
                        'Approval Matrix' => route('erp.approval-matrix'),
                        'Audit Logs' => route('erp.action', 'audit-logs'),
                        'Portal Preview' => route('erp.public.invoice', 'demo-invoice-token'),
                        'Magic Link Settings' => route('erp.modules.show', 'client-portal'),
                        'Create Schedule' => route('erp.modules.show', 'recurring-invoices'),
                        'Generate Due Invoices' => route('erp.modules.show', 'recurring-invoices'),
                        'Email Queue' => route('erp.modules.show', 'email-reminders'),
                        'Reminder Rules' => route('erp.modules.show', 'email-reminders'),
                        'API Tokens' => route('erp.modules.show', 'api-webhooks'),
                        'Outgoing Webhooks' => route('erp.modules.show', 'api-webhooks'),
                        'Security Checklist' => route('erp.modules.show', 'security'),
                    ];
                    $actionSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($action)), '-');
                    $href = isset($actionRoutes[$action]) ? $actionRoutes[$action] : route('erp.action', $actionSlug);
                @endphp
                <a href="{{ $href }}" class="btn {{ $index === 0 ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi {{ $index === 0 ? 'bi-plus-lg' : 'bi-arrow-right-short' }} me-1"></i>{{ $action }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="erp-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Workspace</h2>
                        <small class="text-muted">Server-side DataTables and Ajax CRUD ready</small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('erp.export', 'excel') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Export Excel"><i class="bi bi-file-earmark-excel"></i></a>
                        <a href="{{ route('erp.export', 'pdf') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Export PDF"><i class="bi bi-filetype-pdf"></i></a>
                        <a href="{{ route('erp.invoice.preview') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Print"><i class="bi bi-printer"></i></a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle module-table w-100">
                        <thead>
                        <tr>
                            @foreach($module['columns'] as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($module['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                                <td class="text-end">
                                    <a href="{{ route('erp.action', 'view-record') }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('erp.action', 'edit-record') }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Module Coverage</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($module['features'] as $feature)
                        <span class="badge badge-soft py-2 px-3">{{ $feature }}</span>
                    @endforeach
                </div>
                <hr>
                <h3 class="h6 fw-bold mb-3">ERP Controls</h3>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>Audit trail</span><i class="bi bi-check-circle text-success"></i></div>
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>Approval workflow</span><i class="bi bi-check-circle text-success"></i></div>
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>Branch filtering</span><i class="bi bi-check-circle text-success"></i></div>
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>PDF / Excel export</span><i class="bi bi-check-circle text-success"></i></div>
                    <div class="list-group-item px-0 d-flex justify-content-between"><span>Email-ready forms</span><i class="bi bi-check-circle text-success"></i></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({
        pageLength: 8,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search records' }
    });
</script>
@endpush
