@extends('erp.layout')

@section('title', 'Review AP Payment')
@section('page-title', 'Review AP Payment')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">AP Payment Ready</h1>
            <p class="text-muted mb-0">Payment details captured for approval, voucher printing, and check processing.</p>
        </div>
        <div class="d-flex gap-2">
            @if(strcasecmp($payment['payment_method'] ?? '', 'Cheque') === 0 && in_array(strtolower($payment['approval_status'] ?? ''), ['approved', 'released'], true))
                <a href="{{ route('erp.payables.check-voucher', $payment['id']) }}" class="btn btn-primary"><i class="bi bi-printer me-1"></i> Print Check Voucher</a>
            @else
                <a href="{{ route('erp.payables.check-voucher', $payment['id'] ?? null) }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Check Voucher</a>
                <a href="{{ route('erp.payables.approvals') }}" class="btn btn-primary"><i class="bi bi-shield-check me-1"></i> Approval Queue</a>
            @endif
        </div>
    </div>
    <div class="erp-card p-4">
        <div class="row g-3">
            @foreach($payment as $label => $value)
                <div class="col-md-4">
                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        @if(isset($attachments) && count($attachments))
            <hr>
            <h2 class="h6 fw-bold">Attached Documents</h2>
            <div class="d-flex flex-wrap gap-2">
                @foreach($attachments as $attachment)
                    <span class="badge badge-soft py-2 px-3"><i class="bi bi-paperclip me-1"></i>{{ $attachment->original_name }}</span>
                @endforeach
            </div>
        @endif
    </div>
@endsection
