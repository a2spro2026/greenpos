<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Support\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function upsertDaily(array $data): Attendance
    {
        $company = Workspace::company();
        $employee = Employee::query()
            ->forCompany($company->id)
            ->findOrFail($data['employee_id']);

        $workedHours = $data['worked_hours'] ?? $this->computeWorkedHours(
            $data['check_in'] ?? null,
            $data['check_out'] ?? null,
            (int) ($data['break_minutes'] ?? 0)
        );

        $payload = [
            'company_id' => $company->id,
            'store_id' => $data['store_id'] ?? $employee->store_id ?? Workspace::store()?->id,
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'break_minutes' => (int) ($data['break_minutes'] ?? 0),
            'worked_hours' => $workedHours,
            'status' => $data['status'] ?? 'present',
            'notes' => $data['notes'] ?? null,
            'created_by' => Workspace::user()?->id,
        ];

        return Attendance::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'date' => $data['date'],
            ],
            $payload
        );
    }

    public function bulkEntry(array $rows): Collection
    {
        return DB::transaction(function () use ($rows) {
            $saved = collect();
            foreach ($rows as $row) {
                if (empty($row['employee_id']) || empty($row['date'])) {
                    continue;
                }
                $saved->push($this->upsertDaily($row));
            }

            return $saved;
        });
    }

    public function monthlySummary(int $companyId, int $year, int $month, ?int $employeeId = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $query = Attendance::query()
            ->forCompany($companyId)
            ->with('employee')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId));

        $rows = $query->orderBy('date')->get();

        $byEmployee = $rows->groupBy('employee_id')->map(function (Collection $group) {
            $employee = $group->first()->employee;

            return [
                'employee' => $employee,
                'employee_id' => $employee?->id,
                'name' => $employee?->full_name ?? '—',
                'present' => $group->whereIn('status', ['present', 'late', 'partial'])->count(),
                'absent' => $group->where('status', 'absent')->count(),
                'leave' => $group->where('status', 'leave')->count(),
                'late' => $group->where('status', 'late')->count(),
                'worked_hours' => round((float) $group->sum('worked_hours'), 2),
                'days' => $group->count(),
            ];
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'period' => $start->translatedFormat('F Y'),
            'totals' => [
                'records' => $rows->count(),
                'present' => $rows->whereIn('status', ['present', 'late', 'partial'])->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'leave' => $rows->where('status', 'leave')->count(),
                'worked_hours' => round((float) $rows->sum('worked_hours'), 2),
            ],
            'by_employee' => $byEmployee,
            'records' => $rows,
        ];
    }

    public function computeWorkedHours(?string $checkIn, ?string $checkOut, int $breakMinutes = 0): ?float
    {
        if (! $checkIn || ! $checkOut) {
            return null;
        }

        try {
            $in = Carbon::parse($checkIn);
            $out = Carbon::parse($checkOut);
        } catch (\Throwable) {
            return null;
        }

        if ($out->lessThanOrEqualTo($in)) {
            $out->addDay();
        }

        $minutes = $in->diffInMinutes($out) - max(0, $breakMinutes);

        return round(max(0, $minutes) / 60, 2);
    }
}
