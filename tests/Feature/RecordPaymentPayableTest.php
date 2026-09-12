<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordPaymentPayableTest extends TestCase
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

    public function test_record_payment_shows_only_unpaid_invoice_with_net_payable_amount()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 112,
            'withholding_tax_amount' => 2,
            'paid_total' => 0,
            'status' => 'posted',
        ]);

        $this->get(route('erp.payments.create'))
            ->assertOk()
            ->assertSee('BI-2026-REIMB')
            ->assertSee('PHP 160.00')
            ->assertSee('data-balance="160.00"', false)
            ->assertSee('data-cwt-balance="2.00"', false);

        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'paid_total' => 112,
            'status' => 'paid',
        ]);

        $this->get(route('erp.payments.create'))
            ->assertOk()
            ->assertDontSee('value="BI-2026-REIMB"', false);
    }

    public function test_net_payment_and_withholding_close_only_the_billing_invoice_amount()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 112,
            'withholding_tax_amount' => 2,
            'paid_total' => 0,
            'status' => 'posted',
        ]);

        $this->post(route('erp.payments.receipt'), [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => $invoice->document_no,
            'payment_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => '160.00',
            'cwt_amount' => '2.00',
        ])->assertOk();

        $updated = DB::table('sales_documents')->where('id', $invoice->id)->first();
        $receipt = DB::table('receipts')->orderBy('id', 'desc')->first();

        $this->assertEquals(162.0, (float) $updated->paid_total);
        $this->assertEquals(0.0, (float) $updated->balance_due);
        $this->assertSame('paid', $updated->status);
        $this->assertEquals(160.0, (float) $receipt->amount);
        $this->assertEquals(2.0, (float) $receipt->cwt_amount);
        $this->assertEquals(162.0, (float) DB::table('receipt_applications')
            ->where('receipt_id', $receipt->id)
            ->value('amount_applied'));

        $this->get(route('erp.payments.create'))
            ->assertOk()
            ->assertSeeInOrder(['For Payment', 'Paid Amount'])
            ->assertSee('PHP 160.00');
    }

    public function test_overpayment_shows_a_warning_and_cannot_be_saved()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 112,
            'withholding_tax_amount' => 2,
            'paid_total' => 0,
            'status' => 'posted',
        ]);

        $this->get(route('erp.payments.create'))
            ->assertOk()
            ->assertSee('Warning: This overpays the invoice');

        $this->from(route('erp.payments.create'))->post(route('erp.payments.receipt'), [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => $invoice->document_no,
            'payment_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => '160.01',
            'cwt_amount' => '2.00',
        ])->assertRedirect(route('erp.payments.create'))->assertSessionHasErrors('amount');

        $this->assertFalse(DB::table('receipts')->where('amount', 160.01)->exists());
    }
}
