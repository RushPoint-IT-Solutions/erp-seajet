@extends('erp.layout')

@section('title', 'Check Voucher')
@section('page-title', 'Check Voucher')

@php
    $settings = $layout['settings'];
    $positions = $layout['positions'];
    $fieldStyles = $layout['field_styles'];
    $customFields = $layout['custom_fields'];
    $formattedAmount = number_format($voucher['net'], 2);
    $values = [
        'logo_mark' => 'SJ',
        'company_name' => strtoupper($voucher['company']),
        'form_title' => 'CHECK VOUCHER',
        'payee_label' => 'PAYEE',
        'payee' => $voucher['supplier'],
        'check_no_label' => 'Check No:',
        'check_no' => $voucher['check_no'],
        'document_no_label' => 'Document No:',
        'document_no' => $voucher['document_no'],
        'document_date_label' => 'Document Date:',
        'document_date' => $voucher['document_date'],
        'payment_info_title' => 'PAYMENT INFORMATION',
        'amount_header' => 'Amount',
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
        'prepared_by' => '',
        'prepared_date_label' => 'Date:',
        'checked_by_label' => 'Checked by:',
        'checked_by' => '',
        'checked_date_label' => 'Date:',
        'approved_by_label' => 'Approved by:',
        'approved_by' => '',
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
    .cv-field.amount { text-align: right; }
    .cv-field.header-text { text-align: center; font-weight: 700; letter-spacing: .02em; }
    .cv-field.logo-mark { text-align: center; font-weight: 800; color: #6b7280; border: 2px solid #9ca3af; transform: skew(-12deg); }
    .cv-field.label { color: #374151; }
    .cv-field.signature { text-align: center; border-top: 1px solid #111827; padding-top: 6px; }
    .cv-box { position: absolute; border: 1px solid #111827; }
    .cv-line { position: absolute; border-top: 1px solid #111827; }
    .cv-vline { position: absolute; border-left: 1px solid #111827; }
    .cv-muted-line { position: absolute; border-top: 1px dashed #111827; opacity: .55; }
    @media print {
        .erp-sidebar, .erp-topbar, .no-print { display: none !important; }
        .erp-main { margin: 0 !important; }
        .erp-main > section { padding: 0 !important; }
        body { background: #fff !important; }
        .cv-print-sheet { border: 0; box-shadow: none; margin: 0; }
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
                <div class="cv-box" style="left: 46px; top: 190px; width: 292px; height: 126px;"></div>
                <div class="cv-line" style="left: 46px; top: 236px; width: 292px;"></div>
                <div class="cv-muted-line" style="left: 640px; top: 218px; width: 174px;"></div>
                <div class="cv-muted-line" style="left: 640px; top: 258px; width: 174px;"></div>
                <div class="cv-muted-line" style="left: 640px; top: 298px; width: 174px;"></div>
                <div class="cv-muted-line" style="left: 384px; top: 502px; width: 288px;"></div>
                <div class="cv-box" style="left: 46px; top: 522px; width: 748px; height: 104px;"></div>
                <div class="cv-box" style="left: 48px; top: 650px; width: 744px; height: 176px;"></div>
                <div class="cv-line" style="left: 48px; top: 678px; width: 744px;"></div>
                <div class="cv-line" style="left: 48px; top: 790px; width: 744px;"></div>
                <div class="cv-vline" style="left: 504px; top: 650px; height: 176px;"></div>
                <div class="cv-vline" style="left: 650px; top: 650px; height: 176px;"></div>
                <div class="cv-box" style="left: 48px; top: 840px; width: 744px; height: 150px;"></div>
                <div class="cv-vline" style="left: 214px; top: 840px; height: 150px;"></div>
                <div class="cv-vline" style="left: 386px; top: 840px; height: 150px;"></div>
                <div class="cv-vline" style="left: 592px; top: 840px; height: 150px;"></div>
                <div class="cv-line" style="left: 48px; top: 876px; width: 744px;"></div>
                <div class="cv-line" style="left: 48px; top: 962px; width: 744px;"></div>
            @endif

            @foreach($positions as $field => $point)
                @continue(($point[0] ?? 0) < 0 || ($point[1] ?? 0) < 0)
                @php($classes = (in_array($field, ['company_name', 'form_title']) ? ' header-text' : '').($field === 'logo_mark' ? ' logo-mark' : '').(in_array($field, ['amount', 'debit_amount', 'credit_amount', 'total_debit', 'total_credit']) ? ' amount' : '').(strpos($field, 'label') !== false || strpos($field, 'header') !== false ? ' label' : ''))
                <div class="cv-field{{ $classes }}" style="left: {{ $point[0] }}px; top: {{ $point[1] }}px; width: {{ $fieldStyles[$field]['width'] }}px; font-size: {{ $fieldStyles[$field]['font_size'] }}px;">
                    {{ $values[$field] ?? '' }}
                </div>
            @endforeach

            @foreach($customFields as $custom)
                <div class="cv-field" style="left: {{ $custom['x'] }}px; top: {{ $custom['y'] }}px; width: {{ $custom['width'] }}px; font-size: {{ $custom['font_size'] }}px;">
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
