<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestrictApAccountsToExpenseTypes extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('accounts', 'show_in_ap')) {
            DB::table('accounts')
                ->whereNotIn('type', ['Expense', 'Other Expense', 'Other Current Asset', 'Fixed Asset', 'Other Asset'])
                ->update(['show_in_ap' => false]);
        }
    }

    public function down()
    {
        // Account visibility is user-controlled and is not safely inferred on rollback.
    }
}
