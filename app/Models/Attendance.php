<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUSES = [
        'present' => 'Présent',
        'absent' => 'Absent',
        'leave' => 'Congé',
        'late' => 'Retard',
        'partial' => 'Partiel',
    ];

    public const STATUS_COLORS = [
        'present' => 'bg-emerald-100 text-emerald-800',
        'absent' => 'bg-rose-100 text-rose-800',
        'leave' => 'bg-sky-100 text-sky-800',
        'late' => 'bg-amber-100 text-amber-800',
        'partial' => 'bg-violet-100 text-violet-800',
    ];

    protected $fillable = [
        'company_id',
        'store_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'break_minutes',
        'worked_hours',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'worked_hours' => 'decimal:2',
            'break_minutes' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('notes', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->orWhereHas('employee', function (Builder $eq) use ($like) {
                    $eq->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                });
        });
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-slate-100 text-slate-700';
    }
}
