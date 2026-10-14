<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorBillDeletionTest extends TestCase
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
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_unposted_vendor_bill_can_be_deleted_with_its_lines_and_attachments()
    {
        $billId = $this->createVendorBill('BILL-DELETE-001');
        DB::table('vendor_bill_lines')->insert([
            'purchase_document_id' => $billId,
            'account_name' => 'Test expense',
            'amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('document_attachments')->insert([
            'document_type' => 'purchase_document',
            'document_id' => $billId,
            'disk' => 'local',
            'path' => 'erp/test-vendor-bill.txt',
            'original_name' => 'test-vendor-bill.txt',
            'size' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->delete(route('erp.payables.vendor-bills.delete', $billId))
            ->assertRedirect(route('erp.expenses.index'))
            ->assertSessionHas('status', 'Vendor bill BILL-DELETE-001 and its connected accounting entries deleted.');

        $this->assertDatabaseMissing('purchase_documents', ['id' => $billId]);
        $this->assertDatabaseMissing('vendor_bill_lines', ['purchase_document_id' => $billId]);
        $this->assertDatabaseMissing('document_attachments', [
            'document_type' => 'purchase_document',
            'document_id' => $billId,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'auditable_type' => 'purchase_document',
            'auditable_id' => $billId,
        ]);
    }

    public function test_posted_unpaid_vendor_bill_is_deleted_with_its_accounting_entries()
    {
        $billId = $this->createVendorBill('BILL-DELETE-002', [
            'status' => 'posted',
            'posting_status' => 'posted',
        ]);
        $companyId = DB::table('companies')->orderBy('id')->value('id');
        $accountId = DB::table('accounts')->where('company_id', $companyId)->orderBy('id')->value('id');
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => $companyId,
            'journal_no' => 'JV-BILL-DELETE-002',
            'journal_date' => '2026-09-01',
            'source_module' => 'AP',
            'source_type' => 'purchase_document',
            'source_id' => $billId,
            'posting_type' => 'original',
            'reference_no' => 'BILL-DELETE-002',
            'status' => 'posted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_entry_lines')->insert([
            'journal_entry_id' => $journalId,
            'account_id' => $accountId,
            'description' => 'Connected vendor bill journal',
            'debit' => 100,
            'credit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('purchase_documents')->where('id', $billId)->update(['journal_entry_id' => $journalId]);

        $this->delete(route('erp.payables.vendor-bills.delete', $billId))
            ->assertRedirect(route('erp.expenses.index'))
            ->assertSessionHas('status', 'Vendor bill BILL-DELETE-002 and its connected accounting entries deleted.');

        $this->assertDatabaseMissing('purchase_documents', ['id' => $billId]);
        $this->assertDatabaseMissing('journal_entry_lines', ['journal_entry_id' => $journalId]);
        $this->assertDatabaseMissing('journal_entries', ['id' => $journalId]);
    }

    public function test_vendor_bill_linked_to_a_payment_cannot_be_deleted()
    {
        $billId = $this->createVendorBill('BILL-DELETE-003');
        $bill = DB::table('purchase_documents')->where('id', $billId)->first();
        DB::table('payments')->insert([
            'company_id' => $bill->company_id,
            'supplier_id' => $bill->supplier_id,
            'payment_no' => 'PV-DELETE-001',
            'bill_no' => $bill->document_no,
            'payment_date' => '2026-09-01',
            'payment_method' => 'Bank',
            'amount' => 100,
            'status' => 'for_approval',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from(route('erp.payables.vendor-bills.create', ['bill' => 'BILL-DELETE-003']))
            ->delete(route('erp.payables.vendor-bills.delete', $billId))
            ->assertSessionHasErrors('bill');

        $this->assertDatabaseHas('purchase_documents', ['id' => $billId]);
    }

    public function test_bill_with_only_a_voided_payment_can_be_deleted_without_deleting_payment_history()
    {
        $billId = $this->createVendorBill('BILL-VOIDED-PAYMENT');
        $bill = DB::table('purchase_documents')->where('id', $billId)->first();
        $paymentId = DB::table('payments')->insertGetId([
            'company_id' => $bill->company_id, 'supplier_id' => $bill->supplier_id,
            'payment_no' => 'CV-VOIDED', 'bill_no' => $bill->document_no,
            'payment_date' => '2026-10-07', 'payment_method' => 'Cheque', 'amount' => 100,
            'status' => 'voided', 'approval_status' => 'Voided', 'posting_status' => 'unposted',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $applicationId = DB::table('payment_applications')->insertGetId([
            'payment_id' => $paymentId, 'purchase_document_id' => $billId,
            'amount_applied' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->delete(route('erp.payables.vendor-bills.delete', $billId))
            ->assertRedirect(route('erp.expenses.index'));
        $this->assertDatabaseMissing('purchase_documents', ['id' => $billId]);
        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'voided', 'bill_no' => $bill->document_no]);
        $this->assertDatabaseHas('payment_applications', [
            'id' => $applicationId, 'purchase_document_id' => null, 'amount_applied' => 100,
        ]);
        $audit = DB::table('activity_logs')->where('auditable_type', 'purchase_document')
            ->where('auditable_id', $billId)->where('action', 'deleted')->first();
        $this->assertEquals($billId, json_decode($audit->old_values, true)['payment_applications'][0]['purchase_document_id']);
    }

    public function test_active_or_unreversed_payment_application_still_blocks_bill_deletion()
    {
        foreach ([['for_approval', 'unposted'], ['voided', 'posted']] as $index => $state) {
            $billId = $this->createVendorBill('BILL-BLOCKED-'.$index);
            $bill = DB::table('purchase_documents')->where('id', $billId)->first();
            $paymentId = DB::table('payments')->insertGetId([
                'company_id' => $bill->company_id, 'supplier_id' => $bill->supplier_id,
                'payment_no' => 'CV-BLOCKED-'.$index, 'bill_no' => null,
                'payment_date' => '2026-10-07', 'payment_method' => 'Cheque', 'amount' => 100,
                'status' => $state[0], 'posting_status' => $state[1],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('payment_applications')->insert([
                'payment_id' => $paymentId, 'purchase_document_id' => $billId,
                'amount_applied' => 100, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->delete(route('erp.payables.vendor-bills.delete', $billId))->assertSessionHasErrors('bill');
            $this->assertDatabaseHas('purchase_documents', ['id' => $billId]);
            $this->assertDatabaseHas('payment_applications', ['payment_id' => $paymentId, 'purchase_document_id' => $billId]);
        }
    }

    private function createVendorBill($documentNo, array $overrides = [])
    {
        $companyId = DB::table('companies')->orderBy('id')->value('id');
        $supplierId = DB::table('suppliers')->where('company_id', $companyId)->orderBy('id')->value('id');

        return DB::table('purchase_documents')->insertGetId(array_merge([
            'company_id' => $companyId,
            'supplier_id' => $supplierId,
            'document_type' => 'vendor_bill',
            'document_no' => $documentNo,
            'document_date' => '2026-09-01',
            'currency' => 'PHP',
            'subtotal' => 100,
            'tax_total' => 0,
            'grand_total' => 100,
            'paid_total' => 0,
            'status' => 'for_payment',
            'posting_status' => 'unposted',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
