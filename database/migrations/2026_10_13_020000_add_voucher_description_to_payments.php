<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVoucherDescriptionToPayments extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'voucher_description')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->text('voucher_description')->nullable()->after('amount_words_snapshot');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'voucher_description')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('voucher_description');
            });
        }
    }
}
