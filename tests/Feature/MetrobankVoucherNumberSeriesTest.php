<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetrobankVoucherNumberSeriesTest extends TestCase
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

    public function test_metrobank_cv_and_jv_series_are_available_in_finance_settings()
    {
        foreach (['check_voucher' => 'CV-MB-', 'journal_voucher' => 'JV-MB-'] as $type => $prefix) {
            $this->assertDatabaseHas('number_series', [
                'company_id' => 1,
                'document_type' => $type,
                'class_code' => 'METROBANK',
                'prefix' => $prefix,
                'current_number' => 0,
                'is_active' => 1,
            ]);
        }

        $this->get(route('erp.settings.index'))
            ->assertOk()
            ->assertSee('METROBANK')
            ->assertSee('CV-MB-00001')
            ->assertSee('JV-MB-000001');
    }

    public function test_new_metrobank_payment_uses_the_metrobank_check_voucher_series()
    {
        $bank = DB::table('bank_accounts')->where('bank_name', 'Metrobank')->first();
        $sourceBill = DB::table('purchase_documents')->where('currency', $bank->currency)->first();
        $supplier = DB::table('suppliers')->where('id', $sourceBill->supplier_id)->first();

        $bill = (array) $sourceBill;
        unset($bill['id']);
        $bill['document_no'] = 'BILL-METROBANK-CV-TEST';
        $bill['reference_no'] = 'MB-CV-TEST';
        $bill['paid_total'] = 0;
        $bill['grand_total'] = 100;
        $bill['net_amount'] = 100;
        $bill['status'] = 'for_payment';
        $bill['approval_status'] = 'Approved';
        DB::table('purchase_documents')->insert($bill);

        $globalCurrent = (int) DB::table('number_series')
            ->where('company_id', $sourceBill->company_id)
            ->where('document_type', 'check_voucher')
            ->where('class_code', '')
            ->value('current_number');

        $this->post(route('erp.payables.payments.review'), [
            'payment_date' => '2026-10-13',
            'bill_no' => $bill['document_no'],
            'supplier_id' => $supplier->code,
            'supplier_name' => $supplier->name,
            'payee_name' => $supplier->name,
            'payment_method' => 'Bank Transfer',
            'bank_account' => $bank->account_no,
            'currency' => $bank->currency,
            'exchange_rate' => $bank->currency === 'PHP' ? 1 : 58.25,
            'amount' => 100,
        ])->assertOk();

        $payment = DB::table('payments')->where('bill_no', $bill['document_no'])->first();
        $this->assertSame('CV-MB-00001', $payment->voucher_no);
        $this->assertSame(1, (int) DB::table('number_series')
            ->where('company_id', $sourceBill->company_id)
            ->where('document_type', 'check_voucher')
            ->where('class_code', 'METROBANK')
            ->value('current_number'));
        $this->assertSame($globalCurrent, (int) DB::table('number_series')
            ->where('company_id', $sourceBill->company_id)
            ->where('document_type', 'check_voucher')
            ->where('class_code', '')
            ->value('current_number'));
    }

    public function test_payment_form_exposes_the_correct_cv_preview_for_each_bank_series()
    {
        $response = $this->get(route('erp.payables.payments.create'));

        $response->assertOk()
            ->assertSee('CV No')
            ->assertSee('Select a bank account')
            ->assertSee('data-next-voucher-no="CV-MB-00001"', false)
            ->assertSee('data-next-voucher-no="CV-00426"', false);
    }
}
