@extends('erp.layout')

@section('title', 'User Status Review')
@section('page-title', 'User Status Review')

@section('content')
    <div class="erp-card p-4">
        <h1 class="h4 fw-bold mb-1">User Status Update Ready</h1>
        <p class="text-muted mb-4">This status update will be logged in the audit trail.</p>
        <div class="row g-3">
            @foreach($status as $label => $value)
                <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div><div class="fw-semibold">{{ $value }}</div></div></div>
            @endforeach
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.users.index') }}" class="btn btn-primary">Done</a>
        </div>
    </div>
@endsection
