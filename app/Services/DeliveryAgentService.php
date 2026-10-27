<?php

namespace App\Services;

use App\Models\DeliveryAgent;
use App\Models\Sale;
use App\Support\Workspace;

class DeliveryAgentService
{
    public function create(array $data): DeliveryAgent
    {
        $company = Workspace::company();

        return DeliveryAgent::query()->create([
            'company_id' => $company->id,
            'store_id' => $data['store_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'internal',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'commission_percent' => $data['commission_percent'] ?? 0,
            'commission_fixed' => $data['commission_fixed'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'platform_name' => $data['platform_name'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(DeliveryAgent $agent, array $data): DeliveryAgent
    {
        $agent->update([
            'store_id' => $data['store_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'internal',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'commission_percent' => $data['commission_percent'] ?? 0,
            'commission_fixed' => $data['commission_fixed'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $agent->is_active,
            'platform_name' => $data['platform_name'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $agent->fresh();
    }

    public function softDelete(DeliveryAgent $agent): void
    {
        $agent->update(['is_active' => false]);
        $agent->delete();
    }

    /**
     * @return array{deliveries:int,revenue:float,commission:float,avg_ticket:float}
     */
    public function performance(DeliveryAgent $agent, ?string $from = null, ?string $to = null): array
    {
        $query = Sale::query()
            ->forCompany($agent->company_id)
            ->where('delivery_agent_id', $agent->id)
            ->whereNotIn('status', ['draft', 'cancelled']);

        if ($from) {
            $query->whereDate('sold_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('sold_at', '<=', $to);
        }

        $sales = $query->get(['id', 'total_ttc', 'delivery_commission']);
        $deliveries = $sales->count();
        $revenue = (float) $sales->sum('total_ttc');
        $commission = (float) $sales->sum(function (Sale $sale) use ($agent) {
            if ($sale->delivery_commission !== null) {
                return (float) $sale->delivery_commission;
            }

            return round(
                ((float) $sale->total_ttc * (float) $agent->commission_percent / 100)
                + (float) $agent->commission_fixed,
                2
            );
        });

        return [
            'deliveries' => $deliveries,
            'revenue' => $revenue,
            'commission' => $commission,
            'avg_ticket' => $deliveries > 0 ? round($revenue / $deliveries, 2) : 0.0,
        ];
    }

    /**
     * @return array<int, array{agent:DeliveryAgent,stats:array}>
     */
    public function ranking(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $agents = DeliveryAgent::query()->forCompany($companyId)->orderBy('name')->get();

        return $agents->map(fn (DeliveryAgent $agent) => [
            'agent' => $agent,
            'stats' => $this->performance($agent, $from, $to),
        ])->sortByDesc(fn ($row) => $row['stats']['revenue'])->values()->all();
    }
}
