@extends('erp.layout')

@section('title', 'Import Opening Invoices & Bills')
@section('page-title', 'Import Opening Invoices & Bills')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Import Opening Invoices & Bills</h1>
            <p class="text-muted mb-0">Create payment-ready customer invoices and vendor bills with balanced opening-balance journals dated from the workbook.</p>
        </div>
        <a href="{{ route('erp.accounting.journals') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Journal Entries</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info">
        <strong>Posting rule:</strong> A/R rows debit the selected receivable account and credit Opening Balance Equity. A/P rows debit Opening Balance Equity and credit the selected payable account. A/R rows become open invoices visible in AR Aging and Receive Payment; A/P rows become approved open vendor bills in Pay Bills. Each journal, invoice, and bill uses the date in its Excel row. Rows with the same Excel date are combined into one balanced journal.
    </div>

    <form method="post" action="{{ route('erp.accounting.opening-balances.preview') }}" enctype="multipart/form-data" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Opening-balance workbook (.xlsx)</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <div class="form-text">Rows with blank totals/footer labels are ignored. Missing A/R customers are created during import. Unmatched vendors, branches, or previously imported rows are skipped and listed in the preview.</div>
            </div>
        </div>

        <hr class="my-4">
        <h2 class="h6 fw-bold mb-3">Account mapping</h2>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <div class="text-uppercase small fw-bold text-muted mb-3">Offset account</div>
                    <label class="form-label">Opening Balance</label>
                    <select name="opening_balance_account_id" class="form-select erp-select" required>
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('opening_balance_account_id', $defaults['opening_balance_account_id']) === (string) $account->id ? 'selected' : '' }}>{{ $account->label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <div class="text-uppercase small fw-bold text-muted mb-3">Receivable and payable accounts</div>
                    @foreach([
                        'ar_trade' => 'A/R Trade',
                        'ar_reimbursable' => 'A/R Reimbursable',
                        'ap_trade' => 'A/P Trade',
                    ] as $prefix => $label)
                        <div class="mb-3">
                            <label class="form-label">{{ $label }}</label>
                            <div class="row g-2">
                                @foreach(['php' => 'PHP account', 'usd' => 'USD account'] as $currency => $currencyLabel)
                                    @php($field = $prefix.'_'.$currency.'_account_id')
                                    <div class="col-md-6">
                                        <select name="{{ $field }}" class="form-select erp-select" aria-label="{{ $label }} {{ strtoupper($currency) }} account" required>
                                            <option value="">{{ $currencyLabel }}</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ (string) old($field, $defaults[$field]) === (string) $account->id ? 'selected' : '' }}>{{ $account->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-primary"><i class="bi bi-search me-1"></i> Preview Import</button>
        </div>
    </form>

    @if($preview)
        <div class="erp-card p-4 mt-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Import preview</h2>
                    <p class="text-muted mb-0">Only rows marked Ready will be posted. Skipped rows remain available to import later after the master data is corrected.</p>
                </div>
                @if($preview['valid_count'] > 0)
                    <form method="post" action="{{ route('erp.accounting.opening-balances.commit') }}" onsubmit="return confirm('Post {{ $preview['valid_count'] }} opening-balance rows to the general ledger?')">
                        {{ csrf_field() }}
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i> Import {{ $preview['valid_count'] }} Ready Rows</button>
                    </form>
                @endif
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Ready rows</div><div class="fs-4 fw-bold text-success">{{ number_format($preview['valid_count']) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Skipped rows</div><div class="fs-4 fw-bold {{ $preview['skipped_count'] ? 'text-danger' : '' }}">{{ number_format($preview['skipped_count']) }}</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Excel posting dates</div><div class="fw-bold">{{ $preview['source_date_from'] ?: '-' }}@if($preview['source_date_to'] && $preview['source_date_to'] !== $preview['source_date_from']) to {{ $preview['source_date_to'] }}@endif</div><div class="small text-muted">{{ number_format($preview['source_date_count']) }} journal(s)</div></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><div class="text-muted small">Balanced debit / credit</div><div class="fs-4 fw-bold">PHP {{ number_format($preview['total_amount'], 2) }}</div></div></div>
            </div>

            @if(!empty($preview['summary']))
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Balance type</th><th>Currency</th><th class="text-end">Rows</th><th class="text-end">PHP equivalent</th></tr></thead>
                        <tbody>
                        @foreach($preview['summary'] as $summary)
                            <tr><td>{{ $summary['label'] }}</td><td>{{ $summary['currency'] }}</td><td class="text-end">{{ number_format($summary['rows']) }}</td><td class="text-end">{{ number_format($summary['amount'], 2) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="table-responsive" style="max-height: 600px; overflow: auto;">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th>Source</th><th>Branch</th><th>Type</th><th>Date</th><th>Customer / Vendor</th><th>Source number</th><th>Invoice / Bill No.</th><th>Currency</th><th class="text-end">Opening Balance</th><th>Credit account</th><th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($preview['rows'] as $row)
                        <tr class="{{ $row['is_valid'] ? '' : 'table-danger' }}">
                            <td class="text-nowrap">{{ $row['sheet_name'] }} #{{ $row['row_number'] }}</td>
                            <td>{{ $row['branch'] ?: '-' }}</td>
                            <td>{{ $row['balance_type_label'] }}</td>
                            <td class="text-nowrap">{{ $row['source_date'] ?: '-' }}</td>
                            <td>{{ $row['party_name'] ?: '-' }}</td>
                            <td>{{ $row['reference_no'] ?: '-' }}</td>
                            <td>{{ $row['subledger_document_no'] ?: '-' }}</td>
                            <td>{{ $row['currency'] }}</td>
                            <td class="text-end text-nowrap">{{ number_format($row['base_amount'], 2) }}</td>
                            <td>{{ $row['target_account_label'] ?: '-' }}</td>
                            <td style="min-width: 220px">
                                @if($row['is_valid'])
                                    <span class="badge text-bg-success">Ready</span>
                                    @if(!empty($row['will_create_customer']))
                                        <div class="small text-primary mt-1">New customer will be created</div>
                                    @endif
                                @else
                                    @foreach($row['errors'] as $error)
                                        <div class="small text-danger">{{ $error }}</div>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($recentImports->isNotEmpty())
        <div class="erp-card p-4 mt-4">
            <h2 class="h6 fw-bold mb-3">Recent opening-balance imports</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Imported</th><th>File</th><th>Excel posting dates</th><th class="text-end">Rows</th><th class="text-end">AR invoices</th><th class="text-end">AP bills</th><th class="text-end">Skipped</th><th class="text-end">Amount</th><th>Journals</th></tr></thead>
                    <tbody>
                    @foreach($recentImports as $batch)
                        <tr>
                            <td>{{ $batch->imported_at }}</td><td>{{ $batch->original_filename }}</td><td>{{ $batch->source_date_from }}@if($batch->source_date_to !== $batch->source_date_from) to {{ $batch->source_date_to }}@endif</td><td class="text-end">{{ number_format($batch->row_count) }}</td><td class="text-end">{{ number_format($batch->ar_rows) }}</td><td class="text-end">{{ number_format($batch->ap_rows) }}</td><td class="text-end">{{ number_format($batch->skipped_count) }}</td><td class="text-end">{{ number_format($batch->total_amount, 2) }}</td>
                            <td>@if($batch->journal_entry_id)<a href="{{ route('erp.accounting.journals.show', $batch->journal_entry_id) }}">{{ number_format($batch->journal_count) }} journal(s)</a>@else-@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
