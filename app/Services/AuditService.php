<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(array $payload): AuditEvent
    {
        $request = request();
        $ua = $payload['user_agent'] ?? $request?->userAgent();
        $parsed = $this->parseUserAgent($ua);

        return AuditEvent::query()->create([
            'company_id' => $payload['company_id'] ?? Workspace::company()?->id,
            'store_id' => $payload['store_id'] ?? Workspace::store()?->id,
            'user_id' => $payload['user_id'] ?? Auth::id(),
            'module' => $payload['module'] ?? 'system',
            'action' => $payload['action'] ?? 'other',
            'event_type' => $payload['event_type'] ?? 'system',
            'severity' => $payload['severity'] ?? 'info',
            'subject_type' => $payload['subject_type'] ?? null,
            'subject_id' => $payload['subject_id'] ?? null,
            'subject_label' => $payload['subject_label'] ?? null,
            'description' => $payload['description'] ?? null,
            'old_values' => $payload['old_values'] ?? null,
            'new_values' => $payload['new_values'] ?? null,
            'result' => $payload['result'] ?? 'success',
            'ip_address' => $payload['ip_address'] ?? $request?->ip(),
            'user_agent' => $ua,
            'device' => $payload['device'] ?? $parsed['device'],
            'browser' => $payload['browser'] ?? $parsed['browser'],
            'platform' => $payload['platform'] ?? $parsed['platform'],
            'route_name' => $payload['route_name'] ?? ($request?->route()?->getName()),
            'http_method' => $payload['http_method'] ?? $request?->method(),
            'url' => $payload['url'] ?? ($request ? substr($request->fullUrl(), 0, 500) : null),
            'duration_ms' => $payload['duration_ms'] ?? null,
            'system_notes' => $payload['system_notes'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? now(),
        ]);
    }

    public function logFromRequest(Request $request, int $statusCode, ?float $startedAt = null): ?AuditEvent
    {
        $method = strtoupper($request->method());
        $routeName = $request->route()?->getName() ?? '';

        $shouldLog = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $this->isSensitiveGet($routeName);

        if (! $shouldLog || $this->shouldSkipRoute($routeName)) {
            return null;
        }

        [$module, $action, $eventType, $severity] = $this->mapRoute($routeName, $method);
        $sanitized = $this->sanitizeInput($request->except([
            '_token', '_method', 'password', 'password_confirmation', 'current_password',
        ]));

        $result = $statusCode >= 400 ? ($statusCode === 403 ? 'denied' : 'failure') : 'success';
        if ($result !== 'success' && $severity === 'info') {
            $severity = 'warning';
        }

        $duration = $startedAt ? (int) ((microtime(true) - $startedAt) * 1000) : null;

        return $this->log([
            'module' => $module,
            'action' => $action,
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $this->buildDescription($module, $action, $routeName),
            'subject_label' => $routeName ?: $request->path(),
            'new_values' => $sanitized ?: null,
            'result' => $result,
            'route_name' => $routeName ?: null,
            'http_method' => $method,
            'duration_ms' => $duration,
            'system_notes' => 'HTTP '.$statusCode,
        ]);
    }

    public function dashboardStats(): array
    {
        $companyId = Workspace::company()?->id;
        $base = AuditEvent::query()->forCompany($companyId);

        $today = (clone $base)->whereDate('occurred_at', today())->count();
        $critical = (clone $base)->where('severity', 'critical')->where('occurred_at', '>=', now()->subDays(7))->count();

        $logins = (clone $base)->where('action', 'login')
            ->latest('occurred_at')
            ->limit(8)
            ->with('user')
            ->get();

        $topUsers = (clone $base)
            ->where('occurred_at', '>=', now()->subDays(7))
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->limit(6)
            ->with('user')
            ->get();

        $byModule = (clone $base)
            ->where('occurred_at', '>=', now()->subDays(14))
            ->selectRaw('module, COUNT(*) as cnt')
            ->groupBy('module')
            ->orderByDesc('cnt')
            ->limit(8)
            ->pluck('cnt', 'module');

        $byDay = collect(range(6, 0))->map(function (int $i) use ($base) {
            $day = now()->subDays($i)->startOfDay();

            return [
                'label' => $day->format('D d'),
                'count' => (clone $base)->whereBetween('occurred_at', [$day, (clone $day)->endOfDay()])->count(),
            ];
        });

        $bySeverity = (clone $base)
            ->where('occurred_at', '>=', now()->subDays(14))
            ->selectRaw('severity, COUNT(*) as cnt')
            ->groupBy('severity')
            ->pluck('cnt', 'severity');

        return [
            'today' => $today,
            'critical' => $critical,
            'week' => (clone $base)->where('occurred_at', '>=', now()->subDays(7))->count(),
            'total' => (clone $base)->count(),
            'logins' => $logins,
            'top_users' => $topUsers,
            'by_module' => $byModule,
            'by_day' => $byDay,
            'by_severity' => $bySeverity,
            'recent' => (clone $base)->latest('occurred_at')->limit(10)->with(['user', 'store'])->get(),
        ];
    }

    public function purgeOlderThan(int $days): int
    {
        $companyId = Workspace::company()?->id;
        $cutoff = now()->subDays($days);

        return AuditEvent::query()
            ->forCompany($companyId)
            ->where('occurred_at', '<', $cutoff)
            ->where('severity', '!=', 'critical') // keep critical longer by default
            ->delete();
    }

    public function seedDemo(?int $userId = null): void
    {
        $companyId = Workspace::company()?->id;
        if (! $companyId) {
            return;
        }

        if (AuditEvent::query()->forCompany($companyId)->count() >= 15) {
            return;
        }

        $userId = $userId ?? Auth::id();
        $samples = [
            ['action' => 'login', 'module' => 'auth', 'event_type' => 'auth', 'severity' => 'info', 'description' => 'Connexion réussie'],
            ['action' => 'create', 'module' => 'products', 'event_type' => 'crud', 'severity' => 'info', 'description' => 'Produit créé', 'subject_label' => 'Produit démo'],
            ['action' => 'update', 'module' => 'customers', 'event_type' => 'crud', 'severity' => 'info', 'description' => 'Client modifié', 'old_values' => ['name' => 'Ancien'], 'new_values' => ['name' => 'Nouveau']],
            ['action' => 'sale', 'module' => 'sales', 'event_type' => 'finance', 'severity' => 'important', 'description' => 'Vente enregistrée'],
            ['action' => 'payment', 'module' => 'payments', 'event_type' => 'finance', 'severity' => 'important', 'description' => 'Paiement reçu'],
            ['action' => 'invoice', 'module' => 'invoices', 'event_type' => 'finance', 'severity' => 'info', 'description' => 'Facture émise'],
            ['action' => 'quote', 'module' => 'quotes', 'event_type' => 'finance', 'severity' => 'info', 'description' => 'Devis accepté'],
            ['action' => 'purchase', 'module' => 'purchases', 'event_type' => 'finance', 'severity' => 'info', 'description' => 'Commande d\'achat'],
            ['action' => 'stock_move', 'module' => 'stock', 'event_type' => 'stock', 'severity' => 'warning', 'description' => 'Mouvement de stock'],
            ['action' => 'inventory', 'module' => 'stock', 'event_type' => 'stock', 'severity' => 'important', 'description' => 'Inventaire clôturé'],
            ['action' => 'pos_open', 'module' => 'pos', 'event_type' => 'pos', 'severity' => 'info', 'description' => 'Ouverture de caisse'],
            ['action' => 'pos_close', 'module' => 'pos', 'event_type' => 'pos', 'severity' => 'important', 'description' => 'Fermeture de caisse'],
            ['action' => 'export', 'module' => 'reports', 'event_type' => 'system', 'severity' => 'warning', 'description' => 'Export rapport'],
            ['action' => 'print', 'module' => 'invoices', 'event_type' => 'system', 'severity' => 'info', 'description' => 'Impression facture'],
            ['action' => 'settings', 'module' => 'settings', 'event_type' => 'settings', 'severity' => 'important', 'description' => 'Paramètres mis à jour'],
            ['action' => 'permission', 'module' => 'roles', 'event_type' => 'security', 'severity' => 'critical', 'description' => 'Permissions modifiées'],
            ['action' => 'switch_store', 'module' => 'stores', 'event_type' => 'system', 'severity' => 'info', 'description' => 'Changement de boutique'],
            ['action' => 'switch_company', 'module' => 'companies', 'event_type' => 'system', 'severity' => 'important', 'description' => 'Changement d\'entreprise'],
            ['action' => 'delete', 'module' => 'documents', 'event_type' => 'crud', 'severity' => 'critical', 'description' => 'Document supprimé'],
            ['action' => 'import', 'module' => 'documents', 'event_type' => 'crud', 'severity' => 'info', 'description' => 'Import documentaire'],
        ];

        foreach ($samples as $i => $sample) {
            $this->log(array_merge($sample, [
                'user_id' => $userId,
                'company_id' => $companyId,
                'occurred_at' => now()->subMinutes(count($samples) - $i),
                'result' => 'success',
                'ip_address' => '127.0.0.1',
                'browser' => 'Chrome',
                'platform' => 'Windows',
                'device' => 'Desktop',
            ]));
        }
    }

    protected function isSensitiveGet(string $routeName): bool
    {
        foreach (['.export', '.print', '.download', '.pdf', '.preview'] as $suffix) {
            if (str_contains($routeName, $suffix)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldSkipRoute(string $routeName): bool
    {
        // Avoid recursive/noisy logging
        $skip = [
            'audit.',
            'notifications.mark',
            'livewire',
        ];

        foreach ($skip as $prefix) {
            if ($routeName !== '' && str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        // Don't log audit purge itself as noise from mark-read spam - notifications mark is ok to skip via prefix

        return false;
    }

    /**
     * @return array{0:string,1:string,2:string,3:string} module, action, event_type, severity
     */
    protected function mapRoute(string $routeName, string $method): array
    {
        $module = 'system';
        if ($routeName && str_contains($routeName, '.')) {
            $module = explode('.', $routeName)[0];
        }

        $action = match (true) {
            str_contains($routeName, 'login') => 'login',
            str_contains($routeName, 'logout') => 'logout',
            str_contains($routeName, 'switch-all') || (str_contains($routeName, 'stores') && str_contains($routeName, 'switch')) => 'switch_store',
            str_contains($routeName, 'companies') && str_contains($routeName, 'switch') => 'switch_company',
            str_contains($routeName, 'roles') || str_contains($routeName, 'permission') || str_contains($routeName, 'assign') => 'permission',
            str_contains($routeName, 'settings') => 'settings',
            str_contains($routeName, 'export') => 'export',
            str_contains($routeName, 'print') || str_contains($routeName, 'pdf') => 'print',
            str_contains($routeName, 'import') || str_contains($routeName, 'upload') => 'import',
            str_contains($routeName, 'sessions.open') || str_contains($routeName, 'sessions.store') || (str_contains($routeName, 'pos') && str_contains($routeName, 'open')) => 'pos_open',
            str_contains($routeName, 'sessions.close') || (str_contains($routeName, 'pos') && str_contains($routeName, 'close')) => 'pos_close',
            str_contains($routeName, 'inventory') => 'inventory',
            str_contains($routeName, 'movements') || (str_contains($routeName, 'stock') && str_contains($routeName, 'move')) => 'stock_move',
            str_contains($routeName, 'payments') && $method === 'POST' => 'payment',
            str_contains($routeName, 'sales') && in_array($method, ['POST', 'PUT'], true) => 'sale',
            str_contains($routeName, 'purchases') && in_array($method, ['POST', 'PUT'], true) => 'purchase',
            str_contains($routeName, 'invoices') && in_array($method, ['POST', 'PUT'], true) => 'invoice',
            str_contains($routeName, 'quotes') && in_array($method, ['POST', 'PUT'], true) => 'quote',
            str_contains($routeName, 'archive') || str_contains($routeName, 'deactivate') => 'archive',
            str_contains($routeName, 'destroy') || $method === 'DELETE' => 'delete',
            str_contains($routeName, 'store') && $method === 'POST' => 'create',
            str_contains($routeName, 'update') || $method === 'PUT' || $method === 'PATCH' => 'update',
            default => 'other',
        };

        $eventType = match ($module) {
            'pos' => 'pos',
            'stock' => 'stock',
            'settings', 'roles', 'users' => str_contains($action, 'permission') || $module === 'roles' ? 'security' : 'settings',
            'sales', 'purchases', 'invoices', 'quotes', 'payments' => 'finance',
            default => in_array($action, ['login', 'logout'], true) ? 'auth' : 'crud',
        };

        if (in_array($action, ['login', 'logout'], true)) {
            $eventType = 'auth';
        }

        $severity = match ($action) {
            'delete', 'permission' => 'critical',
            'settings', 'switch_company', 'pos_close', 'payment', 'inventory' => 'important',
            'export', 'stock_move', 'archive' => 'warning',
            default => 'info',
        };

        return [$module, $action, $eventType, $severity];
    }

    protected function buildDescription(string $module, string $action, string $routeName): string
    {
        $actionLabel = AuditEvent::ACTIONS[$action] ?? $action;

        return trim($actionLabel.' · '.$module.($routeName ? " ({$routeName})" : ''));
    }

    protected function sanitizeInput(array $input): array
    {
        $clean = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = $this->sanitizeInput($value);
            } elseif (is_scalar($value) || $value === null) {
                $str = (string) $value;
                $clean[$key] = strlen($str) > 500 ? substr($str, 0, 500).'…' : $value;
            } else {
                $clean[$key] = '[object]';
            }
        }

        // Cap size
        $json = json_encode($clean);
        if ($json && strlen($json) > 8000) {
            return ['_truncated' => true, 'keys' => array_keys($clean)];
        }

        return $clean;
    }

    /**
     * @return array{device:string,browser:string,platform:string}
     */
    public function parseUserAgent(?string $ua): array
    {
        $ua = $ua ?: '';
        $browser = 'Unknown';
        $platform = 'Unknown';
        $device = 'Desktop';

        if (preg_match('/Edg\/|Edge\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//i', $ua) && ! preg_match('/Chrome/i', $ua)) {
            $browser = 'Safari';
        }

        if (preg_match('/Windows/i', $ua)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $platform = 'Android';
            $device = 'Mobile';
        } elseif (preg_match('/iPhone|iPad/i', $ua)) {
            $platform = 'iOS';
            $device = preg_match('/iPad/i', $ua) ? 'Tablet' : 'Mobile';
        } elseif (preg_match('/Linux/i', $ua)) {
            $platform = 'Linux';
        }

        if (preg_match('/Mobile/i', $ua) && $device === 'Desktop') {
            $device = 'Mobile';
        }

        return compact('device', 'browser', 'platform');
    }
}
