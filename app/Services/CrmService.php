<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmEmailLog;
use App\Models\CrmEmailTemplate;
use App\Models\CrmGoal;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\Customer;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmService
{
    public function companyId(): int
    {
        $id = Workspace::company()?->id;
        abort_unless($id, 403, 'Workspace requis.');

        return (int) $id;
    }

    public function dashboardStats(): array
    {
        $cid = $this->companyId();
        $leads = CrmLead::query()->forCompany($cid)->whereNull('archived_at');
        $opps = CrmOpportunity::query()->forCompany($cid)->whereNull('deleted_at');

        $prospects = (clone $leads)->where('type', 'prospect')->whereNotIn('status', ['converted', 'archived'])->count();
        $activeLeads = (clone $leads)->where('type', 'lead')->whereNotIn('status', ['converted', 'archived', 'unqualified'])->count();
        $openOpps = (clone $opps)->whereNotIn('stage', ['won', 'lost'])->get();
        $won = (clone $opps)->where('stage', 'won')->where('closed_on', '>=', now()->startOfMonth())->count();
        $converted = CrmLead::query()->forCompany($cid)->whereNotNull('converted_at')->where('converted_at', '>=', now()->subMonths(3))->count();
        $qualifiedPool = CrmLead::query()->forCompany($cid)->whereIn('status', ['qualified', 'converted'])->where('created_at', '>=', now()->subMonths(3))->count();
        $conversion = $qualifiedPool > 0 ? round(($converted / max($qualifiedPool, 1)) * 100, 1) : 0.0;

        $pipelineValue = $openOpps->sum(fn (CrmOpportunity $o) => (float) $o->amount);
        $weighted = $openOpps->sum(fn (CrmOpportunity $o) => $o->weightedAmount());

        $todayActivities = CrmActivity::query()->forCompany($cid)
            ->where(function ($q) {
                $q->whereDate('starts_at', today())
                    ->orWhereDate('due_at', today());
            })
            ->where('status', '!=', 'cancelled')
            ->with(['lead', 'opportunity', 'owner'])
            ->orderBy('starts_at')
            ->limit(12)
            ->get();

        $goal = CrmGoal::query()->where('company_id', $cid)
            ->where('period', 'month')
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->whereNull('owner_user_id')
            ->first();

        if (! $goal) {
            $goal = CrmGoal::query()->create([
                'company_id' => $cid,
                'period' => 'month',
                'year' => now()->year,
                'month' => now()->month,
                'target_amount' => 100000,
                'target_deals' => 10,
                'achieved_amount' => (clone $opps)->where('stage', 'won')->where('closed_on', '>=', now()->startOfMonth())->sum('amount'),
                'achieved_deals' => $won,
            ]);
        } else {
            $goal->update([
                'achieved_amount' => (clone $opps)->where('stage', 'won')->where('closed_on', '>=', now()->startOfMonth())->sum('amount'),
                'achieved_deals' => $won,
            ]);
            $goal->refresh();
        }

        $byStage = collect(CrmOpportunity::STAGES)->map(function ($label, $stage) use ($cid) {
            $items = CrmOpportunity::query()->forCompany($cid)->where('stage', $stage)->get();

            return [
                'stage' => $stage,
                'label' => $label,
                'count' => $items->count(),
                'amount' => round($items->sum('amount'), 2),
            ];
        })->values();

        $byMonth = collect(range(5, 0))->map(function (int $i) use ($cid) {
            $month = now()->subMonths($i)->startOfMonth();
            $end = (clone $month)->endOfMonth();

            return [
                'label' => $month->translatedFormat('M'),
                'leads' => CrmLead::query()->forCompany($cid)->whereBetween('created_at', [$month, $end])->count(),
                'won' => CrmOpportunity::query()->forCompany($cid)->where('stage', 'won')->whereBetween('closed_on', [$month, $end])->sum('amount'),
            ];
        });

        return [
            'prospects' => $prospects,
            'active_leads' => $activeLeads,
            'opportunities' => $openOpps->count(),
            'conversion_rate' => $conversion,
            'pipeline_value' => round($pipelineValue, 2),
            'weighted_value' => round($weighted, 2),
            'today_activities' => $todayActivities,
            'goal' => $goal,
            'by_stage' => $byStage,
            'by_month' => $byMonth,
            'recent_leads' => CrmLead::query()->forCompany($cid)->whereNull('archived_at')->latest()->limit(8)->with('owner')->get(),
            'recent_opps' => CrmOpportunity::query()->forCompany($cid)->latest()->limit(8)->with(['owner', 'lead'])->get(),
        ];
    }

    public function pipelineBoard(): array
    {
        $cid = $this->companyId();
        $columns = [];
        foreach (CrmOpportunity::STAGES as $stage => $label) {
            $items = CrmOpportunity::query()
                ->forCompany($cid)
                ->where('stage', $stage)
                ->with(['owner', 'lead', 'customer'])
                ->orderBy('pipeline_order')
                ->orderByDesc('amount')
                ->get();

            $columns[] = [
                'stage' => $stage,
                'label' => $label,
                'color' => CrmOpportunity::STAGE_COLORS[$stage],
                'count' => $items->count(),
                'amount' => round($items->sum('amount'), 2),
                'items' => $items,
            ];
        }

        return $columns;
    }

    public function moveOpportunity(CrmOpportunity $opp, string $stage, ?int $order = null): CrmOpportunity
    {
        abort_unless($opp->company_id === $this->companyId(), 403);
        abort_unless(array_key_exists($stage, CrmOpportunity::STAGES), 422);

        $data = [
            'stage' => $stage,
            'probability' => CrmOpportunity::STAGE_PROBABILITY[$stage] ?? $opp->probability,
            'pipeline_order' => $order ?? $opp->pipeline_order,
        ];

        if ($stage === 'won') {
            $data['closed_on'] = $opp->closed_on ?: now()->toDateString();
            $data['lost_reason'] = null;
        } elseif ($stage === 'lost') {
            $data['closed_on'] = $opp->closed_on ?: now()->toDateString();
        } else {
            $data['closed_on'] = null;
            $data['lost_reason'] = null;
        }

        $opp->update($data);

        return $opp->fresh(['owner', 'lead', 'customer']);
    }

    public function createLead(array $data): CrmLead
    {
        $cid = $this->companyId();

        return CrmLead::query()->create([
            'company_id' => $cid,
            'store_id' => Workspace::store()?->id,
            'owner_user_id' => $data['owner_user_id'] ?? auth()->id(),
            'type' => $data['type'] ?? 'lead',
            'status' => $data['status'] ?? 'new',
            'source' => $data['source'] ?? 'other',
            'rating' => $data['rating'] ?? 'warm',
            'score' => $data['score'] ?? 20,
            'company_name' => $data['company_name'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? 'MA',
            'website' => $data['website'] ?? null,
            'estimated_value' => $data['estimated_value'] ?? 0,
            'currency' => $data['currency'] ?? 'MAD',
            'description' => $data['description'] ?? null,
            'tags' => $data['tags'] ?? null,
        ]);
    }

    public function updateLead(CrmLead $lead, array $data): CrmLead
    {
        abort_unless($lead->company_id === $this->companyId(), 403);
        $lead->update(collect($data)->only([
            'owner_user_id', 'type', 'status', 'source', 'rating', 'score',
            'company_name', 'first_name', 'last_name', 'email', 'phone', 'mobile',
            'job_title', 'city', 'country', 'website', 'estimated_value', 'currency', 'description',
        ])->all());

        return $lead->fresh();
    }

    public function qualifyLead(CrmLead $lead): CrmLead
    {
        abort_unless($lead->company_id === $this->companyId(), 403);
        $lead->update([
            'status' => 'qualified',
            'type' => 'lead',
            'qualified_at' => now(),
            'score' => max((int) $lead->score, 60),
            'rating' => $lead->rating === 'cold' ? 'warm' : $lead->rating,
        ]);

        return $lead->fresh();
    }

    public function assignLead(CrmLead $lead, int $userId): CrmLead
    {
        abort_unless($lead->company_id === $this->companyId(), 403);
        $lead->update(['owner_user_id' => $userId]);

        return $lead->fresh('owner');
    }

    public function archiveLead(CrmLead $lead): CrmLead
    {
        abort_unless($lead->company_id === $this->companyId(), 403);
        $lead->update(['status' => 'archived', 'archived_at' => now()]);

        return $lead->fresh();
    }

    public function convertLead(CrmLead $lead, bool $createOpportunity = true): array
    {
        abort_unless($lead->company_id === $this->companyId(), 403);

        return DB::transaction(function () use ($lead, $createOpportunity) {
            $name = $lead->company_name ?: $lead->displayName();
            $customer = $lead->customer_id
                ? Customer::query()->find($lead->customer_id)
                : Customer::query()->create([
                    'company_id' => $lead->company_id,
                    'store_id' => $lead->store_id,
                    'created_by' => auth()->id(),
                    'type' => $lead->company_name ? 'company' : 'individual',
                    'name' => $name,
                    'code' => 'CRM-'.strtoupper(Str::random(6)),
                    'email' => $lead->email,
                    'phone' => $lead->phone ?: $lead->mobile,
                    'city' => $lead->city,
                    'country' => $lead->country ?: 'MA',
                    'status' => 'active',
                    'category' => 'standard',
                ]);

            $lead->update([
                'customer_id' => $customer->id,
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            $opp = null;
            if ($createOpportunity) {
                $opp = $this->createOpportunity([
                    'crm_lead_id' => $lead->id,
                    'customer_id' => $customer->id,
                    'owner_user_id' => $lead->owner_user_id,
                    'name' => 'Opportunité — '.$name,
                    'stage' => 'qualified',
                    'amount' => $lead->estimated_value,
                    'description' => $lead->description,
                ]);
            }

            return ['lead' => $lead->fresh(), 'customer' => $customer, 'opportunity' => $opp];
        });
    }

    public function createOpportunity(array $data): CrmOpportunity
    {
        $cid = $this->companyId();
        $stage = $data['stage'] ?? 'new';

        return CrmOpportunity::query()->create([
            'company_id' => $cid,
            'store_id' => Workspace::store()?->id,
            'crm_lead_id' => $data['crm_lead_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'owner_user_id' => $data['owner_user_id'] ?? auth()->id(),
            'name' => $data['name'],
            'stage' => $stage,
            'probability' => $data['probability'] ?? (CrmOpportunity::STAGE_PROBABILITY[$stage] ?? 10),
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'MAD',
            'expected_close_on' => $data['expected_close_on'] ?? now()->addDays(30)->toDateString(),
            'description' => $data['description'] ?? null,
            'pipeline_order' => $data['pipeline_order'] ?? 0,
        ]);
    }

    public function updateOpportunity(CrmOpportunity $opp, array $data): CrmOpportunity
    {
        abort_unless($opp->company_id === $this->companyId(), 403);
        $opp->update(collect($data)->only([
            'name', 'owner_user_id', 'customer_id', 'crm_lead_id', 'stage', 'probability',
            'amount', 'currency', 'expected_close_on', 'description', 'lost_reason',
            'quote_id', 'invoice_id',
        ])->all());

        if (isset($data['stage'])) {
            $this->moveOpportunity($opp->fresh(), $data['stage']);
        }

        return $opp->fresh();
    }

    public function createActivity(array $data): CrmActivity
    {
        return CrmActivity::query()->create([
            'company_id' => $this->companyId(),
            'owner_user_id' => $data['owner_user_id'] ?? auth()->id(),
            'crm_lead_id' => $data['crm_lead_id'] ?? null,
            'crm_opportunity_id' => $data['crm_opportunity_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'] ?? 'planned',
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'all_day' => $data['all_day'] ?? false,
            'priority' => $data['priority'] ?? 'normal',
        ]);
    }

    public function completeActivity(CrmActivity $activity): CrmActivity
    {
        abort_unless($activity->company_id === $this->companyId(), 403);
        $activity->update(['status' => 'done', 'completed_at' => now()]);

        if ($activity->crm_lead_id) {
            CrmLead::query()->where('id', $activity->crm_lead_id)->update(['last_contacted_at' => now()]);
        }

        return $activity->fresh();
    }

    public function calendarEvents(?string $from = null, ?string $to = null): array
    {
        $cid = $this->companyId();
        $from = $from ? now()->parse($from)->startOfDay() : now()->startOfMonth();
        $to = $to ? now()->parse($to)->endOfDay() : now()->endOfMonth();

        return CrmActivity::query()->forCompany($cid)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('starts_at', [$from, $to])
                    ->orWhereBetween('due_at', [$from, $to]);
            })
            ->with(['lead', 'opportunity', 'owner'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CrmActivity $a) => [
                'id' => $a->id,
                'title' => $a->subject,
                'type' => $a->type,
                'type_label' => $a->typeLabel(),
                'status' => $a->status,
                'start' => optional($a->starts_at ?: $a->due_at)?->toIso8601String(),
                'end' => optional($a->ends_at)?->toIso8601String(),
                'lead' => $a->lead?->displayName(),
                'opportunity' => $a->opportunity?->name,
                'url' => route('crm.activities.show', $a),
            ])
            ->all();
    }

    public function ensureEmailTemplates(): void
    {
        $cid = $this->companyId();
        if (CrmEmailTemplate::query()->where('company_id', $cid)->exists()) {
            return;
        }

        $defs = [
            ['code' => 'intro', 'name' => 'Prise de contact', 'category' => 'intro', 'subject' => 'Ravi d’échanger avec {{name}}', 'body' => "Bonjour {{name}},\n\nMerci pour votre intérêt pour GreenPOS. Seriez-vous disponible pour un échange de 15 minutes ?\n\nCordialement"],
            ['code' => 'follow_up', 'name' => 'Relance douce', 'category' => 'follow_up', 'subject' => 'Suite à notre échange — {{name}}', 'body' => "Bonjour {{name}},\n\nJe me permets de revenir vers vous concernant notre proposition. Souhaitez-vous que je précise un point ?\n\nBien à vous"],
            ['code' => 'proposal', 'name' => 'Envoi proposition', 'category' => 'proposal', 'subject' => 'Proposition commerciale GreenPOS', 'body' => "Bonjour {{name}},\n\nVeuillez trouver ci-joint notre proposition. Je reste disponible pour en discuter.\n\nCordialement"],
            ['code' => 'closing', 'name' => 'Closing', 'category' => 'closing', 'subject' => 'Finalisation de votre projet', 'body' => "Bonjour {{name}},\n\nNous sommes prêts à démarrer. Confirmez-vous votre go pour cette semaine ?\n\nCordialement"],
        ];

        foreach ($defs as $d) {
            CrmEmailTemplate::query()->create(array_merge($d, [
                'company_id' => $cid,
                'created_by' => auth()->id(),
                'is_active' => true,
            ]));
        }
    }

    public function logEmail(array $data): CrmEmailLog
    {
        $this->ensureEmailTemplates();

        return CrmEmailLog::query()->create([
            'company_id' => $this->companyId(),
            'crm_email_template_id' => $data['crm_email_template_id'] ?? null,
            'crm_lead_id' => $data['crm_lead_id'] ?? null,
            'crm_opportunity_id' => $data['crm_opportunity_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'user_id' => auth()->id(),
            'to_email' => $data['to_email'],
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
            'status' => $data['status'] ?? 'sent',
            'sent_at' => ($data['status'] ?? 'sent') === 'sent' ? now() : null,
            'tracking_token' => Str::random(40),
        ]);
    }

    public function markEmailOpened(string $token): ?CrmEmailLog
    {
        $log = CrmEmailLog::query()->where('tracking_token', $token)->first();
        if (! $log) {
            return null;
        }

        $log->update([
            'status' => 'opened',
            'opened_at' => $log->opened_at ?: now(),
            'open_count' => $log->open_count + 1,
        ]);

        return $log;
    }

    public function reportStats(): array
    {
        $cid = $this->companyId();
        $owners = User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $cid))
            ->get(['id', 'name', 'email']);

        $byOwner = $owners->map(function (User $u) use ($cid) {
            $won = CrmOpportunity::query()->forCompany($cid)->where('owner_user_id', $u->id)->where('stage', 'won');
            $open = CrmOpportunity::query()->forCompany($cid)->where('owner_user_id', $u->id)->whereNotIn('stage', ['won', 'lost']);

            return [
                'user' => $u->name ?: $u->email,
                'won_amount' => (float) (clone $won)->sum('amount'),
                'won_count' => (clone $won)->count(),
                'open_count' => (clone $open)->count(),
                'open_amount' => (float) (clone $open)->sum('amount'),
            ];
        })->sortByDesc('won_amount')->values();

        $conversionFunnel = [
            'leads' => CrmLead::query()->forCompany($cid)->count(),
            'qualified' => CrmLead::query()->forCompany($cid)->where('status', 'qualified')->count(),
            'opportunities' => CrmOpportunity::query()->forCompany($cid)->count(),
            'won' => CrmOpportunity::query()->forCompany($cid)->where('stage', 'won')->count(),
            'lost' => CrmOpportunity::query()->forCompany($cid)->where('stage', 'lost')->count(),
        ];

        return [
            'by_owner' => $byOwner,
            'funnel' => $conversionFunnel,
            'pipeline' => $this->pipelineBoard(),
            'revenue_won' => (float) CrmOpportunity::query()->forCompany($cid)->where('stage', 'won')->sum('amount'),
            'revenue_month' => (float) CrmOpportunity::query()->forCompany($cid)->where('stage', 'won')->where('closed_on', '>=', now()->startOfMonth())->sum('amount'),
        ];
    }

    public function seedDemoIfEmpty(): void
    {
        $cid = $this->companyId();
        if (CrmLead::query()->forCompany($cid)->exists()) {
            $this->ensureEmailTemplates();

            return;
        }

        $this->ensureEmailTemplates();
        $owner = auth()->id();

        $samples = [
            ['type' => 'prospect', 'company_name' => 'Atlas Retail', 'first_name' => 'Sara', 'last_name' => 'Benali', 'email' => 'sara@atlas-retail.test', 'rating' => 'hot', 'estimated_value' => 45000, 'source' => 'website'],
            ['type' => 'lead', 'company_name' => 'Café Medina', 'first_name' => 'Youssef', 'last_name' => 'Amrani', 'email' => 'youssef@cafemedina.test', 'rating' => 'warm', 'estimated_value' => 12000, 'source' => 'referral', 'status' => 'contacted'],
            ['type' => 'lead', 'company_name' => 'Pharma Plus', 'first_name' => 'Imane', 'last_name' => 'Tazi', 'email' => 'imane@pharmaplus.test', 'rating' => 'hot', 'estimated_value' => 80000, 'source' => 'event', 'status' => 'qualified'],
            ['type' => 'prospect', 'company_name' => 'Mode Casa', 'first_name' => 'Karim', 'last_name' => 'Alaoui', 'email' => 'karim@modecasa.test', 'rating' => 'cold', 'estimated_value' => 25000, 'source' => 'cold_call'],
        ];

        $stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation'];
        foreach ($samples as $i => $s) {
            $lead = $this->createLead(array_merge($s, ['owner_user_id' => $owner]));
            $opp = $this->createOpportunity([
                'crm_lead_id' => $lead->id,
                'owner_user_id' => $owner,
                'name' => 'Deal '.$lead->displayName(),
                'stage' => $stages[$i % count($stages)],
                'amount' => $s['estimated_value'],
            ]);
            $this->createActivity([
                'crm_lead_id' => $lead->id,
                'crm_opportunity_id' => $opp->id,
                'type' => $i % 2 === 0 ? 'call' : 'meeting',
                'subject' => 'Suivi '.$lead->displayName(),
                'starts_at' => now()->addDays($i)->setTime(10 + $i, 0),
                'ends_at' => now()->addDays($i)->setTime(11 + $i, 0),
                'status' => 'planned',
            ]);
        }
    }

    /** AI helpers */
    public function summarizeLead(CrmLead $lead): string
    {
        $opps = $lead->opportunities()->count();
        $acts = $lead->activities()->count();

        return "Prospect/Lead **{$lead->displayName()}** ({$lead->number})\n"
            ."- Statut : {$lead->statusLabel()} · Source : {$lead->sourceLabel()} · Score : {$lead->score}/100 · {$lead->ratingLabel()}\n"
            ."- Contact : {$lead->email} · {$lead->phone}\n"
            ."- Valeur estimée : ".number_format((float) $lead->estimated_value, 0, ',', ' ')." {$lead->currency}\n"
            ."- Opportunités liées : {$opps} · Activités : {$acts}\n"
            .($lead->description ? "- Notes : {$lead->description}\n" : '');
    }

    public function estimateWinProbability(CrmOpportunity $opp): array
    {
        $base = (int) ($opp->probability ?: CrmOpportunity::STAGE_PROBABILITY[$opp->stage] ?? 10);
        $boost = 0;
        if ($opp->customer_id) {
            $boost += 5;
        }
        if ($opp->quote_id) {
            $boost += 10;
        }
        if ($opp->activities()->where('type', 'meeting')->where('status', 'done')->exists()) {
            $boost += 8;
        }
        if ($opp->expected_close_on && $opp->expected_close_on->isPast() && $opp->isOpen()) {
            $boost -= 15;
        }
        $score = max(0, min(99, $base + $boost));

        return [
            'probability' => $score,
            'factors' => [
                'stage' => $opp->stageLabel(),
                'base' => $base,
                'boost' => $boost,
            ],
            'advice' => $score >= 70
                ? 'Forte probabilité — priorisez le closing et une proposition claire.'
                : ($score >= 40
                    ? 'En bonne voie — planifiez une réunion de qualification approfondie.'
                    : 'Risque élevé — relancez rapidement ou requalifiez le besoin.'),
        ];
    }
}
