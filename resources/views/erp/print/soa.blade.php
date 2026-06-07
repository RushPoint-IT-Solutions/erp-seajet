@extends('erp.layout')

@section('title', 'Statement of Account B')
@section('page-title', 'Statement of Account B')

@push('styles')
<style>
    .form-sheet {
        position: relative;
        width: 816px;
        height: 1056px;
        margin: 0 auto;
        background: #f7f4df;
        border: 1px solid #2f3542;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        font-family: Arial, sans-serif;
        color: #1f2937;
        overflow: hidden;
    }
    .template-line,
    .template-box,
    .template-label,
    .template-title,
    .template-logo,
    .template-company {
        position: absolute;
    }
    .template-box {
        border: 1px solid #2f3542;
    }
    .template-line {
        height: 1px;
        background: #2f3542;
    }
    .template-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 700;
    }
    .template-logo {
        left: 210px;
        top: 42px;
        width: 82px;
        height: 50px;
        object-fit: contain;
    }
    .template-company {
        left: 300px;
        top: 38px;
        width: 310px;
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        line-height: 1.15;
    }
    .template-title {
        left: 335px;
        top: 134px;
        font-size: 17px;
        font-weight: 800;
        letter-spacing: .04em;
    }
    .print-field {
        position: absolute;
        font-size: 12px;
        line-height: 1.35;
        white-space: pre-line;
    }
    .print-field.amount {
        text-align: right;
        font-weight: 600;
    }
    .serial {
        color: #991b1b;
        font-weight: 800;
        letter-spacing: .12em;
        font-size: 18px;
    }
    @media print {
        @page { size: Letter; margin: 0; }
        body { background: #fff !important; }
        .form-sheet {
            border: 0;
            box-shadow: none;
            margin: 0;
            background: transparent !important;
        }
        .template-line,
        .template-box,
        .template-label,
        .template-title,
        .template-logo,
        .template-company,
        .serial-label {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
    @php
        $money = function ($amount) {
            return 'PHP '.number_format((float) $amount, 2);
        };
    @endphp

    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.billings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-table me-1"></i> Billing CRUD</a>
        <a href="{{ route('erp.billings.status') }}" class="btn btn-outline-secondary"><i class="bi bi-toggle-on me-1"></i> Update Status</a>
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Content Only</button>
        <a href="{{ route('erp.action', 'email-soa') }}" class="btn btn-primary"><i class="bi bi-envelope me-1"></i> Email SOA</a>
    </div>

    <div class="form-sheet">
        <img src="{{ asset('images/image.png') }}" class="template-logo" alt="SeaJet logo">
        <div class="template-company">
            SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
            <span style="font-size: 10px; font-weight: 600">Unit 18 & 19 Bldg. B, Cargo Village Complex<br>Paranaque City 1704, Philippines</span>
        </div>
        <div class="template-title">STATEMENT OF ACCOUNT</div>
        <div class="template-label serial-label" style="left: 640px; top: 137px">B No.</div>
        <div class="print-field serial" style="left: 692px; top: 134px">007187</div>

        <div class="template-box" style="left: 70px; top: 180px; width: 330px; height: 78px"></div>
        <div class="template-label" style="left: 82px; top: 190px; letter-spacing: 0">CUSTOMER:</div>
        <div class="template-label" style="left: 82px; top: 220px; letter-spacing: 0">ADDRESS:</div>
        <div class="template-label" style="left: 565px; top: 238px; letter-spacing: 0">DATE:</div>
        <div class="template-box" style="left: 610px; top: 222px; width: 175px; height: 38px"></div>

        <div class="template-box" style="left: 54px; top: 295px; width: 710px; height: 584px"></div>
        <div class="template-line" style="left: 54px; top: 319px; width: 710px"></div>
        <div class="template-line" style="left: 635px; top: 295px; width: 1px; height: 584px"></div>
        <div class="template-label" style="left: 320px; top: 302px">Particulars</div>
        <div class="template-label" style="left: 675px; top: 302px; letter-spacing: 0">Amount</div>

        <div class="template-label" style="left: 54px; top: 857px; font-size: 12px; font-style: italic; letter-spacing: 0">"THIS DOCUMENT IS NOT VALID FOR CLAIMING INPUT TAXES"</div>
        <div class="template-label" style="left: 275px; top: 905px; font-size: 16px; letter-spacing: 0">TOTAL REIMBURSEMENT CHARGES</div>
        <div class="template-box" style="left: 640px; top: 892px; width: 124px; height: 36px"></div>
        <div class="template-label" style="left: 480px; top: 985px; font-size: 15px; letter-spacing: 0">SEA-JET INTERNATIONAL FORWARDERS, INC.</div>
        <div class="template-line" style="left: 505px; top: 1028px; width: 300px"></div>

        <div class="print-field" style="left: 152px; top: 190px; width: 230px">{{ $print['customer'] }}</div>
        <div class="print-field" style="left: 148px; top: 220px; width: 240px">28 SHAW BLVD, KAPITOLYO 1603<br>CITY OF PASIG</div>
        <div class="print-field" style="left: 618px; top: 235px; width: 155px">{{ $print['invoice_date'] }}</div>
        <div class="print-field" style="left: 115px; top: 370px; width: 455px">
            OCEAN FREIGHT (95% OF FREIGHT CHARGES)
            BUNKER SURCHARGE
            SOLAS ADMIN
            5% COLLECT FEE

            SHIPPER: LEMOINE HOLLAND B.V.
            CONT'R NO. LETU5131406/TCNU5442735/FSCU8916299
        </div>
        <div class="print-field amount" style="left: 640px; top: 370px; width: 100px">
            368,376.75
            120,022.50
            20,311.50
            9,232.50
            19,388.25
        </div>
        <div class="print-field" style="left: 60px; top: 735px; width: 510px">
            PLEASE PAY THIS REIMBURSEMENT CHARGES TOGETHER WITH INVOICE NUMBER {{ $print['invoice_no'] }}
            REIMBURSEMENT CHARGES     {{ $money($print['reimbursement_total']) }}
            INVOICE AMOUNT            {{ $money($print['billing_total']) }}
            PLEASE PAY THIS AMOUNT    {{ $money($print['soa_total']) }}
        </div>
        <div class="print-field amount" style="left: 640px; top: 899px; width: 115px">{{ number_format((float) $print['soa_total'], 2) }}</div>
        <div class="print-field" style="left: 545px; top: 1005px; width: 180px; text-align: center">MABEL PLATA</div>
    </div>
@endsection
