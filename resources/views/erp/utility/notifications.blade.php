@extends('erp.layout')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
    <div class="erp-card p-3">
        <h1 class="h5 fw-bold mb-3">Notifications</h1>
        <div class="list-group list-group-flush">
            <a href="{{ route('erp.customer-billing.aging') }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                <span>3 customer invoices are nearing due date</span><span class="badge badge-soft">Aging</span>
            </a>
            <a href="{{ route('erp.payments.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                <span>2 payments pending receipt generation</span><span class="badge badge-soft">Collections</span>
            </a>
            <a href="{{ route('erp.customers.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                <span>Customer tax information review available</span><span class="badge badge-soft">Masterlist</span>
            </a>
        </div>
    </div>
@endsection
