<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptPrintLayoutTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        Storage::fake('local');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('receipts');
        Schema::enableForeignKeyConstraints();
        $this->actingAsAdmin();
        $this->withoutExceptionHandling();
    }

    public function test_collection_receipt_positions_can_be_adjusted_and_saved()
    {
        $this->get(route('erp.official-receipt.preview', ['adjust' => 1]))
            ->assertStatus(200)
            ->assertSee('Save Layout')
            ->assertSee('Delete Selected')
            ->assertSee('Choose printable fields')
            ->assertSee('id="receiptFontSize"', false)
            ->assertSee('id="receiptFontFamily"', false)
            ->assertSee('id="receiptBold"', false)
            ->assertSee('data-field-key="printed_by"', false)
            ->assertSee('Printed by: QA Admin')
            ->assertSee('data-field-key="registered_name"', false);

        $this->post(route('erp.official-receipt.layout.save'), [
            'layout_x' => ['registered_name' => 3.5],
            'layout_y' => ['registered_name' => -2],
            'font_size' => ['registered_name' => 12.5],
            'font_family' => ['registered_name' => 'georgia'],
            'bold_field' => ['registered_name'],
            'visibility_present' => 1,
            'visible_field' => [
                'receipt_no', 'payment_date', 'account_no', 'registered_name',
                'business_address', 'description', 'amount', 'reference_no',
                'bank', 'total_amount',
                'printed_by',
            ],
        ])->assertRedirect();

        $this->get(route('erp.official-receipt.preview'))
            ->assertStatus(200)
            ->assertSee('transform: translate(3.5mm, -2mm)', false)
            ->assertSee('font-size: 12.5pt', false)
            ->assertSee('font-family: Georgia, &quot;Times New Roman&quot;, serif; font-weight: 700 !important', false)
            ->assertSee('font-size: 8.25pt; font-family: Arial, Helvetica, sans-serif; font-weight: 400 !important; display: none;', false)
            ->assertSee('Print Content Only');
    }

    public function test_acknowledgement_receipt_positions_can_be_adjusted_and_saved()
    {
        $this->post(route('erp.acknowledgement-receipt.layout.save'), [
            'layout_x' => ['total_amount' => -1.25],
            'layout_y' => ['total_amount' => 4.75],
            'font_size' => ['total_amount' => 14],
            'font_family' => ['total_amount' => 'courier_new'],
            'bold_field' => ['printed_by'],
        ])->assertRedirect();

        $this->get(route('erp.acknowledgement-receipt.preview', ['adjust' => 1]))
            ->assertStatus(200)
            ->assertSee('data-field-key="total_amount"', false)
            ->assertSee('data-field-key="printed_by"', false)
            ->assertSee('Printed by: QA Admin')
            ->assertSee('transform: translate(-1.25mm, 4.75mm)', false)
            ->assertSee('font-size: 14pt', false)
            ->assertSee('font-family: &quot;Courier New&quot;, Courier, monospace; font-weight: 400 !important', false)
            ->assertSee('Reset');

        $this->assertRegExp(
            '/data-field-key="printed_by"[^>]*font-weight:\s*700\s*!important/i',
            $this->get(route('erp.acknowledgement-receipt.preview'))->getContent()
        );
    }
}
