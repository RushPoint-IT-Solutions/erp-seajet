<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeededBillingInvoiceSampleTest extends TestCase
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
    }

    public function test_seeded_billing_invoice_sample_splits_bi_and_soa_b_totals()
    {
        $document = DB::table('sales_documents')
            ->where('document_no', 'BI-2026-REIMB')
            ->first();

        $this->assertNotNull($document);
        $this->assertSame('12%', $document->vat_classification);
        $this->assertEquals(100.00, (float) $document->subtotal);
        $this->assertEquals(12.00, (float) $document->tax_total);
        $this->assertEquals(50.00, (float) $document->freight_total);
        $this->assertEquals(162.00, (float) $document->grand_total);
        $this->assertEquals(112.00, (float) $document->subtotal + (float) $document->tax_total);

        $lines = DB::table('sales_document_lines')
            ->where('sales_document_id', $document->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $lines);
        $this->assertSame('DOC / IDENTIFICATION FEE', $lines[0]->description);
        $this->assertSame('REIMBURSABLE CHARGES', $lines[1]->description);
        $this->assertNull($lines[1]->tax_code_id);
    }
}
