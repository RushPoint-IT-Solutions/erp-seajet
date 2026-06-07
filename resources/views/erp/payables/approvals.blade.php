@extends('erp.layout')

@section('title', 'AP Payment Approvals')
@section('page-title', 'AP Payment Approvals')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Payment Approvals</h1>
            <p class="text-muted mb-0">Review AP payments before release, check printing, or online transfer.</p>
        </div>
        <a href="{{ route('erp.payables.payments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Payment</a>
    </div>
    <div class="erp-card p-3">
        <table class="table table-hover align-middle module-table w-100">
            <thead><tr><th>Payment No</th><th>Supplier</th><th>Shipment Job ID</th><th>Currency</th><th class="text-end">Amount</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach($approvals as $row)
                <tr>
                    @foreach($row as $index => $cell)
                        <td class="{{ $index === 4 ? 'text-end' : '' }}">{{ $cell }}</td>
                    @endforeach
                    <td class="text-end">
                        <a href="{{ route('erp.action', 'approve-ap-payment') }}" class="btn btn-sm btn-outline-secondary">Approve</a>
                        <a href="{{ route('erp.payables.check-voucher') }}" class="btn btn-sm btn-outline-secondary">Voucher</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script>
    $('.module-table').DataTable({ pageLength: 10, lengthChange: false });
</script>
@endpush
