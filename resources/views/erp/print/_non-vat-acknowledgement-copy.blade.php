<div class="nonvat-sheet {{ $copyClass }} {{ $editableCopy && $adjustLayout ? 'is-adjusting' : '' }}" @if($editableCopy) id="receiptLayoutSheet" data-sheet-width-mm="215.9" @endif>
    <img class="nonvat-letterhead-background" src="{{ asset('images/background.jpg') }}" alt="" aria-hidden="true">
    <div class="nonvat-header">
        <span class="receipt-adjustable-field" @if($editableCopy) tabindex="0" data-field-key="report_header" data-field-label="Report header" @endif style="{{ $fieldStyle('report_header') }}">{{ $receiptContent['report_header'] }}</span>
        <div class="nonvat-title-rule"></div>
    </div>
    <div class="nonvat-intro"><span class="receipt-adjustable-field receipt-field-block" @if($editableCopy) tabindex="0" data-field-key="intro_text" data-field-label="Introduction text" @endif style="{{ $fieldStyle('intro_text') }}">{{ $receiptContent['intro_text'] }}</span></div>

    <div class="nonvat-section-label">Payment Details</div>
    <table class="nonvat-details">
        <tr><td>Date</td><td><span class="receipt-adjustable-field" @if($editableCopy) tabindex="0" data-field-key="payment_date" data-field-label="Payment date" @endif style="{{ $fieldStyle('payment_date') }}">{{ $receipt['payment_date'] ?? '' }}</span></td></tr>
        <tr><td>Received From</td><td><span class="receipt-adjustable-field receipt-field-block" @if($editableCopy) tabindex="0" data-field-key="registered_name" data-field-label="Registered name" @endif style="{{ $fieldStyle('registered_name') }}">{{ $receipt['registered_name'] ?? '' }}</span></td></tr>
        <tr><td>Address</td><td><span class="receipt-adjustable-field receipt-field-block" @if($editableCopy) tabindex="0" data-field-key="business_address" data-field-label="Business address" @endif style="{{ $fieldStyle('business_address') }}">{{ $receipt['business_address'] ?? '' }}</span></td></tr>
        <tr><td>Description</td><td><span class="receipt-adjustable-field receipt-field-block nonvat-description" @if($editableCopy) tabindex="0" data-field-key="description" data-field-label="Description" @endif style="{{ $fieldStyle('description') }}">{{ $receipt['description'] ?? '' }}</span></td></tr>
        <tr><td>Amount Received</td><td class="nonvat-amount"><span class="receipt-adjustable-field" @if($editableCopy) tabindex="0" data-field-key="amount" data-field-label="Transaction amount" @endif style="{{ $fieldStyle('amount') }}">{{ $currency }} {{ number_format($amount, 2) }}</span></td></tr>
        <tr><td>Payment Method</td><td>{{ $receipt['payment_method'] ?? '' }}</td></tr>
        <tr class="nonvat-total-row"><td>Total Amount Received</td><td class="nonvat-amount"><span class="receipt-adjustable-field" @if($editableCopy) tabindex="0" data-field-key="total_amount" data-field-label="Total paid amount" @endif style="{{ $fieldStyle('total_amount') }}">{{ $currency }} {{ number_format($amount, 2) }}</span></td></tr>
    </table>

    <div class="nonvat-certification">
        <div class="nonvat-signature">
            <div class="nonvat-signature-space"></div>
            <div class="nonvat-signature-line">Authorized Representative</div>
            <div class="nonvat-signature-caption">Signature over printed name</div>
        </div>
    </div>
    <div class="nonvat-printed-by receipt-adjustable-field" @if($editableCopy) tabindex="0" data-field-key="printed_by" data-field-label="Printed by" @endif style="{{ $fieldStyle('printed_by') }}">Printed by: {{ $receipt['printed_by'] ?? (auth()->user()->name ?? 'User') }}</div>
    <div class="nonvat-document-footer">{{ $copyLabel }}</div>
</div>
