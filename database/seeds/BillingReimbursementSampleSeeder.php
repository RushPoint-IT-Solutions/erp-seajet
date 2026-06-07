<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingReimbursementSampleSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        $companyId = DB::table('companies')->where('code', 'SJ')->value('id');
        if (!$companyId) {
            $companyId = DB::table('companies')->insertGetId([
                'code' => 'SJ',
                'name' => 'SeaJet Holdings Inc.',
                'tin' => '000-123-456-000',
                'address' => 'Harbor Business Center, Manila, Philippines',
                'base_currency' => 'PHP',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $branchId = DB::table('branches')->where('company_id', $companyId)->where('code', 'MAIN')->value('id');
        if (!$branchId) {
            $branchId = DB::table('branches')->insertGetId([
                'company_id' => $companyId,
                'code' => 'MAIN',
                'name' => 'Main Branch',
                'address' => 'Manila',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $customerId = DB::table('customers')->where('company_id', $companyId)->where('code', 'CUST-001')->value('id');
        if (!$customerId) {
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
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $vatTaxCodeId = DB::table('tax_codes')->where('company_id', $companyId)->where('code', 'VAT12')->value('id');
        if (!$vatTaxCodeId) {
            $vatTaxCodeId = DB::table('tax_codes')->insertGetId([
                'company_id' => $companyId,
                'code' => 'VAT12',
                'name' => 'Output VAT 12%',
                'rate' => 12,
                'tax_type' => 'output_vat',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('sales_documents')->updateOrInsert(
            ['company_id' => $companyId, 'document_no' => 'BI-2026-REIMB'],
            [
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'document_type' => 'billing_invoice',
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
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $documentId = DB::table('sales_documents')
            ->where('company_id', $companyId)
            ->where('document_no', 'BI-2026-REIMB')
            ->value('id');

        DB::table('sales_document_lines')->where('sales_document_id', $documentId)->delete();
        DB::table('sales_document_lines')->insert([
            [
                'sales_document_id' => $documentId,
                'description' => 'DOC / IDENTIFICATION FEE',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $vatTaxCodeId,
                'line_total' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sales_document_id' => $documentId,
                'description' => 'REIMBURSABLE CHARGES',
                'quantity' => 1,
                'unit_price' => 50,
                'tax_code_id' => null,
                'line_total' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
