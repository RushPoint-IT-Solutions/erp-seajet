<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkbookSettlementPresentationTest extends TestCase
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

    public function test_same_month_advance_is_in_cdb_and_not_general_journal()
    {
        $case = $this->scenario('88916', '2026-10-01', '2026-10-01', 9000, false, 'Advances to Supplier');
        $cdb = $this->rows('disbursement', '2026-10');
        $row = collect($cdb)->firstWhere('CV#', 'CV-88916');
        $this->assertEquals(9000, $row['ADVANCES TO SUPPLIERS']);
        $this->assertEquals(0, $row['ACCOUNTS PAYABLE- TRADE']);
        $this->assertEquals(9000, $row['CASH IN BANK- PESO']);
        $this->assertSame('Bill memo 88916', $row['PARTICULARS']);
        $this->assertCount(0, $this->rows('general-journal', '2026-10'));
        $this->assertDatabaseHas('journal_entry_lines', ['journal_entry_id' => $case['journal'], 'account_id' => $case['ap'], 'credit' => 9000]);
        $response = $this->get(route('erp.reports.workbook-excel', ['report' => 'disbursement', 'period' => '2026-10']))->assertOk();
        $zip = new \ZipArchive();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip->open($path);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertContains('ADVANCES TO SUPPLIERS', $xml);
        $this->assertContains('CV-88916', $xml);
        $this->assertContains('<v>9000</v>', $xml);
        $zip->close();
        @unlink($path);
    }

    public function test_prior_month_bill_remains_an_ap_settlement_when_paid_in_october()
    {
        $this->scenario('88917', '2026-09-20', '2026-10-01', 9000);
        $row = collect($this->rows('disbursement', '2026-10'))->firstWhere('CV#', 'CV-88917');
        $this->assertEquals(9000, $row['ACCOUNTS PAYABLE- TRADE']);
        $this->assertEquals(0, $row['OTHER EXPENSE']);
        $this->assertEquals(9000, $row['CASH IN BANK- PESO']);
        $this->assertCount(2, $this->rows('general-journal', '2026-09'));
    }

    public function test_october_accrual_paid_in_november_retains_tax_lines_vendor_and_memo()
    {
        $case = $this->scenario('8016', '2026-10-01', '2026-11-01', 99.11, true);
        DB::table('purchase_documents')->where('id', $case['bill'])->update(['status' => 'paid', 'paid_total' => 99.11]);
        $rows = $this->rows('general-journal', '2026-10');
        $this->assertCount(4, $rows);
        foreach ($rows as $row) {
            $this->assertSame($case['vendor'], $row['PAYEE']);
            $this->assertSame('Bill memo 8016', $row['ACCOUNT TITLE/PARTICULARS']);
        }
        $this->assertEquals([89.29, 10.71, 0, 0], array_column($rows, 'DEBIT'));
        $this->assertEquals([0, 0, .89, 99.11], array_column($rows, 'CREDIT'));
        $this->assertCount(0, $this->rows('disbursement', '2026-10'));
        $row = collect($this->rows('disbursement', '2026-11'))->firstWhere('CV#', 'CV-8016');
        $this->assertEquals(99.11, $row['ACCOUNTS PAYABLE- TRADE']);
    }

    public function test_partial_same_month_payment_splits_books_without_changing_ledger_or_trial_balance()
    {
        $case = $this->scenario('PARTIAL', '2026-10-01', '2026-10-20', 40, true);
        $general = $this->rows('general-journal', '2026-10');
        $this->assertCount(4, $general);
        $this->assertEquals(array_sum(array_column($general, 'DEBIT')), array_sum(array_column($general, 'CREDIT')), '', .00001);
        $this->assertEquals(59.11, array_sum(array_column($general, 'A/P TRADE - PHP')) * -1, '', .00001);
        $row = collect($this->rows('disbursement', '2026-10'))->firstWhere('CV#', 'CV-PARTIAL');
        $this->assertEquals(0, $row['ACCOUNTS PAYABLE- TRADE']);
        $this->assertEquals(40, $row['OTHER EXPENSE'] + $row['INPUT VAT'] - $row['WITHHOLDING TAX PAYABLE'], '', .00001);
        $trial = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))->assertOk()->original->getData();
        $ap = collect($trial['rows'])->firstWhere('__account_id', $case['ap']);
        $this->assertEquals(-59.11, $ap['JOURNAL BOOK'], '', .00001);
        $this->assertEquals(0, $ap['CASH DISBURSEMENT BOOK']);
        $this->assertEquals(0, $trial['totals']['ENDING TRIAL BALANCE'], '', .00001);
        $this->getJson(route('erp.reports.trial-balance.drilldown', ['period' => '2026-10', 'account_id' => $case['ap'], 'column' => 'JOURNAL BOOK']))
            ->assertOk()->assertJsonFragment(['total' => -59.11]);
        $this->assertDatabaseHas('journal_entry_lines', ['journal_entry_id' => $case['journal'], 'account_id' => $case['ap'], 'credit' => 99.11]);
    }

    public function test_fully_paid_same_month_expense_moves_its_vat_and_withholding_into_cdb()
    {
        $this->scenario('PAID-TAXED', '2026-10-01', '2026-10-20', 99.11, true);
        $this->assertCount(0, $this->rows('general-journal', '2026-10'));
        $row = collect($this->rows('disbursement', '2026-10'))->firstWhere('CV#', 'CV-PAID-TAXED');
        $this->assertEquals(89.29, $row['OTHER EXPENSE']);
        $this->assertEquals(10.71, $row['INPUT VAT']);
        $this->assertEquals(.89, $row['WITHHOLDING TAX PAYABLE']);
        $this->assertEquals(99.11, $row['CASH IN BANK- PESO']);
        $this->assertEquals(0, $row['ACCOUNTS PAYABLE- TRADE']);
    }

    private function rows($report, $period)
    {
        return $this->get(route('erp.reports.workbook', compact('report', 'period')))->assertOk()->original->getData()['rows'];
    }

    private function account($code, $name)
    {
        return DB::table('accounts')->insertGetId(['company_id' => 1, 'account_category_id' => DB::table('account_categories')->value('id'), 'code' => $code, 'name' => $name, 'type' => 'Other Asset', 'is_active' => true]);
    }

    private function journal($number, $date, $type, $sourceId, $lines)
    {
        $id = DB::table('journal_entries')->insertGetId(['company_id' => 1, 'journal_no' => $number,
            'journal_date' => $date, 'source_type' => $type, 'source_id' => $sourceId, 'source_module' => 'AP',
            'status' => 'posted', 'posting_type' => 'original', 'memo' => 'Automatic posting']);
        foreach ($lines as $line) {
            DB::table('journal_entry_lines')->insert(['journal_entry_id' => $id, 'account_id' => $line[0], 'debit' => $line[1], 'credit' => $line[2], 'description' => 'Automatic line description']);
        }
        return $id;
    }

    private function scenario($number, $billDate, $paymentDate, $amount, $taxed = false, $expenseName = 'Office and Pantry Supplies')
    {
        $expense = $this->account('WBR-EXP-'.$number, $expenseName);
        $ap = $this->account('WBR-AP-'.$number, 'A/P-Trade (BDO - PHP)');
        $cash = $this->account('WBR-CASH-'.$number, 'Cash in Bank-BDO(PHP)');
        $bill = (array) DB::table('purchase_documents')->first();
        unset($bill['id']);
        $billId = DB::table('purchase_documents')->insertGetId(array_merge($bill, [
            'document_no' => 'BILL-'.$number, 'document_date' => $billDate, 'currency' => 'PHP', 'exchange_rate' => 1,
            'grand_total' => $taxed ? 99.11 : $amount, 'paid_total' => 0, 'memo' => 'Bill memo '.$number,
            'status' => 'posted', 'journal_entry_id' => null, 'posting_status' => 'posted',
        ]));
        $vendor = DB::table('suppliers')->where('id', $bill['supplier_id'])->value('name');
        $lines = [[$expense, $taxed ? 89.29 : $amount, 0]];
        if ($taxed) {
            $vat = $this->account('WBR-VAT-'.$number, 'Input VAT');
            $wht = $this->account('WBR-WHT-'.$number, 'Expanded Withholding Tax Payable');
            $lines[] = [$vat, 10.71, 0];
            $lines[] = [$wht, 0, .89];
        }
        $lines[] = [$ap, 0, $taxed ? 99.11 : $amount];
        $journal = $this->journal('JV-'.$number, $billDate, 'purchase_document', $billId, $lines);
        DB::table('purchase_documents')->where('id', $billId)->update(['journal_entry_id' => $journal]);
        $bank = (array) DB::table('bank_accounts')->first();
        unset($bank['id']);
        $bankId = DB::table('bank_accounts')->insertGetId(array_merge($bank, ['bank_name' => 'BDO', 'account_no' => 'WBR-'.$number, 'account_id' => $cash, 'currency' => 'PHP']));
        $payment = (array) DB::table('payments')->first();
        unset($payment['id']);
        $paymentId = DB::table('payments')->insertGetId(array_merge($payment, [
            'payment_no' => 'PV-'.$number, 'voucher_no' => 'CV-'.$number, 'bill_no' => 'BILL-'.$number,
            'payment_date' => $paymentDate, 'bank_account_id' => $bankId, 'amount' => $amount, 'currency' => 'PHP',
            'exchange_rate' => 1, 'status' => 'released', 'approval_status' => 'Released', 'posting_status' => 'posted', 'voucher_description' => null,
        ]));
        $this->journal('PV-JV-'.$number, $paymentDate, 'payment', $paymentId, [[$ap, $amount, 0], [$cash, 0, $amount]]);
        return ['ap' => $ap, 'bill' => $billId, 'journal' => $journal, 'vendor' => $vendor];
    }
}
