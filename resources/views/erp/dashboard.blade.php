@extends('erp.layout')

@section('title', 'ERP Dashboard')
@section('page-title', 'Executive Dashboard')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Financial Command Center</h1>
            <p class="text-muted mb-0">Your dashboard is filtered by the {{ ucwords(str_replace('_', ' ', auth()->user()->role ?? 'user')) }} role.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            @if(auth()->user()->canAccess('invoices', 'view'))
                <a href="{{ route('erp.invoice.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Invoice Preview</a>
                <a href="{{ route('erp.soa.preview') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i> SOA Preview</a>
            @endif
            @if(auth()->user()->canAccess('reports', 'view'))
                <a href="{{ route('erp.export', 'dashboard-summary') }}" class="btn btn-primary"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Export</a>
            @endif
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

    @if($dashboardAccess['ar'] || $dashboardAccess['ap'])
    <div class="row g-3 mb-4">
        <div class="{{ $dashboardAccess['ar'] ? 'col-xl-8' : 'col-12' }}">
            <div class="erp-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">{{ $dashboardAccess['ar'] && $dashboardAccess['ap'] ? 'Revenue vs Expense' : ($dashboardAccess['ar'] ? 'Revenue Trend' : 'Expense Trend') }}</h2>
                        <small class="text-muted">Role-authorized monthly financial trend</small>
                    </div>
                    <span class="badge text-bg-light">FY 2026</span>
                </div>
                <div id="salesExpenseChart" style="min-height: 330px"></div>
            </div>
        </div>
        @if($dashboardAccess['ar'])
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
        @endif
    </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="erp-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Recent Transactions</h2>
                        <small class="text-muted">Ajax-ready DataTables listing</small>
                    </div>
                    @if(auth()->user()->canAccess('ar_reports', 'view'))<a href="{{ route('erp.customer-billing.aging') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></a>@endif
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
                    @foreach($quickActions as $item)
                        <div class="col-6">
                            <a href="{{ $item['route'] }}" class="btn btn-outline-secondary w-100 text-start py-3">
                                <i class="bi {{ $item['icon'] }} text-primary me-1"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
                @if(count($approvalQueue))
                <hr>
                <h3 class="h6 fw-bold mb-3">Approval Queue</h3>
                <div class="d-flex flex-column gap-2">
                    @foreach($approvalQueue as $item)
                        <div class="d-flex justify-content-between"><span>{{ $item['label'] }}</span><span class="badge text-bg-secondary">{{ $item['count'] }}</span></div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var financialChart = @json($chart);
    var agingChart = @json($aging);
    var financialSeries = [];
    @if($dashboardAccess['ar'])
    financialSeries.push({ name: 'Revenue', data: financialChart.revenue });
    @endif
    @if($dashboardAccess['ap'])
    financialSeries.push({ name: 'Expenses', data: financialChart.expenses });
    @endif
    @if($dashboardAccess['ar'] && $dashboardAccess['ap'])
    financialSeries.push({ name: 'Profit', data: financialChart.profit });
    @endif

    @if($dashboardAccess['ar'] || $dashboardAccess['ap'])
    new ApexCharts(document.querySelector('#salesExpenseChart'), {
        chart: { type: 'area', height: 330, toolbar: { show: false } },
        colors: ['#fe790d', '#2c3e50', '#22c55e'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        series: financialSeries,
        xaxis: { categories: financialChart.labels },
        yaxis: { labels: { formatter: value => 'PHP ' + Math.round(value / 1000) + 'k' } },
        fill: { type: 'gradient', gradient: { opacityFrom: .35, opacityTo: .05 } },
        grid: { borderColor: '#edf0f5' }
    }).render();
    @endif

    @if($dashboardAccess['ar'])
    new ApexCharts(document.querySelector('#agingChart'), {
        chart: { type: 'donut', height: 255 },
        labels: agingChart.map(item => item.bucket),
        colors: ['#22c55e', '#84cc16', '#f59e0b', '#ef4444', '#7f1d1d'],
        series: agingChart.map(item => Number(item.amount)),
        legend: { position: 'bottom' },
        dataLabels: { enabled: false }
    }).render();
    @endif

    $('#transactionsTable').DataTable({
        ajax: '{{ route('erp.transactions.data') }}',
        pageLength: 5,
        lengthChange: false,
        order: [[0, 'desc']],
        language: { search: '', searchPlaceholder: 'Search transactions' }
    });
</script>
@endpush
