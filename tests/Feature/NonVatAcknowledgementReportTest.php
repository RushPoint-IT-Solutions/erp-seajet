<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NonVatAcknowledgementReportTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        Storage::fake('local');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->actingAsAdmin();
        $this->withoutExceptionHandling();
    }

    public function test_non_vat_collection_uses_separate_letter_acknowledgement_report()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('customers')->where('id', $invoice->customer_id)->update(['tax_classification' => 'VAT-exempt']);
        DB::table('sales_documents')->where('id', $invoice->id)->update(['vat_classification' => 'exempt']);
        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'receipt_no' => 'NONVAT-AR-001',
            'receipt_type' => 'Official Receipt',
            'receipt_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 160,
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

        $this->get(route('erp.acknowledgement-receipt.preview', ['receipt_no' => 'NONVAT-AR-001']))
            ->assertOk()
            ->assertSee('Non-VAT Acknowledgement Report')
            ->assertSee('ACKNOWLEDGEMENT REPORT')
            ->assertSee('size: Letter portrait;', false)
            ->assertSee('Edit Content &amp; Layout', false)
            ->assertSee('data-field-key="report_header"', false)
            ->assertSee('PHP 160.00');
    }

    public function test_non_vat_content_and_layout_save_separately_from_existing_ar()
    {
        $this->post(route('erp.non-vat-acknowledgement.layout.save'), [
            'layout_x' => ['report_header' => 2.5],
            'layout_y' => ['report_header' => 3.5],
            'font_size' => ['report_header' => 20],
            'font_family' => ['report_header' => 'georgia'],
            'bold_field' => ['report_header'],
            'field_content' => [
                'report_header' => 'CUSTOM NON-VAT ACKNOWLEDGEMENT',
                'intro_text' => 'We acknowledge the following collection:',
                'footer_text' => 'Non-VAT collection confirmed.',
            ],
        ])->assertRedirect(route('erp.non-vat-acknowledgement.preview', ['adjust' => 1]));

        $this->get(route('erp.non-vat-acknowledgement.preview'))
            ->assertOk()
            ->assertSee('CUSTOM NON-VAT ACKNOWLEDGEMENT')
            ->assertSee('transform: translate(2.5mm, 3.5mm)', false)
            ->assertSee('font-size: 20pt', false)
            ->assertSee('font-family: Georgia, &quot;Times New Roman&quot;, serif; font-weight: 700 !important', false);

        $this->get(route('erp.acknowledgement-receipt.preview'))
            ->assertOk()
            ->assertSee('Acknowledgement Receipt')
            ->assertDontSee('CUSTOM NON-VAT ACKNOWLEDGEMENT');
    }
}
