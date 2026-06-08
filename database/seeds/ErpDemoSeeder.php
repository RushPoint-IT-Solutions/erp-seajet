<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ErpDemoSeeder extends Seeder
{
    public function run()
    {
        DB::table('companies')->updateOrInsert(['code' => 'SJ'], [
            'code' => 'SJ',
            'name' => 'SeaJet Holdings Inc.',
            'tin' => '000-123-456-000',
            'address' => 'Harbor Business Center, Manila, Philippines',
            'base_currency' => 'PHP',
            'updated_at' => now(),
        ]);
        $companyId = DB::table('companies')->where('code', 'SJ')->value('id');

        DB::table('branches')->updateOrInsert(['company_id' => $companyId, 'code' => 'MAIN'], [
            'company_id' => $companyId,
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'address' => 'Manila',
            'updated_at' => now(),
        ]);
        $branchId = DB::table('branches')->where('company_id', $companyId)->where('code', 'MAIN')->value('id');

        foreach ([
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'exchange_rate' => 1, 'is_base' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'exchange_rate' => 58, 'is_base' => false],
        ] as $currency) {
            DB::table('currencies')->updateOrInsert(['code' => $currency['code']], $currency + ['updated_at' => now()]);
        }

        DB::table('users')->updateOrInsert(['email' => 'admin@seajet.test'], [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => 'ERP Admin',
            'email' => 'admin@seajet.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => 'Accounting',
            'updated_at' => now(),
        ]);

        $categories = [
            'Assets' => ['name' => 'Assets', 'normal_balance' => 'debit'],
            'Liabilities' => ['name' => 'Liabilities', 'normal_balance' => 'credit'],
            'Equity' => ['name' => 'Equity', 'normal_balance' => 'credit'],
            'Revenue' => ['name' => 'Revenue', 'normal_balance' => 'credit'],
            'Expenses' => ['name' => 'Expenses', 'normal_balance' => 'debit'],
        ];
        $categoryIds = [];
        foreach ($categories as $key => $category) {
            $categoryIds[$key] = $this->firstOrInsertId('account_categories', ['name' => $category['name']], $category);
        }

        $accounts = [
            ['Assets', '1000', 'Cash on Hand', 'Current Asset', 245000],
            ['Assets', '1010', 'Bank - Operating Account', 'Current Asset', 1023500],
            ['Assets', '1200', 'Accounts Receivable', 'Current Asset', 238700],
            ['Assets', '1210', 'A/Trade-Billing Invoice', 'Current Asset', 98500],
            ['Assets', '1220', 'A/R Reimbursable Charges', 'Current Asset', 12800],
            ['Assets', '1230', 'A/R Trade-SOA A', 'Current Asset', 106720],
            ['Assets', '1240', 'A/R Trade-Debit Note', 'Current Asset', 48900],
            ['Assets', '1300', 'Inventory', 'Current Asset', 728900],
            ['Liabilities', '2100', 'Accounts Payable', 'Current Liability', 174250],
            ['Liabilities', '2200', 'Output VAT Payable', 'Current Liability', 41280],
            ['Equity', '3000', 'Owner Equity', 'Equity', 1000000],
            ['Revenue', '4000', 'Service Revenue', 'Revenue', 845230],
            ['Expenses', '5000', 'Cost of Services', 'Expense', 240300],
            ['Expenses', '5100', 'Operating Expenses', 'Expense', 142110],
        ];
        foreach ($accounts as $account) {
            DB::table('accounts')->updateOrInsert(['company_id' => $companyId, 'code' => $account[1]], [
                'company_id' => $companyId,
                'account_category_id' => $categoryIds[$account[0]],
                'code' => $account[1],
                'name' => $account[2],
                'type' => $account[3],
                'opening_balance' => $account[4],
                'updated_at' => now(),
            ]);
        }

        foreach ([
            ['code' => 'VAT12', 'name' => 'Output VAT 12%', 'rate' => 12, 'tax_type' => 'output_vat'],
            ['code' => 'INPUT12', 'name' => 'Input VAT 12%', 'rate' => 12, 'tax_type' => 'input_vat'],
            ['code' => 'ZERO', 'name' => 'Zero Rated', 'rate' => 0, 'tax_type' => 'zero_rated'],
            ['code' => 'EXEMPT', 'name' => 'VAT Exempt', 'rate' => 0, 'tax_type' => 'vat_exempt'],
        ] as $taxCode) {
            DB::table('tax_codes')->updateOrInsert(['company_id' => $companyId, 'code' => $taxCode['code']], $taxCode + ['company_id' => $companyId, 'updated_at' => now()]);
        }
        $vatTaxCodeId = DB::table('tax_codes')->where('company_id', $companyId)->where('code', 'VAT12')->value('id');

        DB::table('customers')->updateOrInsert(['company_id' => $companyId, 'code' => 'CUST-001'], [
            'company_id' => $companyId,
            'code' => 'CUST-001',
            'customer_id' => 'CUST-001',
            'name' => 'North Harbor Trading',
            'email' => 'billing@northharbor.test',
            'credit_limit' => 500000,
            'payment_terms' => '30 Days',
            'credit_term_days' => 30,
            'default_class_code' => 'MANILA',
            'default_ar_account_code' => 'AR_TRADE_BI',
            'updated_at' => now(),
        ]);
        $customerId = DB::table('customers')->where('company_id', $companyId)->where('code', 'CUST-001')->value('id');

        foreach ([
            [
                'company_id' => $companyId,
                'code' => 'CUST-002',
                'customer_id' => 'CUST-002',
                'name' => 'Blue Anchor Logistics',
                'email' => 'billing@blueanchor.test',
                'credit_limit' => 250000,
                'payment_terms' => '60 Days',
                'credit_term_days' => 60,
                'currency' => 'USD',
                'default_class_code' => 'CEBU',
                'default_ar_account_code' => 'AR_TRADE_SOAA',
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'code' => 'CUST-003',
                'customer_id' => 'CUST-003',
                'name' => 'Island Prime Foods',
                'email' => 'billing@islandprime.test',
                'credit_limit' => 150000,
                'payment_terms' => '90 Days',
                'credit_term_days' => 90,
                'currency' => 'PHP',
                'default_class_code' => 'PAMPANGA',
                'default_ar_account_code' => 'AR_TRADE_DN',
                'updated_at' => now(),
            ],
        ] as $customer) {
            DB::table('customers')->updateOrInsert(['company_id' => $companyId, 'code' => $customer['code']], $customer);
        }

        DB::table('suppliers')->updateOrInsert(['company_id' => $companyId, 'code' => 'SUP-001'], [
            'company_id' => $companyId,
            'code' => 'SUP-001',
            'name' => 'Metro Industrial Supply',
            'email' => 'ap@metroindustrial.test',
            'payment_terms' => '30 Days',
            'updated_at' => now(),
        ]);
        $supplierId = DB::table('suppliers')->where('company_id', $companyId)->where('code', 'SUP-001')->value('id');

        DB::table('warehouses')->updateOrInsert(['company_id' => $companyId, 'code' => 'MAIN-WH'], [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'code' => 'MAIN-WH',
            'name' => 'Main Warehouse',
            'updated_at' => now(),
        ]);
        $warehouseId = DB::table('warehouses')->where('company_id', $companyId)->where('code', 'MAIN-WH')->value('id');

        DB::table('items')->updateOrInsert(['company_id' => $companyId, 'sku' => 'INV-FLT-001'], [
            'company_id' => $companyId,
            'sku' => 'INV-FLT-001',
            'barcode' => '480000000001',
            'name' => 'Fuel Filter Assembly',
            'category' => 'Spare Parts',
            'unit_of_measure' => 'pcs',
            'standard_cost' => 3200,
            'selling_price' => 4000,
            'reorder_level' => 20,
            'updated_at' => now(),
        ]);
        $itemId = DB::table('items')->where('company_id', $companyId)->where('sku', 'INV-FLT-001')->value('id');

        DB::table('inventory_balances')->updateOrInsert(['item_id' => $itemId, 'warehouse_id' => $warehouseId], [
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 42,
            'average_cost' => 3200,
            'updated_at' => now(),
        ]);

        DB::table('sales_documents')->updateOrInsert(['company_id' => $companyId, 'document_no' => 'BI-2026-01042'], [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'document_type' => 'billing_invoice',
            'document_no' => 'BI-2026-01042',
            'document_date' => '2026-05-24',
            'due_date' => '2026-06-23',
            'credit_term_days' => 30,
            'customer_id_text' => 'CUST-001',
            'customer_po_no' => 'PO-NHT-5520',
            'shipment_reference' => 'JOB-SEA-2026-0422',
            'job_shipment_id' => 'JOB-SEA-2026-0422',
            'vat_classification' => '12%',
            'class_code' => 'MANILA',
            'ar_account_code' => 'AR_TRADE_BI',
            'template_code' => 'BI-PREPRINT',
            'uses_pre_printed_form' => true,
            'subtotal' => 87946.43,
            'tax_total' => 10553.57,
            'grand_total' => 98500,
            'status' => 'posted',
            'updated_at' => now(),
        ]);
        $invoiceId = DB::table('sales_documents')->where('company_id', $companyId)->where('document_no', 'BI-2026-01042')->value('id');

        foreach ([
            ['company_id' => $companyId, 'code' => 'AR_TRADE_BI', 'name' => 'A/Trade-Billing Invoice', 'currency' => 'PHP', 'document_type' => 'billing_invoice', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_BI', 'name' => 'A/Trade-Billing Invoice', 'currency' => 'USD', 'document_type' => 'billing_invoice', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_REIMBURSABLE', 'name' => 'A/R Reimbursable Charges', 'currency' => 'PHP', 'document_type' => 'acknowledgement_receipt', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_REIMBURSABLE', 'name' => 'A/R Reimbursable Charges', 'currency' => 'USD', 'document_type' => 'acknowledgement_receipt', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_SOAA', 'name' => 'A/R Trade-SOA A', 'currency' => 'PHP', 'document_type' => 'statement_of_account_a', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_SOAA', 'name' => 'A/R Trade-SOA A', 'currency' => 'USD', 'document_type' => 'statement_of_account_a', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_DN', 'name' => 'A/R Trade-Debit Note', 'currency' => 'PHP', 'document_type' => 'debit_note', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_DN', 'name' => 'A/R Trade-Debit Note', 'currency' => 'USD', 'document_type' => 'debit_note', 'created_at' => now(), 'updated_at' => now()],
        ] as $mapping) {
            DB::table('ar_account_mappings')->updateOrInsert([
                'company_id' => $companyId,
                'code' => $mapping['code'],
                'currency' => $mapping['currency'],
            ], $mapping);
        }

        foreach ([
            ['company_id' => $companyId, 'code' => 'BI-PREPRINT', 'name' => 'Billing Invoice Pre-printed Form', 'document_type' => 'billing_invoice', 'uses_pre_printed_form' => true, 'paper_size' => 'A4/Legal', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'SOA-PREPRINT', 'name' => 'Statement of Account Pre-printed Form', 'document_type' => 'statement_of_account', 'uses_pre_printed_form' => true, 'paper_size' => 'A4', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'SOAA-PLAIN', 'name' => 'Statement of Account A Plain Form', 'document_type' => 'statement_of_account_a', 'uses_pre_printed_form' => false, 'paper_size' => 'A4', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'DN-PREPRINT', 'name' => 'Debit Note Pre-printed Form', 'document_type' => 'debit_note', 'uses_pre_printed_form' => true, 'paper_size' => 'A4', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'OR-PREPRINT', 'name' => 'Official Receipt Pre-printed Form', 'document_type' => 'official_receipt', 'uses_pre_printed_form' => true, 'paper_size' => 'Official Form', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR-PREPRINT', 'name' => 'Acknowledgement Receipt Pre-printed Form', 'document_type' => 'acknowledgement_receipt', 'uses_pre_printed_form' => true, 'paper_size' => 'Official Form', 'created_at' => now(), 'updated_at' => now()],
        ] as $template) {
            DB::table('document_templates')->updateOrInsert([
                'company_id' => $companyId,
                'code' => $template['code'],
            ], $template);
        }

        DB::table('sales_document_lines')->where('sales_document_id', $invoiceId)->delete();
        DB::table('sales_document_lines')->insert([
            'sales_document_id' => $invoiceId,
            'description' => 'Freight handling and delivery service',
            'quantity' => 1,
            'unit_price' => 87946.43,
            'tax_code_id' => $vatTaxCodeId,
            'line_total' => 87946.43,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_documents')->updateOrInsert(['company_id' => $companyId, 'document_no' => 'BI-2026-REIMB'], [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'document_type' => 'billing_invoice',
            'document_no' => 'BI-2026-REIMB',
            'document_date' => '2026-05-26',
            'due_date' => '2026-06-25',
            'credit_term_days' => 30,
            'customer_id_text' => 'CUST-001',
            'shipment_reference' => 'JOB-SEA-2026-REIMB',
            'job_shipment_id' => 'JOB-SEA-2026-REIMB',
            'vat_classification' => '12%',
            'class_code' => 'MANILA',
            'ar_account_code' => 'AR_TRADE_BI',
            'template_code' => 'SOA-PREPRINT',
            'uses_pre_printed_form' => true,
            'subtotal' => 100,
            'tax_total' => 12,
            'freight_total' => 50,
            'grand_total' => 162,
            'status' => 'posted',
            'remarks' => 'Sample SOA B includes PHP 50.00 reimbursement; Billing Invoice prints PHP 112.00 only.',
            'updated_at' => now(),
        ]);
        $splitInvoiceId = DB::table('sales_documents')->where('company_id', $companyId)->where('document_no', 'BI-2026-REIMB')->value('id');

        DB::table('sales_document_lines')->where('sales_document_id', $splitInvoiceId)->delete();
        DB::table('sales_document_lines')->insert([
            [
                'sales_document_id' => $splitInvoiceId,
                'description' => 'DOC / IDENTIFICATION FEE',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $vatTaxCodeId,
                'line_total' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sales_document_id' => $splitInvoiceId,
                'description' => 'REIMBURSABLE CHARGES',
                'quantity' => 1,
                'unit_price' => 50,
                'tax_code_id' => null,
                'line_total' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('purchase_documents')->updateOrInsert(['company_id' => $companyId, 'document_no' => 'PO-2026-00491'], [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'supplier_id' => $supplierId,
            'document_type' => 'purchase_order',
            'document_no' => 'PO-2026-00491',
            'document_date' => '2026-05-23',
            'due_date' => '2026-06-12',
            'subtotal' => 114196.43,
            'tax_total' => 13703.57,
            'grand_total' => 127900,
            'status' => 'approved',
            'updated_at' => now(),
        ]);
    }

    private function firstOrInsertId($table, array $keys, array $values)
    {
        $id = DB::table($table)->where($keys)->value('id');

        if ($id) {
            DB::table($table)->where('id', $id)->update($values + ['updated_at' => now()]);
            return $id;
        }

        return DB::table($table)->insertGetId($keys + $values + ['created_at' => now(), 'updated_at' => now()]);
    }
}
