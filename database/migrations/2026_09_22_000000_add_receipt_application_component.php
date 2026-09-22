<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceiptApplicationComponent extends Migration
{
    public function up()
    {
        Schema::table('receipt_applications', function (Blueprint $table) {
            $table->string('component', 3)->nullable()->after('sales_document_id');
        });
    }

    public function down()
    {
        Schema::table('receipt_applications', function (Blueprint $table) {
            $table->dropColumn('component');
        });
    }
}
