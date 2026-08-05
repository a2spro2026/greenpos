# GreenPOS v3 — Rôles et permissions (RBAC)

**Document officiel**  
**Version :** 3.0  
**Statut :** Référence du contrôle d’accès  
**Public :** Équipes Produit, UX/UI, Développement, Sécurité et Direction  
**Documents liés :** `01_PRODUCT_BLUEPRINT.md`, `02_PRODUCT_PRINCIPLES.md`, `03_ACTIVITIES_AND_MODULES_CATALOG.md`, `04.1_ONBOARDING_AND_WORKSPACE.md`, `04.2_DASHBOARD.md`, `05_DESIGN_SYSTEM.md`

---

## Préambule

Ce document définit le **système officiel des rôles et des permissions** de GreenPOS (RBAC — Role-Based Access Control).

Il ne décrit pas une implémentation technique.  
Il définit les **règles métier de contrôle d’accès** que toute conception d’écran, toute API future et toute logique applicative devront respecter.

GreenPOS est une plateforme SaaS multi-entreprises, multi-boutiques et multi-modules.  
Sans un modèle de permissions strict, l’isolation des données, la confiance des clients et la sécurité opérationnelle s’effondrent.

**Principe directeur :**  
pas d’utilisateur sans entreprise, pas d’action sans permission, pas de donnée visible hors périmètre autorisé.

---

## 1. Principes de sécurité de la plateforme

### 1.1 Objectifs de sécurité

Le contrôle d’accès GreenPOS vise à :

- protéger l’isolation totale entre entreprises ;  
- limiter chaque utilisateur à son périmètre utile ;  
- empêcher les actions non autorisées (lecture comme écriture) ;  
- rendre les actions sensibles traçables ;  
- permettre une administration claire sans complexité inutile ;  
- s’adapter aux rôles terrain (caissier, magasinier, manager…) et aux rôles de pilotage.

### 1.2 Principes non négociables

1. **Isolation des entreprises** — aucune entreprise ne peut accéder aux données d’une autre.  
2. **Périmètre boutique** — les données opérationnelles sont rattachées à une boutique, sauf vue explicitement autorisée au niveau entreprise.  
3. **Moindre privilège** — un utilisateur ne reçoit que les droits nécessaires à sa mission.  
4. **Permissions explicites** — aucun droit implicite « par habitude ».  
5. **Modules comme frontières** — un module non activé n’ouvre aucun droit métier associé.  
6. **Rôles avant exceptions** — on part du rôle ; les exceptions sont rares, documentées et auditées.  
7. **Traçabilité** — les actions sensibles laissent une trace compréhensible.  
8. **UI alignée sur les droits** — ce qui n’est pas autorisé ne doit pas apparaître comme action disponible.  
9. **Sécurité > commodité** — en cas de doute, restreindre.  
10. **Cohérence Design System** — les états d’accès (désactivé, interdit, lecture seule) restent clairs et accessibles.

### 1.3 Alignement avec la hiérarchie produit

```
Compte utilisateur
    ↓
Entreprise
    ↓
Boutiques
    ↓
Modules
    ↓
Utilisateurs + Rôles + Permissions
    ↓
Données métier
```

Le RBAC s’exerce **à l’intérieur** de cette hiérarchie.  
Un rôle n’existe jamais « en dehors » d’une entreprise (sauf rôles plateforme explicitement définis, voir Super Admin).

---

## 2. Hiérarchie des rôles

### 2.1 Niveaux de pouvoir (du plus large au plus restreint)

| Niveau | Portée | Exemple |
|--------|--------|---------|
| **Plateforme** | Toute la plateforme GreenPOS | Super Admin |
| **Entreprise** | Une entreprise entière (toutes boutiques / modules selon droits) | Owner, Admin Entreprise |
| **Boutique** | Une ou plusieurs boutiques d’une entreprise | Manager de boutique |
| **Opérationnel** | Actions quotidiennes limitées | Caissier, Magasinier |
| **Spécialisé** | Domaine fonctionnel | Comptable, Commercial |

### 2.2 Règle de cascade

- Un rôle de niveau supérieur peut englober les capacités des niveaux inférieurs **uniquement si** cela est explicitement prévu par sa matrice de permissions.  
- Un rôle inférieur ne peut jamais s’auto-attribuer un rôle supérieur.  
- Le fait d’être Owner d’une entreprise A ne donne **aucun** droit sur l’entreprise B, même sur le même compte.

### 2.3 Distinction Compte / Utilisateur d’entreprise

- Le **compte** est l’identité d’accès à la plateforme.  
- L’**utilisateur d’entreprise** est le rattachement de ce compte (ou d’une invitation) à une entreprise, avec rôle(s) et périmètre.  
- Un même compte peut être Owner d’une entreprise et simple Caissier d’une autre : les droits ne se mélangent pas.

---

## 3. Rôles système

Les rôles système sont fournis par défaut.  
Ils couvrent les besoins V1 et les usages décrits dans le Blueprint et le Dashboard.

### 3.1 Super Admin (plateforme)

**Portée :** plateforme GreenPOS (hors données métier client, sauf outils de support strictement encadrés).

**Mission :** exploitation, support, supervision plateforme.

**Peut typiquement :**
- administrer le catalogue d’activités / modules au niveau plateforme ;  
- gérer des incidents de support selon procédure ;  
- accéder aux outils internes de supervision.

**Ne doit pas :**
- se comporter comme Owner d’entreprises clientes « par défaut » ;  
- modifier des données métier sans cadre d’audit et de justification ;  
- être confondu avec le rôle Owner client.

> Le Super Admin est un rôle **interne GreenPOS**, pas un rôle client.

### 3.2 Owner (Propriétaire)

**Portée :** entreprise.

**Mission :** décideur de l’organisation ; responsable de la configuration globale.

**Peut typiquement :**
- gérer l’entreprise et ses paramètres ;  
- créer / archiver des boutiques ;  
- activer / désactiver des modules ;  
- inviter des utilisateurs et assigner des rôles ;  
- voir le pilotage global (multi-boutiques si autorisé) ;  
- accéder aux rapports consolidés ;  
- définir les dispositions Dashboard recommandées.

**Limites :**
- aucun accès aux autres entreprises du même compte sans bascule de contexte et droits propres ;  
- reste soumis à l’isolation des données.

### 3.3 Admin Entreprise (Administrateur)

**Portée :** entreprise.

**Mission :** bras droit opérationnel de l’Owner pour la configuration et les accès.

**Proche de l’Owner**, avec éventuelles restrictions sur :
- suppression d’entreprise ;  
- facturation / abonnement plateforme ;  
- transfert de propriété.

(Les restrictions exactes Owner vs Admin doivent rester explicites dans la matrice.)

### 3.4 Manager (Responsable de boutique / périmètre)

**Portée :** une ou plusieurs boutiques.

**Mission :** pilotage opérationnel local.

**Peut typiquement :**
- superviser ventes, stock, équipe de son périmètre ;  
- traiter alertes opérationnelles ;  
- gérer paramètres locaux de boutique ;  
- valider certaines opérations sensibles locales ;  
- consulter rapports de son périmètre.

**Ne peut typiquement pas :**
- activer/désactiver des modules au niveau entreprise (sauf délégation explicite) ;  
- gérer toutes les boutiques hors périmètre ;  
- modifier la structure globale de l’entreprise sans droit dédié.

### 3.5 Caissier

**Portée :** boutique(s) assignée(s), modules de vente/caisse.

**Mission :** encaisser rapidement et fiablement.

**Peut typiquement :**
- ouvrir le POS / caisse ;  
- créer des ventes ;  
- encaisser des paiements autorisés ;  
- consulter le nécessaire à la vente (catalogue visible, stock indicatif selon règle).

**Ne peut typiquement pas :**
- voir CA stratégique global ;  
- modifier les prix de référence hors autorisation ;  
- gérer utilisateurs ;  
- clôturer certaines opérations réservées au Manager (selon paramétrage) ;  
- accéder aux modules hors vente.

### 3.6 Magasinier

**Portée :** boutique(s) / zones stock assignées.

**Mission :** fiabilité des stocks et mouvements.

**Peut typiquement :**
- consulter et ajuster stocks selon droits ;  
- réceptionner des achats ;  
- traiter alertes de rupture ;  
- voir mouvements de stock.

**Ne peut typiquement pas :**
- encaisser (sauf double rôle) ;  
- voir données financières sensibles hors besoin ;  
- administrer l’entreprise.

### 3.7 Comptable

**Portée :** entreprise et/ou boutiques selon assignation ; modules finance.

**Mission :** suivi des paiements, factures, encours, clôtures.

**Peut typiquement :**
- consulter facturation / paiements ;  
- exporter des données autorisées ;  
- suivre retards et clôtures ;  
- accéder aux rapports financiers.

**Ne peut typiquement pas :**
- modifier le catalogue produit sans droit ;  
- gérer les rôles ;  
- opérer la caisse terrain sauf besoin explicite.

### 3.8 Commercial

**Portée :** entreprise / boutiques selon assignation ; relation client.

**Mission :** clients, devis, suivi commercial, relances.

**Peut typiquement :**
- gérer clients / CRM selon modules ;  
- créer et suivre des devis ;  
- consulter historiques commerciaux autorisés.

**Ne peut typiquement pas :**
- accéder aux stocks profonds hors besoin métier ;  
- administrer utilisateurs ;  
- clôturer la caisse.

### 3.9 Autres rôles système possibles (extension)

Selon activités et maturité :

- **Serveur / Équipier salle** (Restaurant)  
- **Préparateur cuisine** (Restaurant)  
- **Pharmacien / Préparateur** (Pharmacie) — avec règles métier renforcées  
- **Réceptionniste** (Hôtel)  
- **Technicien atelier** (Garage)  
- **Lecteur / Invité** — lecture seule très limitée  

Ces rôles spécialisés restent soumis aux mêmes principes : périmètre entreprise/boutique + modules + actions.

### 3.10 Rôle du créateur à l’onboarding

Lors de l’onboarding, le créateur du compte devient le **premier Owner** de l’entreprise créée.  
C’est le point de départ de toute délégation ultérieure.

---

## 4. Rôles personnalisés

### 4.1 Principe

Une entreprise peut créer des **rôles personnalisés** pour coller à son organisation, sans casser le modèle RBAC.

### 4.2 Règles

- Un rôle personnalisé appartient à **une seule entreprise**.  
- Il est composé uniquement de permissions existantes au catalogue.  
- Il ne peut pas dépasser les capacités maximales autorisées à l’Owner pour délégation.  
- Il doit avoir un nom clair, une description, et une matrice lisible.  
- Il est versionnable conceptuellement : toute modification majeure doit être comprise avant application aux utilisateurs.

### 4.3 Bonnes pratiques

- Partir d’un rôle système proche, puis restreindre.  
- Éviter les rôles « fourre-tout ».  
- Documenter l’intention du rôle (à quoi il sert sur le terrain).

### 4.4 Interdits

- Créer un rôle personnalisé qui contourne l’isolation inter-entreprises.  
- Accorder des permissions sur des modules non activés (elles restent inopérantes et ne doivent pas tromper l’admin).  
- Permettre à un rôle non Owner/Admin d’éditer librement tous les rôles sans contrôle.

---

## 5. Modèle de permission

### 5.1 Formule conceptuelle

Une autorisation effective résulte de la combinaison :

> **Utilisateur + Entreprise (contexte) + Boutique(s) + Module(s) activés + Rôle(s) + Action**

Si un seul maillon est manquant ou négatif → **accès refusé**.

### 5.2 Dimensions de permission

1. **Par module** — ex. POS, Stock, Clients, Facturation  
2. **Par entreprise** — droits de structure et de pilotage global  
3. **Par boutique** — droits opérationnels locaux  
4. **Par action** — Créer, Lire, Modifier, Supprimer, etc.

### 5.3 Catalogue d’actions standard

Actions transverses officielles :

| Action | Sens |
|--------|------|
| **Créer** | Ajouter une ressource |
| **Lire** | Consulter |
| **Modifier** | Mettre à jour |
| **Supprimer** | Retirer / archiver selon règles |
| **Exporter** | Extraire des données |
| **Importer** | Charger des données |
| **Valider** | Approuver une opération |
| **Annuler** | Annuler une opération métier |
| **Clôturer** | Clôturer une période / caisse / dossier |
| **Assigner** | Affecter (utilisateur, boutique, ressource) |
| **Activer / Désactiver** | Changer l’état d’usage |
| **Configurer** | Modifier des paramètres |
| **Imprimer** | Éditer ticket / document |
| **Rembourser** | Opération financière sensible (si applicable) |

Des actions spécifiques module peuvent exister (ex. « Ouvrir caisse », « Transmettre cuisine », « Délivrer ordonnance ») mais doivent rester explicites.

---

## 6. Permissions par module

### 6.1 Règle générale

- Si le module n’est **pas activé** pour l’entreprise → aucune permission métier de ce module n’est exercable.  
- Si le module est activé mais l’utilisateur n’a pas le droit → pas d’accès.  
- L’UI ne doit pas proposer d’entrer dans un module inaccessible.

### 6.2 Modules plateforme (toujours structurants)

| Module / capacité | Exemples de permissions |
|-------------------|-------------------------|
| **Tableau de bord** | Lire widgets autorisés, personnaliser sa disposition |
| **Utilisateurs** | Inviter, Lire, Modifier, Activer/Désactiver, Assigner rôles |
| **Permissions / Rôles** | Lire, Créer rôles perso, Modifier matrices (Owner/Admin) |
| **Paramètres** | Configurer entreprise / boutique |
| **Entreprises / Boutiques** | Créer boutique, Modifier, Archiver |

### 6.3 Modules métier (exemples V1)

#### POS / Caisse / Ventes / Paiements
- Ouvrir session de caisse  
- Créer vente  
- Encaisser  
- Annuler ligne / ticket (souvent restreint)  
- Clôturer caisse (souvent Manager+)  
- Lire historique ventes (selon rôle)

#### Produits / Stock / Achats / Fournisseurs
- CRUD produits selon droit  
- Ajuster stock  
- Réceptionner achats  
- Exporter inventaire  

#### Clients / CRM
- CRUD clients  
- Voir historique  
- Gérer opportunités (CRM)

#### Facturation / Devis / Paiements
- Créer devis / factures  
- Valider  
- Enregistrer paiements  
- Exporter  

#### Modules spécialisés (Tables, Cuisine, Ordonnances, Livraisons…)
- Permissions dédiées + éventuelles actions réglementaires renforcées (pharmacie notamment)

### 6.4 Matrice indicative (rôle × module)

Légende : **P** plein accès utile au rôle · **L** lecture / usage limité · **—** hors périmètre typique

| Module | Owner | Manager | Caissier | Magasinier | Comptable | Commercial |
|--------|-------|---------|----------|------------|-----------|------------|
| Dashboard | P | P | L | L | L | L |
| POS / Caisse | P | P | P | — | — | L |
| Produits | P | P | L | L | — | L |
| Stock | P | P | L | P | — | L |
| Achats / Fournisseurs | P | P | — | P | L | — |
| Clients | P | P | L | — | L | P |
| Ventes | P | P | P | — | L | L |
| Paiements / Facturation | P | P | L | — | P | L |
| Devis | P | P | — | — | L | P |
| Rapports | P | P | — | L | P | L |
| Utilisateurs / Rôles | P | L* | — | — | — | — |
| Paramètres | P | L/P local | — | — | L | — |

\*Manager : lecture équipe locale / invitations limitées selon politique entreprise.

Cette matrice est **indicative** : la matrice fine par action doit être définie à la configuration du rôle.

---

## 7. Permissions par entreprise

### 7.1 Droits de niveau entreprise

Concernent la structure et le pilotage global :

- créer / modifier l’entreprise (Owner) ;  
- gérer l’activité et les modules ;  
- créer des boutiques ;  
- gérer tous les utilisateurs de l’entreprise ;  
- voir consolidations multi-boutiques ;  
- configurer les rôles personnalisés ;  
- accéder aux paramètres globaux.

### 7.2 Règles

- Les droits entreprise ne traverse**nt** jamais vers une autre entreprise.  
- Un utilisateur peut avoir des droits entreprise forts et un périmètre boutique large, ou l’inverse.  
- Les vues consolidées multi-boutiques exigent une permission explicite de consolidation.

---

## 8. Permissions par boutique

### 8.1 Droits de niveau boutique

Concernent l’exploitation locale :

- ventes et caisse de la boutique ;  
- stock de la boutique ;  
- paramètres locaux ;  
- alertes locales ;  
- équipe assignée à la boutique.

### 8.2 Règles

- Une boutique appartient à une seule entreprise.  
- Un utilisateur peut être assigné à **une ou plusieurs** boutiques.  
- Sans assignation boutique, pas d’action opérationnelle locale.  
- Le changement de boutique active dans l’UI recalcule le contexte visible, sans élargir les droits.

### 8.3 Multi-boutiques

- Voir une boutique ≠ pouvoir agir dessus.  
- Agir sur plusieurs boutiques exige une assignation explicite à chacune (ou un droit entreprise équivalent clairement défini).

---

## 9. Permissions par action

### 9.1 Principe CRUD étendu

Chaque ressource sensible doit pouvoir distinguer au minimum :

- Lire  
- Créer  
- Modifier  
- Supprimer  

Puis les actions métier (Valider, Clôturer, Exporter…).

### 9.2 Exemples d’application

| Ressource | Caissier typique | Manager typique | Owner typique |
|-----------|------------------|-----------------|---------------|
| Vente | Créer, Lire (jour) | Créer, Lire, Annuler, Clôturer | Plein |
| Produit | Lire | Créer, Modifier, Lire | Plein |
| Stock | Lire limité | Ajuster, Valider | Plein |
| Utilisateur | — | Lire équipe | Plein |
| Rapport | — | Lire / Exporter périmètre | Plein |
| Module | — | — | Activer / Désactiver |

### 9.3 Actions sensibles (confirmation + audit)

Toujours traiter comme sensibles :

- Supprimer  
- Annuler une vente finalisée  
- Rembourser  
- Clôturer caisse  
- Modifier prix en masse  
- Activer/désactiver module  
- Changer les rôles  
- Exporter des données personnelles / financières  

---

## 10. Héritage des permissions

### 10.1 Ce qui est hérité

- Un rôle système fournit un **paquet de permissions** de base.  
- Un rôle personnalisé peut hériter d’un modèle (copie initiale), puis diverger.  
- Un utilisateur hérite des permissions de **tous ses rôles actifs** dans l’entreprise (union), dans le respect du périmètre boutique.

### 10.2 Règles d’union et d’intersection

- **Union des permissions de rôles** (si multi-rôles autorisé) : l’utilisateur obtient le maximum accordé par ses rôles.  
- **Intersection avec le périmètre** : même avec une permission « Stock / Modifier », sans boutique assignée → refus.  
- **Intersection avec modules activés** : permission sans module actif → inopérante.  
- **Deny explicite** (si introduit plus tard) doit primer sur l’allow ; en V1, préférer des rôles clairs sans deny complexes.

### 10.3 Non-héritage inter-entreprises

Aucun héritage de permissions d’une entreprise vers une autre.

### 10.4 Héritage Owner → délégation

L’Owner peut déléguer, mais :

- ne peut pas déléguer plus que ce que le système autorise ;  
- reste responsable de la gouvernance des accès ;  
- les délégations dangereuses doivent être auditées.

---

## 11. Restrictions d’accès

### 11.1 Refus systématique si

- pas de contexte entreprise actif ;  
- entreprise distincte de celle du rattachement ;  
- boutique hors assignation ;  
- module inactif ;  
- rôle insuffisant pour l’action ;  
- compte désactivé / invitation non acceptée ;  
- session expirée / non authentifiée.

### 11.2 Comportement UX attendu

Conformément au Design System et au Dashboard :

- masquer les entrées de navigation non autorisées ;  
- ne pas afficher de faux boutons actifs ;  
- si accès direct à une ressource interdite : message clair (« Accès non autorisé ») + sortie sûre ;  
- ne jamais révéler l’existence de données hors périmètre (énumération).

### 11.3 Restrictions métier renforcées

Certaines activités imposent des garde-fous supplémentaires (ex. Pharmacie / ordonnances, données patients).  
Ces garde-fous s’ajoutent au RBAC standard ; ils ne le remplacent pas.

---

## 12. Gestion des utilisateurs

### 12.1 Cycle de vie

1. Invitation (ou création)  
2. Acceptation / activation  
3. Assignation rôle(s) + boutique(s)  
4. Usage quotidien  
5. Modification de droits  
6. Désactivation / réactivation  
7. Suppression / anonymisation selon règles

### 12.2 Règles

- Tout utilisateur opérationnel est rattaché à une entreprise.  
- Un utilisateur peut avoir plusieurs rôles si la politique entreprise l’autorise (à utiliser avec prudence).  
- Les changements de rôle prennent effet immédiatement pour les nouvelles actions ; les sessions en cours doivent être requalifiées selon politique de sécurité.  
- L’Owner ne peut pas être retiré s’il est le dernier Owner (empêcher le lock-out).

### 12.3 Profils visibles dans le Dashboard

Le Dashboard s’adapte au rôle (Owner, Manager, Caissier, Magasinier, Comptable, Commercial) :  
widgets, alertes et raccourcis filtrés par permissions.

---

## 13. Invitation des utilisateurs

### 13.1 Parcours fonctionnel

1. Un Owner/Admin (ou rôle délégué) invite un utilisateur.  
2. Définition : e-mail, rôle(s), boutique(s), message optionnel.  
3. L’invité reçoit une invitation.  
4. Acceptation → rattachement à l’entreprise.  
5. Première connexion guidée (sans refaire tout l’onboarding entreprise).

### 13.2 Règles

- Impossible d’inviter hors droits.  
- Impossible d’assigner un rôle supérieur à soi-même si non autorisé.  
- Les invitations expirent après une durée définie.  
- Une invitation peut être révoquée tant qu’elle n’est pas acceptée.  
- Si l’e-mail possède déjà un compte plateforme, on rattache ; on ne crée pas un doublon d’identité.

### 13.3 Contenu minimal de l’invitation

- entreprise concernée ;  
- rôle proposé ;  
- boutiques proposées ;  
- qui invite ;  
- expiration.

---

## 14. Activation et désactivation des comptes

### 14.1 États

| État | Sens |
|------|------|
| **Invité** | Pas encore actif |
| **Actif** | Peut se connecter et agir selon droits |
| **Désactivé** | Ne peut plus se connecter / agir |
| **Suspendu** | Blocage temporaire (sécurité / litige) |
| **Supprimé** | Sorti du référentiel actif (selon politique de conservation)

### 14.2 Désactivation

- Immédiate sur nouvelles sessions.  
- Révoque l’accès aux entreprises concernées.  
- Conserve l’historique des actions déjà réalisées (audit).  
- N’efface pas les ventes / mouvements passés.

### 14.3 Réactivation

- Réservée aux rôles autorisés.  
- Doit reconfirmer rôles et boutiques.  
- Événement audité.

---

## 15. Règles de sécurité

### 15.1 Authentification

- Accès uniquement après authentification valide.  
- Vérification de compte lors de l’onboarding (conformément au parcours officiel).  
- Politique de mot de passe robuste et messages clairs.

### 15.2 Session et contexte

- Contexte entreprise obligatoire pour agir.  
- Contexte boutique pour l’opérationnel.  
- Basculer d’entreprise = changer de univers de droits.

### 15.3 Séparation des responsabilités

- Celui qui vend n’a pas forcément le droit de clôturer / exporter / supprimer.  
- Celui qui configure les rôles ne devrait pas opérer sans contrôle sur des données hors besoin (selon taille d’équipe).

### 15.4 Données sensibles

- Données financières, personnelles, santé (si modules) : accès restreint + audit renforcé.  
- Exports limités aux rôles autorisés.

### 15.5 Échec sécurisé

Toute ambiguïté de droit se résout par **refus**.

---

## 16. Journaux d’audit (Audit Logs)

### 16.1 Objectif

Garantir la confiance et la traçabilité : savoir **qui a fait quoi, quand, dans quel contexte**.

### 16.2 Événements à journaliser (minimum)

- Connexions / échecs de connexion (selon politique)  
- Invitations, activations, désactivations  
- Changements de rôles et de permissions  
- Création / suppression d’utilisateurs  
- Activation / désactivation de modules  
- Création de boutiques  
- Actions sensibles de caisse (annulation, remboursement, clôture)  
- Exports de données  
- Modifications critiques de paramètres  
- Accès support éventuels (Super Admin)

### 16.3 Contenu d’une entrée d’audit

- horodatage ;  
- acteur (utilisateur) ;  
- entreprise ;  
- boutique (si applicable) ;  
- module / domaine ;  
- action ;  
- cible (ressource) ;  
- résultat (succès / refus) ;  
- métadonnées utiles non techniques excessives.

### 16.4 Règles

- Les logs d’audit ne sont pas modifiables par les utilisateurs métier.  
- Conservation selon politique entreprise / légale.  
- Accès aux logs réservé Owner/Admin (et Super Admin encadré).  
- Un refus d’accès sensible peut lui-même être journalisé.

---

## 17. Cas particuliers

### 17.1 Multi-entreprises

- Un compte peut posséder / rejoindre plusieurs entreprises.  
- Chaque entreprise = droits séparés.  
- L’UI demande ou mémorise le contexte entreprise actif.  
- Aucune vue croisée des données entre entreprises.

### 17.2 Multi-boutiques

- Assignations explicites.  
- Dashboard structure identique, données selon boutique active.  
- Permission de consolidation multi-boutiques distincte de la simple bascule.

### 17.3 Changement de rôle

- Effectué par rôle autorisé.  
- Prise d’effet immédiate pour les nouvelles actions.  
- Notification interne recommandée à l’utilisateur.  
- Événement audité.  
- Si le nouveau rôle retire l’accès au module courant → redirection sûre (ex. Dashboard ou écran d’accès refusé).

### 17.4 Suppression d’utilisateur

- Préférer **désactivation** à la suppression immédiate.  
- Si suppression : conserver l’intégrité historique (ventes réalisées restent attribuées à un identifiant historique).  
- Interdire la suppression du dernier Owner.  
- Révoquer invitations en cours.  
- Auditer l’opération.

### 17.5 Module désactivé alors que des utilisateurs ont des droits

- Les permissions deviennent inopérantes.  
- Les entrées UI disparaissent.  
- À la réactivation, les droits préexistants peuvent revenir selon politique (à confirmer explicitement à l’admin).

### 17.6 Changement d’activité de l’entreprise

- Peut rendre certains modules moins pertinents.  
- Ne doit pas supprimer silencieusement les utilisateurs.  
- Doit informer sur les impacts d’accès / modules (comme pour le changement d’activité décrit dans l’onboarding).

### 17.7 Utilisateur sans boutique

- Peut exister pour des rôles purement entreprise (ex. Owner en configuration).  
- Ne peut pas réaliser d’opérations de caisse / stock local sans boutique.

### 17.8 Conflit multi-rôles

- Si multi-rôles : appliquer l’union des allows dans le périmètre.  
- Documenter clairement le résultat dans l’écran de gestion des accès pour éviter les surprises.

---

## 18. Matrice de décision (résumé opérationnel)

Avant d’autoriser une action, GreenPOS doit pouvoir répondre **oui** à toutes ces questions :

1. L’utilisateur est-il authentifié et actif ?  
2. Quelle entreprise est en contexte ? Y est-il rattaché ?  
3. L’action concerne-t-elle une boutique ? Y est-il assigné ?  
4. Le module requis est-il activé ?  
5. Son rôle possède-t-il l’action demandée sur ce module ?  
6. L’action est-elle compatible avec d’éventuelles règles métier renforcées ?  
7. L’événement sensible doit-il être audité ?

Si une réponse est non → **refus**.

---

## 19. Implications UX / UI

Conformément au Design System et au Dashboard :

- navigation filtrée par droits ;  
- widgets et alertes filtrés par rôle ;  
- raccourcis adaptés ;  
- états lecture seule visibles ;  
- messages d’accès refusé clairs, non techniques ;  
- administration des rôles lisible (noms, descriptions, périmètres).

Le contrôle d’accès n’est pas seulement backend : **l’interface est une couche de sécurité et de clarté**.

---

## 20. Périmètre V1 recommandé

Pour la V1, prioriser :

- rôles système : Owner, Admin, Manager, Caissier, Magasinier, Comptable, Commercial ;  
- invitations ;  
- activation / désactivation ;  
- permissions par module + actions CRUD étendues sur modules V1 ;  
- périmètre multi-boutiques ;  
- isolation multi-entreprises ;  
- audit des actions sensibles ;  
- adaptation Dashboard par rôle.

Reporter si nécessaire :

- moteurs de deny très complexes ;  
- marketplace de rôles ;  
- granularité extrême champ-à-champ non justifiée.

---

## 21. Conclusion

Le système de rôles et permissions est un pilier de confiance de GreenPOS.

Il protège :

- l’isolation des entreprises ;  
- la séparation des responsabilités ;  
- la sécurité des opérations quotidiennes ;  
- la lisibilité de l’administration.

Toute fonctionnalité future — POS, Stock, Pharmacie, Devis, multi-boutiques, Dashboard — devra s’aligner sur ce document.

**Rappel final :**  
**pas d’utilisateur sans entreprise, pas d’action sans permission, pas de donnée hors périmètre.**

**Prochaine étape documentaire recommandée :** spécifier la matrice fine V1 (rôle système × module V1 × action) sous forme d’annexe opérationnelle, puis les écrans d’administration des utilisateurs et des rôles.

---

*GreenPOS v3 — Document officiel — 06_ROLES_AND_PERMISSIONS.md*
