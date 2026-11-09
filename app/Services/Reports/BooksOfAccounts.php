<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BooksOfAccounts
{
    public function bankName($name)
    {
        $name = strtolower((string) $name);
        if (strpos($name, 'bdo') !== false || strpos($name, 'banco de oro') !== false) {
            return 'BDO';
        }
        return strpos($name, 'metro') !== false ? 'METROBANK' : null;
    }

    public function annotate($lines, $companyId, $bank = null)
    {
        $sources = [];
        foreach (['sales_documents', 'receipts', 'payments'] as $table) {
            $types = $table === 'sales_documents'
                ? ['sales_document', 'invoice', 'billing_invoice', 'soa', 'soa_a', 'statement_of_account', 'statement_of_account_a']
                : ($table === 'receipts' ? ['receipt', 'customer_receipt', 'collection'] : ['payment', 'supplier_payment', 'ap_payment']);
            $ids = $lines->filter(function ($line) use ($types) { return in_array(strtolower((string) $line->source_type), $types, true); })->pluck('source_id')->filter()->unique()->all();
            $sources[$table] = Schema::hasTable($table)
                ? DB::table($table)->where('company_id', $companyId)->whereIn('id', $ids)->get()->keyBy('id') : collect();
        }
        $banks = Schema::hasTable('bank_accounts')
            ? DB::table('bank_accounts')->where('company_id', $companyId)->get()->keyBy('id') : collect();
        $billIds = $lines->filter(function ($line) { return in_array(strtolower((string) $line->source_type), ['purchase_document', 'vendor_bill', 'bill'], true); })->pluck('source_id')->filter()->unique()->all();
        $bills = Schema::hasTable('purchase_documents')
            ? DB::table('purchase_documents')->where('company_id', $companyId)->whereIn('id', $billIds)->get()->keyBy('id') : collect();
        $vendors = Schema::hasTable('suppliers') ? DB::table('suppliers')->get()->keyBy('id') : collect();
        foreach ($lines as $line) {
            $bill = in_array(strtolower((string) $line->source_type), ['purchase_document', 'vendor_bill', 'bill'], true) ? $bills->get($line->source_id) : null;
            $vendor = $bill ? $vendors->get($bill->supplier_id) : null;
            $line->report_payee = $vendor ? $vendor->name : ($line->payee ?? '');
            $line->report_description = $bill ? (trim((string) ($bill->memo ?? '')) ?: trim((string) ($bill->remarks ?? ''))) : '';
            $line->book_column = $this->column($line, $sources['sales_documents']->get($line->source_id));
        }
        $lines = $this->moveSameMonthSettlements($lines, $bills, $sources['payments']);
        $result = collect();
        foreach ($lines->groupBy(function ($line) {
            // An import journal can contain unrelated balances for both banks.
            // Each balance's debit/credit pair shares its own description.
            return $line->journal_entry_id.(isset($line->report_payment_id) ? '|payment:'.$line->report_payment_id : '')
                .($line->source_type === 'opening_balance_import' ? '|'.($line->description ?? '') : '');
        }) as $journalLines) {
            $first = $journalLines->first();
            $sourceType = strtolower((string) $first->source_type);
            $table = in_array($sourceType, ['receipt', 'customer_receipt', 'collection'], true) ? 'receipts'
                : (in_array($sourceType, ['payment', 'supplier_payment', 'ap_payment'], true) ? 'payments' : null);
            $source = isset($first->report_payment_id) ? $sources['payments']->get($first->report_payment_id)
                : ($table ? $sources[$table]->get($first->source_id) : null);
            $receivingBank = $source ? $banks->get($source->bank_account_id ?? null) : null;
            // The receiving/payment bank takes priority over the receivable's bank.
            $journalBanks = $receivingBank ? collect([$this->bankName($receivingBank->bank_name)])
                : $journalLines->map(function ($line) { return $this->bankName($line->account_name); })->filter()->unique();
            if ($bank && !$journalBanks->contains($bank)) {
                continue;
            }
            foreach ($journalLines as $line) {
                $result->push($line);
            }
        }
        return $result;
    }

    private function isPayable($line)
    {
        $name = strtolower((string) $line->account_name);
        return strpos($name, 'accounts payable') !== false || strpos($name, 'a/p') !== false;
    }

    private function amounts($line)
    {
        return [(float) ($line->debit_total ?? $line->debit ?? 0), (float) ($line->credit_total ?? $line->credit ?? 0)];
    }

    private function setAmounts($line, $debit, $credit)
    {
        $line->debit = $line->debit_total = round($debit, 4);
        $line->credit = $line->credit_total = round($credit, 4);
    }

    private function moveSameMonthSettlements($lines, $bills, $payments)
    {
        $result = collect();
        $billGroups = $lines->filter(function ($line) { return in_array(strtolower((string) $line->source_type), ['purchase_document', 'vendor_bill', 'bill'], true); })->groupBy('source_id');
        $paymentGroups = $lines->filter(function ($line) { return in_array(strtolower((string) $line->source_type), ['payment', 'supplier_payment', 'ap_payment'], true); })->groupBy('source_id');
        $movedBillIds = [];
        foreach ($billGroups as $billId => $billLines) {
            $bill = $bills->get($billId);
            if (!$bill || empty($bill->document_date)) { continue; }
            $payable = $billLines->sum(function ($line) { return $this->isPayable($line) ? max(0, $this->amounts($line)[1] - $this->amounts($line)[0]) : 0; });
            if ($payable < .00005) { continue; }
            $remaining = $payable;
            $allocations = [];
            foreach ($paymentGroups as $paymentId => $paymentLines) {
                $payment = $payments->get($paymentId);
                if (!$payment || ($payment->bill_no ?? null) !== $bill->document_no
                    || substr((string) $payment->payment_date, 0, 7) !== substr((string) $bill->document_date, 0, 7)) { continue; }
                $debit = $paymentLines->sum(function ($line) { return $this->isPayable($line) ? max(0, $this->amounts($line)[0] - $this->amounts($line)[1]) : 0; });
                $allocated = min($remaining, $debit);
                if ($allocated < .00005) { continue; }
                $allocations[$paymentId] = $allocated;
                $remaining -= $allocated;
                // Net only the matched A/P settlement, keeping FX/other residuals.
                $toRemove = $allocated;
                foreach ($paymentLines as $line) {
                    if (!$this->isPayable($line)) { continue; }
                    list($dr, $cr) = $this->amounts($line);
                    $remove = min($toRemove, max(0, $dr - $cr));
                    $this->setAmounts($line, $dr - $remove, $cr);
                    $toRemove -= $remove;
                }
            }
            if (!$allocations) { continue; }
            $movedBillIds[] = $billId;
            foreach ($billLines as $line) {
                list($dr, $cr) = $this->amounts($line);
                $remainingDr = $dr;
                $remainingCr = $cr;
                foreach ($allocations as $paymentId => $allocated) {
                    $partDr = round($dr * $allocated / $payable, 4);
                    $partCr = round($cr * $allocated / $payable, 4);
                    $remainingDr -= $partDr;
                    $remainingCr -= $partCr;
                    if ($this->isPayable($line)) { continue; }
                    $part = clone $line;
                    $this->setAmounts($part, $partDr, $partCr);
                    $part->book_column = 'CASH DISBURSEMENT BOOK';
                    $part->report_payment_id = $paymentId;
                    $part->report_projection = 'Same-month bill settlement';
                    $part->report_origin = spl_object_hash($line);
                    $result->push($part);
                }
                $unpaid = clone $line;
                $unpaid->report_origin = spl_object_hash($line);
                $this->setAmounts($unpaid, $remainingDr, $remainingCr);
                $result->push($unpaid);
            }
            foreach ($allocations as $paymentId => $allocated) {
                $parts = $result->filter(function ($part) use ($paymentId) { return ($part->report_payment_id ?? null) == $paymentId; });
                $net = $parts->sum(function ($part) { list($dr, $cr) = $this->amounts($part); return $dr - $cr; });
                $delta = round($allocated - $net, 4);
                if (abs($delta) < .00005) { continue; }
                $part = $parts->first(function ($part) { return $this->amounts($part)[0] > 0; });
                $unpaid = $part ? $result->first(function ($candidate) use ($part) {
                    return !isset($candidate->report_payment_id) && ($candidate->report_origin ?? null) === $part->report_origin;
                }) : null;
                if ($part && $unpaid) {
                    list($dr, $cr) = $this->amounts($part);
                    $this->setAmounts($part, $dr + $delta, $cr);
                    list($dr, $cr) = $this->amounts($unpaid);
                    $this->setAmounts($unpaid, $dr - $delta, $cr);
                }
            }
        }
        foreach ($lines as $line) {
            if (in_array(strtolower((string) $line->source_type), ['purchase_document', 'vendor_bill', 'bill'], true)
                && in_array($line->source_id, $movedBillIds)) { continue; }
            $result->push($line);
        }
        return $result->filter(function ($line) {
            list($dr, $cr) = $this->amounts($line);
            return abs($dr) + abs($cr) >= .00005;
        })->values();
    }

    public function column($line, $document = null)
    {
        $type = strtolower((string) $line->source_type);
        if (in_array($type, ['receipt', 'customer_receipt', 'collection'], true)) {
            return 'CASH RECEIPTS BOOK';
        }
        if (in_array($type, ['payment', 'supplier_payment', 'ap_payment'], true)) {
            return 'CASH DISBURSEMENT BOOK';
        }
        if (in_array($type, ['purchase_document', 'vendor_bill', 'bill', 'opening_balance_import', 'opening_balance_row'], true)) {
            return 'JOURNAL BOOK';
        }
        if (in_array($type, ['sales_document', 'invoice', 'billing_invoice', 'soa', 'soa_a', 'statement_of_account', 'statement_of_account_a'], true)) {
            $documentType = strtolower((string) ($document->document_type ?? $type));
            $template = strtoupper((string) ($document->template_code ?? ''));
            if (in_array($documentType, ['soa', 'soa_a', 'soa_b', 'statement_of_account', 'statement_of_account_a'], true)
                || in_array($template, ['SOA_A', 'SOA_B'], true)) {
                return 'SOA';
            }
            // A billing invoice can carry both service and reimbursable components.
            $name = strtolower((string) $line->account_name);
            if (strpos($name, 'reimburs') !== false || strpos($name, 'soa a') !== false
                || strpos(strtolower((string) ($line->description ?? '')), 'reimbursable') !== false) {
                return 'SOA';
            }
            return 'SALES BOOK';
        }
        $module = strtolower((string) $line->source_module);
        if (strpos($module, 'receipt') !== false || strpos($module, 'collection') !== false || $module === 'or') {
            return 'CASH RECEIPTS BOOK';
        }
        if (strpos($module, 'disbursement') !== false || strpos($module, 'payment') !== false) {
            return 'CASH DISBURSEMENT BOOK';
        }
        if (strpos($module, 'soa') !== false || strpos($module, 'statement') !== false) {
            return 'SOA';
        }
        if (strpos($module, 'sale') !== false || strpos($module, 'invoice') !== false || strpos($module, 'billing') !== false) {
            return 'SALES BOOK';
        }
        return 'JOURNAL BOOK';
    }
}
