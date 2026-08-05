# GreenPOS v3 — Catalogue des activités et des modules

**Document officiel**  
**Version :** 3.0  
**Statut :** Catalogue de référence produit  
**Public :** Équipe produit, design et développement  
**Documents liés :** `01_PRODUCT_BLUEPRINT.md`, `02_PRODUCT_PRINCIPLES.md`

---

## 1. Introduction

GreenPOS est une **plateforme SaaS multi-activités**.  
Elle n’impose pas un métier unique : elle accueille des entreprises de natures très différentes, chacune composant son système de gestion à partir d’un catalogue d’activités et de modules.

### Règles de lecture du catalogue

- Chaque **activité** représente un métier de référence.
- Chaque activité possède un ensemble de **modules recommandés**.
- Le client reste **libre d’activer uniquement les modules dont il a besoin**.
- Les modules recommandés guident le démarrage ; ils ne constituent pas une obligation totale.
- De nouvelles activités et de nouveaux modules pourront être ajoutés sans remettre en cause ce catalogue de principe.
- Ce document sert de **référence officielle** pour toutes les spécifications fonctionnelles à venir.

### Priorités des modules

| Priorité | Signification |
|----------|----------------|
| **Essentiel** | Fortement attendu pour démarrer correctement dans l’activité |
| **Recommandé** | Apporte une valeur claire au quotidien ; activation suggérée |
| **Optionnel** | Utile selon la maturité, la taille ou le modèle de l’entreprise |

---

## 2. Catalogue des activités

### 2.1 Vue d’ensemble

| Activité | Description courte |
|----------|--------------------|
| Épicerie | Commerce de proximité alimentaire |
| Supermarché | Grande surface alimentaire et non alimentaire |
| Boutique | Commerce de détail généraliste ou spécialisé |
| Restaurant | Restauration avec service à table |
| Snack | Restauration rapide / vente à emporter |
| Café | Débit de boissons et petite restauration |
| Pâtisserie | Fabrication et vente de pâtisseries |
| Boulangerie | Fabrication et vente de pain et viennoiseries |
| Pharmacie | Officine et délivrance de médicaments |
| Parapharmacie | Produits de santé et bien-être hors ordonnance |
| Librairie | Vente de livres et produits culturels |
| Papeterie | Fournitures scolaires et de bureau |
| Matériaux de construction | Négoce de matériaux pour le bâtiment |
| Quincaillerie | Outillage, quincaillerie et bricolage |
| Garage | Réparation et entretien automobile |
| Atelier mécanique | Atelier de mécanique / maintenance technique |
| Hôtel | Hébergement hôtelier |
| Maison d'hôtes | Hébergement à plus petite échelle |
| Salon de coiffure | Services de coiffure |
| Institut de beauté | Soins esthétiques et beauté |
| Cabinet médical | Consultations médicales |
| Cabinet dentaire | Soins dentaires |
| Clinique | Structure de soins pluridisciplinaire |
| École privée | Établissement scolaire privé |
| Centre de formation | Organisme de formation |
| Salle de sport | Fitness / club sportif |
| Agence immobilière | Transaction et/ou location immobilière |
| Société de services | Prestations de services aux entreprises ou particuliers |
| Société de transport | Transport de personnes ou de marchandises |
| Agriculture | Exploitation agricole / agroalimentaire de proximité |
| Autres | Activités non listées, à composer librement |

---

### 2.2 Fiches activités

#### Épicerie

**Description :** Commerce de proximité vendant denrées alimentaires et produits de première nécessité.  
**Objectifs :** Encaisser rapidement, gérer le stock, suivre les réapprovisionnements, connaître les ventes du jour.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Achats, Fournisseurs, Clients, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Supermarché

**Description :** Point de vente de grande surface combinant alimentaire, hygiène et non alimentaire.  
**Objectifs :** Gérer un catalogue large, plusieurs caisses, stocks importants, promotions et reporting.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Achats, Fournisseurs, Clients, CRM, Paiements, Facturation, Rapports, Tableaux de bord, Notifications, Utilisateurs, Permissions, Paramètres

#### Boutique

**Description :** Commerce de détail généraliste ou spécialisé (mode, électroménager, cadeaux, etc.).  
**Objectifs :** Vendre, suivre le stock, gérer les clients, piloter le chiffre d’affaires.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Clients, CRM, Ventes, Paiements, Facturation, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Restaurant

**Description :** Établissement de restauration avec service, tables et préparation en cuisine.  
**Objectifs :** Prendre les commandes, gérer les tables, transmettre en cuisine, encaisser, suivre les stocks.  
**Modules recommandés :** POS, Tables, Cuisine, Réservations, Caisse, Produits, Stock, Achats, Clients, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Snack

**Description :** Restauration rapide, vente à emporter ou consommation sur place simplifiée.  
**Objectifs :** Encaisser vite, gérer un menu court, suivre le stock et les ventes.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Cuisine, Clients, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Café

**Description :** Débit de boissons avec éventuelle petite restauration.  
**Objectifs :** Encaisser, gérer la carte, suivre stock boissons/consommables, éventuelles réservations.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Réservations, Clients, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Pâtisserie

**Description :** Fabrication et vente de pâtisseries, souvent avec production et vitrine.  
**Objectifs :** Gérer production, stock matières, ventes comptoir, commandes clients.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Achats, Fournisseurs, Clients, Réservations, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Boulangerie

**Description :** Fabrication et vente de pain, viennoiseries et produits associés.  
**Objectifs :** Ventes rapides, suivi stock farines/ingrédients, production quotidienne, reporting.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Achats, Fournisseurs, Clients, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Pharmacie

**Description :** Officine délivrant médicaments, parfois sur ordonnance, avec exigences de traçabilité.  
**Objectifs :** Vendre, gérer stock médicaments, traiter ordonnances, suivre patients, respecter la traçabilité.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Ordonnances, Patients, Fournisseurs, Achats, Paiements, Documents, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Parapharmacie

**Description :** Vente de produits de santé, hygiène et bien-être sans délivrance d’ordonnances médicales.  
**Objectifs :** Catalogue produits, stock, ventes, clients, conseils associés.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Clients, CRM, Achats, Fournisseurs, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Librairie

**Description :** Commerce de livres et produits culturels associés.  
**Objectifs :** Gérer un catalogue large, stock, commandes, clients et ventes.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Clients, CRM, Achats, Fournisseurs, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Papeterie

**Description :** Vente de fournitures scolaires, de bureau et articles associés.  
**Objectifs :** Catalogue, stock saisonnier, ventes, réassort.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Achats, Fournisseurs, Clients, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Matériaux de construction

**Description :** Négoce de matériaux pour chantier et bâtiment (ciment, bois, fer, etc.).  
**Objectifs :** Devis, stock volumineux, ventes, livraisons, éventuellement engins et chantiers.  
**Modules recommandés :** POS, Produits, Stock, Devis, Facturation, Clients, Fournisseurs, Achats, Transport, Livraisons, Engins, Chantiers, Paiements, Documents, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Quincaillerie

**Description :** Commerce d’outillage, quincaillerie, bricolage et articles techniques.  
**Objectifs :** Catalogue dense, stock, ventes comptoir, clients professionnels éventuels.  
**Modules recommandés :** POS, Caisse, Produits, Stock, Clients, Achats, Fournisseurs, Facturation, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Garage

**Description :** Établissement de réparation et d’entretien de véhicules.  
**Objectifs :** Accueillir les véhicules, suivre les réparations, gérer pièces, facturer, encaisser.  
**Modules recommandés :** POS, Atelier, Réparations, Pièces, Stock, Clients, Facturation, Paiements, Planning, Documents, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Atelier mécanique

**Description :** Atelier orienté maintenance mécanique, machines ou équipements.  
**Objectifs :** Ordres de travail, pièces, planning, facturation, suivi clients.  
**Modules recommandés :** Atelier, Maintenance, Pièces, Stock, Clients, Planning, Facturation, Paiements, Documents, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Hôtel

**Description :** Établissement d’hébergement avec chambres et services associés.  
**Objectifs :** Réserver, gérer les chambres, encaisser, suivre clients et services annexes.  
**Modules recommandés :** Réservations, Chambres, POS, Caisse, Clients, CRM, Paiements, Facturation, Planning, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Maison d'hôtes

**Description :** Hébergement à échelle plus petite, souvent avec accueil personnalisé.  
**Objectifs :** Réservations, occupation des chambres, encaissement, relation client.  
**Modules recommandés :** Réservations, Chambres, Clients, Paiements, Facturation, Planning, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Salon de coiffure

**Description :** Prestations de coiffure avec rendez-vous et vente éventuelle de produits.  
**Objectifs :** Planning, réservations, clients, caisse, stock produits.  
**Modules recommandés :** Réservations, Planning, POS, Caisse, Clients, CRM, Produits, Stock, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Institut de beauté

**Description :** Soins esthétiques, beauté et bien-être.  
**Objectifs :** Rendez-vous, fiches clients, ventes de soins/produits, stock.  
**Modules recommandés :** Réservations, Planning, Clients, CRM, POS, Caisse, Produits, Stock, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Cabinet médical

**Description :** Structure de consultations médicales.  
**Objectifs :** Patients, rendez-vous, dossiers, facturation des actes, documents.  
**Modules recommandés :** Patients, Dossiers médicaux, Réservations, Planning, Facturation, Paiements, Documents, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Cabinet dentaire

**Description :** Cabinet de soins dentaires.  
**Objectifs :** Patients, rendez-vous, dossiers, actes, facturation.  
**Modules recommandés :** Patients, Dossiers médicaux, Réservations, Planning, Facturation, Paiements, Documents, Stock, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Clinique

**Description :** Structure de soins plus large, parfois pluridisciplinaire.  
**Objectifs :** Patients, planning, dossiers, facturation, reporting consolidé.  
**Modules recommandés :** Patients, Dossiers médicaux, Réservations, Planning, Facturation, Paiements, Documents, RH, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### École privée

**Description :** Établissement scolaire privé.  
**Objectifs :** Élèves/inscriptions, planning, facturation des frais, documents, communication.  
**Modules recommandés :** Clients, Réservations, Planning, Facturation, Paiements, Documents, RH, Notifications, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Centre de formation

**Description :** Organisme proposant des formations professionnelles ou continues.  
**Objectifs :** Sessions, inscriptions, planning formateurs, facturation, documents.  
**Modules recommandés :** Clients, CRM, Planning, Réservations, Facturation, Paiements, Documents, RH, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Salle de sport

**Description :** Club de fitness ou salle de sport avec abonnements et cours.  
**Objectifs :** Abonnements, membres, planning des cours, caisse annexes, présence.  
**Modules recommandés :** Abonnements, Clients, CRM, Réservations, Planning, Présence, POS, Caisse, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Agence immobilière

**Description :** Agence de transaction, location ou gestion immobilière.  
**Objectifs :** Biens, clients, rendez-vous, documents, suivi commercial.  
**Modules recommandés :** CRM, Clients, Réservations, Planning, Documents, Facturation, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Société de services

**Description :** Entreprise de prestations de services (conseil, maintenance, assistance, etc.).  
**Objectifs :** Clients, devis, planning interventions, facturation, suivi.  
**Modules recommandés :** CRM, Clients, Devis, Facturation, Planning, Documents, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Société de transport

**Description :** Entreprise de transport de personnes ou de marchandises.  
**Objectifs :** Courses/livraisons, planning, flotte, clients, facturation.  
**Modules recommandés :** Transport, Livraisons, Planning, Clients, CRM, Facturation, Paiements, Documents, Maintenance, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Agriculture

**Description :** Exploitation agricole, vente de production, suivi d’activité de proximité.  
**Objectifs :** Stocks/produits, ventes, clients, éventuellement engins et maintenance.  
**Modules recommandés :** Produits, Stock, POS, Caisse, Clients, Achats, Fournisseurs, Engins, Maintenance, Paiements, Rapports, Tableaux de bord, Utilisateurs, Permissions, Paramètres

#### Autres

**Description :** Activité non listée explicitement, composée librement à partir du catalogue de modules.  
**Objectifs :** Permettre à tout métier d’entrer sur la plateforme sans attendre une fiche activité dédiée.  
**Modules recommandés :** Tableaux de bord, Utilisateurs, Permissions, Paramètres, puis sélection libre selon le besoin (POS, Stock, CRM, Facturation, etc.)

---

## 3. Catalogue officiel des modules

### 3.1 Vue d’ensemble des modules

| Module | Priorité plateforme | Famille |
|--------|---------------------|---------|
| POS | Essentiel (selon activité) | Ventes |
| Caisse | Essentiel (selon activité) | Ventes |
| Ventes | Recommandé | Ventes |
| Produits | Essentiel (selon activité) | Catalogue |
| Stock | Essentiel (selon activité) | Logistique |
| Achats | Recommandé | Logistique |
| Fournisseurs | Recommandé | Logistique |
| Clients | Recommandé | Relation |
| CRM | Optionnel | Relation |
| Facturation | Recommandé | Finance |
| Paiements | Essentiel (selon activité) | Finance |
| Devis | Optionnel | Finance |
| Comptabilité | Optionnel | Finance |
| Tables | Recommandé (restauration) | Métier |
| Cuisine | Recommandé (restauration) | Métier |
| Réservations | Recommandé (selon activité) | Métier |
| Ordonnances | Essentiel (pharmacie) | Métier |
| Patients | Essentiel (santé) | Métier |
| Dossiers médicaux | Essentiel (santé) | Métier |
| Chambres | Essentiel (hébergement) | Métier |
| Atelier | Essentiel (garage/atelier) | Métier |
| Réparations | Recommandé (garage) | Métier |
| Pièces | Recommandé (garage/atelier) | Métier |
| Engins | Optionnel | Métier |
| Chantiers | Optionnel | Métier |
| Maintenance | Optionnel | Métier |
| Transport | Optionnel | Logistique |
| Livraisons | Optionnel | Logistique |
| Planning | Recommandé | Organisation |
| Présence | Optionnel | RH |
| RH | Optionnel | RH |
| Salaires | Optionnel | RH |
| Abonnements | Optionnel | Relation |
| Documents | Recommandé | Transversal |
| Notifications | Optionnel | Transversal |
| Rapports | Recommandé | Pilotage |
| Tableaux de bord | Essentiel | Pilotage |
| Utilisateurs | Essentiel | Plateforme |
| Permissions | Essentiel | Plateforme |
| Paramètres | Essentiel | Plateforme |
| Abonnements plateforme | Optionnel | Plateforme |

> **Note :** « Abonnements » (métier, ex. salle de sport) et la gestion d’abonnement à la plateforme GreenPOS sont des concepts distincts. Le second pourra être formalisé ultérieurement.

---

### 3.2 Fiches modules

#### POS

- **Objectif :** Permettre l’encaissement et la prise de commande en point de vente.  
- **Description :** Module de vente assistée : sélection des articles/services, panier, total, finalisation de la vente.  
- **Activités compatibles :** Épicerie, Supermarché, Boutique, Restaurant, Snack, Café, Pâtisserie, Boulangerie, Pharmacie, Parapharmacie, Librairie, Papeterie, Quincaillerie, Matériaux de construction, Garage, Hôtel, Salon de coiffure, Institut de beauté, Salle de sport, Agriculture, Autres.  
- **Dépendances éventuelles :** Produits (recommandée), Paiements (recommandée), Caisse (souvent associée).  
- **Priorité :** Essentiel pour les activités de vente comptoir ; Optionnel sinon.

#### Caisse

- **Objectif :** Gérer le point d’encaissement et les opérations de caisse.  
- **Description :** Ouverture/fermeture, encaissements, suivi des mouvements de caisse liés aux ventes.  
- **Activités compatibles :** Toutes activités avec vente physique.  
- **Dépendances éventuelles :** POS, Paiements.  
- **Priorité :** Essentiel dès qu’un POS est actif.

#### Ventes

- **Objectif :** Suivre l’historique et le pilotage des ventes.  
- **Description :** Consultation des transactions, analyses de vente, suivi commercial au-delà de l’acte d’encaissement.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** POS ou Facturation.  
- **Priorité :** Recommandé.

#### Produits

- **Objectif :** Gérer le catalogue d’articles ou de services vendables.  
- **Description :** Création et maintenance des produits/services, prix, catégories, identifiants de vente.  
- **Activités compatibles :** Toutes activités commercialisant des articles ou services catalogue.  
- **Dépendances éventuelles :** Aucune obligatoire.  
- **Priorité :** Essentiel pour le commerce ; Recommandé ailleurs.

#### Stock

- **Objectif :** Connaître et maîtriser les quantités disponibles.  
- **Description :** Niveaux de stock, mouvements, alertes de seuil bas, cohérence avec les ventes et achats.  
- **Activités compatibles :** Commerce, restauration, pharmacie, garage, matériaux, agriculture, etc.  
- **Dépendances éventuelles :** Produits (recommandée).  
- **Priorité :** Essentiel dès qu’il y a inventaire physique.

#### Achats

- **Objectif :** Gérer les approvisionnements.  
- **Description :** Besoins d’achat, commandes fournisseurs, réceptions liées au stock.  
- **Activités compatibles :** Commerce, restauration, pharmacie, matériaux, garage, agriculture.  
- **Dépendances éventuelles :** Fournisseurs, Stock, Produits.  
- **Priorité :** Recommandé.

#### Fournisseurs

- **Objectif :** Gérer le référentiel des fournisseurs.  
- **Description :** Fiches fournisseurs, coordonnées, historique d’approvisionnement.  
- **Activités compatibles :** Toutes activités achatantes.  
- **Dépendances éventuelles :** Aucune obligatoire ; souvent liée à Achats.  
- **Priorité :** Recommandé.

#### Clients

- **Objectif :** Gérer le référentiel clients.  
- **Description :** Fiches clients, historique d’interaction ou d’achat selon modules actifs.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Aucune obligatoire.  
- **Priorité :** Recommandé.

#### CRM

- **Objectif :** Suivre la relation commerciale au-delà de la simple fiche client.  
- **Description :** Opportunités, suivi commercial, interactions, segmentation simple.  
- **Activités compatibles :** Boutique, services, immobilier, formation, B2B, etc.  
- **Dépendances éventuelles :** Clients.  
- **Priorité :** Optionnel.

#### Facturation

- **Objectif :** Émettre et suivre les factures.  
- **Description :** Création de factures, suivi des montants dus, lien avec clients et paiements.  
- **Activités compatibles :** Services, garage, matériaux, santé, formation, hôtel, B2B.  
- **Dépendances éventuelles :** Clients ; Paiements (recommandée).  
- **Priorité :** Recommandé.

#### Paiements

- **Objectif :** Enregistrer les règlements.  
- **Description :** Modes de paiement, encaissement, suivi des paiements liés aux ventes ou factures.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** POS et/ou Facturation.  
- **Priorité :** Essentiel dès qu’il y a encaissement.

#### Devis

- **Objectif :** Proposer des offres commerciales avant commande.  
- **Description :** Création et suivi de devis, transformation éventuelle en vente/facture.  
- **Activités compatibles :** Matériaux, services, garage, construction, agence, etc.  
- **Dépendances éventuelles :** Clients, Produits ; Facturation (souvent liée).  
- **Priorité :** Optionnel / Recommandé selon activité.

#### Comptabilité

- **Objectif :** Offrir une vision comptable simplifiée ou préparatoire.  
- **Description :** Suivi comptable de base lié aux mouvements financiers ; hors comptabilité générale avancée en V1.  
- **Activités compatibles :** Toutes (maturité variable).  
- **Dépendances éventuelles :** Facturation, Paiements, Ventes.  
- **Priorité :** Optionnel.

#### Tables

- **Objectif :** Gérer le plan de salle et l’occupation des tables.  
- **Description :** Statut des tables, attribution, rotation en salle.  
- **Activités compatibles :** Restaurant, Café (selon modèle), Snack (si service sur place).  
- **Dépendances éventuelles :** POS (souvent), Réservations (optionnelle).  
- **Priorité :** Recommandé / Essentiel en restauration traditionnelle.

#### Cuisine

- **Objectif :** Transmettre et suivre les préparations en cuisine.  
- **Description :** Tickets cuisine, états de préparation, fluidité salle/cuisine.  
- **Activités compatibles :** Restaurant, Snack, Café (si applicable), Pâtisserie (production).  
- **Dépendances éventuelles :** POS ; Tables (souvent en restaurant).  
- **Priorité :** Recommandé en restauration.

#### Réservations

- **Objectif :** Planifier les réservations de ressources (tables, chambres, créneaux, etc.).  
- **Description :** Prise de réservation, calendrier, confirmation, no-show selon règles métier.  
- **Activités compatibles :** Restaurant, Hôtel, Maison d’hôtes, Salon, Institut, Santé, Sport, Formation, etc.  
- **Dépendances éventuelles :** Clients (recommandée) ; Chambres/Tables/Planning selon contexte.  
- **Priorité :** Recommandé selon activité.

#### Ordonnances

- **Objectif :** Traiter les ordonnances en officine.  
- **Description :** Saisie/suivi des ordonnances, lien avec délivrance et stock médicaments.  
- **Activités compatibles :** Pharmacie.  
- **Dépendances éventuelles :** Patients, Produits, Stock, POS.  
- **Priorité :** Essentiel en pharmacie.

#### Patients

- **Objectif :** Gérer le référentiel patients.  
- **Description :** Fiches patients, informations de suivi nécessaires aux soins ou à la délivrance.  
- **Activités compatibles :** Pharmacie, Cabinet médical, Cabinet dentaire, Clinique.  
- **Dépendances éventuelles :** Aucune obligatoire ; liée à Dossiers médicaux / Ordonnances.  
- **Priorité :** Essentiel en santé.

#### Dossiers médicaux

- **Objectif :** Centraliser le suivi médical autorisé dans le périmètre produit.  
- **Description :** Notes, historiques de soins, documents associés au patient (dans les limites définies métier/légales).  
- **Activités compatibles :** Cabinet médical, Cabinet dentaire, Clinique.  
- **Dépendances éventuelles :** Patients.  
- **Priorité :** Essentiel en structures de soins.

#### Chambres

- **Objectif :** Gérer le parc de chambres et leur disponibilité.  
- **Description :** États des chambres, occupation, lien avec réservations et facturation.  
- **Activités compatibles :** Hôtel, Maison d’hôtes.  
- **Dépendances éventuelles :** Réservations.  
- **Priorité :** Essentiel en hébergement.

#### Atelier

- **Objectif :** Organiser le travail d’atelier.  
- **Description :** Ordres de travail, files d’attente atelier, suivi d’avancement.  
- **Activités compatibles :** Garage, Atelier mécanique.  
- **Dépendances éventuelles :** Clients ; Réparations / Maintenance selon contexte.  
- **Priorité :** Essentiel pour garage/atelier.

#### Réparations

- **Objectif :** Suivre les interventions de réparation.  
- **Description :** Diagnostic, travaux, pièces utilisées, clôture de réparation.  
- **Activités compatibles :** Garage, Atelier mécanique.  
- **Dépendances éventuelles :** Atelier, Pièces, Clients.  
- **Priorité :** Recommandé / Essentiel en garage.

#### Pièces

- **Objectif :** Gérer les pièces détachées.  
- **Description :** Catalogue pièces, disponibilité, consommation sur interventions.  
- **Activités compatibles :** Garage, Atelier mécanique, Maintenance.  
- **Dépendances éventuelles :** Stock (recommandée), Produits.  
- **Priorité :** Recommandé.

#### Engins

- **Objectif :** Suivre le parc d’engins ou d’équipements.  
- **Description :** Inventaire des engins, affectation, état, lien éventuel avec maintenance et chantiers.  
- **Activités compatibles :** Matériaux de construction, Agriculture, Transport, BTP.  
- **Dépendances éventuelles :** Maintenance (optionnelle), Chantiers (optionnelle).  
- **Priorité :** Optionnel.

#### Chantiers

- **Objectif :** Suivre les chantiers et leur avancement.  
- **Description :** Fiches chantier, ressources, suivi d’activité liée au terrain.  
- **Activités compatibles :** Matériaux de construction, Construction, Services terrain.  
- **Dépendances éventuelles :** Clients, Engins, Documents, Facturation.  
- **Priorité :** Optionnel.

#### Maintenance

- **Objectif :** Planifier et suivre la maintenance préventive/corrective.  
- **Description :** Interventions de maintenance sur équipements, engins ou installations.  
- **Activités compatibles :** Atelier, Transport, Agriculture, Industrie légère, Hôtellerie (équipements).  
- **Dépendances éventuelles :** Engins ou Pièces selon contexte ; Planning.  
- **Priorité :** Optionnel.

#### Transport

- **Objectif :** Organiser les opérations de transport.  
- **Description :** Courses, tournées, affectations, suivi des transports.  
- **Activités compatibles :** Société de transport, Matériaux, Commerce avec livraison.  
- **Dépendances éventuelles :** Livraisons (souvent), Planning, Clients.  
- **Priorité :** Optionnel / Recommandé selon activité.

#### Livraisons

- **Objectif :** Suivre les livraisons aux clients.  
- **Description :** Préparation, expédition, statut de livraison, preuve de remise éventuelle.  
- **Activités compatibles :** Commerce, Matériaux, Transport, Services.  
- **Dépendances éventuelles :** Clients, Ventes/Facturation, Transport (optionnelle).  
- **Priorité :** Optionnel.

#### Planning

- **Objectif :** Organiser le temps (équipes, ressources, rendez-vous).  
- **Description :** Calendriers, créneaux, affectations horaires.  
- **Activités compatibles :** Santé, Beauté, Sport, Formation, Services, Transport, Garage, Hôtel.  
- **Dépendances éventuelles :** Réservations (souvent liées).  
- **Priorité :** Recommandé.

#### Présence

- **Objectif :** Suivre la présence des personnes (équipe ou membres).  
- **Description :** Pointage, présence aux sessions/cours, suivi simple d’assiduité.  
- **Activités compatibles :** Salle de sport, Formation, RH interne.  
- **Dépendances éventuelles :** Clients ou RH selon cas ; Planning.  
- **Priorité :** Optionnel.

#### RH

- **Objectif :** Gérer les informations essentielles liées aux collaborateurs.  
- **Description :** Référentiel employés, organisation simple des équipes (hors paie avancée).  
- **Activités compatibles :** Toutes (selon taille).  
- **Dépendances éventuelles :** Utilisateurs (possible lien conceptuel) ; Planning, Présence, Salaires.  
- **Priorité :** Optionnel.

#### Salaires

- **Objectif :** Préparer ou suivre les éléments de rémunération.  
- **Description :** Suivi salarial de base ; hors moteur de paie complexe en V1.  
- **Activités compatibles :** Toutes (maturité).  
- **Dépendances éventuelles :** RH, Présence.  
- **Priorité :** Optionnel.

#### Abonnements

- **Objectif :** Gérer les formules récurrentes (membres, forfaits).  
- **Description :** Souscriptions, renouvellements, statuts d’abonnement.  
- **Activités compatibles :** Salle de sport, Formation, Services récurrents, Clubs.  
- **Dépendances éventuelles :** Clients, Paiements.  
- **Priorité :** Optionnel / Essentiel en sport membership.

#### Documents

- **Objectif :** Centraliser les documents métier.  
- **Description :** Stockage et rattachement de documents (tickets, pièces jointes, dossiers).  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Selon module source (Patients, Facturation, Chantiers…).  
- **Priorité :** Recommandé.

#### Notifications

- **Objectif :** Alerter les utilisateurs sur les événements importants.  
- **Description :** Alertes stock, rappels de rendez-vous, notifications internes.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Modules producteurs d’événements.  
- **Priorité :** Optionnel.

#### Rapports

- **Objectif :** Produire des analyses et exports de suivi.  
- **Description :** Rapports de ventes, stock, activité, performance par boutique.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Modules sources de données.  
- **Priorité :** Recommandé.

#### Tableaux de bord

- **Objectif :** Offrir une vue synthétique de pilotage.  
- **Description :** Indicateurs clés après installation de l’espace de travail et au quotidien.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Modules actifs (contenu variable).  
- **Priorité :** Essentiel.

#### Utilisateurs

- **Objectif :** Gérer les personnes ayant accès à l’entreprise.  
- **Description :** Création, activation, rattachement des utilisateurs à l’entreprise / boutiques.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Permissions.  
- **Priorité :** Essentiel.

#### Permissions

- **Objectif :** Contrôler ce que chaque rôle peut faire.  
- **Description :** Droits d’accès aux modules et actions sensibles.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Utilisateurs.  
- **Priorité :** Essentiel.

#### Paramètres

- **Objectif :** Configurer l’entreprise et les boutiques.  
- **Description :** Paramètres généraux, préférences, réglages d’exploitation.  
- **Activités compatibles :** Toutes.  
- **Dépendances éventuelles :** Aucune obligatoire.  
- **Priorité :** Essentiel.

---

### 3.3 Matrice indicative Activité × Modules essentiels

Cette matrice ne remplace pas les fiches : elle aide à visualiser le cœur recommandé.

| Activité | Modules souvent essentiels |
|----------|----------------------------|
| Épicerie / Supermarché / Boutique | POS, Caisse, Produits, Stock, Paiements, Tableaux de bord |
| Restaurant | POS, Tables, Cuisine, Caisse, Produits, Stock, Paiements |
| Snack / Café / Boulangerie / Pâtisserie | POS, Caisse, Produits, Stock, Paiements |
| Pharmacie | POS, Produits, Stock, Ordonnances, Patients, Paiements |
| Parapharmacie / Librairie / Papeterie / Quincaillerie | POS, Produits, Stock, Paiements |
| Matériaux de construction | Produits, Stock, Devis, Facturation, Clients, Livraisons |
| Garage / Atelier | Atelier, Réparations, Pièces, Clients, Facturation |
| Hôtel / Maison d’hôtes | Réservations, Chambres, Clients, Paiements |
| Salon / Institut | Réservations, Planning, Clients, POS/Caisse |
| Santé (cabinets / clinique) | Patients, Dossiers médicaux, Planning, Facturation |
| École / Formation | Clients, Planning, Facturation, Documents |
| Salle de sport | Abonnements, Clients, Planning, Paiements |
| Immobilier / Services | CRM, Clients, Planning, Documents, Facturation |
| Transport | Transport, Livraisons, Planning, Clients, Facturation |
| Agriculture | Produits, Stock, POS (si vente), Engins (si parc) |
| Autres | Tableaux de bord, Utilisateurs, Permissions, Paramètres + modules choisis |

---

## 4. Principes

Les principes suivants gouvernent l’usage de ce catalogue :

1. **Indépendance des modules**  
   Chaque module porte une responsabilité métier claire. Il peut être compris et spécifié pour lui-même.

2. **Évolution séparée**  
   Un module peut évoluer (enrichissements, corrections, améliorations d’usage) sans imposer une refonte des autres modules.

3. **Extension sans modifier le cœur**  
   De nouveaux modules et de nouvelles activités peuvent être ajoutés sans remettre en cause les fondations GreenPOS (compte, entreprise, boutiques, isolation, activation).

4. **Dépendances explicites uniquement**  
   Certaines dépendances sont autorisées (ex. Ordonnances → Patients + Stock), mais elles doivent toujours être :
   - déclarées ;
   - compréhensibles pour le client ;
   - limitées au strict nécessaire.

5. **Liberté d’activation**  
   Les listes « recommandées » guident ; elles n’obligent pas. Le client active ce dont il a besoin.

6. **Cohérence avec les principes produit**  
   Ce catalogue respecte `02_PRODUCT_PRINCIPLES.md` : isolation des entreprises, multi-boutiques, modularité, simplicité.

7. **Priorisation réaliste**  
   Tout n’est pas Essentiel. La V1 devra choisir un sous-ensemble ; ce catalogue reste la carte complète de référence.

---

## 5. Conclusion

Ce document constitue le **catalogue officiel des activités et des modules** de GreenPOS.

Il servira de **base officielle** à :

- la rédaction des spécifications fonctionnelles ;
- la priorisation de la V1 et des versions suivantes ;
- la conception des parcours d’onboarding (choix d’activité → sélection de modules) ;
- l’ajout futur de nouvelles activités et de nouveaux modules.

Aucun module listé ici n’est automatiquement inclus dans la V1.  
La V1 sélectionnera un périmètre restreint, mais **aucune spécification fonctionnelle ne devra inventer une activité ou un module hors de cette référence** sans mise à jour préalable du présent catalogue.

**Prochaine étape documentaire recommandée :** définir le périmètre V1 (activités retenues + premier module métier + modules plateforme obligatoires) puis rédiger les spécifications fonctionnelles correspondantes.

---

*GreenPOS v3 — Document produit officiel — 03_ACTIVITIES_AND_MODULES_CATALOG.md*
