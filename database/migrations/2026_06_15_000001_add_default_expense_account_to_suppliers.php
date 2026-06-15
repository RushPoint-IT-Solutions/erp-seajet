<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultExpenseAccountToSuppliers extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'default_expense_account_id')) {
                    $table->unsignedBigInteger('default_expense_account_id')->nullable()->after('default_ap_account');
                }
            });
        }

        if (Schema::hasTable('vendor_bill_lines')) {
            Schema::table('vendor_bill_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('vendor_bill_lines', 'account_id')) {
                    $table->unsignedBigInteger('account_id')->nullable()->after('purchase_document_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_bill_lines')) {
            Schema::table('vendor_bill_lines', function (Blueprint $table) {
                if (Schema::hasColumn('vendor_bill_lines', 'account_id')) {
                    $table->dropColumn('account_id');
                }
            });
        }

        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (Schema::hasColumn('suppliers', 'default_expense_account_id')) {
                    $table->dropColumn('default_expense_account_id');
                }
            });
        }
    }
}
