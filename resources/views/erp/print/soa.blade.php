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
        font-family: {!! $settings['font_stack'] ?? 'Calibri, Arial, Helvetica, sans-serif' !!};
        color: #000;
        overflow: hidden;
    }
    .form-sheet,
    .form-sheet * {
        font-family: {!! $settings['font_stack'] ?? 'Calibri, Arial, Helvetica, sans-serif' !!} !important;
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
        color: #000;
        font-weight: 700;
        line-height: 1.35;
        white-space: pre-line;
        box-sizing: border-box;
        overflow: hidden;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .print-field.fit-single-line {
        min-height: 14px;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
    }
    .print-field.fit-multiline {
        line-height: 1.12;
    }
    .print-field-customer,
    .print-field-address {
        max-height: 42px;
    }
    .print-field-particulars,
    .print-field-amounts {
        max-height: 395px;
    }
    .soa-item-lines {
        position: absolute;
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-height: 395px;
        overflow: hidden;
        box-sizing: border-box;
    }
    .soa-item-line {
        display: grid;
        align-items: start;
        line-height: 1.12;
        break-inside: avoid;
    }
    .soa-item-description {
        white-space: pre-line;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .soa-item-amount {
        text-align: right;
        white-space: nowrap;
    }
    .print-field-payment_note {
        max-height: 80px;
    }
    .soa-payment-note-line {
        position: absolute;
        box-sizing: border-box;
        color: #000;
        font-weight: 700;
        overflow: hidden;
        white-space: nowrap;
        line-height: 1.1;
    }
    .soa-payment-note-small {
        font-size: 8px;
        letter-spacing: 0;
    }
    .soa-payment-note-amount {
        font-size: 10px;
        font-weight: 700;
    }
    .soa-payment-note-amount > span:last-child {
        position: absolute;
        left: var(--soa-payment-note-amount-left);
        white-space: nowrap;
    }
    @foreach($hiddenFields as $hiddenField)
    .print-field-{{ $hiddenField }} {
        display: none !important;
    }
    @endforeach
    .print-field.amount {
        text-align: right;
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
    @include('erp.partials.paid-indicator', ['paymentStatus' => $print['payment_status']['soa'] ?? []])
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
        $soaLines = collect($print['soa_lines'] ?? [])
            ->filter(function ($line) {
                return trim((string) ($line['description'] ?? '')) !== '' || (float) ($line['amount'] ?? 0) !== 0.0;
            })
            ->values();
        $billingInvoiceAmount = max(0, (float) ($print['billing_total'] ?? 0) - (float) ($print['withholding_tax'] ?? 0));
        $soaBTotal = (float) ($print['reimbursement_total'] ?? 0);
        $combinedPayableTotal = $billingInvoiceAmount + $soaBTotal;
        $paymentNoteLeft = $p['payment_note'][0];
        $paymentNoteTop = $p['payment_note'][1];
        $paymentNoteWidth = $s['payment_note']['width'];
        $paymentNoteFontSize = (int) $s['payment_note']['font_size'];
        $paymentNoteSmallFontSize = max(6, $paymentNoteFontSize - 4);
        $paymentNoteAmountFontSize = max(6, $paymentNoteFontSize - 2);
        // Align the amount column beneath "TOGETHER" in the instruction above.
        $paymentNoteAmountOffset = min(
            max(0, $paymentNoteWidth - 100),
            max(120, $paymentNoteSmallFontSize * 20)
        );
        $paymentNoteLineStep = max(15, $paymentNoteFontSize + 3);
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
        <div class="print-field fit-single-line serial print-field-serial" data-fit-text style="left: {{ $p['serial'][0] }}px; top: {{ $p['serial'][1] }}px; {{ $fieldStyle('serial') }}">{{ $print['soa_no'] ?? '007187' }}</div>

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

        <div class="print-field fit-multiline print-field-customer" data-fit-text style="left: {{ $p['customer'][0] }}px; top: {{ $p['customer'][1] }}px; {{ $fieldStyle('customer') }}">{{ $print['customer'] }}</div>
        <div class="print-field fit-multiline print-field-address" data-fit-text style="left: {{ $p['address'][0] }}px; top: {{ $p['address'][1] }}px; {{ $fieldStyle('address') }}">{{ $print['address'] }}</div>
        <div class="print-field fit-single-line print-field-date" data-fit-text style="left: {{ $p['date'][0] }}px; top: {{ $p['date'][1] }}px; {{ $fieldStyle('date') }}">{{ $print['invoice_date'] }}</div>
        @if($soaLines->isNotEmpty())
            <div class="soa-item-lines" style="left: {{ $p['particulars'][0] }}px; top: {{ min($p['particulars'][1], $p['amounts'][1]) }}px; width: {{ max($s['particulars']['width'], ($p['amounts'][0] - $p['particulars'][0]) + $s['amounts']['width']) }}px; font-size: {{ $s['particulars']['font_size'] }}px">
                @foreach($soaLines as $line)
                    <div class="soa-item-line" style="grid-template-columns: {{ $s['particulars']['width'] }}px {{ $s['amounts']['width'] }}px; column-gap: {{ max(0, $p['amounts'][0] - ($p['particulars'][0] + $s['particulars']['width'])) }}px">
                        <div class="soa-item-description print-field-particulars" style="font-size: {{ $s['particulars']['font_size'] }}px">{{ $line['description'] ?? '' }}</div>
                        <div class="soa-item-amount print-field-amounts" style="font-size: {{ $s['amounts']['font_size'] }}px">{{ (float) ($line['amount'] ?? 0) !== 0.0 ? number_format((float) $line['amount'], 2) : '' }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="print-field fit-multiline print-field-particulars" data-fit-text data-min-font="6" style="left: {{ $p['particulars'][0] }}px; top: {{ $p['particulars'][1] }}px; {{ $fieldStyle('particulars') }}">{{ $soaParticulars }}</div>
            <div class="print-field fit-multiline amount print-field-amounts" data-fit-text data-min-font="6" style="left: {{ $p['amounts'][0] }}px; top: {{ $p['amounts'][1] }}px; {{ $fieldStyle('amounts') }}">{{ $soaAmounts }}</div>
        @endif
        <div class="soa-payment-note-line soa-payment-note-small print-field-payment_note" style="left: {{ $paymentNoteLeft }}px; top: {{ $paymentNoteTop }}px; width: {{ $paymentNoteWidth }}px; font-size: {{ $paymentNoteSmallFontSize }}px">
            PLEASE PAY THIS REIMBURSEMENT CHARGES TOGETHER WITH INVOICE NUMBER {{ $print['invoice_no'] ?? '' }}
        </div>
        <div class="soa-payment-note-line soa-payment-note-amount" style="left: {{ $paymentNoteLeft }}px; top: {{ $paymentNoteTop + $paymentNoteLineStep }}px; width: {{ $paymentNoteWidth }}px; font-size: {{ $paymentNoteAmountFontSize }}px; --soa-payment-note-amount-left: {{ $paymentNoteAmountOffset }}px">
            <span>TOTAL INVOICE AMOUNT</span><span>{{ $money($billingInvoiceAmount) }}</span>
        </div>
        <div class="soa-payment-note-line soa-payment-note-amount" style="left: {{ $paymentNoteLeft }}px; top: {{ $paymentNoteTop + ($paymentNoteLineStep * 2) }}px; width: {{ $paymentNoteWidth }}px; font-size: {{ $paymentNoteAmountFontSize }}px; --soa-payment-note-amount-left: {{ $paymentNoteAmountOffset }}px">
            <span>REIMBURSEMENT CHARGES</span><span>{{ $money($print['reimbursement_total'] ?? 0) }}</span>
        </div>
        <div class="soa-payment-note-line soa-payment-note-amount" style="left: {{ $paymentNoteLeft }}px; top: {{ $paymentNoteTop + ($paymentNoteLineStep * 3) }}px; width: {{ $paymentNoteWidth }}px; font-size: {{ $paymentNoteAmountFontSize }}px; --soa-payment-note-amount-left: {{ $paymentNoteAmountOffset }}px">
            <span>PLEASE PAY THIS AMOUNT</span><span>{{ $money($combinedPayableTotal) }}</span>
        </div>
        <div class="soa-payment-note-line soa-payment-note-small" style="left: {{ $paymentNoteLeft }}px; top: {{ $paymentNoteTop + ($paymentNoteLineStep * 4) + 3 }}px; width: {{ $paymentNoteWidth }}px; font-size: {{ $paymentNoteSmallFontSize }}px; font-style: italic">
            PLEASE DO NOT DEDUCT 2% WITHHOLDING TAX ON REIMBURSEMENT CHARGES.
        </div>
        <div class="print-field fit-single-line amount print-field-total_reimbursement" data-fit-text style="left: {{ $p['total_reimbursement'][0] }}px; top: {{ $p['total_reimbursement'][1] }}px; {{ $fieldStyle('total_reimbursement') }}">{{ number_format($soaBTotal, 2) }}</div>
        <div class="print-field fit-single-line print-field-prepared_by" data-fit-text style="left: {{ $p['prepared_by'][0] }}px; top: {{ $p['prepared_by'][1] }}px; {{ $fieldStyle('prepared_by') }}; text-align: center">{{ $print['prepared_by'] ?: (auth()->user()->name ?? 'User') }}</div>
        <div class="print-field fit-multiline print-field-remarks" data-fit-text data-min-font="6" style="left: {{ $p['remarks'][0] }}px; top: {{ $p['remarks'][1] }}px; {{ $fieldStyle('remarks') }}">{{ $print['remarks'] ?? '' }}</div>
        @foreach($customFields as $custom)
            <div class="print-field fit-multiline" data-fit-text data-min-font="6" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px; max-height: 120px">{{ $custom['text'] }}</div>
        @endforeach
    </div>
    <script>
        (function () {
            function fitPrintableText() {
                document.querySelectorAll('[data-fit-text]').forEach(function (field) {
                    var configured = parseFloat(field.dataset.configuredFont || field.style.fontSize || window.getComputedStyle(field).fontSize);
                    field.dataset.configuredFont = configured;
                    field.style.fontSize = configured + 'px';
                });
            }

            window.addEventListener('load', fitPrintableText);
            window.addEventListener('beforeprint', fitPrintableText);
            window.addEventListener('resize', fitPrintableText);
        })();
    </script>
@endsection
