<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckPrintLayoutTest extends TestCase
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

    public function test_check_print_uses_agenture_matrix_and_payment_date()
    {
        $payment = DB::table('payments')->orderBy('id')->first();

        $this->postJson(route('erp.payables.check-voucher.print.event', $payment->id))->assertOk();
        $response = $this->get(route('erp.payables.check-print', $payment->id));

        $response->assertOk();
        $response->assertSee('Check Print Alignment');
        $response->assertSee('data-font-key="date_m1"', false);
        $response->assertSee('data-font-key="payee"', false);
        $response->assertSee('Agenture Defaults');
        $response->assertSee('size: 210mm 297mm;', false);
        $response->assertSee('data-font-key="payee" style="top:28mm;left:22mm;font-size:12pt"', false);

        $digits = str_split(date('mdY', strtotime($payment->payment_date)));
        $dateFields = ['date_m1', 'date_m2', 'date_d1', 'date_d2', 'date_y1', 'date_y2', 'date_y3', 'date_y4'];
        foreach ($dateFields as $index => $field) {
            $this->assertRegExp('/data-font-key="'.preg_quote($field, '/').'"[^>]*>'.preg_quote($digits[$index], '/').'<\/div>/', $response->getContent());
        }
    }

    public function test_admin_can_save_company_specific_check_alignment()
    {
        $layout = $this->agentureLayout();
        $layout['payee'] = ['top' => 31.4, 'left' => 24.2];
        $layout['font_sizes']['payee'] = 13;
        $bank = DB::table('bank_accounts')->orderBy('id')->first();

        $response = $this->postJson(route('erp.payables.check-print.layout.save'), [
            'layout' => $layout,
            'bank_account_id' => $bank->id,
            'profile_name' => 'Office Printer',
            'printer_name' => 'Epson Check Tray',
            'paper_width_mm' => 215.9,
            'paper_height_mm' => 88.9,
            'feed_orientation' => 'top',
            'offset_x_mm' => 1.2,
            'offset_y_mm' => -0.4,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'Layout saved as a new bank/printer profile version.',
            'layout' => [
                'payee' => ['top' => 31.4, 'left' => 24.2],
                'font_sizes' => ['payee' => 13],
            ],
            'profile' => ['profile_name' => 'Office Printer', 'version' => 1],
        ]);

        $saved = DB::table('check_layout_profiles')->where('profile_name', 'Office Printer')->first();
        $this->assertNotNull($saved);
        $this->assertNotNull($saved->company_id);
        $this->assertSame(31.4, (float) json_decode($saved->positions, true)['payee']['top']);

        $payment = DB::table('payments')->orderBy('id')->first();
        DB::table('payments')->where('id', $payment->id)->update(['bank_account_id' => $bank->id]);
        $this->postJson(route('erp.payables.check-voucher.print.event', $payment->id))->assertOk();
        $this->get(route('erp.payables.check-print', ['payment' => $payment->id, 'profile' => $saved->id]))
            ->assertOk()
            ->assertSee('size: 215.9mm 297mm;', false)
            ->assertSee('width: var(--check-paper-width, 215.9mm);', false)
            ->assertSee('data-font-key="payee" style="top:31mm;left:25.4mm;font-size:13pt"', false)
            ->assertSee('id="checkPrintPageSize"', false);
    }

    public function test_finance_user_cannot_save_the_global_check_alignment()
    {
        $user = new User();
        $user->id = 2;
        $user->name = 'QA Finance';
        $user->email = 'finance@seajet.test';
        $user->role = 'finance';
        $this->actingAs($user);

        $this->postJson(route('erp.payables.check-print.layout.save'), [
            'layout' => $this->agentureLayout(),
        ])->assertStatus(403);

        $this->assertSame(0, DB::table('check_layout_profiles')->count());
    }

    private function agentureLayout()
    {
        return [
            'font_sizes' => [
                'date_m1' => 12, 'date_m2' => 12, 'date_d1' => 12, 'date_d2' => 12,
                'date_y1' => 12, 'date_y2' => 12, 'date_y3' => 12, 'date_y4' => 12,
                'payee' => 12, 'amount_words' => 12, 'amount_figures' => 12,
            ],
            'date_m1' => ['top' => 12, 'left' => 175],
            'date_m2' => ['top' => 12, 'left' => 179],
            'date_d1' => ['top' => 12, 'left' => 185],
            'date_d2' => ['top' => 12, 'left' => 189],
            'date_y1' => ['top' => 12, 'left' => 195],
            'date_y2' => ['top' => 12, 'left' => 199],
            'date_y3' => ['top' => 12, 'left' => 203],
            'date_y4' => ['top' => 12, 'left' => 207],
            'payee' => ['top' => 28, 'left' => 22],
            'amount_words' => ['top' => 36, 'left' => 22],
            'amount_figures' => ['top' => 28, 'left' => 178],
        ];
    }
}
