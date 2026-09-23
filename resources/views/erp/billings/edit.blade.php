@extends('erp.layout')

@section('title', 'Edit Billing')
@section('page-title', 'Edit Billing')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Edit Billing {{ $invoiceNo }}</h1>
            <p class="text-muted mb-0">Update billing information before payment, posting, or printing.</p>
        </div>
        <a href="{{ route('erp.billings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Billings</a>
    </div>
    <form class="erp-card p-4" method="post" action="{{ route('erp.invoices.review') }}">
        {{ csrf_field() }}
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Invoice Number</label><input name="invoice_no" class="form-control" value="{{ $invoiceNo }}" required></div>
            <div class="col-md-3"><label class="form-label">SOA Number</label><input name="soa_no" class="form-control" value="SOA-2026-07187"></div>
            <div class="col-md-3"><label class="form-label">Invoice Date</label><input name="invoice_date" type="date" class="form-control" value="2026-05-24" required></div>
            <div class="col-md-3"><label class="form-label">Due Date</label><input name="due_date" type="date" class="form-control" value="2026-06-23"></div>
            <div class="col-md-3"><label class="form-label">Credit Terms</label><select name="credit_term_days" class="form-select erp-select"><option>30</option><option>60</option><option>90</option></select></div>
            <div class="col-md-4"><label class="form-label">Customer ID</label><select name="customer_id" class="form-select erp-select">@foreach($customers as $customer)<option>{{ $customer['customer_id'] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Customer Name</label><select name="customer_name" class="form-select erp-select">@foreach($customers as $customer)<option>{{ $customer['name'] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Shipment Job ID</label><input name="job_shipment_id" class="form-control" value="JOB-SEA-2026-0423" required></div>
            <div class="col-md-3"><label class="form-label">VAT Classification</label><select name="vat_classification" class="form-select erp-select"><option>12%</option><option>zero-rated</option><option>exempt</option></select></div>
            <div class="col-md-3"><label class="form-label">Class</label><select name="class_code" class="form-select erp-select"><option>MANILA</option><option>PAMPANGA</option><option>CEBU</option></select></div>
            <div class="col-md-3"><label class="form-label">Currency</label><select name="currency" class="form-select erp-select"><option>PHP</option><option>USD</option></select></div>
            <div class="col-md-3"><label class="form-label">Amount</label><input name="amount" class="form-control" value="98500.00" required></div>
            <div class="col-md-12"><label class="form-label">AR Account</label><select name="ar_account" class="form-select erp-select"><option>A/Trade-Billing Invoice</option><option>A/R Reimbursable Charges</option><option>A/R Trade Metro</option><option>A/R Trade-Debit Note</option></select></div>
            <div class="col-md-3"><label class="form-label">MAWB B/L No.</label><input name="mawb_no" class="form-control" value="OOLU32322350120"></div>
            <div class="col-md-3"><label class="form-label">HAWB B/L No.</label><input name="hawb_no" class="form-control" value="2GJXM412-G1"></div>
            <div class="col-md-3"><label class="form-label">Date Shipped</label><input name="date_shipped" type="date" class="form-control" value="2026-05-19"></div>
            <div class="col-md-3"><label class="form-label">Commodity</label><input name="commodity" class="form-control" value="3 x 40'HC"></div>
            <div class="col-md-12"><label class="form-label">Origin / Destination</label><input name="origin_dest" class="form-control" value="ROTTERDAM / MANILA"></div>
            <div class="col-12">
                <label class="form-label">Item Description/Nature of Service</label>
                <textarea name="item_description[]" class="form-control" rows="5">OCEAN FREIGHT (5% OF FREIGHT CHARGES)
(2,100 x 3 CNTRS = $6,300 x 61.55 = 387,765 x 5%)</textarea>
            </div>
            <div class="col-md-4"><label class="form-label">Qty</label><input name="qty[]" class="form-control text-end" value="1"></div>
            <div class="col-md-4"><label class="form-label">Unit Price</label><input name="unit_price[]" class="form-control text-end" value="19388.25"></div>
            <div class="col-md-4"><label class="form-label">Amount</label><input name="line_amount[]" class="form-control text-end" value="19388.25"></div>
            <div class="col-12"><label class="form-label">Footer Remarks</label><textarea name="description" class="form-control" rows="3">Please issue check payable to Sea-Jet International Forwarders, Inc.</textarea></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('erp.billings.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Review Billing</button>
        </div>
    </form>
@endsection
