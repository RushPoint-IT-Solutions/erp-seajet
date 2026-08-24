<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountingPostingControls extends Migration
{
    public function up()
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->unsignedInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'starts_on', 'ends_on'], 'accounting_period_unique_range');
            $table->index(['company_id', 'status']);
        });

        Schema::create('accounting_control_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('control_key');
            $table->unsignedBigInteger('account_id');
            $table->timestamps();
            $table->unique(['company_id', 'control_key'], 'accounting_control_unique_key');
            $table->foreign('account_id')->references('id')->on('accounts');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('source_module');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('posting_type')->default('original')->after('source_id');
            $table->unsignedBigInteger('reversed_entry_id')->nullable()->after('posting_type');
            $table->text('reversal_reason')->nullable()->after('reversed_entry_id');
            $table->string('posting_hash', 64)->nullable()->after('reversal_reason');
            $table->unsignedInteger('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->index(['company_id', 'source_type', 'source_id'], 'journal_source_lookup');
            $table->unique(
                ['company_id', 'source_type', 'source_id', 'posting_type'],
                'journal_source_posting_unique'
            );
        });

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->string('currency', 3)->default('PHP')->after('description');
            $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
            $table->decimal('foreign_debit', 18, 4)->default(0)->after('exchange_rate');
            $table->decimal('foreign_credit', 18, 4)->default(0)->after('foreign_debit');
            $table->string('shipment_job_id')->nullable()->after('supplier_id');
            $table->string('class_code')->nullable()->after('shipment_job_id');
        });

        foreach (['sales_documents', 'purchase_documents', 'receipts', 'payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->string('posting_status')->default('unposted');
                $table->unsignedInteger('posted_by')->nullable();
                $table->timestamp('accounting_posted_at')->nullable();
            });
        }

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('transaction_no');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->unsignedBigInteger('journal_entry_id')->nullable()->after('source_id');
            $table->unique(['source_type', 'source_id'], 'bank_transaction_source_unique');
        });

        Schema::table('client_portal_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_document_id')->nullable()->after('customer_id');
            $table->index('sales_document_id');
        });
    }

    public function down()
    {
        Schema::table('client_portal_tokens', function (Blueprint $table) {
            $table->dropIndex(['sales_document_id']);
            $table->dropColumn('sales_document_id');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropUnique('bank_transaction_source_unique');
            $table->dropColumn(['source_type', 'source_id', 'journal_entry_id']);
        });

        foreach (['payments', 'receipts', 'purchase_documents', 'sales_documents'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['journal_entry_id', 'posting_status', 'posted_by', 'accounting_posted_at']);
            });
        }

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'exchange_rate',
                'foreign_debit',
                'foreign_credit',
                'shipment_job_id',
                'class_code',
            ]);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_source_posting_unique');
            $table->dropIndex('journal_source_lookup');
            $table->dropColumn([
                'source_type',
                'source_id',
                'posting_type',
                'reversed_entry_id',
                'reversal_reason',
                'posting_hash',
                'approved_by',
                'approved_at',
            ]);
        });

        Schema::dropIfExists('accounting_control_accounts');
        Schema::dropIfExists('accounting_periods');
    }
}
