@extends('erp.layout')

@section('title', 'Approval Matrix')
@section('page-title', 'Approval Matrix')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Approval Matrix</h1>
            <p class="text-muted mb-0">Role-based approval levels for billing, AP, banking, FX, tax, and user access.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.payables.approvals') }}" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i>Payment Approvals</a>
            <a href="{{ route('erp.modules.show', 'users') }}" class="btn btn-outline-secondary"><i class="bi bi-people me-1"></i>Users & Access</a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">Approval Workflows</div><div class="h4 fw-bold mb-0">{{ count($approvalLevels) }}</div></div></div>
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">Active Roles</div><div class="h4 fw-bold mb-0">{{ count($rolePermissions) }}</div></div></div>
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">Pending Items</div><div class="h4 fw-bold mb-0">{{ count($pendingApprovals) }}</div></div></div>
        <div class="col-md-3"><div class="erp-card p-3"><div class="text-muted small">Audit Trail</div><div class="h4 fw-bold mb-0">Enabled</div></div></div>
    </div>

    <div class="erp-card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h6 fw-bold mb-0">Workflow Approval Rules</h2>
                <small class="text-muted">Amount thresholds, approver levels, and control notes</small>
            </div>
            <a href="{{ route('erp.export', 'approval-matrix') }}" class="btn btn-sm btn-outline-secondary no-print"><i class="bi bi-file-earmark-excel me-1"></i>Export</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle approval-table w-100">
                <thead>
                <tr>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Prepared By</th>
                    <th>Level 1</th>
                    <th>Final Approval</th>
                    <th>Threshold</th>
                    <th>Control Notes</th>
                </tr>
                </thead>
                <tbody>
                @foreach($approvalLevels as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Role Permissions</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle role-table w-100">
                        <thead><tr><th>Role</th><th>Access Scope</th><th>Approval Rights</th><th>Notes</th></tr></thead>
                        <tbody>
                        @foreach($rolePermissions as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="erp-card p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Pending Approval Queue</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle pending-table w-100">
                        <thead><tr><th>Type</th><th>Reference</th><th>Party</th><th>Shipment</th><th class="text-end">Amount</th><th>Approver</th></tr></thead>
                        <tbody>
                        @foreach($pendingApprovals as $row)
                            <tr>
                                @foreach($row as $index => $cell)
                                    <td class="{{ $index === 4 ? 'text-end fw-semibold' : '' }}">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.approval-table, .role-table, .pending-table').DataTable({
        pageLength: 8,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search approvals' }
    });
</script>
@endpush
