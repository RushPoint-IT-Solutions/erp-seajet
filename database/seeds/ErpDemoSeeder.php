<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ErpDemoSeeder extends Seeder
{
    public function run()
    {
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'SJ',
            'name' => 'SeaJet Holdings Inc.',
            'tin' => '000-123-456-000',
            'address' => 'Harbor Business Center, Manila, Philippines',
            'base_currency' => 'PHP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'address' => 'Manila',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('currencies')->insert([
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'exchange_rate' => 1, 'is_base' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'USD', 'name' => 'US Dollar', 'exchange_rate' => 58, 'is_base' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('users')->insert([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => 'ERP Admin',
            'email' => 'admin@seajet.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => 'Accounting',
            'created_at' => now(),
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
            $categoryIds[$key] = DB::table('account_categories')->insertGetId(array_merge($category, ['created_at' => now(), 'updated_at' => now()]));
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
            DB::table('accounts')->insert([
                'company_id' => $companyId,
                'account_category_id' => $categoryIds[$account[0]],
                'code' => $account[1],
                'name' => $account[2],
                'type' => $account[3],
                'opening_balance' => $account[4],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $vatTaxCodeId = DB::table('tax_codes')->insertGetId(['company_id' => $companyId, 'code' => 'VAT12', 'name' => 'Output VAT 12%', 'rate' => 12, 'tax_type' => 'output_vat', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tax_codes')->insert([
            ['company_id' => $companyId, 'code' => 'INPUT12', 'name' => 'Input VAT 12%', 'rate' => 12, 'tax_type' => 'input_vat', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'ZERO', 'name' => 'Zero Rated', 'rate' => 0, 'tax_type' => 'zero_rated', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'EXEMPT', 'name' => 'VAT Exempt', 'rate' => 0, 'tax_type' => 'vat_exempt', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $customerId = DB::table('customers')->insertGetId([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
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
                'created_at' => now(),
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
                'default_class_code' => 'PAMPANGA',
                'default_ar_account_code' => 'AR_TRADE_DN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $companyId,
            'code' => 'SUP-001',
            'name' => 'Metro Industrial Supply',
            'email' => 'ap@metroindustrial.test',
            'payment_terms' => '30 Days',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $warehouseId = DB::table('warehouses')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'code' => 'MAIN-WH',
            'name' => 'Main Warehouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('items')->insertGetId([
            'company_id' => $companyId,
            'sku' => 'INV-FLT-001',
            'barcode' => '480000000001',
            'name' => 'Fuel Filter Assembly',
            'category' => 'Spare Parts',
            'unit_of_measure' => 'pcs',
            'standard_cost' => 3200,
            'selling_price' => 4000,
            'reorder_level' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_balances')->insert([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 42,
            'average_cost' => 3200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceId = DB::table('sales_documents')->insertGetId([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ar_account_mappings')->insert([
            ['company_id' => $companyId, 'code' => 'AR_TRADE_BI', 'name' => 'A/Trade-Billing Invoice', 'currency' => 'PHP', 'document_type' => 'billing_invoice', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_BI', 'name' => 'A/Trade-Billing Invoice', 'currency' => 'USD', 'document_type' => 'billing_invoice', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_REIMBURSABLE', 'name' => 'A/R Reimbursable Charges', 'currency' => 'PHP', 'document_type' => 'acknowledgement_receipt', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_REIMBURSABLE', 'name' => 'A/R Reimbursable Charges', 'currency' => 'USD', 'document_type' => 'acknowledgement_receipt', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_SOAA', 'name' => 'A/R Trade-SOA A', 'currency' => 'PHP', 'document_type' => 'statement_of_account_a', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_SOAA', 'name' => 'A/R Trade-SOA A', 'currency' => 'USD', 'document_type' => 'statement_of_account_a', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_DN', 'name' => 'A/R Trade-Debit Note', 'currency' => 'PHP', 'document_type' => 'debit_note', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR_TRADE_DN', 'name' => 'A/R Trade-Debit Note', 'currency' => 'USD', 'document_type' => 'debit_note', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('document_templates')->insert([
            ['company_id' => $companyId, 'code' => 'BI-PREPRINT', 'name' => 'Billing Invoice Pre-printed Form', 'document_type' => 'billing_invoice', 'uses_pre_printed_form' => true, 'paper_size' => 'A4/Legal', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'SOA-PREPRINT', 'name' => 'Statement of Account Pre-printed Form', 'document_type' => 'statement_of_account', 'uses_pre_printed_form' => true, 'paper_size' => 'A4', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'SOAA-PLAIN', 'name' => 'Statement of Account A Plain Form', 'document_type' => 'statement_of_account_a', 'uses_pre_printed_form' => false, 'paper_size' => 'A4', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'DN-PREPRINT', 'name' => 'Debit Note Pre-printed Form', 'document_type' => 'debit_note', 'uses_pre_printed_form' => true, 'paper_size' => 'A4', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'OR-PREPRINT', 'name' => 'Official Receipt Pre-printed Form', 'document_type' => 'official_receipt', 'uses_pre_printed_form' => true, 'paper_size' => 'Official Form', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'code' => 'AR-PREPRINT', 'name' => 'Acknowledgement Receipt Pre-printed Form', 'document_type' => 'acknowledgement_receipt', 'uses_pre_printed_form' => true, 'paper_size' => 'Official Form', 'created_at' => now(), 'updated_at' => now()],
        ]);

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

        $splitInvoiceId = DB::table('sales_documents')->insertGetId([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        DB::table('purchase_documents')->insert([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
