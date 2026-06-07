<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateErpCoreTables extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('tin')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('base_currency', 3)->default('PHP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->boolean('is_base')->default(false);
            $table->timestamps();
        });

        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::create('account_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('normal_balance', 6);
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_category_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('account_category_id')->references('id')->on('account_categories');
            $table->foreign('parent_id')->references('id')->on('accounts');
        });

        Schema::create('tax_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->decimal('rate', 8, 4)->default(0);
            $table->string('tax_type');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('account_id')->references('id')->on('accounts');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('tin')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->decimal('credit_limit', 18, 4)->default(0);
            $table->string('payment_terms')->default('COD');
            $table->string('currency', 3)->default('PHP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('tin')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('payment_terms')->default('COD');
            $table->string('currency', 3)->default('PHP');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('code');
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit_of_measure')->default('pcs');
            $table->decimal('standard_cost', 18, 4)->default(0);
            $table->decimal('selling_price', 18, 4)->default(0);
            $table->decimal('reorder_level', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'sku']);
            $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('quantity_on_hand', 18, 4)->default(0);
            $table->decimal('average_cost', 18, 4)->default(0);
            $table->timestamps();
            $table->unique(['item_id', 'warehouse_id']);
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('journal_no');
            $table->date('journal_date');
            $table->string('source_module')->default('GL');
            $table->string('reference_no')->nullable();
            $table->text('memo')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'journal_no']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->string('description')->nullable();
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->timestamps();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries');
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });

        Schema::create('sales_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->string('document_type');
            $table->string('document_no');
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->string('customer_po_no')->nullable();
            $table->string('shipment_reference')->nullable();
            $table->string('currency', 3)->default('PHP');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('discount_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('freight_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->decimal('paid_total', 18, 4)->default(0);
            $table->string('status')->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'document_no']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('customer_id')->references('id')->on('customers');
        });

        Schema::create('sales_document_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sales_document_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->text('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('line_total', 18, 4)->default(0);
            $table->timestamps();
            $table->foreign('sales_document_id')->references('id')->on('sales_documents');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes');
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('receipt_no');
            $table->date('receipt_date');
            $table->string('payment_method');
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 18, 4)->default(0);
            $table->string('status')->default('posted');
            $table->timestamps();
            $table->unique(['company_id', 'receipt_no']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('customer_id')->references('id')->on('customers');
        });

        Schema::create('receipt_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('sales_document_id');
            $table->decimal('amount_applied', 18, 4)->default(0);
            $table->timestamps();
            $table->foreign('receipt_id')->references('id')->on('receipts');
            $table->foreign('sales_document_id')->references('id')->on('sales_documents');
        });

        Schema::create('purchase_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->string('document_type');
            $table->string('document_no');
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('PHP');
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->decimal('paid_total', 18, 4)->default(0);
            $table->string('status')->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'document_no']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });

        Schema::create('purchase_document_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_document_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->text('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('line_total', 18, 4)->default(0);
            $table->timestamps();
            $table->foreign('purchase_document_id')->references('id')->on('purchase_documents');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('payment_no');
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('check_no')->nullable();
            $table->decimal('amount', 18, 4)->default(0);
            $table->string('status')->default('posted');
            $table->timestamps();
            $table->unique(['company_id', 'payment_no']);
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_no');
            $table->string('currency', 3)->default('PHP');
            $table->decimal('book_balance', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('account_id')->references('id')->on('accounts');
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bank_account_id');
            $table->date('transaction_date');
            $table->string('transaction_type');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('item_id');
            $table->date('movement_date');
            $table->string('movement_type');
            $table->string('reference_no')->nullable();
            $table->decimal('quantity_in', 18, 4)->default(0);
            $table->decimal('quantity_out', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('item_id')->references('id')->on('items');
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['approvable_type', 'approvable_id']);
            $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->index(['commentable_type', 'commentable_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('was_successful')->default(false);
            $table->timestamp('logged_in_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('type')->default('info');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        $tables = [
            'notifications', 'login_logs', 'activity_logs', 'comments', 'attachments', 'approval_requests',
            'stock_movements', 'bank_transactions', 'bank_accounts', 'payments', 'purchase_document_lines',
            'purchase_documents', 'receipt_applications', 'receipts', 'sales_document_lines',
            'sales_documents', 'journal_entry_lines', 'journal_entries', 'inventory_balances',
            'items', 'warehouses', 'suppliers', 'customers', 'tax_codes', 'accounts',
            'account_categories', 'fiscal_years', 'currencies', 'branches', 'companies',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
}
