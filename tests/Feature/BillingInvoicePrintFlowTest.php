<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BillingInvoicePrintFlowTest extends TestCase
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
    }

    public function test_create_billing_invoice_exposes_guest_non_tax_soa_a_path()
    {
        $response = $this->get('/erp/invoices/create');

        $response->assertStatus(200);
        $response->assertSee('GUEST - Guest / Non-tax Client');
        $response->assertSee('SOA A');
        $response->assertSee('SOA B');
        $response->assertSee('Billing Invoice');
        $response->assertSee('Default WHT %');
        $response->assertSee('name="line_withholding_rate[]"', false);
        $response->assertSee('Print SOA B + Billing Invoice');
        $response->assertSee('Print SOA A');
        $response->assertSee('<textarea name="memo"', false);
    }

    public function test_vatable_billing_review_can_print_soa_b_and_billing_invoice()
    {
        $response = $this->post('/erp/invoices/review', $this->payload([
            'customer_id' => 'CUST-001',
            'customer_name' => 'North Harbor Trading',
            'vat_classification' => '12%',
            'ar_account' => 'A/Trade-Billing Invoice',
            'invoice_template' => 'SOA_B',
            'customer_tax_code' => 'VAT12',
        ]));

        $response->assertRedirect();
        $show = $this->get($response->headers->get('Location'));
        $show->assertStatus(200);
        $show->assertSee('Print SOA B');
        $show->assertSee('Print Billing Invoice');
        $show->assertDontSee('Print SOA A');
    }

    public function test_vatable_billing_separates_reimbursement_for_bi_and_soa_b_totals()
    {
        $response = $this->post('/erp/invoices/review', $this->payload([
            'customer_id' => 'CUST-001',
            'customer_name' => 'North Harbor Trading',
            'vat_classification' => '12%',
            'ar_account' => 'A/Trade-Billing Invoice',
            'invoice_template' => 'SOA_B',
            'customer_tax_code' => 'VAT12',
            'item' => ['Documentation VAT', 'Reimbursable Charges'],
            'item_description' => ['DOC / IDENTIFICATION FEE', 'REIMBURSABLE CHARGES'],
            'qty' => ['1', '1'],
            'unit_price' => ['100.00', '50.00'],
            'line_class' => ['MANILA', 'MANILA'],
            'line_ar_account' => ['A/Trade-Billing Invoice', 'A/R-Reimbursable Charges'],
            'tax_category' => ['12%', 'exempt'],
            'line_amount' => ['100.00', '50.00'],
        ]));

        $response->assertRedirect();
        $document = DB::table('sales_documents')->where('document_no', 'BI-TEST-001')->first();
        $this->assertNotNull($document);
        $this->assertEquals(100.00, (float) $document->subtotal);
        $this->assertEquals(12.00, (float) $document->tax_total);
        $this->assertEquals(50.00, (float) $document->freight_total);
        $this->assertEquals(162.00, (float) $document->grand_total);

        $invoicePrint = $this->get('/erp/billings/print/invoice?customer=North%20Harbor%20Trading&invoice_no=BI-TEST-001&invoice_date=2026-05-26&soa_no=SOA-TEST-001&billing_total=112.00&reimbursement_total=50.00&soa_total=162.00&vatable_sales=100.00&vat_amount=12.00');
        $invoicePrint->assertStatus(200);
        $invoicePrint->assertSee('PHP 112.00');
        $invoicePrint->assertDontSee('PHP 162.00');

        $soaPrint = $this->get('/erp/billings/print/statement-of-account?customer=North%20Harbor%20Trading&invoice_no=BI-TEST-001&invoice_date=2026-05-26&soa_no=SOA-TEST-001&billing_total=112.00&reimbursement_total=50.00&soa_total=162.00&vatable_sales=100.00&vat_amount=12.00&fs_particulars=22&fs_amounts=20&fs_payment_note=18');
        $soaPrint->assertStatus(200);
        $soaPrint->assertSee('REIMBURSEMENT CHARGES');
        $soaPrint->assertSee('PHP 50.00');
        $soaPrint->assertSee('TOTAL INVOICE AMOUNT');
        $soaPrint->assertSee('PHP 112.00');
        $soaPrint->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif;', false);
        $soaPrint->assertSee('PLEASE PAY THIS AMOUNT');
        $soaPrint->assertDontSee('PHP 162.00');
        $soaPrint->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif !important;', false);
        $this->assertRegExp('/\.print-field\s*\{[^}]*font-weight:\s*700;/s', $soaPrint->getContent());
        $this->assertRegExp('/print-field-particulars[^>]*font-size:\s*22px/', $soaPrint->getContent());
        $this->assertRegExp('/print-field-amounts[^>]*font-size:\s*20px/', $soaPrint->getContent());
        $this->assertRegExp('/soa-payment-note-amount[^>]*font-size:\s*16px/', $soaPrint->getContent());
        $soaPrint->assertDontSee('current -= 0.5', false);
        $this->assertRegExp('/PLEASE PAY THIS AMOUNT<\/span><span>PHP 50\.00<\/span>/', $soaPrint->getContent());
    }

    public function test_guest_or_non_tax_billing_review_can_print_soa_a_only()
    {
        $response = $this->post('/erp/invoices/review', $this->payload([
            'customer_id' => 'GUEST',
            'customer_name' => 'Guest / Non-tax Client',
            'vat_classification' => 'exempt',
            'ar_account' => 'A/R Trade-SOA A',
            'invoice_template' => 'SOA_A',
            'customer_tax_code' => 'EXEMPT',
        ]));

        $response->assertRedirect();
        $show = $this->get($response->headers->get('Location'));
        $show->assertStatus(200);
        $show->assertSee('Print SOA A');
        $show->assertDontSee('Print SOA B');
        $show->assertDontSee('Print Billing Invoice');
    }

    public function test_billing_invoice_calculates_and_prints_withholding_tax_from_percentage()
    {
        $response = $this->post('/erp/invoices/review', $this->payload([
            'customer_id' => 'CUST-001',
            'customer_name' => 'North Harbor Trading',
            'vat_classification' => '12%',
            'ar_account' => 'A/Trade-Billing Invoice',
            'invoice_template' => 'Billing Invoice',
            'customer_tax_code' => 'VAT12',
            'withholding_tax_rate' => '2',
            'item' => ['Documentation VAT', 'Handling Fee', 'Reimbursable Charges'],
            'item_description' => ['DOC / IDENTIFICATION FEE', 'SPECIAL HANDLING', 'REIMBURSABLE CHARGES'],
            'qty' => ['1', '1', '1'],
            'unit_price' => ['100.00', '200.00', '50.00'],
            'line_class' => ['MANILA', 'MANILA', 'MANILA'],
            'line_ar_account' => ['A/Trade-Billing Invoice', 'A/Trade-Billing Invoice', 'A/R-Reimbursable Charges'],
            'line_withholding_rate' => ['2', '5', '99'],
            'tax_category' => ['12%', '12%', 'exempt'],
            'line_amount' => ['100.00', '200.00', '50.00'],
        ]));

        $response->assertRedirect();
        $document = DB::table('sales_documents')->where('document_no', 'BI-TEST-001')->first();
        $this->assertNotNull($document);
        $this->assertEquals(4.0, (float) $document->withholding_tax_rate);
        $this->assertEquals(12.0, (float) $document->withholding_tax_amount);
        $this->assertEquals(386.0, (float) $document->grand_total);

        $lineWithholding = DB::table('sales_document_lines')
            ->where('sales_document_id', $document->id)
            ->orderBy('id')
            ->pluck('withholding_tax_amount')
            ->map(function ($amount) {
                return (float) $amount;
            })
            ->all();
        $this->assertEquals([2.0, 10.0, 0.0], $lineWithholding);

        $show = $this->get($response->headers->get('Location'));
        $show->assertStatus(200);
        $show->assertSee('Less Withholding Tax');
        $show->assertSee('PHP 374.00');

        $print = $this->get('/erp/billings/print/invoice?invoice=BI-TEST-001');
        $print->assertStatus(200);
        $print->assertSee('PHP 12.00');
        $print->assertSee('PHP 324.00');
    }

    public function test_billing_invoice_prints_each_item_as_a_separate_line_with_its_amount()
    {
        $layout = $this->get('/erp/billings/print-layout/invoice');
        $layout->assertStatus(200);
        $layout->assertSee("var invoiceLineFields = ['line_description', 'line_qty', 'line_unit_price', 'line_amount'];", false);

        $response = $this->post('/erp/invoices/review', $this->payload([
            'customer_id' => 'CUST-001',
            'customer_name' => 'North Harbor Trading',
            'vat_classification' => '12%',
            'ar_account' => 'A/Trade-Billing Invoice',
            'invoice_template' => 'Billing Invoice',
            'customer_tax_code' => 'VAT12',
            'memo' => "First memo paragraph.\n\nSecond memo paragraph.",
            'item' => ['Documentation VAT', 'Handling Fee'],
            'item_description' => ['DOCUMENTATION SERVICE', 'SPECIAL HANDLING SERVICE'],
            'qty' => ['1', '3'],
            'unit_price' => ['100.00', '79.15'],
            'line_class' => ['MANILA', 'MANILA'],
            'tax_category' => ['12%', '12%'],
            'line_amount' => ['100.00', '237.45'],
        ]));

        $response->assertRedirect();

        $print = $this->get('/erp/billings/print/invoice?invoice=BI-TEST-001');
        $print->assertStatus(200);
        $print->assertSee('DOCUMENTATION SERVICE');
        $print->assertSee('SPECIAL HANDLING SERVICE');
        $print->assertSee('data-item-gap="1"', false);
        $print->assertSee("First memo paragraph.\n\nSecond memo paragraph.");
        $print->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif;', false);
        $print->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif !important;', false);
        $print->assertSee('font-weight: 700;', false);
        $this->assertRegExp('/\.print-field-prepared_by\s*\{[^}]*font-weight:\s*700\s*!important;/s', $print->getContent());
        $this->assertRegExp('/\.bi-field\s*\{[^}]*line-height:\s*1\.15;/s', $print->getContent());
        $this->assertRegExp('/\.bi-field\s*\{[^}]*letter-spacing:\s*\.25px;/s', $print->getContent());
        $this->assertRegExp('/\.bi-field\s*\{[^}]*letter-spacing:\s*\.25px\s*!important;/s', $print->getContent());
        $print->assertDontSee('current -= 0.5', false);
        preg_match_all('/data-line-row="0" data-line-top="(\d+)"/', $print->getContent(), $firstRowTops);
        preg_match_all('/data-line-row="1" data-line-top="(\d+)"/', $print->getContent(), $secondRowTops);
        $this->assertCount(4, $firstRowTops[1]);
        $this->assertCount(4, $secondRowTops[1]);
        $this->assertCount(1, array_unique($firstRowTops[1]));
        $this->assertCount(1, array_unique($secondRowTops[1]));
        $this->assertRegExp('/print-field-soa_no[^>]*data-items-shift="[1-9]\d*"/', $print->getContent());
        $this->assertRegExp('/print-field-remarks[^>]*data-items-shift="[1-9]\d*"/', $print->getContent());
        $this->assertRegExp('/print-field-line_amount[^>]*>100\.00<\/div>/', $print->getContent());
        $this->assertRegExp('/print-field-line_amount[^>]*>237\.45<\/div>/', $print->getContent());
    }

    public function test_billing_invoice_rejects_invalid_withholding_percentage()
    {
        $response = $this->from('/erp/invoices/create')->post('/erp/invoices/review', $this->payload([
            'withholding_tax_rate' => '101',
        ]));

        $response->assertRedirect('/erp/invoices/create');
        $response->assertSessionHasErrors('withholding_tax_rate');
        $this->assertFalse(DB::table('sales_documents')->where('document_no', 'BI-TEST-001')->exists());
    }

    public function test_closed_period_rolls_back_invoice_and_journal_together()
    {
        $companyId = DB::table('companies')->orderBy('id')->value('id');
        DB::table('accounting_periods')->insert([
            'company_id' => $companyId,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-05-31',
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->from('/erp/invoices/create')->post('/erp/invoices/review', $this->payload([
            'invoice_no' => 'BI-ATOMIC-001',
            'transaction_no' => 'BI-ATOMIC-001',
            'customer_id' => 'CUST-001',
            'customer_name' => 'North Harbor Trading',
            'vat_classification' => '12%',
            'ar_account' => 'A/Trade-Billing Invoice',
            'invoice_template' => 'SOA_B',
            'customer_tax_code' => 'VAT12',
            'save_mode' => 'post',
        ]));

        $response->assertRedirect('/erp/invoices/create')->assertSessionHasErrors('document_date');
        $this->assertFalse(DB::table('sales_documents')->where('document_no', 'BI-ATOMIC-001')->exists());
        $this->assertFalse(DB::table('journal_entries')->where('reference_no', 'BI-ATOMIC-001')->exists());
    }

    private function payload(array $overrides = [])
    {
        return array_merge([
            'job_shipment_id' => 'JOB-TEST-001',
            'invoice_no' => 'BI-TEST-001',
            'transaction_no' => 'TRX-TEST-001',
            'soa_no' => 'SOA-TEST-001',
            'invoice_date' => '2026-05-26',
            'due_date' => '2026-06-25',
            'invoice_to' => 'Test Customer',
            'invoice_amount' => '112.00',
            'total_payment' => '',
            'consignee' => 'Test Consignee',
            'credit_term_days' => '30',
            'tin' => '',
            'class_code' => 'MANILA',
            'currency' => 'PHP',
            'customer_message' => 'Thank you for your business.',
            'memo' => '',
            'description' => '',
            'amount' => '112.00',
            'mawb_no' => '',
            'hawb_no' => '',
            'date_shipped' => '2026-05-26',
            'origin_dest' => 'MANILA / CEBU',
            'commodity' => 'Cargo',
            'item' => ['Documentation VAT'],
            'item_description' => ['DOC / IDENTIFICATION FEE'],
            'qty' => ['1'],
            'unit_price' => ['100.00'],
            'line_class' => ['MANILA'],
            'tax_category' => ['12%'],
            'line_amount' => ['100.00'],
        ], $overrides);
    }
}
