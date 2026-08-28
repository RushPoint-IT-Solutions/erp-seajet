<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpandSeajetCompanyTinBranchCode extends Migration
{
    public function up()
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'tin')) {
            DB::table('companies')
                ->where('code', 'SJ')
                ->where('tin', '000-143-896-000')
                ->update(['tin' => '000-143-896-00000', 'updated_at' => now()]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'tin')) {
            DB::table('companies')
                ->where('code', 'SJ')
                ->where('tin', '000-143-896-00000')
                ->update(['tin' => '000-143-896-000', 'updated_at' => now()]);
        }
    }
}
