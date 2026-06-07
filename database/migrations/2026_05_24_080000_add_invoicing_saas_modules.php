<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoicingSaasModules extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('company_users')) {
            Schema::create('company_users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedInteger('user_id');
                $table->string('role')->default('viewer');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'user_id']);
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        if (!Schema::hasTable('client_contacts')) {
            Schema::create('client_contacts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('customer_id');
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('position')->nullable();
                $table->string('language', 10)->default('en');
                $table->boolean('receives_invoices')->default(true);
                $table->boolean('receives_reminders')->default(true);
                $table->timestamps();
                $table->foreign('customer_id')->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('service_products')) {
            Schema::create('service_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('unit_price', 18, 4)->default(0);
                $table->string('currency', 3)->default('PHP');
                $table->unsignedBigInteger('tax_code_id')->nullable();
                $table->unsignedBigInteger('revenue_account_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'code']);
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('tax_code_id')->references('id')->on('tax_codes');
                $table->foreign('revenue_account_id')->references('id')->on('accounts');
            });
        }

        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('quote_no');
                $table->string('shipment_job_id')->nullable();
                $table->date('quote_date');
                $table->date('valid_until')->nullable();
                $table->string('currency', 3)->default('PHP');
                $table->decimal('subtotal', 18, 4)->default(0);
                $table->decimal('tax_total', 18, 4)->default(0);
                $table->decimal('grand_total', 18, 4)->default(0);
                $table->string('status')->default('draft');
                $table->string('approval_token')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'quote_no']);
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('customer_id')->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('quote_items')) {
            Schema::create('quote_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('quote_id');
                $table->text('description');
                $table->decimal('quantity', 18, 4)->default(1);
                $table->decimal('unit_price', 18, 4)->default(0);
                $table->decimal('line_total', 18, 4)->default(0);
                $table->timestamps();
                $table->foreign('quote_id')->references('id')->on('quotes');
            });
        }

        if (!Schema::hasTable('recurring_invoice_templates')) {
            Schema::create('recurring_invoice_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('template_no');
                $table->string('frequency')->default('monthly');
                $table->date('starts_on');
                $table->date('next_run_on');
                $table->integer('remaining_cycles')->nullable();
                $table->integer('due_days')->default(30);
                $table->boolean('auto_email')->default(true);
                $table->boolean('auto_bill')->default(false);
                $table->string('status')->default('draft');
                $table->timestamp('last_generated_at')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'template_no']);
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('customer_id')->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('customer_payment_methods')) {
            Schema::create('customer_payment_methods', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('gateway')->default('stripe');
                $table->string('gateway_customer_id')->nullable();
                $table->string('gateway_payment_method_id');
                $table->string('brand')->nullable();
                $table->string('last_four', 4)->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('customer_id')->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('online_payment_events')) {
            Schema::create('online_payment_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('gateway')->default('stripe');
                $table->string('event_id')->unique();
                $table->string('event_type');
                $table->string('reference_no')->nullable();
                $table->decimal('amount', 18, 4)->default(0);
                $table->string('currency', 3)->nullable();
                $table->string('status')->default('received');
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('expense_no');
                $table->string('shipment_job_id')->nullable();
                $table->date('expense_date');
                $table->string('category');
                $table->string('currency', 3)->default('PHP');
                $table->decimal('amount', 18, 4)->default(0);
                $table->decimal('tax_amount', 18, 4)->default(0);
                $table->boolean('is_billable')->default(false);
                $table->boolean('is_rebilled')->default(false);
                $table->string('receipt_path')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'expense_no']);
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('supplier_id')->references('id')->on('suppliers');
                $table->foreign('customer_id')->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('supplier_id');
                $table->string('po_no');
                $table->date('po_date');
                $table->string('currency', 3)->default('PHP');
                $table->decimal('grand_total', 18, 4)->default(0);
                $table->string('status')->default('draft');
                $table->timestamps();
                $table->unique(['company_id', 'po_no']);
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('supplier_id')->references('id')->on('suppliers');
            });
        }

        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('project_no');
                $table->string('name');
                $table->string('shipment_job_id')->nullable();
                $table->decimal('hourly_rate', 18, 4)->default(0);
                $table->string('status')->default('active');
                $table->timestamps();
                $table->unique(['company_id', 'project_no']);
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('customer_id')->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('project_tasks')) {
            Schema::create('project_tasks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('project_id');
                $table->string('title');
                $table->string('status')->default('open');
                $table->date('due_date')->nullable();
                $table->boolean('is_billable')->default(true);
                $table->timestamps();
                $table->foreign('project_id')->references('id')->on('projects');
            });
        }

        if (!Schema::hasTable('time_entries')) {
            Schema::create('time_entries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('project_task_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->decimal('hours', 10, 4)->default(0);
                $table->decimal('rate', 18, 4)->default(0);
                $table->boolean('is_invoiced')->default(false);
                $table->timestamps();
                $table->foreign('project_task_id')->references('id')->on('project_tasks');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        if (!Schema::hasTable('client_portal_tokens')) {
            Schema::create('client_portal_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('token')->unique();
                $table->string('purpose')->default('invoice_view');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('customer_id')->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->string('template_key');
                $table->string('subject');
                $table->text('body');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'template_key']);
                $table->foreign('company_id')->references('id')->on('companies');
            });
        }

        if (!Schema::hasTable('reminder_rules')) {
            Schema::create('reminder_rules', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->integer('days_offset')->default(0);
                $table->string('anchor')->default('due_date');
                $table->integer('repeat_days')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->foreign('company_id')->references('id')->on('companies');
            });
        }

        if (!Schema::hasTable('api_access_tokens')) {
            Schema::create('api_access_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('name');
                $table->string('token_hash');
                $table->json('scopes')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->foreign('company_id')->references('id')->on('companies');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        if (!Schema::hasTable('webhook_subscriptions')) {
            Schema::create('webhook_subscriptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->string('event_name');
                $table->string('target_url');
                $table->string('secret')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->foreign('company_id')->references('id')->on('companies');
            });
        }

        if (!Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('webhook_subscription_id');
                $table->string('event_id');
                $table->integer('response_code')->nullable();
                $table->integer('attempts')->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
                $table->foreign('webhook_subscription_id')->references('id')->on('webhook_subscriptions');
            });
        }

        if (!Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id');
                $table->string('setting_key');
                $table->text('setting_value')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'setting_key']);
                $table->foreign('company_id')->references('id')->on('companies');
            });
        }
    }

    public function down()
    {
        $tables = [
            'company_settings',
            'webhook_deliveries',
            'webhook_subscriptions',
            'api_access_tokens',
            'reminder_rules',
            'email_templates',
            'client_portal_tokens',
            'time_entries',
            'project_tasks',
            'projects',
            'purchase_orders',
            'expenses',
            'online_payment_events',
            'customer_payment_methods',
            'recurring_invoice_templates',
            'quote_items',
            'quotes',
            'service_products',
            'client_contacts',
            'company_users',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
}
