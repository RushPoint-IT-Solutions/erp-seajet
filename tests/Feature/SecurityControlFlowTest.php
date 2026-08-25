<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityControlFlowTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
    }

    public function test_password_change_is_not_required_before_erp_access()
    {
        $user = User::where('email', 'admin@seajet.test')->first();
        $this->assertFalse($user->must_change_password);

        // A stale flag from an older deployment must not block login.
        $user->must_change_password = true;
        $user->save();

        $this->actingAs($user)->get('/')->assertStatus(200);
    }

    public function test_invoice_portal_link_is_real_scoped_and_expiring()
    {
        $this->actingAsAdmin();
        $invoice = DB::table('sales_documents')->whereNotNull('customer_id')->first();
        $response = $this->post('/erp/invoices/'.$invoice->id.'/portal-link');
        $response->assertRedirect('/erp/invoices/'.$invoice->id);

        $token = DB::table('client_portal_tokens')->where('sales_document_id', $invoice->id)->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->get('/client/invoices/'.$token->token)
            ->assertStatus(200)
            ->assertSee($invoice->document_no);
        $this->get('/client/invoices/not-a-valid-token')->assertStatus(404);
    }

    public function test_stripe_webhook_rejects_bad_signatures_and_is_idempotent()
    {
        config(['services.stripe.webhook.secret' => 'whsec_test']);
        $payload = json_encode([
            'id' => 'evt_test_001',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['amount_received' => 12345, 'currency' => 'php']],
        ]);
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=bad'], $payload)
            ->assertStatus(400);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');
        $header = 't='.$timestamp.',v1='.$signature;
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $header], $payload)
            ->assertStatus(200);
        $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $header], $payload)
            ->assertStatus(200)
            ->assertJson(['duplicate' => true]);
        $this->assertSame(1, DB::table('online_payment_events')->where('event_id', 'evt_test_001')->count());
    }

    public function test_demo_api_data_is_not_public()
    {
        $this->getJson('/api/v1/clients')->assertStatus(401);
        $this->getJson('/api/v1/invoices')->assertStatus(401);
        $this->getJson('/api/v1/payments')->assertStatus(401);
    }
}
