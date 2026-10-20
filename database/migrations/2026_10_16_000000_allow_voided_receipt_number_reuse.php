<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AllowVoidedReceiptNumberReuse extends Migration
{
    public function up()
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX receipts_active_number_unique ON receipts (company_id, receipt_no) WHERE status <> 'voided'");
        } else {
            Schema::table('receipts', function (Blueprint $table) {
                $table->string('active_receipt_no')
                    ->nullable()->storedAs("CASE WHEN status = 'voided' THEN NULL ELSE receipt_no END");
                $table->unique(['company_id', 'active_receipt_no'], 'receipts_active_number_unique');
            });
        }

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'receipt_no']);
            $table->index(['company_id', 'receipt_no']);
        });
    }

    public function down()
    {
        // Restoring the original constraint requires resolving reused numbers first.
        Schema::table('receipts', function (Blueprint $table) {
            $table->unique(['company_id', 'receipt_no']);
            $table->dropIndex(['company_id', 'receipt_no']);
            $table->dropUnique('receipts_active_number_unique');
        });
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('receipts', function (Blueprint $table) {
                $table->dropColumn('active_receipt_no');
            });
        }
    }
}
