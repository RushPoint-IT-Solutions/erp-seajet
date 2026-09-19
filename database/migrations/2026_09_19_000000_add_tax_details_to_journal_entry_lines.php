<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaxDetailsToJournalEntryLines extends Migration
{
    public function up()
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->string('tax_category', 20)->nullable()->after('description');
            $table->decimal('tax_amount', 18, 4)->default(0)->after('tax_category');
            $table->decimal('withholding_tax_rate', 8, 4)->default(0)->after('tax_amount');
            $table->decimal('withholding_tax_amount', 18, 4)->default(0)->after('withholding_tax_rate');
        });
    }

    public function down()
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropColumn([
                'tax_category',
                'tax_amount',
                'withholding_tax_rate',
                'withholding_tax_amount',
            ]);
        });
    }
}
