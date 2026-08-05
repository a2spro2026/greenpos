<?php

namespace App\Support;

class PermissionCatalog
{
    public const ACTIONS = [
        'view' => 'Voir',
        'create' => 'Créer',
        'update' => 'Modifier',
        'delete' => 'Supprimer',
        'export' => 'Exporter',
        'import' => 'Importer',
        'print' => 'Imprimer',
        'validate' => 'Valider',
        'cancel' => 'Annuler',
        'approve' => 'Approuver',
    ];

    public const MODULES = [
        'dashboard' => 'Dashboard',
        'products' => 'Produits',
        'stock' => 'Stock',
        'purchases' => 'Achats',
        'suppliers' => 'Fournisseurs',
        'customers' => 'Clients',
        'pos' => 'POS',
        'payments' => 'Paiements',
        'invoices' => 'Facturation',
        'quotes' => 'Devis',
        'sales' => 'Ventes',
        'reports' => 'Rapports',
        'users' => 'Utilisateurs',
        'roles' => 'Rôles & Permissions',
        'settings' => 'Paramètres',
        'stores' => 'Boutiques',
        'companies' => 'Entreprises',
        'notifications' => 'Notifications',
        'documents' => 'Documents',
        'audit' => 'Audit',
    ];

    /**
     * Module => list of actions used in the permission matrix.
     */
    public static function moduleActions(): array
    {
        return [
            'dashboard' => ['view', 'export', 'print'],
            'products' => ['view', 'create', 'update', 'delete', 'export', 'import', 'print'],
            'stock' => ['view', 'create', 'update', 'delete', 'export', 'import', 'print', 'validate'],
            'purchases' => ['view', 'create', 'update', 'delete', 'export', 'print', 'validate', 'cancel', 'approve'],
            'suppliers' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'customers' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'pos' => ['view', 'create', 'update', 'delete', 'export', 'print', 'validate', 'cancel'],
            'payments' => ['view', 'create', 'update', 'delete', 'export', 'print', 'validate', 'cancel'],
            'invoices' => ['view', 'create', 'update', 'delete', 'export', 'print', 'validate', 'cancel', 'approve'],
            'quotes' => ['view', 'create', 'update', 'delete', 'export', 'print', 'validate', 'cancel', 'approve'],
            'sales' => ['view', 'create', 'update', 'delete', 'export', 'print', 'validate', 'cancel'],
            'reports' => ['view', 'export', 'print'],
            'users' => ['view', 'create', 'update', 'delete', 'export', 'print', 'approve'],
            'roles' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'settings' => ['view', 'update'],
            'stores' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'companies' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'notifications' => ['view', 'create', 'update', 'delete'],
            'documents' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'audit' => ['view', 'export', 'print'],
        ];
    }


    /**
     * Extra permissions beyond the matrix columns (legacy / special keys).
     */
    public static function extraPermissions(): array
    {
        return [
            ['key' => 'products.archive', 'module' => 'products', 'action' => 'archive', 'label' => 'Archiver', 'group' => 'modules'],
            ['key' => 'products.duplicate', 'module' => 'products', 'action' => 'duplicate', 'label' => 'Dupliquer', 'group' => 'modules'],
            ['key' => 'products.view_purchase_price', 'module' => 'products', 'action' => 'view_purchase_price', 'label' => 'Voir prix d\'achat', 'group' => 'modules'],
            ['key' => 'products.manage_images', 'module' => 'products', 'action' => 'manage_images', 'label' => 'Gérer images', 'group' => 'modules'],
            ['key' => 'stock.move', 'module' => 'stock', 'action' => 'move', 'label' => 'Mouvements', 'group' => 'modules'],
            ['key' => 'stock.adjust', 'module' => 'stock', 'action' => 'adjust', 'label' => 'Ajuster', 'group' => 'modules'],
            ['key' => 'stock.inventory', 'module' => 'stock', 'action' => 'inventory', 'label' => 'Inventaires', 'group' => 'modules'],
            ['key' => 'stock.valuation', 'module' => 'stock', 'action' => 'valuation', 'label' => 'Valorisation', 'group' => 'modules'],
            ['key' => 'purchases.receive', 'module' => 'purchases', 'action' => 'receive', 'label' => 'Réceptionner', 'group' => 'modules'],
            ['key' => 'suppliers.stats', 'module' => 'suppliers', 'action' => 'stats', 'label' => 'Statistiques', 'group' => 'modules'],
            ['key' => 'customers.stats', 'module' => 'customers', 'action' => 'stats', 'label' => 'Statistiques', 'group' => 'modules'],
            ['key' => 'pos.sell', 'module' => 'pos', 'action' => 'sell', 'label' => 'Vendre', 'group' => 'modules'],
            ['key' => 'pos.open', 'module' => 'pos', 'action' => 'open', 'label' => 'Ouvrir caisse', 'group' => 'modules'],
            ['key' => 'pos.close', 'module' => 'pos', 'action' => 'close', 'label' => 'Clôturer caisse', 'group' => 'modules'],
            ['key' => 'pos.hold', 'module' => 'pos', 'action' => 'hold', 'label' => 'Suspendre ticket', 'group' => 'modules'],
            ['key' => 'pos.reprint', 'module' => 'pos', 'action' => 'reprint', 'label' => 'Réimprimer', 'group' => 'modules'],
            ['key' => 'pos.history', 'module' => 'pos', 'action' => 'history', 'label' => 'Historique', 'group' => 'modules'],
            ['key' => 'invoices.pdf', 'module' => 'invoices', 'action' => 'pdf', 'label' => 'PDF', 'group' => 'modules'],
            ['key' => 'invoices.send', 'module' => 'invoices', 'action' => 'send', 'label' => 'Envoyer', 'group' => 'modules'],
            ['key' => 'quotes.convert', 'module' => 'quotes', 'action' => 'convert', 'label' => 'Convertir', 'group' => 'modules'],
            ['key' => 'quotes.send', 'module' => 'quotes', 'action' => 'send', 'label' => 'Envoyer', 'group' => 'modules'],
            ['key' => 'sales.return', 'module' => 'sales', 'action' => 'return', 'label' => 'Retours', 'group' => 'modules'],
            ['key' => 'reports.financial', 'module' => 'reports', 'action' => 'financial', 'label' => 'Rapports financiers', 'group' => 'modules'],
            ['key' => 'reports.advanced', 'module' => 'reports', 'action' => 'advanced', 'label' => 'Statistiques avancées', 'group' => 'modules'],
            ['key' => 'users.reset', 'module' => 'users', 'action' => 'reset', 'label' => 'Réinit. mot de passe', 'group' => 'modules'],
            ['key' => 'users.invite', 'module' => 'users', 'action' => 'invite', 'label' => 'Inviter', 'group' => 'modules'],
            ['key' => 'stores.switch', 'module' => 'stores', 'action' => 'switch', 'label' => 'Changer de boutique', 'group' => 'modules'],
            ['key' => 'companies.switch', 'module' => 'companies', 'action' => 'switch', 'label' => 'Changer d\'entreprise', 'group' => 'modules'],
            ['key' => 'companies.archive', 'module' => 'companies', 'action' => 'archive', 'label' => 'Archiver', 'group' => 'modules'],
            ['key' => 'notifications.archive', 'module' => 'notifications', 'action' => 'archive', 'label' => 'Archiver', 'group' => 'modules'],
            ['key' => 'notifications.preferences', 'module' => 'notifications', 'action' => 'preferences', 'label' => 'Configurer les préférences', 'group' => 'modules'],
            ['key' => 'documents.download', 'module' => 'documents', 'action' => 'download', 'label' => 'Télécharger', 'group' => 'modules'],
            ['key' => 'documents.archive', 'module' => 'documents', 'action' => 'archive', 'label' => 'Archiver', 'group' => 'modules'],
            ['key' => 'documents.folders', 'module' => 'documents', 'action' => 'folders', 'label' => 'Gérer les dossiers', 'group' => 'modules'],
            ['key' => 'audit.critical', 'module' => 'audit', 'action' => 'critical', 'label' => 'Événements critiques', 'group' => 'modules'],
            ['key' => 'audit.purge', 'module' => 'audit', 'action' => 'purge', 'label' => 'Purger les journaux', 'group' => 'modules'],

            // Special / scope
            ['key' => 'scope.own_store', 'module' => 'scope', 'action' => 'own_store', 'label' => 'Voir uniquement sa boutique', 'group' => 'scope', 'description' => 'Limite la visibilité à la boutique assignée'],
            ['key' => 'scope.all_stores', 'module' => 'scope', 'action' => 'all_stores', 'label' => 'Voir toutes les boutiques', 'group' => 'scope'],
            ['key' => 'scope.own_sales', 'module' => 'scope', 'action' => 'own_sales', 'label' => 'Voir uniquement ses ventes', 'group' => 'scope'],
            ['key' => 'scope.own_customers', 'module' => 'scope', 'action' => 'own_customers', 'label' => 'Voir uniquement ses clients', 'group' => 'scope'],
            ['key' => 'scope.own_payments', 'module' => 'scope', 'action' => 'own_payments', 'label' => 'Voir uniquement ses paiements', 'group' => 'scope'],
            ['key' => 'scope.multi_company', 'module' => 'scope', 'action' => 'multi_company', 'label' => 'Accès multi-entreprises', 'group' => 'scope'],
        ];
    }

    public static function allDefinitions(): array
    {
        $defs = [];
        $sort = 0;

        foreach (self::moduleActions() as $module => $actions) {
            foreach ($actions as $action) {
                $defs[] = [
                    'key' => $module.'.'.$action,
                    'module' => $module,
                    'action' => $action,
                    'label' => self::ACTIONS[$action] ?? ucfirst($action),
                    'description' => (self::MODULES[$module] ?? $module).' — '.(self::ACTIONS[$action] ?? $action),
                    'group' => 'modules',
                    'sort_order' => $sort++,
                ];
            }
        }

        foreach (self::extraPermissions() as $extra) {
            $defs[] = [
                'key' => $extra['key'],
                'module' => $extra['module'],
                'action' => $extra['action'],
                'label' => $extra['label'],
                'description' => $extra['description'] ?? null,
                'group' => $extra['group'] ?? 'modules',
                'sort_order' => $sort++,
            ];
        }

        return $defs;
    }

    /**
     * Default role permission keys matching the legacy Workspace matrix + expansions.
     */
    public static function defaultRolePermissions(): array
    {
        $all = collect(self::allDefinitions())->pluck('key')->all();

        $owner = $all; // everything including scope
        $admin = array_values(array_filter($all, fn ($k) => ! in_array($k, ['scope.multi_company'], true) || true));
        // Admin gets all except is_super exclusives — same as owner for V1 company scope
        $admin = $all;

        return [
            'super_admin' => $all,
            'owner' => $owner,
            'admin' => $admin,
            'manager' => [
                'dashboard.view', 'dashboard.export', 'dashboard.print',
                'products.view', 'products.create', 'products.update', 'products.delete', 'products.export', 'products.import', 'products.print',
                'products.archive', 'products.duplicate', 'products.view_purchase_price', 'products.manage_images',
                'stock.view', 'stock.create', 'stock.update', 'stock.export', 'stock.print', 'stock.validate',
                'stock.move', 'stock.adjust', 'stock.inventory', 'stock.valuation',
                'purchases.view', 'purchases.create', 'purchases.update', 'purchases.cancel', 'purchases.receive', 'purchases.export', 'purchases.print', 'purchases.approve',
                'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete', 'suppliers.export', 'suppliers.print', 'suppliers.stats',
                'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'customers.export', 'customers.print', 'customers.stats',
                'pos.view', 'pos.create', 'pos.update', 'pos.print', 'pos.validate', 'pos.cancel',
                'pos.sell', 'pos.open', 'pos.close', 'pos.hold', 'pos.reprint', 'pos.history',
                'payments.view', 'payments.create', 'payments.update', 'payments.export', 'payments.print', 'payments.validate',
                'invoices.view', 'invoices.create', 'invoices.update', 'invoices.cancel', 'invoices.export', 'invoices.print', 'invoices.pdf', 'invoices.send', 'invoices.approve',
                'quotes.view', 'quotes.create', 'quotes.update', 'quotes.export', 'quotes.print', 'quotes.convert', 'quotes.send', 'quotes.approve',
                'sales.view', 'sales.create', 'sales.update', 'sales.cancel', 'sales.return', 'sales.export', 'sales.print', 'sales.validate',
                'reports.view', 'reports.export', 'reports.print', 'reports.advanced',
                'users.view', 'users.export', 'users.print',
                'roles.view',
                'settings.view',
                'stores.view', 'stores.update', 'stores.export', 'stores.print', 'stores.switch',
                'companies.view', 'companies.switch',
                'notifications.view', 'notifications.update', 'notifications.archive', 'notifications.preferences',
                'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.export', 'documents.download', 'documents.archive', 'documents.folders',
                'audit.view', 'audit.export', 'audit.print', 'audit.critical',
                'scope.all_stores',
            ],
            'accountant' => [
                'dashboard.view', 'dashboard.export',
                'products.view', 'products.export', 'products.view_purchase_price',
                'stock.view', 'stock.export', 'stock.valuation',
                'purchases.view', 'purchases.export', 'purchases.print',
                'suppliers.view', 'suppliers.export', 'suppliers.print', 'suppliers.stats',
                'customers.view', 'customers.export', 'customers.print', 'customers.stats',
                'pos.history',
                'payments.view', 'payments.create', 'payments.update', 'payments.export', 'payments.print', 'payments.validate',
                'invoices.view', 'invoices.create', 'invoices.update', 'invoices.cancel', 'invoices.export', 'invoices.print', 'invoices.pdf', 'invoices.send',
                'quotes.view', 'quotes.export', 'quotes.print',
                'sales.view', 'sales.export', 'sales.print',
                'reports.view', 'reports.export', 'reports.print', 'reports.financial',
                'stores.view', 'stores.switch',
                'companies.view', 'companies.switch',
                'notifications.view', 'notifications.update', 'notifications.archive', 'notifications.preferences',
                'documents.view', 'documents.create', 'documents.export', 'documents.download',
                'audit.view', 'audit.export', 'audit.print', 'audit.critical',
                'scope.all_stores',
            ],
            'sales' => [
                'dashboard.view',
                'products.view',
                'stock.view',
                'suppliers.view',
                'customers.view', 'customers.create', 'customers.update', 'customers.export', 'customers.print', 'customers.stats',
                'payments.view',
                'invoices.view', 'invoices.create', 'invoices.print', 'invoices.pdf', 'invoices.send',
                'quotes.view', 'quotes.create', 'quotes.update', 'quotes.export', 'quotes.print', 'quotes.convert', 'quotes.send',
                'sales.view', 'sales.create', 'sales.update', 'sales.print',
                'reports.view', 'reports.print',
                'stores.switch',
                'companies.switch',
                'notifications.view', 'notifications.update', 'notifications.preferences',
                'documents.view', 'documents.create', 'documents.download',
                'scope.own_store', 'scope.own_sales', 'scope.own_customers',
            ],
            'cashier' => [
                'dashboard.view',
                'products.view',
                'stock.view',
                'customers.view', 'customers.create',
                'pos.view', 'pos.create', 'pos.sell', 'pos.open', 'pos.close', 'pos.hold', 'pos.reprint', 'pos.history', 'pos.print',
                'payments.view', 'payments.create',
                'sales.view', 'sales.print',
                'stores.switch',
                'companies.switch',
                'notifications.view', 'notifications.update', 'notifications.preferences',
                'documents.view', 'documents.download',
                'scope.own_store', 'scope.own_sales', 'scope.own_payments',
            ],
            'storekeeper' => [
                'dashboard.view',
                'products.view', 'products.view_purchase_price',
                'stock.view', 'stock.create', 'stock.update', 'stock.export', 'stock.print', 'stock.validate',
                'stock.move', 'stock.adjust', 'stock.inventory', 'stock.valuation',
                'purchases.view', 'purchases.receive', 'purchases.export', 'purchases.print',
                'suppliers.view', 'suppliers.print',
                'stores.switch',
                'companies.switch',
                'notifications.view', 'notifications.update', 'notifications.preferences',
                'documents.view', 'documents.create', 'documents.download',
                'scope.own_store',
            ],
            'employee' => [
                'dashboard.view',
                'products.view',
                'stores.switch',
                'companies.switch',
                'notifications.view', 'notifications.update', 'notifications.preferences',
                'documents.view', 'documents.download',
                'scope.own_store',
            ],
        ];
    }

    public static function defaultRoles(): array
    {
        return [
            ['slug' => 'super_admin', 'name' => 'Super Administrateur', 'description' => 'Accès plateforme complet (interne GreenPOS).', 'color' => 'rose', 'is_system' => true, 'is_super' => true, 'is_default' => true],
            ['slug' => 'owner', 'name' => 'Propriétaire', 'description' => 'Décideur de l\'entreprise, tous les droits.', 'color' => 'emerald', 'is_system' => true, 'is_super' => false, 'is_default' => true],
            ['slug' => 'admin', 'name' => 'Administrateur', 'description' => 'Administration opérationnelle de l\'entreprise.', 'color' => 'teal', 'is_system' => true, 'is_super' => false, 'is_default' => true],
            ['slug' => 'manager', 'name' => 'Manager', 'description' => 'Pilotage opérationnel multi-modules.', 'color' => 'sky', 'is_system' => true, 'is_super' => false, 'is_default' => true],
            ['slug' => 'accountant', 'name' => 'Comptable', 'description' => 'Finance, facturation et rapports.', 'color' => 'amber', 'is_system' => true, 'is_super' => false, 'is_default' => true],
            ['slug' => 'sales', 'name' => 'Commercial', 'description' => 'Clients, devis et ventes.', 'color' => 'indigo', 'is_system' => true, 'is_super' => false, 'is_default' => true],
            ['slug' => 'cashier', 'name' => 'Caissier', 'description' => 'Point de vente et encaissement.', 'color' => 'violet', 'is_system' => true, 'is_super' => false, 'is_default' => true],
            ['slug' => 'storekeeper', 'name' => 'Magasinier', 'description' => 'Stock, inventaires et réceptions.', 'color' => 'orange', 'is_system' => true, 'is_super' => false, 'is_default' => true],
            ['slug' => 'employee', 'name' => 'Employé', 'description' => 'Accès de base limité.', 'color' => 'slate', 'is_system' => true, 'is_super' => false, 'is_default' => true],
        ];
    }

    /**
     * Map legacy ability aliases used by existing controllers to catalog keys.
     */
    public static function aliases(): array
    {
        return [
            // stock.create maps from move conceptually for matrix display; controllers use stock.move etc.
        ];
    }
}
