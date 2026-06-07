<?php

namespace Tests\Feature;

use Tests\TestCase;

class BillingInvoicePrintFlowTest extends TestCase
{
    public function test_create_billing_invoice_exposes_guest_non_tax_soa_a_path()
    {
        $response = $this->get('/erp/invoices/create');

        $response->assertStatus(200);
        $response->assertSee('GUEST - Guest / Non-tax Client');
        $response->assertSee('SOA A');
        $response->assertSee('SOA B');
        $response->assertSee('Billing Invoice');
        $response->assertSee('Print SOA B + Billing Invoice');
        $response->assertSee('Print SOA A');
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

        $response->assertStatus(200);
        $response->assertSee('Print SOA B');
        $response->assertSee('Print Billing Invoice');
        $response->assertDontSee('Print SOA A');
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
            'tax_category' => ['12%', 'exempt'],
            'line_amount' => ['100.00', '50.00'],
        ]));

        $response->assertStatus(200);
        $response->assertSee('Invoice Amount');
        $response->assertSee('112.00');
        $response->assertSee('Reimbursement Total');
        $response->assertSee('50.00');
        $response->assertSee('Soa Total');
        $response->assertSee('162.00');
        $response->assertSee('billing_total=112.00', false);
        $response->assertSee('reimbursement_total=50.00', false);
        $response->assertSee('soa_total=162.00', false);

        $invoicePrint = $this->get('/erp/billings/print/invoice?customer=North%20Harbor%20Trading&invoice_no=BI-TEST-001&invoice_date=2026-05-26&soa_no=SOA-TEST-001&billing_total=112.00&reimbursement_total=50.00&soa_total=162.00&vatable_sales=100.00&vat_amount=12.00');
        $invoicePrint->assertStatus(200);
        $invoicePrint->assertSee('PHP 112.00');
        $invoicePrint->assertDontSee('PHP 162.00');

        $soaPrint = $this->get('/erp/billings/print/statement-of-account?customer=North%20Harbor%20Trading&invoice_no=BI-TEST-001&invoice_date=2026-05-26&soa_no=SOA-TEST-001&billing_total=112.00&reimbursement_total=50.00&soa_total=162.00&vatable_sales=100.00&vat_amount=12.00');
        $soaPrint->assertStatus(200);
        $soaPrint->assertSee('REIMBURSEMENT CHARGES     PHP 50.00');
        $soaPrint->assertSee('INVOICE AMOUNT            PHP 112.00');
        $soaPrint->assertSee('PLEASE PAY THIS AMOUNT    PHP 162.00');
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

        $response->assertStatus(200);
        $response->assertSee('Print SOA A');
        $response->assertDontSee('Print SOA B');
        $response->assertDontSee('Print Billing Invoice');
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
