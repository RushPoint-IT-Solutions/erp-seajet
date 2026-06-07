<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $kpis = [
            ['label' => 'Revenue', 'value' => 845230, 'trend' => '+12.8%', 'tone' => 'success', 'icon' => 'bi-graph-up-arrow'],
            ['label' => 'Expenses', 'value' => 382410, 'trend' => '+4.2%', 'tone' => 'warning', 'icon' => 'bi-wallet2'],
            ['label' => 'Profit', 'value' => 462820, 'trend' => '+18.1%', 'tone' => 'primary', 'icon' => 'bi-piggy-bank'],
            ['label' => 'Cash Balance', 'value' => 1299500, 'trend' => 'Healthy', 'tone' => 'info', 'icon' => 'bi-bank'],
            ['label' => 'Accounts Receivable', 'value' => 238700, 'trend' => '62 invoices', 'tone' => 'danger', 'icon' => 'bi-receipt-cutoff'],
            ['label' => 'Accounts Payable', 'value' => 174250, 'trend' => '31 bills', 'tone' => 'secondary', 'icon' => 'bi-credit-card'],
            ['label' => 'Bank Deposits', 'value' => 328900, 'trend' => '8 pending', 'tone' => 'dark', 'icon' => 'bi-bank'],
            ['label' => 'Current VAT Due', 'value' => 41280, 'trend' => 'For filing', 'tone' => 'muted', 'icon' => 'bi-percent'],
        ];

        $transactions = [
            ['date' => '2026-05-24', 'ref' => 'BI-2026-01042', 'party' => 'North Harbor Trading', 'type' => 'Billing Invoice', 'amount' => 98500, 'status' => 'Posted'],
            ['date' => '2026-05-24', 'ref' => 'OR-2026-00618', 'party' => 'Blue Anchor Logistics', 'type' => 'Official Receipt', 'amount' => 43200, 'status' => 'Cleared'],
            ['date' => '2026-05-23', 'ref' => 'PO-2026-00491', 'party' => 'Metro Industrial Supply', 'type' => 'Purchase Order', 'amount' => 127900, 'status' => 'Approved'],
            ['date' => '2026-05-23', 'ref' => 'JV-2026-00217', 'party' => 'Month-end accrual', 'type' => 'Journal Voucher', 'amount' => 31500, 'status' => 'Draft'],
        ];

        $aging = [
            ['bucket' => 'Current', 'amount' => 84200],
            ['bucket' => '30 Days', 'amount' => 61700],
            ['bucket' => '60 Days', 'amount' => 45900],
            ['bucket' => '90 Days', 'amount' => 28900],
            ['bucket' => '120+ Days', 'amount' => 17900],
        ];

        return view('erp.dashboard', compact('kpis', 'transactions', 'aging'));
    }

    public function transactions()
    {
        $data = [
            ['2026-05-24', 'BI-2026-01042', 'North Harbor Trading', 'Billing Invoice', '98,500.00', 'Posted'],
            ['2026-05-24', 'OR-2026-00618', 'Blue Anchor Logistics', 'Official Receipt', '43,200.00', 'Cleared'],
            ['2026-05-23', 'PO-2026-00491', 'Metro Industrial Supply', 'Purchase Order', '127,900.00', 'Approved'],
            ['2026-05-23', 'JV-2026-00217', 'Month-end accrual', 'Journal Voucher', '31,500.00', 'Draft'],
            ['2026-05-22', 'APV-2026-00372', 'Pacific Fuel Services', 'AP Voucher', '76,880.00', 'For Payment'],
        ];

        return response()->json(['data' => $data]);
    }
}
