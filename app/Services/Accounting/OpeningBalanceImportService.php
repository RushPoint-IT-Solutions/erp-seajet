<?php

namespace App\Services\Accounting;

use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningBalanceImportService
{
    private $reader;
    private $periods;
    private $activity;

    public function __construct(
        XlsxOpeningBalanceReader $reader,
        PeriodLockService $periods,
        ActivityLogger $activity
    ) {
        $this->reader = $reader;
        $this->periods = $periods;
        $this->activity = $activity;
    }

    public function preview($path, $companyId, array $mapping, $fileHash)
    {
        $accounts = $this->mappedAccounts($companyId, $mapping);
        $branches = $this->branchIndex($companyId);
        $customers = $this->partyIndex('customers', $companyId);
        $suppliers = $this->partyIndex('suppliers', $companyId);
        $alreadyImported = $this->alreadyImportedRows($companyId, $fileHash);
        $rows = [];

        foreach ($this->reader->read($path) as $source) {
            $errors = [];
            $balanceType = $this->balanceType($source['account_type'], $source['document_type']);
            if (!$balanceType) {
                $errors[] = 'Unsupported account type: '.($source['account_type'] ?: 'blank');
            }

            $currency = strtoupper($source['currency'] ?: 'PHP');
            if (!in_array($currency, ['PHP', 'USD'], true)) {
                $errors[] = 'Currency must be PHP or USD.';
            }
            if ($currency === 'USD' && (!$source['foreign_amount'] || $source['exchange_rate'] <= 0)) {
                $errors[] = 'USD rows need a foreign amount and exchange rate.';
            }

            $mappingKey = $balanceType ? $balanceType.'_'.strtolower($currency) : null;
            $targetAccount = $mappingKey && isset($accounts[$mappingKey]) ? $accounts[$mappingKey] : null;
            if ($balanceType && !$targetAccount) {
                $errors[] = 'No '.$currency.' account was selected for '.$this->balanceTypeLabel($balanceType).'.';
            }

            $branch = $branches[$this->normalize($source['branch'])] ?? null;
            if (!$branch) {
                $errors[] = 'Branch was not found: '.($source['branch'] ?: 'blank');
            }

            $partyIndex = $balanceType === 'ap_trade' ? $suppliers : $customers;
            $party = $partyIndex[$this->normalize($source['party_name'])] ?? null;
            $willCreateCustomer = !$party
                && in_array($balanceType, ['ar_trade', 'ar_reimbursable'], true)
                && trim((string) $source['party_name']) !== '';
            if (!$party && !$willCreateCustomer) {
                $errors[] = ($balanceType === 'ap_trade' ? 'Vendor' : 'Customer').' was not found: '.($source['party_name'] ?: 'blank');
            }

            if (!$source['source_date']) {
                $errors[] = 'Document date is missing or invalid.';
            }
            if ($source['reference_no'] === '') {
                $errors[] = 'Document number is required.';
            }
            if ($source['base_amount'] <= 0) {
                $errors[] = 'Opening balance must be greater than zero.';
            }

            $rowKey = $source['sheet_name'].'|'.$source['row_number'];
            $isDuplicate = isset($alreadyImported[$rowKey]);
            if ($isDuplicate) {
                $errors[] = 'This source row was already imported.';
            }

            $rows[] = array_merge($source, [
                'balance_type' => $balanceType,
                'balance_type_label' => $this->balanceTypeLabel($balanceType),
                'branch_id' => $branch ? $branch->id : null,
                'party_id' => $party ? $party->id : null,
                'party_code' => $party ? $party->code : null,
                'will_create_customer' => $willCreateCustomer,
                'target_account_id' => $targetAccount ? $targetAccount->id : null,
                'target_account_code' => $targetAccount ? $targetAccount->code : null,
                'target_account_name' => $targetAccount ? $targetAccount->name : null,
                'target_account_label' => $targetAccount ? trim($targetAccount->code.' - '.$targetAccount->name) : null,
                'errors' => $errors,
                'is_valid' => empty($errors),
            ]);
        }

        $rows = $this->assignSubledgerDocumentNumbers($companyId, $rows);

        $validRows = array_values(array_filter($rows, function ($row) {
            return $row['is_valid'];
        }));

        $summary = [];
        foreach ($validRows as $row) {
            $key = $row['balance_type'].'|'.$row['currency'];
            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'label' => $row['balance_type_label'],
                    'currency' => $row['currency'],
                    'rows' => 0,
                    'amount' => 0,
                ];
            }
            $summary[$key]['rows']++;
            $summary[$key]['amount'] += $row['base_amount'];
        }

        return [
            'rows' => $rows,
            'valid_rows' => $validRows,
            'valid_count' => count($validRows),
            'skipped_count' => count($rows) - count($validRows),
            'total_amount' => array_sum(array_column($validRows, 'base_amount')),
            'source_date_from' => $validRows ? min(array_column($validRows, 'source_date')) : null,
            'source_date_to' => $validRows ? max(array_column($validRows, 'source_date')) : null,
            'source_date_count' => count(array_unique(array_column($validRows, 'source_date'))),
            'summary' => array_values($summary),
            'opening_account' => $accounts['opening_balance'] ?? null,
        ];
    }

    public function import($path, $companyId, array $mapping, $fileHash, $filename, $userId)
    {
        $preview = $this->preview($path, $companyId, $mapping, $fileHash);
        if ($preview['valid_count'] < 1) {
            throw ValidationException::withMessages([
                'file' => 'There are no valid, unimported rows to post.',
            ]);
        }

        foreach (array_unique(array_column($preview['valid_rows'], 'source_date')) as $sourceDate) {
            $this->periods->assertOpen($companyId, $sourceDate);
        }

        return $this->postPreview($preview, $companyId, $mapping, $fileHash, $filename, $userId);
    }

    public function importManualArTrade($companyId, array $data, $userId)
    {
        $branch = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('id', $data['branch_id'])
            ->where('is_active', true)
            ->first();
        $customer = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('id', $data['customer_id'])
            ->where('is_active', true)
            ->first();
        $targetAccount = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('id', $data['target_account_id'])
            ->where('is_active', true)
            ->first();
        $openingAccount = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('id', $data['opening_balance_account_id'])
            ->where('is_active', true)
            ->first();

        if (!$branch || !$customer || !$targetAccount || !$openingAccount) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Select an active branch, customer, receivable account, and opening-balance account for this company.',
            ]);
        }
        $targetName = $this->normalize($targetAccount->name);
        if (strpos($targetName, 'ARTRADE') === false && strpos($targetName, 'ACCOUNTSRECEIVABLE') === false) {
            throw ValidationException::withMessages([
                'target_account_id' => 'Select an A/R Trade or Accounts Receivable account.',
            ]);
        }
        if (strpos($this->normalize($openingAccount->name), 'OPENINGBALANCE') === false) {
            throw ValidationException::withMessages([
                'opening_balance_account_id' => 'Select the Opening Balance Equity account.',
            ]);
        }

        $reference = trim((string) $data['reference_no']);
        if (DB::table('sales_documents')->where('company_id', $companyId)
            ->where(function ($query) use ($reference) {
                $query->where('document_no', $reference)->orWhere('transaction_no', $reference);
            })->exists()) {
            throw ValidationException::withMessages([
                'reference_no' => 'This BI/SOA reference already exists.',
            ]);
        }

        $currency = strtoupper($data['currency']);
        $transactionAmount = round((float) $data['amount'], 4);
        $exchangeRate = $currency === 'PHP' ? 1.0 : round((float) $data['exchange_rate'], 6);
        $baseAmount = round($transactionAmount * $exchangeRate, 4);
        $fileHash = hash('sha256', implode('|', [
            'manual-ar', $companyId, $reference, microtime(true), random_int(1, PHP_INT_MAX),
        ]));
        $filename = 'Manual AR Opening Balance - '.$reference;
        $mapping = [
            'opening_balance' => (int) $openingAccount->id,
            'ar_trade_php' => (int) $targetAccount->id,
            'ar_trade_usd' => (int) $targetAccount->id,
        ];
        $row = [
            'sheet_name' => 'Manual Entry',
            'row_number' => 1,
            'account_type' => 'A/R TRADE',
            'document_type' => 'BILLING INVOICE',
            'branch' => $branch->code,
            'branch_id' => $branch->id,
            'party_name' => $customer->name,
            'party_id' => $customer->id,
            'party_code' => $customer->code,
            'source_date' => $data['source_date'],
            'due_date' => $data['due_date'],
            'reference_no' => $reference,
            'currency' => $currency,
            'foreign_amount' => $currency === 'USD' ? $transactionAmount : 0,
            'exchange_rate' => $exchangeRate,
            'base_amount' => $baseAmount,
            'balance_type' => 'ar_trade',
            'balance_type_label' => 'A/R Trade',
            'target_account_id' => $targetAccount->id,
            'target_account_code' => $targetAccount->code,
            'target_account_name' => $targetAccount->name,
            'target_account_label' => trim($targetAccount->code.' - '.$targetAccount->name),
            'will_create_customer' => false,
            'errors' => [],
            'is_valid' => true,
        ];
        $rows = $this->assignSubledgerDocumentNumbers($companyId, [$row]);
        $preview = [
            'rows' => $rows,
            'valid_rows' => $rows,
            'valid_count' => 1,
            'skipped_count' => 0,
            'total_amount' => $baseAmount,
            'source_date_from' => $data['source_date'],
            'source_date_to' => $data['source_date'],
            'source_date_count' => 1,
            'summary' => [[
                'label' => 'A/R Trade',
                'currency' => $currency,
                'rows' => 1,
                'amount' => $baseAmount,
            ]],
            'opening_account' => $openingAccount,
        ];

        $this->periods->assertOpen($companyId, $data['source_date']);

        return $this->postPreview($preview, $companyId, $mapping, $fileHash, $filename, $userId);
    }

    private function postPreview(array $preview, $companyId, array $mapping, $fileHash, $filename, $userId)
    {
        return DB::transaction(function () use ($preview, $companyId, $mapping, $fileHash, $filename, $userId) {
            $now = now();
            $customerResult = $this->createMissingCustomers($companyId, $preview['valid_rows'], $now);
            $preview['valid_rows'] = $customerResult['rows'];
            $batchId = DB::table('opening_balance_import_batches')->insertGetId([
                'company_id' => $companyId,
                'original_filename' => $filename,
                'file_hash' => $fileHash,
                'posting_date' => $preview['source_date_to'],
                'account_mapping' => json_encode($mapping),
                'row_count' => $preview['valid_count'],
                'skipped_count' => $preview['skipped_count'],
                'total_amount' => $preview['total_amount'],
                'status' => 'posted',
                'imported_by' => $userId,
                'imported_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $salesDocumentCount = 0;
            $purchaseDocumentCount = 0;
            $journalIds = [];
            $journalNumbers = [];
            $rowsByDate = [];
            foreach ($preview['valid_rows'] as $row) {
                $rowsByDate[$row['source_date']][] = $row;
            }
            ksort($rowsByDate);

            foreach ($rowsByDate as $sourceDate => $datedRows) {
                $branchIds = array_values(array_unique(array_column($datedRows, 'branch_id')));
                $journalNo = 'OB-'.str_pad($batchId, 6, '0', STR_PAD_LEFT).'-'.date('Ymd', strtotime($sourceDate));
                $importJournalId = DB::table('opening_balance_import_journals')->insertGetId([
                    'batch_id' => $batchId,
                    'company_id' => $companyId,
                    'source_date' => $sourceDate,
                    'journal_entry_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $journalId = DB::table('journal_entries')->insertGetId([
                    'company_id' => $companyId,
                    'branch_id' => count($branchIds) === 1 ? $branchIds[0] : null,
                    'journal_no' => $journalNo,
                    'journal_date' => $sourceDate,
                    'source_module' => 'GL',
                    'source_type' => 'opening_balance_import',
                    'source_id' => $importJournalId,
                    'posting_type' => 'original',
                    'reference_no' => pathinfo($filename, PATHINFO_FILENAME),
                    'payee' => 'Opening Balance Import',
                    'memo' => 'Imported opening balances dated '.$sourceDate.' from '.$filename,
                    'status' => 'posted',
                    'created_by' => $userId,
                    'approved_by' => $userId,
                    'approved_at' => $now,
                    'posted_at' => $sourceDate.' 00:00:00',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('opening_balance_import_journals')->where('id', $importJournalId)->update([
                    'journal_entry_id' => $journalId,
                    'updated_at' => $now,
                ]);
                $journalIds[] = $journalId;
                $journalNumbers[] = $journalNo;

                foreach ($datedRows as $row) {
                $description = $this->lineDescription($row);
                $foreignAmount = $row['currency'] === 'USD' ? (float) $row['foreign_amount'] : 0;
                $isPayable = $row['balance_type'] === 'ap_trade';
                $classCode = $this->classCode($row['branch']);
                $partyFields = $isPayable
                    ? ['customer_id' => null, 'supplier_id' => $row['party_id']]
                    : ['customer_id' => $row['party_id'], 'supplier_id' => null];

                DB::table('journal_entry_lines')->insert([
                    'journal_entry_id' => $journalId,
                    'account_id' => $preview['opening_account']->id,
                    'description' => $description,
                    'tax_category' => null,
                    'tax_amount' => 0,
                    'withholding_tax_rate' => 0,
                    'withholding_tax_amount' => 0,
                    'currency' => $row['currency'],
                    'exchange_rate' => $row['exchange_rate'],
                    'foreign_debit' => $isPayable ? $foreignAmount : 0,
                    'foreign_credit' => $isPayable ? 0 : $foreignAmount,
                    'debit' => $isPayable ? $row['base_amount'] : 0,
                    'credit' => $isPayable ? 0 : $row['base_amount'],
                    'customer_id' => null,
                    'supplier_id' => null,
                    'shipment_job_id' => null,
                    'class_code' => $classCode,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('journal_entry_lines')->insert(array_merge([
                    'journal_entry_id' => $journalId,
                    'account_id' => $row['target_account_id'],
                    'description' => $description,
                    'tax_category' => null,
                    'tax_amount' => 0,
                    'withholding_tax_rate' => 0,
                    'withholding_tax_amount' => 0,
                    'currency' => $row['currency'],
                    'exchange_rate' => $row['exchange_rate'],
                    'foreign_debit' => $isPayable ? 0 : $foreignAmount,
                    'foreign_credit' => $isPayable ? $foreignAmount : 0,
                    'debit' => $isPayable ? 0 : $row['base_amount'],
                    'credit' => $isPayable ? $row['base_amount'] : 0,
                    'shipment_job_id' => null,
                    'class_code' => $classCode,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $partyFields));

                $subledger = $this->createSubledgerDocument(
                    $row,
                    $companyId,
                    $journalId,
                    $preview['opening_account'],
                    $userId,
                    $now
                );
                if ($subledger['sales_document_id']) {
                    $salesDocumentCount++;
                }
                if ($subledger['purchase_document_id']) {
                    $purchaseDocumentCount++;
                }

                DB::table('opening_balance_import_rows')->insert([
                    'batch_id' => $batchId,
                    'company_id' => $companyId,
                    'file_hash' => $fileHash,
                    'sheet_name' => $row['sheet_name'],
                    'row_number' => $row['row_number'],
                    'balance_type' => $row['balance_type'],
                    'branch_name' => $row['branch'],
                    'party_name' => $row['party_name'],
                    'source_date' => $row['source_date'],
                    'reference_no' => $row['reference_no'],
                    'currency' => $row['currency'],
                    'foreign_amount' => $row['foreign_amount'],
                    'exchange_rate' => $row['exchange_rate'],
                    'base_amount' => $row['base_amount'],
                    'journal_entry_id' => $journalId,
                    'subledger_document_no' => $row['subledger_document_no'],
                    'sales_document_id' => $subledger['sales_document_id'],
                    'purchase_document_id' => $subledger['purchase_document_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                }
            }

            DB::table('opening_balance_import_batches')->where('id', $batchId)->update([
                'journal_entry_id' => $journalIds[0],
                'updated_at' => $now,
            ]);

            $this->activity->log('imported', 'opening_balance_import', $batchId, [], [
                'filename' => $filename,
                'journal_nos' => $journalNumbers,
                'source_date_from' => $preview['source_date_from'],
                'source_date_to' => $preview['source_date_to'],
                'rows_imported' => $preview['valid_count'],
                'rows_skipped' => $preview['skipped_count'],
                'invoices_created' => $salesDocumentCount,
                'vendor_bills_created' => $purchaseDocumentCount,
                'customers_created' => $customerResult['created_count'],
                'total_debit' => $preview['total_amount'],
                'total_credit' => $preview['total_amount'],
            ], $companyId);

            return [
                'batch_id' => $batchId,
                'journal_id' => $journalIds[0],
                'journal_no' => $journalNumbers[0],
                'journal_count' => count($journalIds),
                'journal_nos' => $journalNumbers,
                'source_date_from' => $preview['source_date_from'],
                'source_date_to' => $preview['source_date_to'],
                'rows_imported' => $preview['valid_count'],
                'rows_skipped' => $preview['skipped_count'],
                'invoices_created' => $salesDocumentCount,
                'vendor_bills_created' => $purchaseDocumentCount,
                'customers_created' => $customerResult['created_count'],
                'total_amount' => $preview['total_amount'],
            ];
        });
    }

    private function createSubledgerDocument(array $row, $companyId, $journalId, $openingAccount, $userId, $now)
    {
        $transactionAmount = $row['currency'] === 'USD'
            ? (float) $row['foreign_amount']
            : (float) $row['base_amount'];
        $memo = $this->lineDescription($row).' (imported opening balance)';
        $classCode = $this->classCode($row['branch']);
        $postingTimestamp = $row['source_date'].' 00:00:00';

        if ($row['balance_type'] === 'ap_trade') {
            $purchaseDocumentId = DB::table('purchase_documents')->insertGetId([
                'company_id' => $companyId,
                'branch_id' => $row['branch_id'],
                'supplier_id' => $row['party_id'],
                'document_type' => 'vendor_bill',
                'transaction_type' => 'Opening Balance',
                'ap_account' => $row['target_account_name'],
                'opening_balance_control_account_id' => $row['target_account_id'],
                'bill_received' => true,
                'shipment_job_id' => null,
                'document_no' => $row['subledger_document_no'],
                'reference_no' => $row['reference_no'],
                'document_date' => $row['source_date'],
                'due_date' => $row['due_date'] ?? $row['source_date'],
                'terms' => 'Opening Balance',
                'currency' => $row['currency'],
                'exchange_rate' => $row['exchange_rate'],
                'usd_to_php_rate' => $row['exchange_rate'],
                'gross_amount' => $transactionAmount,
                'php_amount' => $row['base_amount'],
                'subtotal' => $transactionAmount,
                'vatable_amount' => 0,
                'zero_rated_amount' => 0,
                'vat_exempt_amount' => $transactionAmount,
                'tax_total' => 0,
                'withholding_tax_rate' => 0,
                'withholding_tax_amount' => 0,
                'grand_total' => $transactionAmount,
                'net_amount' => $transactionAmount,
                'paid_total' => 0,
                'status' => 'posted',
                'approval_status' => 'Approved',
                'remarks' => $memo,
                'memo' => $memo,
                'journal_entry_id' => $journalId,
                'posting_status' => 'posted',
                'posted_by' => $userId,
                'accounting_posted_at' => $postingTimestamp,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('vendor_bill_lines')->insert([
                'purchase_document_id' => $purchaseDocumentId,
                'account_id' => $openingAccount->id,
                'account_name' => trim($openingAccount->code.' - '.$openingAccount->name),
                'tax_category' => 'EXEMPT',
                'amount' => $transactionAmount,
                'memo' => $memo,
                'customer_job_id' => null,
                'is_billable' => false,
                'class_code' => $classCode,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'sales_document_id' => null,
                'purchase_document_id' => $purchaseDocumentId,
            ];
        }

        $isReimbursable = $row['balance_type'] === 'ar_reimbursable';
        $arAccountCode = $isReimbursable ? 'AR_REIMBURSABLE' : 'AR_TRADE_BI';
        $salesDocumentId = DB::table('sales_documents')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $row['branch_id'],
            'customer_id' => $row['party_id'],
            'customer_id_text' => $row['party_code'],
            'document_type' => 'billing_invoice',
            'document_no' => $row['subledger_document_no'],
            'transaction_no' => $row['reference_no'],
            'soa_no' => $isReimbursable ? $row['reference_no'] : null,
            'document_date' => $row['source_date'],
            'due_date' => $row['due_date'] ?? $row['source_date'],
            'credit_term_days' => 0,
            'vat_classification' => 'exempt',
            'class_code' => $classCode,
            'ar_account_code' => $arAccountCode,
            'opening_balance_control_account_id' => $row['target_account_id'],
            'uses_pre_printed_form' => false,
            'currency' => $row['currency'],
            'exchange_rate' => $row['exchange_rate'],
            'subtotal' => $isReimbursable ? 0 : $transactionAmount,
            'vatable_amount' => 0,
            'zero_rated_amount' => 0,
            'vat_exempt_amount' => $transactionAmount,
            'discount_total' => 0,
            'tax_total' => 0,
            'withholding_tax_rate' => 0,
            'withholding_tax_amount' => 0,
            'freight_total' => $isReimbursable ? $transactionAmount : 0,
            'grand_total' => $transactionAmount,
            'invoice_amount' => $isReimbursable ? 0 : $transactionAmount,
            'paid_total' => 0,
            'total_payment' => 0,
            'balance_due' => $transactionAmount,
            'status' => 'posted',
            'remarks' => $memo,
            'memo' => $memo,
            'journal_entry_id' => $journalId,
            'posting_status' => 'posted',
            'posted_by' => $userId,
            'accounting_posted_at' => $postingTimestamp,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('sales_document_lines')->insert([
            'sales_document_id' => $salesDocumentId,
            'item_name' => $isReimbursable ? 'Opening A/R Reimbursable' : 'Opening A/R Trade',
            'description' => $memo,
            'quantity' => 1,
            'unit_price' => $transactionAmount,
            'discount_amount' => 0,
            'tax_code_id' => null,
            'line_total' => $transactionAmount,
            'class_code' => $classCode,
            'tax_category' => 'EXEMPT',
            'ar_account_code' => $arAccountCode,
            'withholding_tax_rate' => 0,
            'withholding_tax_amount' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'sales_document_id' => $salesDocumentId,
            'purchase_document_id' => null,
        ];
    }

    private function createMissingCustomers($companyId, array $rows, $now)
    {
        $customers = [];
        foreach (DB::table('customers')->where('company_id', $companyId)->get(['id', 'code', 'name']) as $customer) {
            $customers[$this->normalize($customer->name)] = $customer;
        }

        $createdCount = 0;
        foreach ($rows as &$row) {
            if ($row['balance_type'] === 'ap_trade' || !empty($row['party_id'])) {
                continue;
            }

            $key = $this->normalize($row['party_name']);
            $customer = $customers[$key] ?? null;
            if (!$customer) {
                $code = $this->uniqueImportedCustomerCode($companyId, $row['party_name']);
                $customerId = DB::table('customers')->insertGetId([
                    'company_id' => $companyId,
                    'code' => $code,
                    'customer_id' => $code,
                    'name' => trim($row['party_name']),
                    'tin' => null,
                    'tax_classification' => 'VAT-exempt',
                    'email' => null,
                    'phone' => null,
                    'billing_address' => null,
                    'contact_person' => null,
                    'credit_limit' => 0,
                    'credit_status' => 'active',
                    'payment_terms' => 'Opening Balance',
                    'credit_term_days' => 0,
                    'currency' => $row['currency'],
                    'default_class_code' => $this->classCode($row['branch']),
                    'default_ar_account_code' => $row['balance_type'] === 'ar_reimbursable'
                        ? 'AR_REIMBURSABLE'
                        : 'AR_TRADE_BI',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $customer = (object) [
                    'id' => $customerId,
                    'code' => $code,
                    'name' => trim($row['party_name']),
                ];
                $customers[$key] = $customer;
                $createdCount++;
            }

            $row['party_id'] = $customer->id;
            $row['party_code'] = $customer->code;
        }
        unset($row);

        return ['rows' => $rows, 'created_count' => $createdCount];
    }

    private function uniqueImportedCustomerCode($companyId, $name)
    {
        $slug = trim(preg_replace('/[^A-Z0-9]+/', '-', strtoupper(trim((string) $name))), '-');
        $base = substr('OB-'.($slug !== '' ? $slug : 'CUSTOMER'), 0, 50);
        $candidate = $base;
        $counter = 2;

        while (DB::table('customers')
            ->where('company_id', $companyId)
            ->where(function ($query) use ($candidate) {
                $query->where('code', $candidate)->orWhere('customer_id', $candidate);
            })
            ->exists()) {
            $suffix = '-'.$counter++;
            $candidate = substr($base, 0, 50 - strlen($suffix)).$suffix;
        }

        return $candidate;
    }

    private function assignSubledgerDocumentNumbers($companyId, array $rows)
    {
        $used = [
            'sales_documents' => [],
            'purchase_documents' => [],
        ];
        foreach (array_keys($used) as $table) {
            foreach (DB::table($table)->where('company_id', $companyId)->pluck('document_no') as $documentNo) {
                $used[$table][strtoupper(trim((string) $documentNo))] = true;
            }
        }

        foreach ($rows as &$row) {
            $row['subledger_document_no'] = null;
            if (!$row['is_valid']) {
                continue;
            }

            $table = $row['balance_type'] === 'ap_trade' ? 'purchase_documents' : 'sales_documents';
            $base = trim((string) $row['reference_no']);
            $candidate = $base;
            if (isset($used[$table][strtoupper($candidate)])) {
                $suffix = '-OB-'.preg_replace('/[^A-Z0-9]+/', '', strtoupper($row['sheet_name'])).'-'.$row['row_number'];
                $candidate = substr($base, 0, max(1, 255 - strlen($suffix))).$suffix;
            }
            $counter = 2;
            $unique = $candidate;
            while (isset($used[$table][strtoupper($unique)])) {
                $numberSuffix = '-'.$counter++;
                $unique = substr($candidate, 0, max(1, 255 - strlen($numberSuffix))).$numberSuffix;
            }

            $row['subledger_document_no'] = $unique;
            $used[$table][strtoupper($unique)] = true;
        }
        unset($row);

        return $rows;
    }

    private function mappedAccounts($companyId, array $mapping)
    {
        $keys = [
            'opening_balance',
            'ar_trade_php', 'ar_trade_usd',
            'ar_reimbursable_php', 'ar_reimbursable_usd',
            'ap_trade_php', 'ap_trade_usd',
        ];
        $ids = [];
        foreach ($keys as $key) {
            if (!empty($mapping[$key])) {
                $ids[] = (int) $mapping[$key];
            }
        }

        $accountsById = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('id', array_unique($ids))
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        $accounts = [];
        foreach ($keys as $key) {
            $id = isset($mapping[$key]) ? (int) $mapping[$key] : 0;
            $accounts[$key] = $id ? $accountsById->get($id) : null;
        }

        if (!$accounts['opening_balance']) {
            throw ValidationException::withMessages([
                'opening_balance_account_id' => 'Select an active Opening Balance Equity account.',
            ]);
        }

        return $accounts;
    }

    private function branchIndex($companyId)
    {
        $index = [];
        foreach (DB::table('branches')->where('company_id', $companyId)->where('is_active', true)->get() as $branch) {
            $index[$this->normalize($branch->code)] = $branch;
            $index[$this->normalize($branch->name)] = $branch;
            if (strtoupper($branch->code) === 'MAIN') {
                $index['MANILA'] = $branch;
            }
        }

        return $index;
    }

    private function partyIndex($table, $companyId)
    {
        $index = [];
        foreach (DB::table($table)->where('company_id', $companyId)->where('is_active', true)->get(['id', 'code', 'name']) as $party) {
            $index[$this->normalize($party->name)] = $party;
        }

        return $index;
    }

    private function alreadyImportedRows($companyId, $fileHash)
    {
        $rows = DB::table('opening_balance_import_rows')
            ->where('company_id', $companyId)
            ->where('file_hash', $fileHash)
            ->get(['sheet_name', 'row_number']);
        $index = [];
        foreach ($rows as $row) {
            $index[$row->sheet_name.'|'.$row->row_number] = true;
        }

        return $index;
    }

    private function balanceType($accountType, $documentType)
    {
        $account = $this->normalize($accountType);
        $document = $this->normalize($documentType);
        if (strpos($account, 'REIMBURS') !== false) {
            return 'ar_reimbursable';
        }
        if (strpos($account, 'APTRADE') !== false || strpos($account, 'ACCOUNTSPAYABLE') !== false) {
            return 'ap_trade';
        }
        if (strpos($account, 'ARTRADE') !== false || strpos($document, 'BILLINGINVOICE') !== false) {
            return 'ar_trade';
        }

        return null;
    }

    private function balanceTypeLabel($type)
    {
        return [
            'ar_trade' => 'A/R Trade',
            'ar_reimbursable' => 'A/R Reimbursable',
            'ap_trade' => 'A/P Trade',
        ][$type] ?? 'Unknown';
    }

    private function lineDescription(array $row)
    {
        return trim($row['balance_type_label'].' opening balance '.$row['reference_no']
            .' | '.$row['party_name'].' | '.$row['source_date']);
    }

    private function classCode($branch)
    {
        $code = strtoupper(trim((string) $branch));
        return [
            'MAIN' => 'MANILA', 'MNL' => 'MANILA',
            'PAM' => 'PAMPANGA', 'CEB' => 'CEBU',
        ][$code] ?? $code;
    }

    private function normalize($value)
    {
        return preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim((string) $value)));
    }
}
