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
        <div class="alert alert-danger" role="alert">
            <strong>Action blocked. Check Preparation could not proceed for the following reason:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-light border small">
        <strong>Control sequence:</strong> another authorized user approves the preparer's payment; cheque payments must then be printed and recorded; release performs the accounting posting.
    </div>

    @php
        $approvalStatusCounts = collect($approvals)->groupBy('status_group')->map->count();
    @endphp
    <ul class="nav nav-tabs mb-3 check-preparation-status-tabs" role="tablist" aria-label="Check preparation status filter">
        <li class="nav-item" role="presentation">
            <button class="nav-link" type="button" data-status-group="approved">Approved <span class="badge text-bg-light ms-1">{{ $approvalStatusCounts->get('approved', 0) }}</span></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" type="button" data-status-group="for_approval">For Approval <span class="badge text-bg-light ms-1">{{ $approvalStatusCounts->get('for_approval', 0) }}</span></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" type="button" data-status-group="cancelled_voided">Cancelled / Voided <span class="badge text-bg-light ms-1">{{ $approvalStatusCounts->get('cancelled_voided', 0) }}</span></button>
        </li>
    </ul>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle module-table w-100">
                <thead><tr><th>Payment</th><th>Check Voucher</th><th>Supplier</th><th>Method</th><th>Check No.</th><th>Currency</th><th class="text-end">Amount</th><th>Status</th><th>Check Control</th><th>Reason / Next Step</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach($approvals as $row)
                    <tr data-status-group="{{ $row['status_group'] }}">
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
                        <td style="min-width:240px"><span class="badge text-wrap text-start {{ $row['workflow_tone'] }}">{{ $row['workflow_message'] }}</span></td>
                        <td class="text-end" style="min-width:260px">
                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                @if($row['can_approve'])
                                    <form method="post" action="{{ route('erp.payables.payments.approve', $row['id']) }}">@csrf<button class="btn btn-sm btn-outline-primary">Approve</button></form>
                                @endif
                                @if($row['can_edit'])
                                    <button type="button" class="btn btn-sm btn-outline-secondary edit-preparation-button"
                                            data-action="{{ route('erp.payables.payments.update-preparation', $row['id']) }}"
                                            data-payment="{{ $row['payment_no'] }}"
                                            data-date="{{ $row['payment_date'] }}"
                                            data-bill="{{ $row['bill_no'] }}">Edit</button>
                                @endif
                                @if($row['can_print'])
                                    <a href="{{ $row['print_count'] || $row['voucher_printed'] ? route('erp.payables.check-print', $row['id']) : route('erp.payables.check-voucher', $row['id']) }}" class="btn btn-sm btn-primary">{{ $row['print_count'] ? 'Reprint Check' : ($row['voucher_printed'] ? 'Print Check' : 'Print Check Voucher') }}</a>
                                @endif
                                @if($row['can_release'])
                                    <form method="post" action="{{ route('erp.payables.payments.release', $row['id']) }}" onsubmit="return confirm('Release and post this payment to the ledger?')">@csrf<button class="btn btn-sm btn-success">Release</button></form>
                                @endif
                                <a href="{{ route('erp.payables.check-voucher', $row['id']) }}" class="btn btn-sm btn-outline-secondary">Voucher</a>
                                @if($row['can_void'])
                                    <button type="button" class="btn btn-sm btn-outline-danger void-payment-button"
                                            data-action="{{ route('erp.payables.payments.void', $row['id']) }}"
                                            data-payment="{{ $row['payment_no'] }}"
                                            data-supplier="{{ $row['supplier'] }}">Void</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="editPreparationModal" tabindex="-1" aria-labelledby="editPreparationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" id="editPreparationForm">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="editPreparationModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Check Preparation</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Update <strong id="editPreparationNumber"></strong>. The new values will also appear on its voucher and unprinted check.</p>
                        <div class="mb-3">
                            <label for="editPaymentDate" class="form-label fw-semibold">Payment / Check Date</label>
                            <input type="date" name="payment_date" id="editPaymentDate" class="form-control" required>
                        </div>
                        <div>
                            <label for="editBillNumber" class="form-label fw-semibold">Bill #</label>
                            <select name="bill_no" id="editBillNumber" class="form-select erp-select" required>
                                @foreach($openBills as $bill)
                                    <option value="{{ $bill['bill_no'] }}">{{ $bill['bill_no'] }} - {{ $bill['supplier_name'] }} - {{ $bill['balance_display'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="voidPaymentModal" tabindex="-1" aria-labelledby="voidPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" id="voidPaymentForm" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="voidPaymentModalLabel"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Void Check Preparation</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">You are voiding <strong id="voidPaymentNumber"></strong><span id="voidPaymentSupplier"></span>. This action requires a reason and will remain in the audit trail.</p>
                        <label for="voidPaymentReason" class="form-label fw-semibold">Reason for voiding</label>
                        <textarea name="reason" id="voidPaymentReason" class="form-control" rows="4" minlength="5" maxlength="1000" required placeholder="Enter at least 5 characters"></textarea>
                        <div class="invalid-feedback">Enter a reason containing at least 5 characters.</div>
                        <div class="form-text"><span id="voidReasonCount">0</span>/1000 characters</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-slash-circle me-1"></i>Void Check Preparation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var checkPreparationTableElement = document.querySelector('.module-table');
    var activeCheckPreparationStatus = 'for_approval';
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable !== checkPreparationTableElement) {
            return true;
        }
        return $(settings.aoData[dataIndex].nTr).data('status-group') === activeCheckPreparationStatus;
    });
    var checkPreparationTable = $('.module-table').DataTable({ pageLength: 10, lengthChange: false, order: [] });
    checkPreparationTable.draw();
    $('.check-preparation-status-tabs [data-status-group]').on('click', function () {
        activeCheckPreparationStatus = $(this).data('status-group');
        $('.check-preparation-status-tabs .nav-link').removeClass('active');
        $(this).addClass('active');
        checkPreparationTable.draw();
    });
    var voidPaymentModalElement = document.getElementById('voidPaymentModal');
    var voidPaymentModal = new bootstrap.Modal(voidPaymentModalElement);
    var voidPaymentForm = document.getElementById('voidPaymentForm');
    var voidPaymentReason = document.getElementById('voidPaymentReason');
    var editPreparationModal = new bootstrap.Modal(document.getElementById('editPreparationModal'));
    var editPreparationForm = document.getElementById('editPreparationForm');

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.edit-preparation-button');
        if (!button) {
            return;
        }

        editPreparationForm.action = button.dataset.action;
        document.getElementById('editPreparationNumber').textContent = button.dataset.payment || 'this payment';
        document.getElementById('editPaymentDate').value = button.dataset.date || '';
        $('#editBillNumber').val(button.dataset.bill).trigger('change');
        editPreparationModal.show();
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.void-payment-button');
        if (!button) {
            return;
        }

        voidPaymentForm.action = button.dataset.action;
        document.getElementById('voidPaymentNumber').textContent = button.dataset.payment || 'this payment';
        document.getElementById('voidPaymentSupplier').textContent = button.dataset.supplier ? ' for ' + button.dataset.supplier : '';
        voidPaymentReason.value = '';
        voidPaymentReason.classList.remove('is-invalid');
        document.getElementById('voidReasonCount').textContent = '0';
        voidPaymentModal.show();
    });

    voidPaymentModalElement.addEventListener('shown.bs.modal', function () {
        voidPaymentReason.focus();
    });

    voidPaymentReason.addEventListener('input', function () {
        document.getElementById('voidReasonCount').textContent = String(this.value.length);
        if (this.value.trim().length >= 5) {
            this.classList.remove('is-invalid');
        }
    });

    voidPaymentForm.addEventListener('submit', function (event) {
        var reason = voidPaymentReason.value.trim();
        if (reason.length < 5) {
            event.preventDefault();
            voidPaymentReason.classList.add('is-invalid');
            voidPaymentReason.focus();
            return;
        }
        voidPaymentReason.value = reason;
    });
</script>
@endpush
