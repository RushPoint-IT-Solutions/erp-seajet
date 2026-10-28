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

    public function test_voided_payments_view_excludes_active_payments()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        $ids = [];
        foreach (['posted', 'draft', 'voided', 'cancelled'] as $status) {
            $ids[$status] = DB::table('receipts')->insertGetId([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'receipt_no' => 'FILTER-'.strtoupper($status),
                'receipt_type' => 'collection_receipt',
                'receipt_date' => '2026-10-28',
                'payment_method' => 'Cash',
                'currency' => 'PHP',
                'exchange_rate' => 1,
                'amount' => 100,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->get(route('erp.payments.create', ['show_voided' => 1]))->assertOk()
            ->assertSee('Show active payments')
            ->assertViewHas('payments', function ($payments) use ($ids) {
                $rows = collect($payments);
                return $rows->contains('id', $ids['voided']) && $rows->contains('id', $ids['cancelled'])
                    && $rows->every(function ($payment) {
                        return in_array($payment['status'], ['Voided', 'Cancelled'], true);
                    });
            });

        foreach ([[], ['show_voided' => '0']] as $query) {
            $this->get(route('erp.payments.create', $query))->assertOk()
                ->assertSee('Show voided payments')
                ->assertViewHas('payments', function ($payments) use ($ids) {
                    $rows = collect($payments);
                    return $rows->contains('id', $ids['posted']) && $rows->contains('id', $ids['draft'])
                        && !$rows->contains('id', $ids['voided']) && !$rows->contains('id', $ids['cancelled']);
                });
        }
    }

    public function test_payment_list_shows_the_recorded_receiving_bank()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        $bank = DB::table('bank_accounts')->where('company_id', $invoice->company_id)->first();
        $this->assertNotNull($bank);
        DB::table('bank_accounts')->where('id', $bank->id)->update([
            'bank_name' => 'Receiving Test Bank',
            'account_name' => 'Customer Collections Account',
        ]);
        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'bank_account_id' => $bank->id,
            'receipt_no' => 'BANK-DISPLAY-001',
            'receipt_type' => 'collection_receipt',
            'receipt_date' => '2026-09-11',
            'payment_method' => 'Bank Transfer',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 162,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('erp.payments.create'))->assertOk()
            ->assertSee('Receiving Bank')
            ->assertViewHas('payments', function ($payments) use ($receiptId) {
                $payment = collect($payments)->firstWhere('id', $receiptId);
                return $payment && $payment['receiving_bank'] === 'Receiving Test Bank'
                    && $payment['receiving_account'] === 'Customer Collections Account'
                    && $payment['amount'] === '162.00';
            });

        DB::table('receipts')->where('id', $receiptId)->update(['bank_account_id' => null]);
        $this->get(route('erp.payments.create'))->assertOk()
            ->assertViewHas('payments', function ($payments) use ($receiptId) {
                $payment = collect($payments)->firstWhere('id', $receiptId);
                return $payment && $payment['receiving_bank'] === '' && $payment['receiving_account'] === '';
            });
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
