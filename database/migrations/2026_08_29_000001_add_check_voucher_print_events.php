<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckVoucherPrintEvents extends Migration
{
    public function up()
    {
        Schema::create('check_voucher_print_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_id');
            $table->json('payload_snapshot');
            $table->json('layout_snapshot');
            $table->string('payload_hash', 64);
            $table->unsignedInteger('printed_by')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['company_id', 'payment_id', 'created_at'], 'check_voucher_print_lookup');
        });
    }

    public function down()
    {
        Schema::dropIfExists('check_voucher_print_events');
    }
}
