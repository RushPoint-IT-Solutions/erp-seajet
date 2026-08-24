<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddControlledCheckLifecycle extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('created_by')->nullable()->after('approval_status');
            $table->timestamp('submitted_at')->nullable()->after('created_by');
            $table->unsignedInteger('approved_by')->nullable()->after('submitted_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->unsignedInteger('released_by')->nullable()->after('approved_at');
            $table->timestamp('released_at')->nullable()->after('released_by');
            $table->unsignedInteger('voided_by')->nullable()->after('released_at');
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('void_reason')->nullable()->after('voided_at');
            $table->string('payee_snapshot')->nullable()->after('void_reason');
            $table->text('amount_words_snapshot')->nullable()->after('payee_snapshot');
            $table->index(['company_id', 'approval_status'], 'payment_approval_lookup');
        });

        Schema::create('check_layout_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('profile_name')->default('Default');
            $table->string('printer_name')->nullable();
            $table->decimal('paper_width_mm', 8, 2)->default(210);
            $table->decimal('paper_height_mm', 8, 2)->default(88.9);
            $table->string('feed_orientation')->default('top');
            $table->decimal('offset_x_mm', 8, 2)->default(0);
            $table->decimal('offset_y_mm', 8, 2)->default(0);
            $table->json('positions');
            $table->json('field_styles')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'bank_account_id', 'profile_name', 'version'], 'check_layout_profile_version');
            $table->index(['company_id', 'bank_account_id', 'is_active'], 'check_layout_active_lookup');
        });

        Schema::create('check_issuances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('bank_account_key');
            $table->string('check_no');
            $table->date('check_date');
            $table->string('payee_name');
            $table->string('currency', 3);
            $table->decimal('amount', 18, 4);
            $table->text('amount_words');
            $table->string('status')->default('assigned');
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('first_printed_at')->nullable();
            $table->unsignedInteger('first_printed_by')->nullable();
            $table->timestamp('last_printed_at')->nullable();
            $table->unsignedInteger('last_printed_by')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->unsignedInteger('released_by')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->unsignedInteger('cleared_by')->nullable();
            $table->string('clearing_reference')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->unsignedBigInteger('replaced_by_check_issuance_id')->nullable();
            $table->text('exception_reason')->nullable();
            $table->timestamps();
            $table->index(['payment_id', 'status'], 'check_issuance_payment_lookup');
            $table->unique(['company_id', 'bank_account_key', 'check_no'], 'check_issuance_bank_number_unique');
            $table->index(['company_id', 'status', 'check_date'], 'check_issuance_register_lookup');
        });

        Schema::create('check_print_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('check_issuance_id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('layout_profile_id')->nullable();
            $table->string('event_type');
            $table->text('reason')->nullable();
            $table->json('layout_snapshot');
            $table->json('payload_snapshot');
            $table->string('payload_hash', 64);
            $table->unsignedInteger('printed_by')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['check_issuance_id', 'created_at'], 'check_print_event_lookup');
        });

        $this->backfillLegacyChecks();
    }

    private function backfillLegacyChecks()
    {
        $payments = DB::table('payments')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'payments.supplier_id')
            ->select('payments.*', 'suppliers.name as supplier_name')
            ->whereRaw('LOWER(payments.payment_method) IN (?, ?)', ['cheque', 'check'])
            ->whereNotNull('payments.check_no')
            ->where('payments.check_no', '<>', '')
            ->orderBy('payments.id')
            ->get();

        foreach ($payments as $payment) {
            $payee = $payment->supplier_name ?: 'Unknown Payee';
            $bankAccountId = $payment->bank_account_id ?: $this->resolveLegacyBankAccountId($payment);
            $bankKey = $bankAccountId
                ? 'ID:'.$bankAccountId
                : 'TEXT:'.strtoupper(trim((string) $payment->bank_account));
            $status = strtolower((string) $payment->approval_status) === 'released' ? 'released' : 'assigned';
            $exception = null;

            if (DB::table('check_issuances')->where('company_id', $payment->company_id)
                ->where('bank_account_key', $bankKey)->where('check_no', $payment->check_no)->exists()) {
                $exception = 'Duplicate legacy check number detected during migration.';
                $bankKey = 'DUPLICATE:PAYMENT:'.$payment->id;
                $status = 'exception';
            }

            DB::table('payments')->where('id', $payment->id)->update([
                'bank_account_id' => $bankAccountId,
                'payee_snapshot' => $payee,
                'submitted_at' => $payment->created_at ?: now(),
            ]);

            DB::table('check_issuances')->insert([
                'company_id' => $payment->company_id,
                'payment_id' => $payment->id,
                'bank_account_id' => $bankAccountId,
                'bank_account_key' => $bankKey,
                'check_no' => trim($payment->check_no),
                'check_date' => $payment->payment_date,
                'payee_name' => $payee,
                'currency' => strtoupper($payment->currency ?: 'PHP'),
                'amount' => $payment->amount,
                'amount_words' => $payment->amount_words_snapshot ?: '',
                'status' => $status,
                'released_at' => $status === 'released' ? ($payment->updated_at ?: now()) : null,
                'exception_reason' => $exception,
                'created_at' => $payment->created_at ?: now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function resolveLegacyBankAccountId($payment)
    {
        $label = strtoupper(trim((string) $payment->bank_account));
        $banks = DB::table('bank_accounts')->where('company_id', $payment->company_id)
            ->where('currency', strtoupper($payment->currency ?: 'PHP'))->get();

        foreach ($banks as $bank) {
            if ($label === strtoupper((string) $bank->account_no)
                || $label === strtoupper((string) $bank->account_name)
                || strpos($label, strtoupper((string) $bank->account_no)) !== false
                || (strpos($label, strtoupper((string) $bank->bank_name)) !== false && strpos($label, 'USD') !== false)) {
                return $bank->id;
            }
        }

        return null;
    }

    public function down()
    {
        Schema::dropIfExists('check_print_events');
        Schema::dropIfExists('check_issuances');
        Schema::dropIfExists('check_layout_profiles');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payment_approval_lookup');
            $table->dropColumn([
                'created_by', 'submitted_at', 'approved_by', 'approved_at', 'released_by',
                'released_at', 'voided_by', 'voided_at', 'void_reason', 'payee_snapshot',
                'amount_words_snapshot',
            ]);
        });
    }
}
