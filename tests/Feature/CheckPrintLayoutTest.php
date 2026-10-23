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
            'status' => 'Shared check layout saved for all banks.',
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

    public function test_bdo_alignment_is_shared_with_metrobank_and_shared_saves_replace_it_for_both()
    {
        $bdo = DB::table('bank_accounts')->where('bank_name', 'BDO')->first();
        $metro = DB::table('bank_accounts')->where('bank_name', 'Metrobank')->first();
        $layout = $this->agentureLayout();
        $layout['payee'] = ['top' => 24.5, 'left' => 26];
        $legacy = [
            'company_id' => $bdo->company_id, 'bank_account_id' => $bdo->id,
            'profile_name' => 'Default', 'positions' => json_encode($layout),
            'field_styles' => json_encode(['font_sizes' => $layout['font_sizes']]),
            'version' => 14, 'is_active' => true, 'offset_x_mm' => 1.2,
        ];
        $bdoProfile = DB::table('check_layout_profiles')->insertGetId($legacy);
        $legacy['bank_account_id'] = $metro->id;
        $legacy['version'] = 20;
        $legacy['positions'] = json_encode($this->agentureLayout());
        $metroProfile = DB::table('check_layout_profiles')->insertGetId($legacy);

        $this->assertSame($bdoProfile, $this->checkLayout('bankCheckLayoutProfile', $metro->id)['id']);
        $this->assertSame($this->checkLayout('bankCheckPrintLayout', $bdo->id),
            $this->checkLayout('bankCheckPrintLayout', $metro->id, $metroProfile));
        $this->assertEquals($layout['payee'], $this->checkLayout('bankCheckPrintLayout', $metro->id)['payee']);

        $payload = [
            'layout' => $layout, 'profile_name' => 'Shared', 'printer_name' => 'Office printer',
            'paper_width_mm' => 210, 'paper_height_mm' => 88.9, 'feed_orientation' => 'top',
            'offset_x_mm' => 2, 'offset_y_mm' => 0,
        ];
        $first = $this->postJson(route('erp.payables.check-print.layout.save'), $payload);
        $first->assertOk()->assertJson(['profile' => ['bank_account_id' => null, 'version' => 1]]);
        $payload['layout']['payee']['left'] = 30;
        $payload['profile_name'] = 'Updated shared layout';
        $this->postJson(route('erp.payables.check-print.layout.save'), $payload)
            ->assertOk()->assertJson(['profile' => ['version' => 2]]);

        $this->assertSame(1, DB::table('check_layout_profiles')->whereNull('bank_account_id')->where('is_active', true)->count());
        $this->assertSame(30.0, $this->checkLayout('bankCheckPrintLayout', $bdo->id, $bdoProfile)['payee']['left']);
        $this->assertSame($this->checkLayout('bankCheckLayoutProfile', $bdo->id),
            $this->checkLayout('bankCheckLayoutProfile', $metro->id, $metroProfile));
        $this->assertSame(2, $this->checkLayout('bankCheckLayoutProfile', $metro->id)['version']);
        $this->assertSame(2, DB::table('check_layout_profiles')->whereNotNull('bank_account_id')->count());

        $payment = DB::table('payments')->orderBy('id')->first();
        $this->postJson(route('erp.payables.check-voucher.print.event', $payment->id))->assertOk();
        $this->get(route('erp.payables.check-print', ['payment' => $payment->id, 'profile' => $bdoProfile]))
            ->assertOk()->assertSee('Shared layout')->assertSee('Updated shared layout v2');
        $this->postJson(route('erp.payables.check-print.event', $payment->id), ['profile_id' => $bdoProfile])->assertOk();
        $event = DB::table('check_print_events')->where('payment_id', $payment->id)->orderBy('id', 'desc')->first();
        $snapshot = json_decode($event->layout_snapshot, true);
        $this->assertSame(2, $snapshot['profile']['version']);
        $this->assertEquals(30, $snapshot['layout']['payee']['left']);
        $this->assertNull($snapshot['profile']['bank_account_id']);
    }

    private function checkLayout($methodName, $bankId, $profileId = null)
    {
        $method = new \ReflectionMethod(\App\Http\Controllers\Erp\ModuleController::class, $methodName);
        $method->setAccessible(true);

        return $method->invoke(app(\App\Http\Controllers\Erp\ModuleController::class), $bankId, $profileId);
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
