<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHiddenFieldsToPrintLayouts extends Migration
{
    public function up()
    {
        if (Schema::hasTable('print_layouts') && !Schema::hasColumn('print_layouts', 'hidden_fields')) {
            Schema::table('print_layouts', function (Blueprint $table) {
                $table->json('hidden_fields')->nullable()->after('custom_fields');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('print_layouts') && Schema::hasColumn('print_layouts', 'hidden_fields')) {
            Schema::table('print_layouts', function (Blueprint $table) {
                $table->dropColumn('hidden_fields');
            });
        }
    }
}
