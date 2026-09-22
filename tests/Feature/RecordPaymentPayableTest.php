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
            ->assertSee("Select the client first. Only that client's unpaid invoices will appear below.", false)
            ->assertSee('class="form-check-input payment-invoice-check"', false)
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

        $response = $this->post(route('erp.payments.receipt'), [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => $invoice->document_no,
            'payment_for' => 'bi',
            'payment_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => '110.00',
            'cwt_amount' => '2.00',
        ]);
        $response->assertOk()
            ->assertSee('VAT Official Receipt')
            ->assertSee('Non-VAT Official Receipt')
            ->assertDontSee('VAT Acknowledgement Receipt');

        $updated = DB::table('sales_documents')->where('id', $invoice->id)->first();
        $receipt = DB::table('receipts')->orderBy('id', 'desc')->first();

        $this->assertEquals(112.0, (float) $updated->paid_total);
        $this->assertEquals(50.0, (float) $updated->balance_due);
        $this->assertSame('partial', $updated->status);
        $this->assertEquals(110.0, (float) $receipt->amount);
        $this->assertEquals(2.0, (float) $receipt->cwt_amount);
        $this->assertSame('collection_receipt', $receipt->receipt_type);
        $this->assertStringStartsWith('OR-', $receipt->receipt_no);
        $this->assertEquals(112.0, (float) DB::table('receipt_applications')
            ->where('receipt_id', $receipt->id)
            ->sum('amount_applied'));

        $this->get(route('erp.payments.create'))
            ->assertOk()
            ->assertSeeInOrder(['For Payment', 'Paid Amount'])
            ->assertSee('PHP 110.00')
            ->assertSee('>OR</button>', false)
            ->assertSee('Non-VAT Print');
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
            'payment_for' => 'bi',
            'payment_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => '110.01',
            'cwt_amount' => '2.00',
        ])->assertRedirect(route('erp.payments.create'))->assertSessionHasErrors('amount');

        $this->assertFalse(DB::table('receipts')->where('amount', 110.01)->exists());
    }

    public function test_new_payment_requires_one_billing_component()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        $this->withExceptionHandling();

        $this->from(route('erp.payments.create'))->post(route('erp.payments.receipt'), [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => $invoice->document_no,
            'payment_for' => 'all',
            'payment_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'amount' => '162.00',
        ])->assertRedirect(route('erp.payments.create'))->assertSessionHasErrors('payment_for');

        $this->assertFalse(DB::table('receipts')->where('amount', 162)->exists());
    }

    public function test_billing_and_soa_portions_can_be_paid_separately()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 112,
            'freight_total' => 50,
            'grand_total' => 162,
            'soa_no' => 'SOA-7370',
            'withholding_tax_amount' => 0,
            'paid_total' => 0,
            'status' => 'posted',
        ]);

        $form = $this->get(route('erp.payments.create'));
        $form->assertOk()
            ->assertSee('Billing Invoice (BI) only')
            ->assertSee('Statement of Account (SOA) only')
            ->assertSee('Next Receipt No.')
            ->assertSee('data-bi-balance="112.00"', false)
            ->assertSee('data-soa-balance="50.00"', false)
            ->assertSee('data-soa-reference="SOA-7370"', false);
        $this->assertStringStartsWith('OR-', $form->original->getData()['receiptSeries']['bi']);
        $this->assertStringStartsWith('AR-', $form->original->getData()['receiptSeries']['soa']);
        $nextBiNumber = $form->original->getData()['receiptSeries']['bi'];
        $nextSoaNumber = $form->original->getData()['receiptSeries']['soa'];

        $fields = [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => $invoice->document_no,
            'payment_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'cwt_amount' => '0.00',
        ];
        $this->post(route('erp.payments.receipt'), $fields + [
            'payment_for' => 'bi', 'amount' => '112.00',
        ])->assertOk();
        $biReceipt = DB::table('receipts')->orderBy('id', 'desc')->first();
        $this->assertStringStartsWith('OR-', $biReceipt->receipt_no);
        $this->assertSame($nextBiNumber, $biReceipt->receipt_no);
        $this->assertSame('collection_receipt', $biReceipt->receipt_type);
        $this->assertDatabaseHas('receipt_applications', [
            'receipt_id' => $biReceipt->id, 'sales_document_id' => $invoice->id,
            'component' => 'bi', 'amount_applied' => 112,
        ]);
        $this->assertEquals(50, (float) DB::table('sales_documents')->where('id', $invoice->id)->value('balance_due'));
        $this->get(route('erp.official-receipt.preview', ['receipt_no' => $biReceipt->receipt_no]))
            ->assertOk()->assertSee('PHP 112.00')->assertSee($biReceipt->receipt_no);

        $this->from(route('erp.payments.create'))->post(route('erp.payments.receipt'), $fields + [
            'payment_for' => 'bi', 'amount' => '1.00',
        ])->assertRedirect(route('erp.payments.create'))->assertSessionHasErrors('amount');

        $this->post(route('erp.payments.receipt'), $fields + [
            'payment_for' => 'soa', 'amount' => '50.00',
        ])->assertOk();
        $soaReceipt = DB::table('receipts')->orderBy('id', 'desc')->first();
        $this->assertStringStartsWith('AR-', $soaReceipt->receipt_no);
        $this->assertSame($nextSoaNumber, $soaReceipt->receipt_no);
        $this->assertSame('acknowledgement_receipt', $soaReceipt->receipt_type);
        $this->assertDatabaseHas('receipt_applications', [
            'receipt_id' => $soaReceipt->id, 'sales_document_id' => $invoice->id,
            'component' => 'soa', 'amount_applied' => 50,
        ]);
        $soaJournal = DB::table('journal_entries')->where('source_type', 'receipt')
            ->where('source_id', $soaReceipt->id)->first();
        $reimbursableAccountId = DB::table('accounts')->where('company_id', $invoice->company_id)
            ->where('code', '1220')->value('id');
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $soaJournal->id,
            'account_id' => $reimbursableAccountId,
            'credit' => 50,
        ]);
        $this->assertSame('paid', DB::table('sales_documents')->where('id', $invoice->id)->value('status'));
        $this->get(route('erp.acknowledgement-receipt.preview', ['receipt_no' => $soaReceipt->receipt_no]))
            ->assertOk()->assertSee('PHP 50.00')->assertSee($soaReceipt->receipt_no)->assertSee('SOA-7370');
        $paymentRows = $this->get(route('erp.payments.create'))->original->getData()['payments'];
        $this->assertSame('SOA-7370', $paymentRows[0]['document_reference']);
        $this->assertSame('BI-2026-REIMB', $paymentRows[1]['document_reference']);
    }

    public function test_receipt_series_and_class_follow_the_invoice_class()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'class_code' => 'CEBU', 'invoice_amount' => 112, 'freight_total' => 50,
            'grand_total' => 162, 'withholding_tax_amount' => 0, 'paid_total' => 0,
            'status' => 'posted',
        ]);

        foreach ([['official_receipt', 'OR-CEB-', 120, 5], ['acknowledgement_receipt', 'AR-CEB-', 45, 6]] as $settings) {
            $this->post(route('erp.settings.number-series.save'), [
                'document_type' => $settings[0], 'class_code' => 'CEBU',
                'prefix' => $settings[1], 'current_number' => $settings[2],
                'padding' => $settings[3], 'is_active' => '1',
            ])->assertRedirect();
        }

        $this->get(route('erp.settings.index'))->assertOk()
            ->assertSee('OR-CEB-00121')->assertSee('AR-CEB-000046');

        $page = $this->get(route('erp.payments.create'));
        $page->assertOk()->assertSee('data-class-code="CEBU"', false);
        $this->assertSame('OR-CEB-00121', $page->original->getData()['receiptSeries']['by_class']['bi']['CEBU']);
        $this->assertSame('AR-CEB-000046', $page->original->getData()['receiptSeries']['by_class']['soa']['CEBU']);

        $fields = [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => [$invoice->document_no], 'class_code' => 'CEBU',
            'payment_date' => '2026-09-12', 'payment_method' => 'Cash',
            'currency' => 'PHP', 'exchange_rate' => 1, 'cwt_amount' => '0.00',
        ];
        $this->post(route('erp.payments.receipt'), $fields + ['payment_for' => 'bi', 'amount' => '112.00'])->assertOk();
        $or = DB::table('receipts')->orderBy('id', 'desc')->first();
        $this->assertSame('OR-CEB-00121', $or->receipt_no);
        $this->assertSame('CEBU', $or->class_code);

        $this->post(route('erp.payments.receipt'), $fields + ['payment_for' => 'soa', 'amount' => '50.00'])->assertOk();
        $ar = DB::table('receipts')->orderBy('id', 'desc')->first();
        $this->assertSame('AR-CEB-000046', $ar->receipt_no);
        $this->assertSame('CEBU', $ar->class_code);
        $this->assertSame(0, (int) DB::table('number_series')->where('document_type', 'official_receipt')->where('class_code', 'MANILA')->value('current_number'));
    }

    public function test_one_receipt_cannot_combine_invoices_from_different_classes()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        $copy = (array) $invoice;
        unset($copy['id']);
        $copy['document_no'] = 'BI-PAM-OTHER';
        $copy['class_code'] = 'PAMPANGA';
        DB::table('sales_documents')->insert($copy);

        $this->from(route('erp.payments.create'))->post(route('erp.payments.receipt'), [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => [$invoice->document_no, 'BI-PAM-OTHER'],
            'payment_for' => 'bi', 'payment_date' => '2026-09-12',
            'payment_method' => 'Cash', 'currency' => 'PHP', 'amount' => '10.00',
        ])->assertRedirect(route('erp.payments.create'))->assertSessionHasErrors('invoice_no');
    }

    public function test_wrong_combined_payment_can_be_reversed_then_replaced_with_bi_only()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 112, 'freight_total' => 50, 'grand_total' => 162,
            'soa_no' => 'SOA-7370', 'withholding_tax_amount' => 0, 'paid_total' => 0, 'status' => 'posted',
        ]);
        $invoice->soa_no = 'SOA-7370';
        $fields = [
            'customer_id' => (string) $invoice->customer_id,
            'invoice_no' => $invoice->document_no,
            'payment_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'cwt_amount' => '0.00',
        ];
        $wrongReceiptId = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'receipt_no' => 'OR-LEGACY-001',
            'receipt_type' => 'collection_receipt',
            'receipt_date' => '2026-09-12',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 162,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('receipt_applications')->insert([
            'receipt_id' => $wrongReceiptId, 'sales_document_id' => $invoice->id,
            'amount_applied' => 162, 'created_at' => now(), 'updated_at' => now(),
        ]);
        app(\App\Services\Accounting\PostingService::class)->postReceipt($wrongReceiptId);
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'paid_total' => 162, 'balance_due' => 0, 'status' => 'paid',
        ]);
        $wrongReceipt = DB::table('receipts')->where('id', $wrongReceiptId)->first();
        $this->assertSame('paid', DB::table('sales_documents')->where('id', $invoice->id)->value('status'));

        $this->get(route('erp.payments.create'))->assertOk()
            ->assertSee(route('erp.payments.correct', $wrongReceipt->id))
            ->assertDontSee('edit-payment');
        $this->post(route('erp.payments.correct', $wrongReceipt->id), [
            'reason' => 'Client settled BI only',
        ])->assertRedirect(route('erp.payments.create', [
            'invoice' => $invoice->document_no, 'payment_for' => 'bi',
        ]));
        $this->assertSame('voided', DB::table('receipts')->where('id', $wrongReceipt->id)->value('status'));
        $this->assertEquals(0, (float) DB::table('sales_documents')->where('id', $invoice->id)->value('paid_total'));
        $this->assertSame('posted', DB::table('sales_documents')->where('id', $invoice->id)->value('status'));
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'receipt', 'source_id' => $wrongReceipt->id,
            'posting_type' => 'reversal', 'status' => 'posted',
        ]);
        $this->get(route('erp.payments.create'))->assertOk()
            ->assertSee('data-bi-balance="112.00"', false)
            ->assertSee('data-soa-balance="50.00"', false)
            ->assertDontSee($wrongReceipt->receipt_no);

        $this->post(route('erp.payments.receipt'), $fields + [
            'payment_for' => 'bi', 'amount' => '112.00',
        ])->assertOk();
        $this->assertEquals(50, (float) DB::table('sales_documents')->where('id', $invoice->id)->value('balance_due'));
        $paymentPage = $this->get(route('erp.payments.create'));
        $paymentPage->assertOk()
            ->assertSee('data-bi-balance="0.00"', false)
            ->assertSee('data-soa-balance="50.00"', false);
        $this->assertSame('112.00', $paymentPage->original->getData()['payments'][0]['for_payment']);
        $statement = $this->get(route('erp.reports.client-soa', ['customer_id' => $invoice->customer_id]));
        $statement->assertOk();
        $statementRow = $statement->original->getData()['invoices']->first(function ($row) use ($invoice) {
            return $row->document_no === $invoice->document_no;
        });
        $this->assertNotNull($statementRow);
        $this->assertSame($invoice->document_no, $statementRow->document_no);
        $this->assertSame($invoice->soa_no, $statementRow->soa_no);
        $this->assertEquals(0, (float) $statementRow->invoice_amount);
        $this->assertEquals(50, (float) $statementRow->soa_amount);
        $this->assertEquals(50, (float) $statementRow->total);
    }

    public function test_one_payment_can_apply_to_multiple_invoices_and_labels_them_on_the_official_receipt()
    {
        $firstInvoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $firstInvoice->id)->update([
            'invoice_amount' => 100,
            'freight_total' => 0,
            'grand_total' => 100,
            'withholding_tax_amount' => 0,
            'paid_total' => 0,
            'status' => 'posted',
        ]);
        $firstInvoice = DB::table('sales_documents')->where('id', $firstInvoice->id)->first();

        $secondInvoiceData = (array) $firstInvoice;
        unset($secondInvoiceData['id']);
        $secondInvoiceData['document_no'] = 'BI-MULTI-002';
        $secondInvoiceId = DB::table('sales_documents')->insertGetId($secondInvoiceData);

        $response = $this->post(route('erp.payments.receipt'), [
            'customer_id' => (string) $firstInvoice->customer_id,
            'invoice_no' => ['BI-MULTI-002', $firstInvoice->document_no],
            'payment_for' => 'bi',
            'payment_date' => '2026-09-13',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => '200.00',
            'cwt_amount' => '0.00',
        ]);
        $response->assertOk();

        $receipt = DB::table('receipts')->orderBy('id', 'desc')->first();
        $this->assertSame(
            [(int) $secondInvoiceId, (int) $firstInvoice->id],
            DB::table('receipt_applications')
                ->where('receipt_id', $receipt->id)
                ->orderBy('id')
                ->pluck('sales_document_id')
                ->map(function ($id) { return (int) $id; })
                ->all()
        );

        $this->get(route('erp.official-receipt.preview', ['receipt_no' => $receipt->receipt_no]))
            ->assertOk()
            ->assertSeeInOrder([
                'Billing 1: BI-MULTI-002',
                'Billing 2: '.$firstInvoice->document_no,
            ]);
    }
}
