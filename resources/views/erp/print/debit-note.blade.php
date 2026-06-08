@extends('erp.layout')

@section('title', 'Debit Note Preview')
@section('page-title', 'Debit Note Pre-printed Form')

@push('styles')
<style>
    .dn-sheet {
        position: relative;
        width: 816px;
        height: 1056px;
        margin: 0 auto;
        background: #f8f4dc;
        border: 1px solid #1e4f86;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .14);
        font-family: Arial, sans-serif;
        color: #163b66;
        overflow: hidden;
    }
    .dn-line,
    .dn-box,
    .dn-label,
    .dn-logo,
    .dn-company {
        position: absolute;
    }
    .dn-box { border: 2px solid #1e4f86; border-radius: 10px; }
    .dn-line { height: 2px; background: #1e4f86; }
    .dn-logo { left: 275px; top: 36px; width: 72px; height: 45px; object-fit: contain; }
    .dn-company { left: 255px; top: 82px; width: 330px; text-align: center; font-weight: 800; font-size: 15px; color: #1e4f86; }
    .dn-label { font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .dn-field { position: absolute; font-size: 12px; line-height: 1.35; white-space: pre-line; color: #1f2937; }
    .dn-field.amount { text-align: right; font-weight: 700; }
    @media print {
        @page { size: Letter; margin: 0; }
        body { background: #fff !important; }
        .dn-sheet { border: 0; box-shadow: none; margin: 0; background: transparent !important; }
        .dn-line,
        .dn-box,
        .dn-label,
        .dn-logo,
        .dn-company { display: none !important; }
    }
</style>
@endpush

@section('content')
    @php
        $currency = $print['currency'] ?? 'PHP';
        $money = function ($amount) use ($currency) {
            return $currency.' '.number_format((float) $amount, 2);
        };
        $memoText = trim($print['memo'] ?? '') !== ''
            ? $print['memo']
            : (trim($print['description'] ?? '') !== '' ? $print['description'] : ($print['line_description'] ?? ''));
        $particulars = trim($memoText) !== '' ? $memoText : 'Debit memo adjustment';
        if (!empty($print['invoice_no'])) {
            $particulars .= "\nInvoice ".$print['invoice_no'];
        }
        if (!empty($print['job_shipment_id'])) {
            $particulars .= "\nShipment ".$print['job_shipment_id'];
        }
        $memoAmount = (float) ($print['soa_total'] ?? $print['billing_total'] ?? $print['line_amount'] ?? 0);
    @endphp

    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Content Only</button>
        <a href="{{ route('erp.export', 'debit-note-pdf') }}" class="btn btn-primary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
    </div>

    <div class="dn-sheet">
        <img src="{{ asset('images/image.png') }}" class="dn-logo" alt="SeaJet logo">
        <div class="dn-company">
            SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
            <span style="font-size: 10px; font-weight: 600">Unit 18 & 19 Bldg. B, Cargo Village Complex<br>Sto. Nino, Paranaque City 1704, Philippines</span>
        </div>
        <div class="dn-label" style="left: 612px; top: 116px">Debit Note No.</div>
        <div class="dn-field" style="left: 700px; top: 112px; width: 105px; color: #991b1b; font-size: 16px; font-weight: 800; text-align: right">{{ $print['invoice_no'] ?? '' }}</div>
        <div class="dn-label" style="left: 615px; top: 165px">Date:</div>
        <div class="dn-box" style="left: 655px; top: 155px; width: 132px; height: 28px"></div>

        <div class="dn-label" style="left: 80px; top: 145px">To:</div>
        <div class="dn-box" style="left: 72px; top: 136px; width: 380px; height: 78px"></div>
        <div class="dn-box" style="left: 72px; top: 232px; width: 720px; height: 60px"></div>
        <div class="dn-line" style="left: 72px; top: 330px; width: 720px; height: 28px"></div>
        <div class="dn-label" style="left: 355px; top: 337px; color: #fff; letter-spacing: .35em">Particulars</div>
        <div class="dn-label" style="left: 725px; top: 337px; color: #fff">Amount</div>
        <div class="dn-box" style="left: 72px; top: 358px; width: 720px; height: 520px; border-radius: 0"></div>
        <div class="dn-line" style="left: 690px; top: 358px; width: 2px; height: 520px"></div>
        <div class="dn-label" style="left: 530px; top: 895px; font-size: 15px">Please Pay This Amount</div>
        <div class="dn-box" style="left: 700px; top: 885px; width: 120px; height: 34px"></div>
        <div class="dn-label" style="left: 530px; top: 938px; font-size: 15px">We Will Pay This Amount</div>
        <div class="dn-box" style="left: 700px; top: 928px; width: 120px; height: 34px"></div>
        <div class="dn-label" style="left: 525px; top: 990px; font-size: 16px">Sea-Jet International Fwdrs., Inc.</div>

        <div class="dn-field" style="left: 100px; top: 158px; width: 330px">{{ $print['customer'] ?? '' }}<br>{{ $print['address'] ?? '' }}</div>
        <div class="dn-field" style="left: 662px; top: 162px">{{ $print['invoice_date'] ?? '' }}</div>
        <div class="dn-field" style="left: 112px; top: 390px; width: 520px">{{ $particulars }}</div>
        <div class="dn-field amount" style="left: 700px; top: 390px; width: 80px">
            {{ number_format($memoAmount, 2) }}
        </div>
        <div class="dn-field amount" style="left: 704px; top: 893px; width: 105px">{{ $money($memoAmount) }}</div>
        <div class="dn-field amount" style="left: 704px; top: 936px; width: 105px">{{ $money($memoAmount) }}</div>
    </div>
@endsection
