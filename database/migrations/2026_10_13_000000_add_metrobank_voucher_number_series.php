<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMetrobankVoucherNumberSeries extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('number_series') || !Schema::hasTable('bank_accounts')) {
            return;
        }

        $companyIds = DB::table('bank_accounts')
            ->whereRaw('LOWER(bank_name) LIKE ?', ['%metro%'])
            ->distinct()
            ->pluck('company_id');

        foreach ($companyIds as $companyId) {
            foreach ([
                ['check_voucher', 'CV-MB-', 5],
                ['journal_voucher', 'JV-MB-', 6],
            ] as [$documentType, $prefix, $padding]) {
                $exists = DB::table('number_series')
                    ->where('company_id', $companyId)
                    ->where('document_type', $documentType)
                    ->where('class_code', 'METROBANK')
                    ->exists();
                if ($exists) {
                    continue;
                }

                DB::table('number_series')->insert([
                    'company_id' => $companyId,
                    'document_type' => $documentType,
                    'class_code' => 'METROBANK',
                    'prefix' => $prefix,
                    'current_number' => 0,
                    'padding' => $padding,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('number_series')) {
            return;
        }

        DB::table('number_series')
            ->where('class_code', 'METROBANK')
            ->whereIn('document_type', ['check_voucher', 'journal_voucher'])
            ->delete();
    }
}
