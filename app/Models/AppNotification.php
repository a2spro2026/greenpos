<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    public const TYPES = [
        'info' => 'Information',
        'success' => 'Succès',
        'warning' => 'Avertissement',
        'error' => 'Erreur',
        'critical' => 'Critique',
    ];

    public const TYPE_COLORS = [
        'info' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-200',
        'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200',
        'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200',
        'error' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200',
        'critical' => 'bg-red-100 text-red-900 dark:bg-red-500/25 dark:text-red-200',
    ];

    public const PRIORITIES = [
        'low' => 'Basse',
        'normal' => 'Normale',
        'high' => 'Haute',
        'critical' => 'Critique',
    ];

    public const STATUSES = [
        'unread' => 'Non lue',
        'read' => 'Lue',
        'archived' => 'Archivée',
    ];

    public const CATEGORIES = [
        'stock_critical' => 'Stock critique',
        'new_sale' => 'Nouvelle vente',
        'payment_received' => 'Paiement reçu',
        'invoice_overdue' => 'Facture échue',
        'quote_accepted' => 'Devis accepté',
        'new_order' => 'Nouvelle commande',
        'inventory_done' => 'Inventaire terminé',
        'user_created' => 'Utilisateur créé',
        'suspicious_login' => 'Connexion suspecte',
        'backup_done' => 'Sauvegarde terminée',
        'system' => 'Système',
    ];

    protected $fillable = [
        'company_id', 'store_id', 'user_id', 'actor_id',
        'type', 'category', 'priority', 'title', 'body', 'icon', 'action_url',
        'status', 'channels', 'meta', 'read_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'meta' => 'array',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForRecipient(Builder $query, ?int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->whereNull('user_id');
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'unread');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%");
        });
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function typeColor(): string
    {
        return self::TYPE_COLORS[$this->type] ?? self::TYPE_COLORS['info'];
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ?: '—');
    }

    public function isUnread(): bool
    {
        return $this->status === 'unread';
    }

    public function isCritical(): bool
    {
        return $this->type === 'critical' || $this->priority === 'critical';
    }
}
