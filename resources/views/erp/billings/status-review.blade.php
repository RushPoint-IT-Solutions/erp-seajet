@extends('erp.layout')

@section('title', 'Billing Status Review')
@section('page-title', 'Billing Status Review')

@section('content')
    <div class="erp-card p-4">
        <h1 class="h4 fw-bold mb-1">Posted Status Ready</h1>
        <p class="text-muted mb-4">This confirms the billing document as posted for accounting records.</p>
        <div class="row g-3">
            @foreach($status as $label => $value)
                <div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div><div class="fw-semibold">{{ $value }}</div></div></div>
            @endforeach
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.billings.status') }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('erp.billings.index') }}" class="btn btn-primary">Confirm Posted</a>
            <a href="{{ route('erp.billings.print-invoice') }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print BI</a>
            <a href="{{ route('erp.billings.print-soa') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-text me-1"></i>Print SOA</a>
        </div>
    </div>
@endsection
