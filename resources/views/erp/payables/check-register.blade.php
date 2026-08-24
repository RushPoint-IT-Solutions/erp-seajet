@extends('erp.layout')

@section('title', 'Check Register')
@section('page-title', 'Controlled Check Register')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Check Register</h1>
            <p class="text-muted mb-0">Every assigned, printed, released, cleared, voided, and replaced check remains traceable.</p>
        </div>
        <a href="{{ route('erp.payables.approvals') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Payment Queue</a>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Action blocked.</strong> {{ $errors->first() }}</div>@endif

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle check-register-table w-100">
                <thead><tr><th>Date</th><th>Check / Bank</th><th>Payee</th><th>Payment / Voucher</th><th class="text-end">Amount</th><th>Status</th><th>Print Audit</th><th>Controlled Action</th></tr></thead>
                <tbody>
                @foreach($checks as $check)
                    <tr>
                        <td>{{ $check->check_date }}</td>
                        <td><div class="fw-semibold">{{ $check->check_no }}</div><small class="text-muted">{{ $check->bank_name ?: 'Bank' }} · {{ $check->account_name ?: $check->bank_account_key }}</small></td>
                        <td>{{ $check->payee_name }}</td>
                        <td><div>{{ $check->payment_no }}</div><small class="text-muted">{{ $check->voucher_no }}</small></td>
                        <td class="text-end">{{ $check->currency }} {{ number_format($check->amount, 2) }}</td>
                        <td><span class="badge {{ $check->status === 'cleared' ? 'text-bg-success' : (in_array($check->status, ['voided', 'replaced', 'exception']) ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ ucfirst($check->status) }}</span></td>
                        <td><div>{{ $check->print_count }} request(s)</div><small class="text-muted">{{ $check->last_printed_at ?: 'Never printed' }}</small></td>
                        <td style="min-width:300px">
                            @if(in_array($check->status, ['assigned', 'printed', 'released']))
                                <a href="{{ route('erp.payables.check-print', $check->payment_id) }}" class="btn btn-sm btn-outline-primary mb-1">{{ $check->print_count ? 'Controlled Reprint' : 'Print' }}</a>
                            @endif

                            @if(in_array($check->status, ['assigned', 'printed']))
                                <form method="post" action="{{ route('erp.payables.checks.replace', $check->id) }}" class="row g-1 mb-1">
                                    @csrf
                                    <div class="col"><input name="new_check_no" class="form-control form-control-sm" placeholder="New check no." required></div>
                                    <div class="col"><input name="reason" class="form-control form-control-sm" placeholder="Spoilage reason" minlength="5" required></div>
                                    <div class="col-auto"><button class="btn btn-sm btn-outline-danger">Replace</button></div>
                                </form>
                            @elseif($check->status === 'released')
                                <form method="post" action="{{ route('erp.payables.checks.clear', $check->id) }}" class="row g-1">
                                    @csrf
                                    <div class="col"><input name="cleared_at" type="date" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required></div>
                                    <div class="col"><input name="clearing_reference" class="form-control form-control-sm" placeholder="Bank statement ref." required></div>
                                    <div class="col-auto"><button class="btn btn-sm btn-success">Clear</button></div>
                                </form>
                            @elseif($check->status === 'cleared')
                                <small class="text-muted">Cleared {{ $check->cleared_at }}<br>{{ $check->clearing_reference }}</small>
                            @elseif($check->status === 'replaced')
                                <small class="text-muted">Replacement check ID: {{ $check->replaced_by_check_issuance_id }}</small>
                            @elseif($check->status === 'voided')
                                <small class="text-muted">{{ $check->void_reason }}</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>$('.check-register-table').DataTable({ pageLength: 15, lengthChange: false, order: [] });</script>
@endpush
