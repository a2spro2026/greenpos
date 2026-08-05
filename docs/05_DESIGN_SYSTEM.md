# GreenPOS v3 — Design System officiel

**Document officiel**  
**Version :** 3.0  
**Statut :** Référence unique du design UI/UX  
**Public :** Équipes Produit, UX/UI, Développement et Direction  
**Documents sources :** `01_PRODUCT_BLUEPRINT.md`, `02_PRODUCT_PRINCIPLES.md`, `03_ACTIVITIES_AND_MODULES_CATALOG.md`, `04.1_ONBOARDING_AND_WORKSPACE.md`, `04.2_DASHBOARD.md`

---

## Préambule

Ce document définit le **Design System officiel de GreenPOS**.  
Il ne décrit pas un écran isolé : il définit les règles visuelles, interactionnelles et UX que **tous** les écrans, modules et parcours doivent respecter.

GreenPOS est une plateforme SaaS modulaire de gestion d’entreprise (ERP/POS). Le design doit donc servir un usage quotidien intensif : encaissement, stock, pilotage, multi-boutiques, multi-rôles — avec clarté, rapidité et confiance.

Toute maquette, prototype, revue d’interface ou évolution visuelle doit se conformer à ce document.  
En cas de conflit entre une préférence esthétique locale et ce Design System, **ce document prime**.

---

## 1. Principes de design

### 1.1 Finalité

Le design GreenPOS a pour but de :

- rendre la plateforme immédiatement compréhensible ;
- accélérer les tâches quotidiennes (surtout la caisse et le stock) ;
- renforcer la confiance (isolation entreprise, rôles, données) ;
- unifier l’expérience entre activités et modules ;
- rester premium sans devenir décoratif.

### 1.2 Principes fondateurs

1. **Simplicité opérationnelle** — une intention claire par écran ; peu de bruit.  
2. **Hiérarchie visuelle forte** — l’œil trouve d’abord le contexte, puis l’essentiel, puis l’action.  
3. **Cohérence multi-modules** — mêmes patterns partout ; le métier change le contenu, pas l’identité.  
4. **Modularité visible** — seuls les modules activés apparaissent ; l’UI reflète la composition réelle.  
5. **Contexte toujours explicite** — entreprise active, boutique active, rôle, module.  
6. **Densité maîtrisée** — informatif pour power users, jamais illisible.  
7. **Action avant décoration** — chaque élément doit aider à comprendre ou à agir.  
8. **Accessibilité non négociable** — contrastes, focus, clavier, lisibilité.  
9. **Progressivité** — onboarding guidé ; personnalisation ensuite, jamais obligatoire.  
10. **Intemporalité** — éviter les modes graphiques éphémères.

### 1.3 Ce que le design doit faire ressentir

- Professionnalisme  
- Rapidité  
- Simplicité  
- Confiance  
- Modernité  
- Maîtrise  

L’utilisateur doit pouvoir se dire :  
**« Je peux travailler toute la journée ici sans fatigue ni confusion. »**

---

## 2. Identité visuelle

### 2.1 Positionnement

GreenPOS n’est pas un logiciel de caisse basique, ni une suite ERP austère.  
Son identité se situe entre **outil premium de productivité** et **cockpit métier** :

- moderne et net ;  
- premium et retenu ;  
- professionnel et rassurant ;  
- adapté au terrain (magasin, restaurant, pharmacie, atelier…).

### 2.2 Ambiance générale

- Surfaces calmes, structure claire, contraste maîtrisé.  
- Sensation de contrôle et de stabilité.  
- Une identité « green » subtile : fraîcheur, croissance, confiance — jamais criarde.  
- Ni look gadget, ni look administratif daté.  
- Ni surcharge marketing dans l’outil de travail.

### 2.3 Promesse de marque dans l’UI

GreenPOS compose un système de gestion module par module.  
L’UI doit donc :

- montrer clairement ce qui est actif ;  
- ne jamais imposer visuellement ce qui n’est pas activé ;  
- conserver une identité unique quel que soit le métier (Restaurant, Pharmacie, Matériaux, etc.).

### 2.4 Logo et marque dans le produit

- Logo / monogramme lisible en petit format (sidebar, favicon conceptuel).  
- Nom « GreenPOS » toujours associé dans la navigation principale.  
- Signature secondaire discrète autorisée (« Business Platform », version).  
- Le logo ne doit pas écraser le contenu opérationnel.

---

## 3. Palette de couleurs

Les couleurs définissent des **rôles sémantiques**.  
Les valeurs exactes (hex) pourront être stabilisées dans les assets de marque, mais les rôles ci-dessous sont obligatoires.

### 3.1 Familles officielles

| Rôle | Intention | Usages typiques |
|------|-----------|-----------------|
| **Primaire** | Identité GreenPOS, confiance, action principale | Boutons primaires, états actifs, accents de navigation |
| **Secondaire** | Soutien structurel | Textes forts, éléments de hiérarchie, surfaces sombres de navigation |
| **Accent** | Point d’attention ponctuel | Highlights rares, détails de marque |
| **Succès** | Confirmation positive | Badges OK, toasts succès, états validés |
| **Erreur / Danger** | Échec, risque, destruction | Erreurs formulaire, suppressions, alertes critiques |
| **Avertissement** | Vigilance sans échec immédiat | Stock bas, caisse non clôturée |
| **Information** | Guidance neutre | Notices, aides, états informatifs |
| **Arrière-plan** | Toile de fond calme | Fond d’application |
| **Surface** | Conteneurs de contenu | Cartes, panneaux, topbar, modales |
| **Texte principal** | Lecture prolongée | Titres, valeurs, contenu |
| **Texte secondaire** | Soutien | Descriptions, métadonnées |
| **Bordures** | Structure discrète | Séparations, contours de cartes/champs |

### 3.2 Règles d’usage

- Peu de couleurs simultanées sur un même écran.  
- La sémantique prime sur l’esthétique (rouge ≠ décoratif).  
- La primaire ne doit pas peindre de grandes surfaces saturées.  
- Les modules métier n’inventent pas leur propre palette.  
- Mode clair et mode sombre partagent la même logique de rôles.

### 3.3 Mode sombre (statut)

Le Dark Mode est **officiel**.  
Il conserve la même identité : premium, sobre, lisible.  
Ce n’est pas un invert naïf, ni un thème saturé type « gaming ».

---

## 4. Typographie

### 4.1 Philosophie

Typographie **nette, professionnelle, hautement lisible**, optimisée pour :

- tableaux denses ;  
- chiffres de caisse ;  
- formulaires ;  
- dashboards.

L’expressivité décorative est secondaire. La clarté opérationnelle prime.

### 4.2 Hiérarchie obligatoire

1. **Titre de page** — annonce l’écran.  
2. **Sous-titre / description** — contextualise en une phrase.  
3. **Titre de section / carte** — structure le contenu.  
4. **Valeur / KPI** — chiffres scannables, poids fort.  
5. **Texte courant** — contenu principal.  
6. **Texte secondaire** — aides, métadonnées.  
7. **Légende / microcopy** — timestamps, notes, hints.

La hiérarchie doit rester lisible **même sans couleur**.

### 4.3 Règles de contenu textuel

- Titres courts, orientés objet métier (« Produits », « Ventes du jour »).  
- Pas de titres marketing dans l’outil (« Révolutionnez votre stock »).  
- Terminologie stable issue du glossaire produit (Entreprise, Boutique, Module, Caisse…).  
- Phrases courtes ; une idée par message.  
- Labels de formulaires toujours visibles (pas uniquement placeholder).

### 4.4 Chiffres et données

- Aligner les montants de façon cohérente.  
- Afficher clairement l’unité / devise lorsqu’elle est pertinente.  
- Les KPI doivent être immédiatement scannables.

---

## 5. Espacements

### 5.1 Système

L’interface s’appuie sur une **échelle d’espacement limitée et cohérente** (rythme régulier).  
Tous les composants (cartes, formulaires, listes, navigation) doivent partager ce rythme.

### 5.2 Règles

- **Marges de page** suffisantes : éviter l’effet collé aux bords.  
- **Respiration** autour des zones de décision (paiement, suppression, validation).  
- **Regroupement** : éléments liés proches ; groupes séparés clairement.  
- **Alignements** stricts : colonnes, actions, libellés.  
- Sur grands écrans, ne pas étirer indéfiniment les lignes de texte.

### 5.3 Densité

Deux densités conceptuelles sont autorisées :

- **Confort** — lecture et pilotage.  
- **Compact** — caisse, tableaux intensifs, power users.

La densité compacte reste lisible ; elle ne devient jamais illisible.

---

## 6. Icônes

### 6.1 Style

- Un seul style d’icônes pour toute la plateforme.  
- Trait clair, géométrie simple, lisibilité aux petites tailles.  
- Pas de mélange outline / filled / cartoon / 3D.

### 6.2 Usage

- Soutenir le texte, rarement le remplacer.  
- Une même action = une même icône partout (ajouter, éditer, supprimer, filtrer…).  
- Les actions critiques conservent un libellé.  
- Ne jamais transmettre une information par la seule icône + couleur.

### 6.3 Navigation

- Icônes de groupes et d’items de sidebar cohérentes.  
- L’icône active suit le même système que l’état de navigation.

---

## 7. Boutons

### 7.1 Variantes officielles

| Variante | Rôle |
|----------|------|
| **Primaire** | Action principale de l’écran / du flux |
| **Secondaire** | Action importante non dominante |
| **Discret / Ghost** | Action de moindre emphase |
| **Danger** | Action destructrice ou irréversible |
| **Désactivé** | Non disponible |
| **Chargement** | Action en cours |

### 7.2 Comportements

- Une seule action primaire dominante par zone de décision.  
- Libellés orientés action, stables dans toute l’app (« Enregistrer », « Valider », « Continuer »).  
- Feedback immédiat au clic.  
- État chargement : empêche le double envoi, évite les sauts de layout.  
- Danger toujours associé à une confirmation lorsque l’impact est élevé.  
- Si désactivé : expliquer pourquoi quand c’est possible.

### 7.3 Placement

- Dans formulaires et modales : convention stable (ex. Annuler à gauche / Primaire à droite), identique partout.  
- Ne pas inventer un style de bouton par module.

---

## 8. Champs de formulaire

### 8.1 Structure

- Label visible.  
- Champ.  
- Aide optionnelle.  
- Message d’erreur / succès local.

### 8.2 États

Par défaut · Focus · Rempli · Désactivé · Lecture seule · Erreur · Succès (parcimonie)

### 8.3 Règles

- Validations compréhensibles ; règles visibles avant l’échec quand c’est utile.  
- Messages d’erreur précis et proches du champ.  
- Ne pas perdre les saisies correctes en cas d’erreur.  
- Grouper les champs par sens métier.  
- Éviter les formulaires interminables : découper ou reporter le non-essentiel (principe d’onboarding).  
- Largeur du champ adaptée au contenu attendu.

### 8.4 Focus et accessibilité

- Focus toujours visible.  
- Navigation clavier fluide.  
- Ne jamais supprimer l’indicateur de focus pour des raisons esthétiques.

---

## 9. Cartes (Cards)

### 9.1 Rôle

La carte regroupe un ensemble cohérent d’informations ou d’actions :

- widgets Dashboard ;  
- raccourcis ;  
- résumés ;  
- panneaux de configuration.

Elle n’est pas un rectangle décoratif. Si la retirer ne nuit ni à la compréhension ni à l’interaction, elle est probablement inutile.

### 9.2 Structure type

1. En-tête (titre ± description)  
2. Contenu (KPI, liste, texte, mini-table)  
3. Pied optionnel (actions, lien « Voir tout »)

### 9.3 Règles

- Un objectif par carte.  
- Ombres légères / bordures discrètes : jamais de relief agressif.  
- Hover élégant et subtil.  
- États : défaut, survol, chargement, vide, erreur, sélectionnée.  
- État vide guidant (surtout premier jour post-onboarding).

### 9.4 Cartes KPI

Chaque carte KPI devrait idéalement contenir :

- icône ;  
- titre ;  
- valeur ;  
- variation / statut ;  
- courte description.

Couleurs discrètes ; hiérarchie chiffres > texte.

---

## 10. Tableaux

### 10.1 Objectif

Les tableaux sont centraux dans un ERP/POS. Ils doivent être :

- scannables ;  
- stables ;  
- actionnables ;  
- cohérents d’un module à l’autre.

### 10.2 Capacité attendue

- Tri sur colonnes pertinentes.  
- Filtres regroupés, réinitialisables, avec filtres actifs visibles.  
- Pagination claire.  
- Densité confort / compact.  
- Actions de ligne limitées et regroupées.  
- Confirmation pour actions dangereuses.

### 10.3 Lisibilité

- En-têtes clairs.  
- Alignement selon type de donnée (texte / nombres).  
- Colonnes prioritaires en premier.  
- Sur mobile/tablette : préserver l’accès aux actions critiques.

---

## 11. Modales

### 11.1 Types

| Type | Usage |
|------|--------|
| **Confirmation** | Action irréversible / fort impact |
| **Formulaire court** | Création / édition simple |
| **Alerte** | Prise de connaissance bloquante |
| **Dialogue de choix** | Alternatives simples |

### 11.2 Règles

- Une modale = un objectif.  
- Titre clair + conséquence compréhensible.  
- Sortie explicite (Annuler / Fermer), sauf cas de sécurité exceptionnel.  
- Formulaires longs → page dédiée, pas modale étroite.  
- Pas de chaînes de modales interminables.  
- Focus correctement géré pendant l’ouverture.

---

## 12. Menus

### 12.1 Types

- Menu utilisateur (profil, préférences, déconnexion)  
- Menu notifications  
- Menus d’actions de ligne / overflow  
- Menus contextuels de sélection (entreprise, boutique)

### 12.2 Règles

- Sobres, prévisibles, fermeture claire.  
- Actions dangereuses séparées visuellement.  
- Accessibles clavier.  
- Ne pas surcharger la topbar de menus concurrents.

---

## 13. Navigation

### 13.1 Architecture de navigation

GreenPOS s’organise autour d’un cadre stable :

- **Sidebar** — navigation principale vers modules et sections.  
- **Topbar** — contexte et actions globales.  
- **Zone principale** — contenu de l’écran.  
- **Breadcrumb** — profondeur (fiche / sous-fiche).  
- **Navigation secondaire** — onglets / sous-sections d’un domaine.

### 13.2 Sidebar

Organisation recommandée par groupes (ex.) :

- Pilotage  
- Ventes  
- Catalogue  
- Relation Client  
- Finance  
- Administration  

Règles :

- n’afficher que ce qui est autorisé et pertinent (modules activés + permissions) ;  
- élément actif clairement visible ;  
- Dashboard toujours identifiable comme point d’entrée ;  
- repliable sur desktop ;  
- devient drawer sur mobile.

Bas de sidebar (recommandé) :

- entreprise active ;  
- version ;  
- indication d’abonnement / offre (si applicable).

### 13.3 Topbar

Doit répondre à : **« Où suis-je, et dans quel contexte ? »**

Éléments typiques :

- recherche globale ;  
- notifications ;  
- bascule Dark Mode ;  
- entreprise active ;  
- boutique active ;  
- profil / avatar / menu utilisateur ;  
- actions rapides.

### 13.4 Règle d’or navigation

La logique de navigation ne change pas selon le module.  
Seuls les items visibles changent selon activité, modules et rôle.

---

## 14. Badges

### 14.1 Rôle

Communiquer un **statut**, une **priorité** ou une **catégorie courte**.

### 14.2 Exemples de sens

- Actif / Inactif  
- Payé / En attente / En retard  
- Ouvert / Fermé / En cours  
- Critique / Haute / Normale / Basse  

### 14.3 Règles

- Texte court.  
- Même statut = même badge partout.  
- Ne remplace pas un message d’erreur complet.  
- Couleur + texte (jamais couleur seule).

---

## 15. Alertes

### 15.1 Niveaux officiels

| Niveau | Intention |
|--------|-----------|
| **Information** | Guidance, état neutre utile |
| **Succès** | Confirmation positive |
| **Attention** | Vigilance (stock bas, tâche à faire) |
| **Critique** | Blocage / risque immédiat |

### 15.2 Contenu d’une alerte

- Icône  
- Titre  
- Description courte  
- Action (« Voir », « Traiter », « Ouvrir »)

### 15.3 Règles Dashboard

- Classer par priorité.  
- Montrer d’abord ce qui bloque l’activité.  
- Respecter le rôle (un caissier ne voit pas forcément les alertes financières du propriétaire).  
- Ne pas transformer le Dashboard en mur d’alertes.

---

## 16. Notifications

### 16.1 Types

Succès · Erreur · Avertissement · Information

### 16.2 Comportement

- Position et style cohérents partout.  
- Empilement maîtrisé.  
- Fermeture possible.  
- Les erreurs de formulaire restent locales au champ.  
- Distinction claire entre :
  - notification éphémère (toast) ;  
  - alerte Dashboard persistante ;  
  - notification de centre d’alertes (topbar).

---

## 17. Indicateurs de chargement

### 17.1 Principes

- Sobriété.  
- L’utilisateur doit comprendre que le système travaille.  
- Préférer le contenu progressif à un écran blanc prolongé.

### 17.2 Patterns autorisés

- **Skeleton** pour cartes, listes, dashboard.  
- **Spinner / état bouton** pour actions ponctuelles.  
- **Progression d’étape** pour onboarding / génération d’espace de travail.

### 17.3 Interdits

- Loaders fantaisistes.  
- Animations longues qui retardent l’action.  
- Masquer une erreur derrière un chargement infini.

---

## 18. Composants réutilisables

### 18.1 Bibliothèque conceptuelle minimale

Tout écran doit composer à partir de composants partagés, notamment :

- Boutons  
- Champs / formulaires  
- Cartes / KPI  
- Tableaux  
- Badges  
- Alertes  
- Notifications  
- Modales / dialogues  
- Menus / dropdowns  
- Sidebar / Topbar / Breadcrumb  
- Onglets  
- Pagination  
- Empty states  
- Skeletons / loaders  
- Avatars  
- Sélecteurs de contexte (entreprise, boutique)  
- Timeline d’activité  
- Raccourcis actionnables  

### 18.2 Empty states

Un état vide doit :

- expliquer pourquoi c’est vide ;  
- proposer la prochaine action utile ;  
- rester rassurant (surtout après onboarding).

### 18.3 Interdiction

Aucun module (POS, Stock, Ordonnances, Tables, Devis…) ne crée sa propre identité graphique ni ses propres composants « forks » non documentés.

---

## 19. Règles responsive

### 19.1 Breakpoints d’intention

| Contexte | Priorité |
|----------|----------|
| **Desktop** | Pilotage dense, multi-panneaux, tableaux |
| **Laptop** | Même logique, largeur utile préservée |
| **Tablette** | Cibles tactiles, navigation adaptée, POS terrain |
| **Mobile** | Actions critiques + consultation ; drawer pour sidebar |

### 19.2 Règles

- Adapter la **disposition**, jamais l’**identité**.  
- Sur mobile, la Sidebar devient un Drawer.  
- Les alertes et actions critiques restent accessibles.  
- Ne pas livrer une « autre application » sur petit écran.

---

## 20. Règles d’accessibilité

### 20.1 Obligations

- Contrastes suffisants (clair et sombre).  
- Navigation clavier des parcours critiques.  
- Focus visible en tout contexte.  
- Tailles de cibles suffisantes.  
- Textes d’erreur associés aux champs.  
- Icônes accompagnées de texte ou d’équivalent accessible.  
- Information jamais portée par la seule couleur.

### 20.2 Usage intensif

GreenPOS doit rester utilisable :

- en rush de caisse ;  
- sur écran partagé / luminosité variable ;  
- par des profils peu techniques après courte prise en main.

L’accessibilité est une condition de qualité, pas un bonus.

---

## 21. Animations et transitions

### 21.1 Intention

Les animations servent la compréhension et la fluidité.  
**Aucune animation décorative inutile.**

### 21.2 Autorisées

- Hover subtil  
- Focus visible  
- Ouverture / fermeture de menus, drawers, modales  
- Transitions d’état courtes  
- Micro-interactions de feedback  
- Apparition de skeletons / chargements sobres  

### 21.3 Interdites

- Parallax dans l’outil  
- Confettis, glow permanents, boucles distrayantes  
- Transitions longues qui retardent l’action  
- Effets différents selon les modules  

### 21.4 Accessibilité mouvement

Prévoir le respect des préférences de réduction de mouvement lorsque c’est pertinent.

---

## 22. Règles UX communes à toute la plateforme

### 22.1 Alignement produit

Le design doit servir les règles métier :

- Compte → Entreprise → Boutiques → Modules → Utilisateurs → Données  
- Isolation totale entre entreprises  
- Activation sélective des modules  
- Adaptation au rôle  
- Onboarding guidé jusqu’au Dashboard  

### 22.2 Règles UX transverses

1. Toujours afficher le contexte (entreprise / boutique).  
2. Toujours indiquer la prochaine action utile.  
3. Toujours préférer un chemin rapide + personnalisation optionnelle.  
4. Toujours sauvegarder la progression des parcours longs (onboarding).  
5. Toujours permettre un retour arrière tant que ce n’est pas finalisé.  
6. Toujours expliquer les dépendances de modules avant validation.  
7. Toujours filtrer l’UI par permissions.  
8. Toujours garder les libellés stables et métier.  
9. Toujours traiter l’état vide comme un état de guidance.  
10. Toujours tester la lisibilité en conditions réelles (rush, densité, mobile).

### 22.3 Parcours critiques à protéger visuellement

- Création de compte / vérification  
- Onboarding (entreprise, activité, boutique, modules)  
- Première arrivée Dashboard  
- Encaissement POS / Caisse  
- Gestion stock / ruptures  
- Bascules multi-entreprises / multi-boutiques  
- Actions destructrices (suppression, annulation)

### 22.4 Critère de qualité UX

Une interface GreenPOS est réussie si un utilisateur peut répondre en moins de 3 secondes à :

- Où suis-je ?  
- Que puis-je faire maintenant ?  
- Qu’est-ce qui demande mon attention ?

---

## 23. Personnalisation (Dashboard et au-delà)

Conformément au Dashboard officiel :

- dispositions personnalisables par utilisateur (dans les limites des permissions) ;  
- widgets déplaçables / masquables ;  
- page d’accueil éventuellement adaptée au rôle (ex. caisse pour caissier) ;  
- dispositions recommandées par rôle / activité comme point de départ.

La personnalisation ne contourne jamais :

- les permissions ;  
- l’isolation des données ;  
- l’identité visuelle commune.

---

## 24. Ce qui est interdit

1. Trop de couleurs sur un même écran  
2. Animations décoratives  
3. Interfaces surchargées  
4. Icônes incohérentes  
5. Tableaux illisibles  
6. Boutons différents selon les modules  
7. Formulaires interminables sans découpage  
8. Ombres lourdes multi-couches / glow  
9. Badges décoratifs sans sens  
10. Dark Mode illisible  
11. Suppression du focus  
12. Messages d’erreur vagues  
13. Affichage de modules inactifs comme s’ils étaient disponibles  
14. Confusion visuelle entre entreprises ou boutiques  
15. Identité graphique locale par module ou par activité  

---

## 25. Gouvernance du Design System

### 25.1 Source de vérité

Ce document est la **référence officielle du design GreenPOS**.  
Les spécifications d’écrans (onboarding, dashboard, modules) s’y conforment ; elles ne le remplacent pas.

### 25.2 Évolution

Toute évolution majeure (nouveau composant, nouveau pattern, changement de palette) doit :

1. être justifiée par un besoin produit réel ;  
2. respecter les principes fondateurs ;  
3. mettre à jour ce document **avant** généralisation ;  
4. rester compatible multi-activités / multi-modules.

### 25.3 Revue

Lors de chaque revue UI, vérifier :

- cohérence avec ce Design System ;  
- cohérence avec Blueprint / Principles / Catalogue / Onboarding / Dashboard ;  
- accessibilité ;  
- densité et lisibilité terrain.

---

## 26. Conclusion

GreenPOS doit offrir une expérience **moderne, premium, professionnelle**, adaptée à une plateforme SaaS ERP/POS utilisée quotidiennement.

Le Design System garantit qu’un utilisateur passant :

- du Dashboard au POS,  
- du Stock à la Facturation,  
- d’une boutique Restaurant à une boutique Pharmacie,

reconnaisse **toujours le même produit** — fiable, rapide, clair, digne de confiance.

Ce document devient la référence officielle de toute conception UI/UX GreenPOS.  
Aucune dérive visuelle « locale » n’est acceptable sans arbitrage et mise à jour documentaire.

**Prochaine étape recommandée :** décliner les patterns de pages types (liste, fiche, formulaire, paramètres, caisse) strictement à partir de ce Design System.

---

*GreenPOS v3 — Document officiel — 05_DESIGN_SYSTEM.md*
