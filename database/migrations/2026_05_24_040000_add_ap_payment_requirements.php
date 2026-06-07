<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApPaymentRequirements extends Migration
{
    public function up()
    {
        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->string('client_customer_id')->nullable()->after('supplier_id');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
            $table->decimal('withholding_tax_rate', 8, 4)->default(0)->after('tax_total');
            $table->decimal('withholding_tax_amount', 18, 4)->default(0)->after('withholding_tax_rate');
            $table->string('approval_status')->default('pending')->after('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('shipment_job_id')->nullable()->after('supplier_id');
            $table->string('bill_no')->nullable()->after('payment_no');
            $table->string('currency', 3)->default('PHP')->after('payment_method');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
            $table->string('bank_account')->nullable()->after('exchange_rate');
            $table->string('approval_status')->default('pending')->after('status');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'shipment_job_id',
                'bill_no',
                'currency',
                'exchange_rate',
                'bank_account',
                'approval_status',
            ]);
        });

        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->dropColumn([
                'client_customer_id',
                'exchange_rate',
                'withholding_tax_rate',
                'withholding_tax_amount',
                'approval_status',
            ]);
        });
    }
}
