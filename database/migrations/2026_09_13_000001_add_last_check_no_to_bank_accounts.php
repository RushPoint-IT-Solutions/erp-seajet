<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLastCheckNoToBankAccounts extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('bank_accounts', 'last_check_no')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->string('last_check_no', 100)->nullable()->after('account_no');
            });
        }

        DB::table('bank_accounts')->orderBy('id')->get()->each(function ($bank) {
            $lastCheckNo = null;

            if (Schema::hasTable('check_issuances')) {
                $lastCheckNo = DB::table('check_issuances')
                    ->where('company_id', $bank->company_id)
                    ->where('bank_account_id', $bank->id)
                    ->whereNotNull('check_no')
                    ->where('check_no', '<>', '')
                    ->orderBy('id', 'desc')
                    ->value('check_no');
            }

            if (!$lastCheckNo && Schema::hasTable('payments')) {
                $lastCheckNo = DB::table('payments')
                    ->where('company_id', $bank->company_id)
                    ->whereNotNull('check_no')
                    ->where('check_no', '<>', '')
                    ->where(function ($query) use ($bank) {
                        $query->where('bank_account_id', $bank->id)
                            ->orWhere('bank_account', $bank->account_no)
                            ->orWhere('bank_account', $bank->account_name)
                            ->orWhere('bank_account', 'like', '%'.$bank->account_no.'%');
                    })
                    ->orderBy('id', 'desc')
                    ->value('check_no');
            }

            if ($lastCheckNo) {
                DB::table('bank_accounts')->where('id', $bank->id)->update([
                    'last_check_no' => trim((string) $lastCheckNo),
                ]);
            }
        });
    }

    public function down()
    {
        if (Schema::hasColumn('bank_accounts', 'last_check_no')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->dropColumn('last_check_no');
            });
        }
    }
}
