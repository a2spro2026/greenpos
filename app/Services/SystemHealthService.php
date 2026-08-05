<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SystemAlert;
use App\Models\SystemHealthEvent;
use App\Models\SystemHealthSnapshot;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class SystemHealthService
{
    public function check(?Company $company = null, bool $persist = true): array
    {
        $company = $company ?? Workspace::company();

        $started = hrtime(true);
        $dbStatus = 'ok';
        $dbError = null;
        try {
            DB::select('select 1 as ok');
        } catch (Throwable $e) {
            $dbStatus = 'down';
            $dbError = $e->getMessage();
        }
        $responseMs = (int) ((hrtime(true) - $started) / 1_000_000);

        $disk = $this->diskStats();
        $services = $this->servicesStatus();

        $overall = 'healthy';
        if ($dbStatus !== 'ok' || ($services['app']['status'] ?? '') === 'down') {
            $overall = 'critical';
        } elseif ($disk['used_percent'] >= 90 || collect($services)->contains(fn ($s) => ($s['status'] ?? '') === 'degraded')) {
            $overall = 'degraded';
        }

        $payload = [
            'overall' => $overall,
            'database_status' => $dbStatus,
            'database_error' => $dbError,
            'response_ms' => $responseMs,
            'disk' => $disk,
            'services' => $services,
            'checked_at' => now()->toIso8601String(),
        ];

        if ($persist) {
            SystemHealthSnapshot::query()->create([
                'company_id' => $company?->id,
                'overall' => $overall,
                'database_status' => $dbStatus,
                'response_ms' => $responseMs,
                'disk_used_percent' => $disk['used_percent'],
                'disk_free_bytes' => $disk['free_bytes'],
                'services' => $services,
                'meta' => ['database_error' => $dbError],
            ]);

            $this->syncAlerts($company?->id, $payload);
        }

        return $payload;
    }

    public function dashboard(?Company $company = null): array
    {
        $company = $company ?? Workspace::company();
        $health = $this->check($company, true);

        $alerts = SystemAlert::query()
            ->when($company, fn ($q) => $q->where(function ($q) use ($company) {
                $q->where('company_id', $company->id)->orWhereNull('company_id');
            }))
            ->open()
            ->latest()
            ->limit(20)
            ->get();

        $incidents = SystemHealthEvent::query()
            ->when($company, fn ($q) => $q->where(function ($q) use ($company) {
                $q->where('company_id', $company->id)->orWhereNull('company_id');
            }))
            ->whereIn('category', ['incident', 'error'])
            ->latest()
            ->limit(15)
            ->get();

        $snapshots = SystemHealthSnapshot::query()
            ->when($company, fn ($q) => $q->where(function ($q) use ($company) {
                $q->where('company_id', $company->id)->orWhereNull('company_id');
            }))
            ->latest()
            ->limit(12)
            ->get();

        return compact('health', 'alerts', 'incidents', 'snapshots');
    }

    public function journal(?Company $company = null, ?string $category = null, int $limit = 50)
    {
        $company = $company ?? Workspace::company();

        return SystemHealthEvent::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function resolveAlert(SystemAlert $alert): void
    {
        $company = Workspace::company();
        if ($company && $alert->company_id && (int) $alert->company_id !== (int) $company->id) {
            abort(404);
        }

        $alert->update(['is_resolved' => true, 'resolved_at' => now()]);
        SystemHealthEvent::query()->create([
            'company_id' => $alert->company_id,
            'user_id' => auth()->id(),
            'category' => 'health',
            'severity' => 'info',
            'title' => 'Alerte résolue',
            'body' => $alert->title,
            'payload' => ['alert_id' => $alert->id, 'type' => $alert->type],
        ]);
    }

    /** @return array{total_bytes:int,free_bytes:int,used_percent:float,path:string} */
    private function diskStats(): array
    {
        $path = storage_path('app');
        $total = @disk_total_space($path) ?: 0;
        $free = @disk_free_space($path) ?: 0;
        $usedPercent = $total > 0 ? round((($total - $free) / $total) * 100, 2) : 0.0;

        return [
            'total_bytes' => (int) $total,
            'free_bytes' => (int) $free,
            'used_percent' => $usedPercent,
            'path' => $path,
        ];
    }

    /** @return array<string, array{status:string,label:string,detail:?string}> */
    private function servicesStatus(): array
    {
        $services = [
            'database' => [
                'label' => 'Base de données',
                'status' => 'ok',
                'detail' => config('database.default'),
            ],
            'cache' => [
                'label' => 'Cache',
                'status' => 'ok',
                'detail' => config('cache.default'),
            ],
            'queue' => [
                'label' => 'Files d’attente',
                'status' => 'ok',
                'detail' => config('queue.default'),
            ],
            'storage' => [
                'label' => 'Stockage local',
                'status' => File::isWritable(storage_path('app')) ? 'ok' : 'down',
                'detail' => storage_path('app'),
            ],
            'app' => [
                'label' => 'Application',
                'status' => 'ok',
                'detail' => 'health',
            ],
        ];

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $services['database']['status'] = 'down';
            $services['database']['detail'] = $e->getMessage();
        }

        try {
            cache()->put('system_health_ping', now()->timestamp, 10);
            if (! cache()->has('system_health_ping')) {
                $services['cache']['status'] = 'degraded';
            }
        } catch (Throwable $e) {
            $services['cache']['status'] = 'down';
            $services['cache']['detail'] = $e->getMessage();
        }

        try {
            $url = rtrim((string) config('app.url'), '/').'/up';
            $response = Http::timeout(3)->get($url);
            if (! $response->successful()) {
                $services['app']['status'] = 'degraded';
                $services['app']['detail'] = 'HTTP '.$response->status();
            }
        } catch (Throwable) {
            // Local CLI / no server — keep ok if DB works
            $services['app']['detail'] = 'endpoint /up non joignable (hors processus web)';
        }

        return $services;
    }

    private function syncAlerts(?int $companyId, array $health): void
    {
        if (($health['database_status'] ?? '') !== 'ok') {
            $this->upsertAlert($companyId, 'database_down', 'critical', 'Base de données inaccessible', $health['database_error'] ?? null);
        } else {
            $this->resolveType($companyId, 'database_down');
        }

        $used = (float) data_get($health, 'disk.used_percent', 0);
        if ($used >= 90) {
            $this->upsertAlert($companyId, 'disk_low', $used >= 95 ? 'critical' : 'warning', 'Espace disque faible', 'Utilisation disque à '.$used.'%.');
        } else {
            $this->resolveType($companyId, 'disk_low');
        }

        foreach ($health['services'] ?? [] as $key => $service) {
            if (($service['status'] ?? '') === 'down') {
                $this->upsertAlert(
                    $companyId,
                    'service_down',
                    'critical',
                    'Service indisponible : '.($service['label'] ?? $key),
                    $service['detail'] ?? null,
                    ['service' => $key]
                );
            }
        }
    }

    private function upsertAlert(?int $companyId, string $type, string $severity, string $title, ?string $body, array $meta = []): void
    {
        $q = SystemAlert::query()->where('type', $type)->where('is_resolved', false);
        if ($companyId) {
            $q->where('company_id', $companyId);
        } else {
            $q->whereNull('company_id');
        }
        $existing = $q->first();
        if ($existing) {
            $existing->update(compact('severity', 'title', 'body') + ['meta' => $meta ?: $existing->meta]);

            return;
        }

        SystemAlert::query()->create([
            'company_id' => $companyId,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'body' => $body,
            'meta' => $meta ?: null,
        ]);

        SystemHealthEvent::query()->create([
            'company_id' => $companyId,
            'category' => 'incident',
            'severity' => $severity,
            'title' => $title,
            'body' => $body,
            'payload' => array_merge(['type' => $type], $meta),
        ]);
    }

    private function resolveType(?int $companyId, string $type): void
    {
        $q = SystemAlert::query()->where('type', $type)->where('is_resolved', false);
        if ($companyId) {
            $q->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'));
        }
        $q->update(['is_resolved' => true, 'resolved_at' => now()]);
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' o';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' Ko';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2).' Mo';
        }

        return round($bytes / 1073741824, 2).' Go';
    }
}
