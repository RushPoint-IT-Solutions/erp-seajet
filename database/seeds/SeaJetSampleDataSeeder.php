<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeaJetSampleDataSeeder extends Seeder
{
    public function run()
    {
        $now = now();
        $companyId = $this->companyId($now);
        $branchId = $this->branchId($companyId, $now);
        $customers = $this->customers($companyId, $now);
        $suppliers = $this->suppliers($companyId, $now);

        $this->vendorBill($companyId, $branchId, $suppliers['SUP-001'], [
            'document_no' => 'BILL-2026-00492',
            'supplier_address' => '12 Industrial Road, Caloocan City',
            'client_customer_id' => 'CUST-001',
            'shipment_job_id' => 'JOB-SEA-2026-0423',
            'document_date' => '2026-06-05',
            'due_date' => '2026-07-05',
            'currency' => 'PHP',
            'usd_to_php_rate' => 1,
            'gross_amount' => 127900,
            'withholding_tax_rate' => 5,
            'memo' => 'Trucking, depot charges, and terminal services for Manila shipment.',
            'status' => 'for_payment',
            'approval_status' => 'For Approval',
            'lines' => [
                ['account_name' => '5000 - Cost of Services', 'tax_category' => 'VAT12', 'amount' => 96000, 'memo' => 'Trucking and terminal handling', 'customer_job_id' => 'CUST-001', 'is_billable' => true, 'class_code' => 'MANILA'],
                ['account_name' => '5100 - Operating Expenses', 'tax_category' => 'VAT12', 'amount' => 31900, 'memo' => 'Depot and documentation charges', 'customer_job_id' => 'CUST-001', 'is_billable' => true, 'class_code' => 'MANILA'],
            ],
            'attachments' => [
                ['original_name' => 'metro-industrial-bill-00492.pdf', 'path' => 'sample-attachments/metro-industrial-bill-00492.pdf', 'mime_type' => 'application/pdf', 'size' => 184320],
            ],
        ], $now);

        $this->vendorBill($companyId, $branchId, $suppliers['SUP-002'], [
            'document_no' => 'BILL-7710',
            'supplier_address' => 'Pier 7 Fuel Depot, Cebu City',
            'client_customer_id' => 'CUST-002',
            'shipment_job_id' => 'JOB-SEA-2026-0398',
            'document_date' => '2026-06-04',
            'due_date' => '2026-06-24',
            'currency' => 'USD',
            'usd_to_php_rate' => 58.750000,
            'gross_amount' => 1325,
            'withholding_tax_rate' => 2,
            'memo' => 'USD bunker and port service charges for Cebu shipment.',
            'status' => 'partially_paid',
            'approval_status' => 'Approved',
            'lines' => [
                ['account_name' => '5000 - Cost of Services', 'tax_category' => 'ZERO', 'amount' => 980, 'memo' => 'Bunker fuel charges', 'customer_job_id' => 'CUST-002', 'is_billable' => true, 'class_code' => 'CEBU'],
                ['account_name' => 'BANK-FEE', 'tax_category' => 'EXEMPT', 'amount' => 345, 'memo' => 'Port service and bank charges', 'customer_job_id' => 'CUST-002', 'is_billable' => true, 'class_code' => 'CEBU'],
            ],
            'attachments' => [
                ['original_name' => 'pacific-fuel-bill-7710.pdf', 'path' => 'sample-attachments/pacific-fuel-bill-7710.pdf', 'mime_type' => 'application/pdf', 'size' => 142900],
            ],
        ], $now);

        $this->vendorBill($companyId, $branchId, $suppliers['SUP-003'], [
            'document_no' => 'BILL-2026-00495',
            'supplier_address' => 'Harbor Office Center, Paranaque City',
            'client_customer_id' => 'CUST-003',
            'shipment_job_id' => 'JOB-SEA-2026-0365',
            'document_date' => '2026-06-02',
            'due_date' => '2026-07-02',
            'currency' => 'PHP',
            'usd_to_php_rate' => 1,
            'gross_amount' => 18000,
            'withholding_tax_rate' => 0,
            'memo' => 'Monthly office and warehousing support charges.',
            'status' => 'draft',
            'approval_status' => 'Draft',
            'lines' => [
                ['account_name' => '5100 - Operating Expenses', 'tax_category' => 'VAT12', 'amount' => 18000, 'memo' => 'Office support charges', 'customer_job_id' => 'CUST-003', 'is_billable' => false, 'class_code' => 'PAMPANGA'],
            ],
            'attachments' => [],
        ], $now);

        $this->payment($companyId, $suppliers['SUP-002'], [
            'payment_no' => 'PV-2026-00311',
            'voucher_no' => 'CV-2026-00419',
            'bill_no' => 'BILL-7710',
            'payment_date' => '2026-06-06',
            'payment_method' => 'Cheque',
            'bank_account' => 'Metrobank USD Collections - USD',
            'check_no' => 'CHK-883121',
            'currency' => 'USD',
            'exchange_rate' => 58.750000,
            'amount' => 1298.50,
            'shipment_job_id' => 'JOB-SEA-2026-0398',
            'status' => 'approved',
            'approval_status' => 'Approved',
        ], $now);

        $this->payment($companyId, $suppliers['SUP-001'], [
            'payment_no' => 'PV-2026-00312',
            'voucher_no' => 'CV-2026-00420',
            'bill_no' => 'BILL-2026-00492',
            'payment_date' => '2026-06-07',
            'payment_method' => 'Cheque',
            'bank_account' => 'BPI Operating - PHP',
            'check_no' => 'CHK-883122',
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 121505,
            'shipment_job_id' => 'JOB-SEA-2026-0423',
            'status' => 'for_approval',
            'approval_status' => 'For Approval',
        ], $now);
    }

    private function companyId($now)
    {
        DB::table('companies')->updateOrInsert(['code' => 'SJ'], $this->payload('companies', [
            'code' => 'SJ',
            'name' => 'SeaJet Holdings Inc.',
            'tin' => '000-123-456-000',
            'address' => 'Harbor Business Center, Manila, Philippines',
            'base_currency' => 'PHP',
            'updated_at' => $now,
        ]));

        return DB::table('companies')->where('code', 'SJ')->value('id');
    }

    private function branchId($companyId, $now)
    {
        DB::table('branches')->updateOrInsert(['company_id' => $companyId, 'code' => 'MAIN'], $this->payload('branches', [
            'company_id' => $companyId,
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'address' => 'Manila',
            'updated_at' => $now,
        ]));

        return DB::table('branches')->where('company_id', $companyId)->where('code', 'MAIN')->value('id');
    }

    private function customers($companyId, $now)
    {
        $rows = [
            'CUST-001' => ['name' => 'North Harbor Trading', 'email' => 'billing@northharbor.test', 'payment_terms' => '30 Days', 'credit_term_days' => 30, 'currency' => 'PHP', 'default_class_code' => 'MANILA', 'default_ar_account_code' => 'AR_TRADE_BI'],
            'CUST-002' => ['name' => 'Blue Anchor Logistics', 'email' => 'billing@blueanchor.test', 'payment_terms' => '60 Days', 'credit_term_days' => 60, 'currency' => 'USD', 'default_class_code' => 'CEBU', 'default_ar_account_code' => 'AR_TRADE_SOAA'],
            'CUST-003' => ['name' => 'Island Prime Foods', 'email' => 'billing@islandprime.test', 'payment_terms' => '90 Days', 'credit_term_days' => 90, 'currency' => 'PHP', 'default_class_code' => 'PAMPANGA', 'default_ar_account_code' => 'AR_TRADE_DN'],
        ];

        foreach ($rows as $code => $row) {
            DB::table('customers')->updateOrInsert(['company_id' => $companyId, 'code' => $code], $this->payload('customers', $row + [
                'company_id' => $companyId,
                'code' => $code,
                'customer_id' => $code,
                'credit_limit' => 250000,
                'is_active' => true,
                'updated_at' => $now,
            ]));
        }

        return array_map(function ($code) use ($companyId) {
            return DB::table('customers')->where('company_id', $companyId)->where('code', $code)->value('id');
        }, array_keys($rows));
    }

    private function suppliers($companyId, $now)
    {
        $rows = [
            'SUP-001' => ['name' => 'Metro Industrial Supply', 'email' => 'ap@metroindustrial.test', 'address' => '12 Industrial Road, Caloocan City', 'payment_terms' => '30 Days', 'payment_term_days' => 30, 'currency' => 'PHP', 'preferred_payment_method' => 'Cheque', 'default_class_code' => 'MANILA', 'default_ap_account' => 'Accounts Payable Trade - PHP', 'withholding_tax' => '5%'],
            'SUP-002' => ['name' => 'Pacific Fuel Services', 'email' => 'billing@pacificfuel.test', 'address' => 'Pier 7 Fuel Depot, Cebu City', 'payment_terms' => '20 Days', 'payment_term_days' => 20, 'currency' => 'USD', 'preferred_payment_method' => 'Cheque', 'default_class_code' => 'CEBU', 'default_ap_account' => 'AP-Trade (BDO - USD)', 'withholding_tax' => '2%'],
            'SUP-003' => ['name' => 'Harbor Office Center', 'email' => 'admin@harboroffice.test', 'address' => 'Harbor Office Center, Paranaque City', 'payment_terms' => '30 Days', 'payment_term_days' => 30, 'currency' => 'PHP', 'preferred_payment_method' => 'Bank', 'default_class_code' => 'PAMPANGA', 'default_ap_account' => 'Operating Expense Payable', 'withholding_tax' => '0%'],
        ];

        $ids = [];
        foreach ($rows as $code => $row) {
            DB::table('suppliers')->updateOrInsert(['company_id' => $companyId, 'code' => $code], $this->payload('suppliers', $row + [
                'company_id' => $companyId,
                'code' => $code,
                'is_active' => true,
                'updated_at' => $now,
            ]));
            $ids[$code] = DB::table('suppliers')->where('company_id', $companyId)->where('code', $code)->value('id');
        }

        return $ids;
    }

    private function vendorBill($companyId, $branchId, $supplierId, array $bill, $now)
    {
        $gross = (float) $bill['gross_amount'];
        $wht = round($gross * ((float) $bill['withholding_tax_rate'] / 100), 4);
        $net = round($gross - $wht, 4);
        $rate = (float) $bill['usd_to_php_rate'];
        $phpAmount = strtoupper($bill['currency']) === 'USD' ? round($gross * $rate, 4) : $gross;

        DB::table('purchase_documents')->updateOrInsert(['company_id' => $companyId, 'document_no' => $bill['document_no']], $this->payload('purchase_documents', [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'supplier_id' => $supplierId,
            'document_type' => 'vendor_bill',
            'transaction_type' => 'Bill',
            'ap_account' => strtoupper($bill['currency']) === 'USD' ? 'AP-Trade (BDO - USD)' : 'Accounts Payable Trade - PHP',
            'bill_received' => true,
            'supplier_address' => $bill['supplier_address'],
            'client_customer_id' => $bill['client_customer_id'],
            'shipment_job_id' => $bill['shipment_job_id'],
            'document_no' => $bill['document_no'],
            'document_date' => $bill['document_date'],
            'due_date' => $bill['due_date'],
            'terms' => '30 Days',
            'currency' => $bill['currency'],
            'exchange_rate' => strtoupper($bill['currency']) === 'USD' ? $rate : 1,
            'usd_to_php_rate' => $rate,
            'gross_amount' => $gross,
            'php_amount' => $phpAmount,
            'subtotal' => $gross,
            'tax_total' => 0,
            'withholding_tax_rate' => $bill['withholding_tax_rate'],
            'withholding_tax_amount' => $wht,
            'grand_total' => $net,
            'net_amount' => $net,
            'paid_total' => $bill['status'] === 'partially_paid' ? $net : 0,
            'status' => $bill['status'],
            'approval_status' => $bill['approval_status'],
            'remarks' => $bill['memo'],
            'memo' => $bill['memo'],
            'updated_at' => $now,
        ]));

        $billId = DB::table('purchase_documents')->where('company_id', $companyId)->where('document_no', $bill['document_no'])->value('id');
        $this->replaceBillLines($billId, $bill['lines'], $now);
        $this->replaceAttachments('purchase_document', $billId, $bill['attachments'], $now);
    }

    private function replaceBillLines($billId, array $lines, $now)
    {
        $table = Schema::hasTable('vendor_bill_lines') ? 'vendor_bill_lines' : 'purchase_document_lines';
        DB::table($table)->where('purchase_document_id', $billId)->delete();

        foreach ($lines as $line) {
            DB::table($table)->insert($this->payload($table, $line + [
                'purchase_document_id' => $billId,
                'description' => $line['memo'],
                'quantity' => 1,
                'unit_cost' => $line['amount'],
                'line_total' => $line['amount'],
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function payment($companyId, $supplierId, array $payment, $now)
    {
        DB::table('payments')->updateOrInsert(['company_id' => $companyId, 'payment_no' => $payment['payment_no']], $this->payload('payments', $payment + [
            'company_id' => $companyId,
            'supplier_id' => $supplierId,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        $paymentId = DB::table('payments')->where('company_id', $companyId)->where('payment_no', $payment['payment_no'])->value('id');
        $billId = DB::table('purchase_documents')->where('company_id', $companyId)->where('document_no', $payment['bill_no'])->value('id');

        if ($billId && Schema::hasTable('payment_applications')) {
            DB::table('payment_applications')->where('payment_id', $paymentId)->delete();
            DB::table('payment_applications')->insert($this->payload('payment_applications', [
                'payment_id' => $paymentId,
                'purchase_document_id' => $billId,
                'amount_applied' => $payment['amount'],
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function replaceAttachments($documentType, $documentId, array $attachments, $now)
    {
        if (!Schema::hasTable('document_attachments')) {
            return;
        }

        DB::table('document_attachments')->where('document_type', $documentType)->where('document_id', $documentId)->delete();
        foreach ($attachments as $attachment) {
            DB::table('document_attachments')->insert($this->payload('document_attachments', $attachment + [
                'document_type' => $documentType,
                'document_id' => $documentId,
                'disk' => 'local',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function payload($table, array $values)
    {
        return array_filter($values, function ($value, $column) use ($table) {
            return Schema::hasColumn($table, $column);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
