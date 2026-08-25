<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DisableForcedPasswordChanges extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'must_change_password')) {
            return;
        }

        DB::table('users')
            ->where('must_change_password', true)
            ->update([
                'must_change_password' => false,
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        // Forced password changes are intentionally not restored.
    }
}
