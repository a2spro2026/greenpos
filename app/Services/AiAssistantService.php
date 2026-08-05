<?php

namespace App\Services;

use App\Ai\AiProviderManager;
use App\Models\AiActionLog;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiPrompt;
use App\Models\AiProvider;
use App\Models\AiSuggestion;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\StockLevel;
use App\Support\Workspace;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiAssistantService
{
    public function __construct(
        private AiProviderManager $providers,
        private GlobalSearchService $search,
    ) {
    }

    public function ensureCatalog(): void
    {
        $this->providers->ensureDefaults();

        $prompts = [
            [
                'code' => 'commercial',
                'name' => 'Assistant Commercial',
                'persona' => 'commercial',
                'icon' => '💼',
                'system_prompt' => 'Tu es l’Assistant Commercial GreenPOS. Tu aides sur clients, devis, ventes, opportunités et relances. Réponds en français, clairement, avec des actions concrètes.',
                'capabilities' => ['customers', 'quotes', 'sales', 'search'],
                'sort_order' => 1,
            ],
            [
                'code' => 'comptable',
                'name' => 'Assistant Comptable',
                'persona' => 'comptable',
                'icon' => '📒',
                'system_prompt' => 'Tu es l’Assistant Comptable GreenPOS. Tu aides sur facturation, paiements, TVA, encours et trésorerie. Réponds en français avec prudence comptable.',
                'capabilities' => ['invoices', 'payments', 'reports'],
                'sort_order' => 2,
            ],
            [
                'code' => 'stock',
                'name' => 'Assistant Stock',
                'persona' => 'stock',
                'icon' => '📦',
                'system_prompt' => 'Tu es l’Assistant Stock GreenPOS. Tu aides sur niveaux, ruptures, valorisation, inventaires et fournisseurs. Réponds en français de façon opérationnelle.',
                'capabilities' => ['stock', 'products', 'suppliers', 'purchases'],
                'sort_order' => 3,
            ],
            [
                'code' => 'pos',
                'name' => 'Assistant POS',
                'persona' => 'pos',
                'icon' => '🧾',
                'system_prompt' => 'Tu es l’Assistant POS GreenPOS. Tu guides la caisse, les sessions, tickets et encaissements. Réponds en français, étapes courtes.',
                'capabilities' => ['pos', 'sales', 'products'],
                'sort_order' => 4,
            ],
            [
                'code' => 'direction',
                'name' => 'Assistant Direction',
                'persona' => 'direction',
                'icon' => '📊',
                'system_prompt' => 'Tu es l’Assistant Direction GreenPOS. Tu synthétises CA, tendances, risques et recommandations stratégiques. Réponds en français, synthétique et orienté décision.',
                'capabilities' => ['reports', 'analytics', 'overview'],
                'sort_order' => 5,
            ],
        ];

        foreach ($prompts as $p) {
            AiPrompt::query()->updateOrCreate(['code' => $p['code']], array_merge($p, ['is_active' => true]));
        }
    }

    public function resolveContext(?string $routeName = null, ?string $path = null): array
    {
        $route = $routeName ?: (request()->route()?->getName() ?? '');
        $path = $path ?: request()->path();

        $map = [
            'products' => ['module' => 'products', 'label' => 'Produits', 'persona' => 'stock', 'hints' => ['Créer un produit', 'Retrouver un SKU', 'Quels produits se vendent le mieux ?']],
            'stock' => ['module' => 'stock', 'label' => 'Stock', 'persona' => 'stock', 'hints' => ['Quels produits vont être en rupture ?', 'Voir les alertes stock', 'Valorisation du stock']],
            'customers' => ['module' => 'customers', 'label' => 'Clients', 'persona' => 'commercial', 'hints' => ['Retrouver un client', 'Quels clients achètent le plus ?', 'Créer un client']],
            'crm' => ['module' => 'crm', 'label' => 'CRM', 'persona' => 'commercial', 'hints' => ['Résumer un prospect', 'Quelles opportunités sont chaudes ?', 'Rédiger un email de relance', 'Estimer la probabilité de vente']],
            'suppliers' => ['module' => 'suppliers', 'label' => 'Fournisseurs', 'persona' => 'stock', 'hints' => ['Quel fournisseur livre le plus ?', 'Créer un fournisseur']],
            'pos' => ['module' => 'pos', 'label' => 'POS / Caisse', 'persona' => 'pos', 'hints' => ['Comment ouvrir une session ?', 'Retrouver un ticket', 'Bonnes pratiques caisse']],
            'invoices' => ['module' => 'invoices', 'label' => 'Facturation', 'persona' => 'comptable', 'hints' => ['Retrouver une facture', 'Créer une facture', 'Factures impayées']],
            'quotes' => ['module' => 'quotes', 'label' => 'Devis', 'persona' => 'commercial', 'hints' => ['Créer un devis', 'Suivre les devis']],
            'sales' => ['module' => 'sales', 'label' => 'Ventes', 'persona' => 'commercial', 'hints' => ['Retrouver une vente', 'Créer une commande', 'Meilleures ventes']],
            'purchases' => ['module' => 'purchases', 'label' => 'Achats', 'persona' => 'stock', 'hints' => ['Créer une commande d’achat', 'Suivre les réceptions']],
            'reports' => ['module' => 'reports', 'label' => 'Rapports', 'persona' => 'direction', 'hints' => ['Pourquoi mon chiffre baisse ?', 'Résumer le CA du mois', 'Top produits']],
            'payments' => ['module' => 'payments', 'label' => 'Paiements', 'persona' => 'comptable', 'hints' => ['Paiements du mois', 'Encaissements en retard']],
        ];

        foreach ($map as $key => $meta) {
            if (str_starts_with($route, $key) || str_contains($path, $key)) {
                return array_merge($meta, ['route' => $route, 'path' => $path]);
            }
        }

        if (str_contains($route, 'payment') || str_contains($path, 'payment')) {
            return array_merge($map['payments'], ['route' => $route, 'path' => $path]);
        }

        return [
            'module' => 'general',
            'label' => 'GreenPOS',
            'persona' => 'direction',
            'hints' => [
                'Quels sont mes meilleurs produits ?',
                'Retrouver une facture',
                'Quels produits vont être en rupture ?',
                'Créer un client',
            ],
            'route' => $route,
            'path' => $path,
        ];
    }

    public function chat(array $input): array
    {
        $this->ensureCatalog();

        $companyId = Workspace::company()?->id;
        $userId = auth()->id();
        $message = trim($input['message'] ?? '');
        $context = $this->resolveContext($input['context_route'] ?? null, $input['context_path'] ?? null);
        $persona = $input['persona'] ?? $context['persona'];

        $prompt = AiPrompt::query()->where('persona', $persona)->where('is_active', true)->first()
            ?? AiPrompt::query()->where('code', 'direction')->first();

        $conversation = null;
        if (! empty($input['conversation_id'])) {
            $conversation = AiConversation::query()
                ->where('id', $input['conversation_id'])
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('user_id', $userId)
                ->first();
        }

        if (! $conversation) {
            $conversation = AiConversation::query()->create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'ai_prompt_id' => $prompt?->id,
                'title' => Str::limit($message, 60) ?: 'Nouvelle conversation',
                'context_module' => $context['module'],
                'context_route' => $context['route'],
                'provider' => $this->providers->defaultCode(),
                'status' => 'active',
                'message_count' => 0,
                'last_message_at' => now(),
            ]);
        }

        AiMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
            'meta' => ['context' => $context],
        ]);

        $engine = $this->buildLocalReply($message, $context, $persona);

        // Optional cloud enrichment when provider configured
        $providerCode = $input['provider'] ?? $this->providers->defaultCode();
        $provider = $this->providers->driver($providerCode);
        if ($providerCode !== 'local' && $provider->isConfigured()) {
            $system = ($prompt?->system_prompt ?: 'Tu es GreenPOS AI.')."\nContexte page : {$context['label']} ({$context['module']}).\nDonnées métier GreenPOS :\n".$engine['context_block'];
            $llm = $provider->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $message],
            ]);
            if (! empty($llm['content'])) {
                $engine['content'] = $llm['content']."\n\n—\n_Sources GreenPOS :_ ".($engine['summary_line'] ?? 'analyse locale');
                $engine['provider'] = $llm['provider'];
                $engine['model'] = $llm['model'] ?? null;
            }
        } else {
            $engine['provider'] = 'local';
            $engine['model'] = 'greenpos-local-v1';
        }

        $actionLogs = [];
        foreach ($engine['actions'] ?? [] as $i => $action) {
            if (($action['requires_confirmation'] ?? false) === true) {
                $log = AiActionLog::query()->create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'ai_conversation_id' => $conversation->id,
                    'action_type' => $action['type'],
                    'status' => 'proposed',
                    'payload' => $action,
                ]);
                $engine['actions'][$i]['action_log_id'] = $log->id;
                $actionLogs[] = $log->id;
            }
        }

        $assistant = AiMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $engine['content'],
            'actions' => $engine['actions'] ?? [],
            'citations' => $engine['citations'] ?? [],
            'meta' => [
                'provider' => $engine['provider'] ?? 'local',
                'model' => $engine['model'] ?? null,
                'intent' => $engine['intent'] ?? 'general',
                'action_logs' => $actionLogs,
            ],
        ]);

        $conversation->update([
            'message_count' => $conversation->message_count + 2,
            'last_message_at' => now(),
            'provider' => $engine['provider'] ?? $conversation->provider,
            'context_module' => $context['module'],
            'ai_prompt_id' => $prompt?->id,
            'title' => $conversation->title ?: Str::limit($message, 60),
        ]);

        return [
            'conversation_id' => $conversation->id,
            'message' => [
                'id' => $assistant->id,
                'role' => 'assistant',
                'content' => $assistant->content,
                'actions' => $assistant->actions ?? [],
                'citations' => $assistant->citations ?? [],
                'meta' => $assistant->meta,
            ],
            'context' => $context,
            'persona' => $persona,
        ];
    }

    protected function buildLocalReply(string $message, array $context, string $persona): array
    {
        $q = mb_strtolower($message);
        $citations = [];
        $actions = [];
        $intent = 'help';

        // Automation proposals first (must not be swallowed by search keywords)
        if (preg_match('/cr[eé]er?\s+(un\s+)?produit/u', $q)) {
            return $this->proposeCreate('create_product', 'Créer un produit', $message, $context, [
                'name' => $this->extractAfter($message, ['produit', 'appelé', 'nommé']) ?: 'Nouveau produit',
            ]);
        }
        if (preg_match('/cr[eé]er?\s+(un\s+)?client/u', $q)) {
            return $this->proposeCreate('create_customer', 'Créer un client', $message, $context, [
                'name' => $this->extractAfter($message, ['client', 'appelé', 'nommé']) ?: 'Nouveau client',
            ]);
        }
        if (preg_match('/cr[eé]er?\s+(un\s+)?fournisseur/u', $q)) {
            return $this->proposeCreate('create_supplier', 'Créer un fournisseur', $message, $context, [
                'name' => $this->extractAfter($message, ['fournisseur', 'appelé', 'nommé']) ?: 'Nouveau fournisseur',
            ]);
        }
        if (preg_match('/cr[eé]er?\s+(une\s+)?facture/u', $q)) {
            return $this->proposeCreate('create_invoice', 'Créer une facture', $message, $context, []);
        }
        if (preg_match('/cr[eé]er?\s+(un\s+)?devis/u', $q)) {
            return $this->proposeCreate('create_quote', 'Créer un devis', $message, $context, []);
        }
        if (preg_match('/cr[eé]er?\s+(une\s+)?commande/u', $q)) {
            return $this->proposeCreate('create_order', 'Créer une commande', $message, $context, []);
        }

        // Search intents
        if (preg_match('/\b(retrouve|trouver|cherche|recherche|où est)\b/u', $q)
            || preg_match('/\b(facture|client|produit|vente|commande)\b.+\b(n[°o]|num|sku|code|#)/u', $q)
            || preg_match('/\b(retrouve|trouver|cherche)\s+(une?\s+)?(facture|client|produit|vente|commande)/u', $q)) {
            $intent = 'search';
            $term = trim(preg_replace('/.*(facture|client|produit|vente|commande|retrouve|trouver|cherche|recherche|où est)\s*/iu', '', $message));
            $term = $term !== '' ? $term : $message;
            $results = $this->search->search($term, 5);
            $citations = $results;
            if ($results === []) {
                $content = "Aucun résultat pour « {$term} » dans GreenPOS. Essayez un nom, un SKU, un n° de facture ou un client.";
            } else {
                $lines = collect($results)->take(8)->map(fn ($r) => "• **{$r['type_label']}** — [{$r['title']}]({$r['url']})".($r['subtitle'] ? " _{$r['subtitle']}_" : ''))->implode("\n");
                $content = "Voici ce que j’ai trouvé pour « {$term} » :\n\n{$lines}";
            }

            return [
                'content' => $this->withContextIntro($content, $context),
                'citations' => $citations,
                'actions' => $actions,
                'intent' => $intent,
                'context_block' => json_encode($results, JSON_UNESCAPED_UNICODE),
                'summary_line' => count($results).' résultats',
            ];
        }

        // Analytics
        if (preg_match('/meilleur(s)? produits?|top produits?|se vendent/u', $q)) {
            $intent = 'analytics_top_products';
            $rows = $this->topProducts();
            $lines = $rows === [] ? 'Pas encore assez de données de ventes.' : collect($rows)->map(fn ($r, $i) => ($i + 1).". **{$r['name']}** — {$r['qty']} u. · ".number_format($r['revenue'], 2, ',', ' ').' MAD')->implode("\n");
            $content = "### Meilleurs produits\n\n{$lines}";

            return $this->pack($content, $context, $intent, $rows);
        }

        if (preg_match('/chiffre.*(baisse|baisse|diminue)|pourquoi.*(ca|chiffre)/u', $q)) {
            $intent = 'analytics_revenue_trend';
            $trend = $this->revenueTrend();
            $content = "### Tendance du chiffre d’affaires\n\n"
                ."• Mois courant : **".number_format($trend['current'], 2, ',', ' ')." MAD**\n"
                ."• Mois précédent : **".number_format($trend['previous'], 2, ',', ' ')." MAD**\n"
                ."• Variation : **{$trend['delta_pct']}%**\n\n"
                .$trend['explanation'];

            return $this->pack($content, $context, $intent, $trend);
        }

        if (preg_match('/fournisseur.*(livre|livraison)|quel fournisseur/u', $q)) {
            $intent = 'analytics_suppliers';
            $rows = $this->topSuppliers();
            $lines = $rows === [] ? 'Pas de commandes d’achat analysables.' : collect($rows)->map(fn ($r, $i) => ($i + 1).". **{$r['name']}** — {$r['orders']} commandes · ".number_format($r['total'], 2, ',', ' ').' MAD')->implode("\n");
            $content = "### Fournisseurs les plus actifs\n\n{$lines}";

            return $this->pack($content, $context, $intent, $rows);
        }

        if (preg_match('/clients?.*(achètent|achetent|meilleur|top)/u', $q)) {
            $intent = 'analytics_customers';
            $rows = $this->topCustomers();
            $lines = $rows === [] ? 'Pas encore de clients avec ventes.' : collect($rows)->map(fn ($r, $i) => ($i + 1).". **{$r['name']}** — ".number_format($r['total'], 2, ',', ' ').' MAD')->implode("\n");
            $content = "### Clients qui achètent le plus\n\n{$lines}";

            return $this->pack($content, $context, $intent, $rows);
        }

        if (preg_match('/rupture|stock bas|alerte stock|manqu/u', $q)) {
            $intent = 'analytics_stockout';
            $rows = $this->lowStock();
            $lines = $rows === [] ? 'Aucune rupture imminente détectée.' : collect($rows)->map(fn ($r) => "• **{$r['name']}** — stock {$r['qty']} (seuil {$r['min']})")->implode("\n");
            $content = "### Produits à risque de rupture\n\n{$lines}";
            $actions[] = [
                'type' => 'open_url',
                'label' => 'Voir les alertes stock',
                'url' => route('stock.alerts'),
                'requires_confirmation' => false,
            ];

            return $this->pack($content, $context, $intent, $rows, $actions);
        }

        // Module help
        $intent = 'help';
        $moduleHelp = $this->moduleHelp($context);
        $content = "### Contexte : {$context['label']}\n\n{$moduleHelp}\n\nVous pouvez me demander de **retrouver** un élément, d’**analyser** vos ventes/stock, ou de **proposer** une création (validée par vous).";

        return $this->pack($content, $context, $intent, ['hints' => $context['hints'] ?? []], [
            ['type' => 'hint', 'label' => $context['hints'][0] ?? 'Aide', 'requires_confirmation' => false],
        ]);
    }

    protected function proposeCreate(string $type, string $label, string $message, array $context, array $payload): array
    {
        $urls = [
            'create_product' => route('products.create'),
            'create_customer' => route('customers.create'),
            'create_supplier' => route('suppliers.create'),
            'create_invoice' => route('invoices.create'),
            'create_quote' => route('quotes.create'),
            'create_order' => route('sales.create'),
        ];

        $action = [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'label' => $label,
            'url' => $urls[$type] ?? null,
            'payload' => $payload,
            'requires_confirmation' => true,
        ];

        $content = "Je peux vous aider à **{$label}**.\n\n"
            ."Détails proposés :\n".collect($payload)->map(fn ($v, $k) => "• {$k} : {$v}")->implode("\n")."\n\n"
            ."⚠️ Aucune création ne sera effectuée sans votre validation.";

        return $this->pack($content, $context, 'automation', $payload, [$action]);
    }

    protected function pack(string $content, array $context, string $intent, mixed $data = null, array $actions = []): array
    {
        return [
            'content' => $this->withContextIntro($content, $context),
            'citations' => [],
            'actions' => $actions,
            'intent' => $intent,
            'context_block' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE),
            'summary_line' => $intent,
        ];
    }

    protected function withContextIntro(string $content, array $context): string
    {
        return "_Page : {$context['label']}_\n\n".$content;
    }

    protected function moduleHelp(array $context): string
    {
        return match ($context['module']) {
            'products' => 'Sur **Produits**, vous gérez le catalogue, SKU, prix et variantes. Je peux rechercher un article ou proposer une création.',
            'stock' => 'Sur **Stock**, surveillez les niveaux, mouvements et alertes. Demandez-moi les ruptures imminentes.',
            'customers' => 'Sur **Clients**, retrouvez une fiche, analysez les meilleurs acheteurs ou proposez une création.',
            'crm' => 'Sur le **CRM**, gérez leads, pipeline et activités. Je peux résumer un prospect, estimer une opportunité ou rédiger un email.',
            'pos' => 'Sur le **POS**, je guide l’ouverture de session, tickets et encaissements.',
            'invoices' => 'Sur **Facturation**, je retrouve une facture, résume les impayés ou propose d’en créer une.',
            'sales' => 'Sur **Ventes**, je retrouve une commande et explique les performances commerciales.',
            'reports' => 'Sur **Rapports**, je synthétise CA, tendances et top produits.',
            default => 'Je connais tous les modules GreenPOS : produits, stock, clients, POS, facturation, ventes, achats et rapports.',
        };
    }

    protected function extractAfter(string $message, array $keywords): ?string
    {
        foreach ($keywords as $kw) {
            if (preg_match('/'.$kw.'\s+(.+)$/iu', $message, $m)) {
                return trim($m[1], " \t\"'«»");
            }
        }

        return null;
    }

    protected function topProducts(int $limit = 5): array
    {
        $companyId = Workspace::company()?->id;
        if (! $companyId || ! Schema::hasTable('sale_lines')) {
            return [];
        }

        return SaleLine::query()
            ->selectRaw('products.name as name, SUM(sale_lines.quantity) as qty, SUM(sale_lines.line_total) as revenue')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'qty' => (float) $r->qty, 'revenue' => (float) $r->revenue])
            ->all();
    }

    protected function revenueTrend(): array
    {
        $companyId = Workspace::company()?->id;
        $current = 0.0;
        $previous = 0.0;

        if ($companyId && Schema::hasTable('sales')) {
            $current = (float) Sale::query()
                ->where('company_id', $companyId)
                ->where('status', '!=', 'cancelled')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('total_ttc');
            $previous = (float) Sale::query()
                ->where('company_id', $companyId)
                ->where('status', '!=', 'cancelled')
                ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
                ->sum('total_ttc');
        }

        if ($companyId && Schema::hasTable('invoices') && $current == 0.0) {
            $current = (float) Invoice::query()->where('company_id', $companyId)->where('status', '!=', 'void')->where('invoiced_at', '>=', now()->startOfMonth())->sum('total_ttc');
            $previous = (float) Invoice::query()->where('company_id', $companyId)->where('status', '!=', 'void')->whereBetween('invoiced_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->sum('total_ttc');
        }

        $delta = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : ($current > 0 ? 100.0 : 0.0);
        $explanation = $delta < 0
            ? 'Le CA est en baisse vs mois précédent. Vérifiez le volume de tickets POS, les paniers moyens et les promotions.'
            : ($delta > 0
                ? 'Le CA progresse. Capitalisez sur les top produits et relancez les clients dormants.'
                : 'Volume stable. Surveillez le stock et les devis en attente pour accélérer.');

        return ['current' => $current, 'previous' => $previous, 'delta_pct' => $delta, 'explanation' => $explanation];
    }

    protected function topSuppliers(int $limit = 5): array
    {
        $companyId = Workspace::company()?->id;
        if (! $companyId || ! Schema::hasTable('purchase_orders')) {
            return [];
        }

        return PurchaseOrder::query()
            ->selectRaw('suppliers.name as name, COUNT(purchase_orders.id) as orders, SUM(purchase_orders.total_ttc) as total')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->where('purchase_orders.company_id', $companyId)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('orders')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'orders' => (int) $r->orders, 'total' => (float) $r->total])
            ->all();
    }

    protected function topCustomers(int $limit = 5): array
    {
        $companyId = Workspace::company()?->id;
        if (! $companyId || ! Schema::hasTable('sales')) {
            return [];
        }

        return Sale::query()
            ->selectRaw('customers.name as name, SUM(sales.total_ttc) as total')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.status', '!=', 'cancelled')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'total' => (float) $r->total])
            ->all();
    }

    protected function lowStock(int $limit = 8): array
    {
        $companyId = Workspace::company()?->id;
        if (! $companyId || ! Schema::hasTable('stock_levels')) {
            return [];
        }

        return StockLevel::query()
            ->with('product:id,name')
            ->whereHas('product', fn ($q) => $q->where('company_id', $companyId))
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->orderBy('quantity')
            ->limit($limit)
            ->get()
            ->map(fn (StockLevel $l) => [
                'name' => $l->product?->name ?? 'Produit',
                'qty' => (float) $l->quantity,
                'min' => (float) $l->min_quantity,
            ])
            ->all();
    }

    public function confirmAction(int $actionLogId): array
    {
        $log = AiActionLog::query()
            ->where('id', $actionLogId)
            ->where('user_id', auth()->id())
            ->where('status', 'proposed')
            ->firstOrFail();

        $payload = $log->payload ?? [];
        $type = $log->action_type;
        $url = $payload['url'] ?? null;

        // We never auto-create records without going through existing forms —
        // confirmation opens the create screen with suggested query params.
        $log->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'executed_at' => now(),
            'result' => ['redirect' => $url, 'note' => 'Redirection vers le formulaire GreenPOS pour validation finale'],
        ]);

        return [
            'ok' => true,
            'redirect' => $url,
            'message' => 'Action confirmée. Complétez le formulaire GreenPOS pour finaliser.',
        ];
    }

    public function cancelAction(int $actionLogId): void
    {
        AiActionLog::query()
            ->where('id', $actionLogId)
            ->where('user_id', auth()->id())
            ->where('status', 'proposed')
            ->update(['status' => 'cancelled']);
    }

    public function dashboardStats(): array
    {
        $this->ensureCatalog();
        $this->seedSuggestionsIfEmpty();

        $companyId = Workspace::company()?->id;
        $userId = auth()->id();

        $conversations = AiConversation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('user_id', $userId)
            ->latest('last_message_at')
            ->limit(12)
            ->with('prompt')
            ->get();

        $messageCount = AiMessage::query()
            ->whereHas('conversation', fn ($q) => $q->where('user_id', $userId)->when($companyId, fn ($qq) => $qq->where('company_id', $companyId)))
            ->count();

        $suggestions = AiSuggestion::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_dismissed', false)
            ->orderByDesc('priority')
            ->limit(10)
            ->get();

        $actions = AiActionLog::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(10)
            ->get();

        return [
            'conversations' => $conversations,
            'conversation_count' => $conversations->count() < 12
                ? AiConversation::query()->where('user_id', $userId)->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count()
                : AiConversation::query()->where('user_id', $userId)->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count(),
            'message_count' => $messageCount,
            'suggestion_count' => $suggestions->count(),
            'suggestions' => $suggestions,
            'actions' => $actions,
            'prompts' => AiPrompt::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'providers' => AiProvider::query()->orderBy('code')->get(),
            'insights' => [
                'top_products' => $this->topProducts(3),
                'low_stock' => $this->lowStock(3),
                'revenue' => $this->revenueTrend(),
            ],
        ];
    }

    public function seedSuggestionsIfEmpty(): void
    {
        $companyId = Workspace::company()?->id;
        if (! $companyId) {
            return;
        }

        if (AiSuggestion::query()->where('company_id', $companyId)->exists()) {
            // Refresh dynamic ones lightly
            return;
        }

        $low = $this->lowStock(1);
        if ($low !== []) {
            AiSuggestion::query()->create([
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'category' => 'alert',
                'title' => 'Risque de rupture détecté',
                'body' => $low[0]['name'].' est sous le seuil minimal.',
                'module' => 'stock',
                'action_url' => route('stock.alerts'),
                'action_label' => 'Voir alertes',
                'priority' => 90,
            ]);
        }

        AiSuggestion::query()->create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'category' => 'tip',
            'title' => 'Posez une question métier',
            'body' => 'Ex. « Quels sont mes meilleurs produits ? » ou « Retrouver une facture ».',
            'module' => 'general',
            'priority' => 40,
        ]);

        AiSuggestion::query()->create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'category' => 'recommendation',
            'title' => 'Analyser le CA du mois',
            'body' => 'Demandez à l’Assistant Direction pourquoi le chiffre évolue.',
            'module' => 'reports',
            'action_url' => route('reports.dashboard'),
            'action_label' => 'Ouvrir rapports',
            'priority' => 60,
        ]);
    }
}
