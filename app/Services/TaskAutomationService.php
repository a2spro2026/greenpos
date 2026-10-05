<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\PosSale;
use App\Models\Sale;
use App\Models\StockLevel;
use Illuminate\Support\Facades\Schema;

/**
 * Ops automation bridged onto CrmActivity (tasks / rendez-vous).
 * Creates tasks for low stock and other rules, advances recurrence, marks reminders.
 */
class TaskAutomationService
{
    public function ensureDefaultRule(Company|int $company): void
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        if (AutomationRule::query()->forCompany($companyId)->exists()) {
            return;
        }

        AutomationRule::query()->create([
            'company_id' => $companyId,
            'name' => 'Alerte stock faible',
            'trigger' => 'low_stock',
            'is_active' => true,
            'config' => ['dedupe_hours' => 24],
        ]);
    }

    public function runCompany(int $companyId): array
    {
        $this->ensureDefaultRule($companyId);
        $created = 0;
        $reminders = 0;
        $recurrences = 0;

        $rules = AutomationRule::query()->forCompany($companyId)->where('is_active', true)->get();
        foreach ($rules as $rule) {
            $created += match ($rule->trigger) {
                'low_stock' => $this->runLowStock($companyId, $rule),
                'sales_threshold' => $this->runSalesThreshold($companyId, $rule),
                'time_based' => $this->runTimeBased($companyId, $rule),
                'production_event' => $this->runProduction($companyId, $rule),
                'custom' => $this->runCustom($companyId, $rule),
                default => 0,
            };
            $rule->update(['last_ran_at' => now()]);
        }

        $reminders = $this->markDueReminders($companyId);
        $recurrences = $this->advanceRecurrence($companyId);

        return [
            'tasks' => $created,
            'reminders' => $reminders,
            'recurrences' => $recurrences,
        ];
    }

    public function runAll(): array
    {
        $totals = ['companies' => 0, 'tasks' => 0, 'reminders' => 0, 'recurrences' => 0];
        if (! Schema::hasTable('automation_rules')) {
            return $totals;
        }

        Company::query()->where('status', 'active')->pluck('id')->each(function ($id) use (&$totals) {
            $result = $this->runCompany((int) $id);
            $totals['companies']++;
            $totals['tasks'] += $result['tasks'];
            $totals['reminders'] += $result['reminders'];
            $totals['recurrences'] += $result['recurrences'];
        });

        return $totals;
    }

    private function runLowStock(int $companyId, AutomationRule $rule): int
    {
        if (! Schema::hasTable('stock_levels')) {
            return 0;
        }
        $hours = (int) ($rule->config['dedupe_hours'] ?? 24);
        $created = 0;

        $levels = StockLevel::query()
            ->where('company_id', $companyId)
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->with('product')
            ->get();

        foreach ($levels as $level) {
            $exists = CrmActivity::query()
                ->forCompany($companyId)
                ->where('type', 'task')
                ->where('status', 'planned')
                ->where('created_at', '>=', now()->subHours($hours))
                ->where('meta->stock_level_id', $level->id)
                ->exists();
            if ($exists) {
                continue;
            }
            $name = $level->product?->name ?? ('Produit #'.$level->product_id);
            CrmActivity::query()->create([
                'company_id' => $companyId,
                'type' => 'task',
                'status' => 'planned',
                'subject' => 'Stock faible : '.$name,
                'body' => 'Quantité '.$level->quantity.' ≤ seuil '.$level->min_quantity.'.',
                'due_at' => now()->addHours(4),
                'priority' => ((float) $level->quantity) <= 0 ? 'high' : 'normal',
                'meta' => [
                    'source' => 'automation',
                    'trigger' => 'low_stock',
                    'rule_id' => $rule->id,
                    'stock_level_id' => $level->id,
                    'product_id' => $level->product_id,
                ],
            ]);
            $created++;
        }

        return $created;
    }

    private function runSalesThreshold(int $companyId, AutomationRule $rule): int
    {
        $threshold = (float) ($rule->config['amount'] ?? 0);
        if ($threshold <= 0) {
            return 0;
        }
        $pos = (float) PosSale::query()->forCompany($companyId)->where('status', 'completed')->whereDate('completed_at', today())->sum('total_ttc');
        $sales = (float) Sale::query()->forCompany($companyId)->whereDate('sold_at', today())->whereNotIn('status', ['draft', 'cancelled'])->sum('total_ttc');
        $total = $pos + $sales;
        if ($total + 0.009 < $threshold) {
            return 0;
        }
        $exists = CrmActivity::query()
            ->forCompany($companyId)
            ->where('type', 'task')
            ->whereDate('created_at', today())
            ->where('meta->rule_id', $rule->id)
            ->exists();
        if ($exists) {
            return 0;
        }
        CrmActivity::query()->create([
            'company_id' => $companyId,
            'type' => 'task',
            'status' => 'planned',
            'subject' => 'Seuil de ventes atteint',
            'body' => 'Total du jour '.number_format($total, 2, ',', ' ').' ≥ '.number_format($threshold, 2, ',', ' ').'.',
            'due_at' => now()->endOfDay(),
            'priority' => 'normal',
            'meta' => ['source' => 'automation', 'trigger' => 'sales_threshold', 'rule_id' => $rule->id, 'total' => $total],
        ]);

        return 1;
    }

    private function runTimeBased(int $companyId, AutomationRule $rule): int
    {
        $hour = (int) ($rule->config['hour'] ?? 9);
        if ((int) now()->format('G') < $hour) {
            return 0;
        }
        if ($rule->last_ran_at && $rule->last_ran_at->isSameDay(now()) && $rule->last_ran_at->hour >= $hour) {
            return 0;
        }
        $subject = $rule->config['subject'] ?? $rule->name;
        CrmActivity::query()->create([
            'company_id' => $companyId,
            'type' => 'task',
            'status' => 'planned',
            'subject' => $subject,
            'body' => $rule->config['body'] ?? 'Tâche planifiée.',
            'due_at' => now()->endOfDay(),
            'priority' => $rule->config['priority'] ?? 'normal',
            'meta' => ['source' => 'automation', 'trigger' => 'time_based', 'rule_id' => $rule->id],
        ]);

        return 1;
    }

    private function runProduction(int $companyId, AutomationRule $rule): int
    {
        if (! Schema::hasTable('productions') && ! Schema::hasTable('production_entries')) {
            return 0;
        }

        return 0;
    }

    private function runCustom(int $companyId, AutomationRule $rule): int
    {
        $subject = $rule->config['subject'] ?? null;
        if (! $subject) {
            return 0;
        }
        if ($rule->last_ran_at && $rule->last_ran_at->isSameDay(now())) {
            return 0;
        }
        CrmActivity::query()->create([
            'company_id' => $companyId,
            'type' => 'task',
            'status' => 'planned',
            'subject' => $subject,
            'body' => $rule->config['body'] ?? null,
            'due_at' => now()->addDay(),
            'priority' => 'normal',
            'meta' => ['source' => 'automation', 'trigger' => 'custom', 'rule_id' => $rule->id],
        ]);

        return 1;
    }

    private function markDueReminders(int $companyId): int
    {
        $count = 0;
        CrmActivity::query()
            ->forCompany($companyId)
            ->where('status', 'planned')
            ->whereNull('reminder_sent_at')
            ->where(function ($q) {
                $q->whereBetween('due_at', [now()->subHour(), now()->addHour()])
                    ->orWhereBetween('starts_at', [now()->subHour(), now()->addHour()]);
            })
            ->each(function (CrmActivity $activity) use (&$count) {
                $activity->update(['reminder_sent_at' => now()]);
                $count++;
            });

        return $count;
    }

    private function advanceRecurrence(int $companyId): int
    {
        $count = 0;
        CrmActivity::query()
            ->forCompany($companyId)
            ->where('status', 'planned')
            ->whereIn('recurrence', ['daily', 'weekly', 'monthly'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->each(function (CrmActivity $activity) use (&$count) {
                $next = match ($activity->recurrence) {
                    'daily' => $activity->due_at->copy()->addDay(),
                    'weekly' => $activity->due_at->copy()->addWeek(),
                    'monthly' => $activity->due_at->copy()->addMonth(),
                    default => null,
                };
                if (! $next) {
                    return;
                }
                if ($activity->recurrence_until && $next->toDateString() > $activity->recurrence_until->toDateString()) {
                    return;
                }
                $activity->update(['due_at' => $next, 'reminder_sent_at' => null]);
                $count++;
            });

        return $count;
    }
}
