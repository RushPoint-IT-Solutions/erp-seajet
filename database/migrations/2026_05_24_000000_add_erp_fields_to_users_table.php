<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddErpFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            $table->string('role')->default('viewer')->after('password');
            $table->string('department')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('department');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company_id', 'branch_id', 'role', 'department', 'is_active', 'last_login_at']);
        });
    }
}
