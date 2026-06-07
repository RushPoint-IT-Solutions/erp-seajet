@extends('erp.layout')

@section('title', 'Billing Invoice Print')
@section('page-title', 'Billing Invoice Print')

@push('styles')
<style>
    .bi-print-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        margin: 0 auto;
        background: #fbf7cf;
        @if($settings['template_url'])
        background-image: url('{{ $settings['template_url'] }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        @endif
        border: 1px solid #222;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        font-family: Arial, sans-serif;
        color: #1f2937;
        overflow: hidden;
    }
    .bi-template-guide {
        position: absolute;
        inset: 28px 24px 46px;
        border: 1px solid rgba(0,0,0,.5);
        pointer-events: none;
    }
    .bi-guide-line {
        position: absolute;
        left: 24px;
        right: 24px;
        height: 1px;
        background: rgba(0,0,0,.45);
    }
    .bi-guide-vline {
        position: absolute;
        top: 330px;
        bottom: 210px;
        width: 1px;
        background: rgba(0,0,0,.35);
    }
    .bi-field {
        position: absolute;
        line-height: 1.28;
        white-space: pre-line;
    }
    .bi-field.amount {
        text-align: right;
        font-weight: 600;
    }
    .bi-field.header-value {
        font-weight: 700;
        color: #9c1c1c;
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
        left: 180px;
        right: 180px;
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        letter-spacing: .02em;
    }
    .bi-logo-preview {
        position: absolute;
        top: 26px;
        left: 92px;
        width: 72px;
        height: 48px;
        object-fit: contain;
    }
    .bi-title-preview {
        position: absolute;
        top: 82px;
        right: 88px;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: .04em;
    }
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
        $money = function ($amount) {
            return 'PHP '.number_format((float) $amount, 2);
        };
        $lineDescription = "OCEAN FREIGHT (5% OF FREIGHT CHARGES)\n(2,100 x 3 CNTRS = \$6,300 x 61.55 = 387,765 x 5%)\n\nSHIPPER: LEMOINE HOLLAND B.V.\nCONTR NO.\nLETU5131406/TCNU5442735/FSCU8916299";
    @endphp

    <div class="bi-print-sheet">
        <img src="{{ asset('images/image.png') }}" class="bi-logo-preview" alt="SeaJet logo">
        <div class="bi-company-preview">
            SEA-JET INTERNATIONAL FORWARDERS, INC.<br>
            <span style="font-size: 10px; font-weight: 600">UNIT 18 & 19 BLDG. B, CARGO VILLAGE COMPLEX, PARANAQUE CITY</span>
        </div>
        <div class="bi-title-preview">BILLING INVOICE</div>
        <div class="bi-template-guide"></div>
        <div class="bi-guide-line" style="top: 242px"></div>
        <div class="bi-guide-line" style="top: 330px"></div>
        <div class="bi-guide-line" style="top: 350px"></div>
        <div class="bi-guide-line" style="top: 798px"></div>
        <div class="bi-guide-line" style="top: 988px"></div>
        <div class="bi-guide-vline" style="left: 470px"></div>
        <div class="bi-guide-vline" style="left: 590px"></div>
        <div class="bi-guide-vline" style="left: 705px"></div>

        <div class="bi-label-preview" style="left: 34px; top: 135px">Cash Sales</div>
        <div class="bi-label-preview" style="left: 118px; top: 135px">Charge Sales</div>
        <div class="bi-label-preview" style="left: 34px; top: 158px">Customer</div>
        <div class="bi-label-preview" style="left: 34px; top: 183px">Address</div>
        <div class="bi-label-preview" style="left: 34px; top: 225px">TIN</div>
        <div class="bi-label-preview" style="left: 34px; top: 263px">MAWB B/L No.</div>
        <div class="bi-label-preview" style="left: 34px; top: 287px">HAWB B/L No.</div>
        <div class="bi-label-preview" style="left: 34px; top: 311px">Date Shipped</div>
        <div class="bi-label-preview" style="left: 478px; top: 263px">Origin/Dest'n</div>
        <div class="bi-label-preview" style="left: 478px; top: 287px">Commodity</div>
        <div class="bi-label-preview" style="left: 52px; top: 351px">Item Description/Nature of Service</div>
        <div class="bi-label-preview" style="left: 504px; top: 351px">Quantity</div>
        <div class="bi-label-preview" style="left: 600px; top: 351px">Unit Price</div>
        <div class="bi-label-preview" style="left: 752px; top: 351px">Amount</div>

        <div class="bi-field" style="left: {{ $p['cash_sales'][0] }}px; top: {{ $p['cash_sales'][1] }}px; {{ $fieldStyle('cash_sales') }}">[ ]</div>
        <div class="bi-field" style="left: {{ $p['charge_sales'][0] }}px; top: {{ $p['charge_sales'][1] }}px; {{ $fieldStyle('charge_sales') }}">[x]</div>
        <div class="bi-field" style="left: {{ $p['customer'][0] }}px; top: {{ $p['customer'][1] }}px; {{ $fieldStyle('customer') }}">{{ $print['customer'] }}</div>
        <div class="bi-field" style="left: {{ $p['address'][0] }}px; top: {{ $p['address'][1] }}px; {{ $fieldStyle('address') }}">28 SHAW BLVD, KAPITOLYO 1603<br>CITY OF PASIG</div>
        <div class="bi-field" style="left: {{ $p['tin'][0] }}px; top: {{ $p['tin'][1] }}px; {{ $fieldStyle('tin') }}">000-281-014-00000</div>
        <div class="bi-field" style="left: {{ $p['mawb'][0] }}px; top: {{ $p['mawb'][1] }}px; {{ $fieldStyle('mawb') }}">OOLU32322350120</div>
        <div class="bi-field" style="left: {{ $p['hawb'][0] }}px; top: {{ $p['hawb'][1] }}px; {{ $fieldStyle('hawb') }}">2GJXM412-G1</div>
        <div class="bi-field" style="left: {{ $p['date_shipped'][0] }}px; top: {{ $p['date_shipped'][1] }}px; {{ $fieldStyle('date_shipped') }}">05/19/2026</div>
        <div class="bi-field header-value" style="left: {{ $p['invoice_no'][0] }}px; top: {{ $p['invoice_no'][1] }}px; {{ $fieldStyle('invoice_no') }}">{{ $print['invoice_no'] }}</div>
        <div class="bi-field" style="left: {{ $p['invoice_date'][0] }}px; top: {{ $p['invoice_date'][1] }}px; {{ $fieldStyle('invoice_date') }}">{{ $print['invoice_date'] }}</div>
        <div class="bi-field" style="left: {{ $p['origin_dest'][0] }}px; top: {{ $p['origin_dest'][1] }}px; {{ $fieldStyle('origin_dest') }}">ROTTERDAM/MNL</div>
        <div class="bi-field" style="left: {{ $p['commodity'][0] }}px; top: {{ $p['commodity'][1] }}px; {{ $fieldStyle('commodity') }}">3 X 40'HC</div>

        <div class="bi-field" style="left: {{ $p['line_description'][0] }}px; top: {{ $p['line_description'][1] }}px; {{ $fieldStyle('line_description') }}">{{ $lineDescription }}</div>
        <div class="bi-field" style="left: {{ $p['soa_no'][0] }}px; top: {{ $p['soa_no'][1] }}px; {{ $fieldStyle('soa_no') }}">SOA# {{ $print['soa_no'] }}</div>
        <div class="bi-field amount" style="left: {{ $p['line_qty'][0] }}px; top: {{ $p['line_qty'][1] }}px; {{ $fieldStyle('line_qty') }}">1</div>
        <div class="bi-field amount" style="left: {{ $p['line_unit_price'][0] }}px; top: {{ $p['line_unit_price'][1] }}px; {{ $fieldStyle('line_unit_price') }}">19,388.25</div>
        <div class="bi-field amount" style="left: {{ $p['line_amount'][0] }}px; top: {{ $p['line_amount'][1] }}px; {{ $fieldStyle('line_amount') }}">19,388.25</div>

        <div class="bi-field amount" style="left: {{ $p['vatable_sales'][0] }}px; top: {{ $p['vatable_sales'][1] }}px; {{ $fieldStyle('vatable_sales') }}">{{ $money($print['vatable_sales']) }}</div>
        <div class="bi-field amount" style="left: {{ $p['vat'][0] }}px; top: {{ $p['vat'][1] }}px; {{ $fieldStyle('vat') }}">{{ $money($print['vat_amount']) }}</div>
        <div class="bi-field amount" style="left: {{ $p['zero_rated_sales'][0] }}px; top: {{ $p['zero_rated_sales'][1] }}px; {{ $fieldStyle('zero_rated_sales') }}">0.00</div>
        <div class="bi-field amount" style="left: {{ $p['vat_exempt_sales'][0] }}px; top: {{ $p['vat_exempt_sales'][1] }}px; {{ $fieldStyle('vat_exempt_sales') }}">0.00</div>

        <div class="bi-field amount" style="left: {{ $p['total_sales'][0] }}px; top: {{ $p['total_sales'][1] }}px; {{ $fieldStyle('total_sales') }}">{{ $money($print['billing_total']) }}</div>
        <div class="bi-field amount" style="left: {{ $p['less_vat'][0] }}px; top: {{ $p['less_vat'][1] }}px; {{ $fieldStyle('less_vat') }}">{{ $money($print['vat_amount']) }}</div>
        <div class="bi-field amount" style="left: {{ $p['amount_net_vat'][0] }}px; top: {{ $p['amount_net_vat'][1] }}px; {{ $fieldStyle('amount_net_vat') }}">{{ $money($print['vatable_sales']) }}</div>
        <div class="bi-field amount" style="left: {{ $p['discount'][0] }}px; top: {{ $p['discount'][1] }}px; {{ $fieldStyle('discount') }}">0.00</div>
        <div class="bi-field amount" style="left: {{ $p['add_vat'][0] }}px; top: {{ $p['add_vat'][1] }}px; {{ $fieldStyle('add_vat') }}">{{ $money($print['vat_amount']) }}</div>
        <div class="bi-field amount" style="left: {{ $p['withholding_tax'][0] }}px; top: {{ $p['withholding_tax'][1] }}px; {{ $fieldStyle('withholding_tax') }}">PHP -387.77</div>
        <div class="bi-field amount header-value" style="left: {{ $p['amount_due'][0] }}px; top: {{ $p['amount_due'][1] }}px; {{ $fieldStyle('amount_due') }}">{{ $money($print['billing_total']) }}</div>
        <div class="bi-field" style="left: {{ $p['prepared_by'][0] }}px; top: {{ $p['prepared_by'][1] }}px; {{ $fieldStyle('prepared_by') }}">MABEL PLATA</div>

        @foreach($customFields as $custom)
            <div class="bi-field" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px">{{ $custom['text'] }}</div>
        @endforeach
    </div>
@endsection
