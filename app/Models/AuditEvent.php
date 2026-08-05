<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    public const SEVERITIES = [
        'info' => 'Information',
        'warning' => 'Avertissement',
        'important' => 'Important',
        'critical' => 'Critique',
    ];

    public const SEVERITY_COLORS = [
        'info' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
        'important' => 'bg-violet-100 text-violet-800 dark:bg-violet-500/20 dark:text-violet-200',
        'critical' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
    ];

    public const ACTIONS = [
        'login' => 'Connexion',
        'logout' => 'Déconnexion',
        'create' => 'Création',
        'update' => 'Modification',
        'delete' => 'Suppression',
        'print' => 'Impression',
        'export' => 'Export',
        'import' => 'Import',
        'payment' => 'Paiement',
        'sale' => 'Vente',
        'purchase' => 'Achat',
        'invoice' => 'Facture',
        'quote' => 'Devis',
        'inventory' => 'Inventaire',
        'stock_move' => 'Mouvement de stock',
        'pos_open' => 'Ouverture de caisse',
        'pos_close' => 'Fermeture de caisse',
        'settings' => 'Paramètres',
        'permission' => 'Permissions',
        'switch_store' => 'Changement de boutique',
        'switch_company' => 'Changement d\'entreprise',
        'archive' => 'Archivage',
        'view' => 'Consultation',
        'other' => 'Autre',
    ];

    public const EVENT_TYPES = [
        'auth' => 'Authentification',
        'crud' => 'Données',
        'finance' => 'Finance',
        'stock' => 'Stock',
        'pos' => 'POS',
        'settings' => 'Configuration',
        'security' => 'Sécurité',
        'system' => 'Système',
    ];

    public const RESULTS = [
        'success' => 'Succès',
        'failure' => 'Échec',
        'denied' => 'Refusé',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'user_id',
        'module', 'action', 'event_type', 'severity',
        'subject_type', 'subject_id', 'subject_label', 'description',
        'old_values', 'new_values', 'result',
        'ip_address', 'user_agent', 'device', 'browser', 'platform',
        'route_name', 'http_method', 'url', 'duration_ms', 'system_notes', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'occurred_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForCompany(Builder $query, ?int $companyId): Builder
    {
        return $companyId ? $query->where('company_id', $companyId) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
                ->orWhere('subject_label', 'like', "%{$term}%")
                ->orWhere('module', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('ip_address', 'like', "%{$term}%")
                ->orWhere('route_name', 'like', "%{$term}%");
        });
    }

    public function severityLabel(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }

    public function severityColor(): string
    {
        return self::SEVERITY_COLORS[$this->severity] ?? self::SEVERITY_COLORS['info'];
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function eventTypeLabel(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? ($this->event_type ?: '—');
    }

    public function resultLabel(): string
    {
        return self::RESULTS[$this->result] ?? $this->result;
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}
