@extends('erp.layout')

@section('title', 'Acknowledgement Receipt Preview')
@section('page-title', 'Acknowledgement Receipt Pre-printed Form')

@push('styles')
<style>
    .ar-sheet {
        position: relative;
        width: 816px;
        height: 528px;
        margin: 0 auto;
        background: #f7f4dc;
        border: 1px solid #1f2937;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        font-family: Arial, sans-serif;
        color: #111827;
        overflow: hidden;
    }
    .ar-box,
    .ar-line,
    .ar-label,
    .ar-logo,
    .ar-company {
        position: absolute;
    }
    .ar-box { border: 1px solid #111827; }
    .ar-line { height: 1px; background: #111827; }
    .ar-logo { left: 265px; top: 32px; width: 72px; height: 45px; object-fit: contain; }
    .ar-company { left: 335px; top: 34px; width: 280px; text-align: left; font-weight: 800; font-size: 13px; line-height: 1.15; }
    .ar-label { font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .ar-field { position: absolute; font-size: 12px; line-height: 1.35; white-space: pre-line; }
    .ar-field.amount { text-align: right; font-weight: 700; }
    @media print {
        @page { size: Letter landscape; margin: 0; }
        body { background: #fff !important; }
        .ar-sheet { border: 0; box-shadow: none; margin: 0; background: transparent !important; }
        .ar-box,
        .ar-line,
        .ar-label,
        .ar-logo,
        .ar-company { display: none !important; }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Content Only</button>
        <a href="{{ route('erp.export', 'acknowledgement-receipt-pdf') }}" class="btn btn-primary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
    </div>

    <div class="ar-sheet">
        <img src="{{ asset('images/image.png') }}" class="ar-logo" alt="SeaJet logo">
        <div class="ar-company">
            SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
            <span style="font-size: 10px; font-weight: 600">Unit 18 & 19 Bldg. B, Cargo Village Complex<br>Paranaque City 1704, Philippines</span>
        </div>
        <div class="ar-label" style="left: 650px; top: 95px; writing-mode: vertical-rl; transform: rotate(180deg); font-size: 16px">Acknowledgement Receipt</div>
        <div class="ar-label" style="left: 690px; top: 140px">No.</div>
        <div class="ar-field" style="left: 720px; top: 137px; color: #991b1b; font-size: 18px; font-weight: 800">0007496</div>

        <div class="ar-box" style="left: 150px; top: 88px; width: 390px; height: 114px"></div>
        <div class="ar-label" style="left: 158px; top: 96px">Received From</div>
        <div class="ar-label" style="left: 158px; top: 125px; font-size: 11px; text-transform: none">Registered Name</div>
        <div class="ar-label" style="left: 158px; top: 148px; font-size: 11px; text-transform: none">TIN:</div>
        <div class="ar-label" style="left: 158px; top: 170px; font-size: 11px; text-transform: none">Business Address:</div>

        <div class="ar-box" style="left: 548px; top: 160px; width: 116px; height: 42px"></div>
        <div class="ar-label" style="left: 555px; top: 165px; font-size: 11px; text-transform: none">Payment Date</div>
        <div class="ar-label" style="left: 555px; top: 184px; font-size: 11px; text-transform: none">Account No.</div>

        <div class="ar-box" style="left: 150px; top: 202px; width: 515px; height: 180px"></div>
        <div class="ar-line" style="left: 150px; top: 232px; width: 515px"></div>
        <div class="ar-line" style="left: 510px; top: 202px; width: 1px; height: 180px"></div>
        <div class="ar-label" style="left: 160px; top: 210px">Description of Transaction / Nature of Service</div>
        <div class="ar-label" style="left: 555px; top: 210px">Amount</div>

        <div class="ar-label" style="left: 95px; top: 405px"><span style="font-size: 24px">□</span> Check</div>
        <div class="ar-label" style="left: 250px; top: 405px"><span style="font-size: 24px">□</span> Cash</div>
        <div class="ar-label" style="left: 395px; top: 405px"><span style="font-size: 24px">□</span> Online</div>
        <div class="ar-box" style="left: 560px; top: 402px; width: 150px; height: 40px"></div>
        <div class="ar-label" style="left: 575px; top: 412px; font-size: 15px">Total Paid Amount</div>
        <div class="ar-label" style="left: 56px; top: 470px; font-size: 13px">"This document is not valid for claim of input tax"</div>
        <div class="ar-line" style="left: 590px; top: 488px; width: 180px"></div>
        <div class="ar-label" style="left: 608px; top: 498px; font-size: 12px">Cashier/Authorized Representative</div>

        <div class="ar-field" style="left: 255px; top: 123px; width: 240px">PHILUSA CORPORATION</div>
        <div class="ar-field" style="left: 185px; top: 147px; width: 240px">000-281-014-00000</div>
        <div class="ar-field" style="left: 255px; top: 168px; width: 260px">28 SHAW BLVD, KAPITOLYO 1603</div>
        <div class="ar-field" style="left: 556px; top: 162px">05/24/2026</div>
        <div class="ar-field" style="left: 165px; top: 260px; width: 320px">Collection for reimbursable charges<br>JOB-SEA-2026-0423</div>
        <div class="ar-field amount" style="left: 525px; top: 260px; width: 120px">12,800.00</div>
        <div class="ar-field amount" style="left: 590px; top: 421px; width: 100px">12,800.00</div>
    </div>
@endsection
