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
            ->assertSessionHas('status', 'Vendor bill BILL-DELETE-001 deleted.');

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

    public function test_posted_vendor_bill_cannot_be_deleted()
    {
        $billId = $this->createVendorBill('BILL-DELETE-002', ['posting_status' => 'posted']);

        $this->from(route('erp.payables.vendor-bills.create', ['bill' => 'BILL-DELETE-002']))
            ->delete(route('erp.payables.vendor-bills.delete', $billId))
            ->assertRedirect(route('erp.payables.vendor-bills.create', ['bill' => 'BILL-DELETE-002']))
            ->assertSessionHasErrors('bill');

        $this->assertDatabaseHas('purchase_documents', ['id' => $billId]);
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
