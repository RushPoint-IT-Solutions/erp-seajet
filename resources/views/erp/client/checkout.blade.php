<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout {{ $checkout['invoice_no'] }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; color: #1f2937; font-family: Inter, "Segoe UI", Arial, sans-serif; }
        .checkout-shell { max-width: 760px; margin: 36px auto; }
        .checkout-card { background: #fff; border: 1px solid #e5e8ee; border-radius: 8px; box-shadow: 0 10px 28px rgba(15, 23, 42, .06); }
        .btn-primary { --bs-btn-bg: #fe790d; --bs-btn-border-color: #fe790d; --bs-btn-hover-bg: #df6500; --bs-btn-hover-border-color: #df6500; }
    </style>
</head>
<body>
<main class="checkout-shell">
    <div class="checkout-card p-4">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Secure Checkout</h1>
                <p class="text-muted mb-0">{{ $checkout['customer'] }} - Invoice {{ $checkout['invoice_no'] }}</p>
            </div>
            <div class="text-end">
                <div class="text-muted small">Payable Amount</div>
                <div class="h3 fw-bold">{{ $checkout['currency'] }} {{ $checkout['amount_due'] }}</div>
            </div>
        </div>
        <div class="alert alert-warning">
            Stripe keys are read from <code>STRIPE_KEY</code>, <code>STRIPE_SECRET</code>, and <code>STRIPE_WEBHOOK_SECRET</code>. This screen is ready for hosted checkout integration and does not store raw card data.
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Gateway Status</div><div class="fw-semibold">{{ $checkout['gateway'] }}</div></div></div>
            <div class="col-md-6"><div class="border rounded p-3 h-100"><div class="text-muted small">Webhook Endpoint</div><div class="fw-semibold">/webhooks/stripe</div></div></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.public.invoice', $checkout['token']) }}" class="btn btn-outline-secondary">Back to Invoice</a>
            <button class="btn btn-primary" type="button">Continue to Hosted Payment</button>
        </div>
    </div>
</main>
</body>
</html>
