@extends('erp.layout')

@section('title', 'Check Voucher')
@section('page-title', 'Check Voucher')

@php
    $settings = $layout['settings'];
    $positions = $layout['positions'];
    $fieldStyles = $layout['field_styles'];
    $customFields = $layout['custom_fields'];
    $boxHeights = $layout['box_heights'];
    $formattedAmount = number_format($voucher['net'], 2);
    $values = [
        'company_name' => strtoupper($voucher['company']),
        'form_title' => 'CHECK VOUCHER',
        'payee_label' => 'PAYEE',
        'payee' => $voucher['vendor'],
        'check_no_label' => 'Check No:',
        'check_no' => $voucher['check_no'],
        'document_no_label' => 'Document No:',
        'document_no' => $voucher['document_no'],
        'document_date_label' => 'Document Date:',
        'document_date' => $voucher['document_date'],
        'payment_info_title' => 'PAYMENT INFORMATION',
        'amount_header' => 'Amount :',
        'account_name_label' => 'Account Name:',
        'account_name' => $voucher['account_name'],
        'amount' => $formattedAmount,
        'account_no_label' => 'Account No:',
        'account_no' => $voucher['account_no'],
        'amount_words' => $voucher['amount_words'],
        'description_label' => 'DESCRIPTION',
        'description' => $voucher['description'],
        'account_title_header' => 'Account Title',
        'debit_header' => 'Debit',
        'credit_header' => 'Credit',
        'debit_account_title' => $voucher['debit_account_title'],
        'debit_amount' => $formattedAmount,
        'credit_account_title' => $voucher['credit_account_title'],
        'credit_amount' => $formattedAmount,
        'total_label' => 'Total',
        'total_debit' => $formattedAmount,
        'total_credit' => $formattedAmount,
        'prepared_by_label' => 'Prepared by:',
        'prepared_by' => 'MLP',
        'prepared_date_label' => 'Date:',
        'checked_by_label' => 'Checked by:',
        'checked_by' => 'GCB',
        'checked_date_label' => 'Date:',
        'approved_by_label' => 'Approved by:',
        'approved_by' => 'ASA/BCA/CAC',
        'approved_date_label' => 'Date:',
        'received_by_label' => 'Received by:',
        'received_by' => "Signature over Printed Name\nID No Presented",
        'received_date_label' => 'Date:',
    ];
@endphp

@push('styles')
<style>
    .cv-print-wrap { overflow: auto; }
    .cv-print-sheet {
        position: relative;
        width: {{ $settings['paper_width'] }}px;
        height: {{ $settings['paper_height'] }}px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #d7dce5;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        overflow: hidden;
        font-family: Arial, sans-serif;
        @if($settings['template_url'] && $settings['print_template'])
        background-image: url('{{ $settings['template_url'] }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        @endif
    }
    .cv-field {
        position: absolute;
        white-space: pre-line;
        line-height: 1.05;
        color: #111827;
    }
    .cv-field.nowrap { white-space: nowrap; }
    .cv-field.amount { text-align: right; }
    .cv-field.header-text { text-align: center; font-weight: 700; letter-spacing: .02em; }
    .cv-field.logo-mark { height: 82px; text-align: center; }
    .cv-field.logo-mark img { display: block; width: 100%; height: 100%; object-fit: contain; }
    .cv-field.label { color: #374151; }
    .cv-field.signature { text-align: center; border-top: 1px solid #111827; padding-top: 6px; }
    .cv-box { position: absolute; border: 1px solid #111827; }
    .cv-line { position: absolute; border-top: 1px solid #111827; }
    .cv-vline { position: absolute; border-left: 1px solid #111827; }
    .cv-amount-box {
        position: absolute;
        left: 430px;
        top: 390px;
        width: 364px;
        height: {{ $boxHeights['amount'] }}px;
        border: 1px solid #111827;
    }
    .cv-amount-box-line {
        position: absolute;
        left: 0;
        top: 44px;
        width: 100%;
        border-top: 1px solid #111827;
    }
    @media print {
        @page { size: letter; margin: 0; }
        html, body {
            width: 8.5in;
            height: 11in;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden;
        }
        .erp-sidebar, .erp-topbar, .no-print { display: none !important; }
        .erp-main { margin: 0 !important; }
        .erp-main > section { padding: 0 !important; }
        body { background: #fff !important; }
        .cv-print-wrap { width: 8.5in; height: 11in; overflow: hidden; }
        .cv-print-sheet {
            border: 0;
            box-shadow: none;
            margin: 0;
            transform: translate(12px, 12px) scale(.97);
            transform-origin: top left;
        }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('erp.payables.check-voucher.layout', array_filter(['payment' => $voucher['id']])) }}" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i> Adjust Layout</a>
        <button type="button" class="btn btn-outline-secondary" id="printVoucherButton"><i class="bi bi-printer me-1"></i> Print Check Voucher</button>
        @if(!empty($voucher['id']))
            <a href="{{ route('erp.payables.check-print', $voucher['id']) }}" class="btn btn-primary {{ $voucherPrinted ? '' : 'd-none' }}" id="printCheckButton"><i class="bi bi-bank me-1"></i> Print Check</a>
        @endif
    </div>

    <div class="cv-print-wrap">
        <div class="cv-print-sheet">
            @if(!$settings['template_url'] || !$settings['print_template'])
                <div class="cv-box" style="left: 46px; top: 190px; width: 292px; height: {{ $boxHeights['payee'] }}px;"></div>
                <div class="cv-line" style="left: 46px; top: 236px; width: 292px;"></div>
                <div class="cv-box" style="left: 46px; top: 522px; width: 748px; height: {{ $boxHeights['description'] }}px;"></div>
                <div class="cv-box" style="left: 48px; top: 650px; width: 744px; height: {{ $boxHeights['accounting'] }}px;"></div>
                <div class="cv-line" style="left: 48px; top: 678px; width: 744px;"></div>
                <div class="cv-line" style="left: 48px; top: 790px; width: 744px;"></div>
                <div class="cv-vline" style="left: 504px; top: 650px; height: {{ $boxHeights['accounting'] }}px;"></div>
                <div class="cv-vline" style="left: 650px; top: 650px; height: {{ $boxHeights['accounting'] }}px;"></div>
                <div class="cv-box" style="left: 48px; top: 840px; width: 744px; height: {{ $boxHeights['signatures'] }}px;"></div>
                <div class="cv-vline" style="left: 214px; top: 840px; height: {{ $boxHeights['signatures'] }}px;"></div>
                <div class="cv-vline" style="left: 386px; top: 840px; height: {{ $boxHeights['signatures'] }}px;"></div>
                <div class="cv-vline" style="left: 592px; top: 840px; height: {{ $boxHeights['signatures'] }}px;"></div>
                <div class="cv-line" style="left: 48px; top: 876px; width: 744px;"></div>
                <div class="cv-line" style="left: 48px; top: 962px; width: 744px;"></div>
            @endif

            <div class="cv-amount-box"><div class="cv-amount-box-line"></div></div>

            @php($paymentInfoFields = ['payment_info_title', 'amount_header', 'account_name_label', 'account_name', 'amount', 'account_no_label', 'account_no', 'amount_words'])
            @foreach($positions as $field => $point)
                @continue(($point[0] ?? 0) < 0 || ($point[1] ?? 0) < 0)
                @php($paymentColumnPositions = ['account_name_label' => [70, 399], 'account_name' => [180, 399], 'account_no_label' => [70, 437], 'account_no' => [180, 437], 'amount_header' => [442, 398], 'amount' => [530, 398], 'amount_words' => [442, 444]])
                @php($fixedWidths = ['account_name_label' => 105, 'account_name' => 230, 'account_no_label' => 105, 'account_no' => 230, 'amount_header' => 80, 'amount' => 250, 'amount_words' => 330])
                @php($renderTop = isset($paymentColumnPositions[$field]) ? $paymentColumnPositions[$field][1] : (($point[1] ?? 0) - (in_array($field, $paymentInfoFields, true) ? 28 : 0)))
                @php($minimumWidth = ['logo_mark' => 320, 'company_name' => 460, 'form_title' => 200, 'payment_info_title' => 220][$field] ?? $fieldStyles[$field]['width'])
                @php($renderWidth = $fixedWidths[$field] ?? max($fieldStyles[$field]['width'], $minimumWidth))
                @php($renderLeft = $field === 'logo_mark' ? (($settings['paper_width'] - $renderWidth) / 2) : (isset($paymentColumnPositions[$field]) ? $paymentColumnPositions[$field][0] : (($point[0] ?? 0) - (in_array($field, ['company_name', 'form_title'], true) ? (($renderWidth - $fieldStyles[$field]['width']) / 2) : 0))))
                @php($fontIncrease = $field === 'company_name' ? 4 : 2)
                @php($classes = (in_array($field, ['company_name', 'form_title']) ? ' header-text' : '').($field === 'logo_mark' ? ' logo-mark' : '').(in_array($field, ['company_name', 'form_title', 'payment_info_title'], true) ? ' nowrap' : '').(in_array($field, ['debit_amount', 'credit_amount', 'total_debit', 'total_credit']) ? ' amount' : '').(strpos($field, 'label') !== false || strpos($field, 'header') !== false ? ' label' : ''))
                <div class="cv-field{{ $classes }}" style="left: {{ $renderLeft }}px; top: {{ $renderTop }}px; width: {{ $renderWidth }}px; height: {{ $fieldStyles[$field]['height'] }}px; font-size: {{ min(40, $fieldStyles[$field]['font_size'] + $fontIncrease) }}px;">
                    @if($field === 'logo_mark')
                        <img src="{{ asset('images/gen_off_seajetlogo.webp') }}" alt="SeaJet logo">
                    @else
                        {{ $values[$field] ?? '' }}
                    @endif
                </div>
            @endforeach

            @foreach($customFields as $custom)
                <div class="cv-field" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; height: {{ $custom['height'] }}px; font-size: {{ min(40, $custom['font_size'] + 2) }}px;">
                    {{ $custom['text'] }}
                </div>
            @endforeach
        </div>
    </div>

    @if(!empty($voucher['id']))
        <script>
            document.getElementById('printVoucherButton').addEventListener('click', async function () {
                const button = this;
                button.disabled = true;

                try {
                    const response = await fetch('{{ route('erp.payables.check-voucher.print.event', $voucher['id']) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Unable to record the check voucher print request.');
                    }

                    window.print();
                    document.getElementById('printCheckButton').classList.remove('d-none');
                } catch (error) {
                    window.alert(error.message);
                } finally {
                    button.disabled = false;
                }
            });
        </script>
    @else
        <script>
            document.getElementById('printVoucherButton').addEventListener('click', function () {
                window.print();
            });
        </script>
    @endif
@endsection
