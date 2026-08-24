<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PeriodLockService
{
    public function assertOpen($companyId, $date)
    {
        $date = date('Y-m-d', strtotime((string) $date));

        if (Schema::hasTable('fiscal_years')) {
            $closedYear = DB::table('fiscal_years')
                ->where('company_id', $companyId)
                ->where('is_closed', true)
                ->where('starts_on', '<=', $date)
                ->where('ends_on', '>=', $date)
                ->exists();

            if ($closedYear) {
                throw ValidationException::withMessages([
                    'document_date' => 'The fiscal year containing '.$date.' is closed. Posting is not allowed.',
                ]);
            }
        }

        if (!Schema::hasTable('accounting_periods')) {
            return;
        }

        $period = DB::table('accounting_periods')
            ->where('company_id', $companyId)
            ->where('starts_on', '<=', $date)
            ->where('ends_on', '>=', $date)
            ->orderBy('starts_on', 'desc')
            ->first();

        if ($period && in_array(strtolower($period->status), ['closed', 'locked'], true)) {
            throw ValidationException::withMessages([
                'document_date' => 'The accounting period containing '.$date.' is locked.',
            ]);
        }
    }
}
