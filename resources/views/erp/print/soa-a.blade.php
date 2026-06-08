@extends('erp.layout')

@section('title', 'Statement of Account A')
@section('page-title', 'Statement of Account A')

@push('styles')
<style>
    .soa-a-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        margin: 0 auto;
        background: #fff;
        @if($settings['template_url'])
        background-image: url('{{ $settings['template_url'] }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        @endif
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
    @foreach($hiddenFields as $hiddenField)
    .print-field-{{ $hiddenField }} {
        display: none !important;
    }
    @endforeach
    .soa-a-sheet .amount { text-align: right; font-weight: 600; }
    @media print {
        @page { size: {{ $settings['page_css'] }}; margin: 0; }
        body { background: #fff !important; }
        .soa-a-sheet {
            border: 0;
            box-shadow: none;
            margin: 0;
            width: {{ $settings['paper_width'] }}px;
            height: {{ $settings['paper_height'] }}px;
            @if(!$settings['print_template'])
            background: transparent !important;
            @endif
        }
        .soa-a-sheet .line,
        .soa-a-sheet .box,
        .soa-a-sheet .label,
        .soa-a-sheet .logo,
        .soa-a-sheet .company { display: none !important; }
    }
</style>
@endpush

@section('content')
    @php
        $p = $positions;
        $s = $fieldStyles;
        $fieldStyle = function ($field) use ($s) {
            return 'width: '.$s[$field]['width'].'px; font-size: '.$s[$field]['font_size'].'px';
        };
        $soaParticulars = trim($print['line_description'] ?? '') !== ''
            ? $print['line_description']
            : "OCEAN FREIGHT\nBUNKER SURCHARGE\nSOLAS ADMIN\n5% COLLECT FEE";
        $soaAmounts = trim($print['billing_amounts'] ?? '') !== ''
            ? $print['billing_amounts']
            : number_format((float) ($print['billing_total'] ?? $print['soa_total'] ?? 0), 2);
    @endphp

    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.billings.print-layout.soa-a', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i> Adjust Layout</a>
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
        <div class="field print-field-serial" style="left: {{ $p['serial'][0] }}px; top: {{ $p['serial'][1] }}px; {{ $fieldStyle('serial') }}; color: #991b1b; font-weight: 800">{{ $print['soa_no'] ?? '0907' }}</div>

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

        <div class="field print-field-particulars" style="left: {{ $p['particulars'][0] }}px; top: {{ $p['particulars'][1] }}px; {{ $fieldStyle('particulars') }}">{{ $soaParticulars }}</div>
        <div class="field amount print-field-amounts" style="left: {{ $p['amounts'][0] }}px; top: {{ $p['amounts'][1] }}px; {{ $fieldStyle('amounts') }}">{{ $soaAmounts }}</div>
        <div class="field amount print-field-total_reimbursement" style="left: {{ $p['total_reimbursement'][0] }}px; top: {{ $p['total_reimbursement'][1] }}px; {{ $fieldStyle('total_reimbursement') }}">{{ number_format((float) ($print['soa_total'] ?? 0), 2) }}</div>
        <div class="field" style="left: 92px; top: 228px; width: 360px; font-size: 12px">{{ $print['customer'] ?? '' }}</div>
        <div class="field" style="left: 92px; top: 248px; width: 360px; font-size: 11px">{{ $print['address'] ?? '' }}</div>
        <div class="field" style="left: 645px; top: 228px; width: 90px; font-size: 12px">{{ $print['invoice_date'] ?? '' }}</div>
        <div class="field" style="left: 92px; top: 282px; width: 520px; font-size: 11px">
            Invoice: {{ $print['invoice_no'] ?? '' }}{{ !empty($print['job_shipment_id']) ? ' | Job: '.$print['job_shipment_id'] : '' }}
        </div>
        @foreach($customFields as $custom)
            <div class="field" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px">{{ $custom['text'] }}</div>
        @endforeach
    </div>
@endsection
