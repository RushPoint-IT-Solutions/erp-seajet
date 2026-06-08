@extends('erp.layout')

@section('title', 'Review Vendor Bill')
@section('page-title', 'Review Vendor Bill')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Vendor Bill Ready for Approval</h1>
            <p class="text-muted mb-0">Shipment-linked AP bill captured with withholding tax and multi-currency details.</p>
        </div>
        <a href="{{ route('erp.payables.approvals') }}" class="btn btn-primary"><i class="bi bi-check2-square me-1"></i> Send for Approval</a>
    </div>
    <div class="erp-card p-4">
        <div class="row g-3">
            @foreach($bill as $label => $value)
                @continue(is_array($value))
                <div class="col-md-4">
                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        @if(!empty($lines))
            <hr>
            <h2 class="h6 fw-bold">Expense Lines</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Account</th>
                        <th>Tax</th>
                        <th class="text-end">Amount</th>
                        <th>Memo</th>
                        <th>Customer:Job</th>
                        <th>Billable</th>
                        <th>Class</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($lines as $line)
                        <tr>
                            <td>{{ $line->account_name ?? '' }}</td>
                            <td>{{ $line->tax_category ?? '' }}</td>
                            <td class="text-end">{{ number_format((float) ($line->amount ?? $line->line_total ?? 0), 2) }}</td>
                            <td>{{ $line->memo ?? $line->description ?? '' }}</td>
                            <td>{{ $line->customer_job_id ?? '' }}</td>
                            <td>{{ !empty($line->is_billable) ? 'Yes' : 'No' }}</td>
                            <td>{{ $line->class_code ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if(isset($attachments) && count($attachments))
            <hr>
            <h2 class="h6 fw-bold">Attached Documents</h2>
            <div class="d-flex flex-wrap gap-2">
                @foreach($attachments as $attachment)
                    <span class="badge badge-soft py-2 px-3"><i class="bi bi-paperclip me-1"></i>{{ $attachment->original_name }}</span>
                @endforeach
            </div>
        @endif
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('erp.expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-list-ul me-1"></i> Expense List</a>
            <a href="{{ route('erp.payables.vendor-bills.create') }}" class="btn btn-outline-secondary"><i class="bi bi-plus-lg me-1"></i> Enter Another Bill</a>
            <a href="{{ route('erp.payables.payments.create', ['bill' => $bill['bill_no']]) }}" class="btn btn-primary"><i class="bi bi-cash-stack me-1"></i> Pay Bill</a>
        </div>
    </div>
@endsection
