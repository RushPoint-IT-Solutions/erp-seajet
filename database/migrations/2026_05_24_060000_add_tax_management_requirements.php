<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaxManagementRequirements extends Migration
{
    public function up()
    {
        Schema::table('tax_codes', function (Blueprint $table) {
            $table->string('classification')->nullable()->after('tax_type');
            $table->boolean('is_withholding')->default(false)->after('classification');
            $table->string('withholding_kind')->nullable()->after('is_withholding');
        });

        Schema::table('sales_documents', function (Blueprint $table) {
            $table->decimal('vatable_amount', 18, 4)->default(0)->after('subtotal');
            $table->decimal('zero_rated_amount', 18, 4)->default(0)->after('vatable_amount');
            $table->decimal('vat_exempt_amount', 18, 4)->default(0)->after('zero_rated_amount');
            $table->decimal('withholding_tax_rate', 8, 4)->default(0)->after('tax_total');
            $table->decimal('withholding_tax_amount', 18, 4)->default(0)->after('withholding_tax_rate');
        });

        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->decimal('vatable_amount', 18, 4)->default(0)->after('subtotal');
            $table->decimal('zero_rated_amount', 18, 4)->default(0)->after('vatable_amount');
            $table->decimal('vat_exempt_amount', 18, 4)->default(0)->after('zero_rated_amount');
        });
    }

    public function down()
    {
        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->dropColumn(['vatable_amount', 'zero_rated_amount', 'vat_exempt_amount']);
        });

        Schema::table('sales_documents', function (Blueprint $table) {
            $table->dropColumn([
                'vatable_amount',
                'zero_rated_amount',
                'vat_exempt_amount',
                'withholding_tax_rate',
                'withholding_tax_amount',
            ]);
        });

        Schema::table('tax_codes', function (Blueprint $table) {
            $table->dropColumn(['classification', 'is_withholding', 'withholding_kind']);
        });
    }
}
