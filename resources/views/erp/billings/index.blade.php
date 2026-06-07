@extends('erp.layout')

@section('title', 'Billing CRUD')
@section('page-title', 'Billing CRUD')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Billing CRUD</h1>
            <p class="text-muted mb-0">Create, view, edit, update status, and print Billing Invoice or Statement of Account.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="{{ route('erp.invoices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Billing</a>
            <a href="{{ route('erp.billings.status') }}" class="btn btn-outline-secondary"><i class="bi bi-toggle-on me-1"></i>Update Status</a>
            <a href="{{ route('erp.billings.print-invoice') }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print BI</a>
            <a href="{{ route('erp.billings.print-soa') }}" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print SOA</a>
            <a href="{{ route('erp.billings.print-layout.invoice') }}" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i>BI Layout</a>
        </div>
    </div>

    <div class="erp-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle billings-table w-100">
                <thead>
                <tr>
                    <th>Document No.</th><th>Customer</th><th>Shipment Job ID</th><th>Currency</th><th class="text-end">Amount</th><th>Status</th><th>Date</th><th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($billings as $billing)
                    <tr>
                        <td class="fw-semibold">{{ $billing[0] }}</td>
                        <td>{{ $billing[1] }}</td>
                        <td>{{ $billing[2] }}</td>
                        <td>{{ $billing[3] }}</td>
                        <td class="text-end">{{ $billing[4] }}</td>
                        <td><span class="badge badge-soft">{{ $billing[5] }}</span></td>
                        <td>{{ $billing[6] }}</td>
                        <td class="text-end">
                            <a href="{{ route('erp.billings.edit', $billing[0]) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('erp.billings.status', ['invoice' => $billing[0]]) }}" class="btn btn-sm btn-outline-secondary" title="Status"><i class="bi bi-toggle-on"></i></a>
                            <a href="{{ route('erp.billings.print-invoice', ['invoice' => $billing[0]]) }}" class="btn btn-sm btn-outline-secondary" title="Print BI"><i class="bi bi-printer"></i></a>
                            <a href="{{ route('erp.billings.print-soa', ['customer' => $billing[1]]) }}" class="btn btn-sm btn-outline-secondary" title="Print SOA"><i class="bi bi-file-earmark-text"></i></a>
                            <a href="{{ route('erp.billings.delete', $billing[0]) }}" class="btn btn-sm btn-outline-danger" title="Void"><i class="bi bi-trash"></i></a>
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
    $('.billings-table').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { search: '', searchPlaceholder: 'Search billings' }
    });
</script>
@endpush
