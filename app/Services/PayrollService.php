<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Support\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function previewCalculate(Employee $employee, int $year, int $month, array $overrides = []): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth();

        $attendances = Attendance::query()
            ->forCompany($employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $workedStatuses = ['present', 'late', 'partial'];
        $workedDays = $attendances->whereIn('status', $workedStatuses)->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $workedHours = (float) $attendances->whereIn('status', $workedStatuses)->sum('worked_hours');

        $payType = $overrides['pay_type'] ?? $employee->pay_type ?? 'monthly';
        $baseRate = (float) ($overrides['base_rate'] ?? $employee->base_rate ?? 0);
        $normalPerDay = (float) ($employee->normal_hours_per_day ?: 8);
        $multiplier = (float) ($employee->overtime_multiplier ?: 1.5);
        $absencePenalty = (float) ($employee->absence_penalty ?: 0);

        $normalHours = round($normalPerDay * max($workedDays, 0), 2);
        $overtimeHours = round(max(0, $workedHours - $normalHours), 2);

        $gross = $this->computeGross($payType, $baseRate, $workedHours, $workedDays, $normalHours, $start);
        $hourlyRate = $this->hourlyEquivalent($payType, $baseRate, $normalPerDay, $start);
        $overtimeAmount = round($overtimeHours * $hourlyRate * $multiplier, 2);
        $absenceDeduction = round($absentDays * $absencePenalty, 2);

        $prime = (float) ($overrides['prime'] ?? 0);
        $bonus = (float) ($overrides['bonus'] ?? 0);
        $advance = (float) ($overrides['advance'] ?? 0);
        $retention = (float) ($overrides['retention'] ?? 0);

        $net = round($gross + $overtimeAmount + $prime + $bonus - $absenceDeduction - $advance - $retention, 2);

        return [
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'store_id' => $employee->store_id,
            'year' => $year,
            'month' => $month,
            'pay_type' => $payType,
            'base_rate' => $baseRate,
            'normal_hours' => $normalHours,
            'overtime_hours' => $overtimeHours,
            'worked_days' => $workedDays,
            'absent_days' => $absentDays,
            'worked_hours' => $workedHours,
            'gross' => $gross,
            'overtime_amount' => $overtimeAmount,
            'absence_deduction' => $absenceDeduction,
            'prime' => $prime,
            'bonus' => $bonus,
            'advance' => $advance,
            'retention' => $retention,
            'net' => $net,
            'hourly_rate' => $hourlyRate,
            'overtime_multiplier' => $multiplier,
        ];
    }

    public function calculateAndSave(Employee $employee, int $year, int $month, array $overrides = []): Payroll
    {
        $calc = $this->previewCalculate($employee, $year, $month, $overrides);

        return Payroll::query()->updateOrCreate(
            [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'store_id' => $calc['store_id'],
                'pay_type' => $calc['pay_type'],
                'base_rate' => $calc['base_rate'],
                'normal_hours' => $calc['normal_hours'],
                'overtime_hours' => $calc['overtime_hours'],
                'worked_days' => $calc['worked_days'],
                'absent_days' => $calc['absent_days'],
                'gross' => $calc['gross'],
                'overtime_amount' => $calc['overtime_amount'],
                'absence_deduction' => $calc['absence_deduction'],
                'prime' => $calc['prime'],
                'bonus' => $calc['bonus'],
                'advance' => $calc['advance'],
                'retention' => $calc['retention'],
                'net' => $calc['net'],
                'status' => $overrides['status'] ?? 'pending',
                'notes' => $overrides['notes'] ?? null,
                'calculated_at' => now(),
                'created_by' => Workspace::user()?->id,
            ]
        );
    }

    public function bulkCalculate(int $companyId, int $year, int $month, ?array $employeeIds = null): Collection
    {
        return DB::transaction(function () use ($companyId, $year, $month, $employeeIds) {
            $employees = Employee::query()
                ->forCompany($companyId)
                ->where('status', 'active')
                ->when($employeeIds, fn ($q) => $q->whereIn('id', $employeeIds))
                ->get();

            $saved = collect();
            foreach ($employees as $employee) {
                $saved->push($this->calculateAndSave($employee, $year, $month));
            }

            return $saved;
        });
    }

    public function markPaid(Payroll $payroll, array $data = []): Payroll
    {
        $amount = isset($data['paid_amount'])
            ? (float) $data['paid_amount']
            : (float) $payroll->net;

        $status = 'paid';
        if ($amount <= 0) {
            $status = 'pending';
        } elseif ($amount + 0.001 < (float) $payroll->net) {
            $status = 'partial';
        }

        $payroll->update([
            'paid_amount' => $amount,
            'paid_at' => $data['paid_at'] ?? now(),
            'payment_method' => $data['payment_method'] ?? $payroll->payment_method,
            'status' => $status,
            'notes' => $data['notes'] ?? $payroll->notes,
        ]);

        return $payroll->fresh(['employee']);
    }

    protected function computeGross(string $payType, float $baseRate, float $workedHours, int $workedDays, float $normalHours, Carbon $monthStart): float
    {
        return match ($payType) {
            'hourly' => round($baseRate * ($normalHours > 0 ? min($workedHours, $normalHours) : $workedHours), 2),
            'daily' => round($baseRate * $workedDays, 2),
            'weekly' => round($baseRate * ($workedDays / 7), 2),
            default => round($baseRate, 2), // monthly
        };
    }

    protected function hourlyEquivalent(string $payType, float $baseRate, float $normalPerDay, Carbon $monthStart): float
    {
        $normalPerDay = max($normalPerDay, 0.01);

        return match ($payType) {
            'hourly' => $baseRate,
            'daily' => $baseRate / $normalPerDay,
            'weekly' => $baseRate / ($normalPerDay * 7),
            default => $baseRate / ($normalPerDay * max($monthStart->daysInMonth, 1)),
        };
    }
}
