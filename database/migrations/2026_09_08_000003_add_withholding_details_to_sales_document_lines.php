<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWithholdingDetailsToSalesDocumentLines extends Migration
{
    public function up()
    {
        Schema::table('sales_document_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_document_lines', 'withholding_tax_rate')) {
                $table->decimal('withholding_tax_rate', 8, 4)->nullable()->after('tax_category');
            }
            if (!Schema::hasColumn('sales_document_lines', 'withholding_tax_amount')) {
                $table->decimal('withholding_tax_amount', 18, 4)->nullable()->after('withholding_tax_rate');
            }
        });
    }

    public function down()
    {
        $columns = array_values(array_filter([
            'withholding_tax_rate',
            'withholding_tax_amount',
        ], function ($column) {
            return Schema::hasColumn('sales_document_lines', $column);
        }));

        if ($columns) {
            Schema::table('sales_document_lines', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}
