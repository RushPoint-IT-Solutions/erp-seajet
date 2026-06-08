@extends('erp.layout')

@section('title', 'Finance Settings')
@section('page-title', 'Finance Settings')

@push('styles')
<style>
    .settings-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .settings-form { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; }
    .settings-table { max-height: 360px; overflow: auto; }
    .settings-table table { min-width: 760px; }
    @media (max-width: 1199.98px) { .settings-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Finance Settings</h1>
            <p class="text-muted mb-0">Database-connected company, branches, cost centers, items, numbering, tax, currency, and bank setup.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.accounting.coa') }}" class="btn btn-primary"><i class="bi bi-journal-text me-1"></i>Chart of Accounts</a>
            <a href="{{ route('erp.approval-matrix') }}" class="btn btn-outline-secondary"><i class="bi bi-check2-square me-1"></i>Approvals</a>
            <a href="{{ route('erp.export', 'finance-settings') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i>Export</a>
        </div>
    </div>

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

    <div class="row g-3 mb-4">
        @foreach($settingStats as $stat)
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

    <div class="erp-card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Finance Setup Checklist</h2>
                <small class="text-muted">Every item below is now tied to a database table or an existing finance module.</small>
            </div>
        </div>
        @foreach($settingGroups as $group => $settings)
            <div class="mb-3">
                <h3 class="h6 fw-bold mb-2"><i class="bi bi-folder2-open text-primary me-2"></i>{{ $group }}</h3>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Setting</th><th>Description</th><th>Status</th><th class="text-end">Open</th></tr></thead>
                        <tbody>
                        @foreach($settings as $setting)
                            <tr>
                                <td class="fw-semibold"><i class="bi {{ $setting['icon'] }} me-2 text-primary"></i>{{ $setting['name'] }}</td>
                                <td>{{ $setting['description'] }}</td>
                                <td><span class="badge badge-soft">{{ $setting['status'] }}</span></td>
                                <td class="text-end"><a href="{{ $setting['route'] }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right"></i></a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <div class="settings-grid">
        <section class="erp-card p-3" id="company">
            <h2 class="h6 fw-bold mb-3">Company Finance Profile</h2>
            <form method="post" action="{{ route('erp.settings.company.save') }}" class="settings-form">
                {{ csrf_field() }}
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small text-muted">Company Name</label><input name="name" class="form-control form-control-sm" value="{{ $company->name ?? '' }}" required></div>
                    <div class="col-md-3"><label class="form-label small text-muted">TIN</label><input name="tin" class="form-control form-control-sm" value="{{ $company->tin ?? '' }}"></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Base Currency</label><input name="base_currency" class="form-control form-control-sm text-uppercase" value="{{ $company->base_currency ?? 'PHP' }}" maxlength="3" required></div>
                    <div class="col-12"><label class="form-label small text-muted">Address</label><input name="address" class="form-control form-control-sm" value="{{ $company->address ?? '' }}"></div>
                </div>
                <div class="text-end mt-2"><button class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Save Company</button></div>
            </form>
        </section>

        <section class="erp-card p-3" id="branches">
            <h2 class="h6 fw-bold mb-3">Branches</h2>
            <form method="post" action="{{ route('erp.settings.branches.save') }}" class="settings-form settings-edit-form mb-3">
                {{ csrf_field() }}<input type="hidden" name="id">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small text-muted">Code</label><input name="code" class="form-control form-control-sm" required></div>
                    <div class="col-md-5"><label class="form-label small text-muted">Name</label><input name="name" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Address</label><input name="address" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked> <span class="form-check-label">Active</span></label></div>
                </div>
                <div class="text-end"><button class="btn btn-sm btn-primary">Save Branch</button></div>
            </form>
            <div class="settings-table table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Code</th><th>Name</th><th>Address</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td class="fw-semibold">{{ $branch->code }}</td><td>{{ $branch->name }}</td><td>{{ $branch->address }}</td><td>{{ $branch->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary edit-row" data-form="#branches form" data-row='@json($branch)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No branches yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-card p-3" id="cost-centers">
            <h2 class="h6 fw-bold mb-3">Cost Centers</h2>
            <form method="post" action="{{ route('erp.settings.cost-centers.save') }}" class="settings-form settings-edit-form mb-3">
                {{ csrf_field() }}<input type="hidden" name="id">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small text-muted">Code</label><input name="code" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Name</label><input name="name" class="form-control form-control-sm" required></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Department</label><input name="department" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Branch</label><select name="branch_id" class="form-select form-select-sm"><option value=""></option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->code }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked> <span class="form-check-label">Active</span></label></div>
                </div>
                <div class="text-end"><button class="btn btn-sm btn-primary">Save Cost Center</button></div>
            </form>
            <div class="settings-table table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Branch</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($costCenters as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ $row->department }}</td><td>{{ $row->branch_code }}</td><td>{{ $row->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary edit-row" data-form="#cost-centers form" data-row='@json($row)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Run migrations, then add cost centers here.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-card p-3" id="item-list">
            <h2 class="h6 fw-bold mb-3">Item List</h2>
            <form method="post" action="{{ route('erp.settings.items.save') }}" class="settings-form settings-edit-form mb-3">
                {{ csrf_field() }}<input type="hidden" name="id">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small text-muted">SKU</label><input name="sku" class="form-control form-control-sm" required></div>
                    <div class="col-md-5"><label class="form-label small text-muted">Name</label><input name="name" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Category</label><input name="category" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label small text-muted">UOM</label><input name="unit_of_measure" class="form-control form-control-sm" value="pcs" required></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Cost</label><input name="standard_cost" class="form-control form-control-sm text-end"></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Price</label><input name="selling_price" class="form-control form-control-sm text-end"></div>
                    <div class="col-md-3 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked> <span class="form-check-label">Active</span></label></div>
                </div>
                <div class="text-end"><button class="btn btn-sm btn-primary">Save Item</button></div>
            </form>
            <div class="settings-table table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>UOM</th><th class="text-end">Price</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->sku }}</td><td>{{ $item->name }}</td><td>{{ $item->category }}</td><td>{{ $item->unit_of_measure }}</td><td class="text-end">{{ number_format((float) $item->selling_price, 2) }}</td><td>{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary edit-row" data-form="#item-list form" data-row='@json($item)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No items yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-card p-3" id="number-series">
            <h2 class="h6 fw-bold mb-3">Number Series</h2>
            <form method="post" action="{{ route('erp.settings.number-series.save') }}" class="settings-form settings-edit-form mb-3">
                {{ csrf_field() }}<input type="hidden" name="id">
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label small text-muted">Document Type</label><input name="document_type" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Prefix</label><input name="prefix" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Current No.</label><input name="current_number" type="number" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Padding</label><input name="padding" type="number" class="form-control form-control-sm" value="5" required></div>
                    <div class="col-md-1 d-flex align-items-end"><input type="checkbox" name="is_active" value="1" class="form-check-input mb-2" checked></div>
                </div>
                <div class="text-end"><button class="btn btn-sm btn-primary">Save Series</button></div>
            </form>
            <div class="settings-table table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Document</th><th>Prefix</th><th>Current</th><th>Next</th><th>Padding</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($numberSeries as $series)
                        <tr>
                            <td class="fw-semibold">{{ $series->document_type }}</td><td>{{ $series->prefix }}</td><td>{{ $series->current_number }}</td><td>{{ $series->prefix }}{{ str_pad($series->current_number + 1, $series->padding, '0', STR_PAD_LEFT) }}</td><td>{{ $series->padding }}</td><td>{{ $series->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary edit-row" data-form="#number-series form" data-row='@json($series)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No number series yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-card p-3" id="tax-codes">
            <h2 class="h6 fw-bold mb-3">Tax Codes</h2>
            <form method="post" action="{{ route('erp.settings.tax-codes.save') }}" class="settings-form settings-edit-form mb-3">
                {{ csrf_field() }}<input type="hidden" name="id">
                <div class="row g-2">
                    <div class="col-md-2"><label class="form-label small text-muted">Code</label><input name="code" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Name</label><input name="name" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Rate %</label><input name="rate" class="form-control form-control-sm text-end" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Type</label><input name="tax_type" class="form-control form-control-sm" value="VAT" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Class</label><input name="classification" class="form-control form-control-sm"></div>
                    <div class="col-md-5"><label class="form-label small text-muted">GL Account</label><select name="account_id" class="form-select form-select-sm"><option value=""></option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label small text-muted">WHT Kind</label><input name="withholding_kind" class="form-control form-control-sm"></div>
                    <div class="col-md-2 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_withholding" value="1" class="form-check-input"> WHT</label></div>
                    <div class="col-md-2 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked> Active</label></div>
                </div>
                <div class="text-end"><button class="btn btn-sm btn-primary">Save Tax Code</button></div>
            </form>
            <div class="settings-table table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Code</th><th>Name</th><th>Rate</th><th>Type</th><th>WHT</th><th>Account</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($taxCodes as $tax)
                        <tr>
                            <td class="fw-semibold">{{ $tax->code }}</td><td>{{ $tax->name }}</td><td>{{ number_format((float) $tax->rate, 2) }}%</td><td>{{ $tax->tax_type }}</td><td>{{ !empty($tax->is_withholding) ? 'Yes' : 'No' }}</td><td>{{ trim(($tax->account_code ?? '').' '.($tax->account_name ?? '')) }}</td><td>{{ $tax->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary edit-row" data-form="#tax-codes form" data-row='@json($tax)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No tax codes yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-card p-3" id="currency-rates">
            <h2 class="h6 fw-bold mb-3">Currency Rates</h2>
            <form method="post" action="{{ route('erp.settings.currency-rates.save') }}" class="settings-form settings-edit-form mb-3">
                {{ csrf_field() }}<input type="hidden" name="id">
                <div class="row g-2">
                    <div class="col-md-2"><label class="form-label small text-muted">From</label><input name="source_currency" class="form-control form-control-sm text-uppercase" value="USD" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">To</label><input name="target_currency" class="form-control form-control-sm text-uppercase" value="PHP" required></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Date</label><input name="rate_date" type="date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Rate</label><input name="rate" class="form-control form-control-sm text-end" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Source</label><input name="rate_source" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked> <span class="form-check-label">Active</span></label></div>
                </div>
                <div class="text-end"><button class="btn btn-sm btn-primary">Save Rate</button></div>
            </form>
            <div class="settings-table table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Date</th><th>Pair</th><th class="text-end">Rate</th><th>Source</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($currencyRates as $rate)
                        <tr>
                            <td>{{ $rate->rate_date }}</td><td class="fw-semibold">{{ $rate->source_currency }}/{{ $rate->target_currency }}</td><td class="text-end">{{ number_format((float) $rate->rate, 6) }}</td><td>{{ $rate->rate_source }}</td><td>{{ $rate->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary edit-row" data-form="#currency-rates form" data-row='@json($rate)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No currency rates yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-card p-3" id="bank-accounts">
            <h2 class="h6 fw-bold mb-3">Bank Accounts</h2>
            <form method="post" action="{{ route('erp.settings.bank-accounts.save') }}" class="settings-form settings-edit-form mb-3">
                {{ csrf_field() }}<input type="hidden" name="id">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small text-muted">Bank</label><input name="bank_name" class="form-control form-control-sm" required></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Branch</label><input name="branch_name" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Account Name</label><input name="account_name" class="form-control form-control-sm" required></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Account No.</label><input name="account_no" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Currency</label><input name="currency" class="form-control form-control-sm text-uppercase" value="PHP" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">SWIFT</label><input name="swift_code" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label small text-muted">GL Account</label><select name="account_id" class="form-select form-select-sm"><option value=""></option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Book Bal.</label><input name="book_balance" class="form-control form-control-sm text-end"></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Bank Bal.</label><input name="bank_balance" class="form-control form-control-sm text-end"></div>
                    <div class="col-md-1 d-flex align-items-end"><input type="checkbox" name="is_active" value="1" class="form-check-input mb-2" checked></div>
                </div>
                <div class="text-end"><button class="btn btn-sm btn-primary">Save Bank</button></div>
            </form>
            <div class="settings-table table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Bank</th><th>Account</th><th>No.</th><th>Currency</th><th class="text-end">Book</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($bankAccounts as $bank)
                        <tr>
                            <td class="fw-semibold">{{ $bank->bank_name }}</td><td>{{ $bank->account_name }}</td><td>{{ $bank->account_no }}</td><td>{{ $bank->currency }}</td><td class="text-end">{{ number_format((float) $bank->book_balance, 2) }}</td><td>{{ $bank->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary edit-row" data-form="#bank-accounts form" data-row='@json($bank)'><i class="bi bi-pencil"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No bank accounts yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('.edit-row').on('click', function () {
            var form = $($(this).data('form'));
            var row = $(this).data('row') || {};
            form[0].reset();
            Object.keys(row).forEach(function (key) {
                var field = form.find('[name="' + key + '"]');
                if (!field.length) return;
                if (field.attr('type') === 'checkbox') {
                    field.prop('checked', !!row[key]);
                } else {
                    field.val(row[key]);
                }
            });
            form.find('[name="is_active"]').prop('checked', row.is_active === undefined ? true : !!row.is_active);
            form[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
</script>
@endpush
