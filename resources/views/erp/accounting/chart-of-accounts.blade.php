@extends('erp.layout')

@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Chart of Accounts</h1>
            <p class="text-muted mb-0">Account structure for assets, liabilities, equity, revenue, and expenses.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.accounting.journals') }}" class="btn btn-primary"><i class="bi bi-journal-plus me-1"></i> Journal Entry</a>
            <a href="{{ route('erp.export', 'chart-of-accounts') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Category</th>
                    <th>Parent Account</th>
                    <th>Normal Balance</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($accounts as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account[0] }}</td>
                        <td>{{ $account[1] }}</td>
                        <td>{{ $account[2] }}</td>
                        <td>{{ $account[3] }}</td>
                        <td>{{ $account[4] }}</td>
                        <td class="text-end">{{ $account[5] }}</td>
                        <td><span class="badge badge-soft">{{ $account[6] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({
        pageLength: 25,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search accounts' }
    });
</script>
@endpush
