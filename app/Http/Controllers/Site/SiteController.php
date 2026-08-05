<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\CompanyRegistrationService;
use App\Services\SaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function __construct(
        private CompanyRegistrationService $registrations,
        private SaasService $saas,
    ) {
    }

    public function home(): View
    {
        return view('site.home', [
            'plans' => $this->registrations->publicPlans(),
            'modules' => $this->modules(),
            'sectors' => $this->sectorsList(),
            'reasons' => $this->reasons(),
            'testimonials' => $this->testimonials(),
            'faqs' => $this->faqs(),
        ]);
    }

    public function features(): View
    {
        return view('site.features', [
            'modules' => $this->modules(),
        ]);
    }

    public function pricing(): View
    {
        $this->saas->ensurePlans();

        return view('site.pricing', [
            'plans' => $this->registrations->publicPlans(),
        ]);
    }

    public function sectors(): View
    {
        return view('site.sectors', [
            'sectors' => $this->sectorsList(),
            'groups' => $this->sectorGroups(),
        ]);
    }

    public function about(): View
    {
        return view('site.about');
    }

    public function contact(Request $request): View
    {
        return view('site.contact', [
            'demo' => $request->boolean('demo') || $request->query('sujet') === 'demo',
        ]);
    }

    public function contactSubmit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Log::info('site.contact', $data);

        try {
            $to = config('mail.from.address', 'hello@greenpos.com');
            Mail::raw(
                "Contact GreenPOS\n\n"
                ."Nom : {$data['name']}\n"
                ."Email : {$data['email']}\n"
                .'Société : '.($data['company'] ?? '—')."\n"
                .'Téléphone : '.($data['phone'] ?? '—')."\n"
                .'Sujet : '.($data['subject'] ?? '—')."\n\n"
                .$data['message'],
                function ($message) use ($data, $to) {
                    $message->to($to)
                        ->replyTo($data['email'], $data['name'])
                        ->subject('[GreenPOS] '.($data['subject'] ?: 'Nouveau message contact'));
                }
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Merci. Votre message a bien été envoyé. Notre équipe vous répondra rapidement.');
    }

    /** @return list<array{key:string,title:string,desc:string}> */
    private function modules(): array
    {
        return [
            ['key' => 'pos', 'title' => 'POS', 'desc' => 'Caisse tactile rapide, tickets, sessions de caisse et encaissements multi-moyens.'],
            ['key' => 'products', 'title' => 'Produits', 'desc' => 'Catalogue, variantes, codes-barres, prix et images centralisés.'],
            ['key' => 'stock', 'title' => 'Stock', 'desc' => 'Niveaux temps réel, alertes, inventaires et valorisation.'],
            ['key' => 'purchases', 'title' => 'Achats', 'desc' => 'Demandes, commandes fournisseurs et réceptions suivies.'],
            ['key' => 'suppliers', 'title' => 'Fournisseurs', 'desc' => 'Fiches, historique d’achats et documents fournisseurs.'],
            ['key' => 'customers', 'title' => 'Clients', 'desc' => 'Base clients, fidélité et historique d’achats unifié.'],
            ['key' => 'crm', 'title' => 'CRM', 'desc' => 'Leads, opportunités, activités et suivi commercial.'],
            ['key' => 'invoicing', 'title' => 'Facturation', 'desc' => 'Devis, factures, paiements et relances professionnels.'],
            ['key' => 'accounting', 'title' => 'Comptabilité', 'desc' => 'Exports, rapprochements et vision financière claire.'],
            ['key' => 'reports', 'title' => 'Rapports', 'desc' => 'Ventes, marges, stock et performance par boutique.'],
            ['key' => 'hr', 'title' => 'RH', 'desc' => 'Équipes, rôles, permissions et accès sécurisés.'],
            ['key' => 'multi_store', 'title' => 'Multi-boutiques', 'desc' => 'Pilotez plusieurs points de vente depuis un seul espace.'],
            ['key' => 'users', 'title' => 'Multi-utilisateurs', 'desc' => 'Droits fins par rôle pour chaque collaborateur.'],
            ['key' => 'dashboard', 'title' => 'Tableau de bord', 'desc' => 'KPIs du jour, alertes et activité en un coup d’œil.'],
            ['key' => 'notifications', 'title' => 'Notifications', 'desc' => 'Alertes stock, ventes et événements métier en temps réel.'],
        ];
    }

    /** @return list<string> */
    private function sectorsList(): array
    {
        return [
            'Épicerie', 'Supermarché', 'Restaurant', 'Snack', 'Café',
            'Pharmacie', 'Parapharmacie', 'Quincaillerie', 'Matériaux de construction',
            'Librairie', 'Papeterie', 'Garage', 'Salon de coiffure', 'Institut de beauté',
            'Clinique', 'Cabinet médical', 'Hôtel', 'Agence immobilière',
            'Boutique de mode', 'Électroménager', 'Opticien', 'Fleuriste',
            'Boulangerie', 'Station-service', 'Centre sportif',
        ];
    }

    /** @return array<string, list<string>> */
    private function sectorGroups(): array
    {
        return [
            'Commerce & retail' => ['Épicerie', 'Supermarché', 'Boutique de mode', 'Librairie', 'Papeterie', 'Électroménager', 'Fleuriste', 'Opticien'],
            'Restauration' => ['Restaurant', 'Snack', 'Café', 'Boulangerie'],
            'Santé & bien-être' => ['Pharmacie', 'Parapharmacie', 'Clinique', 'Cabinet médical', 'Salon de coiffure', 'Institut de beauté', 'Centre sportif'],
            'Services & BTP' => ['Quincaillerie', 'Matériaux de construction', 'Garage', 'Station-service', 'Agence immobilière', 'Hôtel'],
        ];
    }

    /** @return list<array{title:string,desc:string}> */
    private function reasons(): array
    {
        return [
            ['title' => 'Tout-en-un', 'desc' => 'POS, stock, ventes, CRM et facturation dans une seule plateforme SaaS.'],
            ['title' => 'Prêt pour la croissance', 'desc' => 'Ajoutez boutiques, utilisateurs et modules sans migrer vos données.'],
            ['title' => 'Conçu pour le terrain', 'desc' => 'Interface rapide en caisse, fiable hors pics d’affluence, claire au bureau.'],
            ['title' => 'Sécurité multi-entreprise', 'desc' => 'Isolation des données, rôles précis et console plateforme dédiée.'],
        ];
    }

    /** @return list<array{quote:string,name:string,role:string}> */
    private function testimonials(): array
    {
        return [
            ['quote' => 'Nous avons unifié caisse, stock et facturation. Les équipes gagnent du temps dès l’ouverture.', 'name' => 'Sara El Amrani', 'role' => 'Directrice, réseau d’épiceries'],
            ['quote' => 'Le multi-boutiques est limpide. On pilote trois restaurants avec les mêmes indicateurs.', 'name' => 'Youssef Benali', 'role' => 'Gérant restauration'],
            ['quote' => 'GreenPOS nous a permis de professionnaliser les achats et les inventaires sans complexité inutile.', 'name' => 'Nadia Cherkaoui', 'role' => 'Responsable opérations retail'],
        ];
    }

    /** @return list<array{q:string,a:string}> */
    private function faqs(): array
    {
        return [
            ['q' => 'GreenPOS convient-il à mon activité ?', 'a' => 'Oui. La plateforme couvre le commerce, la restauration, la santé, les services et bien d’autres secteurs, avec des modules activables selon vos besoins.'],
            ['q' => 'Puis-je gérer plusieurs boutiques ?', 'a' => 'Oui. Selon votre plan, vous ajoutez des points de vente, isolez les stocks et consolidez les rapports.'],
            ['q' => 'Comment démarrer ?', 'a' => 'Créez votre demande d’entreprise en ligne. Après validation, votre espace est activé avec boutique, administrateur et modules du plan choisi.'],
            ['q' => 'Mes données sont-elles isolées ?', 'a' => 'Chaque entreprise dispose d’un espace isolé. Les droits utilisateurs et la console Super Admin renforcent la gouvernance.'],
            ['q' => 'Puis-je changer de plan plus tard ?', 'a' => 'Oui. Vous évoluez de Starter à Business ou Enterprise sans perdre votre historique.'],
        ];
    }
}
