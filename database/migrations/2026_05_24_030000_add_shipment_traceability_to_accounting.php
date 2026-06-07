<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShipmentTraceabilityToAccounting extends Migration
{
    public function up()
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('shipment_job_id')->nullable()->after('journal_date');
        });

        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->string('shipment_job_id')->nullable()->after('document_no');
        });
    }

    public function down()
    {
        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->dropColumn('shipment_job_id');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('shipment_job_id');
        });
    }
}
