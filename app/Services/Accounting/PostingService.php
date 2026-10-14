<?php

namespace App\Services\Accounting;

use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PostingService
{
    private $periods;
    private $activity;

    private $defaultAccounts = [
        'cash' => ['1000', 'Cash on Hand', 'Assets', 'Current Asset'],
        'bank' => ['1010', 'Bank - Operating Account', 'Assets', 'Current Asset'],
        'accounts_receivable' => ['1200', 'Accounts Receivable', 'Assets', 'Current Asset'],
        'ar_trade_billing' => ['1210', 'A/Trade-Billing Invoice', 'Assets', 'Current Asset'],
        'ar_reimbursable' => ['1220', 'A/R Reimbursable Charges', 'Assets', 'Current Asset'],
        'ar_soa_a' => ['1230', 'A/R Trade-SOA A', 'Assets', 'Current Asset'],
        'ar_debit_note' => ['1240', 'A/R Trade-Debit Note', 'Assets', 'Current Asset'],
        'input_vat' => ['1250', 'Input VAT', 'Assets', 'Current Asset'],
        'cwt_receivable' => ['1260', 'Creditable Withholding Tax Receivable', 'Assets', 'Current Asset'],
        'accounts_payable' => ['2100', 'Accounts Payable', 'Liabilities', 'Current Liability'],
        'output_vat' => ['2200', 'Output VAT Payable', 'Liabilities', 'Current Liability'],
        'withholding_tax_payable' => ['2300', 'Withholding Tax Payable', 'Liabilities', 'Current Liability'],
        'customer_deposits' => ['2400', 'Customer Deposits', 'Liabilities', 'Current Liability'],
        'service_revenue' => ['4000', 'Service Revenue', 'Revenue', 'Revenue'],
        'reimbursable_recovery' => ['4010', 'Reimbursable Cost Recovery', 'Revenue', 'Revenue'],
        'fx_gain' => ['4100', 'Foreign Exchange Gain', 'Revenue', 'Other Revenue'],
        'cost_of_services' => ['5000', 'Cost of Services', 'Expenses', 'Expense'],
        'operating_expense' => ['5100', 'Operating Expenses', 'Expenses', 'Expense'],
        'bank_charges' => ['5200', 'Bank Charges', 'Expenses', 'Expense'],
        'fx_loss' => ['5300', 'Foreign Exchange Loss', 'Expenses', 'Expense'],
    ];

    public function __construct(PeriodLockService $periods, ActivityLogger $activity)
    {
        $this->periods = $periods;
        $this->activity = $activity;
    }

    public function postSalesDocument($documentId)
    {
        return DB::transaction(function () use ($documentId) {
            $document = DB::table('sales_documents')->where('id', $documentId)->lockForUpdate()->first();
            $this->assertDocumentCanPost($document, 'sales document');

            $existing = $this->existingJournal('sales_document', $document->id, $document->company_id);
            if ($existing) {
                return $existing;
            }

            $this->periods->assertOpen($document->company_id, $document->document_date);
            $currency = strtoupper($document->currency ?: 'PHP');
            $rate = $this->rate($currency, $document->exchange_rate ?? 1);
            $tax = max(0, (float) $document->tax_total);
            $reimbursement = max(0, (float) $document->freight_total);
            $total = max(0, (float) $document->grand_total);
            $billing = max(0, $total - $reimbursement);
            $serviceRevenue = max(0, $billing - $tax);
            $lines = [];

            if ($billing > 0) {
                $lines[] = $this->line(
                    $this->salesReceivableAccount($document),
                    'Accounts receivable - '.$document->document_no,
                    $billing,
                    0,
                    $currency,
                    $rate,
                    $document->customer_id,
                    null,
                    $document->job_shipment_id,
                    $document->class_code
                );
            }
            if ($reimbursement > 0) {
                $lines[] = $this->line(
                    $this->reimbursableReceivableAccount($document->company_id, $currency),
                    'Reimbursable receivable - '.$document->document_no,
                    $reimbursement,
                    0,
                    $currency,
                    $rate,
                    $document->customer_id,
                    null,
                    $document->job_shipment_id,
                    $document->class_code
                );
            }
            if ($serviceRevenue > 0) {
                $isSoaA = in_array(strtolower((string) $document->document_type), ['soa_a', 'statement_of_account_a'], true)
                    || ($document->template_code ?? '') === 'SOA_A';
                if ($isSoaA) {
                    $incomeAmounts = ['Service Income-SOA A' => $serviceRevenue];
                } elseif (strtolower((string) $document->document_type) === 'debit_note') {
                    $incomeAmounts = ['Service Income-DN' => $serviceRevenue];
                } else {
                    $remaining = round($serviceRevenue, 2);
                    $incomeAmounts = [];
                    foreach (['Service Income- Vatable' => 'vatable_amount',
                        'Service Income-Zero rated' => 'zero_rated_amount',
                        'Service Income-Vat exempt' => 'vat_exempt_amount'] as $accountName => $column) {
                        $amount = min($remaining, max(0, round((float) ($document->$column ?? 0), 2)));
                        if ($amount > 0) {
                            $incomeAmounts[$accountName] = $amount;
                            $remaining = round($remaining - $amount, 2);
                        }
                    }
                    if ($remaining > 0) {
                        $classification = strtolower((string) ($document->vat_classification ?? ''));
                        $accountName = $tax > 0 || strpos($classification, '12%') !== false
                            ? 'Service Income- Vatable'
                            : (strpos($classification, 'zero') !== false
                                ? 'Service Income-Zero rated' : 'Service Income-Vat exempt');
                        $incomeAmounts[$accountName] = ($incomeAmounts[$accountName] ?? 0) + $remaining;
                    }
                }

                foreach ($incomeAmounts as $accountName => $incomeAmount) {
                    $lines[] = $this->line(
                        $this->salesIncomeAccount($document->company_id, $accountName),
                        $accountName.' - '.$document->document_no,
                        0,
                        $incomeAmount,
                        $currency,
                        $rate,
                        $document->customer_id,
                        null,
                        $document->job_shipment_id,
                        $document->class_code
                    );
                }
            }
            if ($reimbursement > 0) {
                $lines[] = $this->line(
                    $this->reimbursableChargesAccount($document->company_id),
                    'Reimbursable cost recovery - '.$document->document_no,
                    0,
                    $reimbursement,
                    $currency,
                    $rate,
                    $document->customer_id,
                    null,
                    $document->job_shipment_id,
                    $document->class_code
                );
            }
            if ($tax > 0) {
                $lines[] = $this->line(
                    $this->controlAccount($document->company_id, 'output_vat'),
                    'Output VAT - '.$document->document_no,
                    0,
                    $tax,
                    $currency,
                    $rate,
                    $document->customer_id,
                    null,
                    $document->job_shipment_id,
                    $document->class_code
                );
            }

            $journal = $this->createJournal(
                $document->company_id,
                $document->branch_id,
                $document->document_date,
                'AR',
                'sales_document',
                $document->id,
                $document->document_no,
                $document->job_shipment_id,
                'Automatic posting for '.$document->document_type.' '.$document->document_no,
                $lines
            );

            $this->markPosted('sales_documents', $document->id, $journal->id, [
                'status' => (float) $document->paid_total >= $total && $total > 0 ? 'paid' : 'posted',
            ]);

            return $journal;
        });
    }

    public function postPurchaseDocument($documentId)
    {
        return DB::transaction(function () use ($documentId) {
            $document = DB::table('purchase_documents')->where('id', $documentId)->lockForUpdate()->first();
            $this->assertDocumentCanPost($document, 'purchase document');

            $existing = $this->existingJournal('purchase_document', $document->id, $document->company_id);
            if ($existing) {
                return $existing;
            }

            $this->periods->assertOpen($document->company_id, $document->document_date);
            $currency = strtoupper($document->currency ?: 'PHP');
            $rate = $this->rate($currency, $document->exchange_rate ?? 1);
            $gross = max(0, (float) ($document->gross_amount ?: $document->subtotal));
            $inputVat = max(0, (float) $document->tax_total);
            $withholding = max(0, (float) $document->withholding_tax_amount);
            $payable = max(0, (float) $document->grand_total);
            $expense = max(0, $gross - $inputVat);
            $lines = [
                $this->line(
                    $this->purchaseExpenseAccount($document),
                    'Shipment/vendor cost - '.$document->document_no,
                    $expense,
                    0,
                    $currency,
                    $rate,
                    null,
                    $document->supplier_id,
                    $document->shipment_job_id
                ),
            ];

            if ($inputVat > 0) {
                $lines[] = $this->line(
                    $this->controlAccount($document->company_id, 'input_vat'),
                    'Input VAT - '.$document->document_no,
                    $inputVat,
                    0,
                    $currency,
                    $rate,
                    null,
                    $document->supplier_id,
                    $document->shipment_job_id
                );
            }
            if ($withholding > 0) {
                $lines[] = $this->line(
                    $this->controlAccount($document->company_id, 'withholding_tax_payable'),
                    'Expanded withholding tax - '.$document->document_no,
                    0,
                    $withholding,
                    $currency,
                    $rate,
                    null,
                    $document->supplier_id,
                    $document->shipment_job_id
                );
            }
            $lines[] = $this->line(
                $this->purchasePayableAccount($document),
                'Accounts payable - '.$document->document_no,
                0,
                $payable,
                $currency,
                $rate,
                null,
                $document->supplier_id,
                $document->shipment_job_id
            );

            $journal = $this->createJournal(
                $document->company_id,
                $document->branch_id,
                $document->document_date,
                'AP',
                'purchase_document',
                $document->id,
                $document->document_no,
                $document->shipment_job_id,
                'Automatic posting for vendor bill '.$document->document_no,
                $lines
            );

            $this->markPosted('purchase_documents', $document->id, $journal->id, [
                'status' => (float) $document->paid_total >= $payable && $payable > 0 ? 'paid' : 'posted',
                'approval_status' => 'Approved',
            ]);

            return $journal;
        });
    }

    public function postReceipt($receiptId)
    {
        return DB::transaction(function () use ($receiptId) {
            $receipt = DB::table('receipts')->where('id', $receiptId)->lockForUpdate()->first();
            $this->assertDocumentCanPost($receipt, 'receipt');
            $existing = $this->existingJournal('receipt', $receipt->id, $receipt->company_id);
            if ($existing) {
                return $existing;
            }

            $this->periods->assertOpen($receipt->company_id, $receipt->receipt_date);
            $currency = strtoupper($receipt->currency ?: 'PHP');
            $rate = $this->rate($currency, $receipt->exchange_rate ?? 1);
            $amount = max(0, (float) $receipt->amount);
            $cwtAmount = max(0, (float) ($receipt->cwt_amount ?? 0));
            $applications = DB::table('receipt_applications')
                ->join('sales_documents', 'sales_documents.id', '=', 'receipt_applications.sales_document_id')
                ->where('receipt_applications.receipt_id', $receipt->id)
                ->select('receipt_applications.amount_applied', 'receipt_applications.component', 'sales_documents.*')
                ->get();
            $lines = [
                $this->line(
                    $this->cashOrBankAccount($receipt->company_id, $receipt->payment_method, null),
                    'Collection '.$receipt->receipt_no,
                    $amount,
                    0,
                    $currency,
                    $rate,
                    $receipt->customer_id,
                    null,
                    $receipt->job_shipment_id,
                    $receipt->class_code
                ),
            ];
            if ($cwtAmount > 0) {
                $lines[] = $this->line(
                    $this->controlAccount($receipt->company_id, 'cwt_receivable'),
                    'Creditable withholding tax - '.$receipt->receipt_no,
                    $cwtAmount,
                    0,
                    $currency,
                    $rate,
                    $receipt->customer_id,
                    null,
                    $receipt->job_shipment_id,
                    $receipt->class_code
                );
            }
            $applied = 0.0;

            foreach ($applications as $application) {
                $applicationAmount = max(0, (float) $application->amount_applied);
                $applied += $applicationAmount;
                $lines[] = $this->line(
                    $application->component === 'soa'
                        && !in_array(strtolower((string) $application->document_type), ['soa', 'soa_a', 'statement_of_account_a'], true)
                        ? ($this->openingBalanceControlAccount($application)
                            ?: $this->reimbursableReceivableAccount($receipt->company_id, $currency))
                        : $this->salesReceivableAccount($application),
                    'Applied to '.$application->document_no,
                    0,
                    $applicationAmount,
                    $currency,
                    $rate,
                    $receipt->customer_id,
                    null,
                    $application->job_shipment_id ?: $receipt->job_shipment_id,
                    $application->class_code ?: $receipt->class_code
                );
            }

            $settlement = $amount + $cwtAmount;
            if ($settlement > $applied) {
                $lines[] = $this->line(
                    $this->controlAccount($receipt->company_id, 'customer_deposits'),
                    'Unapplied customer receipt '.$receipt->receipt_no,
                    0,
                    $settlement - $applied,
                    $currency,
                    $rate,
                    $receipt->customer_id,
                    null,
                    $receipt->job_shipment_id,
                    $receipt->class_code
                );
            }

            $journal = $this->createJournal(
                $receipt->company_id,
                null,
                $receipt->receipt_date,
                'AR',
                'receipt',
                $receipt->id,
                $receipt->receipt_no,
                $receipt->job_shipment_id,
                'Automatic posting for collection '.$receipt->receipt_no,
                $lines
            );

            $this->markPosted('receipts', $receipt->id, $journal->id, ['status' => 'posted']);
            $this->recordBankMovement($receipt->company_id, 'receipt', $receipt->id, $journal->id, $receipt->receipt_date, $receipt->receipt_no, $receipt->payment_method, $receipt->bank_account_id ?? null, $currency, $rate, $amount, 0, $receipt->job_shipment_id);

            return $journal;
        });
    }

    public function postSupplierPayment($paymentId)
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = DB::table('payments')->where('id', $paymentId)->lockForUpdate()->first();
            $this->assertDocumentCanPost($payment, 'supplier payment');
            if (strtolower((string) $payment->approval_status) !== 'released') {
                throw ValidationException::withMessages([
                    'approval_status' => 'A supplier payment can be posted only after controlled release.',
                ]);
            }
            $existing = $this->existingJournal('payment', $payment->id, $payment->company_id);
            if ($existing) {
                return $existing;
            }

            $this->periods->assertOpen($payment->company_id, $payment->payment_date);
            $currency = strtoupper($payment->currency ?: 'PHP');
            $rate = $this->rate($currency, $payment->exchange_rate ?? 1);
            $amount = max(0, (float) $payment->amount);
            $bill = DB::table('purchase_documents')
                ->where('company_id', $payment->company_id)
                ->where('document_no', $payment->bill_no)
                ->first();
            $lines = [
                $this->line(
                    $bill ? $this->purchasePayableAccount($bill) : $this->controlAccount($payment->company_id, 'accounts_payable'),
                    'Settlement of '.$payment->bill_no,
                    $amount,
                    0,
                    $currency,
                    $rate,
                    null,
                    $payment->supplier_id,
                    $payment->shipment_job_id
                ),
                $this->line(
                    $this->cashOrBankAccount($payment->company_id, $payment->payment_method, $payment->bank_account_id ?? $payment->bank_account),
                    'Supplier payment '.$payment->payment_no,
                    0,
                    $amount,
                    $currency,
                    $rate,
                    null,
                    $payment->supplier_id,
                    $payment->shipment_job_id
                ),
            ];

            $journal = $this->createJournal(
                $payment->company_id,
                null,
                $payment->payment_date,
                'AP',
                'payment',
                $payment->id,
                $payment->payment_no,
                $payment->shipment_job_id,
                'Automatic posting for supplier payment '.$payment->payment_no,
                $lines
            );

            $this->markPosted('payments', $payment->id, $journal->id, ['status' => 'released']);
            $this->recordBankMovement($payment->company_id, 'payment', $payment->id, $journal->id, $payment->payment_date, $payment->payment_no, $payment->payment_method, $payment->bank_account_id ?? $payment->bank_account, $currency, $rate, 0, $amount, $payment->shipment_job_id);

            return $journal;
        });
    }

    public function reverseImportedReceivable($row, $document, $reason)
    {
        $original = DB::table('journal_entries')->where('id', $row->journal_entry_id)
            ->where('company_id', $row->company_id)->where('status', 'posted')->lockForUpdate()->first();
        if (!$original || $original->source_type !== 'opening_balance_import') {
            throw ValidationException::withMessages(['balance' => 'The opening-balance journal is not available for reversal.']);
        }
        $existing = DB::table('journal_entries')->where('company_id', $row->company_id)
            ->where('source_type', 'opening_balance_row')->where('source_id', $row->id)->exists();
        if ($existing) {
            throw ValidationException::withMessages(['balance' => 'This imported balance has already been reversed.']);
        }
        $controlLines = DB::table('journal_entry_lines')->where('journal_entry_id', $original->id)
            ->where('account_id', $document->opening_balance_control_account_id)
            ->where('customer_id', $document->customer_id)->where('currency', $row->currency)
            ->where(function ($query) use ($row) {
                $query->where('debit', $row->base_amount)->orWhere('credit', $row->base_amount);
            })->get()
            ->filter(function ($line) use ($row) {
                return strpos($line->description, ' opening balance '.$row->reference_no.' | '.$row->party_name.' | '.$row->source_date) !== false;
            });
        if ($controlLines->count() !== 1) {
            throw ValidationException::withMessages(['balance' => 'The exact imported balance journal lines could not be identified.']);
        }
        $control = $controlLines->first();
        $offsets = DB::table('journal_entry_lines')->where('journal_entry_id', $original->id)
            ->where('description', $control->description)
            ->where((float) $control->debit > 0 ? 'credit' : 'debit', $row->base_amount)
            ->where('currency', $row->currency)->whereNull('customer_id')->whereNull('supplier_id')->get();
        if ($offsets->count() !== 1) {
            throw ValidationException::withMessages(['balance' => 'The opening-balance offset could not be identified.']);
        }
        if ((float) $control->credit > 0) {
            $correction = DB::table('journal_entries')->where('company_id', $row->company_id)
                ->where('source_type', 'opening_balance_import_correction')->where('source_id', $row->id)
                ->where('status', 'posted')->first();
            $correctionLines = $correction ? DB::table('journal_entry_lines')->where('journal_entry_id', $correction->id)->get() : collect();
            $offset = $offsets->first();
            if ($correctionLines->count() !== 2
                || abs($correctionLines->where('account_id', $control->account_id)->sum('debit') - 2 * (float) $row->base_amount) > 0.0001
                || abs($correctionLines->where('account_id', $offset->account_id)->sum('credit') - 2 * (float) $row->base_amount) > 0.0001) {
                throw ValidationException::withMessages(['balance' => 'This historical import requires a verified correcting journal before deletion.']);
            }
            // The correction offsets the wrong original and restores a normal A/R debit.
            foreach ([$control, $offset] as $line) {
                $debit = $line->debit; $line->debit = $line->credit; $line->credit = $debit;
                $foreignDebit = $line->foreign_debit; $line->foreign_debit = $line->foreign_credit; $line->foreign_credit = $foreignDebit;
            }
        }
        $this->periods->assertOpen($row->company_id, date('Y-m-d'));
        $lines = [];
        foreach ([$control, $offsets->first()] as $line) {
            $lines[] = [
                'account_id' => $line->account_id, 'description' => 'Reversal: '.$line->description,
                'currency' => $line->currency, 'exchange_rate' => $line->exchange_rate,
                'foreign_debit' => $line->foreign_credit, 'foreign_credit' => $line->foreign_debit,
                'debit' => $line->credit, 'credit' => $line->debit,
                'customer_id' => $line->customer_id, 'supplier_id' => $line->supplier_id,
                'shipment_job_id' => $line->shipment_job_id, 'class_code' => $line->class_code,
            ];
        }
        return $this->createJournal($row->company_id, $document->branch_id, date('Y-m-d'), 'GL',
            'opening_balance_row', $row->id, $document->document_no, null,
            'Remove imported A/R balance: '.$reason, $lines, 'reversal', $original->id, $reason);
    }

    public function reverseSource($sourceType, $sourceId, $reason)
    {
        return DB::transaction(function () use ($sourceType, $sourceId, $reason) {
            $original = DB::table('journal_entries')
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('posting_type', 'original')
                ->where('status', 'posted')
                ->lockForUpdate()
                ->first();

            if (!$original) {
                return null;
            }

            $existing = DB::table('journal_entries')
                ->where('company_id', $original->company_id)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('posting_type', 'reversal')
                ->first();
            if ($existing) {
                return $existing;
            }

            $this->periods->assertOpen($original->company_id, date('Y-m-d'));
            $originalLines = DB::table('journal_entry_lines')->where('journal_entry_id', $original->id)->get();
            $lines = [];
            foreach ($originalLines as $line) {
                $lines[] = [
                    'account_id' => $line->account_id,
                    'description' => 'Reversal: '.$line->description,
                    'currency' => $line->currency,
                    'exchange_rate' => $line->exchange_rate,
                    'foreign_debit' => $line->foreign_credit,
                    'foreign_credit' => $line->foreign_debit,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'customer_id' => $line->customer_id,
                    'supplier_id' => $line->supplier_id,
                    'shipment_job_id' => $line->shipment_job_id,
                    'class_code' => $line->class_code,
                ];
            }

            $reversal = $this->createJournal(
                $original->company_id,
                $original->branch_id,
                date('Y-m-d'),
                $original->source_module,
                $sourceType,
                $sourceId,
                $original->reference_no,
                $original->shipment_job_id,
                'Reversal of '.$original->journal_no.': '.$reason,
                $lines,
                'reversal',
                $original->id,
                $reason
            );

            DB::table('journal_entries')->where('id', $original->id)->update([
                'status' => 'reversed',
                'updated_at' => now(),
            ]);

            $this->reverseBankMovement($sourceType, $sourceId, $reversal->id, $reason);

            return $reversal;
        });
    }

    private function createJournal($companyId, $branchId, $date, $module, $sourceType, $sourceId, $reference, $jobId, $memo, array $lines, $postingType = 'original', $reversedEntryId = null, $reversalReason = null)
    {
        $this->assertBalanced($lines);
        $journalNo = $this->nextJournalNumber($companyId, $sourceType, $sourceId);
        $hash = hash('sha256', json_encode([$companyId, $sourceType, $sourceId, $postingType, $date, $lines]));
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'journal_no' => $journalNo,
            'journal_date' => $date,
            'shipment_job_id' => $jobId,
            'source_module' => $module,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'posting_type' => $postingType,
            'reversed_entry_id' => $reversedEntryId,
            'reversal_reason' => $reversalReason,
            'posting_hash' => $hash,
            'reference_no' => $reference,
            'memo' => $memo,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($lines as $line) {
            $line['journal_entry_id'] = $journalId;
            $line['created_at'] = now();
            $line['updated_at'] = now();
            DB::table('journal_entry_lines')->insert($line);
        }

        $this->activity->log('posted', 'journal_entry', $journalId, [], [
            'journal_no' => $journalNo,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'posting_type' => $postingType,
        ], $companyId);

        return DB::table('journal_entries')->where('id', $journalId)->first();
    }

    private function line($accountId, $description, $debit, $credit, $currency, $rate, $customerId = null, $supplierId = null, $jobId = null, $classCode = null)
    {
        return [
            'account_id' => $accountId,
            'description' => $description,
            'currency' => $currency,
            'exchange_rate' => $rate,
            'foreign_debit' => round($debit, 4),
            'foreign_credit' => round($credit, 4),
            'debit' => round($debit * $rate, 4),
            'credit' => round($credit * $rate, 4),
            'customer_id' => $customerId,
            'supplier_id' => $supplierId,
            'shipment_job_id' => $jobId,
            'class_code' => $classCode,
        ];
    }

    private function assertBalanced(array $lines)
    {
        $debits = round(array_sum(array_column($lines, 'debit')), 2);
        $credits = round(array_sum(array_column($lines, 'credit')), 2);
        if (empty($lines) || $debits <= 0 || abs($debits - $credits) > 0.01) {
            throw ValidationException::withMessages([
                'posting' => 'Posting failed because the journal is not balanced. Debits: '.number_format($debits, 2).'; credits: '.number_format($credits, 2).'.',
            ]);
        }
    }

    private function assertDocumentCanPost($document, $label)
    {
        if (!$document) {
            throw ValidationException::withMessages(['posting' => ucfirst($label).' was not found.']);
        }
        if (in_array(strtolower((string) $document->status), ['cancelled', 'voided'], true)) {
            throw ValidationException::withMessages(['posting' => 'A cancelled or voided '.$label.' cannot be posted.']);
        }
    }

    private function existingJournal($sourceType, $sourceId, $companyId)
    {
        return DB::table('journal_entries')
            ->where('company_id', $companyId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('posting_type', 'original')
            ->first();
    }

    private function markPosted($table, $id, $journalId, array $extra = [])
    {
        DB::table($table)->where('id', $id)->update(array_merge([
            'journal_entry_id' => $journalId,
            'posting_status' => 'posted',
            'posted_by' => auth()->id(),
            'accounting_posted_at' => now(),
            'updated_at' => now(),
        ], $extra));
    }

    private function salesReceivableAccount($document)
    {
        $openingBalanceAccount = $this->openingBalanceControlAccount($document);
        if ($openingBalanceAccount) {
            return $openingBalanceAccount;
        }

        $keyMap = [
            'AR_TRADE_BI' => 'ar_trade_billing',
            'AR_REIMBURSABLE' => 'ar_reimbursable',
            'AR_TRADE_SOAA' => 'ar_soa_a',
            'AR_TRADE_DN' => 'ar_debit_note',
        ];
        $mappingCode = $document->ar_account_code ?? 'AR_TRADE_BI';
        $currency = strtoupper($document->currency ?: 'PHP');

        if ($mappingCode === 'AR_TRADE_BI') {
            $bdoAccount = DB::table('accounts')
                ->where('company_id', $document->company_id)
                ->where('name', 'like', 'A/R%Trade%BDO%'.$currency.'%')
                ->where('is_active', true)
                ->value('id');
            if ($bdoAccount) {
                return $bdoAccount;
            }
        }

        if ($mappingCode === 'AR_REIMBURSABLE') {
            return $this->reimbursableReceivableAccount($document->company_id, $currency);
        }

        if ($mappingCode === 'AR_TRADE_SOAA') {
            $metroAccount = DB::table('accounts')
                ->where('company_id', $document->company_id)
                ->where('name', 'like', 'A/R%Trade%Metro%'.$currency.'%')
                ->where('is_active', true)
                ->value('id');
            if ($metroAccount) {
                return $metroAccount;
            }

            throw ValidationException::withMessages([
                'posting' => 'An active A/R Trade Metro account for '.$currency.' is required to post SOA A.',
            ]);
        }

        if (Schema::hasTable('ar_account_mappings')) {
            $mapping = DB::table('ar_account_mappings')
                ->where('company_id', $document->company_id)
                ->where('code', $mappingCode)
                ->where('currency', strtoupper($document->currency ?: 'PHP'))
                ->first();
            if ($mapping && $mapping->account_id) {
                return $mapping->account_id;
            }
        }

        return $this->controlAccount($document->company_id, $keyMap[$mappingCode] ?? 'accounts_receivable');
    }

    private function reimbursableReceivableAccount($companyId, $currency)
    {
        $accountId = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('name', 'like', 'A/R%Reimbursable%'.strtoupper($currency).'%')
            ->where('is_active', true)
            ->value('id');

        return $accountId ?: $this->controlAccount($companyId, 'ar_reimbursable');
    }

    private function salesIncomeAccount($companyId, $name)
    {
        $accountId = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('name', $name)
            ->where('is_active', true)
            ->value('id');

        return $accountId ?: $this->controlAccount($companyId, 'service_revenue');
    }

    private function reimbursableChargesAccount($companyId)
    {
        $accountId = DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('name', 'Reimbursable charges')
            ->where('is_active', true)
            ->value('id');

        return $accountId ?: $this->controlAccount($companyId, 'reimbursable_recovery');
    }

    private function purchaseExpenseAccount($document)
    {
        foreach (['vendor_bill_lines', 'purchase_document_lines'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'account_id')) {
                $accountId = DB::table($table)
                    ->where('purchase_document_id', $document->id)
                    ->whereNotNull('account_id')
                    ->value('account_id');
                if ($accountId) {
                    return $accountId;
                }
            }
        }

        return $this->controlAccount($document->company_id, 'cost_of_services');
    }

    private function purchasePayableAccount($document)
    {
        $openingBalanceAccount = $this->openingBalanceControlAccount($document);
        if ($openingBalanceAccount) {
            return $openingBalanceAccount;
        }

        $label = trim((string) ($document->ap_account ?? ''));
        if ($label !== '') {
            $accountId = DB::table('accounts')
                ->where('company_id', $document->company_id)
                ->where('is_active', true)
                ->where('type', 'Accounts Payable')
                ->where(function ($query) use ($label) {
                    $query->where('name', $label)->orWhere('code', $label);
                })
                ->value('id');
            if ($accountId) {
                return $accountId;
            }
        }

        return $this->controlAccount($document->company_id, 'accounts_payable');
    }

    private function openingBalanceControlAccount($document)
    {
        $accountId = isset($document->opening_balance_control_account_id)
            ? (int) $document->opening_balance_control_account_id
            : 0;
        if (!$accountId) {
            return null;
        }

        return DB::table('accounts')
            ->where('id', $accountId)
            ->where('company_id', $document->company_id)
            ->where('is_active', true)
            ->value('id');
    }

    private function cashOrBankAccount($companyId, $method, $bankLabel)
    {
        if (stripos((string) $method, 'cash') !== false) {
            return $this->controlAccount($companyId, 'cash');
        }

        if ($bankLabel && Schema::hasTable('bank_accounts')) {
            $bankQuery = DB::table('bank_accounts')->where('company_id', $companyId);
            if (is_numeric($bankLabel)) {
                $bankQuery->where('id', $bankLabel);
            } else {
                $bankQuery->where(function ($query) use ($bankLabel) {
                    $query->where('account_name', $bankLabel)
                        ->orWhere('account_no', $bankLabel)
                        ->orWhere('bank_name', $bankLabel);
                });
            }
            $accountId = $bankQuery->value('account_id');
            if ($accountId) {
                return $accountId;
            }
        }

        return $this->controlAccount($companyId, 'bank');
    }

    private function controlAccount($companyId, $key)
    {
        if (Schema::hasTable('accounting_control_accounts')) {
            $accountId = DB::table('accounting_control_accounts')
                ->where('company_id', $companyId)
                ->where('control_key', $key)
                ->value('account_id');
            if ($accountId) {
                return $accountId;
            }
        }

        if (!isset($this->defaultAccounts[$key])) {
            throw ValidationException::withMessages(['posting' => 'No control account is configured for '.$key.'.']);
        }

        list($code, $name, $categoryName, $type) = $this->defaultAccounts[$key];
        $accountId = DB::table('accounts')->where('company_id', $companyId)->where('code', $code)->value('id');
        if (!$accountId) {
            $categoryId = DB::table('account_categories')->where('name', $categoryName)->value('id');
            if (!$categoryId) {
                $categoryId = DB::table('account_categories')->insertGetId([
                    'name' => $categoryName,
                    'normal_balance' => in_array($categoryName, ['Assets', 'Expenses'], true) ? 'debit' : 'credit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $accountId = DB::table('accounts')->insertGetId([
                'company_id' => $companyId,
                'account_category_id' => $categoryId,
                'parent_id' => null,
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'opening_balance' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('accounting_control_accounts')) {
            DB::table('accounting_control_accounts')->updateOrInsert(
                ['company_id' => $companyId, 'control_key' => $key],
                ['account_id' => $accountId, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return $accountId;
    }

    private function nextJournalNumber($companyId, $sourceType = null, $sourceId = null)
    {
        if (!Schema::hasTable('number_series')) {
            return 'JV-'.date('YmdHis');
        }

        $seriesClass = $this->journalNumberSeriesClass($companyId, $sourceType, $sourceId);
        $defaultPrefix = $seriesClass === 'METROBANK' ? 'JV-MB-' : 'JV-';

        $series = DB::table('number_series')
            ->where('company_id', $companyId)
            ->where('document_type', 'journal_voucher')
            ->where('class_code', $seriesClass)
            ->lockForUpdate()
            ->first();
        if (!$series) {
            DB::table('number_series')->insert([
                'company_id' => $companyId,
                'document_type' => 'journal_voucher',
                'class_code' => $seriesClass,
                'prefix' => $defaultPrefix,
                'current_number' => 0,
                'padding' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $series = DB::table('number_series')
                ->where('company_id', $companyId)
                ->where('document_type', 'journal_voucher')
                ->where('class_code', $seriesClass)
                ->lockForUpdate()
                ->first();
        }

        $next = (int) $series->current_number + 1;
        $prefix = $series->prefix ?: $defaultPrefix;
        $padding = (int) $series->padding;
        $journalNo = $prefix.str_pad($next, $padding, '0', STR_PAD_LEFT);

        // Imported or manually entered journals can legitimately move the
        // ledger ahead of its number-series counter. Reconcile the allocator
        // with the ledger while the series row is locked so automatic posting
        // never attempts an already-issued journal number.
        while (DB::table('journal_entries')
            ->where('company_id', $companyId)
            ->where('journal_no', $journalNo)
            ->exists()) {
            $next++;
            $journalNo = $prefix.str_pad($next, $padding, '0', STR_PAD_LEFT);
        }

        DB::table('number_series')->where('id', $series->id)->update([
            'current_number' => $next,
            'updated_at' => now(),
        ]);

        return $journalNo;
    }

    private function journalNumberSeriesClass($companyId, $sourceType, $sourceId)
    {
        if (!Schema::hasTable('bank_accounts')) {
            return '';
        }

        $sourceTables = [
            'payment' => 'payments',
            'receipt' => 'receipts',
        ];
        if (!isset($sourceTables[$sourceType]) || !$sourceId || !Schema::hasTable($sourceTables[$sourceType])) {
            return '';
        }

        $bankAccountId = DB::table($sourceTables[$sourceType])
            ->where('company_id', $companyId)
            ->where('id', $sourceId)
            ->value('bank_account_id');
        if (!$bankAccountId) {
            return '';
        }

        $bankName = DB::table('bank_accounts')
            ->where('company_id', $companyId)
            ->where('id', $bankAccountId)
            ->value('bank_name');

        return stripos((string) $bankName, 'metro') !== false ? 'METROBANK' : '';
    }

    private function rate($currency, $rate)
    {
        return $currency === 'PHP' ? 1.0 : max(0.000001, (float) $rate);
    }

    private function recordBankMovement($companyId, $sourceType, $sourceId, $journalId, $date, $reference, $method, $bankLabel, $currency, $rate, $debit, $credit, $jobId)
    {
        if (!Schema::hasTable('bank_accounts') || !Schema::hasTable('bank_transactions')) {
            return;
        }
        if (stripos((string) $method, 'cash') !== false) {
            return;
        }

        $query = DB::table('bank_accounts')->where('company_id', $companyId)->where('is_active', true);
        if ($bankLabel) {
            if (is_numeric($bankLabel)) {
                $query->where('id', $bankLabel);
            } else {
                $query->where(function ($inner) use ($bankLabel) {
                    $inner->where('account_name', $bankLabel)
                        ->orWhere('account_no', $bankLabel)
                        ->orWhere('bank_name', $bankLabel);
                });
            }
        } else {
            $query->where('currency', $currency);
        }
        $bank = $query->orderBy('id')->lockForUpdate()->first();
        if (!$bank) {
            return;
        }

        $baseDebit = round($debit * $rate, 4);
        $baseCredit = round($credit * $rate, 4);
        DB::table('bank_transactions')->insert([
            'bank_account_id' => $bank->id,
            'transaction_no' => $reference,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'journal_entry_id' => $journalId,
            'transaction_date' => $date,
            'transaction_type' => $debit > 0 ? 'deposit' : 'withdrawal',
            'reference_no' => $reference,
            'shipment_job_id' => $jobId,
            'currency' => $currency,
            'exchange_rate' => $rate,
            'foreign_amount' => round($debit > 0 ? $debit : $credit, 4),
            'base_amount' => round($baseDebit > 0 ? $baseDebit : $baseCredit, 4),
            'description' => ucfirst($sourceType).' '.$reference,
            'debit' => $baseDebit,
            'credit' => $baseCredit,
            'is_reconciled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bank_accounts')->where('id', $bank->id)->update([
            'book_balance' => (float) $bank->book_balance + $baseDebit - $baseCredit,
            'updated_at' => now(),
        ]);
    }

    private function reverseBankMovement($sourceType, $sourceId, $journalId, $reason)
    {
        if (!Schema::hasTable('bank_transactions')) {
            return;
        }

        $original = DB::table('bank_transactions')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->lockForUpdate()
            ->first();
        if (!$original) {
            return;
        }

        $reversalType = substr($sourceType.'_reversal', 0, 255);
        if (DB::table('bank_transactions')->where('source_type', $reversalType)->where('source_id', $sourceId)->exists()) {
            return;
        }

        $bank = DB::table('bank_accounts')->where('id', $original->bank_account_id)->lockForUpdate()->first();
        $debit = (float) $original->credit;
        $credit = (float) $original->debit;
        DB::table('bank_transactions')->insert([
            'bank_account_id' => $original->bank_account_id,
            'transaction_no' => 'REV-'.$original->transaction_no,
            'source_type' => $reversalType,
            'source_id' => $sourceId,
            'journal_entry_id' => $journalId,
            'transaction_date' => date('Y-m-d'),
            'transaction_type' => $debit > 0 ? 'deposit' : 'withdrawal',
            'reference_no' => $original->reference_no,
            'shipment_job_id' => $original->shipment_job_id,
            'currency' => $original->currency,
            'exchange_rate' => $original->exchange_rate,
            'foreign_amount' => $original->foreign_amount,
            'base_amount' => $original->base_amount,
            'description' => 'Reversal: '.$reason,
            'debit' => $debit,
            'credit' => $credit,
            'is_reconciled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($bank) {
            DB::table('bank_accounts')->where('id', $bank->id)->update([
                'book_balance' => (float) $bank->book_balance + $debit - $credit,
                'updated_at' => now(),
            ]);
        }
    }
}
