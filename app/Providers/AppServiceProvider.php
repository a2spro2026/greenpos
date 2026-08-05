<?php

namespace App\Providers;

use App\Models\Product;
use App\Policies\ProductPolicy;
use App\Services\AuditService;
use App\Support\Workspace;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);

        foreach (['view', 'move', 'adjust', 'inventory', 'export', 'valuation'] as $ability) {
            Gate::define('stock.'.$ability, fn () => Workspace::can('stock.'.$ability));
        }

        foreach (['view', 'create', 'update', 'cancel', 'receive', 'export', 'print'] as $ability) {
            Gate::define('purchases.'.$ability, fn () => Workspace::can('purchases.'.$ability));
        }

        foreach (['view', 'create', 'update', 'delete', 'export', 'print', 'stats'] as $ability) {
            Gate::define('suppliers.'.$ability, fn () => Workspace::can('suppliers.'.$ability));
        }

        foreach (['view', 'create', 'update', 'delete', 'export', 'print', 'stats'] as $ability) {
            Gate::define('customers.'.$ability, fn () => Workspace::can('customers.'.$ability));
        }

        foreach (['view', 'sell', 'open', 'close', 'hold', 'cancel', 'reprint', 'history'] as $ability) {
            Gate::define('pos.'.$ability, fn () => Workspace::can('pos.'.$ability));
        }

        foreach (['view', 'create', 'update', 'delete', 'cancel', 'export', 'print', 'pdf', 'send'] as $ability) {
            Gate::define('invoices.'.$ability, fn () => Workspace::can('invoices.'.$ability));
        }

        foreach (['view', 'create', 'update', 'delete', 'export', 'print', 'convert', 'send'] as $ability) {
            Gate::define('quotes.'.$ability, fn () => Workspace::can('quotes.'.$ability));
        }

        foreach (['view', 'create', 'update', 'cancel', 'return', 'export', 'print'] as $ability) {
            Gate::define('sales.'.$ability, fn () => Workspace::can('sales.'.$ability));
        }

        foreach (['view', 'export', 'print', 'financial', 'advanced'] as $ability) {
            Gate::define('reports.'.$ability, fn () => Workspace::can('reports.'.$ability));
        }

        foreach (['view', 'create', 'update', 'delete', 'reset', 'invite', 'export', 'print'] as $ability) {
            Gate::define('users.'.$ability, fn () => Workspace::can('users.'.$ability));
        }

        foreach (['view', 'create', 'update', 'delete', 'export', 'print'] as $ability) {
            Gate::define('roles.'.$ability, fn () => Workspace::can('roles.'.$ability));
        }

        foreach (['view', 'export', 'print'] as $ability) {
            Gate::define('dashboard.'.$ability, fn () => Workspace::can('dashboard.'.$ability));
        }

        foreach (['view', 'create', 'update', 'delete', 'export', 'print', 'validate', 'cancel'] as $ability) {
            Gate::define('payments.'.$ability, fn () => Workspace::can('payments.'.$ability));
        }

        // Catch-all for catalog permissions (settings, audit, documents, notifications, scope…)
        Gate::before(function ($user, string $ability) {
            if (! str_contains($ability, '.')) {
                return null;
            }

            return Workspace::can($ability) ?: null;
        });

        \Illuminate\Pagination\Paginator::useTailwind();

        Event::listen(Login::class, function (Login $event) {
            if (! Schema::hasTable('audit_events')) {
                return;
            }
            try {
                app(AuditService::class)->log([
                    'user_id' => $event->user->id,
                    'module' => 'auth',
                    'action' => 'login',
                    'event_type' => 'auth',
                    'severity' => 'info',
                    'description' => 'Connexion réussie',
                    'subject_type' => $event->user::class,
                    'subject_id' => $event->user->id,
                    'subject_label' => method_exists($event->user, 'displayName')
                        ? $event->user->displayName()
                        : $event->user->email,
                ]);
            } catch (\Throwable) {
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (! Schema::hasTable('audit_events') || ! $event->user) {
                return;
            }
            try {
                app(AuditService::class)->log([
                    'user_id' => $event->user->id,
                    'module' => 'auth',
                    'action' => 'logout',
                    'event_type' => 'auth',
                    'severity' => 'info',
                    'description' => 'Déconnexion',
                    'subject_type' => $event->user::class,
                    'subject_id' => $event->user->id,
                    'subject_label' => method_exists($event->user, 'displayName')
                        ? $event->user->displayName()
                        : $event->user->email,
                ]);
            } catch (\Throwable) {
            }
        });

        $regMail = \App\Listeners\SendCompanyRegistrationNotifications::class;
        Event::listen(\App\Events\CompanyRegistrationSubmitted::class, [$regMail, 'handleSubmitted']);
        Event::listen(\App\Events\CompanyRegistrationApproved::class, [$regMail, 'handleApproved']);
        Event::listen(\App\Events\CompanyRegistrationRejected::class, [$regMail, 'handleRejected']);
        Event::listen(\App\Events\CompanyRegistrationSuspended::class, [$regMail, 'handleSuspended']);

        // First-boot: ensure Super Admin + GreenPOS workspace exist
        try {
            if (Schema::hasTable('users')) {
                app(\App\Services\PlatformBootstrapService::class)->ensureIfEmpty();
            }
        } catch (\Throwable) {
            // Migrations may not be ready yet
        }
    }
}
