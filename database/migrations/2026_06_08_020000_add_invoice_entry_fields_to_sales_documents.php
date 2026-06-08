<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceEntryFieldsToSalesDocuments extends Migration
{
    public function up()
    {
        Schema::table('sales_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_documents', 'mawb_no')) {
                $table->string('mawb_no')->nullable()->after('job_shipment_id');
            }
            if (!Schema::hasColumn('sales_documents', 'hawb_no')) {
                $table->string('hawb_no')->nullable()->after('mawb_no');
            }
            if (!Schema::hasColumn('sales_documents', 'date_shipped')) {
                $table->date('date_shipped')->nullable()->after('hawb_no');
            }
            if (!Schema::hasColumn('sales_documents', 'origin_dest')) {
                $table->string('origin_dest')->nullable()->after('date_shipped');
            }
            if (!Schema::hasColumn('sales_documents', 'commodity')) {
                $table->string('commodity')->nullable()->after('origin_dest');
            }
            if (!Schema::hasColumn('sales_documents', 'invoice_templates')) {
                $table->text('invoice_templates')->nullable()->after('template_code');
            }
        });
    }

    public function down()
    {
        Schema::table('sales_documents', function (Blueprint $table) {
            $table->dropColumn([
                'mawb_no',
                'hawb_no',
                'date_shipped',
                'origin_dest',
                'commodity',
                'invoice_templates',
            ]);
        });
    }
}
