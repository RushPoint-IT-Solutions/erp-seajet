@extends('erp.layout')

@section('title', 'Deactivate User Review')
@section('page-title', 'Deactivate User Review')

@section('content')
    <div class="erp-card p-4">
        <h1 class="h4 fw-bold mb-1">User Deactivation Ready</h1>
        <p class="text-muted mb-4">This controlled delete action will keep audit history and mark access inactive.</p>
        <div class="row g-3">
            @foreach($delete as $label => $value)
                <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div><div class="fw-semibold">{{ $value }}</div></div></div>
            @endforeach
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.users.index') }}" class="btn btn-danger">Done</a>
        </div>
    </div>
@endsection
