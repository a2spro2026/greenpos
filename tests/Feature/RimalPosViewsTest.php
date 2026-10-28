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

    public function test_phase4_pilotage_reports_exports_bl_and_documents(): void
    {
        $this->product->update(['purchase_price' => 40]);
        $pos = app(\App\Services\PosService::class);
        $pos->completeSale([['product_id' => $this->product->id, 'quantity' => 2]], [['method' => 'cash', 'amount' => 240, 'tendered' => 240]]);
        $sales = app(\App\Services\SaleService::class);
        $order = $sales->create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'expected_delivery_at' => now()->addDay()->toDateString()], [
            ['product_id' => $this->product->id, 'quantity' => 1],
        ]);
        $sales->confirm($order);

        $m = app(\App\Services\RimalReportService::class)->metrics($this->company->id, now()->startOfMonth(), now()->endOfDay());
        $this->assertEquals(240.0, $m['collected']);
        $this->assertEquals(200.0 + 100.0 - 120.0, $m['margin']); // HT POS 200 + commande 100 − coût 3×40

        $this->get(route('reports.pilotage', ['store_id' => 'all']))->assertOk()->assertSee('Trésorerie nette')->assertSee('Consolidé');
        $this->get(route('reports.pilotage.export'))->assertOk();
        foreach (array_keys(\App\Services\RimalReportService::REPORTS) as $type) {
            $this->get(route('reports.extra', $type))->assertOk();
            $this->get(route('reports.extra.print', $type))->assertOk()->assertSee('Enregistrer en PDF');
            $x = $this->get(route('reports.extra.export', $type))->assertOk();
            $this->assertStringContainsString('<Workbook', $x->streamedContent());
        }
        $this->get(route('reports.extra', 'commandes'))->assertSee($order->number)->assertSee('Sortie en cours');
        $this->get(route('reports.extra.print', 'pilotage'))->assertOk();
        $this->get(route('sales.delivery-note', $order))->assertOk()->assertSee('BON DE LIVRAISON')->assertSee('Test Co');
        $this->get(route('reports.documents'))->assertOk()->assertSee('BL-'.$order->number);
        $this->get(route('reports.documents', ['type' => 'ticket']))->assertOk()->assertDontSee('BL-'.$order->number);
    }

    public function test_phase5_offline_sync_idempotent_conflicts_and_terminal(): void
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();
        $ticket = ['client_uuid' => $uuid, 'offline_at' => now()->subMinutes(5)->toIso8601String(),
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payments' => [['method' => 'cheque', 'amount' => 120, 'reference' => 'CHQ-9', 'due_date' => now()->toDateString()]],
            'ticket_discount_percent' => 0, 'ticket_name' => 'Hors ligne 1'];

        $r1 = $this->postJson(route('pos.offline.sync'), ['sales' => [$ticket]])->assertOk()->json('results.0');
        $this->assertTrue($r1['ok']);
        $r2 = $this->postJson(route('pos.offline.sync'), ['sales' => [$ticket]])->assertOk()->json('results.0');
        $this->assertTrue($r2['ok']);
        $this->assertTrue($r2['duplicate']);
        $this->assertSame(1, \App\Models\PosSale::query()->where('client_uuid', $uuid)->count());
        $sale = \App\Models\PosSale::query()->where('client_uuid', $uuid)->first();
        $this->assertSame('CHQ-9', $sale->payments->first()->reference);
        $this->assertStringContainsString('Saisi hors ligne', (string) $sale->notes);

        // Conflit : stock insuffisant -> consigné, jamais perdu
        $bad = ['client_uuid' => (string) \Illuminate\Support\Str::uuid(), 'items' => [['product_id' => $this->product->id, 'quantity' => 50]], 'payments' => [['method' => 'cash', 'amount' => 6000]]];
        $r3 = $this->postJson(route('pos.offline.sync'), ['sales' => [$bad]])->assertOk()->json('results.0');
        $this->assertFalse($r3['ok']);
        $this->assertTrue($r3['conflict']);
        $conflict = \App\Models\PosSyncConflict::query()->where('client_uuid', $bad['client_uuid'])->firstOrFail();
        $this->assertSame('open', $conflict->status);
        $this->get(route('pos.sync-conflicts.index'))->assertOk()->assertSee($bad['client_uuid']);

        // Réapprovisionnement puis « Réessayer » -> synchronisé
        app(\App\Services\StockService::class)->applyMovement(['store_id' => $this->store->id, 'product_id' => $this->product->id, 'type' => 'in', 'quantity' => 100, 'moved_at' => now()]);
        $this->post(route('pos.sync-conflicts.retry', $conflict))->assertRedirect();
        $this->assertSame('synced', $conflict->fresh()->status);
        $this->assertNotNull($conflict->fresh()->pos_sale_id);

        // Classement manuel
        $bad2 = ['client_uuid' => (string) \Illuminate\Support\Str::uuid(), 'items' => [['product_id' => 999999, 'quantity' => 1]], 'payments' => [['method' => 'cash', 'amount' => 10]]];
        $this->postJson(route('pos.offline.sync'), ['sales' => [$bad2]])->assertOk()->assertJsonPath('results.0.conflict', true);
        $c2 = \App\Models\PosSyncConflict::query()->where('client_uuid', $bad2['client_uuid'])->firstOrFail();
        $this->post(route('pos.sync-conflicts.resolve', $c2), ['note' => 'produit supprimé'])->assertRedirect();
        $this->assertSame('resolved', $c2->fresh()->status);

        // Caisse fermée : erreur transitoire, pas de conflit
        \App\Models\PosSession::query()->update(['status' => 'closed', 'closed_at' => now()]);
        $t4 = ['client_uuid' => (string) \Illuminate\Support\Str::uuid(), 'items' => [['product_id' => $this->product->id, 'quantity' => 1]], 'payments' => [['method' => 'cash', 'amount' => 120]]];
        $r4 = $this->postJson(route('pos.offline.sync'), ['sales' => [$t4]])->assertOk()->json('results.0');
        $this->assertTrue($r4['retry'] ?? false);
        $this->assertSame(0, \App\Models\PosSyncConflict::query()->where('client_uuid', $t4['client_uuid'])->count());

        $snap = $this->getJson(route('pos.offline.snapshot'))->assertOk();
        $this->assertNotEmpty($snap->json('version'));
        $this->assertArrayHasKey('delivery_platforms', $snap->json());
        $this->assertArrayHasKey('effet', $snap->json('payment_methods'));
    }

    public function test_phase5_terminal_has_offline_integration(): void
    {
        $html = $this->get(route('pos.terminal'))->assertOk()->getContent();
        file_put_contents(sys_get_temp_dir().'/rimal_terminal.html', $html);
        $this->assertStringContainsString('pos-net-status', $html);
        $this->assertStringContainsString('/sw.js?v=', $html);
        $this->assertStringContainsString('js/pos-offline.js?v=', $html);
        $this->assertLessThan(strpos($html, 'const Offline = window.GreenPosOffline'), strpos($html, 'js/pos-offline.js?v='));
    }

    public function test_qa_fixes_treasury_terminal_order_advance_reports(): void
    {
        app(\App\Services\PosService::class)->completeSale([['product_id' => $this->product->id, 'quantity' => 1]], [['method' => 'cash', 'amount' => 120, 'tendered' => 120]]);
        $this->get(route('accounting.cash.index'))->assertOk()->assertSee('Trésorerie (caisse &amp; banque)', false)->assertSee('120,00');

        $html = $this->get(route('pos.terminal'))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-method="bank_transfer"', $html); $this->assertStringNotContainsString('data-mix="check"', $html);
        $this->assertStringContainsString('pos-dark', $html);
        $this->assertStringContainsString('Reste à répartir', $html);

        $this->post(route('sales.store'), [
            'store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'confirm' => 1,
            'lines' => [['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 100, 'tax_rate' => 20]],
            'advance_amount' => 50, 'advance_method' => 'cash',
        ])->assertRedirect();
        $order = \App\Models\Sale::query()->latest('id')->first();
        $this->assertSame('reserved', $order->stock_state);
        $this->assertEquals(50.0, (float) $order->fresh()->amount_paid);
        $this->get(route('sales.show', $order))->assertOk()->assertSee('Sortie en cours')->assertSee('Bon de livraison');
        $this->get(route('sales.index'))->assertOk()->assertSee('Tickets de caisse (POS)');

        foreach (['sales', 'stock'] as $t) {
            $x = $this->get(route('reports.export', ['type' => $t, 'format' => 'xls']))->assertOk();
            $this->assertStringContainsString('<Workbook', $x->streamedContent());
        }
        $this->get(route('reports.sales'))->assertOk()->assertSee('Export Excel')->assertSee('Période');
        $this->get(route('settings.section', 'pos'))->assertOk()->assertDontSee('Configurez les options de cette section.');
        $this->get(route('account.subscription'))->assertOk()->assertSee('Modules activés');
        $this->followingRedirects()->get(route('products.categories.index'))->assertOk()->assertSee('Catégories');
        $this->post(route('products.categories.store'), ['name' => 'Viennoiseries'])->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Viennoiseries', 'company_id' => $this->company->id]);
    }
}
