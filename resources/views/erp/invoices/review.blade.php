@extends('erp.layout')

@section('title', 'Review Invoice')
@section('page-title', 'Review Invoice')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Invoice Ready</h1>
            <p class="text-muted mb-0">The invoice information was accepted and remains traceable to its Shipment Job ID.</p>
        </div>
        <a href="{{ route('erp.payments.create', ['invoice' => $invoice['invoice_no'] ?? '']) }}" class="btn btn-primary"><i class="bi bi-cash me-1"></i> Proceed to Payment</a>
    </div>

    @if(!empty($invoice['saved_message']))
        <div class="alert alert-success">{{ $invoice['saved_message'] }}</div>
    @endif

    <div class="erp-card p-4">
        <div class="row g-3">
            @foreach($invoice as $label => $value)
                @continue(is_array($value))
                <div class="col-md-4">
                    <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        @if(isset($invoice['item_description']))
            <hr>
            <h2 class="h6 fw-bold">Invoice Line Items</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Qty</th>
                        <th>Class</th>
                        <th class="text-end">Total</th>
                        <th>Tax</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($invoice['item_description'] as $index => $description)
                        <tr>
                            <td>{{ $invoice['item'][$index] ?? '' }}</td>
                            <td style="white-space: pre-line">{{ $description }}</td>
                            <td class="text-end">{{ $invoice['unit_price'][$index] ?? '' }}</td>
                            <td class="text-end">{{ $invoice['qty'][$index] ?? '' }}</td>
                            <td>{{ $invoice['line_class'][$index] ?? '' }}</td>
                            <td class="text-end">{{ $invoice['line_amount'][$index] ?? '' }}</td>
                            <td>{{ $invoice['tax_category'][$index] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <hr>
        <div class="d-flex flex-wrap gap-2">
            @foreach($printDocuments as $document)
                <a href="{{ $document['route'] }}" class="btn btn-outline-secondary"><i class="bi {{ $document['icon'] }} me-1"></i> {{ $document['label'] }}</a>
            @endforeach
            <a href="{{ route('erp.billings.print-layout.invoice') }}" class="btn btn-outline-secondary"><i class="bi bi-sliders me-1"></i> Adjust Print Layout</a>
            <a href="{{ route('erp.billings.status') }}" class="btn btn-outline-secondary"><i class="bi bi-toggle-on me-1"></i> Update Status</a>
            <a href="{{ route('erp.invoices.create') }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i> Create Another</a>
        </div>
    </div>
@endsection
