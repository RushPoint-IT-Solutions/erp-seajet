<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVendorNumberSeries extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('companies') || !Schema::hasTable('number_series')) {
            return;
        }

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $exists = DB::table('number_series')
                ->where('company_id', $companyId)
                ->where('document_type', 'vendor')
                ->exists();

            if ($exists) {
                continue;
            }

            $currentNumber = 0;
            if (Schema::hasTable('suppliers')) {
                $currentNumber = DB::table('suppliers')
                    ->where('company_id', $companyId)
                    ->where('code', 'like', 'VND-%')
                    ->pluck('code')
                    ->reduce(function ($highest, $code) {
                        $suffix = substr((string) $code, 4);

                        return ctype_digit($suffix) ? max($highest, (int) $suffix) : $highest;
                    }, 0);
            }

            DB::table('number_series')->insert([
                'company_id' => $companyId,
                'document_type' => 'vendor',
                'prefix' => 'VND-',
                'current_number' => $currentNumber,
                'padding' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('number_series')) {
            DB::table('number_series')->where('document_type', 'vendor')->delete();
        }
    }
}
