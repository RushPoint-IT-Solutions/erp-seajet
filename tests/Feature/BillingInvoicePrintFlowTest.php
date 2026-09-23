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
        $response->assertSee('name="class_code"', false);
        $response->assertDontSee('name="line_class[]"', false);
        $response->assertSee('Print SOA B + Billing Invoice');
        $response->assertSee('Print SOA A');
        $response->assertSee('<textarea name="memo"', false);
    }

    public function test_client_statement_uses_letterhead_without_the_legacy_company_header()
    {
        $response = $this->get(route('erp.reports.client-soa'));

        $response->assertOk();
        $response->assertSee('class="client-soa-letterhead"', false);
        $response->assertSee('images/background.jpg');
        $response->assertDontSee('class="client-soa-header"', false);
        $response->assertDontSee('class="client-soa-company"', false);
        $response->assertDontSee('class="client-soa-logo"', false);
        $response->assertSee('STATEMENT OF ACCOUNT');
        $this->assertSame(11, substr_count($response->getContent(), 'class="client-soa-empty-row"'));
    }

    public function test_invoice_class_is_document_level_and_applies_to_all_lines()
    {
        $response = $this->post('/erp/invoices/review', $this->payload([
            'class_code' => 'CEBU',
            'vat_classification' => '12%',
            'item' => ['Documentation VAT', 'Handling Fee'],
            'item_description' => ['DOCUMENTATION', 'HANDLING'],
            'qty' => ['1', '1'],
            'unit_price' => ['100.00', '200.00'],
            'line_class' => ['MANILA', 'PAMPANGA'],
            'tax_category' => ['12%', '12%'],
        ]));

        $response->assertRedirect();
        $document = DB::table('sales_documents')->where('document_no', 'BI-TEST-001')->first();
        $this->assertSame('CEBU', $document->class_code);
        $this->assertSame(['CEBU', 'CEBU'], DB::table('sales_document_lines')
            ->where('sales_document_id', $document->id)
            ->orderBy('id')
            ->pluck('class_code')
            ->all());
    }

    public function test_automatic_billing_invoice_numbers_advance_independently_by_class()
    {
        $this->get('/erp/invoices/create')->assertOk();
        $series = DB::table('number_series')->where('document_type', 'billing_invoice');
        $this->assertSame(3, (clone $series)->where('class_code', '<>', '')->count());
        (clone $series)->where('class_code', 'MANILA')->update(['current_number' => 40]);
        (clone $series)->where('class_code', 'CEBU')->update(['current_number' => 90]);

        $create = $this->get('/erp/invoices/create');
        $create->assertOk();
        $this->assertSame('BI-MNL-000041', $create->original->getData()['invoiceSeries']['MANILA']);
        $this->assertSame('BI-CEB-000091', $create->original->getData()['invoiceSeries']['CEBU']);

        $this->withoutExceptionHandling();
        $this->post('/erp/invoices/review', $this->payload([
            'invoice_no' => '', 'transaction_no' => '', 'class_code' => 'CEBU', 'vat_classification' => '12%',
        ]))->assertRedirect();
        $this->assertDatabaseHas('sales_documents', ['document_no' => 'BI-CEB-000091', 'class_code' => 'CEBU']);

        $this->post('/erp/invoices/review', $this->payload([
            'invoice_no' => '', 'transaction_no' => '', 'class_code' => 'MANILA', 'vat_classification' => '12%',
        ]))->assertRedirect();
        $this->assertDatabaseHas('sales_documents', ['document_no' => 'BI-MNL-000041', 'class_code' => 'MANILA']);
        $companyId = DB::table('sales_documents')->where('document_no', 'BI-MNL-000041')->value('company_id');
        $this->assertSame(41, (int) (clone $series)->where('company_id', $companyId)->where('class_code', 'MANILA')->value('current_number'));
        $this->assertSame(91, (int) (clone $series)->where('company_id', $companyId)->where('class_code', 'CEBU')->value('current_number'));
        $this->assertSame(0, (int) (clone $series)->where('company_id', $companyId)->where('class_code', 'PAMPANGA')->value('current_number'));
    }

    public function test_soa_a_uses_one_automatic_number_series_across_all_classes()
    {
        DB::table('number_series')
            ->where('document_type', 'statement_of_account_a')
            ->where('class_code', '')
            ->update(['current_number' => 20]);

        $create = $this->get('/erp/invoices/create');
        $create->assertOk();
        $this->assertSame('SOAA-0021', $create->original->getData()['soaASeries']);

        foreach (['MANILA', 'CEBU', 'PAMPANGA'] as $index => $classCode) {
            $number = 'SOAA-'.str_pad((string) (21 + $index), 4, '0', STR_PAD_LEFT);
            $this->post('/erp/invoices/review', $this->payload([
                'invoice_no' => '',
                'transaction_no' => '',
                'soa_no' => '',
                'class_code' => $classCode,
                'invoice_template' => 'SOA_A',
                'ar_account' => 'A/R Trade Metro',
                'line_ar_account' => ['A/R Trade Metro'],
                'vat_classification' => 'exempt',
            ]))->assertRedirect();

            $this->assertDatabaseHas('sales_documents', [
                'document_no' => $number,
                'soa_no' => $number,
                'class_code' => $classCode,
                'ar_account_code' => 'AR_TRADE_SOAA',
            ]);
        }

        $companyId = DB::table('sales_documents')->where('document_no', 'SOAA-0021')->value('company_id');
        $this->assertSame(0, DB::table('number_series')
            ->where('company_id', $companyId)
            ->where('document_type', 'statement_of_account_a')
            ->whereIn('class_code', ['MANILA', 'CEBU', 'PAMPANGA'])
            ->count());
        $this->assertSame(23, (int) DB::table('number_series')
            ->where('company_id', $companyId)
            ->where('document_type', 'statement_of_account_a')
            ->where('class_code', '')
            ->value('current_number'));
        DB::table('sales_documents')->where('document_no', 'SOAA-0021')->update(['soa_no' => null]);
        $this->get(route('erp.soa-a.preview', ['invoice' => 'SOAA-0021']))
            ->assertOk()
            ->assertSee('print-field-serial', false)
            ->assertDontSee('.print-field-serial {', false)
            ->assertSee('SOAA-0021');
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

    public function test_billing_invoice_hides_empty_soa_number()
    {
        $withoutSoa = $this->get('/erp/billings/print/invoice?invoice_no=BI-NO-SOA&soa_no=');

        $withoutSoa->assertStatus(200);
        $withoutSoa->assertDontSee('class="bi-field fit-single-line print-field-soa_no"', false);
        $withoutSoa->assertDontSee('SOA#');

        $withSoa = $this->get('/erp/billings/print/invoice?invoice_no=BI-WITH-SOA&soa_no=SOA-001');

        $withSoa->assertStatus(200);
        $withSoa->assertSee('class="bi-field fit-single-line print-field-soa_no"', false);
        $withSoa->assertSee('SOA# SOA-001');
    }

    public function test_billing_totals_round_components_to_cents_before_calculating_amount_due()
    {
        $response = $this->post('/erp/invoices/review', $this->payload([
            'invoice_no' => 'BI-ROUND-001',
            'transaction_no' => 'BI-ROUND-001',
            'vat_classification' => '12%',
            'withholding_tax_rate' => '2',
            'item' => ['Taxable service'],
            'item_description' => ['TAXABLE SERVICE'],
            'qty' => ['1'],
            'unit_price' => ['34719.85'],
            'line_amount' => ['34719.85'],
            'line_withholding_rate' => ['2'],
            'tax_category' => ['12%'],
        ]));

        $response->assertRedirect();

        $document = DB::table('sales_documents')->where('document_no', 'BI-ROUND-001')->first();
        $this->assertNotNull($document);
        $this->assertEquals(4166.38, (float) $document->tax_total);
        $this->assertEquals(38886.23, (float) $document->grand_total);
        $this->assertEquals(694.40, (float) $document->withholding_tax_amount);

        $show = $this->get($response->headers->get('Location'));
        $show->assertStatus(200);
        $show->assertSee('PHP 38,886.23');
        $show->assertSee('PHP 694.40');
        $show->assertSee('PHP 38,191.83');
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
        $soaPrint->assertSee('PHP 162.00');
        $soaPrint->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif !important;', false);
        $this->assertRegExp('/\.print-field\s*\{[^}]*font-weight:\s*700;/s', $soaPrint->getContent());
        $this->assertRegExp('/print-field-particulars[^>]*font-size:\s*22px/', $soaPrint->getContent());
        $this->assertRegExp('/print-field-amounts[^>]*font-size:\s*20px/', $soaPrint->getContent());
        $this->assertRegExp('/soa-payment-note-amount[^>]*font-size:\s*16px/', $soaPrint->getContent());
        $this->assertRegExp('/soa-payment-note-amount[^>]*--soa-payment-note-amount-left:\s*280px/', $soaPrint->getContent());
        $soaPrint->assertDontSee('current -= 0.5', false);
        $this->assertRegExp('/PLEASE PAY THIS AMOUNT<\/span><span>PHP 162\.00<\/span>/', $soaPrint->getContent());
        $this->assertRegExp('/print-field-total_reimbursement[^>]*>50\.00<\/div>/', $soaPrint->getContent());
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

        $soa = $this->get('/erp/billings/print/statement-of-account?billing_total=336.00&reimbursement_total=50.00&withholding_tax=12.00');
        $soa->assertStatus(200);
        $this->assertRegExp('/TOTAL INVOICE AMOUNT<\/span><span>PHP 324\.00<\/span>/', $soa->getContent());
        $this->assertRegExp('/PLEASE PAY THIS AMOUNT<\/span><span>PHP 374\.00<\/span>/', $soa->getContent());
        $this->assertRegExp('/print-field-total_reimbursement[^>]*>50\.00<\/div>/', $soa->getContent());
    }

    public function test_billing_invoice_prints_non_zero_zero_rated_sales_and_hides_zero_amount()
    {
        $withZeroRatedSales = $this->get('/erp/billings/print/invoice?vatable_sales=0&zero_rated_sales=47813.39&billing_total=47813.39');
        $withZeroRatedSales->assertStatus(200);
        $this->assertRegExp('/<div class="[^"]*print-field-zero_rated_sales[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $withZeroRatedSales->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vatable_sales[^"]*"/', $withZeroRatedSales->getContent());

        $withoutZeroRatedSales = $this->get('/erp/billings/print/invoice?vatable_sales=0&zero_rated_sales=0&billing_total=0');
        $withoutZeroRatedSales->assertStatus(200);
        $this->assertNotRegExp('/<div class="[^"]*print-field-zero_rated_sales[^"]*"/', $withoutZeroRatedSales->getContent());
        foreach (['total_sales', 'less_vat', 'amount_net_vat', 'discount', 'add_vat', 'withholding_tax', 'amount_due'] as $field) {
            $this->assertNotRegExp('/<div class="[^"]*print-field-'.$field.'(?:\s|")/', $withoutZeroRatedSales->getContent());
        }
    }

    public function test_billing_invoice_prints_the_applicable_tax_total_on_the_left_and_hides_zero_tax_fields()
    {
        $zeroRated = $this->get('/erp/billings/print/invoice?vatable_sales=0&vat_amount=0&zero_rated_sales=47813.39&vat_exempt_sales=0&billing_total=47813.39');
        $zeroRated->assertStatus(200);
        $this->assertRegExp('/<div class="[^"]*print-field-zero_rated_sales[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $zeroRated->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vatable_sales[^"]*"/', $zeroRated->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vat(?:\s|")/', $zeroRated->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vat_exempt_sales[^"]*"/', $zeroRated->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-total_sales[^"]*"/', $zeroRated->getContent());
        $this->assertRegExp('/<div class="[^"]*print-field-amount_net_vat[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $zeroRated->getContent());

        $vatExempt = $this->get('/erp/billings/print/invoice?vatable_sales=0&vat_amount=0&zero_rated_sales=0&vat_exempt_sales=47813.39&billing_total=47813.39');
        $vatExempt->assertStatus(200);
        $this->assertRegExp('/<div class="[^"]*print-field-vat_exempt_sales[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $vatExempt->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vatable_sales[^"]*"/', $vatExempt->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vat(?:\s|")/', $vatExempt->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-zero_rated_sales[^"]*"/', $vatExempt->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-total_sales[^"]*"/', $vatExempt->getContent());
        $this->assertRegExp('/<div class="[^"]*print-field-amount_net_vat[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $vatExempt->getContent());

        $legacyZeroRated = $this->get('/erp/billings/print/invoice?vat_classification=zero-rated&billing_total=47813.39&vat_amount=0');
        $legacyZeroRated->assertStatus(200);
        $this->assertRegExp('/<div class="[^"]*print-field-zero_rated_sales[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $legacyZeroRated->getContent());

        $legacyVatExempt = $this->get('/erp/billings/print/invoice?vat_classification=exempt&billing_total=47813.39&vat_amount=0');
        $legacyVatExempt->assertStatus(200);
        $this->assertRegExp('/<div class="[^"]*print-field-vat_exempt_sales[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $legacyVatExempt->getContent());
    }

    public function test_zero_rated_and_vat_exempt_sales_are_not_saved_as_vatable_sales()
    {
        $this->post('/erp/invoices/review', $this->payload([
            'invoice_no' => 'BI-ZERO-001',
            'transaction_no' => 'BI-ZERO-001',
            'vat_classification' => 'zero-rated',
            'tax_category' => ['zero-rated'],
        ]))->assertRedirect();

        $zeroRated = DB::table('sales_documents')->where('document_no', 'BI-ZERO-001')->first();
        $this->assertEquals(100.0, (float) $zeroRated->subtotal);
        $this->assertEquals(0.0, (float) $zeroRated->vatable_amount);
        $this->assertEquals(100.0, (float) $zeroRated->zero_rated_amount);

        $this->post('/erp/invoices/review', $this->payload([
            'invoice_no' => 'BI-EXEMPT-001',
            'transaction_no' => 'BI-EXEMPT-001',
            'vat_classification' => 'exempt',
            'tax_category' => ['exempt'],
        ]))->assertRedirect();

        $vatExempt = DB::table('sales_documents')->where('document_no', 'BI-EXEMPT-001')->first();
        $this->assertEquals(100.0, (float) $vatExempt->subtotal);
        $this->assertEquals(0.0, (float) $vatExempt->vatable_amount);
        $this->assertEquals(100.0, (float) $vatExempt->vat_exempt_amount);
    }

    public function test_mixed_zero_rated_invoice_and_exempt_reimbursements_print_the_zero_rated_total()
    {
        $items = [
            'C.O-ZERO', 'Trucking ZERO', 'Chassis ZERO', 'Documentation ZERO',
            'Handling ZERO', 'O. freight ZERO', 'Seal ZERO', 'ETRACC ZERO',
            'Arrastre RE',
        ];
        $descriptions = [
            'CERTIFICATE OF ORIGIN', 'TRUCKING CHARGES', 'CHASSIS RENTAL', 'DOCUMENTATION FEE',
            'HANDLING FEE', 'OCEAN FREIGHT CHARGES', 'SEAL FEE', 'ETRACC',
            'ARRASTRE AND AFS',
        ];
        $amounts = [
            '2000.00', '32500.00', '2500.00', '5500.00',
            '1500.00', '2513.39', '350.00', '950.00',
            '5381.60',
        ];

        $this->post('/erp/invoices/review', $this->payload([
            'invoice_no' => 'BI-MIXED-ZERO-001',
            'transaction_no' => 'BI-MIXED-ZERO-001',
            'vat_classification' => 'zero-rated',
            'item' => $items,
            'item_description' => $descriptions,
            'qty' => array_fill(0, count($items), '1'),
            'unit_price' => $amounts,
            'line_amount' => $amounts,
            'tax_category' => array_merge(array_fill(0, 8, 'zero-rated'), ['exempt']),
            'line_ar_account' => array_merge(array_fill(0, 8, 'A/Trade-Billing Invoice'), ['A/R-Reimbursable Charges']),
        ]))->assertRedirect();

        $document = DB::table('sales_documents')->where('document_no', 'BI-MIXED-ZERO-001')->first();
        $this->assertEquals(0.0, (float) $document->vatable_amount);
        $this->assertEquals(47813.39, (float) $document->zero_rated_amount);
        $this->assertEquals(5381.60, (float) $document->freight_total);

        $print = $this->get('/erp/billings/print/invoice?invoice=BI-MIXED-ZERO-001');
        $print->assertStatus(200);
        $this->assertRegExp('/<div class="[^"]*print-field-zero_rated_sales[^"]*"[^>]*>PHP 47,813\.39<\/div>/', $print->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vatable_sales[^"]*"/', $print->getContent());
        $this->assertNotRegExp('/<div class="[^"]*print-field-vat_exempt_sales[^"]*"/', $print->getContent());
    }

    public function test_billing_invoice_prints_each_item_as_a_separate_line_with_its_amount()
    {
        $layout = $this->get('/erp/billings/print-layout/invoice');
        $layout->assertStatus(200);
        $layout->assertSee("var invoiceLineFields = ['line_description', 'line_qty', 'line_unit_price', 'line_amount'];", false);
        $layout->assertSee('id="fontFamilyControl"', false);
        $layout->assertSee('name="font_family"', false);
        $layout->assertSee('Arial (Agenture)');
        $layout->assertSee('Sans Serif (Epson 10 CPI)');
        $layout->assertSee('fixed-width 12 pt text for 10 CPI output');
        $layout->assertSee('Times New Roman');
        $layout->assertSee('id="allFontSizeControl"', false);
        $layout->assertSee('id="boldAllControl"', false);
        $layout->assertSee("setHiddenValue(this.getAttribute('data-field'), 'fs', fontSize, this.getAttribute('data-kind'));", false);

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
        $print->assertSee('data-item-gap="0"', false);
        $print->assertSee("First memo paragraph.\n\nSecond memo paragraph.");
        $print->assertSee('font-family: Arial, Helvetica, sans-serif;', false);
        $print->assertSee('font-family: Arial, Helvetica, sans-serif !important;', false);
        $print->assertSee('font-weight: 700;', false);
        $this->assertRegExp('/\.print-field-prepared_by\s*\{[^}]*font-weight:\s*700\s*!important;/s', $print->getContent());
        $this->assertRegExp('/\.bi-field\s*\{[^}]*line-height:\s*1\.15;/s', $print->getContent());
        $this->assertRegExp('/\.bi-field\s*\{[^}]*letter-spacing:\s*\.25px;/s', $print->getContent());
        $this->assertRegExp('/\.bi-field\s*\{[^}]*letter-spacing:\s*\.25px\s*!important;/s', $print->getContent());
        $print->assertDontSee('current -= 0.5', false);
        $this->assertRegExp('/print-field-customer[^>]*data-fit-bounded[^>]*data-min-font="6"[^>]*height:\s*(?:1[2-9]|[23]\d|4[0-2])px;/', $print->getContent());
        $this->assertRegExp('/print-field-address[^>]*data-fit-bounded[^>]*data-min-font="6"[^>]*height:\s*(?:1[2-9]|[23]\d|4[0-2])px;/', $print->getContent());
        $print->assertSee("document.querySelectorAll('[data-fit-bounded]')", false);
        $print->assertSee("field.style.setProperty('font-size', fitted + 'px', 'important');", false);
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

        $calibriPrint = $this->get('/erp/billings/print/invoice?invoice=BI-TEST-001&font_family=calibri');
        $calibriPrint->assertStatus(200);
        $calibriPrint->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif;', false);
        $calibriPrint->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif !important;', false);

        $sansSerifPrint = $this->get('/erp/billings/print/invoice?invoice=BI-TEST-001&font_family=sans_serif');
        $sansSerifPrint->assertStatus(200);
        $sansSerifPrint->assertSee('font-family: &quot;Epson Sans Serif&quot;, &quot;Courier New&quot;, &quot;Lucida Console&quot;, monospace;', false);
        $sansSerifPrint->assertSee('font-family: &quot;Epson Sans Serif&quot;, &quot;Courier New&quot;, &quot;Lucida Console&quot;, monospace !important;', false);
        $sansSerifPrint->assertSee('bi-print-sheet print-pitch-10', false);
        $this->assertRegExp('/\.bi-print-sheet\.print-pitch-10 \.bi-field\s*\{[^}]*font-size:\s*12pt\s*!important;[^}]*letter-spacing:\s*0\s*!important;/s', $sansSerifPrint->getContent());

        $regularPrint = $this->get('/erp/billings/print/invoice?invoice=BI-TEST-001&bold_all=0');
        $regularPrint->assertStatus(200);
        $this->assertRegExp('/\.bi-field\s*\{[^}]*font-weight:\s*400;/s', $regularPrint->getContent());
        $this->assertRegExp('/\.print-field-prepared_by\s*\{[^}]*font-weight:\s*400\s*!important;/s', $regularPrint->getContent());
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
