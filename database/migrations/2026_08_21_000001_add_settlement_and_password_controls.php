<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AddSettlementAndPasswordControls extends Migration
{
    public function up()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_method');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
            $table->decimal('cwt_amount', 18, 4)->default(0)->after('amount');
            $table->string('cwt_certificate_no')->nullable()->after('cwt_amount');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_method');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            if (Hash::check('password', $user->password)) {
                DB::table('users')->where('id', $user->id)->update([
                    'must_change_password' => true,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('bank_account_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['bank_account_id', 'exchange_rate', 'cwt_amount', 'cwt_certificate_no']);
        });
    }
}
