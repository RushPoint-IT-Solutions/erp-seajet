<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\Security\RbacService;

class DashboardController extends Controller
{
    public function index()
    {
        $rbac = app(RbacService::class);
        $user = auth()->user();
        $dashboardAccess = [
            'ar' => $rbac->allows($user, 'invoices', 'view') || $rbac->allows($user, 'payments', 'view'),
            'ap' => $rbac->allows($user, 'payables', 'view'),
            'banking' => $rbac->allows($user, 'banking', 'view'),
            'accounting' => $rbac->allows($user, 'accounting', 'view'),
        ];
        $salesTotal = $this->sumTable('sales_documents', 'grand_total', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided']);
        });
        $salesPaid = $this->sumTable('sales_documents', 'paid_total', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided']);
        });
        $purchaseTotal = $this->sumTable('purchase_documents', 'grand_total', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided']);
        });
        $purchasePaid = $this->sumTable('purchase_documents', 'paid_total', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided']);
        });
        $cashBalance = $this->sumTable('bank_accounts', 'book_balance', function ($query) {
            $query->where('is_active', true);
        });
        $arBalance = max(0, $salesTotal - $salesPaid);
        $apBalance = max(0, $purchaseTotal - $purchasePaid);
        $vatDue = $this->sumTable('sales_documents', 'tax_total', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided']);
        }) - $this->sumTable('purchase_documents', 'tax_total', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided']);
        });
        $openInvoiceCount = $this->countTable('sales_documents', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided', 'paid']);
        });
        $openBillCount = $this->countTable('purchase_documents', function ($query) {
            $query->whereNotIn('status', ['cancelled', 'voided', 'paid']);
        });
        $unreconciledDeposits = $this->sumTable('bank_transactions', 'debit', function ($query) {
            $query->where('is_reconciled', false);
        });

        $kpis = [];
        if ($dashboardAccess['ar']) {
            $kpis[] = ['label' => 'Revenue', 'value' => $salesTotal, 'trend' => $this->countTable('sales_documents').' sales docs', 'tone' => 'success', 'icon' => 'bi-graph-up-arrow'];
            $kpis[] = ['label' => 'Accounts Receivable', 'value' => $arBalance, 'trend' => $openInvoiceCount.' open invoices', 'tone' => 'danger', 'icon' => 'bi-receipt-cutoff'];
        }
        if ($dashboardAccess['ap']) {
            $kpis[] = ['label' => 'Expenses', 'value' => $purchaseTotal, 'trend' => $this->countTable('purchase_documents').' AP docs', 'tone' => 'warning', 'icon' => 'bi-wallet2'];
            $kpis[] = ['label' => 'Accounts Payable', 'value' => $apBalance, 'trend' => $openBillCount.' open bills', 'tone' => 'secondary', 'icon' => 'bi-credit-card'];
        }
        if ($dashboardAccess['ar'] && $dashboardAccess['ap']) {
            $kpis[] = ['label' => 'Profit', 'value' => $salesTotal - $purchaseTotal, 'trend' => 'Revenue less AP', 'tone' => 'primary', 'icon' => 'bi-piggy-bank'];
        }
        if ($dashboardAccess['banking']) {
            $kpis[] = ['label' => 'Cash Balance', 'value' => $cashBalance, 'trend' => $this->countTable('bank_accounts', function ($query) { $query->where('is_active', true); }).' active banks', 'tone' => 'info', 'icon' => 'bi-bank'];
            $kpis[] = ['label' => 'Bank Deposits', 'value' => $unreconciledDeposits, 'trend' => $this->countTable('bank_transactions', function ($query) { $query->where('is_reconciled', false); }).' unreconciled', 'tone' => 'dark', 'icon' => 'bi-bank'];
        }
        if ($dashboardAccess['accounting']) {
            $kpis[] = ['label' => 'Current VAT Due', 'value' => $vatDue, 'trend' => 'Output less input VAT', 'tone' => 'muted', 'icon' => 'bi-percent'];
        }

        $transactions = $this->recentTransactions();
        $aging = $dashboardAccess['ar'] ? $this->arAging() : [];
        $chart = $this->financialTrend($dashboardAccess['ar'], $dashboardAccess['ap']);
        $approvalQueue = [];
        if ($dashboardAccess['ap']) {
            $approvalQueue[] = ['label' => 'Purchase Orders', 'count' => $this->countTable('purchase_documents', function ($query) { $query->whereIn('status', ['draft', 'pending', 'for approval', 'approved']); })];
            $approvalQueue[] = ['label' => 'Supplier Bills', 'count' => $this->countTable('purchase_documents', function ($query) { $query->whereNotIn('status', ['paid', 'cancelled', 'voided']); })];
        }
        if ($dashboardAccess['accounting']) {
            $approvalQueue[] = ['label' => 'Journal Entries', 'count' => $this->countTable('journal_entries', function ($query) { $query->where('status', 'draft'); })];
        }

        $quickActions = [];
        foreach ([
            ['module' => 'invoices', 'action' => 'create', 'label' => 'Create Invoice', 'icon' => 'bi-file-earmark-plus', 'route' => route('erp.invoices.create')],
            ['module' => 'clients', 'action' => 'view', 'label' => 'Customer Masterlist', 'icon' => 'bi-people', 'route' => route('erp.customers.index')],
            ['module' => 'payments', 'action' => 'create', 'label' => 'Receive Payment', 'icon' => 'bi-cash', 'route' => route('erp.payments.create')],
            ['module' => 'ar_reports', 'action' => 'view', 'label' => 'Aging Report', 'icon' => 'bi-hourglass-split', 'route' => route('erp.customer-billing.aging')],
            ['module' => 'accounting', 'action' => 'create', 'label' => 'Journal Entry', 'icon' => 'bi-journal-plus', 'route' => route('erp.action', 'journal-entry')],
            ['module' => 'banking', 'action' => 'create', 'label' => 'Bank Transfer', 'icon' => 'bi-arrow-left-right', 'route' => route('erp.action', 'bank-transfer')],
        ] as $item) {
            if ($rbac->allows($user, $item['module'], $item['action'])) {
                $quickActions[] = $item;
            }
        }

        return view('erp.dashboard', compact('kpis', 'transactions', 'aging', 'chart', 'approvalQueue', 'dashboardAccess', 'quickActions'));
    }

    public function transactions()
    {
        $data = array_map(function ($transaction) {
            return [
                $transaction['date'],
                $transaction['ref'],
                $transaction['party'],
                $transaction['type'],
                number_format($transaction['amount'], 2),
                $transaction['status'],
            ];
        }, $this->recentTransactions(50));

        return response()->json(['data' => $data]);
    }

    private function recentTransactions($limit = 10)
    {
        $transactions = collect();
        $companyId = $this->companyId();

        $rbac = app(RbacService::class);
        $user = auth()->user();

        if ($rbac->allows($user, 'invoices', 'view') && Schema::hasTable('sales_documents')) {
            $transactions = $transactions->merge(DB::table('sales_documents')
                ->leftJoin('customers', 'customers.id', '=', 'sales_documents.customer_id')
                ->select(
                    'sales_documents.document_date as date',
                    'sales_documents.document_no as ref',
                    'customers.name as party',
                    'sales_documents.document_type as type',
                    'sales_documents.grand_total as amount',
                    'sales_documents.status'
                )
                ->where('sales_documents.company_id', $companyId)
                ->orderBy('sales_documents.document_date', 'desc')
                ->orderBy('sales_documents.id', 'desc')
                ->limit($limit)
                ->get());
        }

        if ($rbac->allows($user, 'payments', 'view') && Schema::hasTable('receipts')) {
            $transactions = $transactions->merge(DB::table('receipts')
                ->leftJoin('customers', 'customers.id', '=', 'receipts.customer_id')
                ->select(
                    'receipts.receipt_date as date',
                    'receipts.receipt_no as ref',
                    'customers.name as party',
                    DB::raw("'Collection Receipt' as type"),
                    'receipts.amount',
                    'receipts.status'
                )
                ->where('receipts.company_id', $companyId)
                ->orderBy('receipts.receipt_date', 'desc')
                ->orderBy('receipts.id', 'desc')
                ->limit($limit)
                ->get());
        }

        if ($rbac->allows($user, 'payables', 'view') && Schema::hasTable('purchase_documents')) {
            $transactions = $transactions->merge(DB::table('purchase_documents')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_documents.supplier_id')
                ->select(
                    'purchase_documents.document_date as date',
                    'purchase_documents.document_no as ref',
                    'suppliers.name as party',
                    'purchase_documents.document_type as type',
                    'purchase_documents.grand_total as amount',
                    'purchase_documents.status'
                )
                ->where('purchase_documents.company_id', $companyId)
                ->orderBy('purchase_documents.document_date', 'desc')
                ->orderBy('purchase_documents.id', 'desc')
                ->limit($limit)
                ->get());
        }

        if ($rbac->allows($user, 'payables', 'view') && Schema::hasTable('payments')) {
            $transactions = $transactions->merge(DB::table('payments')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
                ->select(
                    'payments.payment_date as date',
                    'payments.payment_no as ref',
                    'suppliers.name as party',
                    DB::raw("'Supplier Payment' as type"),
                    'payments.amount',
                    'payments.status'
                )
                ->where('payments.company_id', $companyId)
                ->orderBy('payments.payment_date', 'desc')
                ->orderBy('payments.id', 'desc')
                ->limit($limit)
                ->get());
        }

        if ($rbac->allows($user, 'accounting', 'view') && Schema::hasTable('journal_entries')) {
            $journalParty = Schema::hasColumn('journal_entries', 'memo')
                ? DB::raw("COALESCE(memo, 'Journal Entry') as party")
                : DB::raw("'Journal Entry' as party");

            $transactions = $transactions->merge(DB::table('journal_entries')
                ->select(
                    'journal_date as date',
                    'journal_no as ref',
                    $journalParty,
                    DB::raw("'Journal Entry' as type"),
                    DB::raw('0 as amount'),
                    'status'
                )
                ->where('company_id', $companyId)
                ->orderBy('journal_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get());
        }

        return $transactions
            ->sortByDesc(function ($transaction) {
                return (string) $transaction->date.' '.(string) $transaction->ref;
            })
            ->take($limit)
            ->map(function ($transaction) {
                return [
                    'date' => $transaction->date,
                    'ref' => $transaction->ref,
                    'party' => $transaction->party ?: '-',
                    'type' => ucwords(str_replace('_', ' ', $transaction->type)),
                    'amount' => (float) $transaction->amount,
                    'status' => ucwords(str_replace('_', ' ', $transaction->status)),
                ];
            })
            ->values()
            ->all();
    }

    private function arAging()
    {
        $buckets = [
            'Current' => 0.0,
            '30 Days' => 0.0,
            '60 Days' => 0.0,
            '90 Days' => 0.0,
            '120+ Days' => 0.0,
        ];

        if (!Schema::hasTable('sales_documents')) {
            return $this->bucketRows($buckets);
        }

        DB::table('sales_documents')
            ->select('due_date', 'grand_total', 'paid_total')
            ->where('company_id', $this->companyId())
            ->whereNotIn('status', ['cancelled', 'voided', 'paid'])
            ->get()
            ->each(function ($invoice) use (&$buckets) {
                $balance = max(0, (float) $invoice->grand_total - (float) $invoice->paid_total);
                if ($balance <= 0) {
                    return;
                }

                $daysPastDue = $invoice->due_date ? now()->diffInDays(\Carbon\Carbon::parse($invoice->due_date), false) * -1 : 0;
                if ($daysPastDue <= 0) {
                    $buckets['Current'] += $balance;
                } elseif ($daysPastDue <= 30) {
                    $buckets['30 Days'] += $balance;
                } elseif ($daysPastDue <= 60) {
                    $buckets['60 Days'] += $balance;
                } elseif ($daysPastDue <= 120) {
                    $buckets['90 Days'] += $balance;
                } else {
                    $buckets['120+ Days'] += $balance;
                }
            });

        return $this->bucketRows($buckets);
    }

    private function financialTrend($includeAr = true, $includeAp = true)
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        $profit = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $month = now()->copy()->subMonths($offset);
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();
            $monthRevenue = $includeAr ? $this->sumTable('sales_documents', 'grand_total', function ($query) use ($start, $end) {
                $query->whereBetween('document_date', [$start, $end])->whereNotIn('status', ['cancelled', 'voided']);
            }) : 0;
            $monthExpenses = $includeAp ? $this->sumTable('purchase_documents', 'grand_total', function ($query) use ($start, $end) {
                $query->whereBetween('document_date', [$start, $end])->whereNotIn('status', ['cancelled', 'voided']);
            }) + $this->sumTable('payments', 'amount', function ($query) use ($start, $end) {
                $query->whereBetween('payment_date', [$start, $end])->whereNotIn('status', ['cancelled', 'voided']);
            }) : 0;

            $labels[] = $month->format('M');
            $revenue[] = round($monthRevenue, 2);
            $expenses[] = round($monthExpenses, 2);
            $profit[] = round($monthRevenue - $monthExpenses, 2);
        }

        return compact('labels', 'revenue', 'expenses', 'profit');
    }

    private function bucketRows(array $buckets)
    {
        return collect($buckets)->map(function ($amount, $bucket) {
            return ['bucket' => $bucket, 'amount' => $amount];
        })->values()->all();
    }

    private function sumTable($table, $column, callable $scope = null)
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return 0.0;
        }

        $query = DB::table($table);
        $this->scopeCompany($query, $table);
        if ($scope) {
            $scope($query);
        }

        return (float) $query->sum($column);
    }

    private function countTable($table, callable $scope = null)
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        $this->scopeCompany($query, $table);
        if ($scope) {
            $scope($query);
        }

        return (int) $query->count();
    }

    private function scopeCompany($query, $table)
    {
        $companyId = $this->companyId();
        if (!$companyId) {
            return;
        }
        if (Schema::hasColumn($table, 'company_id')) {
            $query->where($table.'.company_id', $companyId);
        } elseif ($table === 'bank_transactions' && Schema::hasTable('bank_accounts')) {
            $query->whereIn('bank_account_id', DB::table('bank_accounts')
                ->where('company_id', $companyId)
                ->select('id'));
        }
    }

    private function companyId()
    {
        $user = auth()->user();
        if ($user && !empty($user->company_id)) {
            return $user->company_id;
        }

        return Schema::hasTable('companies') ? DB::table('companies')->orderBy('id')->value('id') : null;
    }
}
