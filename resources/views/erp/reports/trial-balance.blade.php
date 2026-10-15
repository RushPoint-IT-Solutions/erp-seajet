@extends('erp.layout')

@section('title', 'Trial Balance')
@section('page-title', 'Trial Balance')

@push('styles')
<style>
    .trial-paper {
        background: #fff;
        border: 1px solid #d8dde6;
        box-shadow: 0 14px 36px rgba(15, 23, 42, .08);
        color: #111;
    }
    .trial-header {
        text-align: center;
        line-height: 1.2;
        padding: 22px 18px 12px;
        font-family: "Times New Roman", Times, serif;
    }
    .trial-company {
        font-weight: 700;
        font-size: 14px;
        text-transform: uppercase;
    }
    .trial-title {
        font-weight: 700;
        font-size: 18px;
        text-transform: uppercase;
    }
    .trial-subtitle {
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
    }
    .trial-table {
        min-width: 1080px;
        font-size: 12px;
        font-family: Arial, sans-serif;
    }
    .trial-table th {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }
    .trial-table td {
        border: 1px solid #e2e8f0;
        vertical-align: top;
        white-space: nowrap;
    }
    .trial-table .amount {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .trial-amount-button {
        appearance: none;
        border: 0;
        background: transparent;
        color: #0d6efd;
        padding: 0;
        font: inherit;
        font-variant-numeric: tabular-nums;
        text-decoration: underline;
        text-decoration-style: dotted;
        text-underline-offset: 3px;
        cursor: pointer;
    }
    .trial-amount-button:hover,
    .trial-amount-button:focus {
        color: #084298;
        text-decoration-style: solid;
    }
    .trial-drilldown-table {
        font-size: 12px;
    }
    .trial-drilldown-table th {
        white-space: nowrap;
    }
    .trial-drilldown-table td {
        vertical-align: top;
    }
    .trial-table tfoot td {
        border-top: 2px solid #111827;
        font-weight: 800;
        background: #fff7ed;
    }
    @media print {
        @page { size: A4 landscape; margin: 8mm; }
        .no-print, .erp-sidebar, .erp-topbar { display: none !important; }
        .erp-shell, .erp-main, main, section {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .trial-paper {
            border: 0;
            box-shadow: none;
        }
    }
</style>
@endpush

@section('content')
@php
    $companyName = $company && !empty($company->name) ? $company->name : 'SEA-JET INTERNATIONAL FORWARDERS, INC.';
    $money = function ($value) {
        if (!is_numeric($value)) {
            return $value;
        }
        $amount = (float) $value;
        if (abs($amount) < .005) {
            return '-';
        }
        return $amount < 0 ? '(' . number_format(abs($amount), 2) . ')' : number_format($amount, 2);
    };
    $periodLabel = preg_match('/^\d{4}-\d{2}$/', $period) ? strtoupper(date('F t, Y', strtotime($period . '-01'))) : strtoupper($period);
    $columns = $activeReport['columns'];
@endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
        <div>
            <h1 class="h3 fw-bold mb-1">Trial Balance</h1>
            <p class="text-muted mb-0">Format copied from REPORTS.xlsx, account titles from Chart of Accounts.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('erp.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Reports</a>
            <a href="{{ route('erp.reports.workbook', ['report' => 'trial-balance', 'period' => $period]) }}" class="btn btn-outline-secondary"><i class="bi bi-folder-check me-1"></i>Workbook</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <form class="erp-card p-3 mb-3 no-print" method="get" action="{{ route('erp.reports.trial-balance') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Period</label>
                <input type="month" name="period" class="form-control" value="{{ $period }}">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </div>
    </form>

    <div class="trial-paper">
        <div class="trial-header">
            <div class="trial-company">{{ $companyName }}</div>
            <div class="trial-title">TRIAL BALANCE</div>
            <div class="trial-subtitle">AS OF {{ $periodLabel }}</div>
        </div>

        <div class="table-responsive">
            <table class="table trial-table mb-0">
                <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @if(count($rows))
                    @foreach($rows as $row)
                        <tr>
                            @foreach($columns as $column)
                                @php($value = $row[$column] ?? '')
                                <td class="{{ is_numeric($value) ? 'amount' : '' }}">
                                    @if(is_numeric($value) && abs((float) $value) >= .005)
                                        <button type="button" class="trial-amount-button no-print" data-trial-drilldown data-account-id="{{ $row['__account_id'] }}" data-account="{{ $row['ACCOUNT TITLES'] }}" data-column="{{ $column }}">{{ $money($value) }}</button>
                                        <span class="d-none d-print-inline">{{ $money($value) }}</span>
                                    @else
                                        {{ is_numeric($value) ? $money($value) : $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">
                            No chart of accounts found.
                        </td>
                    </tr>
                @endif
                </tbody>
                @if(count($rows))
                    <tfoot>
                    <tr>
                        @foreach($columns as $column)
                            @php($value = $totals[$column] ?? '')
                            <td class="{{ is_numeric($value) ? 'amount' : '' }}">
                                @if(is_numeric($value) && abs((float) $value) >= .005)
                                    <button type="button" class="trial-amount-button no-print" data-trial-drilldown data-account-id="all" data-account="All accounts" data-column="{{ $column }}">{{ $money($value) }}</button>
                                    <span class="d-none d-print-inline">{{ $money($value) }}</span>
                                @else
                                    {{ is_numeric($value) ? $money($value) : $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="modal fade" id="trialBalanceDrilldownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="trialDrilldownTitle">Trial Balance Raw Data</h2>
                        <div class="small text-muted" id="trialDrilldownSubtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover trial-drilldown-table mb-0">
                            <thead class="table-light sticky-top">
                            <tr>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Journal No.</th>
                                <th>Reference</th>
                                <th>Source</th>
                                <th>Description</th>
                                <th>Encoded By</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Net Effect</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody id="trialDrilldownBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="small text-muted" id="trialDrilldownCount"></span>
                    <strong>Net total: <span id="trialDrilldownTotal">-</span></strong>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('trialBalanceDrilldownModal');
            var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            var body = document.getElementById('trialDrilldownBody');
            var title = document.getElementById('trialDrilldownTitle');
            var subtitle = document.getElementById('trialDrilldownSubtitle');
            var count = document.getElementById('trialDrilldownCount');
            var total = document.getElementById('trialDrilldownTotal');
            var endpoint = @json(route('erp.reports.trial-balance.drilldown'));
            var period = @json($period);

            function money(value) {
                var amount = Number(value) || 0;
                if (Math.abs(amount) < .005) return '-';
                var formatted = Math.abs(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                return amount < 0 ? '(' + formatted + ')' : formatted;
            }

            function cell(value, className) {
                var td = document.createElement('td');
                td.textContent = value || '-';
                if (className) td.className = className;
                return td;
            }

            document.addEventListener('click', function (event) {
                var button = event.target.closest('[data-trial-drilldown]');
                if (!button) return;

                title.textContent = button.dataset.column;
                subtitle.textContent = button.dataset.account + ' · ' + period;
                body.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>Loading raw data...</td></tr>';
                count.textContent = '';
                total.textContent = '-';
                modal.show();

                var params = new URLSearchParams({
                    period: period,
                    account_id: button.dataset.accountId,
                    column: button.dataset.column
                });

                fetch(endpoint + '?' + params.toString(), {headers: {'Accept': 'application/json'}})
                    .then(function (response) {
                        if (!response.ok) throw new Error('Unable to load the trial balance raw data.');
                        return response.json();
                    })
                    .then(function (data) {
                        body.innerHTML = '';
                        if (!data.entries.length) {
                            body.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-5">No journal lines found for this amount.</td></tr>';
                        } else {
                            data.entries.forEach(function (entry) {
                                var tr = document.createElement('tr');
                                tr.appendChild(cell(entry.date));
                                tr.appendChild(cell(entry.account));
                                tr.appendChild(cell(entry.journal_no));
                                tr.appendChild(cell(entry.reference_no));
                                tr.appendChild(cell(entry.source));
                                tr.appendChild(cell(entry.description));
                                tr.appendChild(cell(entry.encoded_by || 'Not recorded'));
                                tr.appendChild(cell(money(entry.debit), 'text-end'));
                                tr.appendChild(cell(money(entry.credit), 'text-end'));
                                tr.appendChild(cell(money(entry.effect), 'text-end fw-semibold'));
                                var statusCell = document.createElement('td');
                                var badge = document.createElement('span');
                                badge.className = 'badge ' + (entry.can_delete ? 'text-bg-success' : 'text-bg-secondary');
                                badge.textContent = entry.can_delete ? 'Can delete/void' : 'View only';
                                badge.title = entry.delete_reason || '';
                                statusCell.appendChild(badge);
                                if (entry.delete_reason) {
                                    var reason = document.createElement('div');
                                    reason.className = 'small text-muted mt-1';
                                    reason.textContent = entry.delete_reason;
                                    statusCell.appendChild(reason);
                                }
                                tr.appendChild(statusCell);

                                var actionCell = document.createElement('td');
                                actionCell.className = 'text-nowrap';
                                function addAction(url, label, className) {
                                    if (!url) return;
                                    var link = document.createElement('a');
                                    link.href = url;
                                    link.className = 'btn btn-sm ' + className + ' me-1 mb-1';
                                    link.textContent = label;
                                    actionCell.appendChild(link);
                                }
                                addAction(entry.source_url, entry.source_label, 'btn-outline-primary');
                                addAction(entry.journal_url, 'View Journal', 'btn-outline-secondary');
                                addAction(entry.delete_url, entry.delete_label, 'btn-outline-danger');
                                if (!actionCell.children.length) actionCell.textContent = '-';
                                tr.appendChild(actionCell);
                                body.appendChild(tr);
                            });
                        }
                        count.textContent = data.entries.length.toLocaleString() + (data.entries.length === 1 ? ' raw line' : ' raw lines');
                        total.textContent = money(data.total);
                    })
                    .catch(function (error) {
                        body.innerHTML = '<tr><td colspan="12" class="text-center text-danger py-5"></td></tr>';
                        body.querySelector('td').textContent = error.message;
                    });
            });
        });
    </script>
@endsection
