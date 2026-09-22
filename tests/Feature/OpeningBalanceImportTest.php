<?php

namespace Tests\Feature;

use App\Services\Accounting\PostingService;
use App\Services\Accounting\XlsxOpeningBalanceReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class OpeningBalanceImportTest extends TestCase
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

    public function test_opening_balance_workbook_can_be_previewed_and_posted_once()
    {
        $accounts = $this->accountMapping();
        $customer = DB::table('customers')->where('company_id', 1)->where('is_active', true)->first();
        $supplier = DB::table('suppliers')->where('company_id', 1)->where('is_active', true)->first();

        $this->get(route('erp.accounting.journals'))
            ->assertStatus(200)
            ->assertSee('Import Opening Balances');

        $this->get(route('erp.invoices.index'))
            ->assertStatus(200)
            ->assertSee('Import Invoices');
        $this->get(route('erp.expenses.index'))
            ->assertStatus(200)
            ->assertSee('Import Bills');

        $page = $this->get(route('erp.accounting.opening-balances.import'));
        $page->assertStatus(200)
            ->assertSee('Opening-balance workbook')
            ->assertSee('A/R Trade')
            ->assertSee('A/R Reimbursable')
            ->assertSee('A/P Trade');

        $rows = [
            ['MAIN', 'A/R TRADE', 'BILLING INVOICE', '2026-09-01', $customer->name, 'INV-OB-001', 1000, '', ''],
            ['CEB', 'A/R REIMBURSABLE', 'SOA B', '2026-09-02', $customer->name, 'REIM-OB-001', 2000, 40, 50],
            ['PAM', 'A/P TRADE', 'VENDOR BILL', '2026-09-03', $supplier->name, 'AP-OB-001', 500, '', ''],
            ['MAIN', 'A/R TRADE', 'BILLING INVOICE', '2026-09-04', 'UNKNOWN CUSTOMER', 'INV-BAD-001', 100, '', ''],
        ];
        $workbookPath = $this->workbook($rows);

        $preview = $this->post(route('erp.accounting.opening-balances.preview'), array_merge([
            'file' => new UploadedFile($workbookPath, 'opening-balances.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ], $accounts));

        $preview->assertStatus(200)
            ->assertSee('Import preview')
            ->assertSee('Import 4 Ready Rows')
            ->assertSee('New customer will be created')
            ->assertSee('PHP 3,600.00');

        $viewData = $preview->original->getData();
        $this->assertSame(4, $viewData['preview']['valid_count']);
        $this->assertSame(0, $viewData['preview']['skipped_count']);

        $commit = $this->post(route('erp.accounting.opening-balances.commit'), [
            'token' => $viewData['token'],
        ]);

        $journals = DB::table('journal_entries')->where('source_type', 'opening_balance_import')->orderBy('journal_date')->get();
        $journal = $journals->first();
        $commit->assertRedirect(route('erp.accounting.journals.show', $journal->id));
        $this->assertCount(4, $journals);
        $this->assertSame(['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04'], $journals->pluck('journal_date')->all());
        $this->assertSame('2026-09-01 00:00:00', $journals[0]->posted_at);
        $this->assertSame('2026-09-02 00:00:00', $journals[1]->posted_at);
        $this->assertSame('2026-09-03 00:00:00', $journals[2]->posted_at);
        $this->assertSame('2026-09-04 00:00:00', $journals[3]->posted_at);
        $this->assertSame('posted', $journal->status);
        $this->assertSame(8, DB::table('journal_entry_lines')->whereIn('journal_entry_id', $journals->pluck('id'))->count());
        $this->assertEquals(3600.0, (float) DB::table('journal_entry_lines')->whereIn('journal_entry_id', $journals->pluck('id'))->sum('debit'));
        $this->assertEquals(3600.0, (float) DB::table('journal_entry_lines')->whereIn('journal_entry_id', $journals->pluck('id'))->sum('credit'));
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journals[1]->id,
            'account_id' => $accounts['opening_balance_account_id'],
            'debit' => 2000,
            'currency' => 'USD',
            'foreign_debit' => 40,
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journals[1]->id,
            'account_id' => $accounts['ar_reimbursable_usd_account_id'],
            'customer_id' => $customer->id,
            'credit' => 2000,
            'foreign_credit' => 40,
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journals[2]->id,
            'account_id' => $accounts['ap_trade_php_account_id'],
            'supplier_id' => $supplier->id,
            'credit' => 500,
        ]);
        $this->assertDatabaseHas('opening_balance_import_batches', [
            'journal_entry_id' => $journal->id,
            'row_count' => 4,
            'skipped_count' => 0,
            'posting_date' => '2026-09-04',
        ]);
        $this->assertSame(4, DB::table('opening_balance_import_rows')->count());

        $tradeInvoice = DB::table('sales_documents')->where('transaction_no', 'INV-OB-001')->first();
        $reimbursableInvoice = DB::table('sales_documents')->where('transaction_no', 'REIM-OB-001')->first();
        $vendorBill = DB::table('purchase_documents')->where('reference_no', 'AP-OB-001')->first();
        $createdCustomer = DB::table('customers')->where('name', 'UNKNOWN CUSTOMER')->first();
        $createdCustomerInvoice = DB::table('sales_documents')->where('transaction_no', 'INV-BAD-001')->first();
        $this->assertNotNull($tradeInvoice);
        $this->assertNotNull($reimbursableInvoice);
        $this->assertNotNull($vendorBill);
        $this->assertNotNull($createdCustomer);
        $this->assertNotNull($createdCustomerInvoice);
        $this->assertSame((int) $createdCustomer->id, (int) $createdCustomerInvoice->customer_id);
        $this->assertSame('OB-UNKNOWN-CUSTOMER', $createdCustomer->code);
        $this->assertSame('2026-09-04', $createdCustomerInvoice->document_date);
        $this->assertSame((int) $journals[3]->id, (int) $createdCustomerInvoice->journal_entry_id);
        $this->assertSame('posted', $tradeInvoice->posting_status);
        $this->assertSame('posted', $vendorBill->posting_status);
        $this->assertSame('2026-09-01', $tradeInvoice->document_date);
        $this->assertSame('2026-09-02', $reimbursableInvoice->document_date);
        $this->assertSame('2026-09-03', $vendorBill->document_date);
        $this->assertSame('2026-09-01 00:00:00', $tradeInvoice->accounting_posted_at);
        $this->assertSame('2026-09-02 00:00:00', $reimbursableInvoice->accounting_posted_at);
        $this->assertSame('2026-09-03 00:00:00', $vendorBill->accounting_posted_at);
        $this->assertSame((int) $journals[0]->id, (int) $tradeInvoice->journal_entry_id);
        $this->assertSame((int) $journals[1]->id, (int) $reimbursableInvoice->journal_entry_id);
        $this->assertSame((int) $journals[2]->id, (int) $vendorBill->journal_entry_id);
        $this->assertEquals(1000.0, (float) $tradeInvoice->balance_due);
        $this->assertSame('USD', $reimbursableInvoice->currency);
        $this->assertEquals(40.0, (float) $reimbursableInvoice->grand_total);
        $this->assertEquals(50.0, (float) $reimbursableInvoice->exchange_rate);
        $this->assertEquals(500.0, (float) $vendorBill->grand_total);
        $this->assertSame('Approved', $vendorBill->approval_status);
        $this->assertSame(3, DB::table('sales_document_lines')
            ->whereIn('sales_document_id', [$tradeInvoice->id, $reimbursableInvoice->id, $createdCustomerInvoice->id])->count());
        $this->assertSame(1, DB::table('vendor_bill_lines')->where('purchase_document_id', $vendorBill->id)->count());
        $this->assertSame(3, DB::table('opening_balance_import_rows')->whereNotNull('sales_document_id')->count());
        $this->assertSame(1, DB::table('opening_balance_import_rows')->whereNotNull('purchase_document_id')->count());

        $this->get(route('erp.payments.create'))
            ->assertStatus(200)
            ->assertSee($tradeInvoice->document_no)
            ->assertSee($reimbursableInvoice->document_no);
        $this->get(route('erp.payables.payments.create'))
            ->assertStatus(200)
            ->assertSee($vendorBill->document_no);
        $this->get(route('erp.invoices.index'))
            ->assertStatus(200)
            ->assertSee($tradeInvoice->document_no)
            ->assertSee($reimbursableInvoice->document_no);
        $this->get(route('erp.expenses.index'))
            ->assertStatus(200)
            ->assertSee($vendorBill->document_no);

        $receiptId = DB::table('receipts')->insertGetId([
            'company_id' => 1,
            'customer_id' => $customer->id,
            'receipt_no' => 'OR-OB-TEST',
            'receipt_date' => '2026-09-20',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 1000,
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('receipt_applications')->insert([
            'receipt_id' => $receiptId,
            'sales_document_id' => $tradeInvoice->id,
            'amount_applied' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        app(PostingService::class)->postReceipt($receiptId);
        $this->get(route('erp.official-receipt.preview', ['receipt_no' => 'OR-OB-TEST']))
            ->assertOk()
            ->assertSee('PAYMENT FOR LOCAL CHARGES')
            ->assertSee('BI# INV-OB-001')
            ->assertDontSee('imported opening balance');
        DB::table('sales_documents')->where('id', $tradeInvoice->id)->update(['transaction_no' => 'BI#000644']);
        $this->get(route('erp.official-receipt.preview', ['receipt_no' => 'OR-OB-TEST']))
            ->assertOk()
            ->assertSee('BI#000644')
            ->assertDontSee('BI# BI#000644');
        $receiptJournalId = DB::table('journal_entries')
            ->where('source_type', 'receipt')->where('source_id', $receiptId)->value('id');
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $receiptJournalId,
            'account_id' => $accounts['ar_trade_php_account_id'],
            'credit' => 1000,
        ]);

        $this->post(route('erp.payments.receipt'), [
            'customer_id' => (string) $customer->id,
            'invoice_no' => $reimbursableInvoice->document_no,
            'payment_for' => 'soa',
            'payment_date' => '2026-09-20',
            'payment_method' => 'Cash',
            'currency' => 'USD',
            'exchange_rate' => 50,
            'amount' => '40.00',
            'cwt_amount' => '0.00',
        ])->assertOk();
        $soaReceipt = DB::table('receipts')->orderBy('id', 'desc')->first();
        $this->assertDatabaseHas('receipt_applications', [
            'receipt_id' => $soaReceipt->id,
            'sales_document_id' => $reimbursableInvoice->id,
            'component' => 'soa',
            'amount_applied' => 40,
        ]);
        $this->get(route('erp.acknowledgement-receipt.preview', ['receipt_no' => $soaReceipt->receipt_no]))
            ->assertOk()
            ->assertSee('PAYMENT FOR REIMBURSABLE CHARGES')
            ->assertSee('SOA# REIM-OB-001')
            ->assertDontSee('imported opening balance');

        $supplierPaymentId = DB::table('payments')->insertGetId([
            'company_id' => 1,
            'supplier_id' => $supplier->id,
            'payment_no' => 'PV-OB-TEST',
            'bill_no' => $vendorBill->document_no,
            'payment_date' => '2026-09-20',
            'payment_method' => 'Cash',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 500,
            'status' => 'released',
            'approval_status' => 'Released',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        app(PostingService::class)->postSupplierPayment($supplierPaymentId);
        $supplierPaymentJournalId = DB::table('journal_entries')
            ->where('source_type', 'payment')->where('source_id', $supplierPaymentId)->value('id');
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $supplierPaymentJournalId,
            'account_id' => $accounts['ap_trade_php_account_id'],
            'debit' => 500,
        ]);

        $duplicatePreview = $this->post(route('erp.accounting.opening-balances.preview'), array_merge([
            'file' => new UploadedFile($workbookPath, 'opening-balances.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ], $accounts));
        $duplicatePreview->assertStatus(200)->assertSee('This source row was already imported.');
        $duplicateData = $duplicatePreview->original->getData()['preview'];
        $this->assertSame(0, $duplicateData['valid_count']);
        $this->assertSame(4, $duplicateData['skipped_count']);
    }

    public function test_dedicated_seeder_imports_the_workbook_and_is_safe_to_run_again()
    {
        $this->accountMapping();
        $customer = DB::table('customers')->where('company_id', 1)->where('is_active', true)->first();
        $supplier = DB::table('suppliers')->where('company_id', 1)->where('is_active', true)->first();
        $arRows = [
            ['MAIN', 'A/R TRADE', 'BILLING INVOICE', '2026-09-01', $customer->name, 'SEED-AR-001', 1000, '', ''],
        ];
        $apRows = [
            ['MAIN', 'A/P TRADE', 'VENDOR BILL', '2026-09-02', $supplier->name, 'SEED-AP-001', 500, '', ''],
        ];
        $arPath = $this->workbook($arRows);
        $apPath = $this->workbook($apRows);

        putenv('OPENING_BALANCE_IMPORT_FILE='.$arPath);
        putenv('OPENING_BALANCE_AP_IMPORT_FILE='.$apPath);
        $_ENV['OPENING_BALANCE_IMPORT_FILE'] = $arPath;
        $_ENV['OPENING_BALANCE_AP_IMPORT_FILE'] = $apPath;

        try {
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'App\\Seeders\\OpeningBalanceImportSeeder',
                '--force' => true,
            ]);
            $this->assertSame(0, $exitCode);
            $output = Artisan::output();
            $this->assertStringContainsString('Created open invoices: 1', $output);
            $this->assertStringContainsString('Created open vendor bills: 1', $output);
            $this->assertStringContainsString('Created customers: 0', $output);
            $this->assertStringContainsString('Created journals: 1 (Excel dates 2026-09-01 to 2026-09-01)', $output);
            $this->assertStringContainsString('Created journals: 1 (Excel dates 2026-09-02 to 2026-09-02)', $output);
            $this->assertSame(2, DB::table('opening_balance_import_batches')->count());
            $this->assertSame(2, DB::table('journal_entries')->where('source_type', 'opening_balance_import')->count());
            $this->assertDatabaseHas('sales_documents', ['transaction_no' => 'SEED-AR-001']);
            $this->assertDatabaseHas('purchase_documents', ['reference_no' => 'SEED-AP-001']);

            $exitCode = Artisan::call('db:seed', [
                '--class' => 'App\\Seeders\\OpeningBalanceImportSeeder',
                '--force' => true,
            ]);
            $this->assertSame(0, $exitCode);
            $output = Artisan::output();
            $this->assertStringContainsString('Nothing was imported', $output);
            $this->assertSame(2, DB::table('opening_balance_import_batches')->count());
        } finally {
            putenv('OPENING_BALANCE_IMPORT_FILE');
            putenv('OPENING_BALANCE_AP_IMPORT_FILE');
            unset($_ENV['OPENING_BALANCE_IMPORT_FILE'], $_ENV['OPENING_BALANCE_AP_IMPORT_FILE']);
        }
    }

    public function test_default_seed_workbooks_are_packaged_with_ar_and_ap_rows()
    {
        $arPath = base_path('docs/AR TRADE/AR TRADE & AR REIMBURSABLE.xlsx');
        $apPath = base_path('docs/A-P TRADE.xlsx');
        $this->assertFileExists($arPath);
        $this->assertFileExists($apPath);

        $counts = [];
        foreach (app(XlsxOpeningBalanceReader::class)->read($arPath) as $row) {
            $counts[$row['sheet_name']] = ($counts[$row['sheet_name']] ?? 0) + 1;
        }

        $this->assertSame([
            'AR-TRADE' => 134,
            'AR-REIM' => 116,
        ], $counts);

        $apRows = app(XlsxOpeningBalanceReader::class)->read($apPath);
        $this->assertCount(43, $apRows);
        $this->assertSame(['A/P TRADE'], array_values(array_unique(array_column($apRows, 'account_type'))));
        $this->assertSame(['MAIN'], array_values(array_unique(array_column($apRows, 'branch'))));
        $this->assertSame(['PHP'], array_values(array_unique(array_column($apRows, 'currency'))));
    }

    private function accountMapping()
    {
        $equityCategoryId = DB::table('account_categories')->where('name', 'Equity')->value('id');
        $openingAccountId = DB::table('accounts')->insertGetId([
            'company_id' => 1,
            'account_category_id' => $equityCategoryId,
            'code' => '3999',
            'name' => 'Opening Balance Equity',
            'type' => 'Equity',
            'opening_balance' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $arTradeId = DB::table('accounts')->where('company_id', 1)->where('code', '1210')->value('id');
        $arReimbursableId = DB::table('accounts')->where('company_id', 1)->where('code', '1220')->value('id');
        $apTradeId = DB::table('accounts')->where('company_id', 1)->where('code', '2100')->value('id');

        return [
            'opening_balance_account_id' => $openingAccountId,
            'ar_trade_php_account_id' => $arTradeId,
            'ar_trade_usd_account_id' => $arTradeId,
            'ar_reimbursable_php_account_id' => $arReimbursableId,
            'ar_reimbursable_usd_account_id' => $arReimbursableId,
            'ap_trade_php_account_id' => $apTradeId,
            'ap_trade_usd_account_id' => $apTradeId,
        ];
    }

    private function workbook(array $rows)
    {
        $path = tempnam(sys_get_temp_dir(), 'opening_balance_');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="OPENING BALANCES" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');

        array_unshift($rows, ['BRANCH', 'ACCOUNT TYPE', 'DOCUMENT TYPE', 'Date', 'CUSTOMER NAME', 'NUMBER', 'OPENING BALANCE', 'USD AMOUNT', 'EXCHANGE RATE']);
        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $column = chr(65 + $columnIndex);
                if (is_numeric($value) && $value !== '') {
                    $cells .= '<c r="'.$column.$rowNumber.'"><v>'.$value.'</v></c>';
                } else {
                    $cells .= '<c r="'.$column.$rowNumber.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
                }
            }
            $sheetRows .= '<row r="'.$rowNumber.'">'.$cells.'</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();

        $xlsxPath = $path.'.xlsx';
        rename($path, $xlsxPath);

        return $xlsxPath;
    }
}
