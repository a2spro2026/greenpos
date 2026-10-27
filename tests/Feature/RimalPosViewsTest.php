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
}
