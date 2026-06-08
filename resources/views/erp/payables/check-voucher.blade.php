@extends('erp.layout')

@section('title', 'Check Voucher')
@section('page-title', 'Check Voucher')

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Voucher</button>
        @if(!empty($voucher['id']))
            <a href="{{ route('erp.payables.check-print', $voucher['id']) }}" class="btn btn-primary"><i class="bi bi-bank me-1"></i> Print Check</a>
        @endif
    </div>
    <div class="erp-card p-4 mx-auto" style="max-width: 920px">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Check Voucher</h1>
                <div class="text-muted">{{ $voucher['voucher_no'] }}</div>
            </div>
            <div class="text-end">
                <div class="fw-bold">{{ $voucher['company'] }}</div>
                <div class="text-muted">Currency: {{ $voucher['currency'] }}</div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="small text-muted">Supplier</div><div class="fw-semibold">{{ $voucher['supplier'] }}</div></div>
            <div class="col-md-4"><div class="small text-muted">Shipment Job ID</div><div class="fw-semibold">{{ $voucher['shipment_job_id'] }}</div></div>
            <div class="col-md-4"><div class="small text-muted">Payment Method</div><div class="fw-semibold">{{ $voucher['payment_method'] }}</div></div>
        </div>
        <table class="table table-bordered">
            <thead><tr><th>Bill No</th><th>Description</th><th class="text-end">Gross</th><th class="text-end">WHT</th><th class="text-end">Net</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $voucher['bill_no'] }}</td>
                    <td>{{ $voucher['description'] }}</td>
                    <td class="text-end">{{ number_format($voucher['gross'], 2) }}</td>
                    <td class="text-end">{{ number_format($voucher['withholding'], 2) }}</td>
                    <td class="text-end">{{ number_format($voucher['net'], 2) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="row g-4 mt-5">
            <div class="col-4 text-center"><div class="border-top pt-2">Prepared By</div></div>
            <div class="col-4 text-center"><div class="border-top pt-2">Approved By</div></div>
            <div class="col-4 text-center"><div class="border-top pt-2">Received By</div></div>
        </div>
    </div>
@endsection
