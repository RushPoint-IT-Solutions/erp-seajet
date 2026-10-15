<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestoreAxelumAr7632WithCorrectDate extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('receipts') || !Schema::hasTable('customers')) {
            return;
        }

        DB::transaction(function () {
            $receipt = DB::table('receipts')
                ->join('customers', 'customers.id', '=', 'receipts.customer_id')
                ->where('customers.name', 'like', '%AXELUM%')
                ->where('receipts.receipt_date', '2026-10-13')
                ->where(function ($query) {
                    $query->where('receipts.receipt_no', 'like', '%7632')
                        ->orWhere('receipts.reference_no', 'like', '%7632');
                })
                ->where(function ($query) {
                    $query->where('receipts.status', 'voided')
                        ->orWhere('receipts.posting_status', 'reversed');
                })
                ->select('receipts.*')
                ->orderByDesc('receipts.id')
                ->lockForUpdate()
                ->first();

            if (!$receipt) {
                return;
            }

            $originalJournal = Schema::hasTable('journal_entries')
                ? DB::table('journal_entries')
                    ->where('company_id', $receipt->company_id)
                    ->where('source_type', 'receipt')
                    ->where('source_id', $receipt->id)
                    ->where('posting_type', 'original')
                    ->first()
                : null;
            $reversalJournal = Schema::hasTable('journal_entries')
                ? DB::table('journal_entries')
                    ->where('company_id', $receipt->company_id)
                    ->where('source_type', 'receipt')
                    ->where('source_id', $receipt->id)
                    ->where('posting_type', 'reversal')
                    ->first()
                : null;

            if (Schema::hasTable('bank_transactions')) {
                $reversalBank = DB::table('bank_transactions')
                    ->where('source_type', 'receipt_reversal')
                    ->where('source_id', $receipt->id)
                    ->lockForUpdate()
                    ->first();
                if ($reversalBank) {
                    if (Schema::hasTable('bank_accounts')) {
                        $bank = DB::table('bank_accounts')->where('id', $reversalBank->bank_account_id)->lockForUpdate()->first();
                        if ($bank) {
                            DB::table('bank_accounts')->where('id', $bank->id)->update([
                                'book_balance' => (float) $bank->book_balance - (float) $reversalBank->debit + (float) $reversalBank->credit,
                                'updated_at' => now(),
                            ]);
                        }
                    }
                    DB::table('bank_transactions')->where('id', $reversalBank->id)->delete();
                }
                DB::table('bank_transactions')
                    ->where('source_type', 'receipt')
                    ->where('source_id', $receipt->id)
                    ->update(['transaction_date' => '2026-10-08', 'updated_at' => now()]);
            }

            if ($reversalJournal) {
                if (Schema::hasTable('journal_entry_lines')) {
                    DB::table('journal_entry_lines')->where('journal_entry_id', $reversalJournal->id)->delete();
                }
                DB::table('journal_entries')->where('id', $reversalJournal->id)->delete();
            }
            if ($originalJournal) {
                DB::table('journal_entries')->where('id', $originalJournal->id)->update([
                    'journal_date' => '2026-10-08',
                    'status' => 'posted',
                    'updated_at' => now(),
                ]);
            }

            DB::table('receipts')->where('id', $receipt->id)->update([
                'receipt_date' => '2026-10-08',
                'status' => 'posted',
                'posting_status' => 'posted',
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('receipt_applications') && Schema::hasTable('sales_documents')) {
                $documentIds = DB::table('receipt_applications')
                    ->where('receipt_id', $receipt->id)
                    ->pluck('sales_document_id');
                foreach ($documentIds as $documentId) {
                    $document = DB::table('sales_documents')->where('id', $documentId)->lockForUpdate()->first();
                    if (!$document) {
                        continue;
                    }
                    $paid = (float) DB::table('receipt_applications')
                        ->join('receipts', 'receipts.id', '=', 'receipt_applications.receipt_id')
                        ->where('receipt_applications.sales_document_id', $documentId)
                        ->whereNotIn('receipts.status', ['cancelled', 'voided'])
                        ->sum('receipt_applications.amount_applied');
                    $total = max(0, (float) $document->grand_total);
                    if ($total <= 0) {
                        $total = max(0, (float) $document->invoice_amount) + max(0, (float) $document->freight_total);
                    }
                    DB::table('sales_documents')->where('id', $documentId)->update([
                        'paid_total' => $paid,
                        'total_payment' => $paid,
                        'balance_due' => max(0, $total - $paid),
                        'status' => $paid >= $total - 0.005 && $total > 0 ? 'paid' : ($paid > 0.005 ? 'partial' : 'posted'),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (Schema::hasTable('activity_logs')) {
                DB::table('activity_logs')->insert([
                    'company_id' => $receipt->company_id,
                    'user_id' => null,
                    'action' => 'restored_date_corrected',
                    'auditable_type' => 'receipt',
                    'auditable_id' => $receipt->id,
                    'old_values' => json_encode(['status' => $receipt->status, 'receipt_date' => $receipt->receipt_date]),
                    'new_values' => json_encode(['status' => 'posted', 'receipt_date' => '2026-10-08']),
                    'ip_address' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down()
    {
        // Intentionally irreversible: this is a targeted accounting-data correction.
    }
}
