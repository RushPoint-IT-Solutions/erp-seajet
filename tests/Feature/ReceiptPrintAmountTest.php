<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReceiptPrintAmountTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->actingAsAdmin();
        $this->withoutExceptionHandling();
    }

    public function test_receipts_print_only_the_amount_for_their_document_type()
    {
        $invoice = DB::table('sales_documents')
            ->where('document_no', 'BI-2026-REIMB')
            ->first();

        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 112,
        ]);

        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'receipt_no' => 'CR-SPLIT-001',
            'receipt_type' => 'collection_receipt',
            'receipt_date' => '2026-09-11',
            'payment_method' => 'Check',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 162,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('receipt_applications')->insert([
            'receipt_id' => $receiptId,
            'sales_document_id' => $invoice->id,
            'amount_applied' => 162,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('erp.official-receipt.preview', ['receipt_no' => 'CR-SPLIT-001']))
            ->assertStatus(200)
            ->assertSee('PHP 112.00')
            ->assertDontSee('PHP 162.00');

        $this->get(route('erp.acknowledgement-receipt.preview', ['receipt_no' => 'CR-SPLIT-001']))
            ->assertStatus(200)
            ->assertSee('PHP 50.00')
            ->assertDontSee('PHP 162.00');
    }

    public function test_legacy_invoice_uses_freight_as_the_soa_split()
    {
        $invoice = DB::table('sales_documents')
            ->where('document_no', 'BI-2026-REIMB')
            ->first();

        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'receipt_no' => 'CR-LEGACY-001',
            'receipt_type' => 'collection_receipt',
            'receipt_date' => '2026-09-11',
            'payment_method' => 'Check',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 162,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('receipt_applications')->insert([
            'receipt_id' => $receiptId,
            'sales_document_id' => $invoice->id,
            'amount_applied' => 162,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('erp.official-receipt.preview', ['receipt_no' => 'CR-LEGACY-001']))
            ->assertStatus(200)
            ->assertSee('PHP 112.00');

        $this->get(route('erp.acknowledgement-receipt.preview', ['receipt_no' => 'CR-LEGACY-001']))
            ->assertStatus(200)
            ->assertSee('PHP 50.00');
    }

    public function test_receipt_amounts_use_billing_invoice_less_withholding_and_the_explicit_soa_b_column()
    {
        $invoice = DB::table('sales_documents')
            ->where('document_no', 'BI-2026-REIMB')
            ->first();

        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 112,
            'withholding_tax_amount' => 12,
            'freight_total' => 50,
            'grand_total' => 999,
        ]);

        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'receipt_no' => 'CR-SOURCE-001',
            'receipt_type' => 'collection_receipt',
            'receipt_date' => '2026-09-12',
            'payment_method' => 'Check',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 999,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('receipt_applications')->insert([
            'receipt_id' => $receiptId,
            'sales_document_id' => $invoice->id,
            'amount_applied' => 999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('erp.official-receipt.preview', ['receipt_no' => 'CR-SOURCE-001']))
            ->assertStatus(200)
            ->assertSee('PHP 100.00')
            ->assertDontSee('PHP 999.00');

        $this->get(route('erp.acknowledgement-receipt.preview', ['receipt_no' => 'CR-SOURCE-001']))
            ->assertStatus(200)
            ->assertSee('PHP 50.00')
            ->assertDontSee('PHP 887.00')
            ->assertDontSee('PHP 999.00');
    }
}
