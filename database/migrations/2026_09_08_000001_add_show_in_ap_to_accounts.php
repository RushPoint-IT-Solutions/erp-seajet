<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddShowInApToAccounts extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('accounts', 'show_in_ap')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->boolean('show_in_ap')->default(false)->after('type');
            });

            DB::table('accounts')
                ->whereIn('type', ['Expense', 'Other Expense'])
                ->update(['show_in_ap' => true]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('accounts', 'show_in_ap')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('show_in_ap');
            });
        }
    }
}
