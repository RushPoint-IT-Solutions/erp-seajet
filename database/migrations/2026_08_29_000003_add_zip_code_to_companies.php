<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZipCodeToCompanies extends Migration
{
    public function up()
    {
        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'zip_code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('zip_code', 10)->nullable()->after('address');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'zip_code')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('zip_code');
            });
        }
    }
}
