<?php

namespace App\Support;

/**
 * Structure exacte du menu latéral (IA métier).
 * Chaque item référence un module (activation) et une route existante.
 */
class SidebarStructure
{
    /**
     * @return list<array{label: string, items: list<array<string, mixed>>}>
     */
    public static function groups(): array
    {
        return [
            [
                'label' => 'Point de vente',
                'items' => [
                    self::item('Vente rapide', 'pos.terminal', 'pos', 'pos.view', 'pos', 'pos.terminal'),
                    self::item('Tickets enregistrés', 'pos.tickets.index', 'pos', 'pos.view', 'pos', 'pos.tickets.*'),
                    self::item('Commandes', 'sales.index', 'sales', 'sales.view', 'sales', 'sales.index'),
                    self::item('Cuisine (écran)', 'pos.kds', 'pos', 'pos.view', 'pos', 'pos.kds*'),
                    self::item('Ouverture / Fermeture de caisse', 'pos.sessions.index', 'pos', 'pos.view', 'pos', 'pos.sessions.*'),
                    self::item('Journal de caisse', 'pos.journal', 'pos', 'pos.view', 'audit', 'pos.journal*'),
                    self::item('À encaisser (chèques, effets)', 'pos.collections', 'pos', 'pos.view', 'payments', 'pos.collections'),
                ],
            ],
            [
                'label' => 'Pilotage',
                'items' => [
                    self::item('Tableau de bord', 'home', 'dashboard', null, 'dashboard', 'home'),
                    self::item('Rapports', 'reports.dashboard', 'reports', 'reports.view', 'reports', 'reports.*'),
                    self::item('Documents', 'documents.dashboard', 'documents', 'documents.view', 'documents', 'documents.*'),
                    self::item('Assistant IA', 'ai.dashboard', 'ai', null, 'ai', 'ai.*'),
                ],
            ],
            [
                'label' => 'Catalogue',
                'items' => [
                    self::item('Produits / Articles', 'products.index', 'products', 'products.view', 'products', 'products.*'),
                    self::item('Catégories', 'products.index', 'products', 'products.view', 'categories', 'products.*', query: ['tab' => 'categories']),
                    self::item('Options & variantes', 'products.index', 'products', 'products.view', 'products', 'products.*', query: ['tab' => 'variants']),
                ],
            ],
            [
                'label' => 'Stock',
                'items' => [
                    self::item('Entrées', 'stock.movements.create', 'stock', 'stock.move', 'stock', 'stock.movements.*', query: ['type' => 'in']),
                    self::item('Sorties', 'stock.movements.create', 'stock', 'stock.move', 'stock', 'stock.movements.*', query: ['type' => 'out']),
                    self::item('Ajustements', 'stock.movements.create', 'stock', 'stock.adjust', 'stock', 'stock.movements.*', query: ['type' => 'adjustment']),
                    self::item('Transferts', 'stock.movements.index', 'stock', 'stock.view', 'stock', 'stock.movements.*', query: ['type' => 'transfer']),
                    self::item('Inventaires', 'stock.inventories.index', 'stock', 'stock.inventory', 'inventory', 'stock.inventories.*'),
                    self::item('Mouvements de stock', 'stock.movements.index', 'stock', 'stock.view', 'stock', 'stock.movements.*'),
                ],
            ],
            [
                'label' => 'Ventes & Clients',
                'items' => [
                    self::item('Devis', 'quotes.dashboard', 'quotes', 'quotes.view', 'quotes', 'quotes.*'),
                    self::item('Commandes clients', 'sales.index', 'sales', 'sales.view', 'sales', 'sales.*'),
                    self::item('Bons de livraison', 'delivery.index', 'delivery', 'sales.view', 'sales', 'delivery.*'),
                    self::item('Factures clients', 'invoices.dashboard', 'invoices', 'invoices.view', 'invoices', 'invoices.*'),
                    self::item('Tableau des ventes', 'sales.dashboard', 'sales', 'sales.view', 'sales', 'sales.*'),
                    self::item('Avoir', 'invoices.index', 'invoices', 'invoices.view', 'invoices', 'invoices.*', query: ['type' => 'credit_note']),
                    self::item('Clients & Fidélité', 'customers.dashboard', 'customers', 'customers.view', 'customers', 'customers.*'),
                ],
            ],
            [
                'label' => 'Achats & Fournisseurs',
                'items' => [
                    self::item('Bons de commande', 'purchases.orders.index', 'purchases', 'purchases.view', 'purchases', 'purchases.orders.*'),
                    self::item('Réception de marchandises', 'purchases.receipts.index', 'purchases', 'purchases.view', 'purchases', 'purchases.receipts.*'),
                    self::item('Historique achats & factures fournisseurs', 'purchases.history', 'purchases', 'purchases.view', 'purchases', 'purchases.history'),
                    self::item('Retour fournisseur', 'purchases.orders.index', 'purchases', 'purchases.view', 'purchases', 'purchases.orders.*', query: ['status' => 'return']),
                    self::item('Fournisseurs', 'suppliers.dashboard', 'suppliers', 'suppliers.view', 'suppliers', 'suppliers.*'),
                ],
            ],
            [
                'label' => 'Production',
                'items' => [
                    self::item('Productions', 'ops.production.index', 'production', 'stock.view', 'inventory', 'ops.production.*'),
                    self::item('Fiches techniques / Recettes', 'ops.production.bom', 'production', 'stock.view', 'inventory', 'ops.production.bom*'),
                    self::item('Matières premières', 'products.index', 'products', 'products.view', 'products', 'products.*', query: ['kind' => 'raw']),
                    self::item('Consommations et pertes', 'ops.losses.index', 'losses', 'stock.adjust', 'stock', 'ops.losses.*'),
                    self::item('Coûts de production', 'ops.production.index', 'production', 'stock.view', 'reports', 'ops.production.*', query: ['view' => 'costs']),
                ],
            ],
            [
                'label' => 'Finance',
                'items' => [
                    self::item('Dépenses', 'accounting.expenses.index', 'accounting', 'accounting.view', 'payments', 'accounting.expenses.*'),
                    self::item('Encaissements', 'accounting.collections.index', 'accounting', 'accounting.view', 'payments', 'accounting.collections.*'),
                    self::item('Décaissements', 'accounting.expenses.index', 'accounting', 'accounting.view', 'payments', 'accounting.expenses.*', query: ['flow' => 'out']),
                    self::item('Créances (chèques, effets, crédits)', 'pos.collections', 'pos', 'pos.view', 'invoices', 'pos.collections'),
                    self::item('Commissions', 'hr.payroll.index', 'hr', 'hr.view', 'payments', 'hr.payroll.*'),
                    self::item('Trésorerie (caisse & banque)', 'accounting.cash.index', 'accounting', 'accounting.view', 'accounting', 'accounting.cash.*'),
                    self::item('Comptabilité', 'accounting.home', 'accounting', 'accounting.view', 'accounting', 'accounting.*'),
                ],
            ],
            [
                'label' => 'Organisation & CRM',
                'items' => [
                    self::item('Calendrier / Agenda', 'crm.calendar', 'crm', null, 'crm', 'crm.calendar'),
                    self::item('Rendez-vous', 'crm.activities.index', 'crm', null, 'crm', 'crm.activities.*', query: ['type' => 'appointment']),
                    self::item('Tâches', 'crm.activities.index', 'crm', null, 'crm', 'crm.activities.*', query: ['type' => 'task']),
                    self::item('Incidents', 'crm.incidents.index', 'crm', null, 'crm', 'crm.incidents.*'),
                    self::item('Notifications', 'notifications.dashboard', 'notifications', 'notifications.view', 'notifications', 'notifications.*'),
                ],
            ],
            [
                'label' => 'Équipe',
                'items' => [
                    self::item('Collaborateurs', 'hr.employees.index', 'hr', 'hr.view', 'users', 'hr.employees.*'),
                    self::item('Présences / Absences', 'hr.attendance.index', 'hr', 'hr.view', 'users', 'hr.attendance.*'),
                    self::item('Paie', 'hr.payroll.index', 'hr', 'hr.view', 'payments', 'hr.payroll.*'),
                    self::item('Livreurs & Livraisons', 'delivery.index', 'delivery', 'sales.view', 'sales', 'delivery.*'),
                    self::item('Utilisateurs', 'users.dashboard', 'users', 'users.view', 'users', 'users.*'),
                    self::item('Rôles & autorisations', 'roles.dashboard', 'roles', 'roles.view', 'roles', 'roles.*'),
                ],
            ],
            [
                'label' => 'Paramètres',
                'items' => [
                    self::item('Informations établissement', 'settings.company', 'settings', 'settings.view', 'settings', 'settings.company*'),
                    self::item('Magasin / boutique', 'settings.stores', 'settings', 'settings.view', 'stores', 'settings.stores*'),
                    self::item('Caisse & matériel', 'settings.section', 'settings', 'settings.view', 'pos', 'settings.section', params: ['section' => 'pos']),
                    self::item('Apparence', 'branding.index', 'branding', 'settings.view', 'branding', 'branding.*'),
                    self::item('Fiscalité', 'settings.section', 'settings', 'settings.view', 'settings', 'settings.section', params: ['section' => 'tax']),
                    self::item('Devise', 'settings.section', 'settings', 'settings.view', 'settings', 'settings.section', params: ['section' => 'currencies']),
                    self::item('Langue', 'settings.section', 'settings', 'settings.view', 'settings', 'settings.section', params: ['section' => 'languages']),
                    self::item('Listes personnalisées', 'settings.section', 'settings', 'settings.view', 'settings', 'settings.section', params: ['section' => 'numbering']),
                    self::item('Sauvegarde & restauration', 'system.backups', 'system', 'settings.view', 'system', 'system.backups*'),
                    self::item('Modules activés', 'modules.index', 'modules', 'settings.view', 'modules', 'modules.*'),
                ],
            ],
            [
                'label' => 'Assistance',
                'items' => [
                    self::item('Support', 'site.contact', 'dashboard', null, 'notifications', 'site.contact'),
                    self::item('État / Santé du système', 'system.dashboard', 'system', 'settings.view', 'system', 'system.*'),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected static function item(
        string $label,
        string $route,
        string $module,
        ?string $permission,
        string $icon,
        string $routeIs,
        array $params = [],
        array $query = [],
    ): array {
        return [
            'label' => $label,
            'route' => $route,
            'params' => $params,
            'query' => $query,
            'module' => $module,
            'permission' => $permission,
            'icon' => $icon,
            'route_is' => $routeIs,
        ];
    }
}
