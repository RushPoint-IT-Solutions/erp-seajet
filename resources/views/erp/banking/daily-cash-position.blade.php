@extends('erp.layout')

@section('title', 'Daily Cash Position')
@section('page-title', 'Daily Cash Position')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Daily Cash Position</h1>
            <p class="text-muted mb-0">Daily bank balance visibility by account, currency, deposits, withdrawals, and available funds.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('erp.export', 'pdf') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i>PDF</a>
            <a href="{{ route('erp.export', 'excel') }}" class="btn btn-primary"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach($accounts as $account)
            <div class="col-md-4">
                <div class="erp-card p-3 h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="text-muted small">{{ $account['bank'] }} · {{ $account['account_no'] }}</div>
                            <h2 class="h6 fw-bold mb-2">{{ $account['name'] }}</h2>
                        </div>
                        <span class="badge badge-soft align-self-start">{{ $account['currency'] }}</span>
                    </div>
                    <div class="display-6 fw-bold">{{ $account['book_balance'] }}</div>
                    <div class="text-muted small">Book cash available today</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="erp-card p-3">
        <h2 class="h6 fw-bold mb-3">Daily Movement Summary</h2>
        <div class="table-responsive">
            <table class="table table-hover align-middle cash-position-table w-100">
                <thead>
                <tr>
                    <th>Currency</th>
                    <th>Movement</th>
                    <th class="text-end">Amount</th>
                </tr>
                </thead>
                <tbody>
                @foreach($summary as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row[2], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.cash-position-table').DataTable({
        paging: false,
        info: false,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search movements' }
    });
</script>
@endpush
