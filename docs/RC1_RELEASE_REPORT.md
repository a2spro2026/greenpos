# GreenPOS V1 — Release Candidate RC1

**Date :** 5 août 2026  
**Statut :** Release Candidate (RC1) — prêt pour démonstration et commercialisation contrôlée  
**Stack :** Laravel 12 · Blade · Vite 7 · Tailwind CSS 4 · MySQL/SQLite

---

## Modules terminés (V1)

| Domaine | Modules |
|---------|---------|
| Accès | Authentification (démo locale), Onboarding, Dashboard |
| Catalogue | Produits, Stock |
| Achats | Achats, Fournisseurs |
| Ventes | POS, Ventes, Clients, Devis, Facturation, Paiements (transverses) |
| Pilotage | Rapports & BI, Notifications, Documents (DMS), Audit |
| Administration | Utilisateurs, Rôles & Permissions, Paramètres, Multi-boutiques, Multi-entreprises |

Aucun nouveau module métier n’a été créé pendant la phase RC1.

---

## Bugs corrigés (RC1)

### Critique / Sécurité
- Auto-login démo limité à `local` / `testing` (plus en production)
- Routes publiques facture `/f/{token}` et devis `/d/{token}` sorties du middleware `workspace`
- Route `POST /logout` + page `/logged-out`
- `ProductRequest::authorize()` vérifie `products.create` / `products.update`
- Permissions resserrées : `purchases.approve`, `invoices.approve`, `companies.archive`, `companies.switch`, `stores.switch`

### Navigation & UX
- Suppression du lien mort CRM (hors scope V1)
- Lien Paiements branché (`reports.payments` ou `sales.dashboard`)
- Menu utilisateur dynamique (profil, préférences, déconnexion)
- Bouton « Nouveau » avec actions contextuelles
- Dashboard home : KPIs réels, alertes live, timeline audit
- Badge version `v1.0 · RC1`

### Design system
- Ajout des utilitaires manquants : `gp-input`, `gp-select`, `gp-textarea`, `gp-label`, `gp-table`, `gp-empty`, `gp-flash`, `gp-skeleton`, `gp-loader`, `gp-btn-danger`
- Messages d’erreur de validation globaux dans le layout
- Ajustements responsive (iOS font-size, boutons full-width mobile)

### Performances
- Dashboard stock : 1 requête mouvements / 7 jours au lieu de N requêtes journalières
- Eager-load colonnes ciblées sur stock dashboard

### Configuration
- `.env.example` : `APP_NAME=GreenPOS`, locale `fr`, checklist production
- `config/app.php` : défauts GreenPOS / fr

---

## Optimisations réalisées

- Middleware audit déjà en place (traçabilité sans réécriture métier)
- Pagination conservée sur les listes modules
- Build Vite production OK (`app-*.css` ~112 kB / JS ~50 kB)
- Script de smoke RC1 : `php scripts/rc1_smoke.php` (**71 checks OK**)

---

## Validations RC1

| Phase | Résultat |
|-------|----------|
| Routes modules (19 dashboards) | HTTP 200 |
| Permissions rôles (owner, manager, cashier, sales, accountant, storekeeper) | Matrice OK |
| Design tokens CSS | Présents |
| Navigation sans `href="#"` | OK |
| Compilation Vite | OK |

---

## Recommandations post-RC1 (V1.1 / GA)

1. **Auth réelle** — écran login/mot de passe oublié (Breeze/Fortify) ; retirer totalement l’auto-login hors démo
2. **Module Paiements dédié** — UI consolidée (aujourd’hui transverse ventes/factures/POS/rapports)
3. **CRM** — reporté volontairement hors V1
4. **Tests automatisés** — Feature tests Laravel pour workflows critiques (POS, facture, stock)
5. **Perf catalogue** — pagination / recherche AJAX sur formulaires vente/facture (éviter `all products`)
6. **Observabilité** — Sentry / logs structurés en production
7. **Production** — `APP_DEBUG=false`, HTTPS, `APP_KEY` fort, backups DB, `php artisan config:cache route:cache view:cache`
8. **Roles sync** — relancer `ensureSystemRoles()` après déploiement pour propager `settings.view` manager

---

## Checklist go-live (opérateur)

- [ ] `APP_ENV=production` / `APP_DEBUG=false`
- [ ] Migrations + seed rôles (`ensureSystemRoles`)
- [ ] `php artisan storage:link`
- [ ] `npm run build`
- [ ] `php scripts/rc1_smoke.php`
- [ ] Vérifier partage public facture/devis sans session
- [ ] Compte propriétaire non-démo créé ; mot de passe démo changé

---

**Verdict :** GreenPOS V1 est déclaré **Release Candidate RC1**.  
Produit stable pour démo commerciale et pilotes clients, sous réserve d’auth production avant GA.
