<?php

namespace Tests\Feature;

use App\Services\Accounting\PostingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountingPostingFlowTest extends TestCase
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

    public function test_sales_document_posts_a_balanced_source_linked_journal_once()
    {
        $document = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        $this->assertNotNull($document);

        $posting = app(PostingService::class);
        $journal = $posting->postSalesDocument($document->id);
        $sameJournal = $posting->postSalesDocument($document->id);

        $this->assertSame($journal->id, $sameJournal->id);
        $this->assertSame(1, DB::table('journal_entries')
            ->where('source_type', 'sales_document')
            ->where('source_id', $document->id)
            ->where('posting_type', 'original')
            ->count());

        $totals = DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journal->id)
            ->selectRaw('SUM(debit) as debits, SUM(credit) as credits')
            ->first();
        $this->assertEquals(162.00, (float) $totals->debits);
        $this->assertEquals(162.00, (float) $totals->credits);

        $posted = DB::table('sales_documents')->where('id', $document->id)->first();
        $this->assertSame('posted', $posted->posting_status);
        $this->assertEquals($journal->id, $posted->journal_entry_id);
    }

    public function test_posted_sales_document_reverses_without_deleting_original_journal()
    {
        $document = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        $posting = app(PostingService::class);
        $original = $posting->postSalesDocument($document->id);
        $reversal = $posting->reverseSource('sales_document', $document->id, 'Test correction');

        $this->assertNotNull($reversal);
        $this->assertSame('reversed', DB::table('journal_entries')->where('id', $original->id)->value('status'));
        $this->assertSame('reversal', $reversal->posting_type);
        $this->assertEquals($original->id, $reversal->reversed_entry_id);

        $net = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.source_type', 'sales_document')
            ->where('journal_entries.source_id', $document->id)
            ->selectRaw('SUM(debit - credit) as net')
            ->value('net');
        $this->assertEquals(0.0, (float) $net);
    }

    public function test_closed_period_rejects_posting()
    {
        $document = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('accounting_periods')->insert([
            'company_id' => $document->company_id,
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        app(PostingService::class)->postSalesDocument($document->id);
    }

    public function test_vendor_bill_posts_input_vat_withholding_and_payable_balanced()
    {
        $bill = DB::table('purchase_documents')
            ->where('withholding_tax_amount', '>', 0)
            ->orderBy('id')
            ->first();
        $this->assertNotNull($bill);

        $journal = app(PostingService::class)->postPurchaseDocument($bill->id);
        $totals = DB::table('journal_entry_lines')
            ->where('journal_entry_id', $journal->id)
            ->selectRaw('SUM(debit) as debits, SUM(credit) as credits')
            ->first();

        $this->assertEquals((float) $totals->debits, (float) $totals->credits);
        $this->assertGreaterThan(0, (float) $totals->debits);
        $this->assertSame('posted', DB::table('purchase_documents')->where('id', $bill->id)->value('posting_status'));
    }

    public function test_customer_collection_posts_cash_cwt_and_reverses_the_bank_movement()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        $bank = DB::table('bank_accounts')->where('company_id', $invoice->company_id)->where('currency', 'PHP')->first();
        $openingBalance = (float) $bank->book_balance;
        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'receipt_no' => 'CR-TEST-CWT',
            'receipt_type' => 'collection_receipt',
            'receipt_date' => '2026-08-15',
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id,
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 90,
            'cwt_amount' => 10,
            'cwt_certificate_no' => '2307-TEST-001',
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('receipt_applications')->insert([
            'receipt_id' => $receiptId,
            'sales_document_id' => $invoice->id,
            'amount_applied' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $posting = app(PostingService::class);
        $journal = $posting->postReceipt($receiptId);
        $totals = DB::table('journal_entry_lines')->where('journal_entry_id', $journal->id)
            ->selectRaw('SUM(debit) as debits, SUM(credit) as credits')->first();
        $this->assertEquals(100.0, (float) $totals->debits);
        $this->assertEquals(100.0, (float) $totals->credits);
        $this->assertEquals(10.0, (float) DB::table('journal_entry_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entry_lines.journal_entry_id', $journal->id)
            ->where('accounts.code', '1260')->value('journal_entry_lines.debit'));
        $this->assertEquals($openingBalance + 90, (float) DB::table('bank_accounts')->where('id', $bank->id)->value('book_balance'));

        $posting->reverseSource('receipt', $receiptId, 'Collection correction');
        $this->assertEquals($openingBalance, (float) DB::table('bank_accounts')->where('id', $bank->id)->value('book_balance'));
        $this->assertSame(2, DB::table('bank_transactions')->where('source_id', $receiptId)->whereIn('source_type', ['receipt', 'receipt_reversal'])->count());
    }

    public function test_usd_supplier_payment_posts_at_exchange_rate_and_updates_cashbook()
    {
        $bill = DB::table('purchase_documents')->orderBy('id')->first();
        $bank = DB::table('bank_accounts')->where('company_id', $bill->company_id)->where('currency', 'USD')->first();
        $paymentId = DB::table('payments')->insertGetId([
            'company_id' => $bill->company_id,
            'supplier_id' => $bill->supplier_id,
            'bill_no' => $bill->document_no,
            'payment_no' => 'PV-USD-TEST',
            'payment_date' => '2026-08-16',
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id,
            'bank_account' => $bank->account_no,
            'currency' => 'USD',
            'exchange_rate' => 58.25,
            'amount' => 100,
            'status' => 'released',
            'approval_status' => 'Released',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journal = app(PostingService::class)->postSupplierPayment($paymentId);
        $totals = DB::table('journal_entry_lines')->where('journal_entry_id', $journal->id)
            ->selectRaw('SUM(debit) as debits, SUM(credit) as credits')->first();
        $this->assertEquals(5825.0, (float) $totals->debits);
        $this->assertEquals(5825.0, (float) $totals->credits);
        $this->assertEquals(5825.0, (float) DB::table('bank_transactions')
            ->where('source_type', 'payment')->where('source_id', $paymentId)->value('credit'));
    }
}
