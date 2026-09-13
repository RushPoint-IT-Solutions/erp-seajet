<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ControlledCheckLifecycleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->actingAsAdmin();
    }

    public function test_maker_checker_print_release_and_clear_lifecycle()
    {
        list($bill, $bank) = $this->billAndBank();
        DB::table('purchase_documents')->where('id', $bill->id)->update([
            'memo' => 'Vendor Bill header memo for check voucher',
            'remarks' => 'Legacy bill remarks',
            'client_customer_id' => 'CL-CONTROL',
        ]);
        $balanceBefore = (float) $bill->grand_total - (float) $bill->paid_total;

        $response = $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'amount' => '10.50',
            'check_no' => 'CONTROL-1001',
        ]));
        $response->assertOk();

        $payment = DB::table('payments')->where('payment_no', 'PV-CONTROL-1001')->first();
        $this->assertSame('For Approval', $payment->approval_status);
        $this->assertSame('unposted', $payment->posting_status);
        $this->assertSame(1, (int) $payment->created_by);
        $this->assertSame('Ten Pesos and 50/100 Only', $payment->amount_words_snapshot);
        $this->assertSame('Alternate Check Payee', $payment->payee_snapshot);
        $this->assertDatabaseHas('check_issuances', [
            'payment_id' => $payment->id,
            'check_no' => 'CONTROL-1001',
            'status' => 'assigned',
        ]);
        $this->get(route('erp.payables.approvals'))
            ->assertOk()
            ->assertSee('Check No.')
            ->assertSee('CONTROL-1001')
            ->assertSee(route('erp.payables.payments.approve', $payment->id));
        $supplierName = DB::table('suppliers')->where('id', $bill->supplier_id)->value('name');
        $this->get(route('erp.payables.check-voucher', $payment->id))
            ->assertOk()
            ->assertSee('Account Name:')
            ->assertSee('height: 82px', false)
            ->assertSee('width: 320px', false)
            ->assertSee($supplierName)
            ->assertSee('A/P Trade ('.$bank->bank_name.') - '.$bill->currency)
            ->assertSee('CL-CONTROL')
            ->assertDontSee('Alternate Check Payee')
            ->assertSee('Cash in Bank - '.$bank->bank_name.' ('.$bank->account_no.')')
            ->assertSee('Vendor Bill header memo for check voucher')
            ->assertDontSee('Legacy bill remarks')
            ->assertSee('MLP')
            ->assertSee('GCB')
            ->assertSee('ASA/BCA/CAC')
            ->assertDontSee($bank->account_name);

        DB::table('payments')->where('id', $payment->id)->update([
            'bank_account_id' => null,
            'bank_account' => null,
            'check_no' => null,
        ]);
        $this->get(route('erp.payables.check-voucher', $payment->id))
            ->assertRedirect(route('erp.payables.approvals'))
            ->assertSessionHasErrors(['bank_account', 'check_no']);
        $this->postJson(route('erp.payables.check-voucher.print.event', $payment->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account', 'check_no']);
        DB::table('payments')->where('id', $payment->id)->update([
            'bank_account_id' => $bank->id,
            'bank_account' => $bank->account_no,
            'check_no' => 'CONTROL-1001',
        ]);

        $this->get(route('erp.payables.check-print', $payment->id))
            ->assertRedirect(route('erp.payables.approvals'))
            ->assertSessionHasErrors('check');

        $this->post(route('erp.payables.payments.approve', $payment->id))
            ->assertRedirect(route('erp.payables.approvals'));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'approval_status' => 'Approved',
            'posting_status' => 'unposted',
        ]);

        $this->actingAsFinance($bill->company_id);
        $this->get(route('erp.payables.check-print', $payment->id))
            ->assertRedirect(route('erp.payables.approvals'))
            ->assertSessionHasErrors('check');
        $this->postJson(route('erp.payables.check-voucher.print.event', $payment->id))->assertOk();
        $this->assertDatabaseHas('check_voucher_print_events', ['payment_id' => $payment->id]);
        $this->get(route('erp.payables.check-print', $payment->id))
            ->assertOk()
            ->assertSee('id="checkPayeeName"', false)
            ->assertSee('Alternate Check Payee')
            ->assertSee('size: A4 portrait', false)
            ->assertSee('font-family: Calibri, Arial, Helvetica, sans-serif', false)
            ->assertSee('font-weight: 700', false)
            ->assertDontSee('rotate(90deg)', false);
        $this->post(route('erp.payables.check-print.payee.update', $payment->id), [
            'payee_name' => 'Updated Printed Payee',
        ])->assertRedirect(route('erp.payables.check-print', $payment->id));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payee_snapshot' => 'Updated Printed Payee',
        ]);
        $this->assertDatabaseHas('check_issuances', [
            'payment_id' => $payment->id,
            'payee_name' => 'Updated Printed Payee',
        ]);
        $this->get(route('erp.payables.check-print', $payment->id))
            ->assertOk()
            ->assertSee('Updated Printed Payee');
        $this->postJson(route('erp.payables.check-print.event', $payment->id), [])->assertOk();
        $this->assertDatabaseHas('check_print_events', [
            'payment_id' => $payment->id,
            'event_type' => 'original_print_requested',
        ]);

        $this->postJson(route('erp.payables.check-print.event', $payment->id), [])
            ->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->postJson(route('erp.payables.check-print.event', $payment->id), ['reason' => 'Printer paper jam'])
            ->assertOk()->assertJson(['event' => ['is_reprint' => true, 'print_count' => 2]]);

        $this->post(route('erp.payables.payments.release', $payment->id))
            ->assertRedirect(route('erp.payables.approvals'));
        $released = DB::table('payments')->where('id', $payment->id)->first();
        $this->assertSame('Released', $released->approval_status);
        $this->assertSame('posted', $released->posting_status);
        $this->assertNotNull($released->journal_entry_id);
        $this->assertEquals($balanceBefore - 10.50, (float) DB::table('purchase_documents')->where('id', $bill->id)->value(DB::raw('grand_total - paid_total')));
        $this->post(route('erp.payables.check-print.payee.update', $payment->id), [
            'payee_name' => 'Disallowed Released Payee',
        ])->assertSessionHasErrors('payee_name');
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payee_snapshot' => 'Updated Printed Payee',
        ]);

        $check = DB::table('check_issuances')->where('payment_id', $payment->id)->where('status', 'released')->first();
        $this->post(route('erp.payables.checks.clear', $check->id), [
            'cleared_at' => date('Y-m-d'),
            'clearing_reference' => 'BANK-STMT-1001',
        ])->assertRedirect(route('erp.payables.check-register'));
        $this->assertDatabaseHas('check_issuances', [
            'id' => $check->id,
            'status' => 'cleared',
            'clearing_reference' => 'BANK-STMT-1001',
        ]);
    }

    public function test_overpayment_and_duplicate_bank_check_number_are_rejected()
    {
        list($bill, $bank) = $this->billAndBank();
        $balance = (float) $bill->grand_total - (float) $bill->paid_total;

        $this->from(route('erp.payables.payments.create'))->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'amount' => (string) ($balance + 1),
            'check_no' => 'CONTROL-OVERPAY',
        ]))->assertRedirect(route('erp.payables.payments.create'))->assertSessionHasErrors('amount');

        $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'amount' => '5.00',
            'check_no' => 'CONTROL-DUPLICATE',
        ]))->assertOk();

        DB::table('payments')->where('payment_no', 'PV-CONTROL-1001')->update([
            'status' => 'released',
            'approval_status' => 'Released',
        ]);

        $duplicatePayload = $this->paymentPayload($bill, $bank, [
            'payment_no' => 'PV-CONTROL-1002',
            'amount' => '5.00',
            'check_no' => 'CONTROL-DUPLICATE',
        ]);
        $this->from(route('erp.payables.payments.create'))->post(route('erp.payables.payments.review'), $duplicatePayload)
            ->assertRedirect(route('erp.payables.payments.create'))->assertSessionHasErrors('check_no');
        $this->assertDatabaseMissing('payments', ['payment_no' => 'PV-CONTROL-1002']);
    }

    public function test_automatic_payment_number_skips_an_existing_stale_preview()
    {
        list($bill, $bank) = $this->billAndBank();
        $existingPaymentId = DB::table('payments')->orderBy('id')->value('id');
        DB::table('payments')->where('id', $existingPaymentId)->update(['payment_no' => 'PV-00323']);
        DB::table('number_series')
            ->where('company_id', $bill->company_id)
            ->where('document_type', 'payment_voucher')
            ->update(['current_number' => 322, 'prefix' => 'PV-', 'padding' => 5]);

        $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'payment_no' => 'PV-00323',
            'check_no' => 'CONTROL-AUTO-NUMBER',
            'amount' => '6.00',
        ]))->assertOk();

        $payment = DB::table('payments')->where('check_no', 'CONTROL-AUTO-NUMBER')->first();
        $this->assertNotNull($payment);
        $this->assertSame('PV-00324', $payment->payment_no);
        $this->assertSame(324, (int) DB::table('number_series')
            ->where('company_id', $bill->company_id)
            ->where('document_type', 'payment_voucher')
            ->value('current_number'));
    }

    public function test_check_preparation_opens_payment_form_with_cheque_selected()
    {
        $this->get(route('erp.payables.approvals'))
            ->assertOk()
            ->assertSee(route('erp.payables.payments.create', ['method' => 'Cheque']));

        $this->get(route('erp.payables.payments.create', ['method' => 'Cheque']))
            ->assertOk()
            ->assertSee('value="Cheque" selected', false)
            ->assertSee('id="checkNumber" class="form-control" value="" required', false);
    }

    public function test_check_preparation_assigns_the_next_bank_check_number_automatically()
    {
        list($bill, $bank) = $this->billAndBank();

        $this->get(route('erp.payables.payments.create', ['method' => 'Cheque']))
            ->assertOk()
            ->assertSee('data-next-check-no="470131"', false)
            ->assertSee('data-next-check-no="000001"', false)
            ->assertSee('The next number is assigned automatically');

        $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'check_no' => null,
            'amount' => '5.00',
        ]))->assertOk();

        $payment = DB::table('payments')->where('payment_no', 'PV-CONTROL-1001')->first();
        $this->assertSame('470131', $payment->check_no);
        $this->assertDatabaseHas('check_issuances', [
            'payment_id' => $payment->id,
            'bank_account_id' => $bank->id,
            'check_no' => '470131',
        ]);
        $this->assertSame('470131', DB::table('bank_accounts')->where('id', $bank->id)->value('last_check_no'));
        $this->assertNull(DB::table('bank_accounts')->where('id', '<>', $bank->id)->value('last_check_no'));
    }

    public function test_entry_forms_offer_clear_actions_and_reset_data_is_only_in_finance_settings()
    {
        $this->get(route('erp.invoices.create'))->assertOk()->assertSee('Clear Form');
        $this->get(route('erp.payables.vendor-bills.create'))->assertOk()->assertSee('Clear Form');
        $this->get(route('erp.payables.payments.create', ['method' => 'Cheque']))->assertOk()->assertSee('Clear Form');
        $this->get(route('erp.invoices.index'))->assertOk()->assertDontSee('Reset Data');
        $this->get(route('erp.expenses.index'))->assertOk()->assertDontSee('Reset Data');
        $this->get(route('erp.payables.approvals'))->assertOk()->assertDontSee('Reset Data');
        $this->get(route('erp.accounting.journals'))->assertOk()->assertDontSee('Reset Data');
        $this->get(route('erp.settings.index'))->assertOk()->assertSee('Reset Data');
    }

    public function test_preparer_with_approval_access_can_approve_own_payment()
    {
        list($bill, $bank) = $this->billAndBank();

        $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'payment_method' => 'Bank',
            'check_no' => null,
            'amount' => '5.00',
        ]))->assertOk();

        $payment = DB::table('payments')->where('payment_no', 'PV-CONTROL-1001')->first();
        $this->assertNotNull($payment);
        $this->get(route('erp.payables.approvals'))
            ->assertOk()
            ->assertSee(route('erp.payables.payments.approve', $payment->id));
        $this->post(route('erp.payables.payments.approve', $payment->id))
            ->assertRedirect(route('erp.payables.approvals'));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'approval_status' => 'Approved',
            'approved_by' => 1,
        ]);
    }

    public function test_custom_role_with_approve_access_can_approve_payment()
    {
        list($bill, $bank) = $this->billAndBank();

        $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'payment_method' => 'Bank',
            'check_no' => null,
            'amount' => '5.00',
        ]))->assertOk();

        $payment = DB::table('payments')->where('payment_no', 'PV-CONTROL-1001')->first();
        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $bill->company_id,
            'name' => 'Custom AP Approver',
            'slug' => 'custom_ap_approver',
            'description' => 'Approval-only test role',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionIds = DB::table('permissions')
            ->where('slug', 'payables.view')
            ->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }

        $approver = new User();
        $approver->id = 901;
        $approver->name = 'Custom AP Approver';
        $approver->email = 'custom.approver@seajet.test';
        $approver->role = 'custom_ap_approver';
        $approver->company_id = $bill->company_id;
        $approver->is_active = true;
        $this->actingAs($approver);

        $this->get(route('erp.payables.approvals'))
            ->assertOk()
            ->assertSee(route('erp.payables.payments.approve', $payment->id))
            ->assertDontSee(route('erp.payables.payments.release', $payment->id));
        $this->post(route('erp.payables.payments.approve', $payment->id))
            ->assertRedirect(route('erp.payables.approvals'));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'approval_status' => 'Approved',
            'approved_by' => 901,
        ]);
    }

    public function test_layout_saves_new_versions_and_keeps_only_latest_active()
    {
        $bank = DB::table('bank_accounts')->orderBy('id')->first();
        $payload = $this->layoutPayload($bank->id);

        $this->postJson(route('erp.payables.check-print.layout.save'), $payload)
            ->assertOk()->assertJson(['profile' => ['version' => 1]]);
        $payload['offset_x_mm'] = 2.5;
        $this->postJson(route('erp.payables.check-print.layout.save'), $payload)
            ->assertOk()->assertJson(['profile' => ['version' => 2, 'offset_x_mm' => 2.5]]);

        $this->assertSame(2, DB::table('check_layout_profiles')->where('profile_name', 'QA Printer')->count());
        $this->assertSame(1, DB::table('check_layout_profiles')->where('profile_name', 'QA Printer')->where('is_active', true)->count());
    }

    public function test_check_voucher_layout_saves_field_and_table_box_heights()
    {
        $this->get(route('erp.payables.check-voucher.layout'))
            ->assertOk()
            ->assertSee('id="fieldHeightControl"', false)
            ->assertSee('id="box_height_accounting"', false)
            ->assertSee('id="accountingTableGuide"', false)
            ->assertSee('Move Accounting Table')
            ->assertSee('Move Payee Table')
            ->assertSee('Move Description Table')
            ->assertSee('Move Amount Table')
            ->assertSee('Move Signatures Table')
            ->assertSee('id="box_x_accounting"', false)
            ->assertSee('id="box_y_accounting"', false)
            ->assertSee('id="box_x_payee"', false)
            ->assertSee('id="box_x_description"', false)
            ->assertSee('id="box_x_amount"', false)
            ->assertSee('id="box_x_signatures"', false)
            ->assertDontSee('cv-guide-muted');

        $this->post(route('erp.payables.check-voucher.layout.save'), [
            'paper_size' => 'letter',
            'paper_width' => 816,
            'paper_height' => 1056,
            'print_template' => '0',
            'custom_fields_present' => '1',
            'h_description' => 60,
            'x_amount' => 600,
            'y_amount' => 405,
            'x_amount_words' => 410,
            'y_amount_words' => 438,
            'box_height_accounting' => 220,
            'box_x_accounting' => 72,
            'box_y_accounting' => 345,
            'box_x_payee' => 55,
            'box_y_payee' => 180,
            'box_x_description' => 65,
            'box_y_description' => 450,
            'box_x_amount' => 75,
            'box_y_amount' => 525,
            'box_x_signatures' => 85,
            'box_y_signatures' => 610,
        ])->assertRedirect(route('erp.payables.check-voucher.layout'));

        $styles = json_decode(DB::table('print_layouts')
            ->where('document_type', 'check_voucher')
            ->value('field_styles'), true);
        $this->assertSame(60, $styles['description']['height']);
        $this->assertSame(220, $styles['_box_heights']['accounting']);
        $this->assertSame([72, 345], $styles['_box_positions']['accounting']);
        $this->assertSame([55, 180], $styles['_box_positions']['payee']);
        $this->assertSame([65, 450], $styles['_box_positions']['description']);
        $this->assertSame([75, 525], $styles['_box_positions']['amount']);
        $this->assertSame([85, 610], $styles['_box_positions']['signatures']);

        $this->get(route('erp.payables.check-voucher'))
            ->assertOk()
            ->assertSee('width: 748px', false)
            ->assertDontSee('width: 364px', false)
            ->assertSee('left: 600px; top: 405px', false)
            ->assertSee('left: 410px; top: 438px', false)
            ->assertSee('height: 60px', false)
            ->assertSee('height: 220px', false)
            ->assertSee('cv-accounting-table" style="left: 72px; top: 345px;', false)
            ->assertSee('cv-payee-table" style="left: 55px; top: 180px;', false)
            ->assertSee('cv-description-table" style="left: 65px; top: 450px;', false)
            ->assertSee('cv-amount-box" style="left: 75px; top: 525px;', false)
            ->assertSee('cv-signatures-table" style="left: 85px; top: 610px;', false)
            ->assertDontSee('cv-muted-line');
    }

    public function test_spoiled_check_replacement_and_released_payment_void_are_auditable()
    {
        list($bill, $bank) = $this->billAndBank();
        $paidBefore = (float) $bill->paid_total;
        $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'amount' => '7.25',
            'check_no' => 'CONTROL-SPOILED',
        ]))->assertOk();
        $payment = DB::table('payments')->where('payment_no', 'PV-CONTROL-1001')->first();

        $this->actingAsFinance($bill->company_id);
        $this->post(route('erp.payables.payments.approve', $payment->id));
        $this->postJson(route('erp.payables.check-voucher.print.event', $payment->id))->assertOk();
        $this->postJson(route('erp.payables.check-print.event', $payment->id), [])->assertOk();
        $spoiled = DB::table('check_issuances')->where('payment_id', $payment->id)->first();

        $this->post(route('erp.payables.checks.replace', $spoiled->id), [
            'new_check_no' => 'CONTROL-REPLACEMENT',
            'reason' => 'Check stock jammed during printing',
        ])->assertRedirect(route('erp.payables.check-register'));
        $this->assertDatabaseHas('check_issuances', [
            'id' => $spoiled->id,
            'status' => 'replaced',
        ]);
        $replacement = DB::table('check_issuances')->where('payment_id', $payment->id)
            ->where('check_no', 'CONTROL-REPLACEMENT')->first();
        $this->assertNotNull($replacement);
        $this->assertSame('assigned', $replacement->status);
        $this->assertSame('CONTROL-REPLACEMENT', DB::table('payments')->where('id', $payment->id)->value('check_no'));

        $this->postJson(route('erp.payables.check-print.event', $payment->id), [])->assertOk();
        $this->post(route('erp.payables.payments.release', $payment->id));
        $this->assertEquals($paidBefore + 7.25, (float) DB::table('purchase_documents')->where('id', $bill->id)->value('paid_total'));

        $this->post(route('erp.payables.payments.void', $payment->id), [
            'reason' => 'Supplier requested cancellation',
        ])->assertRedirect(route('erp.payables.approvals'));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'voided',
            'approval_status' => 'Voided',
            'posting_status' => 'reversed',
        ]);
        $this->assertDatabaseHas('check_issuances', [
            'id' => $replacement->id,
            'status' => 'voided',
        ]);
        $this->assertEquals($paidBefore, (float) DB::table('purchase_documents')->where('id', $bill->id)->value('paid_total'));
        $this->assertSame(1, DB::table('journal_entries')->where('source_type', 'payment')
            ->where('source_id', $payment->id)->where('posting_type', 'reversal')->count());

        $newPreparationUrl = route('erp.payables.payments.create', [
            'bill' => $bill->document_no,
            'method' => 'Cheque',
        ]);
        $this->get(route('erp.payables.check-voucher.bill', $bill->document_no))
            ->assertRedirect($newPreparationUrl);
        $this->actingAsAdmin();
        $this->get($newPreparationUrl)
            ->assertOk()
            ->assertSee('value="Cheque" selected', false);

        $this->post(route('erp.payables.payments.review'), $this->paymentPayload($bill, $bank, [
            'payment_no' => 'PV-CONTROL-RECREATED',
            'check_no' => 'CONTROL-RECREATED',
            'amount' => '7.25',
        ]))->assertOk();
        $this->assertDatabaseHas('payments', [
            'bill_no' => $bill->document_no,
            'payment_no' => 'PV-CONTROL-RECREATED',
            'check_no' => 'CONTROL-RECREATED',
            'status' => 'for_approval',
        ]);
    }

    private function actingAsFinance($companyId)
    {
        $user = new User();
        $user->id = 900;
        $user->company_id = $companyId;
        $user->name = 'QA Finance Approver';
        $user->email = 'finance-approver@seajet.test';
        $user->role = 'finance';
        $this->actingAs($user);
    }

    private function billAndBank()
    {
        $bill = DB::table('purchase_documents')
            ->whereRaw('grand_total > paid_total + 20')
            ->where('currency', 'PHP')
            ->whereNotIn('status', ['cancelled', 'voided'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('payments')
                    ->whereColumn('payments.bill_no', 'purchase_documents.document_no')
                    ->whereNotIn('payments.status', ['released', 'cancelled', 'voided']);
            })
            ->orderBy('id')
            ->first();
        $bank = DB::table('bank_accounts')
            ->where('company_id', $bill->company_id)
            ->where('currency', $bill->currency)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $this->assertNotNull($bill);
        $this->assertNotNull($bank);

        return [$bill, $bank];
    }

    private function paymentPayload($bill, $bank, array $overrides = [])
    {
        return array_merge([
            'payment_no' => 'PV-CONTROL-1001',
            'payment_date' => date('Y-m-d'),
            'bill_no' => $bill->document_no,
            'shipment_job_id' => $bill->shipment_job_id,
            'supplier_id' => (string) $bill->supplier_id,
            'supplier_name' => 'Snapshot Payee',
            'payee_name' => 'Alternate Check Payee',
            'payment_method' => 'Cheque',
            'bank_account' => $bank->account_no,
            'check_no' => 'CONTROL-1001',
            'currency' => $bill->currency,
            'exchange_rate' => $bill->currency === 'PHP' ? 1 : 58.25,
            'amount' => '10.50',
        ], $overrides);
    }

    private function layoutPayload($bankAccountId)
    {
        $layout = [
            'font_sizes' => [
                'date_m1' => 12, 'date_m2' => 12, 'date_d1' => 12, 'date_d2' => 12,
                'date_y1' => 12, 'date_y2' => 12, 'date_y3' => 12, 'date_y4' => 12,
                'payee' => 12, 'amount_words' => 12, 'amount_figures' => 12,
            ],
            'date_m1' => ['top' => 12, 'left' => 175], 'date_m2' => ['top' => 12, 'left' => 179],
            'date_d1' => ['top' => 12, 'left' => 185], 'date_d2' => ['top' => 12, 'left' => 189],
            'date_y1' => ['top' => 12, 'left' => 195], 'date_y2' => ['top' => 12, 'left' => 199],
            'date_y3' => ['top' => 12, 'left' => 203], 'date_y4' => ['top' => 12, 'left' => 207],
            'payee' => ['top' => 28, 'left' => 22], 'amount_words' => ['top' => 36, 'left' => 22],
            'amount_figures' => ['top' => 28, 'left' => 178],
        ];

        return [
            'layout' => $layout,
            'bank_account_id' => $bankAccountId,
            'profile_name' => 'QA Printer',
            'printer_name' => 'QA Device',
            'paper_width_mm' => 210,
            'paper_height_mm' => 88.9,
            'feed_orientation' => 'top',
            'offset_x_mm' => 0,
            'offset_y_mm' => 0,
        ];
    }
}
