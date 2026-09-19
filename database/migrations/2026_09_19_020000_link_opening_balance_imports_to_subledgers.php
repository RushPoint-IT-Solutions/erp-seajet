<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkOpeningBalanceImportsToSubledgers extends Migration
{
    public function up()
    {
        Schema::table('sales_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('opening_balance_control_account_id')->nullable();
            $table->foreign('opening_balance_control_account_id', 'sales_documents_opening_control_fk')
                ->references('id')->on('accounts');
        });

        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('opening_balance_control_account_id')->nullable();
            $table->foreign('opening_balance_control_account_id', 'purchase_documents_opening_control_fk')
                ->references('id')->on('accounts');
        });

        Schema::table('opening_balance_import_rows', function (Blueprint $table) {
            $table->string('subledger_document_no')->nullable();
            $table->unsignedBigInteger('sales_document_id')->nullable();
            $table->unsignedBigInteger('purchase_document_id')->nullable();
            $table->foreign('sales_document_id', 'opening_import_sales_document_fk')
                ->references('id')->on('sales_documents')->onDelete('set null');
            $table->foreign('purchase_document_id', 'opening_import_purchase_document_fk')
                ->references('id')->on('purchase_documents')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('opening_balance_import_rows', function (Blueprint $table) {
            $table->dropForeign('opening_import_sales_document_fk');
            $table->dropForeign('opening_import_purchase_document_fk');
            $table->dropColumn(['subledger_document_no', 'sales_document_id', 'purchase_document_id']);
        });

        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->dropForeign('purchase_documents_opening_control_fk');
            $table->dropColumn('opening_balance_control_account_id');
        });

        Schema::table('sales_documents', function (Blueprint $table) {
            $table->dropForeign('sales_documents_opening_control_fk');
            $table->dropColumn('opening_balance_control_account_id');
        });
    }
}
