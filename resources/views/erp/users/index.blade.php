@extends('erp.layout')

@section('title', 'Users CRUD')
@section('page-title', 'Users CRUD')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Users CRUD</h1>
            <p class="text-muted mb-0">Create, view, edit, update status, and audit ERP user access.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Invite / Create User</a>
            <a href="{{ route('erp.users.status') }}" class="btn btn-outline-secondary"><i class="bi bi-toggle-on me-1"></i>Update Status</a>
            <a href="{{ route('erp.approval-matrix') }}" class="btn btn-outline-secondary"><i class="bi bi-check2-square me-1"></i>Approval Matrix</a>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle users-table w-100">
                <thead>
                <tr>
                    <th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Branch</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user['user_id'] }}</td>
                        <td>{{ $user['name'] }}</td>
                        <td>{{ $user['email'] }}</td>
                        <td>{{ $user['role'] }}</td>
                        <td>{{ $user['department'] }}</td>
                        <td>{{ $user['branch'] }}</td>
                        <td><span class="badge badge-soft">{{ $user['status'] }}</span></td>
                        <td>{{ $user['last_login'] }}</td>
                        <td class="text-end">
                            <a href="{{ route('erp.users.edit', $user['user_id']) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('erp.users.status', ['user' => $user['user_id']]) }}" class="btn btn-sm btn-outline-secondary" title="Status"><i class="bi bi-toggle-on"></i></a>
                            <a href="{{ route('erp.users.delete', $user['user_id']) }}" class="btn btn-sm btn-outline-danger" title="Deactivate"><i class="bi bi-trash"></i></a>
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
    $('.users-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search users' }
    });
</script>
@endpush
