@extends('erp.layout')

@section('title', $title)
@section('page-title', $title)

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $title }}</h1>
            <p class="text-muted mb-0">Generated from posted journal entries and live subledger balances.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.export', str_slug($title).'-pdf') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
            <a href="{{ route('erp.export', str_slug($title).'-excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>

    @if(!empty($tieOut) && !$tieOut['balanced'])
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <div>{{ $tieOut['label'] }} does not tie out: {{ number_format($tieOut['left'], 2) }} vs {{ number_format($tieOut['right'], 2) }}. Check for unposted or misclassified entries.</div>
        </div>
    @endif

    <div class="erp-card p-4">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
            <div>
                <div class="fw-bold">{{ $company->name ?? 'Company' }}</div>
                <div class="text-muted">{{ $periodLabel ?? '' }}</div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Currency</div>
                <div class="fw-semibold">{{ $company->base_currency ?? 'PHP' }}</div>
            </div>
        </div>
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>Section</th>
                <th>Account / Line Item</th>
                <th class="text-end">Amount</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php $isTotal = stripos($row[1], 'total') !== false || stripos($row[1], 'net income') !== false; @endphp
                <tr class="{{ $isTotal ? 'fw-bold border-top' : '' }}">
                    <td>{{ $row[0] }}</td>
                    <td>{{ $row[1] }}</td>
                    <td class="text-end">{{ $row[2] < 0 ? '(' . number_format(abs($row[2]), 2) . ')' : number_format($row[2], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No posted activity found for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if(!empty($tieOut))
            <div class="d-flex justify-content-between border-top pt-3 mt-2 {{ $tieOut['balanced'] ? 'text-success' : 'text-danger' }}">
                <div class="fw-semibold"><i class="bi {{ $tieOut['balanced'] ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>{{ $tieOut['label'] }}</div>
                <div class="fw-bold">{{ $tieOut['balanced'] ? 'Balanced' : 'Out of balance' }}</div>
            </div>
        @endif
    </div>
@endsection
