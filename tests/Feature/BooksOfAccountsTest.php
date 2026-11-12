<?php

namespace Tests\Feature;

use App\Services\Accounting\PostingService;
use App\Services\Reports\BooksOfAccounts;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BooksOfAccountsTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        DB::table('journal_entry_lines')->delete();
        DB::table('journal_entries')->delete();
        DB::table('accounts')->update(['opening_balance' => 0]);
        $this->actingAsAdmin();
    }

    public function test_1_bank_filter_keeps_both_sides_and_matches_income_drilldown_and_export()
    {
        $invoice = $this->invoice('BANK-BDO', ['freight_total' => 0, 'grand_total' => 112]);
        $this->postInvoice($invoice);
        $this->invoice('BANK-METRO', ['document_type' => 'soa_a', 'template_code' => 'SOA_A', 'ar_account_code' => 'AR_TRADE_SOAA', 'freight_total' => 0, 'grand_total' => 112]);
        $metro = DB::table('sales_documents')->where('document_no', 'BANK-METRO')->first();
        $this->account('1100', 'A/R-Trade(Metro-PHP)');
        $this->postInvoice($metro);
        $data = $this->trial(['bank' => 'BDO']);
        $this->assertEquals(112, $this->row($data, '1080')['SALES BOOK']);
        $this->assertEquals(-100, $this->row($data, '4020')['SALES BOOK']);
        $this->assertEquals(0, $this->row($data, '1100')['SOA']);
        $this->assertEquals(0, $data['totals']['ENDING TRIAL BALANCE']);
        $income = DB::table('accounts')->where('code', '4020')->value('id');
        $this->getJson(route('erp.reports.trial-balance.drilldown', ['period' => '2026-10', 'bank' => 'BDO', 'account_id' => $income, 'column' => 'SALES BOOK']))
            ->assertOk()->assertJsonFragment(['total' => -100]);
        $export = $this->get(route('erp.reports.workbook-excel', ['report' => 'trial-balance', 'period' => '2026-10', 'bank' => 'BDO']))->assertOk();
        $zip = new \ZipArchive();
        $path = $export->baseResponse->getFile()->getPathname();
        $zip->open($path);
        $this->assertContains('BDO', $zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        @unlink($path);
        $this->getJson(route('erp.reports.trial-balance', ['bank' => 'OTHER']))->assertStatus(422);
    }

    public function test_2_billing_splits_tax_classes_into_sales_and_keeps_cwt_at_collection()
    {
        $invoice = $this->invoice('BOOK-MIXED', [
            'grand_total' => 107.2, 'freight_total' => 0, 'tax_total' => 7.2,
            'vatable_amount' => 60, 'zero_rated_amount' => 25, 'vat_exempt_amount' => 15, 'withholding_tax_amount' => 2,
        ]);
        $this->postInvoice($invoice);
        $data = $this->trial();
        foreach (['1080' => 107.2, '4020' => -60, '4060' => -25, '4050' => -15] as $code => $expected) {
            $this->assertEquals($expected, $this->row($data, $code)['SALES BOOK']);
        }
        $this->assertEquals(0, $data['totals']['JOURNAL BOOK']);
        $this->assertEquals(0, $data['totals']['ENDING TRIAL BALANCE']);
        $rows = $this->get(route('erp.reports.workbook', ['report' => 'sales-manila', 'period' => '2026-10']))->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('BILLING INVOICE', 'BOOK-MIXED');
        $this->assertEquals(60, $row['SERVICE INCOME VATABLE']);
        $this->assertEquals(25, $row['SERVICE INCOME ZERO RATED']);
        $this->assertEquals(15, $row['SERVICE INCOME VAT EXEMPT']);
        $this->assertEquals(2, $row['CREDITABLE WITHHOLDING TAX']);
    }

    public function test_import_batch_bank_filter_retains_only_the_selected_balances_debit_credit_pair()
    {
        $lines = collect();
        foreach (['BDO' => 'A/P Trade BDO - PHP', 'Metro' => 'A/P Trade Metro - PHP'] as $bank => $account) {
            foreach ([$account, 'Opening Balance Equity'] as $name) {
                $lines->push((object) ['journal_entry_id' => 999, 'source_type' => 'opening_balance_import',
                    'source_id' => null, 'source_module' => 'GL', 'description' => $bank.' imported balance', 'account_name' => $name, 'debit' => 1, 'credit' => 0]);
            }
        }
        $selected = app(BooksOfAccounts::class)->annotate($lines, 1, 'BDO');
        $this->assertCount(2, $selected);
        $this->assertSame(['BDO imported balance'], $selected->pluck('description')->unique()->values()->all());
    }

    public function test_3_soa_reimbursables_debit_both_currencies_and_soa_a_uses_metro_and_income_4040()
    {
        $this->postInvoice($this->invoice('SOA-B-USD', ['currency' => 'USD', 'exchange_rate' => 56, 'grand_total' => 50, 'freight_total' => 50, 'tax_total' => 0]));
        $this->account('1100', 'A/R-Trade(Metro-PHP)');
        $this->postInvoice($this->invoice('SOA-A-PHP', ['document_type' => 'soa_a', 'template_code' => 'SOA_A', 'grand_total' => 100, 'freight_total' => 0, 'tax_total' => 0]));
        $data = $this->trial();
        $this->assertEquals(2800, $this->row($data, '1070')['SOA']);
        $this->assertEquals(100, $this->row($data, '1100')['SOA']);
        $this->assertEquals(-100, $this->row($data, '4040')['SOA']);
        $rows = $this->get(route('erp.reports.workbook', ['report' => 'soa', 'period' => '2026-10']))->assertOk()->original->getData()['rows'];
        $this->assertEquals(array_sum(array_column($rows, 'DEBIT (PHP)')), array_sum(array_column($rows, 'CREDIT (PHP)')));
    }

    public function test_4_collection_debits_selected_bank_and_cwt_and_credits_trade_receivable()
    {
        $invoice = $this->invoice('COLLECT-BI', ['grand_total' => 112, 'freight_total' => 0]);
        $this->postInvoice($invoice);
        $cash = $this->account('1030', 'Cash in Bank-Metro(PHP)');
        $cwt = $this->account('1330', 'Creditable Withholding Tax');
        $bank = (array) DB::table('bank_accounts')->first();
        unset($bank['id']);
        $bankId = DB::table('bank_accounts')->insertGetId(array_merge($bank, ['bank_name' => 'Metrobank', 'account_no' => 'BOOK-COLLECTION', 'currency' => 'PHP', 'account_id' => $cash]));
        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => 1, 'customer_id' => $invoice->customer_id, 'receipt_no' => 'OR-BOOK-TEST', 'receipt_date' => '2026-10-20',
            'receipt_type' => 'collection_receipt', 'class_code' => 'MANILA', 'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bankId, 'currency' => 'PHP', 'amount' => 110, 'cwt_amount' => 2, 'status' => 'draft',
        ]);
        DB::table('receipt_applications')->insert(['receipt_id' => $receiptId, 'sales_document_id' => $invoice->id, 'component' => 'bi', 'amount_applied' => 112]);
        app(PostingService::class)->postReceipt($receiptId);
        $data = $this->trial(['bank' => 'METROBANK']);
        $this->assertEquals(110, $this->row($data, '1030')['CASH RECEIPTS BOOK']);
        $this->assertEquals(2, $this->row($data, '1330')['CASH RECEIPTS BOOK']);
        $this->assertEquals(-112, $this->row($data, '1080')['CASH RECEIPTS BOOK']);
        $this->assertEquals(0, $data['totals']['CASH RECEIPTS BOOK']);
        $bdo = $this->trial(['bank' => 'BDO']);
        $this->assertEquals(0, $this->row($bdo, '1080')['CASH RECEIPTS BOOK']);
    }

    public function test_5_and_6_vendor_accrual_and_released_payment_belong_to_different_books()
    {
        $bill = DB::table('purchase_documents')->where('paid_total', 0)->first();
        DB::table('purchase_documents')->where('id', $bill->id)->update(['document_date' => '2026-10-15', 'status' => 'draft', 'posting_status' => 'unposted', 'journal_entry_id' => null]);
        $journal = app(PostingService::class)->postPurchaseDocument($bill->id);
        $data = $this->trial();
        $this->assertEquals(0, $data['totals']['CASH DISBURSEMENT BOOK']);
        $credit = DB::table('journal_entry_lines')->where('journal_entry_id', $journal->id)->where('credit', '>', 0)->first();
        $row = collect($data['rows'])->firstWhere('__account_id', $credit->account_id);
        $this->assertLessThan(0, $row['JOURNAL BOOK']);
        $payment = (array) DB::table('payments')->first();
        unset($payment['id']);
        $bank = DB::table('bank_accounts')->where('currency', $bill->currency)->first();
        $paymentId = DB::table('payments')->insertGetId(array_merge($payment, [
            'payment_no' => 'PV-BOOK-TEST', 'bill_no' => $bill->document_no, 'payment_date' => '2026-10-21',
            'bank_account_id' => $bank->id, 'currency' => $bill->currency, 'amount' => 5,
            'status' => 'released', 'approval_status' => 'Released', 'posting_status' => 'unposted', 'journal_entry_id' => null,
        ]));
        app(PostingService::class)->postSupplierPayment($paymentId);
        $general = $this->get(route('erp.reports.workbook', ['report' => 'general-journal', 'period' => '2026-10']))->assertOk()->original->getData()['rows'];
        $this->assertNotContains('PV-BOOK-TEST', json_encode($general));
        $data = $this->trial();
        $this->assertEquals(0, $data['totals']['CASH DISBURSEMENT BOOK']);
        $this->assertNotEquals(0, collect($data['rows'])->sum(function ($row) { return abs($row['CASH DISBURSEMENT BOOK']); }));
    }

    private function invoice($number, array $changes = [])
    {
        $source = (array) DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        unset($source['id']);
        $id = DB::table('sales_documents')->insertGetId(array_merge($source, [
            'document_no' => $number, 'transaction_no' => $number, 'document_date' => '2026-10-15',
            'status' => 'draft', 'posting_status' => 'unposted', 'journal_entry_id' => null,
            'document_type' => 'billing_invoice', 'template_code' => 'BI', 'ar_account_code' => 'AR_TRADE_BI',
            'paid_total' => 0,
        ], $changes));
        return DB::table('sales_documents')->where('id', $id)->first();
    }

    private function postInvoice($invoice)
    {
        return app(PostingService::class)->postSalesDocument($invoice->id);
    }

    private function account($code, $name)
    {
        $account = DB::table('accounts')->where('company_id', 1)->where('code', $code)->first();
        if ($account) {
            DB::table('accounts')->where('id', $account->id)->update(['name' => $name]);
            return $account->id;
        }
        return DB::table('accounts')->insertGetId(['company_id' => 1, 'code' => $code, 'name' => $name, 'type' => 'Accounts Receivable', 'is_active' => true]);
    }

    private function trial(array $filters = [])
    {
        return $this->get(route('erp.reports.trial-balance', array_merge(['period' => '2026-10'], $filters)))->assertOk()->original->getData();
    }

    private function row($data, $code)
    {
        return collect($data['rows'])->first(function ($row) use ($code) { return strpos($row['ACCOUNT TITLES'], $code.' - ') === 0; });
    }
}
