@extends('erp.layout')

@section('title', 'Void Billing Review')
@section('page-title', 'Void Billing Review')

@section('content')
    <div class="erp-card p-4">
        <h1 class="h4 fw-bold mb-1">Billing Void Ready</h1>
        <p class="text-muted mb-4">This controlled delete action will update billing status and preserve audit traceability.</p>
        <div class="row g-3">
            @foreach($delete as $label => $value)
                <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div><div class="fw-semibold">{{ $value }}</div></div></div>
            @endforeach
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.billings.delete', $delete['invoice_no'] ?? 'BI-2026-01042') }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('erp.billings.index') }}" class="btn btn-danger">Void Billing</a>
        </div>
    </div>
@endsection
