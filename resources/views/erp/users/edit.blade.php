@extends('erp.layout')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Edit User {{ $userId }}</h1>
            <p class="text-muted mb-0">Update user role, department, branch, and access scope with audit trail.</p>
        </div>
        <a href="{{ route('erp.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Users</a>
    </div>

    <form method="post" action="{{ route('erp.users.review') }}" class="erp-card p-4">
        {{ csrf_field() }}
        @include('erp.users.partials.form', ['mode' => 'edit', 'userId' => $userId])
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Review Changes</button>
        </div>
    </form>
@endsection
