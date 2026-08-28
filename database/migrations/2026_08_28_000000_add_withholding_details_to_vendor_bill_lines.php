<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWithholdingDetailsToVendorBillLines extends Migration
{
    public function up()
    {
        foreach (['vendor_bill_lines', 'purchase_document_lines'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'atc_code')) {
                    $table->string('atc_code', 20)->nullable()->after('tax_category');
                }
                if (!Schema::hasColumn($tableName, 'withholding_tax_rate')) {
                    $table->decimal('withholding_tax_rate', 8, 4)->default(0)->after('atc_code');
                }
                if (!Schema::hasColumn($tableName, 'withholding_tax_amount')) {
                    $table->decimal('withholding_tax_amount', 18, 4)->default(0)->after('withholding_tax_rate');
                }
            });
        }
    }

    public function down()
    {
        foreach (['vendor_bill_lines', 'purchase_document_lines'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $columns = array_values(array_filter([
                'atc_code',
                'withholding_tax_rate',
                'withholding_tax_amount',
            ], function ($column) use ($tableName) {
                return Schema::hasColumn($tableName, $column);
            }));

            if ($columns) {
                Schema::table($tableName, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
}
