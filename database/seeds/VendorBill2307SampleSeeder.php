<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VendorBill2307SampleSeeder extends Seeder
{
    const DOCUMENT_NO = 'BILL-2307-SAMPLE';

    public function run()
    {
        if (!Schema::hasTable('purchase_documents') || !Schema::hasTable('vendor_bill_lines')) {
            throw new RuntimeException('Vendor bill tables are missing. Run php artisan migrate first.');
        }
        foreach (['atc_code', 'withholding_tax_rate', 'withholding_tax_amount'] as $column) {
            if (!Schema::hasColumn('vendor_bill_lines', $column)) {
                throw new RuntimeException('Vendor bill withholding columns are missing. Run php artisan migrate first.');
            }
        }

        $company = DB::table('companies')->where('code', 'SJ')->first();
        if (!$company) {
            throw new RuntimeException('SeaJet company master data is missing. Run SeaJetProductionDataSeeder first.');
        }

        $supplier = DB::table('suppliers')
            ->where('company_id', $company->id)
            ->where('code', 'SUP-001')
            ->first();
        $account = DB::table('accounts')
            ->where('company_id', $company->id)
            ->where('name', 'Export Air Expense')
            ->where('atc_code', 'WC160')
            ->first();
        $branchId = DB::table('branches')
            ->where('company_id', $company->id)
            ->where('code', 'MAIN')
            ->value('id');

        if (!$supplier || !$account) {
            throw new RuntimeException('The sample supplier or WC160 expense account is missing. Run SeaJetProductionDataSeeder first.');
        }

        DB::transaction(function () use ($company, $supplier, $account, $branchId) {
            $now = now();
            DB::table('purchase_documents')->updateOrInsert(
                ['company_id' => $company->id, 'document_no' => self::DOCUMENT_NO],
                [
                    'branch_id' => $branchId,
                    'supplier_id' => $supplier->id,
                    'ap_account' => 'Accounts Payable Trade',
                    'bill_received' => true,
                    'supplier_address' => $supplier->address,
                    'document_type' => 'vendor_bill',
                    'transaction_type' => 'Bill',
                    'reference_no' => 'SAMPLE-SI-2088',
                    'shipment_job_id' => 'JOB-2307-SAMPLE',
                    'document_date' => '2026-08-28',
                    'due_date' => '2026-09-27',
                    'terms' => '30 Days',
                    'currency' => 'PHP',
                    'gross_amount' => 11200,
                    'exchange_rate' => 1,
                    'usd_to_php_rate' => 1,
                    'php_amount' => 11200,
                    'subtotal' => 11200,
                    'vatable_amount' => 10000,
                    'zero_rated_amount' => 0,
                    'vat_exempt_amount' => 0,
                    'tax_total' => 1200,
                    'withholding_tax_rate' => 2,
                    'withholding_tax_amount' => 200,
                    'net_amount' => 11000,
                    'grand_total' => 11000,
                    'paid_total' => 0,
                    'status' => 'for_payment',
                    'approval_status' => 'For Approval',
                    'remarks' => 'SAMPLE DATA — BIR Form 2307 draggable print test.',
                    'memo' => 'SAMPLE DATA — Export air service subject to WC160 at 2%.',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $billId = DB::table('purchase_documents')
                ->where('company_id', $company->id)
                ->where('document_no', self::DOCUMENT_NO)
                ->value('id');

            DB::table('vendor_bill_lines')->where('purchase_document_id', $billId)->delete();
            DB::table('vendor_bill_lines')->insert([
                'purchase_document_id' => $billId,
                'account_id' => $account->id,
                'account_name' => $account->code.' - '.$account->name,
                'tax_category' => 'VAT12',
                'atc_code' => 'WC160',
                'withholding_tax_rate' => 2,
                'withholding_tax_amount' => 200,
                'amount' => 11200,
                'memo' => 'Sample export air freight service',
                'customer_job_id' => 'JOB-2307-SAMPLE',
                'is_billable' => false,
                'class_code' => 'MANILA',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $this->command->info('Sample vendor bill ready: '.self::DOCUMENT_NO);
    }
}
