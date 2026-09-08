@extends('erp.layout')

@section('title', 'AP Payment Approvals')
@section('page-title', 'AP Payment Approvals')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Controlled Payment Queue</h1>
            <p class="text-muted mb-0">Preparation, approval, check printing, and release are separate accountable steps.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('erp.payables.check-register') }}" class="btn btn-outline-secondary"><i class="bi bi-journal-check me-1"></i> Check Register</a>
            <a href="{{ route('erp.payables.payments.create', ['method' => 'Cheque']) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Payment</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><strong>Action blocked.</strong> {{ $errors->first() }}</div>
    @endif

    <div class="alert alert-light border small">
        <strong>Control sequence:</strong> another authorized user approves the preparer's payment; cheque payments must then be printed and recorded; release performs the accounting posting.
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead><tr><th>Payment</th><th>Check Voucher</th><th>Supplier</th><th>Method</th><th>Check No.</th><th>Currency</th><th class="text-end">Amount</th><th>Status</th><th>Check Control</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach($approvals as $row)
                    <tr>
                        <td><div class="fw-semibold">{{ $row['payment_no'] }}</div><small class="text-muted">{{ $row['shipment_job_id'] }}</small></td>
                        <td><a href="{{ route('erp.payables.check-voucher', $row['id']) }}" class="fw-semibold text-decoration-none">{{ $row['check_voucher'] }}</a></td>
                        <td>{{ $row['supplier'] }}</td>
                        <td>{{ $row['payment_method'] }}</td>
                        <td class="fw-semibold">{{ $row['check_no'] }}</td>
                        <td>{{ $row['currency'] }}</td>
                        <td class="text-end">{{ $row['amount'] }}</td>
                        <td><span class="badge {{ strtolower($row['status']) === 'released' ? 'text-bg-success' : (strtolower($row['status']) === 'voided' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ $row['status'] }}</span></td>
                        <td>
                            @if($row['check_status'])
                                <span class="badge badge-soft">{{ ucfirst($row['check_status']) }}</span>
                                <small class="d-block text-muted">Prints: {{ $row['print_count'] }}</small>
                            @else
                                <span class="text-muted">Not applicable</span>
                            @endif
                        </td>
                        <td class="text-end" style="min-width:260px">
                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                @if($row['can_approve'])
                                    <form method="post" action="{{ route('erp.payables.payments.approve', $row['id']) }}">@csrf<button class="btn btn-sm btn-outline-primary">Approve</button></form>
                                @endif
                                @if($row['can_print'])
                                    <a href="{{ $row['print_count'] || $row['voucher_printed'] ? route('erp.payables.check-print', $row['id']) : route('erp.payables.check-voucher', $row['id']) }}" class="btn btn-sm btn-primary">{{ $row['print_count'] ? 'Reprint Check' : ($row['voucher_printed'] ? 'Print Check' : 'Print Check Voucher') }}</a>
                                @endif
                                @if($row['can_release'])
                                    <form method="post" action="{{ route('erp.payables.payments.release', $row['id']) }}" onsubmit="return confirm('Release and post this payment to the ledger?')">@csrf<button class="btn btn-sm btn-success">Release</button></form>
                                @endif
                                <a href="{{ route('erp.payables.check-voucher', $row['id']) }}" class="btn btn-sm btn-outline-secondary">Voucher</a>
                                @if($row['can_void'])
                                    <form method="post" action="{{ route('erp.payables.payments.void', $row['id']) }}" class="void-payment-form">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-outline-danger">Void</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({ pageLength: 10, lengthChange: false, order: [] });
    document.querySelectorAll('.void-payment-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var reason = window.prompt('Enter the payment void reason:') || '';
            if (reason.trim().length < 5) {
                event.preventDefault();
                return;
            }
            form.querySelector('[name="reason"]').value = reason;
        });
    });
</script>
@endpush
