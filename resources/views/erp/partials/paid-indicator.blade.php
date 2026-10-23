@if(!empty($paymentStatus['paid']))
    <div class="document-paid-indicator d-flex align-items-center gap-2 my-2 no-print" style="color:#65ad35;font-weight:700" role="status" aria-label="Fully paid">
        <i class="bi bi-check-circle-fill" style="font-size:24px" aria-hidden="true"></i>
        <span>PAID</span>
        @if(!empty($paymentStatus['date']))
            <span class="small">{{ \Carbon\Carbon::parse($paymentStatus['date'])->format('m/d/Y') }}</span>
        @endif
    </div>
@endif
