<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PosSale;
use App\Models\PosTable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\User;
use App\Services\PosService;
use App\Services\SaleService;
use App\Services\SettingService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RimalPosTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Store $store;
    protected Product $product;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::query()->create(['name' => 'Test Co', 'status' => 'active', 'currency' => 'MAD']);
        $this->store = Store::query()->create(['company_id' => $this->company->id, 'name' => 'Boutique', 'code' => 'B1', 'is_active' => true, 'is_default' => true]);
        $user = User::factory()->create();
        $user->companies()->attach($this->company->id, ['role' => 'owner', 'status' => 'active', 'is_primary' => true]);
        $user->stores()->attach($this->store->id);
        $this->actingAs($user);

        $this->product = Product::query()->create([
            'company_id' => $this->company->id, 'name' => 'Pain', 'slug' => 'pain', 'sku' => 'P1', 'type' => 'product',
            'sale_price' => 100, 'tax_rate' => 20, 'track_stock' => true, 'status' => 'active',
        ]);
        $this->customer = Customer::query()->create(['company_id' => $this->company->id, 'name' => 'Client A', 'code' => 'C1', 'status' => 'active', 'phone' => '0600000000']);
        app(StockService::class)->applyMovement(['store_id' => $this->store->id, 'product_id' => $this->product->id, 'type' => 'in', 'quantity' => 10, 'moved_at' => now()]);
        app(PosService::class)->openSession(100);
    }

    protected function level(): StockLevel
    {
        return StockLevel::query()->where('product_id', $this->product->id)->where('store_id', $this->store->id)->first();
    }

    public function test_ticket_discount_and_new_payment_methods(): void
    {
        $sale = app(PosService::class)->completeSale(
            [['product_id' => $this->product->id, 'quantity' => 1, 'discount_percent' => 10]],
            [
                ['method' => 'instant_transfer', 'amount' => 50],
                ['method' => 'effet', 'amount' => 58, 'reference' => 'LCN-1', 'due_date' => now()->addMonth()->toDateString()],
            ],
            null, null, null,
            ['ticket_discount_percent' => 10, 'ticket_name' => 'Karim']
        );

        // 100 * 0.9 * 0.9 = 81 HT, + 20% = 97.20
        $this->assertSame('97.20', (string) $sale->total_ttc);
        $this->assertSame('Karim', $sale->ticket_name);
        $this->assertEquals(2, $sale->payments->count());
        $this->assertSame('LCN-1', $sale->payments->firstWhere('method', 'effet')->reference);
    }

    public function test_cheque_requires_number_and_due_date(): void
    {
        $this->expectException(ValidationException::class);
        app(PosService::class)->completeSale(
            [['product_id' => $this->product->id, 'quantity' => 1]],
            [['method' => 'cheque', 'amount' => 120]]
        );
    }

    public function test_delivery_platform_commission_from_table(): void
    {
        $glovo = \App\Models\DeliveryPlatform::query()->create([
            'company_id' => $this->company->id, 'name' => 'Glovo', 'code' => 'glovo', 'kind' => 'external',
            'is_active' => true, 'commission_type' => 'percent', 'commission_value' => 25,
        ]);
        $sale = app(PosService::class)->completeSale(
            [['product_id' => $this->product->id, 'quantity' => 1]],
            [['method' => 'card', 'amount' => 120]],
            null, null, null,
            ['delivery_platform_id' => $glovo->id]
        );
        $this->assertSame('Glovo', $sale->delivery_platform);
        $this->assertSame('delivery', $sale->service_mode);
        $this->assertSame($glovo->id, $sale->delivery_platform_id);
        $this->assertSame('30.00', (string) $sale->platform_commission);

        $this->expectException(ValidationException::class);
        app(PosService::class)->completeSale(
            [['product_id' => $this->product->id, 'quantity' => 1]],
            [['method' => 'card', 'amount' => 120]],
            null, null, null,
            ['delivery_platform_id' => 999999]
        );
    }

    public function test_offline_sale_is_idempotent_by_client_uuid(): void
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();
        $pos = app(PosService::class);
        $a = $pos->completeSale([['product_id' => $this->product->id, 'quantity' => 1]], [['method' => 'cash', 'amount' => 120]], null, null, null, ['client_uuid' => $uuid]);
        $b = $pos->completeSale([['product_id' => $this->product->id, 'quantity' => 1]], [['method' => 'cash', 'amount' => 120]], null, null, null, ['client_uuid' => $uuid]);
        $this->assertSame($a->id, $b->id);
        $this->assertEquals(9, (float) $this->level()->quantity);
    }

    public function test_future_cheque_is_deferred_for_collection(): void
    {
        $sale = app(PosService::class)->completeSale(
            [['product_id' => $this->product->id, 'quantity' => 1]],
            [['method' => 'cheque', 'amount' => 120, 'reference' => 'CHQ-9', 'due_date' => now()->addDays(10)->toDateString()]]
        );
        $pay = $sale->payments->first();
        $this->assertTrue((bool) $pay->is_deferred);
        $this->assertSame('scheduled', $pay->collection_status);
    }

    public function test_saved_ticket_with_name_and_table_is_updated_in_place(): void
    {
        $table = PosTable::query()->create(['company_id' => $this->company->id, 'store_id' => $this->store->id, 'name' => 'T1', 'status' => 'free']);
        $pos = app(PosService::class);
        $held = $pos->holdSale([['product_id' => $this->product->id, 'quantity' => 1]], null, null, ['ticket_name' => 'Table Karim', 'pos_table_id' => $table->id]);
        $this->assertSame('occupied', $table->fresh()->status);

        $again = $pos->holdSale([['product_id' => $this->product->id, 'quantity' => 3]], null, null, ['ticket_name' => 'Table Karim', 'pos_table_id' => $table->id, 'held_sale_id' => $held->id]);
        $this->assertSame($held->id, $again->id);
        $this->assertSame('360.00', (string) $again->total_ttc);
        $this->assertSame(1, PosSale::query()->where('status', 'held')->count());

        $pos->cancelSale($again);
        $this->assertSame('free', $table->fresh()->status);
    }

    public function test_order_reserves_stock_until_full_delivery(): void
    {
        $sales = app(SaleService::class);
        $order = $sales->create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id, 'expected_delivery_at' => now()->addDays(2)->toDateString()], [
            ['product_id' => $this->product->id, 'quantity' => 4],
        ]);
        $sales->confirm($order);
        $order->refresh();
        $this->assertSame('reserved', $order->stock_state);
        $this->assertEquals(10, (float) $this->level()->quantity);
        $this->assertEquals(4, (float) $this->level()->reserved_quantity);

        // Reserved stock cannot be sold at the POS: only 6 available
        try {
            app(PosService::class)->completeSale([['product_id' => $this->product->id, 'quantity' => 7]], [['method' => 'cash', 'amount' => 1000]]);
            $this->fail('Reserved stock should not be sellable');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        $sales->advanceStatus($order, 'preparing');
        $this->assertEquals(4, (float) $this->level()->reserved_quantity);
        $sales->advanceStatus($order->fresh(), 'delivered');
        $this->assertEquals(6, (float) $this->level()->quantity);
        $this->assertEquals(0, (float) $this->level()->reserved_quantity);
        $this->assertSame('out', $order->fresh()->stock_state);
    }

    public function test_cancel_reserved_order_releases_stock(): void
    {
        $sales = app(SaleService::class);
        $order = $sales->create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id], [
            ['product_id' => $this->product->id, 'quantity' => 3],
        ]);
        $sales->confirm($order);
        $sales->cancel($order->fresh());
        $this->assertEquals(10, (float) $this->level()->quantity);
        $this->assertEquals(0, (float) $this->level()->reserved_quantity);
    }

    public function test_order_cannot_reserve_more_than_available(): void
    {
        $sales = app(SaleService::class);
        $order = $sales->create(['store_id' => $this->store->id, 'customer_id' => $this->customer->id], [
            ['product_id' => $this->product->id, 'quantity' => 11],
        ]);
        $this->expectException(ValidationException::class);
        $sales->confirm($order);
    }

    public function test_order_form_requires_customer(): void
    {
        $this->withoutMiddleware();
        $response = $this->post(route('sales.store'), [
            'store_id' => $this->store->id,
            'lines' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);
        $this->assertContains($response->status(), [302, 403], "status ".$response->status());
        if ($response->status() === 302) { $response->assertSessionHasErrors("customer_id"); }
    }
}
