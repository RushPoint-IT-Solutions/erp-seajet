<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBir2307Representatives extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('bir_2307_payor_representative_name')->nullable()->after('address');
            $table->string('bir_2307_payor_representative_title')->nullable()->after('bir_2307_payor_representative_name');
            $table->string('bir_2307_payor_representative_tin', 30)->nullable()->after('bir_2307_payor_representative_title');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('bir_2307_payee_representative_name')->nullable()->after('address');
            $table->string('bir_2307_payee_representative_title')->nullable()->after('bir_2307_payee_representative_name');
            $table->string('bir_2307_payee_representative_tin', 30)->nullable()->after('bir_2307_payee_representative_title');
        });

        DB::table('companies')->where('code', 'SJ')->update([
            'bir_2307_payor_representative_name' => 'Gina C. Boloy',
            'bir_2307_payor_representative_title' => 'Finance Manager',
            'bir_2307_payor_representative_tin' => '293-263-579-000',
        ]);
    }

    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'bir_2307_payee_representative_name',
                'bir_2307_payee_representative_title',
                'bir_2307_payee_representative_tin',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'bir_2307_payor_representative_name',
                'bir_2307_payor_representative_title',
                'bir_2307_payor_representative_tin',
            ]);
        });
    }
}
