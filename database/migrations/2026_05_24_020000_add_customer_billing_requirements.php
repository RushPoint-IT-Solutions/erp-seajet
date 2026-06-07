<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomerBillingRequirements extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_id')->nullable()->after('code');
            $table->unsignedSmallInteger('credit_term_days')->default(30)->after('payment_terms');
            $table->string('default_class_code')->default('MANILA')->after('currency');
            $table->string('default_ar_account_code')->default('AR_TRADE_BI')->after('default_class_code');
        });

        Schema::table('sales_documents', function (Blueprint $table) {
            $table->string('customer_id_text')->nullable()->after('customer_id');
            $table->string('job_shipment_id')->nullable()->after('shipment_reference');
            $table->string('vat_classification')->default('12%')->after('job_shipment_id');
            $table->string('class_code')->default('MANILA')->after('vat_classification');
            $table->string('ar_account_code')->default('AR_TRADE_BI')->after('class_code');
            $table->string('template_code')->nullable()->after('ar_account_code');
            $table->boolean('uses_pre_printed_form')->default(true)->after('template_code');
            $table->unsignedSmallInteger('credit_term_days')->default(30)->after('due_date');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->string('receipt_type')->default('official_receipt')->after('receipt_no');
            $table->string('customer_id_text')->nullable()->after('customer_id');
            $table->string('job_shipment_id')->nullable()->after('customer_id_text');
            $table->string('class_code')->default('MANILA')->after('job_shipment_id');
            $table->string('currency', 3)->default('PHP')->after('class_code');
            $table->string('template_code')->nullable()->after('status');
            $table->boolean('uses_pre_printed_form')->default(true)->after('template_code');
        });

        Schema::create('ar_account_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('currency', 3);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('document_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code', 'currency']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('account_id')->references('id')->on('accounts');
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('document_type');
            $table->boolean('uses_pre_printed_form')->default(true);
            $table->string('paper_size')->default('A4');
            $table->json('field_map')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('ar_account_mappings');

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_type',
                'customer_id_text',
                'job_shipment_id',
                'class_code',
                'currency',
                'template_code',
                'uses_pre_printed_form',
            ]);
        });

        Schema::table('sales_documents', function (Blueprint $table) {
            $table->dropColumn([
                'customer_id_text',
                'job_shipment_id',
                'vat_classification',
                'class_code',
                'ar_account_code',
                'template_code',
                'uses_pre_printed_form',
                'credit_term_days',
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_id',
                'credit_term_days',
                'default_class_code',
                'default_ar_account_code',
            ]);
        });
    }
}
