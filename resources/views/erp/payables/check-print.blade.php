@extends('erp.layout')

@section('title', 'Print Check')
@section('page-title', 'Print Check')

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Check</button>
        <a href="{{ route('erp.payables.check-voucher') }}" class="btn btn-primary"><i class="bi bi-receipt me-1"></i> Voucher</a>
    </div>
    <div class="erp-card p-4 mx-auto" style="max-width: 920px">
        <div class="border rounded p-4" style="min-height: 330px">
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <div class="small text-muted">Bank Account</div>
                    <div class="fw-bold">BPI Operating - PHP</div>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Check No</div>
                    <div class="fw-bold">CHK-883121</div>
                </div>
            </div>
            <div class="mb-4">
                <div class="small text-muted">Payee</div>
                <div class="h4 fw-bold">Metro Industrial Supply</div>
            </div>
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="small text-muted">Amount in words</div>
                    <div>One Hundred Twenty One Thousand Five Hundred Five Pesos Only</div>
                </div>
                <div class="h4 fw-bold">PHP 121,505.00</div>
            </div>
            <div class="text-end mt-5">
                <span class="d-inline-block border-top pt-2" style="min-width: 240px">Authorized Signature</span>
            </div>
        </div>
    </div>
@endsection
