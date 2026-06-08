<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SeaJetTestDataSeeder extends Seeder
{
    public function run()
    {
        $now = now();
        $companyId = $this->company($now);
        $branches = $this->branches($companyId, $now);
        $accounts = $this->accounts($companyId, $now);

        $this->users($companyId, $branches['MAIN'], $now);
        $this->currencies($now);
        $this->numberSeries($companyId, $now);
        $this->financeSettings($companyId, $branches, $accounts, $now);
        $customers = $this->customers($companyId, $now);
        $suppliers = $this->suppliers($companyId, $now);
        $items = $this->items($companyId, $now);
        $taxCodes = $this->taxCodes($companyId, $accounts, $now);
        $banks = $this->bankAccounts($companyId, $accounts, $now);

        $salesDocs = $this->salesDocuments($companyId, $branches, $customers, $items, $taxCodes, $now);
        $this->receipts($companyId, $customers, $salesDocs, $now);
        $purchaseDocs = $this->purchaseDocuments($companyId, $branches, $suppliers, $now);
        $this->payments($companyId, $suppliers, $purchaseDocs, $now);
        $this->bankTransactions($banks, $now);
        $this->journalEntries($companyId, $branches, $accounts, $customers, $suppliers, $now);
        $this->approvalRequests($companyId, $purchaseDocs, $now);
        $this->printLayouts($companyId, $now);
    }

    private function company($now)
    {
        $this->upsert('companies', ['code' => 'SJ'], [
            'code' => 'SJ',
            'name' => 'SEA-JET INTERNATIONAL FORWARDERS INC.',
            'tin' => '000-123-456-000',
            'address' => 'Harbor Business Center, Manila, Philippines',
            'base_currency' => 'PHP',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('companies')->where('code', 'SJ')->value('id');
    }

    private function branches($companyId, $now)
    {
        $rows = [
            'MAIN' => ['Main Branch', 'Manila'],
            'CEB' => ['Cebu Branch', 'Cebu City'],
            'PAM' => ['Pampanga Branch', 'San Fernando, Pampanga'],
        ];

        $ids = [];
        foreach ($rows as $code => $row) {
            $this->upsert('branches', ['company_id' => $companyId, 'code' => $code], [
                'company_id' => $companyId,
                'code' => $code,
                'name' => $row[0],
                'address' => $row[1],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$code] = DB::table('branches')->where('company_id', $companyId)->where('code', $code)->value('id');
        }

        return $ids;
    }

    private function users($companyId, $branchId, $now)
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        foreach ([
            ['ERP Admin', 'admin@seajet.test', 'admin', 'Accounting'],
            ['Finance User', 'finance@seajet.test', 'finance', 'Finance'],
            ['Accounting User', 'accounting@seajet.test', 'accounting', 'Accounting'],
        ] as $user) {
            $this->upsert('users', ['email' => $user[1]], [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'name' => $user[0],
                'email' => $user[1],
                'password' => Hash::make('password'),
                'role' => $user[2],
                'department' => $user[3],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function currencies($now)
    {
        foreach ([
            ['PHP', 'Philippine Peso', 1, true],
            ['USD', 'US Dollar', 58.250000, false],
        ] as $row) {
            $this->upsert('currencies', ['code' => $row[0]], [
                'code' => $row[0],
                'name' => $row[1],
                'exchange_rate' => $row[2],
                'is_base' => $row[3],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function accounts($companyId, $now)
    {
        $categoryIds = [];
        foreach ([
            'Assets' => 'debit',
            'Liabilities' => 'credit',
            'Equity' => 'credit',
            'Revenue' => 'credit',
            'Expenses' => 'debit',
        ] as $name => $normal) {
            $this->upsert('account_categories', ['name' => $name], [
                'name' => $name,
                'normal_balance' => $normal,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryIds[$name] = DB::table('account_categories')->where('name', $name)->value('id');
        }

        $rows = [
            ['Assets', '1000', 'Cash on Hand', 'Current Asset', 245000],
            ['Assets', '1010', 'Bank - BDO PHP', 'Current Asset', 1023500],
            ['Assets', '1020', 'Bank - Metrobank USD', 'Current Asset', 1180000],
            ['Assets', '1200', 'Accounts Receivable', 'Current Asset', 238700],
            ['Assets', '1210', 'A/Trade-Billing Invoice', 'Current Asset', 98500],
            ['Assets', '1220', 'A/R Reimbursable Charges', 'Current Asset', 12800],
            ['Assets', '1230', 'A/R Trade-SOA A', 'Current Asset', 106720],
            ['Liabilities', '2100', 'Accounts Payable', 'Current Liability', 174250],
            ['Liabilities', '2200', 'Output VAT Payable', 'Current Liability', 41280],
            ['Liabilities', '2300', 'Withholding Tax Payable', 'Current Liability', 9400],
            ['Equity', '3000', 'Owner Equity', 'Equity', 1000000],
            ['Revenue', '4000', 'Service Revenue', 'Revenue', 845230],
            ['Expenses', '5000', 'Cost of Services', 'Expense', 240300],
            ['Expenses', '5100', 'Operating Expenses', 'Expense', 142110],
            ['Expenses', '5200', 'Bank Charges', 'Expense', 12500],
        ];

        $ids = [];
        foreach ($rows as $row) {
            $this->upsert('accounts', ['company_id' => $companyId, 'code' => $row[1]], [
                'company_id' => $companyId,
                'account_category_id' => $categoryIds[$row[0]],
                'code' => $row[1],
                'name' => $row[2],
                'type' => $row[3],
                'opening_balance' => $row[4],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$row[1]] = DB::table('accounts')->where('company_id', $companyId)->where('code', $row[1])->value('id');
        }

        return $ids;
    }

    private function numberSeries($companyId, $now)
    {
        foreach ([
            ['billing_invoice', 'BI-', 1045, 6],
            ['statement_of_account', 'SOA-', 7190, 5],
            ['statement_of_account_a', 'SOAA-', 910, 4],
            ['debit_note', 'DN-', 17745, 6],
            ['official_receipt', 'OR-', 620, 5],
            ['acknowledgement_receipt', 'AR-', 7500, 6],
            ['vendor_bill', 'BILL-', 500, 5],
            ['payment_voucher', 'PV-', 320, 5],
            ['check_voucher', 'CV-', 425, 5],
            ['journal_voucher', 'JV-', 220, 5],
        ] as $row) {
            $this->upsert('number_series', ['company_id' => $companyId, 'document_type' => $row[0]], [
                'company_id' => $companyId,
                'document_type' => $row[0],
                'prefix' => $row[1],
                'current_number' => $row[2],
                'padding' => $row[3],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function financeSettings($companyId, $branches, $accounts, $now)
    {
        foreach ([
            ['CC-MNL-OPS', 'Manila Operations', 'Operations', $branches['MAIN']],
            ['CC-CEB-OPS', 'Cebu Operations', 'Operations', $branches['CEB']],
            ['CC-PAM-OPS', 'Pampanga Operations', 'Operations', $branches['PAM']],
            ['CC-HQ-FIN', 'Head Office Finance', 'Finance', $branches['MAIN']],
        ] as $row) {
            $this->upsert('cost_centers', ['company_id' => $companyId, 'code' => $row[0]], [
                'company_id' => $companyId,
                'branch_id' => $row[3],
                'code' => $row[0],
                'name' => $row[1],
                'department' => $row[2],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ([
            ['USD', 'PHP', '2026-06-08', 58.250000, 'BSP'],
            ['USD', 'PHP', '2026-06-07', 58.150000, 'BSP'],
            ['PHP', 'USD', '2026-06-08', 0.017167, 'System'],
        ] as $row) {
            $this->upsert('currency_rates', [
                'company_id' => $companyId,
                'source_currency' => $row[0],
                'target_currency' => $row[1],
                'rate_date' => $row[2],
            ], [
                'company_id' => $companyId,
                'source_currency' => $row[0],
                'target_currency' => $row[1],
                'rate_date' => $row[2],
                'rate' => $row[3],
                'rate_source' => $row[4],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function taxCodes($companyId, $accounts, $now)
    {
        $rows = [
            ['VAT12', 'Output/Input VAT 12%', 12, 'VAT', 'VAT', false, null, $accounts['2200']],
            ['ZERO', 'Zero-rated', 0, 'VAT', 'Zero-rated', false, null, null],
            ['EXEMPT', 'VAT Exempt', 0, 'VAT', 'Exempt', false, null, null],
            ['EWT2', 'Expanded Withholding Tax 2%', 2, 'Withholding', 'EWT', true, 'Expanded', $accounts['2300']],
            ['EWT5', 'Expanded Withholding Tax 5%', 5, 'Withholding', 'EWT', true, 'Expanded', $accounts['2300']],
            ['CWT2', 'Creditable Withholding Tax 2%', 2, 'Withholding', 'CWT', true, 'Creditable', $accounts['2300']],
        ];

        $ids = [];
        foreach ($rows as $row) {
            $this->upsert('tax_codes', ['company_id' => $companyId, 'code' => $row[0]], [
                'company_id' => $companyId,
                'code' => $row[0],
                'name' => $row[1],
                'rate' => $row[2],
                'tax_type' => $row[3],
                'classification' => $row[4],
                'is_withholding' => $row[5],
                'withholding_kind' => $row[6],
                'account_id' => $row[7],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$row[0]] = DB::table('tax_codes')->where('company_id', $companyId)->where('code', $row[0])->value('id');
        }

        return $ids;
    }

    private function customers($companyId, $now)
    {
        $rows = [
            ['CUST-001', 'ASA COLOR TRADING CORPORATION', '000-111-222-000', 'ap@asacolor.test', '2060 Manila', '30 Days', 'PHP'],
            ['CUST-002', 'MCC LABELS (MANILA) PHILIPPINES INC.', '000-333-444-000', 'finance@mcclabels.test', 'Laguna Technopark', '30 Days', 'PHP'],
            ['CUST-003', 'BLUE ANCHOR LOGISTICS', '000-555-666-000', 'billing@blueanchor.test', 'Cebu City', '60 Days', 'USD'],
        ];
        $ids = [];
        foreach ($rows as $row) {
            $this->upsert('customers', ['company_id' => $companyId, 'code' => $row[0]], [
                'company_id' => $companyId,
                'code' => $row[0],
                'name' => $row[1],
                'tin' => $row[2],
                'email' => $row[3],
                'billing_address' => $row[4],
                'payment_terms' => $row[5],
                'currency' => $row[6],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$row[0]] = DB::table('customers')->where('company_id', $companyId)->where('code', $row[0])->value('id');
        }
        return $ids;
    }

    private function suppliers($companyId, $now)
    {
        $rows = [
            ['SUP-001', 'UTS LOGISTICS AND DISTRIBUTION CO., INC.', '000-777-888-000', '2060 Dr. Manuel Carreon St., Brgy 866 Zone 095 Sta Ana Manila', '30 Days', 'PHP', 'Cheque'],
            ['SUP-002', 'PACIFIC FUEL SERVICES', '000-510-294-000', 'Pier 7 Fuel Depot, Cebu City', '20 Days', 'USD', 'Bank Transfer'],
            ['SUP-003', 'HARBOR OFFICE CENTER', '000-772-130-000', 'Paranaque City', '30 Days', 'PHP', 'Cheque'],
            ['SUP-004', 'METRO INDUSTRIAL SUPPLY', '000-891-205-000', 'Binondo, Manila', '30 Days', 'PHP', 'Cheque'],
        ];
        $ids = [];
        foreach ($rows as $row) {
            $this->upsert('suppliers', ['company_id' => $companyId, 'code' => $row[0]], [
                'company_id' => $companyId,
                'code' => $row[0],
                'name' => $row[1],
                'tin' => $row[2],
                'address' => $row[3],
                'payment_terms' => $row[4],
                'currency' => $row[5],
                'preferred_payment_method' => $row[6],
                'default_ap_account' => 'Accounts Payable Trade',
                'default_class_code' => 'MANILA',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$row[0]] = DB::table('suppliers')->where('company_id', $companyId)->where('code', $row[0])->value('id');
        }
        return $ids;
    }

    private function items($companyId, $now)
    {
        $rows = [
            ['SVC-FREIGHT', 'Ocean Freight', 'Service', 'job', 0, 65000],
            ['SVC-THC', 'Terminal Handling', 'Service', 'job', 0, 18500],
            ['SVC-DOC', 'Documentation Fee', 'Service', 'job', 0, 3500],
            ['REIMB-PORT', 'Port Advances / Reimbursable', 'Reimbursable', 'job', 0, 0],
            ['AP-WAREHOUSE', 'Warehousing Charges', 'AP Expense', 'job', 0, 0],
            ['BANK-FEE', 'Bank Charges', 'Expense', 'txn', 0, 0],
        ];
        $ids = [];
        foreach ($rows as $row) {
            $this->upsert('items', ['company_id' => $companyId, 'sku' => $row[0]], [
                'company_id' => $companyId,
                'sku' => $row[0],
                'name' => $row[1],
                'category' => $row[2],
                'unit_of_measure' => $row[3],
                'standard_cost' => $row[4],
                'selling_price' => $row[5],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$row[0]] = DB::table('items')->where('company_id', $companyId)->where('sku', $row[0])->value('id');
        }
        return $ids;
    }

    private function bankAccounts($companyId, $accounts, $now)
    {
        $rows = [
            ['BDO', 'Manila', 'SEA-JET PHP OPERATING', '4898002879', 'PHP', $accounts['1010'], 1250000, 1255000],
            ['Metrobank', 'Cebu', 'SEA-JET USD COLLECTIONS', 'USD-8821900', 'USD', $accounts['1020'], 20260, 20260],
        ];
        $ids = [];
        foreach ($rows as $row) {
            $this->upsert('bank_accounts', ['company_id' => $companyId, 'account_no' => $row[3]], [
                'company_id' => $companyId,
                'bank_name' => $row[0],
                'branch_name' => $row[1],
                'account_name' => $row[2],
                'account_no' => $row[3],
                'currency' => $row[4],
                'account_id' => $row[5],
                'book_balance' => $row[6],
                'bank_balance' => $row[7],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ids[$row[3]] = DB::table('bank_accounts')->where('company_id', $companyId)->where('account_no', $row[3])->value('id');
        }
        return $ids;
    }

    private function salesDocuments($companyId, $branches, $customers, $items, $taxCodes, $now)
    {
        $docs = [
            ['BI-2026-01042', $customers['CUST-001'], 'Billing Invoice', '2026-06-01', '2026-07-01', 'JOB-SEA-2026-0422', 'PHP', 1, 98500, 11820, 110320, 'posted'],
            ['SOAA-2026-00211', $customers['CUST-003'], 'Statement A', '2026-06-03', '2026-07-03', 'JOB-SEA-2026-0398', 'USD', 58.25, 1840, 0, 1840, 'sent'],
        ];
        $ids = [];
        foreach ($docs as $doc) {
            $this->upsert('sales_documents', ['company_id' => $companyId, 'document_no' => $doc[0]], [
                'company_id' => $companyId,
                'branch_id' => $branches['MAIN'],
                'customer_id' => $doc[1],
                'document_type' => $doc[2],
                'document_no' => $doc[0],
                'document_date' => $doc[3],
                'due_date' => $doc[4],
                'shipment_reference' => $doc[5],
                'job_shipment_id' => $doc[5],
                'currency' => $doc[6],
                'exchange_rate' => $doc[7],
                'subtotal' => $doc[8],
                'tax_total' => $doc[9],
                'grand_total' => $doc[10],
                'paid_total' => 0,
                'status' => $doc[11],
                'remarks' => 'Seeded test sales document.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = DB::table('sales_documents')->where('company_id', $companyId)->where('document_no', $doc[0])->value('id');
            $ids[$doc[0]] = $id;
            if (Schema::hasTable('sales_document_lines')) {
                DB::table('sales_document_lines')->where('sales_document_id', $id)->delete();
                DB::table('sales_document_lines')->insert($this->payload('sales_document_lines', [
                    'sales_document_id' => $id,
                    'item_id' => $items['SVC-FREIGHT'],
                    'description' => 'Freight and logistics services '.$doc[5],
                    'quantity' => 1,
                    'unit_price' => $doc[8],
                    'tax_code_id' => $doc[6] === 'USD' ? $taxCodes['ZERO'] : $taxCodes['VAT12'],
                    'line_total' => $doc[8],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
        return $ids;
    }

    private function receipts($companyId, $customers, $salesDocs, $now)
    {
        $rows = [
            ['OR-2026-00618', $customers['CUST-001'], '2026-06-06', 'Check', 'CHK-88901', 50000, $salesDocs['BI-2026-01042']],
            ['AR-2026-00109', $customers['CUST-003'], '2026-06-07', 'Bank Transfer', 'BNK-USD-778', 1000, $salesDocs['SOAA-2026-00211']],
        ];
        foreach ($rows as $row) {
            $this->upsert('receipts', ['company_id' => $companyId, 'receipt_no' => $row[0]], [
                'company_id' => $companyId,
                'customer_id' => $row[1],
                'receipt_no' => $row[0],
                'receipt_date' => $row[2],
                'payment_method' => $row[3],
                'reference_no' => $row[4],
                'amount' => $row[5],
                'status' => 'posted',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $receiptId = DB::table('receipts')->where('company_id', $companyId)->where('receipt_no', $row[0])->value('id');
            $this->upsert('receipt_applications', ['receipt_id' => $receiptId, 'sales_document_id' => $row[6]], [
                'receipt_id' => $receiptId,
                'sales_document_id' => $row[6],
                'amount_applied' => $row[5],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function purchaseDocuments($companyId, $branches, $suppliers, $now)
    {
        $docs = [
            ['BILL-000501', $suppliers['SUP-001'], 'UTS LOGISTICS AND DISTRIBUTION CO., INC.', '2026-05-21', '2026-05-31', 'PHP', 93960.41, 0, '87953 PAYMENT FOR WAREHOUSING CHARGES - 26-115/ASA COLOR - 26-114/MCC LABELS - 26-116/ASA COLOR -', 'Approved', 'for_payment'],
            ['BILL-000502', $suppliers['SUP-004'], 'METRO INDUSTRIAL SUPPLY', '2026-06-05', '2026-07-05', 'PHP', 127900, 6395, 'Trucking and terminal services for Manila shipment.', 'For Approval', 'for_payment'],
            ['BILL-000503', $suppliers['SUP-002'], 'PACIFIC FUEL SERVICES', '2026-06-04', '2026-06-24', 'USD', 1325, 26.50, 'USD bunker and port service charges for Cebu shipment.', 'Approved', 'partially_paid'],
        ];
        $ids = [];
        foreach ($docs as $doc) {
            $net = $doc[6] - $doc[7];
            $rate = $doc[5] === 'USD' ? 58.25 : 1;
            $this->upsert('purchase_documents', ['company_id' => $companyId, 'document_no' => $doc[0]], [
                'company_id' => $companyId,
                'branch_id' => $branches['MAIN'],
                'supplier_id' => $doc[1],
                'document_type' => 'vendor_bill',
                'transaction_type' => 'Bill',
                'ap_account' => 'Accounts Payable Trade - PHP',
                'bill_received' => true,
                'supplier_address' => 'Seeded supplier address',
                'client_customer_id' => 'CUST-001',
                'shipment_job_id' => 'JOB-SEA-2026-0423',
                'document_no' => $doc[0],
                'reference_no' => str_replace('BILL-', 'B-', $doc[0]),
                'document_date' => $doc[3],
                'due_date' => $doc[4],
                'terms' => '30 Days',
                'currency' => $doc[5],
                'exchange_rate' => $rate,
                'usd_to_php_rate' => $rate,
                'gross_amount' => $doc[6],
                'php_amount' => $doc[6] * $rate,
                'subtotal' => $doc[6],
                'tax_total' => 0,
                'withholding_tax_rate' => $doc[7] > 0 ? round(($doc[7] / $doc[6]) * 100, 2) : 0,
                'withholding_tax_amount' => $doc[7],
                'grand_total' => $net,
                'net_amount' => $net,
                'paid_total' => $doc[0] === 'BILL-000503' ? 600 : 0,
                'status' => $doc[10],
                'approval_status' => $doc[9],
                'remarks' => $doc[8],
                'memo' => $doc[8],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = DB::table('purchase_documents')->where('company_id', $companyId)->where('document_no', $doc[0])->value('id');
            $ids[$doc[0]] = $id;
            $this->vendorBillLines($id, $doc[6], $doc[8], $now);
            $this->documentAttachment('purchase_document', $id, strtolower($doc[0]).'.pdf', $now);
        }
        return $ids;
    }

    private function vendorBillLines($billId, $total, $memo, $now)
    {
        $table = Schema::hasTable('vendor_bill_lines') ? 'vendor_bill_lines' : 'purchase_document_lines';
        if (!Schema::hasTable($table)) {
            return;
        }
        DB::table($table)->where('purchase_document_id', $billId)->delete();
        foreach ([0.45, 0.25, 0.30] as $index => $portion) {
            DB::table($table)->insert($this->payload($table, [
                'purchase_document_id' => $billId,
                'account_name' => 'Reimbursable charges',
                'tax_category' => 'VAT12',
                'amount' => round($total * $portion, 2),
                'description' => $memo,
                'quantity' => 1,
                'unit_cost' => round($total * $portion, 2),
                'line_total' => round($total * $portion, 2),
                'memo' => $memo,
                'customer_job_id' => $index === 1 ? 'CUST-002' : 'CUST-001',
                'is_billable' => true,
                'class_code' => 'MANILA',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function payments($companyId, $suppliers, $purchaseDocs, $now)
    {
        $rows = [
            ['PV-000321', 'CV-000426', $suppliers['SUP-001'], 'BILL-000501', '2026-06-08', 'Cheque', 'BDO CA# 4898002879', '470130', 'PHP', 93960.41, 'Approved'],
            ['PV-000322', 'CV-000427', $suppliers['SUP-002'], 'BILL-000503', '2026-06-08', 'Bank Transfer', 'Metrobank USD Collections - USD', null, 'USD', 600, 'Released'],
        ];
        foreach ($rows as $row) {
            $this->upsert('payments', ['company_id' => $companyId, 'payment_no' => $row[0]], [
                'company_id' => $companyId,
                'supplier_id' => $row[2],
                'payment_no' => $row[0],
                'voucher_no' => $row[1],
                'bill_no' => $row[3],
                'shipment_job_id' => 'JOB-SEA-2026-0423',
                'payment_date' => $row[4],
                'payment_method' => $row[5],
                'bank_account' => $row[6],
                'check_no' => $row[7],
                'currency' => $row[8],
                'amount' => $row[9],
                'status' => strtolower($row[10]),
                'approval_status' => $row[10],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $paymentId = DB::table('payments')->where('company_id', $companyId)->where('payment_no', $row[0])->value('id');
            $this->upsert('payment_applications', ['payment_id' => $paymentId, 'purchase_document_id' => $purchaseDocs[$row[3]]], [
                'payment_id' => $paymentId,
                'purchase_document_id' => $purchaseDocs[$row[3]],
                'amount_applied' => $row[9],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->documentAttachment('payment', $paymentId, strtolower($row[0]).'-support.pdf', $now);
        }
    }

    private function bankTransactions($banks, $now)
    {
        foreach ([
            [$banks['4898002879'], 'BTX-0001', '2026-06-08', 'Withdrawal', 'PV-000321', 'Check payment to UTS Logistics', 0, 93960.41, 'PHP'],
            [$banks['USD-8821900'], 'BTX-0002', '2026-06-08', 'Withdrawal', 'PV-000322', 'USD bank transfer to Pacific Fuel', 0, 600, 'USD'],
        ] as $row) {
            $this->upsert('bank_transactions', ['bank_account_id' => $row[0], 'transaction_no' => $row[1]], [
                'bank_account_id' => $row[0],
                'transaction_no' => $row[1],
                'transaction_date' => $row[2],
                'transaction_type' => $row[3],
                'reference_no' => $row[4],
                'description' => $row[5],
                'debit' => $row[6],
                'credit' => $row[7],
                'currency' => $row[8],
                'exchange_rate' => $row[8] === 'USD' ? 58.25 : 1,
                'foreign_amount' => $row[7],
                'base_amount' => $row[8] === 'USD' ? $row[7] * 58.25 : $row[7],
                'is_reconciled' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function journalEntries($companyId, $branches, $accounts, $customers, $suppliers, $now)
    {
        $this->upsert('journal_entries', ['company_id' => $companyId, 'journal_no' => 'JV-000221'], [
            'company_id' => $companyId,
            'branch_id' => $branches['MAIN'],
            'journal_no' => 'JV-000221',
            'journal_date' => '2026-06-08',
            'source_module' => 'AP',
            'reference_no' => 'PV-000321',
            'memo' => 'Seeded AP payment journal.',
            'status' => 'posted',
            'posted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $journalId = DB::table('journal_entries')->where('company_id', $companyId)->where('journal_no', 'JV-000221')->value('id');
        if (Schema::hasTable('journal_entry_lines')) {
            DB::table('journal_entry_lines')->where('journal_entry_id', $journalId)->delete();
            foreach ([
                [$accounts['2100'], 'Debit AP Trade', 93960.41, 0, null, $suppliers['SUP-001']],
                [$accounts['1010'], 'Credit BDO bank', 0, 93960.41, null, $suppliers['SUP-001']],
            ] as $line) {
                DB::table('journal_entry_lines')->insert($this->payload('journal_entry_lines', [
                    'journal_entry_id' => $journalId,
                    'account_id' => $line[0],
                    'description' => $line[1],
                    'debit' => $line[2],
                    'credit' => $line[3],
                    'customer_id' => $line[4],
                    'supplier_id' => $line[5],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    private function approvalRequests($companyId, $purchaseDocs, $now)
    {
        foreach ($purchaseDocs as $billNo => $id) {
            $this->upsert('approval_requests', ['company_id' => $companyId, 'approvable_type' => 'purchase_document', 'approvable_id' => $id], [
                'company_id' => $companyId,
                'approvable_type' => 'purchase_document',
                'approvable_id' => $id,
                'status' => $billNo === 'BILL-000502' ? 'pending' : 'approved',
                'remarks' => 'Seeded approval request for testing.',
                'approved_at' => $billNo === 'BILL-000502' ? null : $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function printLayouts($companyId, $now)
    {
        $positions = json_encode(['company_name' => [232, 108], 'form_title' => [338, 136], 'payee' => [58, 242], 'check_no' => [640, 196], 'document_no' => [640, 236], 'document_date' => [640, 276]]);
        $this->upsert('print_layouts', ['company_id' => null, 'document_type' => 'check_voucher', 'layout_name' => 'Default'], [
            'company_id' => null,
            'document_type' => 'check_voucher',
            'layout_name' => 'Default',
            'paper_size' => 'letter',
            'paper_width' => 816,
            'paper_height' => 1056,
            'print_template' => false,
            'positions' => $positions,
            'field_styles' => json_encode([]),
            'custom_fields' => json_encode([]),
            'is_default' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function documentAttachment($documentType, $documentId, $name, $now)
    {
        $this->upsert('document_attachments', ['document_type' => $documentType, 'document_id' => $documentId, 'original_name' => $name], [
            'document_type' => $documentType,
            'document_id' => $documentId,
            'disk' => 'local',
            'path' => 'sample-attachments/'.$name,
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size' => 128000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function upsert($table, array $keys, array $values)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->updateOrInsert($keys, $this->payload($table, $values));
    }

    private function payload($table, array $values)
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return array_filter($values, function ($value, $column) use ($table) {
            return Schema::hasColumn($table, $column);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
