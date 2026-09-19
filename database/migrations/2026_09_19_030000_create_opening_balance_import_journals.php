<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpeningBalanceImportJournals extends Migration
{
    public function up()
    {
        Schema::create('opening_balance_import_journals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('company_id');
            $table->date('source_date');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'source_date'], 'opening_balance_batch_date_unique');
            $table->foreign('batch_id')->references('id')->on('opening_balance_import_batches')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('opening_balance_import_journals');
    }
}
