<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::get('/clients', function () {
        return response()->json([
            'data' => [
                ['id' => 'CUST-001', 'name' => 'North Harbor Trading', 'currency' => 'PHP', 'payment_terms' => '30 Days'],
                ['id' => 'CUST-002', 'name' => 'Blue Anchor Logistics', 'currency' => 'USD', 'payment_terms' => '60 Days'],
            ],
        ]);
    })->middleware('permission.access:clients,view');

    Route::get('/invoices', function () {
        return response()->json([
            'data' => [
                ['invoice_no' => 'BI-2026-01043', 'status' => 'draft', 'balance' => 21327.07, 'currency' => 'PHP'],
                ['invoice_no' => 'BI-2026-01042', 'status' => 'partial', 'balance' => 55300.00, 'currency' => 'PHP'],
            ],
        ]);
    })->middleware('permission.access:invoices,view');

    Route::get('/payments', function () {
        return response()->json([
            'data' => [
                ['payment_no' => 'OR-2026-00618', 'invoice_no' => 'BI-2026-01042', 'method' => 'bank_transfer', 'amount' => 43200.00],
            ],
        ]);
    })->middleware('permission.access:payments,view');

    Route::get('/expenses', function () {
        return response()->json([
            'data' => [
                ['expense_no' => 'EXP-2026-00871', 'vendor' => 'Pacific Fuel Services', 'amount' => 1325.00, 'currency' => 'USD'],
            ],
        ]);
    })->middleware('permission.access:payables,view');

    Route::get('/projects', function () {
        return response()->json([
            'data' => [
                ['project_no' => 'JOB-SEA-2026-0423', 'client' => 'Metro Manila Forwarders', 'status' => 'active'],
            ],
        ]);
    })->middleware('permission.access:clients,view');

    Route::get('/reports/revenue', function () {
        return response()->json([
            'period' => '2026-05',
            'currency' => 'PHP',
            'revenue' => 321727.07,
            'unpaid' => 55300.00,
            'overdue' => 12400.00,
        ]);
    })->middleware('permission.access:reports,view');

    Route::get('/webhook-events', function () {
        return response()->json([
            'events' => ['invoice.created', 'invoice.sent', 'invoice.viewed', 'invoice.paid', 'quote.approved', 'payment.failed'],
        ]);
    })->middleware('permission.access:invoices,view');
});
