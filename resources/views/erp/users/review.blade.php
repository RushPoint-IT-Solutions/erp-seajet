@extends('erp.layout')

@section('title', 'Review User')
@section('page-title', 'Review User')

@section('content')
    <div class="erp-card p-4">
        <h1 class="h4 fw-bold mb-1">User Changes Ready</h1>
        <p class="text-muted mb-4">Review before saving to the users table and activity log.</p>
        <div class="row g-3">
            @foreach($user as $label => $value)
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                        <div class="fw-semibold">{{ $value ?: '-' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.users.create') }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('erp.users.index') }}" class="btn btn-primary">Save User</a>
        </div>
    </div>
@endsection
