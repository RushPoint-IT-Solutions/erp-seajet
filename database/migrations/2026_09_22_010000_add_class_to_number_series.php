<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddClassToNumberSeries extends Migration
{
    public function up(): void
    {
        Schema::table('number_series', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'document_type']);
            $table->string('class_code', 50)->default('')->after('document_type');
            $table->unique(['company_id', 'document_type', 'class_code'], 'number_series_company_type_class_unique');
        });

        $defaults = [
            'billing_invoice' => ['BI', 6],
            'official_receipt' => ['OR', 5],
            'acknowledgement_receipt' => ['AR', 6],
        ];
        $codes = ['MANILA' => 'MNL', 'PAMPANGA' => 'PAM', 'CEBU' => 'CEB'];
        $companyIds = DB::table('number_series')->whereNotNull('company_id')
            ->whereIn('document_type', array_keys($defaults))
            ->distinct()->pluck('company_id');

        foreach ($companyIds as $companyId) {
            foreach ($defaults as $type => [$label, $padding]) {
                foreach ($codes as $class => $shortCode) {
                    if (DB::table('number_series')->where('company_id', $companyId)
                        ->where('document_type', $type)->where('class_code', $class)->exists()) {
                        continue;
                    }
                    DB::table('number_series')->insert([
                        'company_id' => $companyId,
                        'document_type' => $type,
                        'class_code' => $class,
                        'prefix' => $label.'-'.$shortCode.'-',
                        'current_number' => 0,
                        'padding' => $padding,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('number_series')->where('class_code', '<>', '')->delete();
        Schema::table('number_series', function (Blueprint $table) {
            $table->dropUnique('number_series_company_type_class_unique');
            $table->dropColumn('class_code');
            $table->unique(['company_id', 'document_type']);
        });
    }
}
