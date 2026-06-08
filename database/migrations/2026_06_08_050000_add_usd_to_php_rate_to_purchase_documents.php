<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsdToPhpRateToPurchaseDocuments extends Migration
{
    public function up()
    {
        Schema::table('purchase_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_documents', 'usd_to_php_rate')) {
                $table->decimal('usd_to_php_rate', 18, 6)->default(1)->after('exchange_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('purchase_documents', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_documents', 'usd_to_php_rate')) {
                $table->dropColumn('usd_to_php_rate');
            }
        });
    }
}
