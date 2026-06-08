<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApDocumentAttachments extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('document_attachments')) {
            Schema::create('document_attachments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('document_type');
                $table->unsignedBigInteger('document_id');
                $table->string('disk')->default('local');
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->index(['document_type', 'document_id']);
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'voucher_no')) {
                $table->string('voucher_no')->nullable()->after('payment_no');
            }
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'voucher_no')) {
                $table->dropColumn('voucher_no');
            }
        });

        Schema::dropIfExists('document_attachments');
    }
}
