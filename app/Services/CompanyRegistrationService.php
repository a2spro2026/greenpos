<?php

namespace App\Services;

use App\Events\CompanyRegistrationApproved;
use App\Events\CompanyRegistrationRejected;
use App\Events\CompanyRegistrationSubmitted;
use App\Events\CompanyRegistrationSuspended;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\PlatformNotification;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyRegistrationService
{
    public function __construct(
        private PlatformAdminService $platform,
        private SaasService $saas,
    ) {
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SaasPlan> */
    public function publicPlans()
    {
        $this->saas->ensurePlans();

        return SaasPlan::query()
            ->where('is_active', true)
            ->whereIn('code', ['starter', 'standard', 'enterprise'])
            ->orderBy('sort_order')
            ->get();
    }

    public function findByReference(string $reference): ?CompanyRegistrationRequest
    {
        $ref = strtoupper(trim($reference));

        return CompanyRegistrationRequest::query()
            ->with(['plan', 'company'])
            ->whereRaw('UPPER(reference) = ?', [$ref])
            ->first();
    }

    /**
     * Public self-serve registration — creates a pending request only.
     */
    public function submit(array $data): CompanyRegistrationRequest
    {
        $this->saas->ensurePlans();

        if (User::query()->where('email', $data['owner_email'])->exists()) {
            throw ValidationException::withMessages([
                'owner_email' => 'Un compte existe déjà avec cet e-mail.',
            ]);
        }

        if (CompanyRegistrationRequest::query()
            ->where('owner_email', $data['owner_email'])
            ->where('status', CompanyRegistrationRequest::STATUS_PENDING)
            ->exists()) {
            throw ValidationException::withMessages([
                'owner_email' => 'Une demande en attente existe déjà pour cet e-mail.',
            ]);
        }

        $plan = SaasPlan::query()->findOrFail($data['saas_plan_id']);

        $request = CompanyRegistrationRequest::query()->create([
            'reference' => $this->nextReference(),
            'owner_name' => trim($data['owner_name']),
            'owner_phone' => $data['owner_phone'] ?? null,
            'owner_email' => strtolower(trim($data['owner_email'])),
            'password_hash' => Hash::make($data['password']),
            'company_name' => trim($data['company_name']),
            'activity' => $data['activity'] ?? null,
            'country' => $data['country'] ?? 'Maroc',
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'currency' => strtoupper($data['currency'] ?? 'MAD'),
            'store_name' => trim($data['store_name'] ?? 'Boutique principale'),
            'saas_plan_id' => $plan->id,
            'status' => CompanyRegistrationRequest::STATUS_PENDING,
        ]);

        $this->notifyPlatformAdmins($request);
        $this->saas->logAudit(
            'platform',
            'info',
            'Nouvelle demande d’inscription',
            $request->company_name,
            null,
            [
                'request_id' => $request->id,
                'reference' => $request->reference,
                'email' => $request->owner_email,
                'plan' => $plan->code,
            ]
        );

        CompanyRegistrationSubmitted::dispatch($request);

        return $request;
    }

    public function approve(CompanyRegistrationRequest $request, User $admin): CompanyRegistrationRequest
    {
        if ($request->company_id && $request->status === CompanyRegistrationRequest::STATUS_SUSPENDED) {
            return $this->reactivateSuspended($request, $admin);
        }

        if (! $request->canApprove()) {
            throw ValidationException::withMessages([
                'status' => 'Cette demande ne peut pas être approuvée dans son état actuel.',
            ]);
        }

        if (User::query()->where('email', $request->owner_email)->exists()) {
            throw ValidationException::withMessages([
                'owner_email' => 'Un utilisateur existe déjà avec cet e-mail. Impossible d’approuver.',
            ]);
        }

        $approved = DB::transaction(function () use ($request, $admin) {
            $result = $this->platform->provisionCompany([
                'name' => $request->company_name,
                'activity' => $request->activity,
                'owner_name' => $request->owner_name,
                'email' => $request->owner_email,
                'phone' => $request->owner_phone,
                'address' => $request->address,
                'country' => $request->country,
                'city' => $request->city,
                'currency' => $request->currency,
                'store_name' => $request->store_name,
                'saas_plan_id' => $request->saas_plan_id,
                'password_hash' => $request->password_hash,
            ]);

            $request->forceFill([
                'status' => CompanyRegistrationRequest::STATUS_ACTIVE,
                'company_id' => $result['company']->id,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
                'rejection_reason' => null,
                'suspend_reason' => null,
                'suspended_at' => null,
            ])->save();

            $this->saas->logAudit(
                'tenant',
                'info',
                'Demande d’inscription approuvée',
                $request->company_name,
                $result['tenant'],
                ['request_id' => $request->id, 'admin_id' => $admin->id]
            );

            return $request->fresh(['plan', 'company', 'reviewer']);
        });

        CompanyRegistrationApproved::dispatch($approved);

        return $approved;
    }

    public function reject(CompanyRegistrationRequest $request, User $admin, string $reason): CompanyRegistrationRequest
    {
        if (! $request->canReject()) {
            throw ValidationException::withMessages([
                'status' => 'Seules les demandes en attente peuvent être refusées.',
            ]);
        }

        $request->forceFill([
            'status' => CompanyRegistrationRequest::STATUS_REJECTED,
            'rejection_reason' => trim($reason),
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejected_at' => now(),
        ])->save();

        $this->saas->logAudit(
            'platform',
            'warning',
            'Demande d’inscription refusée',
            $request->company_name,
            null,
            ['request_id' => $request->id, 'reason' => $reason, 'admin_id' => $admin->id]
        );

        $fresh = $request->fresh(['plan', 'reviewer']);
        CompanyRegistrationRejected::dispatch($fresh);

        return $fresh;
    }

    public function suspend(CompanyRegistrationRequest $request, User $admin, ?string $reason = null): CompanyRegistrationRequest
    {
        if (! $request->canSuspend()) {
            throw ValidationException::withMessages([
                'status' => 'Cette demande ne peut pas être suspendue.',
            ]);
        }

        if ($request->company_id) {
            $company = Company::query()->find($request->company_id);
            if ($company) {
                $this->platform->suspendCompany($company, $reason);
            }
        }

        $request->forceFill([
            'status' => CompanyRegistrationRequest::STATUS_SUSPENDED,
            'suspend_reason' => $reason ? trim($reason) : null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'suspended_at' => now(),
        ])->save();

        $this->saas->logAudit(
            'platform',
            'warning',
            'Demande / entreprise suspendue',
            $request->company_name,
            null,
            ['request_id' => $request->id, 'company_id' => $request->company_id, 'admin_id' => $admin->id]
        );

        $fresh = $request->fresh(['plan', 'company', 'reviewer']);
        CompanyRegistrationSuspended::dispatch($fresh);

        return $fresh;
    }

    public function reactivateSuspended(CompanyRegistrationRequest $request, User $admin): CompanyRegistrationRequest
    {
        if ($request->status !== CompanyRegistrationRequest::STATUS_SUSPENDED) {
            throw ValidationException::withMessages([
                'status' => 'Seules les demandes suspendues peuvent être réactivées.',
            ]);
        }

        if ($request->company_id) {
            $company = Company::query()->find($request->company_id);
            if ($company) {
                $this->platform->reactivateCompany($company);
            }

            $request->forceFill([
                'status' => CompanyRegistrationRequest::STATUS_ACTIVE,
                'suspend_reason' => null,
                'suspended_at' => null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            $fresh = $request->fresh(['plan', 'company', 'reviewer']);
            CompanyRegistrationApproved::dispatch($fresh);

            return $fresh;
        }

        $request->forceFill([
            'status' => CompanyRegistrationRequest::STATUS_PENDING,
            'suspend_reason' => null,
            'suspended_at' => null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        return $request->fresh(['plan', 'company', 'reviewer']);
    }

    /** @return array<string, int|float|null> */
    public function statusCounts(): array
    {
        if (! Schema::hasTable('company_registration_requests')) {
            return [
                'pending' => 0,
                'active' => 0,
                'suspended' => 0,
                'rejected' => 0,
                'trials' => 0,
            ];
        }

        $base = CompanyRegistrationRequest::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'pending' => (int) ($base[CompanyRegistrationRequest::STATUS_PENDING] ?? 0),
            'active' => (int) ($base[CompanyRegistrationRequest::STATUS_ACTIVE] ?? 0),
            'suspended' => (int) ($base[CompanyRegistrationRequest::STATUS_SUSPENDED] ?? 0),
            'rejected' => (int) ($base[CompanyRegistrationRequest::STATUS_REJECTED] ?? 0),
            'trials' => (int) \App\Models\SaasTenant::query()->where('status', 'trial')->whereNull('archived_at')->count(),
        ];
    }

    /**
     * @return array{
     *   today:int,
     *   week:int,
     *   acceptance_rate:float|null,
     *   avg_validation_hours:float|null,
     *   avg_validation_label:string
     * }
     */
    public function acquisitionStats(): array
    {
        if (! Schema::hasTable('company_registration_requests')) {
            return [
                'today' => 0,
                'week' => 0,
                'acceptance_rate' => null,
                'avg_validation_hours' => null,
                'avg_validation_label' => '—',
            ];
        }

        $today = CompanyRegistrationRequest::query()
            ->whereDate('created_at', today())
            ->count();

        $week = CompanyRegistrationRequest::query()
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $approved = CompanyRegistrationRequest::query()
            ->where('status', CompanyRegistrationRequest::STATUS_ACTIVE)
            ->whereNotNull('approved_at')
            ->count();

        $rejected = CompanyRegistrationRequest::query()
            ->where('status', CompanyRegistrationRequest::STATUS_REJECTED)
            ->count();

        $decided = $approved + $rejected;
        $acceptanceRate = $decided > 0 ? round(($approved / $decided) * 100, 1) : null;

        $avgHours = null;
        $rows = CompanyRegistrationRequest::query()
            ->where('status', CompanyRegistrationRequest::STATUS_ACTIVE)
            ->whereNotNull('approved_at')
            ->get(['created_at', 'approved_at']);

        if ($rows->isNotEmpty()) {
            $avgHours = round((float) $rows->avg(
                fn ($r) => $r->created_at->diffInMinutes($r->approved_at) / 60
            ), 1);
        }

        $label = '—';
        if ($avgHours !== null) {
            if ($avgHours < 1) {
                $label = max(1, (int) round($avgHours * 60)).' min';
            } elseif ($avgHours < 48) {
                $label = $avgHours.' h';
            } else {
                $label = round($avgHours / 24, 1).' j';
            }
        }

        return [
            'today' => $today,
            'week' => $week,
            'acceptance_rate' => $acceptanceRate,
            'avg_validation_hours' => $avgHours,
            'avg_validation_label' => $label,
        ];
    }

    /** @return array{tone:string,title:string,body:string} */
    public function statusMessage(CompanyRegistrationRequest $request): array
    {
        return match ($request->status) {
            CompanyRegistrationRequest::STATUS_ACTIVE => [
                'tone' => 'success',
                'title' => 'Votre entreprise a été activée.',
                'body' => 'Vous pouvez maintenant vous connecter.',
            ],
            CompanyRegistrationRequest::STATUS_REJECTED => [
                'tone' => 'danger',
                'title' => 'Votre demande n’a pas été acceptée.',
                'body' => 'Vous pouvez contacter notre équipe.',
            ],
            CompanyRegistrationRequest::STATUS_SUSPENDED => [
                'tone' => 'warn',
                'title' => 'Votre demande est suspendue.',
                'body' => 'Veuillez contacter GreenPOS.',
            ],
            default => [
                'tone' => 'info',
                'title' => 'Votre demande est en cours d’étude.',
                'body' => 'Vous recevrez un email dès qu’elle sera validée.',
            ],
        };
    }

    private function nextReference(): string
    {
        do {
            $ref = 'REQ-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (CompanyRegistrationRequest::query()->where('reference', $ref)->exists());

        return $ref;
    }

    private function notifyPlatformAdmins(CompanyRegistrationRequest $request): void
    {
        if (! Schema::hasTable('platform_notifications')) {
            return;
        }

        $admins = User::query()->where('is_platform_admin', true)->where('status', 'active')->get();
        $title = 'Nouvelle demande d’inscription';
        $body = $request->company_name.' — '.$request->owner_name.' ('.$request->owner_email.')';
        $url = route('admin.registrations.show', $request);

        if ($admins->isEmpty()) {
            PlatformNotification::query()->create([
                'user_id' => null,
                'type' => 'registration',
                'title' => $title,
                'body' => $body,
                'action_url' => $url,
                'data' => ['request_id' => $request->id, 'reference' => $request->reference],
            ]);

            return;
        }

        foreach ($admins as $admin) {
            PlatformNotification::query()->create([
                'user_id' => $admin->id,
                'type' => 'registration',
                'title' => $title,
                'body' => $body,
                'action_url' => $url,
                'data' => ['request_id' => $request->id, 'reference' => $request->reference],
            ]);
        }
    }
}
