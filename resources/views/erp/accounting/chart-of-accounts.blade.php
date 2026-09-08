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
            <a href="{{ route('erp.accounting.coa.create') }}" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Add Account</a>
            <a href="{{ route('erp.accounting.journals') }}" class="btn btn-primary"><i class="bi bi-journal-plus me-1"></i> Journal Entry</a>
            <a href="{{ route('erp.export', 'chart-of-accounts') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Export</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Parent Account</th>
                    <th>Normal Balance</th>
                    <th class="text-end">Beginning Balance</th>
                    <th>Status</th>
                    <th>AP Items</th>
                    <th class="text-end no-print">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($accounts as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account['code'] }}</td>
                        <td>{{ $account['name'] }}</td>
                        <td>{{ $account['type'] }}</td>
                        <td>{{ $account['category'] }}</td>
                        <td>{{ $account['parent_code'] }}</td>
                        <td>{{ $account['normal_balance'] }}</td>
                        <td class="text-end">{{ $account['opening_balance'] }}</td>
                        <td><span class="badge {{ $account['is_active'] ? 'badge-soft' : 'bg-secondary' }}">{{ $account['is_active'] ? 'Active' : 'Inactive' }}</span></td>
                        <td><span class="badge {{ $account['show_in_ap'] ? 'text-bg-success' : 'text-bg-light' }}">{{ $account['show_in_ap'] ? 'Shown' : 'Hidden' }}</span></td>
                        <td class="text-end no-print">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('erp.accounting.coa.edit', $account['id']) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" action="{{ route('erp.accounting.coa.toggle-status', $account['id']) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $account['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $account['is_active'] ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $account['is_active'] ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
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
        language: { search: '', searchPlaceholder: 'Search accounts' },
        columnDefs: [{ orderable: false, targets: -1 }]
    });
</script>
@endpush
