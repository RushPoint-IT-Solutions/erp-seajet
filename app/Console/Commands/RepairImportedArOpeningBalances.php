<?php

namespace App\Console\Commands;

use App\Services\Accounting\PeriodLockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairImportedArOpeningBalances extends Command
{
    protected $signature = 'seajet:repair-imported-ar {--company=SJ} {--date=} {--apply}';

    protected $description = 'Preview or post correcting journals for AR opening balances imported with reversed debits and credits.';

    public function handle(PeriodLockService $periods)
    {
        $company = DB::table('companies')->where('code', $this->option('company'))->first();
        if (!$company) {
            $this->error('Company code was not found.');
            return 1;
        }

        $date = $this->option('date') ?: date('Y-m-d');
        $parsedDate = \DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            $this->error('Use --date=YYYY-MM-DD for the correction posting date.');
            return 1;
        }

        $rows = DB::table('opening_balance_import_rows as imported')
            ->join('opening_balance_import_batches as batches', 'batches.id', '=', 'imported.batch_id')
            ->join('sales_documents as documents', 'documents.id', '=', 'imported.sales_document_id')
            ->select(
                'imported.*', 'batches.account_mapping',
                'documents.opening_balance_control_account_id as ar_account_id',
                'documents.customer_id', 'documents.branch_id', 'documents.class_code'
            )
            ->where('imported.company_id', $company->id)
            ->whereIn('imported.balance_type', ['ar_trade', 'ar_reimbursable'])
            ->orderBy('imported.id')
            ->get();

        $candidates = [];
        $ambiguous = 0;
        $alreadyCorrect = 0;
        foreach ($rows as $row) {
            if (DB::table('journal_entries')->where('company_id', $company->id)
                ->where('source_type', 'opening_balance_import_correction')
                ->where('source_id', $row->id)->exists()) {
                $alreadyCorrect++;
                continue;
            }

            $mapping = json_decode((string) $row->account_mapping, true);
            $openingAccountId = isset($mapping['opening_balance']) ? (int) $mapping['opening_balance'] : 0;
            $arAccountId = (int) $row->ar_account_id;
            $description = ($row->balance_type === 'ar_reimbursable' ? 'A/R Reimbursable' : 'A/R Trade')
                .' opening balance '.$row->reference_no.' | '.$row->party_name.' | '.$row->source_date;
            $lines = DB::table('journal_entry_lines')
                ->where('journal_entry_id', $row->journal_entry_id)
                ->where('description', $description);
            $wrongAr = (clone $lines)->where('account_id', $arAccountId)
                ->where('customer_id', $row->customer_id)
                ->where('debit', 0)->where('credit', $row->base_amount)->exists();
            $wrongOpening = (clone $lines)->where('account_id', $openingAccountId)
                ->where('debit', $row->base_amount)->where('credit', 0)->exists();
            $correctAr = (clone $lines)->where('account_id', $arAccountId)
                ->where('customer_id', $row->customer_id)
                ->where('debit', $row->base_amount)->where('credit', 0)->exists();
            $correctOpening = (clone $lines)->where('account_id', $openingAccountId)
                ->where('debit', 0)->where('credit', $row->base_amount)->exists();

            if ($correctAr && $correctOpening) {
                $alreadyCorrect++;
            } elseif ($openingAccountId && $arAccountId && $wrongAr && $wrongOpening) {
                $row->opening_account_id = $openingAccountId;
                $candidates[] = $row;
            } else {
                $ambiguous++;
                $this->warn('Skipped import row '.$row->id.' ('.$row->reference_no.'): original journal did not match the expected reversed pair.');
            }
        }

        $this->line('Imported AR rows: '.$rows->count());
        $this->line('Already correct or corrected: '.$alreadyCorrect);
        $this->line('Needs correction: '.count($candidates));
        $this->line('Needs manual review: '.$ambiguous);
        $this->line('Correction posting date: '.$date);
        $this->line('Adjustment debit and credit each: PHP '.number_format(array_sum(array_map(function ($row) {
            return 2 * (float) $row->base_amount;
        }, $candidates)), 2));

        if (!$this->option('apply')) {
            $this->comment('Preview only. Add --apply after reviewing these counts.');
            return 0;
        }
        if ($ambiguous) {
            $this->error('No corrections posted because some rows need manual review.');
            return 1;
        }
        if (!$candidates) {
            return 0;
        }

        $periods->assertOpen($company->id, $date);
        $posted = 0;
        foreach ($candidates as $row) {
            DB::transaction(function () use ($company, $row, $date, &$posted) {
                DB::table('opening_balance_import_rows')->where('id', $row->id)->lockForUpdate()->first();
                if (DB::table('journal_entries')->where('company_id', $company->id)
                    ->where('source_type', 'opening_balance_import_correction')
                    ->where('source_id', $row->id)->exists()) {
                    return;
                }

                $now = now();
                $amount = round(2 * (float) $row->base_amount, 4);
                $foreignAmount = strtoupper((string) $row->currency) === 'USD'
                    ? round(2 * (float) $row->foreign_amount, 4) : 0;
                $classCode = $row->class_code;
                $journalId = DB::table('journal_entries')->insertGetId([
                    'company_id' => $company->id,
                    'branch_id' => $row->branch_id,
                    'journal_no' => 'OBR-'.str_pad($row->id, 8, '0', STR_PAD_LEFT),
                    'journal_date' => $date,
                    'source_module' => 'GL',
                    'source_type' => 'opening_balance_import_correction',
                    'source_id' => $row->id,
                    'posting_type' => 'original',
                    'reference_no' => $row->reference_no,
                    'memo' => 'Correct reversed AR opening balance from import row '.$row->id
                        .'; original journal '.$row->journal_entry_id.'.',
                    'status' => 'posted',
                    'posted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $line = [
                    'journal_entry_id' => $journalId,
                    'description' => 'Correction for imported AR '.$row->reference_no,
                    'currency' => $row->currency,
                    'exchange_rate' => $row->exchange_rate,
                    'tax_amount' => 0,
                    'withholding_tax_rate' => 0,
                    'withholding_tax_amount' => 0,
                    'shipment_job_id' => null,
                    'class_code' => $classCode,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                DB::table('journal_entry_lines')->insert(array_merge($line, [
                    'account_id' => $row->ar_account_id,
                    'debit' => $amount, 'credit' => 0,
                    'foreign_debit' => $foreignAmount, 'foreign_credit' => 0,
                    'customer_id' => $row->customer_id, 'supplier_id' => null,
                ]));
                DB::table('journal_entry_lines')->insert(array_merge($line, [
                    'account_id' => $row->opening_account_id,
                    'debit' => 0, 'credit' => $amount,
                    'foreign_debit' => 0, 'foreign_credit' => $foreignAmount,
                    'customer_id' => null, 'supplier_id' => null,
                ]));
                $posted++;
            });
        }

        $this->info('Posted correcting journals: '.$posted);
        return 0;
    }
}
