@extends('erp.layout')

@section('title', 'Billing Invoice Print')
@section('page-title', 'Billing Invoice Print')

@push('styles')
<style>
    .bi-print-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        box-sizing: border-box;
        margin: 0 auto;
        background: #fff;
        @if($settings['template_url'])
        background-image: url('{{ $settings['template_url'] }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        @endif
        border: 1px solid #222;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        font-family: Calibri, Arial, Helvetica, sans-serif;
        color: #1f2937;
        overflow: hidden;
    }
    .bi-print-sheet,
    .bi-print-sheet * {
        font-family: Calibri, Arial, Helvetica, sans-serif !important;
    }
    .bi-template-guide,
    .bi-guide-line,
    .bi-guide-vline,
    .bi-guide-box {
        position: absolute;
        pointer-events: none;
        border-color: #111;
    }
    .bi-template-guide { left: 48px; top: 214px; width: 720px; height: 710px; border: 1px solid #111; }
    .bi-guide-line { left: 48px; width: 720px; height: 1px; background: #111; }
    .bi-guide-vline { top: 282px; height: 642px; width: 1px; background: #111; }
    .bi-guide-box { border: 1px solid #111; }
    .bi-field {
        position: absolute;
        box-sizing: border-box;
        padding: 0;
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        line-height: .92;
        white-space: pre-line;
        overflow: hidden;
        overflow-wrap: anywhere;
        word-break: break-word;
        color: #000;
        font-weight: 700;
    }
    .bi-field.fit-single-line {
        align-items: center;
        min-height: 14px;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
    }
    .bi-field.fit-multiline {
        line-height: 1.08;
    }
    .print-field-customer,
    .print-field-address {
        max-height: 42px;
    }
    .print-field-origin_dest,
    .print-field-commodity {
        max-height: 24px;
    }
    .print-field-line_description {
        max-height: 360px;
    }
    @foreach($hiddenFields as $hiddenField)
    .print-field-{{ $hiddenField }} {
        display: none !important;
    }
    @endforeach
    .bi-field.amount {
        text-align: right;
        justify-content: flex-end;
    }
    .bi-field.header-value {
        font-weight: 700;
        color: #9c1c1c;
    }
    .print-field-vatable_sales,
    .print-field-vat,
    .print-field-zero_rated_sales,
    .print-field-vat_exempt_sales,
    .print-field-total_sales,
    .print-field-less_vat,
    .print-field-amount_net_vat,
    .print-field-discount,
    .print-field-add_vat,
    .print-field-withholding_tax,
    .print-field-amount_due,
    .print-field-prepared_by {
        color: #000 !important;
        font-weight: 800 !important;
    }
    .bi-label-preview {
        position: absolute;
        font-size: 11px;
        color: rgba(0,0,0,.55);
        text-transform: uppercase;
        letter-spacing: .02em;
    }
    .bi-company-preview {
        position: absolute;
        top: 28px;
        left: 190px;
        right: 190px;
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        letter-spacing: .02em;
    }
    .bi-logo-preview {
        position: absolute;
        top: 26px;
        left: 70px;
        width: 92px;
        height: 64px;
        object-fit: contain;
    }
    .bi-title-preview {
        position: absolute;
        top: 91px;
        right: 60px;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: .04em;
    }
    @if($settings['template_url'])
    .bi-template-guide,
    .bi-guide-line,
    .bi-guide-vline,
    .bi-guide-box,
    .bi-label-preview,
    .bi-company-preview,
    .bi-logo-preview,
    .bi-title-preview {
        display: none !important;
    }
    @endif
    @media print {
        @page {
            size: {{ $settings['page_css'] }};
            margin: 0;
        }
        body {
            background: #fff !important;
        }
        .bi-print-sheet {
            width: {{ $settings['paper_width'] }}px;
            height: {{ $settings['paper_height'] }}px;
            border: 0;
            box-shadow: none;
            @if(!$settings['print_template'])
            background: transparent !important;
            @endif
            margin: 0;
        }
        .bi-template-guide,
        .bi-guide-line,
        .bi-guide-vline,
        .bi-label-preview,
        .bi-company-preview,
        .bi-logo-preview,
        .bi-title-preview {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.billings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-table me-1"></i> Billing CRUD</a>
        <a href="{{ route('erp.billings.print-layout.invoice', request()->query()) }}" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i> Adjust Layout</a>
        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> {{ $settings['print_template'] ? 'Print With Template' : 'Print Content Only' }}</button>
        <a href="{{ route('erp.export', 'billing-invoice-pdf') }}" class="btn btn-primary"><i class="bi bi-filetype-pdf me-1"></i> View PDF</a>
    </div>

    @php
        $p = $positions;
        $s = $fieldStyles;
        $fieldStyle = function ($field) use ($s) {
            return 'width: '.$s[$field]['width'].'px; font-size: '.$s[$field]['font_size'].'px';
        };
        $currency = $print['currency'] ?? 'PHP';
        $money = function ($amount) use ($currency) {
            return trim($currency.' '.number_format((float) $amount, 2));
        };
        $plainAmount = function ($amount) {
            return number_format((float) $amount, 2);
        };
        $lineDescription = $print['line_description'] ?? '';
        $billingLines = $print['billing_lines'] ?? [];
    @endphp

    <div class="bi-print-sheet">
        <img src="{{ asset('images/image.png') }}" class="bi-logo-preview" alt="SeaJet logo">
        <div class="bi-company-preview">
            SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
            <span style="font-size: 10px; font-weight: 600">UNIT 1 ESSEL PARK BLDG. MC ARTHUR HI-WAY TELABASTAGAN<br>CITY OF SAN FERNANDO, PAMPANGA, PHILIPPINES 2000<br>TEL NO. 045 6522632<br>VAT REG. TIN: 000-143-896-00001</span>
        </div>
        <div class="bi-title-preview">BILLING INVOICE</div>
        <div class="bi-label-preview" style="left: 625px; top: 130px; color: #9c1c1c; font-size: 18px; font-weight: 700; text-transform: none">No</div>
        <div class="bi-template-guide"></div>
        <div class="bi-guide-box" style="left: 660px; top: 165px; width: 108px; height: 34px"></div>
        <div class="bi-guide-line" style="top: 242px"></div>
        <div class="bi-guide-line" style="top: 282px"></div>
        <div class="bi-guide-line" style="top: 306px"></div>
        <div class="bi-guide-line" style="top: 924px"></div>
        <div class="bi-guide-vline" style="left: 430px"></div>
        <div class="bi-guide-vline" style="left: 540px"></div>
        <div class="bi-guide-vline" style="left: 660px"></div>

        <div class="bi-label-preview" style="left: 55px; top: 118px">Cash Sales</div>
        <div class="bi-label-preview" style="left: 165px; top: 118px">Charge Sales</div>
        <div class="bi-label-preview" style="left: 55px; top: 148px">Customer</div>
        <div class="bi-label-preview" style="left: 55px; top: 175px">Address</div>
        <div class="bi-label-preview" style="left: 55px; top: 202px">TIN</div>
        <div class="bi-label-preview" style="left: 55px; top: 244px">MAWB B/L No.:</div>
        <div class="bi-label-preview" style="left: 55px; top: 270px">HAWB B/L No.:</div>
        <div class="bi-label-preview" style="left: 55px; top: 296px">Date Shipped:</div>
        <div class="bi-label-preview" style="left: 350px; top: 244px">Origin/Dest'n:</div>
        <div class="bi-label-preview" style="left: 350px; top: 270px">Commodity:</div>
        <div class="bi-label-preview" style="left: 60px; top: 288px; font-weight: 800">Item Description/ Nature of Service</div>
        <div class="bi-label-preview" style="left: 445px; top: 288px; font-weight: 800">Quantity</div>
        <div class="bi-label-preview" style="left: 565px; top: 288px; font-weight: 800">Unit Price</div>
        <div class="bi-label-preview" style="left: 705px; top: 288px; font-weight: 800">Amount</div>
        <div class="bi-label-preview" style="left: 620px; top: 170px">Date:</div>
        <div class="bi-label-preview" style="left: 65px; top: 790px; text-transform: none">VATable Sales</div>
        <div class="bi-label-preview" style="left: 65px; top: 815px; text-transform: none">VAT</div>
        <div class="bi-label-preview" style="left: 65px; top: 840px; text-transform: none">Zero-Rated Sales</div>
        <div class="bi-label-preview" style="left: 65px; top: 865px; text-transform: none">VAT-Exempt Sales</div>
        <div class="bi-label-preview" style="left: 455px; top: 790px; text-transform: none">Total Sales (VAT Inclusive)</div>
        <div class="bi-label-preview" style="left: 455px; top: 815px; text-transform: none">Less VAT</div>
        <div class="bi-label-preview" style="left: 455px; top: 840px; text-transform: none">Amount Net of VAT</div>
        <div class="bi-label-preview" style="left: 455px; top: 865px; text-transform: none">Less: Discount</div>
        <div class="bi-label-preview" style="left: 455px; top: 890px; text-transform: none">Add: VAT</div>
        <div class="bi-label-preview" style="left: 455px; top: 915px; text-transform: none">Less Withholding Tax</div>
        <div class="bi-label-preview" style="left: 455px; top: 948px; font-size: 16px; font-weight: 800">PLEASE PAY THIS AMOUNT</div>
        <div class="bi-label-preview" style="left: 455px; top: 995px; font-size: 13px; font-weight: 800">SEA-JET INTERNATIONAL FORWARDERS, INC.</div>

        <div class="bi-field print-field-cash_sales" style="left: {{ $p['cash_sales'][0] }}px; top: {{ $p['cash_sales'][1] }}px; {{ $fieldStyle('cash_sales') }}">[ ]</div>
        <div class="bi-field print-field-charge_sales" style="left: {{ $p['charge_sales'][0] }}px; top: {{ $p['charge_sales'][1] }}px; {{ $fieldStyle('charge_sales') }}">[x]</div>
        <div class="bi-field fit-multiline print-field-customer" data-fit-text style="left: {{ $p['customer'][0] }}px; top: {{ $p['customer'][1] }}px; {{ $fieldStyle('customer') }}">{{ $print['customer'] }}</div>
        <div class="bi-field fit-multiline print-field-address" data-fit-text style="left: {{ $p['address'][0] }}px; top: {{ $p['address'][1] }}px; {{ $fieldStyle('address') }}">{{ $print['address'] }}</div>
        <div class="bi-field fit-single-line print-field-tin" data-fit-text style="left: {{ $p['tin'][0] }}px; top: {{ $p['tin'][1] }}px; {{ $fieldStyle('tin') }}">{{ $print['tin'] }}</div>
        <div class="bi-field fit-single-line print-field-mawb" data-fit-text style="left: {{ $p['mawb'][0] }}px; top: {{ $p['mawb'][1] }}px; {{ $fieldStyle('mawb') }}">{{ $print['mawb'] }}</div>
        <div class="bi-field fit-single-line print-field-hawb" data-fit-text style="left: {{ $p['hawb'][0] }}px; top: {{ $p['hawb'][1] }}px; {{ $fieldStyle('hawb') }}">{{ $print['hawb'] }}</div>
        <div class="bi-field fit-single-line print-field-date_shipped" data-fit-text style="left: {{ $p['date_shipped'][0] }}px; top: {{ $p['date_shipped'][1] }}px; {{ $fieldStyle('date_shipped') }}">{{ $print['date_shipped'] }}</div>
        <div class="bi-field fit-single-line header-value print-field-invoice_no" data-fit-text style="left: {{ $p['invoice_no'][0] }}px; top: {{ $p['invoice_no'][1] }}px; {{ $fieldStyle('invoice_no') }}">{{ $print['invoice_no'] }}</div>
        <div class="bi-field fit-single-line print-field-invoice_date" data-fit-text style="left: {{ $p['invoice_date'][0] }}px; top: {{ $p['invoice_date'][1] }}px; {{ $fieldStyle('invoice_date') }}">{{ $print['invoice_date'] }}</div>
        <div class="bi-field fit-multiline print-field-origin_dest" data-fit-text style="left: {{ $p['origin_dest'][0] }}px; top: {{ $p['origin_dest'][1] }}px; {{ $fieldStyle('origin_dest') }}">{{ $print['origin_dest'] }}</div>
        <div class="bi-field fit-multiline print-field-commodity" data-fit-text style="left: {{ $p['commodity'][0] }}px; top: {{ $p['commodity'][1] }}px; {{ $fieldStyle('commodity') }}">{{ $print['commodity'] }}</div>

        @if(!empty($billingLines))
            @php
                $lineOffset = 0;
                $itemGap = 3;
                $firstRowAdvance = 0;
            @endphp
            @foreach($billingLines as $line)
                @php
                    $descriptionLines = preg_split('/\r\n|\r|\n/', (string) ($line['description'] ?? '')) ?: [''];
                    $visualLineCount = 0;
                    foreach ($descriptionLines as $descriptionLine) {
                        $visualLineCount += max(1, (int) ceil(max(1, strlen($descriptionLine)) / 48));
                    }
                    $rowHeight = max(14, ($visualLineCount * 12) + 1);
                    $lineTop = $p['line_description'][1] + $lineOffset;
                    $rowAdvance = $rowHeight + $itemGap;
                    if ($loop->first) {
                        $firstRowAdvance = $rowAdvance;
                    }
                @endphp
                <div class="bi-field fit-multiline print-field-line_description" data-fit-text data-min-font="6" data-item-gap="{{ $itemGap }}" data-line-row="{{ $loop->index }}" data-line-top="{{ $lineTop }}" style="left: {{ $p['line_description'][0] }}px; top: {{ $lineTop }}px; height: {{ $rowHeight }}px; {{ $fieldStyle('line_description') }}">{{ $line['description'] }}</div>
                <div class="bi-field fit-single-line amount print-field-line_qty" data-fit-text data-line-row="{{ $loop->index }}" data-line-top="{{ $lineTop }}" style="left: {{ $p['line_qty'][0] }}px; top: {{ $lineTop }}px; {{ $fieldStyle('line_qty') }}">{{ $line['quantity'] }}</div>
                <div class="bi-field fit-single-line amount print-field-line_unit_price" data-fit-text data-line-row="{{ $loop->index }}" data-line-top="{{ $lineTop }}" style="left: {{ $p['line_unit_price'][0] }}px; top: {{ $lineTop }}px; {{ $fieldStyle('line_unit_price') }}">{{ $plainAmount($line['unit_price']) }}</div>
                <div class="bi-field fit-single-line amount print-field-line_amount" data-fit-text data-line-row="{{ $loop->index }}" data-line-top="{{ $lineTop }}" style="left: {{ $p['line_amount'][0] }}px; top: {{ $lineTop }}px; {{ $fieldStyle('line_amount') }}">{{ $plainAmount($line['amount']) }}</div>
                @php
                    $lineOffset += $rowAdvance;
                @endphp
            @endforeach
        @else
            <div class="bi-field fit-multiline print-field-line_description" data-fit-text data-min-font="6" style="left: {{ $p['line_description'][0] }}px; top: {{ $p['line_description'][1] }}px; {{ $fieldStyle('line_description') }}">{{ $lineDescription }}</div>
            <div class="bi-field fit-single-line amount print-field-line_qty" data-fit-text style="left: {{ $p['line_qty'][0] }}px; top: {{ $p['line_description'][1] }}px; {{ $fieldStyle('line_qty') }}">{{ $print['line_qty'] }}</div>
            <div class="bi-field fit-single-line amount print-field-line_unit_price" data-fit-text style="left: {{ $p['line_unit_price'][0] }}px; top: {{ $p['line_description'][1] }}px; {{ $fieldStyle('line_unit_price') }}">{{ $plainAmount($print['line_unit_price']) }}</div>
            <div class="bi-field fit-single-line amount print-field-line_amount" data-fit-text style="left: {{ $p['line_amount'][0] }}px; top: {{ $p['line_description'][1] }}px; {{ $fieldStyle('line_amount') }}">{{ $plainAmount($print['line_amount']) }}</div>
        @endif
        @php
            $printedLinesBottom = !empty($billingLines) ? $p['line_description'][1] + $lineOffset : $p['line_description'][1] + 28;
            $additionalItemsOffset = !empty($billingLines) ? max(0, $lineOffset - $firstRowAdvance) : 0;
            $soaTop = max($p['soa_no'][1] + $additionalItemsOffset, $printedLinesBottom + 8);
            $remarksTop = max($p['remarks'][1] + $additionalItemsOffset, $soaTop + 30);
        @endphp
        <div class="bi-field fit-single-line print-field-soa_no" data-fit-text data-items-shift="{{ $additionalItemsOffset }}" style="left: {{ $p['soa_no'][0] }}px; top: {{ $soaTop }}px; {{ $fieldStyle('soa_no') }}">SOA# {{ $print['soa_no'] }}</div>
        <div class="bi-field fit-multiline print-field-remarks" data-fit-text data-min-font="6" data-items-shift="{{ $additionalItemsOffset }}" style="left: {{ $p['remarks'][0] }}px; top: {{ $remarksTop }}px; {{ $fieldStyle('remarks') }}">{{ $print['remarks'] ?? '' }}</div>
        <div class="bi-field fit-single-line amount print-field-vatable_sales" data-fit-text style="left: {{ $p['vatable_sales'][0] }}px; top: {{ $p['vatable_sales'][1] }}px; {{ $fieldStyle('vatable_sales') }}">{{ $money($print['vatable_sales']) }}</div>
        <div class="bi-field fit-single-line amount print-field-vat" data-fit-text style="left: {{ $p['vat'][0] }}px; top: {{ $p['vat'][1] }}px; {{ $fieldStyle('vat') }}">{{ $money($print['vat_amount']) }}</div>
        <div class="bi-field fit-single-line amount print-field-zero_rated_sales" data-fit-text style="left: {{ $p['zero_rated_sales'][0] }}px; top: {{ $p['zero_rated_sales'][1] }}px; {{ $fieldStyle('zero_rated_sales') }}">{{ $plainAmount($print['zero_rated_sales']) }}</div>
        <div class="bi-field fit-single-line amount print-field-vat_exempt_sales" data-fit-text style="left: {{ $p['vat_exempt_sales'][0] }}px; top: {{ $p['vat_exempt_sales'][1] }}px; {{ $fieldStyle('vat_exempt_sales') }}">{{ $plainAmount($print['vat_exempt_sales']) }}</div>

        <div class="bi-field fit-single-line amount print-field-total_sales" data-fit-text style="left: {{ $p['total_sales'][0] }}px; top: {{ $p['total_sales'][1] }}px; {{ $fieldStyle('total_sales') }}">{{ $money($print['billing_total']) }}</div>
        <div class="bi-field fit-single-line amount print-field-less_vat" data-fit-text style="left: {{ $p['less_vat'][0] }}px; top: {{ $p['less_vat'][1] }}px; {{ $fieldStyle('less_vat') }}">{{ $money($print['vat_amount']) }}</div>
        <div class="bi-field fit-single-line amount print-field-amount_net_vat" data-fit-text style="left: {{ $p['amount_net_vat'][0] }}px; top: {{ $p['amount_net_vat'][1] }}px; {{ $fieldStyle('amount_net_vat') }}">{{ $money($print['billing_total'] - $print['vat_amount']) }}</div>
        <div class="bi-field fit-single-line amount print-field-discount" data-fit-text style="left: {{ $p['discount'][0] }}px; top: {{ $p['discount'][1] }}px; {{ $fieldStyle('discount') }}">{{ $plainAmount($print['discount']) }}</div>
        <div class="bi-field fit-single-line amount print-field-add_vat" data-fit-text style="left: {{ $p['add_vat'][0] }}px; top: {{ $p['add_vat'][1] }}px; {{ $fieldStyle('add_vat') }}">{{ $money($print['vat_amount']) }}</div>
        <div class="bi-field fit-single-line amount print-field-withholding_tax" data-fit-text style="left: {{ $p['withholding_tax'][0] }}px; top: {{ $p['withholding_tax'][1] }}px; {{ $fieldStyle('withholding_tax') }}">{{ $money($print['withholding_tax']) }}</div>
        <div class="bi-field fit-single-line amount header-value print-field-amount_due" data-fit-text style="left: {{ $p['amount_due'][0] }}px; top: {{ $p['amount_due'][1] }}px; {{ $fieldStyle('amount_due') }}">{{ $money(max(0, $print['billing_total'] - $print['withholding_tax'])) }}</div>
        <div class="bi-field fit-single-line print-field-prepared_by" data-fit-text style="left: {{ $p['prepared_by'][0] }}px; top: {{ $p['prepared_by'][1] }}px; {{ $fieldStyle('prepared_by') }}">{{ $print['prepared_by'] }}</div>

        @foreach($customFields as $custom)
            <div class="bi-field fit-multiline" data-fit-text data-min-font="6" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px; max-height: 120px">{{ $custom['text'] }}</div>
        @endforeach
    </div>
    <script>
        (function () {
            function fitPrintableText() {
                document.querySelectorAll('[data-fit-text]').forEach(function (field) {
                    var base = parseFloat(field.dataset.baseFont || window.getComputedStyle(field).fontSize);
                    var min = parseFloat(field.dataset.minFont || 7);
                    field.dataset.baseFont = base;
                    field.style.fontSize = base + 'px';
                    field.style.transform = '';

                    var current = base;
                    while (current > min && (field.scrollWidth > field.clientWidth + 1 || field.scrollHeight > field.clientHeight + 1)) {
                        current -= 0.5;
                        field.style.fontSize = current + 'px';
                    }
                });
            }

            window.addEventListener('load', fitPrintableText);
            window.addEventListener('beforeprint', fitPrintableText);
            window.addEventListener('resize', fitPrintableText);
        })();
    </script>
@endsection
