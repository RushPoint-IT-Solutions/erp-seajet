<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Migrations\Migration;

class EnsureAdminUserForAuth extends Migration
{
    public function up()
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@seajet.test'],
            [
                'name' => 'ERP Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department' => 'Accounting',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down()
    {
        //
    }
}
