<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoaAPrintFontTest extends TestCase
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
        $this->actingAsAdmin();
        $this->withoutExceptionHandling();
    }

    public function test_soa_a_layout_has_font_and_font_size_controls_and_saves_them()
    {
        $this->get(route('erp.billings.print-layout.soa-a'))
            ->assertOk()
            ->assertSee('for="fontFamilyControl">Font', false)
            ->assertSee('id="fontFamilyControl"', false)
            ->assertSee('for="allFontSizeControl">Font Size', false)
            ->assertSee('id="allFontSizeControl"', false)
            ->assertSee('Times New Roman');

        $this->post(route('erp.billings.print-layout.soa-a.save'), [
            'paper_size' => 'letter',
            'paper_width' => 816,
            'paper_height' => 1056,
            'print_template' => '0',
            'font_family' => 'times_new_roman',
            'fs_serial' => 16,
            'fs_customer' => 16,
            'fs_shipper' => 16,
            'fs_particulars' => 16,
            'fs_amounts' => 16,
            'fs_total_reimbursement' => 16,
        ])->assertRedirect(route('erp.billings.print-layout.soa-a'));

        $saved = DB::table('print_layouts')->where('document_type', 'soa_a')->first();
        $styles = json_decode($saved->field_styles, true);
        $this->assertSame('times_new_roman', $styles['_font_family']);
        $this->assertSame(16, $styles['particulars']['font_size']);

        $this->get(route('erp.soa-a.preview'))
            ->assertOk()
            ->assertSee('font-family: "Times New Roman", Times, serif;', false)
            ->assertSee('font-size: 16px', false);

        $this->get(route('erp.billings.print-layout.soa-b'))
            ->assertOk()
            ->assertDontSee('id="fontFamilyControl"', false)
            ->assertDontSee('id="allFontSizeControl"', false);
    }
}
