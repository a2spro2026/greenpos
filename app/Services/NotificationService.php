<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function preferencesFor(?User $user = null): NotificationPreference
    {
        $user = $user ?? Auth::user();
        $company = Workspace::company();

        $prefs = NotificationPreference::query()->firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $user->id],
            NotificationPreference::defaults()
        );

        // Merge defaults for missing keys
        $defaults = NotificationPreference::defaults();
        $prefs->types = $prefs->types ?: $defaults['types'];
        $prefs->categories = $prefs->categories ?: $defaults['categories'];
        $prefs->channels = array_merge($defaults['channels'], $prefs->channels ?? []);

        return $prefs;
    }

    public function savePreferences(array $data, ?User $user = null): NotificationPreference
    {
        $prefs = $this->preferencesFor($user);
        $prefs->update([
            'enabled' => (bool) ($data['enabled'] ?? true),
            'frequency' => $data['frequency'] ?? 'realtime',
            'types' => $data['types'] ?? array_keys(AppNotification::TYPES),
            'categories' => $data['categories'] ?? array_keys(AppNotification::CATEGORIES),
            'channels' => [
                'internal' => (bool) ($data['channels']['internal'] ?? true),
                'email' => (bool) ($data['channels']['email'] ?? false),
                'sms' => (bool) ($data['channels']['sms'] ?? false),
                'whatsapp' => (bool) ($data['channels']['whatsapp'] ?? false),
                'push' => (bool) ($data['channels']['push'] ?? false),
            ],
        ]);

        return $prefs->fresh();
    }

    /**
     * Dispatch a business notification (internal + prepared external channels).
     */
    public function dispatch(array $payload): ?AppNotification
    {
        $company = Workspace::company();
        if (! $company) {
            return null;
        }

        $userId = $payload['user_id'] ?? null;
        $type = $payload['type'] ?? 'info';
        $category = $payload['category'] ?? 'system';

        if ($userId) {
            $user = User::query()->find($userId);
            if ($user && ! $this->shouldReceive($user, $type, $category)) {
                return null;
            }
        }

        $channels = $payload['channels'] ?? ['internal' => true];
        if ($userId) {
            $prefs = $this->preferencesFor(User::query()->find($userId));
            $channels = array_merge($channels, array_filter($prefs->channels ?? [], fn ($v) => $v));
            $channels['internal'] = ($prefs->channels['internal'] ?? true);
        }

        $notification = AppNotification::query()->create([
            'company_id' => $company->id,
            'store_id' => $payload['store_id'] ?? Workspace::store()?->id,
            'user_id' => $userId,
            'actor_id' => $payload['actor_id'] ?? Auth::id(),
            'type' => $type,
            'category' => $category,
            'priority' => $payload['priority'] ?? ($type === 'critical' ? 'critical' : 'normal'),
            'title' => $payload['title'],
            'body' => $payload['body'] ?? null,
            'icon' => $payload['icon'] ?? $this->defaultIcon($type),
            'action_url' => $payload['action_url'] ?? null,
            'status' => 'unread',
            'channels' => $channels,
            'meta' => $payload['meta'] ?? null,
        ]);

        $this->deliverPreparedChannels($notification);

        return $notification;
    }

    public function shouldReceive(User $user, string $type, string $category): bool
    {
        $prefs = $this->preferencesFor($user);
        if (! $prefs->enabled) {
            return false;
        }
        if (! in_array($type, $prefs->types ?? [], true)) {
            return false;
        }
        if ($category && ! in_array($category, $prefs->categories ?? [], true)) {
            return false;
        }

        return true;
    }

    /**
     * External channels are prepared (logged) — not fully wired yet.
     */
    protected function deliverPreparedChannels(AppNotification $notification): void
    {
        $channels = $notification->channels ?? [];

        foreach (['email', 'sms', 'whatsapp', 'push'] as $channel) {
            if (! empty($channels[$channel])) {
                Log::info("notification.{$channel}.prepared", [
                    'notification_id' => $notification->id,
                    'company_id' => $notification->company_id,
                    'user_id' => $notification->user_id,
                    'title' => $notification->title,
                ]);
            }
        }
    }

    public function queryForCurrentUser()
    {
        $company = Workspace::company();
        $user = Auth::user();

        return AppNotification::query()
            ->forCompany($company->id)
            ->forRecipient($user?->id)
            ->with(['user', 'actor', 'store']);
    }

    public function dashboardStats(): array
    {
        $base = $this->queryForCurrentUser();

        return [
            'unread' => (clone $base)->where('status', 'unread')->count(),
            'critical' => (clone $base)->where(function ($q) {
                $q->where('type', 'critical')->orWhere('priority', 'critical');
            })->where('status', '!=', 'archived')->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'archived' => (clone $base)->where('status', 'archived')->count(),
            'read' => (clone $base)->where('status', 'read')->count(),
            'total' => (clone $base)->count(),
        ];
    }

    public function unreadCount(): int
    {
        return $this->queryForCurrentUser()->where('status', 'unread')->count();
    }

    public function latest(int $limit = 8): Collection
    {
        return $this->queryForCurrentUser()
            ->where('status', '!=', 'archived')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markRead(AppNotification $notification): AppNotification
    {
        $this->assertAccess($notification);
        if ($notification->status === 'unread') {
            $notification->update(['status' => 'read', 'read_at' => now()]);
        }

        return $notification->fresh();
    }

    public function markAllRead(): int
    {
        return $this->queryForCurrentUser()
            ->where('status', 'unread')
            ->update(['status' => 'read', 'read_at' => now()]);
    }

    public function archive(AppNotification $notification): AppNotification
    {
        $this->assertAccess($notification);
        $notification->update([
            'status' => 'archived',
            'archived_at' => now(),
            'read_at' => $notification->read_at ?? now(),
        ]);

        return $notification->fresh();
    }

    public function delete(AppNotification $notification): void
    {
        $this->assertAccess($notification);
        $notification->delete();
    }

    public function assertAccess(AppNotification $notification): void
    {
        $company = Workspace::company();
        $user = Auth::user();
        if ($notification->company_id !== $company?->id) {
            abort(404);
        }
        if ($notification->user_id && $notification->user_id !== $user?->id) {
            // Allow company admins to manage all
            if (! Workspace::can('notifications.delete') && ! in_array(Workspace::role(), ['owner', 'admin'], true)) {
                abort(403);
            }
        }
    }

    public function seedDemo(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        $samples = [
            ['type' => 'critical', 'category' => 'stock_critical', 'priority' => 'critical', 'title' => 'Stock critique', 'body' => '3 produits sous le seuil minimum.', 'action_url' => '/stock/alerts'],
            ['type' => 'success', 'category' => 'new_sale', 'priority' => 'normal', 'title' => 'Nouvelle vente', 'body' => 'Vente VTE-2026-0042 enregistrée.', 'action_url' => '/sales'],
            ['type' => 'success', 'category' => 'payment_received', 'priority' => 'normal', 'title' => 'Paiement reçu', 'body' => 'Paiement de 1 250,00 MAD encaissé.', 'action_url' => '/invoices'],
            ['type' => 'warning', 'category' => 'invoice_overdue', 'priority' => 'high', 'title' => 'Facture échue', 'body' => 'FAC-2026-0018 dépasse la date d\'échéance.', 'action_url' => '/invoices'],
            ['type' => 'success', 'category' => 'quote_accepted', 'priority' => 'normal', 'title' => 'Devis accepté', 'body' => 'Le client a accepté le devis DEV-2026-0007.', 'action_url' => '/quotes'],
            ['type' => 'info', 'category' => 'new_order', 'priority' => 'normal', 'title' => 'Nouvelle commande', 'body' => 'Commande d\'achat CMD-2026-0011 créée.', 'action_url' => '/purchases'],
            ['type' => 'info', 'category' => 'inventory_done', 'priority' => 'normal', 'title' => 'Inventaire terminé', 'body' => 'L\'inventaire de la boutique a été clôturé.', 'action_url' => '/stock'],
            ['type' => 'info', 'category' => 'user_created', 'priority' => 'low', 'title' => 'Utilisateur créé', 'body' => 'Un nouveau collaborateur a rejoint l\'espace.', 'action_url' => '/users'],
            ['type' => 'warning', 'category' => 'suspicious_login', 'priority' => 'high', 'title' => 'Connexion suspecte', 'body' => 'Connexion depuis un nouvel appareil détectée.', 'action_url' => '/users'],
            ['type' => 'success', 'category' => 'backup_done', 'priority' => 'low', 'title' => 'Sauvegarde terminée', 'body' => 'La sauvegarde nocturne s\'est terminée avec succès.', 'action_url' => '/settings'],
        ];

        foreach ($samples as $sample) {
            $exists = AppNotification::query()
                ->where('company_id', Workspace::company()->id)
                ->where('user_id', $userId)
                ->where('category', $sample['category'])
                ->where('title', $sample['title'])
                ->exists();
            if ($exists) {
                continue;
            }
            $this->dispatch(array_merge($sample, [
                'user_id' => $userId,
                'channels' => ['internal' => true, 'email' => false],
            ]));
        }
    }

    protected function defaultIcon(string $type): string
    {
        return match ($type) {
            'success' => 'check',
            'warning' => 'alert',
            'error', 'critical' => 'error',
            default => 'info',
        };
    }
}
