<?php

namespace App\Services;

use App\Models\CrmIncident;
use App\Models\IncidentTypeAssignment;

class IncidentAssignmentService
{
    public function resolveAssigneeId(int $companyId, string $type): ?int
    {
        $row = IncidentTypeAssignment::query()
            ->where('company_id', $companyId)
            ->where('incident_type', $type)
            ->where('is_active', true)
            ->first();

        return $row?->user_id;
    }

    public function assignIfEmpty(CrmIncident $incident): CrmIncident
    {
        if ($incident->assignee_user_id) {
            return $incident;
        }
        $userId = $this->resolveAssigneeId($incident->company_id, $incident->type);
        if ($userId) {
            $incident->update(['assignee_user_id' => $userId]);
        }

        return $incident->fresh();
    }

    /** @return array<string, int> */
    public function statistics(int $companyId): array
    {
        $base = CrmIncident::query()->forCompany($companyId);

        return [
            'open' => (clone $base)->where('status', 'open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'resolved' => (clone $base)->whereIn('status', ['resolved', 'closed'])->count(),
            'total' => (clone $base)->count(),
        ];
    }
}
