<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixErpDatabaseCompleteness extends Migration
{
    public function up()
    {
        $this->fixSalesDocuments();
        $this->fixPurchaseDocuments();
        $this->fixExpenseStorage();
        $this->createTemplateAndControlTables();
        $this->createSecurityTables();
        $this->seedNumberSeries();
    }

    private function fixSalesDocuments()
    {
        Schema::table('sales_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_documents', 'transaction_no')) {
                $table->string('transaction_no')->nullable()->after('document_no');
            }
            if (!Schema::hasColumn('sales_documents', 'soa_no')) {
                $table->string('soa_no')->nullable()->after('transaction_no');
            }
            if (!Schema::hasColumn('sales_documents', 'invoice_to')) {
                $table->text('invoice_to')->nullable()->after('customer_po_no');
            }
            if (!Schema::hasColumn('sales_documents', 'consignee')) {
                $table->text('consignee')->nullable()->after('invoice_to');
            }
            if (!Schema::hasColumn('sales_documents', 'customer_message')) {
                $table->text('customer_message')->nullable()->after('remarks');
            }
            if (!Schema::hasColumn('sales_documents', 'memo')) {
                $table->text('memo')->nullable()->after('customer_message');
            }
            if (!Schema::hasColumn('sales_documents', 'customer_tax_code')) {
                $table->string('customer_tax_code')->nullable()->after('memo');
            }
            if (!Schema::hasColumn('sales_documents', 'invoice_amount')) {
                $table->decimal('invoice_amount', 18, 4)->default(0)->after('grand_total');
            }
            if (!Schema::hasColumn('sales_documents', 'total_payment')) {
                $table->decimal('total_payment', 18, 4)->default(0)->after('paid_total');
            }
            if (!Schema::hasColumn('sales_documents', 'balance_due')) {
                $table->decimal('balance_due', 18, 4)->default(0)->after('total_payment');
            }
        });

        Schema::table('sales_document_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_document_lines', 'item_name')) {
                $table->string('item_name')->nullable()->after('item_id');
            }
            if (!Schema::hasColumn('sales_document_lines', 'class_code')) {
                $table->string('class_code')->nullable()->after('line_total');
            }
            if (!Schema::hasColumn('sales_document_lines', 'tax_category')) {
                $table->string('tax_category')->nullable()->after('class_code');
            }
        });
    }

    private function fixPurchaseDocuments()
    {
        Schema::table('purchase_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_documents', 'transaction_type')) {
                $table->string('transaction_type')->default('Bill')->after('document_type');
            }
            if (!Schema::hasColumn('purchase_documents', 'ap_account')) {
                $table->string('ap_account')->nullable()->after('supplier_id');
            }
            if (!Schema::hasColumn('purchase_documents', 'bill_received')) {
                $table->boolean('bill_received')->default(false)->after('ap_account');
            }
            if (!Schema::hasColumn('purchase_documents', 'supplier_address')) {
                $table->text('supplier_address')->nullable()->after('bill_received');
            }
            if (!Schema::hasColumn('purchase_documents', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('document_no');
            }
            if (!Schema::hasColumn('purchase_documents', 'terms')) {
                $table->string('terms')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('purchase_documents', 'gross_amount')) {
                $table->decimal('gross_amount', 18, 4)->default(0)->after('currency');
            }
            if (!Schema::hasColumn('purchase_documents', 'php_amount')) {
                $table->decimal('php_amount', 18, 4)->default(0)->after('exchange_rate');
            }
            if (!Schema::hasColumn('purchase_documents', 'net_amount')) {
                $table->decimal('net_amount', 18, 4)->default(0)->after('withholding_tax_amount');
            }
            if (!Schema::hasColumn('purchase_documents', 'memo')) {
                $table->text('memo')->nullable()->after('remarks');
            }
        });

        Schema::table('purchase_document_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_document_lines', 'account_name')) {
                $table->string('account_name')->nullable()->after('item_id');
            }
            if (!Schema::hasColumn('purchase_document_lines', 'tax_category')) {
                $table->string('tax_category')->nullable()->after('tax_code_id');
            }
            if (!Schema::hasColumn('purchase_document_lines', 'memo')) {
                $table->text('memo')->nullable()->after('line_total');
            }
            if (!Schema::hasColumn('purchase_document_lines', 'customer_job_id')) {
                $table->string('customer_job_id')->nullable()->after('memo');
            }
            if (!Schema::hasColumn('purchase_document_lines', 'is_billable')) {
                $table->boolean('is_billable')->default(false)->after('customer_job_id');
            }
            if (!Schema::hasColumn('purchase_document_lines', 'class_code')) {
                $table->string('class_code')->nullable()->after('is_billable');
            }
        });
    }

    private function fixExpenseStorage()
    {
        if (!Schema::hasTable('vendor_bill_lines')) {
            Schema::create('vendor_bill_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('purchase_document_id')->nullable();
                $table->string('account_name')->nullable();
                $table->string('tax_category')->nullable();
                $table->decimal('amount', 18, 4)->default(0);
                $table->text('memo')->nullable();
                $table->string('customer_job_id')->nullable();
                $table->boolean('is_billable')->default(false);
                $table->string('class_code')->nullable();
                $table->timestamps();
                $table->foreign('purchase_document_id')->references('id')->on('purchase_documents');
            });
        }

        if (!Schema::hasTable('expense_lines')) {
            Schema::create('expense_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('expense_id');
                $table->string('account_name')->nullable();
                $table->string('tax_category')->nullable();
                $table->decimal('amount', 18, 4)->default(0);
                $table->text('memo')->nullable();
                $table->string('customer_job_id')->nullable();
                $table->boolean('is_billable')->default(false);
                $table->string('class_code')->nullable();
                $table->timestamps();
                $table->foreign('expense_id')->references('id')->on('expenses');
            });
        }

        if (!Schema::hasTable('payment_applications')) {
            Schema::create('payment_applications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('purchase_document_id')->nullable();
                $table->decimal('amount_applied', 18, 4)->default(0);
                $table->timestamps();
                $table->foreign('payment_id')->references('id')->on('payments');
                $table->foreign('purchase_document_id')->references('id')->on('purchase_documents');
            });
        }
    }

    private function createTemplateAndControlTables()
    {
        if (!Schema::hasTable('print_layouts')) {
            Schema::create('print_layouts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('document_type');
                $table->string('layout_name')->default('Default');
                $table->string('paper_size')->default('letter');
                $table->unsignedInteger('paper_width')->default(816);
                $table->unsignedInteger('paper_height')->default(1056);
                $table->text('template_url')->nullable();
                $table->boolean('print_template')->default(false);
                $table->json('positions')->nullable();
                $table->json('field_styles')->nullable();
                $table->json('custom_fields')->nullable();
                $table->boolean('is_default')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'document_type', 'layout_name'], 'print_layout_unique_name');
            });
        }

        if (!Schema::hasTable('number_series')) {
            Schema::create('number_series', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('document_type');
                $table->string('prefix')->nullable();
                $table->unsignedInteger('current_number')->default(0);
                $table->unsignedTinyInteger('padding')->default(5);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'document_type']);
            });
        }

        if (!Schema::hasTable('document_status_histories')) {
            Schema::create('document_status_histories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('document_type');
                $table->string('document_no');
                $table->string('old_status')->nullable();
                $table->string('new_status');
                $table->text('remarks')->nullable();
                $table->unsignedInteger('changed_by')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createSecurityTables()
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'slug']);
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('module');
                $table->string('action');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
                $table->primary(['role_id', 'permission_id']);
                $table->foreign('role_id')->references('id')->on('roles');
                $table->foreign('permission_id')->references('id')->on('permissions');
            });
        }
    }

    private function seedNumberSeries()
    {
        if (!Schema::hasTable('number_series')) {
            return;
        }

        $companyIds = Schema::hasTable('companies') ? DB::table('companies')->pluck('id')->all() : [null];
        if (empty($companyIds)) {
            $companyIds = [null];
        }

        $series = [
            ['billing_invoice', 'BI-', 1031, 6],
            ['statement_of_account', 'SOA-', 7187, 5],
            ['statement_of_account_a', 'SOAA-', 907, 4],
            ['debit_note', 'DN-', 17741, 6],
            ['official_receipt', 'OR-', 618, 5],
            ['acknowledgement_receipt', 'AR-', 7496, 6],
            ['vendor', 'VND-', 0, 4],
            ['vendor_bill', 'BILL-', 492, 5],
            ['payment_voucher', 'PV-', 312, 5],
            ['check_voucher', 'CV-', 418, 5],
            ['journal_voucher', 'JV-', 216, 5],
        ];

        foreach ($companyIds as $companyId) {
            foreach ($series as $row) {
                $exists = DB::table('number_series')
                    ->where('company_id', $companyId)
                    ->where('document_type', $row[0])
                    ->exists();

                if (!$exists) {
                    DB::table('number_series')->insert([
                        'company_id' => $companyId,
                        'document_type' => $row[0],
                        'prefix' => $row[1],
                        'current_number' => $row[2],
                        'padding' => $row[3],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('document_status_histories');
        Schema::dropIfExists('number_series');
        Schema::dropIfExists('print_layouts');
        Schema::dropIfExists('payment_applications');
        Schema::dropIfExists('expense_lines');
        Schema::dropIfExists('vendor_bill_lines');
    }
}
