@extends('erp.layout')

@section('title', 'Statement of Account A')
@section('page-title', 'Statement of Account A')

@push('styles')
<style>
    .soa-a-sheet {
        position: relative;
        width: 816px;
        height: 1056px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #111827;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        font-family: Arial, sans-serif;
        color: #111827;
        overflow: hidden;
    }
    .soa-a-sheet .line,
    .soa-a-sheet .box,
    .soa-a-sheet .label,
    .soa-a-sheet .logo,
    .soa-a-sheet .company {
        position: absolute;
    }
    .soa-a-sheet .box { border: 1px solid #111827; }
    .soa-a-sheet .line { height: 1px; background: #111827; }
    .soa-a-sheet .logo { left: 245px; top: 40px; width: 72px; height: 45px; object-fit: contain; }
    .soa-a-sheet .company { left: 320px; top: 36px; width: 310px; text-align: center; font-weight: 700; font-size: 13px; line-height: 1.15; }
    .soa-a-sheet .label { font-size: 12px; font-weight: 700; text-transform: uppercase; }
    .soa-a-sheet .field { position: absolute; font-size: 12px; line-height: 1.35; white-space: pre-line; }
    .soa-a-sheet .amount { text-align: right; font-weight: 600; }
    @media print {
        @page { size: Letter; margin: 0; }
        body { background: #fff !important; }
        .soa-a-sheet { border: 0; box-shadow: none; margin: 0; }
        .soa-a-sheet .line,
        .soa-a-sheet .box,
        .soa-a-sheet .label,
        .soa-a-sheet .logo,
        .soa-a-sheet .company { display: none !important; }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Content Only</button>
        <a href="{{ route('erp.action', 'email-soa-a') }}" class="btn btn-primary"><i class="bi bi-envelope me-1"></i> Email SOA A</a>
    </div>

    <div class="soa-a-sheet">
        <img src="{{ asset('images/image.png') }}" class="logo" alt="SeaJet logo">
        <div class="company">
            SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
            <span style="font-size: 10px; font-weight: 600">Unit 18 & 19 Bldg. B, Cargo Village Complex<br>Paranaque City 1704, Philippines</span>
        </div>
        <div class="label" style="left: 330px; top: 132px; font-size: 17px">Statement of Account</div>
        <div class="label" style="left: 635px; top: 137px">A No.</div>
        <div class="field" style="left: 690px; top: 134px; color: #991b1b; font-size: 18px; font-weight: 800">0907</div>

        <div class="box" style="left: 66px; top: 185px; width: 690px; height: 588px"></div>
        <div class="line" style="left: 66px; top: 213px; width: 690px"></div>
        <div class="line" style="left: 625px; top: 185px; width: 1px; height: 588px"></div>
        <div class="label" style="left: 310px; top: 193px; letter-spacing: .3em">Particulars</div>
        <div class="label" style="left: 666px; top: 193px">Amount</div>

        <div class="label" style="left: 62px; top: 755px; font-size: 12px; font-style: italic">"This document is not valid for claiming input taxes"</div>
        <div class="label" style="left: 280px; top: 805px; font-size: 16px">Total Reimbursement Charges</div>
        <div class="box" style="left: 635px; top: 790px; width: 120px; height: 34px"></div>
        <div class="label" style="left: 480px; top: 900px; font-size: 15px">Sea-Jet International Forwarders, Inc.</div>
        <div class="line" style="left: 505px; top: 948px; width: 300px"></div>

        <div class="field" style="left: 112px; top: 250px; width: 455px">
            OCEAN FREIGHT
            BUNKER SURCHARGE
            SOLAS ADMIN
            5% COLLECT FEE

            SHIPPER: LEMOINE HOLLAND B.V.
            CONT'R NO. LETU5131406/TCNU5442735/FSCU8916299
        </div>
        <div class="field amount" style="left: 637px; top: 250px; width: 100px">
            368,376.75
            120,022.50
            20,311.50
            9,232.50
        </div>
        <div class="field amount" style="left: 637px; top: 797px; width: 110px">518,943.25</div>
    </div>
@endsection
