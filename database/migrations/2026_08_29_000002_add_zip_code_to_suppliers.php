<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZipCodeToSuppliers extends Migration
{
    public function up()
    {
        if (Schema::hasTable('suppliers') && !Schema::hasColumn('suppliers', 'zip_code')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('zip_code', 10)->nullable()->after('address');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'zip_code')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('zip_code');
            });
        }
    }
}
