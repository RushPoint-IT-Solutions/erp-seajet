<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherSettlementService
{
    public function appliedAmount($payment)
    {
        if ($payment->applied_to_bill !== null) {
            return (float) $payment->applied_to_bill;
        }
        return in_array(strtolower((string) $payment->status), ['released', 'posted'], true)
            || strtolower((string) $payment->approval_status) === 'released' ? (float) $payment->amount : 0;
    }

    public function apply($paymentId)
    {
        $this->settle($paymentId, false);
    }

    public function remove($paymentId)
    {
        $this->settle($paymentId, true);
    }

    private function settle($paymentId, $remove)
    {
        DB::transaction(function () use ($paymentId, $remove) {
            $payment = DB::table('payments')->where('id', $paymentId)->lockForUpdate()->first();
            if (!$payment) {
                return;
            }
            $bill = DB::table('purchase_documents')->where('company_id', $payment->company_id)
                ->where('document_no', $payment->bill_no)->lockForUpdate()->first();
            if (!$bill) {
                return;
            }
            $amount = $remove ? 0 : (float) $payment->amount;
            $paid = round((float) $bill->paid_total - $this->appliedAmount($payment) + $amount, 2);
            if (!$remove && $paid > round((float) $bill->grand_total, 2)) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the outstanding supplier bill balance.']);
            }
            $paid = max(0, $paid);
            DB::table('purchase_documents')->where('id', $bill->id)->update([
                'paid_total' => $paid,
                'status' => $paid >= (float) $bill->grand_total ? 'paid' : ($paid > 0 ? 'partially_paid' : 'for_payment'),
                'updated_at' => now(),
            ]);
            DB::table('payments')->where('id', $payment->id)->update(['applied_to_bill' => $amount]);
        });
    }
}
