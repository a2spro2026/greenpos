<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmAutomation;
use App\Models\CrmIncident;
use App\Models\Employee;
use App\Models\StockLevel;
use App\Support\Workspace;

class CrmAutomationService
{
    public function __construct(
        private ReminderService $reminders,
        private NotificationService $notifications,
    ) {
    }

    public function run(CrmAutomation $automation): array
    {
        $company = Workspace::company();
        $results = [];
        $conditions = $automation->conditions ?? [];
        $actions = $automation->actions ?? [];

        $shouldRun = match ($automation->trigger_type) {
            'low_stock' => StockLevel::query()
                ->where('company_id', $company->id)
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->exists(),
            'sales_threshold' => true,
            'production', 'time_based', 'custom' => true,
            default => false,
        };

        if (! $shouldRun && ($conditions['force'] ?? false) !== true) {
            return ['skipped' => true];
        }

        foreach ($actions as $action) {
            $type = $action['type'] ?? null;
            if ($type === 'create_incident') {
                $incident = CrmIncident::query()->create([
                    'company_id' => $company->id,
                    'store_id' => Workspace::store()?->id,
                    'type' => $action['incident_type'] ?? $automation->trigger_type,
                    'priority' => $action['priority'] ?? 'medium',
                    'status' => 'open',
                    'title' => $action['title'] ?? ('Auto: '.$automation->name),
                    'description' => $action['description'] ?? null,
                    'assigned_to' => $this->resolveAssignee($action),
                    'auto_assigned' => true,
                    'created_by' => Workspace::user()?->id,
                ]);
                $results[] = ['incident' => $incident->id];
            } elseif ($type === 'create_task') {
                $task = CrmActivity::query()->create([
                    'company_id' => $company->id,
                    'owner_user_id' => Workspace::user()?->id,
                    'type' => 'task',
                    'subject' => $action['title'] ?? $automation->name,
                    'body' => $action['description'] ?? null,
                    'status' => 'planned',
                    'priority' => $action['priority'] ?? 'medium',
                    'due_at' => now()->addDay(),
                ]);
                $results[] = ['task' => $task->id];
            } elseif ($type === 'send_reminder') {
                $reminder = $this->reminders->schedule([
                    'channel' => $action['channel'] ?? 'notification',
                    'recipient' => $action['recipient'] ?? (Workspace::user()?->email ?? 'owner'),
                    'message' => $action['message'] ?? $automation->name,
                    'scheduled_at' => now(),
                ]);
                $results[] = ['reminder' => $reminder->id];
            } elseif ($type === 'notify') {
                $this->notifications->dispatch([
                    'title' => $action['title'] ?? $automation->name,
                    'body' => $action['body'] ?? null,
                    'category' => 'crm',
                    'type' => 'warning',
                    'channels' => ['internal' => true],
                ]);
                $results[] = ['notify' => true];
            }
        }

        $automation->update(['last_run_at' => now()]);

        return $results;
    }

    public function runAllActive(?string $trigger = null): int
    {
        $company = Workspace::company();
        $query = CrmAutomation::query()->forCompany($company->id)->where('is_active', true);
        if ($trigger) {
            $query->where('trigger_type', $trigger);
        }

        $count = 0;
        foreach ($query->get() as $automation) {
            $this->run($automation);
            $count++;
        }

        return $count;
    }

    protected function resolveAssignee(array $action): ?int
    {
        if (! empty($action['assigned_to'])) {
            return (int) $action['assigned_to'];
        }

        if (! empty($action['employee_job_role'])) {
            $emp = Employee::query()
                ->forCompany(Workspace::company()->id)
                ->where('job_role', $action['employee_job_role'])
                ->where('status', 'active')
                ->whereNotNull('user_id')
                ->first();

            return $emp?->user_id;
        }

        return null;
    }
}
