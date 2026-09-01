@extends('erp.layout')

@section('title', 'Journal Entry Posted')
@section('page-title', 'Journal Entry Posted')

@section('content')
    @if(session('status'))
        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</div>
    @endif
    <div class="erp-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Journal Entry Posted</h1>
                <p class="text-muted mb-0">This entry has been written to the general ledger and is now reflected in the Trial Balance and financial statements.</p>
            </div>
            <span class="badge text-bg-success py-2 px-3 text-uppercase">{{ $journal->status }}</span>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="small text-muted">Journal No</div>
                <div class="fw-semibold">{{ $journal->journal_no }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Journal Date</div>
                <div class="fw-semibold">{{ $journal->journal_date }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Reference No</div>
                <div class="fw-semibold">{{ $journal->reference_no ?: '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Posted At</div>
                <div class="fw-semibold">{{ $journal->posted_at }}</div>
            </div>
            <div class="col-12">
                <div class="small text-muted">Memo</div>
                <div class="fw-semibold">{{ $journal->memo ?: '-' }}</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Account</th>
                    <th>Description</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                </tr>
                </thead>
                <tbody>
                @foreach($lines as $line)
                    <tr>
                        <td>{{ trim(($line->account_code ? $line->account_code.' - ' : '').$line->account_name) }}</td>
                        <td>{{ $line->description ?: '-' }}</td>
                        <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                        <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="fw-bold">
                    <td colspan="2" class="text-end">Totals</td>
                    <td class="text-end">{{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end">{{ number_format($totalCredit, 2) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>

        <hr>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.accounting.journals') }}" class="btn btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i> New Journal Entry</a>
            <a href="{{ route('erp.reports.trial-balance') }}" class="btn btn-primary"><i class="bi bi-table me-1"></i> View Trial Balance</a>
        </div>
    </div>
@endsection
