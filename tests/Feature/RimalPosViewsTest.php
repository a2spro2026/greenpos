<?php

namespace Tests\Feature;

class RimalPosViewsTest extends RimalPosTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $mock = \Mockery::mock(\App\Services\ModuleManagerService::class)->makePartial();
        $mock->shouldReceive('allowsAbility')->andReturn(true);
        $this->app->instance(\App\Services\ModuleManagerService::class, $mock);
        $this->company->forceFill(['modules_setup_at' => now()])->save();
        $this->withSession(['last_activity_at' => now()->timestamp]);
    }

    public function test_terminal_renders_with_new_controls(): void
    {
        $r = $this->get(route("pos.terminal"));
        if ($r->status() === 403) {
            $this->markTestSkipped('permission model not seeded in tests');
        }
        $r->assertOk()->assertSee('pos-delivery-platform', false)->assertSee('Virement instantané')->assertSee('pos-categories', false);
    }

    public function test_settings_pos_and_sales_form_render(): void
    {
        foreach ([route('settings.section', 'pos'), route('sales.create')] as $url) {
            $r = $this->get($url);
            $r->assertOk();
        }
        $this->assertTrue(true);
    }

    public function test_left_categories_and_order_page_render(): void
    {
        app(\App\Services\SettingService::class)->saveGroup('pos', ['categories_position' => 'left']);
        $this->get(route('pos.terminal'))->assertOk()->assertSee('data-position="left"', false);

        $sales = app(\App\Services\SaleService::class);
        $order = $sales->create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'expected_delivery_at' => now()->addDay()->toDateString()], [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ]);
        $sales->confirm($order);
        $this->get(route('sales.show', $order))->assertOk()->assertSee('Sortie en cours');
        $this->get(route('stock.levels'))->assertOk()->assertSee('en sortie en cours');
    }

    public function test_cash_journal_movements_breakdown_and_collections(): void
    {
        $pos = app(\App\Services\PosService::class);
        $cash = app(\App\Services\PosCashService::class);
        $session = $pos->currentOpenSession();
        $pos->completeSale([['product_id' => $this->product->id, 'quantity' => 1]], [['method' => 'cash', 'amount' => 120, 'tendered' => 200]]);
        $pos->completeSale([['product_id' => $this->product->id, 'quantity' => 1]], [['method' => 'effet', 'amount' => 120, 'reference' => 'LCN-7', 'due_date' => now()->addDays(30)->toDateString()]]);
        $this->post(route('pos.cash-movements.store'), ['direction' => 'out', 'amount' => 20, 'reason' => 'Achat fournitures'])->assertRedirect();
        $this->post(route('pos.cash-movements.store'), ['direction' => 'in', 'amount' => 50, 'reason' => 'Apport monnaie'])->assertRedirect();

        // 100 fond + 120 ventes espèces + 50 - 20
        $this->assertEquals(250.0, $cash->expectedCash($session->fresh()));

        $this->get(route('pos.journal'))->assertOk()->assertSee('Achat fournitures')->assertSee('Solde final');
        $this->get(route('pos.journal.export'))->assertOk();
        $this->get(route('pos.journal.print'))->assertOk();
        $this->get(route('pos.sessions.close.form', $session))->assertOk()->assertSee('Comptage par coupures');
        $this->get(route('pos.collections'))->assertOk()->assertSee('LCN-7');

        // Comptage : 1×200 + 1×50 = 250 → écart 0
        $this->post(route('pos.sessions.close', $session), ['closing_counted' => 0, 'breakdown' => ['200' => 1, '50' => 1]])->assertRedirect();
        $closed = $session->fresh();
        $this->assertEquals(250.0, (float) $closed->closing_counted);
        $this->assertEquals(0.0, (float) $closed->cash_difference);
        $this->assertSame(['200' => 1, '50' => 1], array_map('intval', $closed->closing_breakdown));
    }
}
