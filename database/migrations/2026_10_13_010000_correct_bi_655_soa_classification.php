<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CorrectBi655SoaClassification extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_documents')) {
            return;
        }

        DB::table('sales_documents')
            ->where('document_no', 'BI-655')
            ->where('customer_id_text', 'CL-0047')
            ->where('job_shipment_id', 'GPA#3323')
            ->where('document_type', 'statement_of_account_a')
            ->update([
                'document_type' => 'billing_invoice',
                'template_code' => 'Billing Invoice',
                'invoice_templates' => json_encode(['Billing Invoice', 'SOA_B']),
                'uses_pre_printed_form' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_documents')) {
            return;
        }

        $document = DB::table('sales_documents')
            ->where('document_no', 'BI-655')
            ->where('customer_id_text', 'CL-0047')
            ->where('job_shipment_id', 'GPA#3323')
            ->where('document_type', 'billing_invoice')
            ->first();
        if (!$document) {
            return;
        }

        $hasReceipt = Schema::hasTable('receipt_applications')
            && DB::table('receipt_applications')->where('sales_document_id', $document->id)->exists();
        if ($hasReceipt) {
            return;
        }

        DB::table('sales_documents')->where('id', $document->id)->update([
            'document_type' => 'statement_of_account_a',
            'template_code' => 'Billing Invoice',
            'invoice_templates' => json_encode(['Billing Invoice', 'SOA_A']),
            'uses_pre_printed_form' => true,
            'updated_at' => now(),
        ]);
    }
}
