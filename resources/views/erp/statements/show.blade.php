@extends('erp.layout')

@section('title', $title)
@section('page-title', $title)

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $title }}</h1>
            <p class="text-muted mb-0">Financial statement generated from traceable shipment-linked accounting entries.</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route('erp.export', str_slug($title).'-pdf') }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-pdf me-1"></i> PDF</a>
            <a href="{{ route('erp.export', str_slug($title).'-excel') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>

    <div class="erp-card p-4">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
            <div>
                <div class="fw-bold">SeaJet Holdings Inc.</div>
                <div class="text-muted">For the period ending May 24, 2026</div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Currency</div>
                <div class="fw-semibold">PHP</div>
            </div>
        </div>
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>Section</th>
                <th>Account / Line Item</th>
                <th class="text-end">Amount</th>
            </tr>
            </thead>
            <tbody>
            @php $total = 0; @endphp
            @foreach($rows as $row)
                @php $total += $row[2]; @endphp
                <tr>
                    <td>{{ $row[0] }}</td>
                    <td>{{ $row[1] }}</td>
                    <td class="text-end">{{ number_format(abs($row[2]), 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr class="fw-bold">
                <td colspan="2">Net Total</td>
                <td class="text-end">{{ number_format(abs($total), 2) }}</td>
            </tr>
            </tfoot>
        </table>
    </div>
@endsection
