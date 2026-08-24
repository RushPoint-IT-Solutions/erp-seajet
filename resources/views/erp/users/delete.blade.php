@extends('erp.layout')

@section('title', 'Deactivate User')
@section('page-title', 'Deactivate User')

@section('content')
    <form method="post" action="{{ route('erp.users.delete.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        <h1 class="h4 fw-bold mb-1">Deactivate User {{ $userId }}</h1>
        <p class="text-muted mb-4">Users are deactivated instead of hard deleted so login and activity audit trails remain intact.</p>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">User ID</label><input name="user_id" class="form-control" value="{{ $userId }}" required></div>
            <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $record->name }}" readonly></div>
            <div class="col-md-4"><label class="form-label">Action</label><select name="delete_action" class="form-select"><option>Deactivate</option><option>Lock Account</option><option>Revoke Access</option></select></div>
            <div class="col-12"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3">Access removed by administrator request.</textarea></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-danger">Review Deactivation</button>
        </div>
    </form>
@endsection
