<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\CrmIncident;
use App\Models\CustomList;
use App\Models\DeliveryPlatform;
use App\Models\IncidentTypeAssignment;
use App\Models\OptionVariant;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\User;
use App\Services\CollectionService;
use App\Services\CustomListService;
use App\Services\IncidentAssignmentService;
use App\Services\PosService;
use App\Services\SaleRefundService;
use App\Services\SaleService;
use App\Services\StockService;
use App\Services\TaskAutomationService;
use App\Support\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_parity_behaviors(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => 'Parity Café',
            'currency' => 'MAD',
            'status' => 'active',
        ]);
        $store = Store::query()->create([
            'company_id' => $company->id,
            'name' => 'Comptoir',
            'is_active' => true,
        ]);
        $user->companies()->attach($company->id, [
            'role' => 'owner',
            'status' => 'active',
            'is_primary' => true,
        ]);
        $this->actingAs($user);
        Workspace::set($company, $store);

        $lists = app(CustomListService::class);
        $lists->ensureDefaults($company);
        $lists->ensureDefaults($company);
        $this->assertSame(1, CustomList::query()->forCompany($company->id)->where('type', 'mode_de_paiement')->where('code', 'cash')->count());
        $this->assertTrue(CustomList::query()->forCompany($company->id)->where('type', 'mode_de_service')->where('code', 'delivery')->exists());

        $product = Product::query()->create([
            'company_id' => $company->id,
            'type' => 'physical',
            'name' => 'Café',
            'slug' => 'cafe',
            'sku' => 'CAF-1',
            'sale_price' => 10,
            'tax_rate' => 0,
            'track_stock' => true,
            'status' => 'active',
            'unit' => 'pce',
        ]);
        app(StockService::class)->applyMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 20,
            'moved_at' => now(),
        ]);

        PosSession::query()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'opened_by' => $user->id,
            'number' => 'CS-1',
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $credit = CustomList::query()->forCompany($company->id)->where('code', 'credit')->firstOrFail();
        $pos = app(PosService::class);
        $deferred = $pos->completeSale(
            [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]],
            [[
                'method' => 'credit',
                'custom_list_id' => $credit->id,
                'amount' => 10,
                'due_date' => now()->addDays(3)->toDateString(),
            ]]
        );
        $this->assertSame('to_collect', $deferred->fresh()->payment_status_code);
        $payment = $deferred->payments()->first();
        $this->assertTrue($payment->is_deferred);
        $this->assertSame('scheduled', $payment->collection_status);
        app(CollectionService::class)->collect($payment);
        $this->assertSame('collected', $deferred->fresh()->payment_status_code);
        $this->assertSame('collected', $payment->fresh()->collection_status);

        $option = ProductOption::query()->create([
            'company_id' => $company->id,
            'name' => 'Supplément',
            'selection_mode' => 'fixed',
            'is_active' => true,
        ]);
        $variant = OptionVariant::query()->create([
            'product_option_id' => $option->id,
            'name' => 'Lait',
            'extra_price' => 5,
            'is_active' => true,
        ]);
        $product->options()->attach($option->id);
        $withExtra = $pos->completeSale(
            [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10, 'option_variant_ids' => [$variant->id]]],
            [['method' => 'cash', 'amount' => 15, 'tendered' => 15]]
        );
        $line = $withExtra->lines()->first();
        $this->assertEquals(15.0, (float) $line->unit_price);
        $this->assertEquals(15.0, (float) $withExtra->total_ttc);
        $this->assertSame('paid', $withExtra->fresh()->payment_status_code);

        $platform = DeliveryPlatform::query()->create([
            'company_id' => $company->id,
            'name' => 'Glovo',
            'code' => 'glovo',
            'kind' => 'external',
            'is_active' => true,
            'is_delivery_agent' => true,
        ]);
        $synced = $lists->syncPlatformServiceMode($platform);
        $this->assertSame('mode_de_service', $synced->type);
        $this->assertSame('platform-'.$platform->id, $synced->code);
        $this->assertTrue($synced->meta('requires_delivery_agent'));
        $this->assertSame('delivery', $synced->meta('operational_mode'));

        $agent = User::factory()->create();
        $agent->companies()->attach($company->id, ['role' => 'manager', 'status' => 'active', 'is_primary' => false]);
        IncidentTypeAssignment::query()->create([
            'company_id' => $company->id,
            'incident_type' => 'complaint',
            'user_id' => $agent->id,
            'is_active' => true,
        ]);
        $incident = CrmIncident::query()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'number' => 'INC-1',
            'type' => 'complaint',
            'subject' => 'Retard',
            'status' => 'open',
        ]);
        $assigned = app(IncidentAssignmentService::class)->assignIfEmpty($incident);
        $this->assertSame($agent->id, $assigned->assignee_user_id);

        StockLevel::query()->where('product_id', $product->id)->update([
            'quantity' => 1,
            'min_quantity' => 5,
        ]);
        $result = app(TaskAutomationService::class)->runCompany($company->id);
        $this->assertGreaterThanOrEqual(1, $result['tasks']);
        $this->assertTrue(CrmActivity::query()->forCompany($company->id)->where('type', 'task')->where('subject', 'like', 'Stock faible%')->exists());

        $sale = Sale::query()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'created_by' => $user->id,
            'number' => 'VT-1',
            'status' => 'confirmed',
            'sold_at' => now()->toDateString(),
            'subtotal_ht' => 100,
            'tax_total' => 0,
            'total_ttc' => 100,
            'amount_paid' => 100,
            'currency' => 'MAD',
        ]);
        $saleLine = SaleLine::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 50,
            'tax_rate' => 0,
            'line_subtotal' => 100,
            'line_tax' => 0,
            'line_total' => 100,
        ]);
        $before = (float) StockLevel::query()->where('product_id', $product->id)->value('quantity');
        $refund = app(SaleRefundService::class)->refundSale($sale, [
            'amount' => 40,
            'method' => 'cash',
            'reason' => 'Client mécontent',
            'restock' => true,
            'lines' => [['line_id' => $saleLine->id, 'quantity' => 1]],
        ]);
        $this->assertSame('partial', $refund->type);
        $this->assertSame('restocked', $refund->status);
        $this->assertEquals(1.0, (float) $saleLine->fresh()->returned_quantity);
        $this->assertEquals(40.0, (float) $sale->fresh()->amount_returned);
        $this->assertEquals($before + 1, (float) StockLevel::query()->where('product_id', $product->id)->value('quantity'));

        $creditSale = Sale::query()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'number' => 'VT-2',
            'status' => 'confirmed',
            'sold_at' => now()->toDateString(),
            'total_ttc' => 80,
            'amount_paid' => 0,
            'currency' => 'MAD',
        ]);
        $salePayment = app(SaleService::class)->recordPayment($creditSale, [
            'amount' => 80,
            'method' => 'credit',
            'custom_list_id' => $credit->id,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);
        $this->assertTrue($salePayment->is_deferred);
        $this->assertEquals(0.0, (float) $creditSale->fresh()->amount_paid);
        $this->assertSame('to_collect', $creditSale->fresh()->payment_status_code);
        app(CollectionService::class)->collect($salePayment);
        $this->assertEquals(80.0, (float) $creditSale->fresh()->amount_paid);
        $this->assertSame('collected', $creditSale->fresh()->payment_status_code);
    }
}
