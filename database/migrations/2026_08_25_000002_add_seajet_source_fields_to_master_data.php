<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeajetSourceFieldsToMasterData extends Migration
{
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'tax_code')) {
                $table->string('tax_code')->nullable()->after('type');
            }
            if (!Schema::hasColumn('accounts', 'atc_code')) {
                $table->string('atc_code')->nullable()->after('tax_code');
            }
        });

        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('items', 'tax_type')) {
                $table->string('tax_type')->nullable()->after('category');
            }
            if (!Schema::hasColumn('items', 'account_name')) {
                $table->string('account_name')->nullable()->after('tax_type');
            }
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $columns = array_values(array_filter(['description', 'tax_type', 'account_name'], function ($column) {
                return Schema::hasColumn('items', $column);
            }));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            $columns = array_values(array_filter(['tax_code', 'atc_code'], function ($column) {
                return Schema::hasColumn('accounts', $column);
            }));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
}
