<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpeningBalanceImportTables extends Migration
{
    public function up()
    {
        Schema::create('opening_balance_import_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('original_filename');
            $table->string('file_hash', 64);
            $table->date('posting_date');
            $table->text('account_mapping')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('status')->default('posted');
            $table->unsignedInteger('imported_by')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'file_hash'], 'opening_balance_batch_file_lookup');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries');
        });

        Schema::create('opening_balance_import_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('company_id');
            $table->string('file_hash', 64);
            $table->string('sheet_name');
            $table->unsignedInteger('row_number');
            $table->string('balance_type', 30);
            $table->string('branch_name');
            $table->string('party_name');
            $table->date('source_date');
            $table->string('reference_no');
            $table->string('currency', 3)->default('PHP');
            $table->decimal('foreign_amount', 18, 4)->nullable();
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('base_amount', 18, 4);
            $table->unsignedBigInteger('journal_entry_id');
            $table->timestamps();
            $table->unique(
                ['company_id', 'file_hash', 'sheet_name', 'row_number'],
                'opening_balance_import_row_unique'
            );
            $table->foreign('batch_id')->references('id')->on('opening_balance_import_batches');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries');
        });
    }

    public function down()
    {
        Schema::dropIfExists('opening_balance_import_rows');
        Schema::dropIfExists('opening_balance_import_batches');
    }
}
