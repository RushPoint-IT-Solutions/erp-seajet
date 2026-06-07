<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMultiCurrencyMasterRequirements extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('tax_classification')->default('12% VAT')->after('tin');
            $table->string('credit_status')->default('active')->after('credit_limit');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('tax_classification')->default('12% VAT')->after('tin');
            $table->smallInteger('payment_term_days')->default(30)->after('payment_terms');
            $table->string('preferred_payment_method')->nullable()->after('currency');
        });

        Schema::create('currency_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('source_currency', 3);
            $table->string('target_currency', 3);
            $table->date('rate_date');
            $table->decimal('rate', 18, 6);
            $table->string('rate_source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'source_currency', 'target_currency', 'rate_date'], 'currency_rates_unique_rate');
        });

        Schema::create('fx_gain_loss_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('reference_no');
            $table->string('shipment_job_id')->nullable();
            $table->string('gain_loss_type');
            $table->string('currency', 3);
            $table->decimal('foreign_amount', 18, 4)->default(0);
            $table->decimal('document_rate', 18, 6)->default(1);
            $table->decimal('revaluation_rate', 18, 6)->default(1);
            $table->decimal('base_amount', 18, 4)->default(0);
            $table->decimal('gain_loss_amount', 18, 4)->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fx_gain_loss_entries');
        Schema::dropIfExists('currency_rates');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['tax_classification', 'payment_term_days', 'preferred_payment_method']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tax_classification', 'credit_status']);
        });
    }
}
