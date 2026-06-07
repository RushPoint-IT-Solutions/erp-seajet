@extends('erp.layout')

@section('title', 'ERP Dashboard')
@section('page-title', 'Executive Dashboard')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Financial Command Center</h1>
            <p class="text-muted mb-0">Revenue, collections, payables, cash, and banking in one SME ERP workspace.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.invoice.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Invoice Preview</a>
            <a href="{{ route('erp.soa.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i> SOA Preview</a>
            <a href="{{ route('erp.export', 'dashboard-summary') }}" class="btn btn-primary"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Export</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($kpis as $kpi)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="erp-card kpi-card p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">{{ $kpi['label'] }}</div>
                            <div class="h4 fw-bold mb-1">{{ config('erp.currency') }} {{ number_format($kpi['value'], 2) }}</div>
                            <span class="badge badge-soft">{{ $kpi['trend'] }}</span>
                        </div>
                        <span class="kpi-icon"><i class="bi {{ $kpi['icon'] }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="erp-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Revenue vs Expense</h2>
                        <small class="text-muted">Monthly financial trend</small>
                    </div>
                    <span class="badge text-bg-light">FY 2026</span>
                </div>
                <div id="salesExpenseChart" style="min-height: 330px"></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="erp-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">AR Aging Summary</h2>
                        <small class="text-muted">Customer receivables by bucket</small>
                    </div>
                    <i class="bi bi-hourglass-split text-primary"></i>
                </div>
                <div id="agingChart" style="min-height: 255px"></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        @foreach($aging as $item)
                            <tr>
                                <td>{{ $item['bucket'] }}</td>
                                <td class="text-end fw-semibold">{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="erp-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Recent Transactions</h2>
                        <small class="text-muted">Ajax-ready DataTables listing</small>
                    </div>
                    <a href="{{ route('erp.customer-billing.aging') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></a>
                </div>
                <div class="table-responsive">
                    <table id="transactionsTable" class="table table-hover align-middle w-100">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Party</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="erp-card p-3">
                <h2 class="h6 fw-bold mb-3">Quick Actions</h2>
                <div class="row g-2">
                    @foreach([
                        'Create Invoice' => ['icon' => 'bi-file-earmark-plus', 'route' => route('erp.invoices.create')],
                        'Customer Masterlist' => ['icon' => 'bi-people', 'route' => route('erp.customers.index')],
                        'Receive Payment' => ['icon' => 'bi-cash', 'route' => route('erp.payments.create')],
                        'Aging Report' => ['icon' => 'bi-hourglass-split', 'route' => route('erp.customer-billing.aging')],
                        'Journal Entry' => ['icon' => 'bi-journal-plus', 'route' => route('erp.action', 'journal-entry')],
                        'Bank Transfer' => ['icon' => 'bi-arrow-left-right', 'route' => route('erp.action', 'bank-transfer')]
                    ] as $label => $item)
                        <div class="col-6">
                            <a href="{{ $item['route'] }}" class="btn btn-outline-secondary w-100 text-start py-3">
                                <i class="bi {{ $item['icon'] }} text-primary me-1"></i>
                                <span>{{ $label }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
                <hr>
                <h3 class="h6 fw-bold mb-3">Approval Queue</h3>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between"><span>Purchase Orders</span><span class="badge text-bg-warning">7</span></div>
                    <div class="d-flex justify-content-between"><span>Supplier Bills</span><span class="badge text-bg-danger">4</span></div>
                    <div class="d-flex justify-content-between"><span>Journal Entries</span><span class="badge text-bg-secondary">3</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    new ApexCharts(document.querySelector('#salesExpenseChart'), {
        chart: { type: 'area', height: 330, toolbar: { show: false } },
        colors: ['#fe790d', '#2c3e50', '#22c55e'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        series: [
            { name: 'Revenue', data: [420000, 510000, 580000, 610000, 845230, 790000] },
            { name: 'Expenses', data: [230000, 280000, 300000, 340000, 382410, 360000] },
            { name: 'Profit', data: [190000, 230000, 280000, 270000, 462820, 430000] }
        ],
        xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'] },
        yaxis: { labels: { formatter: value => 'PHP ' + Math.round(value / 1000) + 'k' } },
        fill: { type: 'gradient', gradient: { opacityFrom: .35, opacityTo: .05 } },
        grid: { borderColor: '#edf0f5' }
    }).render();

    new ApexCharts(document.querySelector('#agingChart'), {
        chart: { type: 'donut', height: 255 },
        labels: ['Current', '30 Days', '60 Days', '90 Days', '120+ Days'],
        colors: ['#22c55e', '#84cc16', '#f59e0b', '#ef4444', '#7f1d1d'],
        series: [84200, 61700, 45900, 28900, 17900],
        legend: { position: 'bottom' },
        dataLabels: { enabled: false }
    }).render();

    $('#transactionsTable').DataTable({
        ajax: '{{ route('erp.transactions.data') }}',
        pageLength: 5,
        lengthChange: false,
        order: [[0, 'desc']],
        language: { search: '', searchPlaceholder: 'Search transactions' }
    });
</script>
@endpush
