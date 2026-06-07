<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankingRequirements extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('branch_name')->nullable()->after('bank_name');
            $table->string('swift_code')->nullable()->after('account_no');
            $table->decimal('bank_balance', 18, 4)->default(0)->after('book_balance');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('transaction_no')->nullable()->after('id');
            $table->string('shipment_job_id')->nullable()->after('reference_no');
            $table->string('currency', 3)->default('PHP')->after('shipment_job_id');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
            $table->decimal('foreign_amount', 18, 4)->default(0)->after('exchange_rate');
            $table->decimal('base_amount', 18, 4)->default(0)->after('foreign_amount');
            $table->timestamp('reconciled_at')->nullable()->after('is_reconciled');
        });
    }

    public function down()
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_no',
                'shipment_job_id',
                'currency',
                'exchange_rate',
                'foreign_amount',
                'base_amount',
                'reconciled_at',
            ]);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['branch_name', 'swift_code', 'bank_balance']);
        });
    }
}
