@extends('erp.layout')

@section('title', 'Update User Status')
@section('page-title', 'Update User Status')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Update User Status</h1>
            <p class="text-muted mb-0">Activate, deactivate, lock, or resend invitation with audit trail.</p>
        </div>
        <a href="{{ route('erp.users.index') }}" class="btn btn-outline-secondary">Users</a>
    </div>
    <form method="post" action="{{ route('erp.users.status.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">User ID</label><select name="user_id" class="form-select erp-select">@foreach($users as $user)<option>{{ $user['user_id'] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Name</label><select name="name" class="form-select erp-select">@foreach($users as $user)<option>{{ $user['name'] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Current Status</label><select name="current_status" class="form-select erp-select"><option>Active</option><option>Pending Invite</option><option>Inactive</option><option>Locked</option></select></div>
            <div class="col-md-4"><label class="form-label">New Status</label><select name="new_status" class="form-select erp-select"><option>Active</option><option>Pending Invite</option><option>Inactive</option><option>Locked</option></select></div>
            <div class="col-md-8"><label class="form-label">Reason</label><input name="reason" class="form-control" value="Role or employment status update"></div>
        </div>
        <div class="d-flex justify-content-end mt-4"><button class="btn btn-primary">Review Status Update</button></div>
    </form>
@endsection
