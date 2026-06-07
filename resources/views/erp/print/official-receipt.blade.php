@extends('erp.layout')

@section('title', 'Official Receipt Preview')
@section('page-title', 'Official Receipt Pre-printed Form')

@push('styles')
<style>
    .or-sheet {
        position: relative;
        width: 816px;
        height: 1056px;
        margin: 0 auto;
        background: #f8f4dc;
        border: 1px solid #1f2937;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        font-family: Arial, sans-serif;
        color: #111827;
        overflow: hidden;
    }
    .or-box,
    .or-line,
    .or-label,
    .or-logo,
    .or-company {
        position: absolute;
    }
    .or-box { border: 1px solid #111827; }
    .or-line { height: 1px; background: #111827; }
    .or-logo { left: 94px; top: 36px; width: 72px; height: 45px; object-fit: contain; }
    .or-company { left: 180px; top: 38px; width: 390px; text-align: center; font-weight: 800; font-size: 14px; line-height: 1.15; }
    .or-label { font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .or-field { position: absolute; font-size: 12px; line-height: 1.35; white-space: pre-line; }
    .or-field.amount { text-align: right; font-weight: 700; }
    @media print {
        @page { size: Letter; margin: 0; }
        body { background: #fff !important; }
        .or-sheet { border: 0; box-shadow: none; margin: 0; background: transparent !important; }
        .or-box,
        .or-line,
        .or-label,
        .or-logo,
        .or-company { display: none !important; }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Content Only</button>
        <a href="{{ route('erp.action', 'email-official-receipt') }}" class="btn btn-primary"><i class="bi bi-envelope me-1"></i> Email OR</a>
    </div>

    <div class="or-sheet">
        <img src="{{ asset('images/image.png') }}" class="or-logo" alt="SeaJet logo">
        <div class="or-company">
            SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
            <span style="font-size: 10px; font-weight: 600">Unit 18 & 19 Bldg. B, Cargo Village Complex<br>Paranaque City 1704, Philippines</span>
        </div>
        <div class="or-label" style="left: 620px; top: 64px; font-size: 18px">Official Receipt</div>
        <div class="or-label" style="left: 628px; top: 95px">No.</div>
        <div class="or-field" style="left: 662px; top: 91px; color: #991b1b; font-size: 18px; font-weight: 800">00618</div>
        <div class="or-label" style="left: 640px; top: 135px">Date:</div>
        <div class="or-box" style="left: 685px; top: 125px; width: 100px; height: 28px"></div>

        <div class="or-label" style="left: 70px; top: 146px">Received From</div>
        <div class="or-line" style="left: 165px; top: 163px; width: 420px"></div>
        <div class="or-label" style="left: 70px; top: 185px">Business Address</div>
        <div class="or-line" style="left: 185px; top: 202px; width: 400px"></div>
        <div class="or-label" style="left: 70px; top: 224px">TIN</div>
        <div class="or-line" style="left: 110px; top: 241px; width: 230px"></div>

        <div class="or-box" style="left: 66px; top: 285px; width: 690px; height: 440px"></div>
        <div class="or-line" style="left: 66px; top: 315px; width: 690px"></div>
        <div class="or-line" style="left: 566px; top: 285px; width: 1px; height: 440px"></div>
        <div class="or-label" style="left: 260px; top: 294px">Description / Nature of Collection</div>
        <div class="or-label" style="left: 625px; top: 294px">Amount</div>

        <div class="or-label" style="left: 90px; top: 760px"><span style="font-size: 24px">□</span> Cash</div>
        <div class="or-label" style="left: 210px; top: 760px"><span style="font-size: 24px">□</span> Check</div>
        <div class="or-label" style="left: 330px; top: 760px"><span style="font-size: 24px">□</span> Online</div>
        <div class="or-label" style="left: 500px; top: 760px; font-size: 15px">Total Paid Amount</div>
        <div class="or-box" style="left: 650px; top: 750px; width: 110px; height: 34px"></div>
        <div class="or-label" style="left: 76px; top: 825px; font-size: 13px">"This document is not valid for claim of input tax"</div>
        <div class="or-line" style="left: 500px; top: 948px; width: 250px"></div>
        <div class="or-label" style="left: 535px; top: 962px; font-size: 12px">Cashier / Authorized Representative</div>

        <div class="or-field" style="left: 170px; top: 146px; width: 370px">NORTH HARBOR TRADING</div>
        <div class="or-field" style="left: 190px; top: 185px; width: 360px">North Harbor Center, Manila</div>
        <div class="or-field" style="left: 115px; top: 224px">000-345-111-000</div>
        <div class="or-field" style="left: 690px; top: 131px">05/24/2026</div>
        <div class="or-field" style="left: 105px; top: 355px; width: 420px">Payment for Billing Invoice BI-2026-01042<br>Shipment JOB-SEA-2026-0422</div>
        <div class="or-field amount" style="left: 585px; top: 355px; width: 140px">43,200.00</div>
        <div class="or-field amount" style="left: 655px; top: 759px; width: 98px">43,200.00</div>
    </div>
@endsection
