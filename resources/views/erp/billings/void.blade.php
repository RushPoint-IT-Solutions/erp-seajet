@extends('erp.layout')

@section('title', $action === 'void' ? 'Void Invoice' : 'Cancel Invoice')
@section('page-title', $action === 'void' ? 'Void Invoice' : 'Cancel Invoice')

@section('content')
    @php
        $isVoid = $action === 'void';
        $isFinal = in_array(strtolower((string) $record->status), ['cancelled', 'voided'], true);
    @endphp

    <div class="erp-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">{{ $isVoid ? 'Void Posted Invoice' : 'Cancel Draft Invoice' }}</h1>
                <p class="text-muted mb-0">
                    The invoice and every line item will remain in the database for audit history.
                    @if($willReverseJournal)
                        Its posted journal entry will be reversed.
                    @elseif($isVoid)
                        No posted journal exists for this legacy invoice, so only its status will be voided.
                    @endif
                </p>
            </div>
            <span class="badge badge-soft">{{ ucwords(str_replace('_', ' ', $record->status ?: 'draft')) }}</span>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted">Invoice No.</div>
                    <div class="fw-semibold">{{ $record->document_no }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted">Customer</div>
                    <div class="fw-semibold">{{ $billing['customer'] ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted">Amount</div>
                    <div class="fw-semibold">{{ $record->currency ?: 'PHP' }} {{ number_format((float) $record->grand_total, 2) }}</div>
                </div>
            </div>
        </div>

        @if($isFinal)
            <div class="alert alert-warning mb-0">This invoice is already {{ $record->status }}. No further action is allowed.</div>
            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('erp.invoices.show', $record->id) }}" class="btn btn-outline-secondary">Back to Invoice</a>
            </div>
        @else
            <form method="post" action="{{ route('erp.billings.void.store', $record->id) }}">
                @csrf
                <div>
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required minlength="5" maxlength="1000" placeholder="Enter the business reason for this {{ $isVoid ? 'void' : 'cancellation' }}.">{{ old('reason') }}</textarea>
                    <div class="form-text">This reason is written to the invoice audit trail.</div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('erp.invoices.show', $record->id) }}" class="btn btn-outline-secondary">Back</a>
                    <button class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>{{ $willReverseJournal ? 'Void Invoice and Reverse Journal' : ($isVoid ? 'Void Invoice' : 'Cancel Draft Invoice') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection
