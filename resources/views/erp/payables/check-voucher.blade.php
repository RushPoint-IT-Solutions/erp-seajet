@extends('erp.layout')

@section('title', 'Check Voucher')
@section('page-title', 'Check Voucher')

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Voucher</button>
        <a href="{{ route('erp.export', 'check-voucher-pdf') }}" class="btn btn-primary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
    </div>
    <div class="erp-card p-4 mx-auto" style="max-width: 920px">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Check Voucher</h1>
                <div class="text-muted">CV-2026-00241</div>
            </div>
            <div class="text-end">
                <div class="fw-bold">SeaJet Holdings Inc.</div>
                <div class="text-muted">Currency: PHP</div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="small text-muted">Supplier</div><div class="fw-semibold">Metro Industrial Supply</div></div>
            <div class="col-md-4"><div class="small text-muted">Shipment Job ID</div><div class="fw-semibold">JOB-SEA-2026-0423</div></div>
            <div class="col-md-4"><div class="small text-muted">Payment Method</div><div class="fw-semibold">Cheque</div></div>
        </div>
        <table class="table table-bordered">
            <thead><tr><th>Bill No</th><th>Description</th><th class="text-end">Gross</th><th class="text-end">WHT</th><th class="text-end">Net</th></tr></thead>
            <tbody><tr><td>BILL-2026-00492</td><td>Shipment-linked vendor expense</td><td class="text-end">127,900.00</td><td class="text-end">6,395.00</td><td class="text-end">121,505.00</td></tr></tbody>
        </table>
        <div class="row g-4 mt-5">
            <div class="col-4 text-center"><div class="border-top pt-2">Prepared By</div></div>
            <div class="col-4 text-center"><div class="border-top pt-2">Approved By</div></div>
            <div class="col-4 text-center"><div class="border-top pt-2">Received By</div></div>
        </div>
    </div>
@endsection
