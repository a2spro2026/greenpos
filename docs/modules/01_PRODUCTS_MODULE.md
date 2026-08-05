# GreenPOS v3 — Module Produits

**Document officiel**  
**Version :** 3.0  
**Statut :** Spécification fonctionnelle de référence  
**Module :** Produits  
**Public :** Équipes Produit, UX/UI, Développement  
**Documents liés :** `01_PRODUCT_BLUEPRINT.md`, `02_PRODUCT_PRINCIPLES.md`, `03_ACTIVITIES_AND_MODULES_CATALOG.md`, `04.1_ONBOARDING_AND_WORKSPACE.md`, `04.2_DASHBOARD.md`, `05_DESIGN_SYSTEM.md`, `06_ROLES_AND_PERMISSIONS.md`

---

## 1. Vision du module

Le module **Produits** est le référentiel catalogue de GreenPOS.

Il permet à chaque entreprise de définir, organiser et maintenir les articles et services qu’elle commercialise ou consomme, dans le respect de :

- l’activité choisie ;  
- des boutiques de l’entreprise ;  
- des modules activés ;  
- des rôles et permissions.

Dans GreenPOS, le POS n’est qu’un module parmi d’autres.  
Le module Produits, lui, est une **brique transverse** : sans catalogue fiable, il n’y a ni vente cohérente, ni stock fiable, ni facturation exacte, ni reporting pertinent.

### Promesse

> Donner à chaque entreprise un catalogue clair, structuré et activable, adapté à son métier — sans imposer de complexité inutile.

---

## 2. Objectifs métier

### 2.1 Objectifs principaux

- Créer et maintenir un catalogue produit / service.  
- Garantir des prix, taxes et identifiants fiables à la vente.  
- Préparer le lien avec Stock, POS, Achats, Facturation et Rapports.  
- Adapter le catalogue à l’activité (épicerie, restaurant, pharmacie, matériaux…).  
- Permettre une recherche rapide en situation réelle (caisse, réassort, devis).

### 2.2 Objectifs d’expérience

- Ajouter un produit en peu d’étapes.  
- Retrouver un produit instantanément (nom, SKU, code-barres).  
- Comprendre immédiatement le statut d’un produit (actif, inactif, archivé).  
- Éviter les doublons et les ambiguïtés de prix.

### 2.3 Objectifs de qualité

- Données catalogue cohérentes entre boutiques d’une même entreprise (avec éventuelles spécificités locales).  
- Isolation totale : le catalogue d’une entreprise n’est jamais visible d’une autre.  
- Permissions respectées (Owner/Manager vs Caissier lecture limitée, etc.).

---

## 3. Périmètre fonctionnel

### 3.1 Inclus dans le module Produits

- Gestion des produits et services  
- Types de produits  
- Catégories / sous-catégories  
- Unités de mesure  
- Marques  
- Association fournisseurs (référentiel lié)  
- SKU / références internes  
- Codes-barres / QR  
- Prix d’achat et de vente  
- Taxes  
- Remises catalogue (règles simples)  
- Variantes  
- Packs / compositions (niveau fonctionnel)  
- Images  
- Statuts  
- Recherche, filtres, tris  
- Import / export  
- Validation des fiches  
- Interactions avec modules dépendants  

### 3.2 Hors périmètre strict (autres modules)

- Quantités en stock et mouvements → **Stock**  
- Réceptions et commandes fournisseurs → **Achats**  
- Encaissement panier → **POS / Caisse / Ventes / Paiements**  
- Émission de factures → **Facturation**  
- Ordonnances / patients → **Ordonnances / Patients** (pharmacie)  
- Tables / cuisine → modules restaurant  

Le module Produits **fournit le référentiel** ; il ne remplace pas ces modules.

### 3.3 Activation

Selon le catalogue officiel :

- Priorité : **Essentiel** pour le commerce ; **Recommandé** ailleurs.  
- Dépendances : aucune obligatoire pour démarrer un catalogue simple.  
- Souvent associé à : Stock, POS, Achats, Fournisseurs, Facturation.

Si le module Produits n’est pas activé : pas d’accès catalogue, et les modules dépendants qui l’exigent doivent l’indiquer explicitement.

---

## 4. Concepts fondamentaux

### 4.1 Produit

Un **produit** est une entité vendable et/ou consommable du catalogue, rattachée à une **entreprise**.

Il peut être :

- vendu via POS / Facturation / Devis ;  
- suivi en stock (si type et module Stock le permettent) ;  
- acheté auprès de fournisseurs ;  
- composé d’autres produits (pack / recette selon activité).

### 4.2 Périmètre organisationnel

- Le catalogue appartient à l’**entreprise**.  
- Certaines informations peuvent être **communes** à toutes les boutiques.  
- Certaines informations peuvent être **locales** à une boutique (prix local, disponibilité, statut local), selon règles configurées.  
- Aucune donnée catalogue ne traverse une autre entreprise.

### 4.3 Fiche produit

La fiche produit est l’écran de vérité d’un article : identité, classification, prix, taxes, variantes, médias, statuts, liaisons.

---

## 5. Types de produits

Le type détermine les comportements attendus (stockable, vendable, composable, etc.).

### 5.1 Produit physique

- Article tangible.  
- Généralement stockable.  
- Vendable à l’unité ou selon unité de mesure.  
- Ex. : bouteille d’eau, vis, livre, médicament OTC.

### 5.2 Service

- Prestations non stockables (ou non gérées en quantité inventoriable classique).  
- Vendable.  
- Ex. : coupe homme, diagnostic atelier, livraison, consultation (selon activité).

### 5.3 Variante (parent / enfants)

- Un produit parent avec déclinaisons (taille, couleur, format, dosage…).  
- Chaque variante enfant peut avoir SKU, code-barres, prix et stock propres.  
- Le parent sert à la navigation catalogue ; la vente porte en général sur la variante.

### 5.4 Pack / lot / composition

- Ensemble de produits vendus ensemble.  
- Peut décrémenter plusieurs composants à la vente (si Stock actif et règle définie).  
- Ex. : panier cadeau, menu restaurant, kit matériaux.

### 5.5 Matière première / consommable

- Utilisé en production ou transformation.  
- Pas forcément vendu directement.  
- Ex. : farine (boulangerie), pièces détachées (garage), ingrédients (restaurant).

### 5.6 Produit numérique / licence (optionnel)

- Non physique.  
- Non stockable classiquement.  
- Utile pour certains services / formations / abonnements liés.

### 5.7 Règles liées au type

- Le type est obligatoire à la création.  
- Le changement de type après usage réel (ventes / stock) doit être restreint ou guidé (risque d’incohérence).  
- Un type non stockable ne doit pas exiger de gestion d’inventaire.

---

## 6. Catégories et sous-catégories

### 6.1 Objectif

Structurer le catalogue pour :

- navigation POS ;  
- filtres admin ;  
- reporting ;  
- recommandations d’activité.

### 6.2 Règles

- Catégories rattachées à l’entreprise.  
- Arborescence : catégorie → sous-catégories (profondeur limitée pour rester simple).  
- Un produit a au moins une catégorie principale.  
- Catégories additionnelles possibles (tags de classement) si cela reste clair.  
- Une catégorie vide reste autorisée (état normal en démarrage).  
- Suppression d’une catégorie : interdite si des produits y sont rattachés, ou réaffectation obligatoire.

### 6.3 Exemples selon activité

- Épicerie : Boissons / Épicerie salée / Hygiène  
- Restaurant : Entrées / Plats / Boissons / Menus  
- Pharmacie : OTC / Parapharmacie / Dispositifs  
- Matériaux : Ciment / Bois / Quincaillerie / Outillage  

---

## 7. Unités de mesure

### 7.1 Objectif

Exprimer la quantité de vente et/ou de stock.

### 7.2 Exemples

- Pièce (pce)  
- Kilogramme (kg)  
- Gramme (g)  
- Litre (L)  
- Mètre (m)  
- Mètre carré (m²)  
- Heure (h)  
- Colis / carton  

### 7.3 Règles

- Unité de vente obligatoire pour produit vendable.  
- Unité de stock peut être identique ou liée par coefficient (ex. carton = 12 pièces), si le besoin est activé.  
- Les conversions doivent être explicites ; pas de conversion implicite dangereuse.  
- Affichage cohérent dans POS, Stock, Achats, Facturation.

---

## 8. Marques

### 8.1 Objectif

Qualifier commercialement les produits et faciliter recherche / filtres.

### 8.2 Règles

- Marque optionnelle.  
- Référentiel de marques par entreprise.  
- Éviter les doublons (normalisation simple des libellés).  
- Filtrable dans la liste produits.

---

## 9. Fournisseurs associés

### 9.1 Objectif

Lier un produit à un ou plusieurs fournisseurs pour faciliter Achats et réassort.

### 9.2 Règles

- Association optionnelle au niveau Produits.  
- Un produit peut avoir un fournisseur principal + fournisseurs secondaires.  
- Référence fournisseur (code article fournisseur) stockable sur la liaison.  
- Prix d’achat peut être lié au fournisseur principal.  
- La gestion complète des commandes reste dans **Achats / Fournisseurs**.

---

## 10. Codes-barres et QR Codes

### 10.1 Objectif

Identification rapide à la caisse, en inventaire, en réception.

### 10.2 Règles

- Un produit (ou une variante) peut avoir un ou plusieurs codes.  
- Unicité du code-barres **au sein de l’entreprise**.  
- Formats courants acceptés (EAN, UPC, codes internes…).  
- QR Code peut porter une référence interne ou une URL métier interne (non technique côté spec).  
- Recherche POS prioritaire par scan code-barres.  
- En cas de doublon à l’import : rejet ou rapport d’erreur explicite.

---

## 11. Références internes (SKU)

### 11.1 Objectif

Référence stable de gestion interne.

### 11.2 Règles

- SKU unique par entreprise (obligatoire ou auto-généré selon paramètre).  
- Immutable autant que possible après premières ventes (ou modification contrôlée).  
- Visible dans listes, exports, tickets selon configuration.  
- Distinct du code-barres (même s’ils peuvent coïncider).

---

## 12. Prix d’achat et de vente

### 12.1 Prix d’achat

- Coût de revient d’approvisionnement.  
- Utilisé pour marges et Achats.  
- Peut varier selon fournisseur.  
- Visible selon permissions (souvent masqué au caissier).

### 12.2 Prix de vente

- Prix standard de vente TTC ou HT selon mode fiscal de l’entreprise.  
- Peut exister au niveau entreprise et/ou boutique.  
- Doit être le prix appliqué par défaut au POS, sauf remise / tarif spécial.

### 12.3 Marges

- Calcul indicatif : vente vs achat (selon taxes).  
- Affichage réservé aux rôles autorisés (Owner, Manager, Comptable…).

### 12.4 Règles

- Prix de vente obligatoire pour produit vendable actif.  
- Prix ≥ 0 (sauf cas promotionnels contrôlés).  
- Historique des changements de prix recommandé (audit / traçabilité).  
- Pas de prix « magique » différent entre écran catalogue et ticket.

---

## 13. Taxes

### 13.1 Objectif

Appliquer correctement la fiscalité à la vente / facturation.

### 13.2 Règles

- Chaque produit vendable a une taxe (ou profil de taxe) applicable.  
- Les taux disponibles dépendent des paramètres entreprise / pays.  
- Cohérence obligatoire avec Facturation et POS.  
- Affichage HT/TTC selon paramètre entreprise.  
- Modification de taxe = impact sur prochaines ventes ; pas de réécriture silencieuse de l’historique.

---

## 14. Remises

### 14.1 Remises catalogue

- Remise permanente ou temporaire sur fiche produit.  
- Pourcentage ou montant.  
- Période de validité optionnelle.  
- Visible clairement en caisse.

### 14.2 Remises opérationnelles

- Remise ponctuelle en caisse = permission spécifique (souvent Manager+).  
- Ne doit pas être confondue avec la remise catalogue.

### 14.3 Règles

- Cumuls de remises : règles simples et explicites (éviter les empilements opaques en V1).  
- Une remise ne doit pas produire un prix final négatif.  
- Traçabilité sur la vente.

---

## 15. Variantes

### 15.1 Attributs types

- Taille  
- Couleur  
- Format / conditionnement  
- Dosage / volume  
- Matière  
- Autres attributs définis par l’entreprise  

### 15.2 Règles

- Le parent regroupe ; les enfants sont vendables.  
- Chaque variante peut avoir : SKU, code-barres, prix, image, statut, stock.  
- Création assistée de matrices de variantes (ex. S/M/L × Rouge/Bleu).  
- Désactiver une variante ne désactive pas automatiquement tout le parent (et inversement : règles à confirmer à l’UI).  
- En POS : sélection du parent puis choix de variante, ou scan direct de la variante.

---

## 16. Images du produit

### 16.1 Objectif

Reconnaissance visuelle en caisse, catalogue, fiches.

### 16.2 Règles

- Image principale + galerie optionnelle.  
- Formats et poids raisonnables (contraintes UX : chargement rapide).  
- Image de variante possible.  
- Placeholder standard si aucune image.  
- Les images ne remplacent jamais le nom / SKU pour l’identification critique.

---

## 17. Statuts produit

| Statut | Sens | Effet typique |
|--------|------|----------------|
| **Actif** | Utilisable | Visible vente / achat selon droits |
| **Inactif** | Temporairement retiré | Masqué POS ; conservé en admin |
| **Archivé** | Fin de vie | Non proposé aux flux courants ; conservé pour historique |

### 17.1 Règles

- Seuls les produits actifs (et autorisés) apparaissent en POS par défaut.  
- Un produit avec stock restant peut être inactivé (blocage vente) tout en restant gérable en stock.  
- Archivage : pas de suppression destructive si historique de ventes existe.  
- Changement de statut auditable pour les rôles concernés.

---

## 18. Autres attributs de fiche (recommandés)

- Nom commercial (obligatoire)  
- Description courte / longue  
- Catégorie  
- Marque  
- Type  
- SKU  
- Codes-barres  
- Unité  
- Prix achat / vente  
- Taxe  
- Seuil d’alerte stock (si Stock ; souvent porté aussi par Stock)  
- Boutique(s) de disponibilité  
- Tags internes  
- Date de création / mise à jour  
- Utilisateur dernier modificateur  

Champs avancés selon activité (voir section 27).

---

## 19. Parcours fonctionnels principaux

### 19.1 Création d’un produit

1. Accéder au module Produits.  
2. Cliquer sur « Nouveau produit ».  
3. Choisir le type.  
4. Saisir les informations essentielles (nom, catégorie, SKU/code, prix, taxe).  
5. Ajouter optionnellement variantes, images, fournisseur, remises.  
6. Valider.  
7. Produit actif (ou brouillon/inactif selon option).

### 19.2 Modification

- Édition contrôlée par permissions.  
- Champs sensibles (prix, taxe, SKU) avec vigilance / confirmation si déjà vendu.

### 19.3 Désactivation / archivage

- Action explicite.  
- Impact annoncé (disparition POS, etc.).

### 19.4 Consultation liste

- Table dense, scannable, conforme Design System.  
- Actions de ligne : voir, modifier, désactiver, archiver (selon droits).

---

## 20. Règles de validation

### 20.1 Obligatoires (produit vendable)

- Nom  
- Type  
- Catégorie principale  
- Unité de vente  
- Prix de vente  
- Taxe applicable  
- Statut  
- SKU (ou génération automatique)

### 20.2 Conditionnelles

- Code-barres unique si renseigné  
- Variantes : au moins un attribut et une combinaison valide  
- Pack : au moins un composant  
- Service : pas d’exigence stock  

### 20.3 Messages

- Erreurs locales claires, proches des champs (Design System).  
- Pas de validation technique jargonisante.

---

## 21. Filtres et recherche

### 21.1 Recherche

Recherche unique portant sur :

- nom  
- SKU  
- code-barres  
- marque  
- description courte (option)

Doit rester rapide et tolérante (casse, accents autant que possible).

### 21.2 Filtres

- Statut  
- Type  
- Catégorie  
- Marque  
- Fournisseur  
- Boutique de disponibilité  
- Avec / sans image  
- Avec / sans stock (si module Stock)  
- Fourchette de prix  

### 21.3 Tri

- Nom  
- Date de mise à jour  
- Prix  
- SKU  
- Statut  

### 21.4 UX liste

- Densité confort / compact.  
- Colonnes prioritaires : Image, Nom, SKU, Catégorie, Prix, Statut.  
- Prix d’achat masqué selon rôle.

---

## 22. Imports et exports

### 22.1 Export

- Formats usuels tableurs.  
- Colonnes stables et documentées.  
- Filtre courant respecté (exporter de la sélection visible).  
- Permission **Exporter** requise.  
- Audit recommandé.

### 22.2 Import

- Modèle de fichier fourni.  
- Création et/ou mise à jour par SKU.  
- Rapport de résultats : succès / erreurs ligne à ligne.  
- Contrôle d’unicité SKU / codes-barres.  
- Permission **Importer** requise.  
- En V1 : privilégier un import simple et robuste plutôt qu’un moteur complexe.

### 22.3 Règles

- Aucun import ne traverse les entreprises.  
- Les erreurs n’interrompent pas nécessairement tout le fichier (stratégie à afficher clairement : stop global vs partiel).

---

## 23. Permissions liées au module

Alignement avec `06_ROLES_AND_PERMISSIONS.md`.

### 23.1 Actions module Produits

- Lire  
- Créer  
- Modifier  
- Supprimer / Archiver  
- Exporter  
- Importer  
- Configurer (catégories, unités, marques — selon délégation)  
- Voir prix d’achat  
- Modifier prix  

### 23.2 Matrice indicative

| Action | Owner | Manager | Caissier | Magasinier | Comptable | Commercial |
|--------|-------|---------|----------|------------|-----------|------------|
| Lire catalogue | Oui | Oui | Oui (limité) | Oui (limité) | Optionnel | Oui (limité) |
| Créer / Modifier | Oui | Oui | Non | Non* | Non | Non* |
| Voir prix achat | Oui | Oui | Non | Optionnel | Oui | Non |
| Modifier prix vente | Oui | Oui | Non | Non | Non | Non |
| Importer / Exporter | Oui | Oui/Optionnel | Non | Non | Export possible | Non |
| Archiver | Oui | Oui | Non | Non | Non | Non |

\*Sauf délégation / rôle personnalisé.

### 23.3 Règles UI

- Pas de bouton « Créer » pour un caissier.  
- Champs prix d’achat absents ou masqués sans permission.  
- Accès module absent si non autorisé / non activé.

---

## 24. Interactions avec les autres modules

### 24.1 Stock

- Un produit stockable apparaît dans Stock.  
- Les ventes décrémentent le stock du bon produit/variante.  
- Alertes rupture s’appuient sur le référentiel produit.  
- Ajustements stock ne modifient pas la fiche catalogue (sauf seuils si partagés).

### 24.2 POS / Caisse / Ventes

- Le POS lit uniquement produits actifs vendables.  
- Scan code-barres → produit/variante.  
- Prix / taxe / remise catalogue appliqués.  
- Ticket affiche le libellé commercial cohérent.

### 24.3 Achats / Fournisseurs

- Réassort basé sur produits + liaisons fournisseurs.  
- Prix d’achat de référence.  
- Réceptions liées au SKU.

### 24.4 Facturation / Devis / Paiements

- Lignes de documents s’appuient sur le catalogue (ou snapshots de prix au moment du document).  
- Descriptions et taxes cohérentes.

### 24.5 Rapports / Dashboard

- Top produits, CA par catégorie, ruptures.  
- Widgets Dashboard (produits en rupture, raccourci « Ajouter un produit »).  
- Aucun widget Produits si module inactif.

### 24.6 Paramètres / Utilisateurs

- Paramètres de taxe, devise, mode HT/TTC influencent l’affichage prix.  
- Permissions contrôlent chaque action.

---

## 25. États d’erreur et cas limites

| Situation | Comportement attendu |
|-----------|----------------------|
| SKU dupliqué | Refus + message clair |
| Code-barres dupliqué | Refus + message clair |
| Prix manquant sur produit actif vendable | Validation bloquante |
| Suppression d’un produit déjà vendu | Refus ; proposer archivage |
| Import ligne invalide | Rapport d’erreur détaillé |
| Module Stock absent | Masquer exigences stock ; permettre catalogue simple |
| Produit inactif scanné en caisse | Message « produit non disponible » |
| Variante incomplète | Impossible d’activer la vente de la combinaison |
| Perte d’image | Placeholder ; fiche reste valide |
| Accès sans permission | Accès refusé, pas de fuite d’information |

---

## 26. Règles métier transverses

1. Toute fiche produit appartient à une entreprise.  
2. L’unicité SKU / code-barres est scoping entreprise.  
3. Le prix affiché en vente est le prix applicable réel.  
4. Un produit archivé reste dans l’historique documentaire.  
5. Les modules dépendants consomment le catalogue ; ils ne le dupliquent pas.  
6. Les changements critiques (prix, taxe, statut) sont traçables.  
7. La simplicité prime : champs avancés progressifs, pas de formulaire interminable.  
8. Multi-boutiques : disponibilité locale explicite si utilisée.  
9. Multi-entreprises : zéro partage implicite.  
10. Conformité RBAC et Design System obligatoire.

---

## 27. Cas particuliers selon les activités

### 27.1 Épicerie / Boutique / Supermarché

- Fort volume d’articles.  
- Code-barres critique.  
- Catégories nombreuses.  
- Import catalogue fréquent.  
- Variants conditionnement (pack ×6, etc.).

### 27.2 Restaurant / Snack / Café / Boulangerie / Pâtisserie

- Produits = plats, boissons, formules.  
- Compositions / recettes (matières premières).  
- Disponibilité du jour (actif/inactif rapide).  
- Moins de code-barres, plus de sélection visuelle POS.  
- Packs menus.

### 27.3 Pharmacie / Parapharmacie

- Traçabilité renforcée.  
- Champs possibles : DCI, dosage, forme galénique (selon besoin).  
- Interaction future avec Ordonnances.  
- Vigilance sur droits et audit.  
- Certains articles non vendables librement selon règles métier.

### 27.4 Matériaux de construction / Quincaillerie

- Unités variées (m, m², tonne, pièce).  
- Variantes formats.  
- Lien fort Devis / Livraisons.  
- Références fournisseur importantes.  
- Prix B2B et remises commerciales.

### 27.5 Garage / Atelier

- Pièces détachées + services main-d’œuvre.  
- SKU pièces critiques.  
- Association éventuelle à Réparations / Atelier.  
- Services horaires.

### 27.6 Hôtel / Beauté / Sport / Services

- Beaucoup de services.  
- Produits annexes (boutique).  
- Abonnements éventuels (autre module) distincts du simple service catalogue.

### 27.7 Autres

- Composition libre du catalogue à partir des types standards.  
- Pas de champ métier imposé hors besoin.

---

## 28. Bonnes pratiques UX

Conformément à `05_DESIGN_SYSTEM.md`, `04.1` et `04.2` :

### 28.1 Liste produits

- Table claire, recherche dominante.  
- Filtres visibles et réinitialisables.  
- Statuts en badges cohérents.  
- Actions de ligne limitées.

### 28.2 Fiche produit

- Sections groupées : Identité → Classification → Prix & taxes → Variantes → Médias → Liaisons → Statut.  
- Champs essentiels d’abord ; avancés ensuite.  
- Sauvegarde explicite ; messages de succès discrets.  
- Éviter les formulaires interminables.

### 28.3 POS / usage terrain

- Résultats de recherche immédiatement actionnables.  
- Variantes sélectionnables sans friction.  
- Images utiles mais non bloquantes.

### 28.4 États vides

- Catalogue vide après onboarding : CTA « Ajouter le premier produit » / « Importer ».  
- Ton rassurant, jamais technique.

### 28.5 Accessibilité

- Contrastes, focus, labels, pas d’info par la seule couleur.  
- Cibles cliquables suffisantes.

### 28.6 Responsive

- Desktop : table dense.  
- Mobile : recherche + actions critiques + fiche lisible.  
- Pas d’identité différente.

---

## 29. Contenu Dashboard lié au module

Lorsque Produits est actif :

- raccourci « Ajouter un produit » ;  
- widget / alerte « Produits en rupture » (avec Stock) ;  
- éventuel compteur de produits actifs (pilotage).

Contenu adapté au rôle (Owner/Manager vs Caissier).

---

## 30. Critères d’acceptation (référence dev)

Le module Produits est considéré conforme si :

1. On peut créer un produit actif vendable avec prix et taxe.  
2. La recherche par nom / SKU / code-barres fonctionne.  
3. Les unicités entreprise sont respectées.  
4. Un caissier ne peut pas modifier le catalogue ni voir les prix d’achat.  
5. Un produit inactif n’apparaît pas en POS.  
6. Les variantes se vendent correctement.  
7. L’import produit un rapport d’erreurs clair.  
8. Aucune donnée catalogue ne fuit vers une autre entreprise.  
9. Stock/POS/Facturation consomment la même vérité catalogue.  
10. L’UI respecte le Design System (listes, fiches, badges, états).

---

## 31. Conclusion

Le module **Produits** est le référentiel catalogue officiel de GreenPOS.

Il doit être :

- simple à prendre en main ;  
- robuste pour la caisse et le stock ;  
- extensible selon les activités ;  
- strictement isolé par entreprise ;  
- gouverné par le RBAC ;  
- cohérent avec toute la documentation produit.

Cette spécification est la **référence fonctionnelle officielle** pour concevoir et développer le module Produits.  
Toute évolution (nouveaux types, tarifs avancés, multi-tarifs B2B complexes) devra mettre à jour ce document avant généralisation.

**Prochaine étape recommandée :** spécifier le module Stock (mouvements, seuils, cohérence ventes ↔ quantités) en s’appuyant sur ce référentiel Produits.

---

*GreenPOS v3 — Document fonctionnel officiel — modules/01_PRODUCTS_MODULE.md*
