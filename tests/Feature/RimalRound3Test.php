<?php

namespace Tests\Feature;

use App\Models\PosSale;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Services\HeldTicketArchiver;
use App\Services\PurchaseService;

class RimalRound3Test extends RimalPosViewsTest
{
    public function test_supplier_return_flow_moves_stock_out_and_records_credit_note(): void
    {
        $this->product->forceFill(['track_stock' => true])->save();
        $supplier = Supplier::query()->create(['company_id' => $this->company->id, 'name' => 'Minoterie Test', 'code' => 'F-T1', 'status' => 'active']);
        $purchases = app(PurchaseService::class);
        $order = $purchases->createOrder(['store_id' => $this->store->id, 'supplier_id' => $supplier->id], [
            ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 5, 'tax_rate' => 20],
        ]);
        $purchases->confirm($order);
        $line = $order->fresh('lines')->lines->first();
        $receipt = $purchases->createReceipt($order->fresh(), [$line->id => 10]);
        $purchases->validateReceipt($receipt);
        $rLine = $receipt->fresh('lines')->lines->first();

        $level = fn () => (float) \App\Models\StockLevel::query()->where('product_id', $this->product->id)->where('store_id', $this->store->id)->value('quantity');
        $before = $level();

        $this->get(route('purchases.returns.index'))->assertOk()->assertSee('Aucun retour fournisseur');
        $this->get(route('purchases.returns.create', ['receipt' => $receipt->id]))->assertOk()->assertSee($this->product->name);

        // dépassement refusé
        $this->post(route('purchases.returns.store'), ['receipt_id' => $receipt->id, 'quantities' => [$rLine->id => 11]])->assertSessionHasErrors();

        $this->post(route('purchases.returns.store'), ['receipt_id' => $receipt->id, 'reason' => 'Produit défectueux', 'quantities' => [$rLine->id => 3]])->assertRedirect();
        $ret = SupplierReturn::query()->firstOrFail();
        $this->assertSame('draft', $ret->status);
        $this->assertEquals(15.0, (float) $ret->total_ht);
        $this->assertEquals(18.0, (float) $ret->total_ttc);
        $this->assertEquals($before, $level());

        $this->post(route('purchases.returns.validate', $ret))->assertRedirect();
        $this->assertSame('validated', $ret->fresh()->status);
        $this->assertEquals($before - 3, $level());

        $this->post(route('purchases.returns.credit', $ret), ['credit_note_number' => 'AV-77', 'credit_note_amount' => 18])->assertRedirect();
        $this->assertSame('credited', $ret->fresh()->status);

        $this->get(route('purchases.returns.show', $ret))->assertOk()->assertSee('AV-77');
        $this->get(route('purchases.returns.print', $ret))->assertOk()->assertSee('Bon de retour fournisseur');
        $this->get(route('accounting.home'))->assertOk()->assertSee('Avoirs fournisseurs attendus');
    }

    public function test_old_held_tickets_are_archived_not_deleted(): void
    {
        $pos = app(\App\Services\PosService::class);
        $pos->currentOpenSession();
        $old = $pos->holdSale([['product_id' => $this->product->id, 'quantity' => 1]]);
        $old->forceFill(['held_at' => now()->subDays(40)])->save();
        $recent = $pos->holdSale([['product_id' => $this->product->id, 'quantity' => 1]]);

        $n = app(HeldTicketArchiver::class)->archiveStale($this->company);
        $this->assertSame(1, $n);
        $this->assertNotNull($old->fresh()->archived_at);
        $this->assertNull($recent->fresh()->archived_at);
        $this->assertSame(2, PosSale::query()->where('status', 'held')->count());

        $this->followingRedirects()->get(route('pos.tickets.index', ['status' => 'archived']))->assertOk()->assertSee($old->number)->assertDontSee($recent->number);
        $this->post(route('pos.tickets.archive', $old))->assertRedirect();
        $this->assertNull($old->fresh()->archived_at);
        $this->post(route('pos.tickets.archive', $recent))->assertRedirect();
        $this->assertNotNull($recent->fresh()->archived_at);
    }

    public function test_contact_title_uses_app_name(): void
    {
        config(['app.name' => 'RIM POS']);
        $this->get('/contact')->assertOk()->assertSee('Contact — RIM POS', false);
    }
}
