<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArAccountCodeToSalesDocumentLines extends Migration
{
    public function up(): void
    {
        Schema::table('sales_document_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_document_lines', 'ar_account_code')) {
                $table->string('ar_account_code')->nullable()->after('class_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_document_lines', function (Blueprint $table) {
            if (Schema::hasColumn('sales_document_lines', 'ar_account_code')) {
                $table->dropColumn('ar_account_code');
            }
        });
    }
}
