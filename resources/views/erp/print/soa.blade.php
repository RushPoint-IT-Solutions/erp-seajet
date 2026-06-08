@extends('erp.layout')

@section('title', 'Statement of Account B')
@section('page-title', 'Statement of Account B')

@push('styles')
<style>
    .form-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        margin: 0 auto;
        background: #f7f4df;
        @if($settings['template_url'])
        background-image: url('{{ $settings['template_url'] }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        @endif
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
    @foreach($hiddenFields as $hiddenField)
    .print-field-{{ $hiddenField }} {
        display: none !important;
    }
    @endforeach
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
        @page { size: {{ $settings['page_css'] }}; margin: 0; }
        body { background: #fff !important; }
        .form-sheet {
            border: 0;
            box-shadow: none;
            margin: 0;
            width: {{ $settings['paper_width'] }}px;
            height: {{ $settings['paper_height'] }}px;
            @if(!$settings['print_template'])
            background: transparent !important;
            @endif
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
        $p = $positions;
        $s = $fieldStyles;
        $fieldStyle = function ($field) use ($s) {
            return 'width: '.$s[$field]['width'].'px; font-size: '.$s[$field]['font_size'].'px';
        };
        $currency = $print['currency'] ?? 'PHP';
        $money = function ($amount) use ($currency) {
            return $currency.' '.number_format((float) $amount, 2);
        };
        $soaParticulars = trim($print['soa_particulars'] ?? '') !== ''
            ? $print['soa_particulars']
            : "REIMBURSABLE CHARGES";
        $soaAmounts = trim($print['soa_amounts'] ?? '') !== ''
            ? $print['soa_amounts']
            : number_format((float) ($print['reimbursement_total'] ?? 0), 2);
        $paymentNote = "PLEASE PAY THIS REIMBURSEMENT CHARGES TOGETHER WITH INVOICE NUMBER ".($print['invoice_no'] ?? '')."\n"
            ."REIMBURSEMENT CHARGES     ".$money($print['reimbursement_total'] ?? 0)."\n"
            ."INVOICE AMOUNT            ".$money($print['billing_total'] ?? 0)."\n"
            ."PLEASE PAY THIS AMOUNT    ".$money($print['soa_total'] ?? 0);
    @endphp

    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.billings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-table me-1"></i> Billing CRUD</a>
        <a href="{{ route('erp.billings.status') }}" class="btn btn-outline-secondary"><i class="bi bi-toggle-on me-1"></i> Update Status</a>
        <a href="{{ route('erp.billings.print-layout.soa-b', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i> Adjust Layout</a>
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
        <div class="print-field serial print-field-serial" style="left: {{ $p['serial'][0] }}px; top: {{ $p['serial'][1] }}px; {{ $fieldStyle('serial') }}">{{ $print['soa_no'] ?? '007187' }}</div>

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
        <div class="template-label" style="left: 330px; top: 905px; font-size: 16px; letter-spacing: 0">PLEASE PAY THIS AMOUNT</div>
        <div class="template-box" style="left: 640px; top: 892px; width: 124px; height: 36px"></div>
        <div class="template-label" style="left: 480px; top: 985px; font-size: 15px; letter-spacing: 0">SEA-JET INTERNATIONAL FORWARDERS, INC.</div>
        <div class="template-line" style="left: 505px; top: 1028px; width: 300px"></div>

        <div class="print-field print-field-customer" style="left: {{ $p['customer'][0] }}px; top: {{ $p['customer'][1] }}px; {{ $fieldStyle('customer') }}">{{ $print['customer'] }}</div>
        <div class="print-field print-field-address" style="left: {{ $p['address'][0] }}px; top: {{ $p['address'][1] }}px; {{ $fieldStyle('address') }}">{{ $print['address'] }}</div>
        <div class="print-field print-field-date" style="left: {{ $p['date'][0] }}px; top: {{ $p['date'][1] }}px; {{ $fieldStyle('date') }}">{{ $print['invoice_date'] }}</div>
        <div class="print-field print-field-particulars" style="left: {{ $p['particulars'][0] }}px; top: {{ $p['particulars'][1] }}px; {{ $fieldStyle('particulars') }}">{{ $soaParticulars }}</div>
        <div class="print-field amount print-field-amounts" style="left: {{ $p['amounts'][0] }}px; top: {{ $p['amounts'][1] }}px; {{ $fieldStyle('amounts') }}">{{ $soaAmounts }}</div>
        <div class="print-field print-field-payment_note" style="left: {{ $p['payment_note'][0] }}px; top: {{ $p['payment_note'][1] }}px; {{ $fieldStyle('payment_note') }}">{{ $paymentNote }}</div>
        <div class="print-field amount print-field-total_reimbursement" style="left: {{ $p['total_reimbursement'][0] }}px; top: {{ $p['total_reimbursement'][1] }}px; {{ $fieldStyle('total_reimbursement') }}">{{ number_format((float) $print['soa_total'], 2) }}</div>
        <div class="print-field print-field-prepared_by" style="left: {{ $p['prepared_by'][0] }}px; top: {{ $p['prepared_by'][1] }}px; {{ $fieldStyle('prepared_by') }}; text-align: center">{{ $print['prepared_by'] ?: 'MABEL PLATA' }}</div>
        @foreach($customFields as $custom)
            <div class="print-field" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px">{{ $custom['text'] }}</div>
        @endforeach
    </div>
@endsection
