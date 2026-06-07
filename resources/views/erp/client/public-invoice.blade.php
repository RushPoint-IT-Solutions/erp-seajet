<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice['invoice_no'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; color: #1f2937; font-family: Inter, "Segoe UI", Arial, sans-serif; }
        .portal-shell { max-width: 980px; margin: 32px auto; }
        .portal-card { background: #fff; border: 1px solid #e5e8ee; border-radius: 8px; box-shadow: 0 10px 28px rgba(15, 23, 42, .06); }
        .portal-accent { color: #fe790d; }
        .btn-primary { --bs-btn-bg: #fe790d; --bs-btn-border-color: #fe790d; --bs-btn-hover-bg: #df6500; --bs-btn-hover-border-color: #df6500; }
    </style>
</head>
<body>
<main class="portal-shell">
    <div class="portal-card p-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <img src="{{ asset('images/image.png') }}" alt="Company logo" style="height: 42px; object-fit: contain">
                <h1 class="h4 fw-bold mt-3 mb-1">Invoice {{ $invoice['invoice_no'] }}</h1>
                <p class="text-muted mb-0">Secure client invoice view for {{ $invoice['customer'] }}</p>
            </div>
            <div class="text-end">
                <div class="text-muted small">Amount Due</div>
                <div class="h3 fw-bold portal-accent">{{ $invoice['currency'] }} {{ $invoice['amount_due'] }}</div>
                <span class="badge text-bg-warning">{{ $invoice['status'] }}</span>
            </div>
        </div>
    </div>

    <div class="portal-card p-4">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="text-muted small">Due Date</div><div class="fw-semibold">{{ $invoice['due_date'] }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Access Token</div><div class="fw-semibold">{{ $invoice['token'] }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Payment Security</div><div class="fw-semibold">Hosted checkout, no raw card storage</div></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                @foreach($invoice['items'] as $item)
                    <tr>
                        <td>{{ $item[0] }}</td>
                        <td class="text-end">{{ $item[1] }}</td>
                        <td class="text-end">{{ $item[2] }}</td>
                        <td class="text-end">{{ $item[3] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('erp.billings.print-invoice') }}" class="btn btn-outline-secondary">Download PDF</a>
            <a href="{{ route('erp.public.checkout', $invoice['token']) }}" class="btn btn-primary">Pay Now</a>
        </div>
    </div>
</main>
</body>
</html>
