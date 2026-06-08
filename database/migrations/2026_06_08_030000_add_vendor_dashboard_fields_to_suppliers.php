<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVendorDashboardFieldsToSuppliers extends Migration
{
    public function up()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('tax_classification');
            }
            if (!Schema::hasColumn('suppliers', 'default_class_code')) {
                $table->string('default_class_code')->default('MANILA')->after('preferred_payment_method');
            }
            if (!Schema::hasColumn('suppliers', 'default_ap_account')) {
                $table->string('default_ap_account')->nullable()->after('default_class_code');
            }
            if (!Schema::hasColumn('suppliers', 'withholding_tax')) {
                $table->string('withholding_tax')->default('0%')->after('default_ap_account');
            }
        });
    }

    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'contact_person',
                'default_class_code',
                'default_ap_account',
                'withholding_tax',
            ]);
        });
    }
}
