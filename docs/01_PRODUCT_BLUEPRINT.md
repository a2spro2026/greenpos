# GreenPOS v3 — Product Blueprint

**Document officiel**  
**Version :** 3.0  
**Statut :** Référence produit  
**Public :** Équipe produit, design et développement

---

## 1. Vision du produit

GreenPOS n’est **pas** un simple logiciel de caisse.

GreenPOS est une **plateforme SaaS modulaire de gestion d’entreprise**, conçue pour construire automatiquement un système de gestion adapté au métier de chaque client.

La plateforme permet à tout client de :

- créer un compte ;
- choisir son activité ;
- créer son entreprise ;
- créer une ou plusieurs boutiques ;
- sélectionner uniquement les modules dont il a besoin.

Chaque activité dispose de son propre ensemble de modules. Le client n’active que ce qui correspond à son métier. Le reste reste disponible pour plus tard, sans complexité inutile dès le départ.

### Exemples d’activités et de modules

| Activité | Exemples de modules |
|----------|---------------------|
| **Restaurant** | POS, Tables, Cuisine, Réservations, Stock, Achats… |
| **Pharmacie** | POS, Médicaments, Ordonnances, Stock… |
| **Matériaux de construction** | POS, Stock, Devis, Transport, Engins… |
| **Garage** | POS, Atelier, Réparations, Pièces… |
| **Hôtel** | Réservations, Chambres, POS… |

GreenPOS doit permettre d’**ajouter facilement de nouvelles activités** dans le futur, sans modifier le cœur de la plateforme. La vision n’est pas de livrer un outil figé pour un seul métier, mais une fondation évolutive capable d’accueillir des métiers nouveaux au fil du temps.

GreenPOS ne vend pas un logiciel unique. GreenPOS fournit une plateforme capable de **composer** un système de gestion sur mesure, activité par activité, module par module.

---

## 2. Mission

Permettre à toute entreprise de **construire, en quelques étapes, un espace de travail adapté à son activité**, puis de le faire évoluer en activant ou désactivant des modules selon ses besoins réels.

GreenPOS v3 a pour mission de :

- accompagner le client de la création de compte jusqu’à un tableau de bord opérationnel ;
- proposer un catalogue d’activités et de modules clair et compréhensible ;
- activer uniquement les capacités utiles à chaque métier ;
- supporter plusieurs entreprises et plusieurs boutiques au sein d’un même compte ;
- rester simple à prendre en main, tout en restant évolutif et sûr ;
- offrir une base solide sur laquelle de nouvelles activités et de nouveaux modules peuvent être ajoutés sans remettre en cause le cœur de la plateforme.

La mission n’est pas de remplacer chaque outil métier spécialisé du marché. La mission est de fournir **une plateforme unique**, modulaire et progressive, dans laquelle chaque entreprise assemble son système de gestion.

---

## 3. Problèmes résolus

GreenPOS v3 répond aux difficultés concrètes des entreprises qui cherchent un système de gestion adapté à leur métier, sans complexité ni sur-équipement :

| Problème | Impact métier | Réponse GreenPOS |
|----------|---------------|------------------|
| Logiciels figés pour un seul métier | Mauvaise adaptation, fonctionnalités inutiles | Catalogue d’activités + modules métier |
| Suites trop lourdes dès le départ | Coût, rejet, formation longue | Activation uniquement des modules nécessaires |
| Outils dispersés (caisse, stock, atelier, réservations…) | Perte de temps, données incohérentes | Une plateforme unique, composable par modules |
| Impossible d’évoluer sans changer d’outil | Frein à la croissance | Modules activables / désactivables à tout moment |
| Gestion multi-sites difficile | Manque de vision, double saisie | Multi-entreprises et multi-boutiques |
| Ajout d’un nouveau métier coûteux | Dépendance forte, délais longs | Nouvelles activités ajoutables sans modifier le cœur |
| Parcours d’onboarding confus | Abandon avant même l’usage | Parcours guidé : compte → activité → entreprise → boutique → modules |
| Manque de pilotage global | Décisions à l’aveugle | Tableau de bord dès l’installation de l’espace de travail |

---

## 4. Utilisateurs cibles

### 4.1 Profils principaux

**Créateur de compte / propriétaire**  
Décideur qui ouvre GreenPOS, choisit l’activité, crée l’entreprise et les boutiques, sélectionne les modules. Priorité : démarrer vite, comprendre ce qu’il active, garder le contrôle de l’évolution.

**Administrateur d’entreprise**  
Garant de la configuration et des accès. Priorité : structure (entreprise, boutiques, utilisateurs), activation des modules, cohérence globale.

**Responsable de boutique / manager**  
Pilote l’activité locale d’une ou plusieurs boutiques. Priorité : usage quotidien des modules activés, suivi opérationnel, performance de son périmètre.

**Utilisateur opérationnel (selon modules activés)**  
Caissier, serveur, magasinier, technicien atelier, réceptionniste, etc. Priorité : accomplir rapidement ses tâches métier dans les modules qui lui sont ouverts, sans bruit inutile.

### 4.2 Contextes d’usage

- Entreprises mono-boutique ou multi-boutiques  
- Organisations multi-entreprises au sein d’un même compte  
- Métiers variés (restauration, pharmacie, matériaux, garage, hôtellerie, et futurs métiers)  
- Organisations qui veulent démarrer petit puis activer de nouveaux modules  
- Équipes avec niveaux de responsabilité et périmètres différents  

GreenPOS v3 s’adresse aux entreprises qui veulent une **plateforme de gestion adaptable**, pas un logiciel unique imposé à tous les métiers.

---

## 5. Activités supportées

GreenPOS v3 couvre, au niveau plateforme, les activités suivantes :

1. **Création et gestion de compte**  
   Ouverture d’un accès à la plateforme et identification de l’utilisateur.

2. **Choix d’activité**  
   Sélection du métier de référence (restaurant, pharmacie, garage, etc.) qui détermine le catalogue de modules proposés.

3. **Création d’entreprise**  
   Définition de l’entité juridique / organisationnelle qui porte l’activité.

4. **Gestion multi-boutiques**  
   Création et administration d’une ou plusieurs boutiques rattachées à l’entreprise.

5. **Catalogue d’activités**  
   Présentation claire des métiers supportés par la plateforme.

6. **Catalogue de modules**  
   Présentation des modules disponibles pour l’activité choisie, avec activation sélective.

7. **Activation / désactivation de modules**  
   Composition et évolution de l’espace de travail selon les besoins du moment.

8. **Installation de l’espace de travail**  
   Mise en place automatique de l’environnement adapté après sélection des modules.

9. **Pilotage via tableau de bord**  
   Point d’entrée synthétique après installation, puis au quotidien.

10. **Usage métier via modules activés**  
    Exemples selon l’activité : POS, stock, réservations, atelier, ordonances, devis, transport, etc. — uniquement ceux choisis par le client.

Le POS n’est qu’**un module possible** parmi d’autres. Il n’est plus le centre exclusif de la vision produit.

---

## 6. Parcours utilisateur global

Le parcours officiel d’entrée dans GreenPOS est le suivant :

### 6.1 Création du compte

L’utilisateur crée son compte et accède à la plateforme.

### 6.2 Choix de l’activité

Il sélectionne son métier (ex. restaurant, pharmacie, garage, hôtel…). Ce choix oriente le catalogue de modules proposés.

### 6.3 Création de l’entreprise

Il crée son entreprise : l’entité qui portera les boutiques, les modules et l’organisation.

### 6.4 Création de la première boutique

Il crée au moins une boutique. D’autres boutiques pourront être ajoutées ensuite.

### 6.5 Sélection des modules

Il choisit uniquement les modules dont il a besoin pour démarrer. Rien n’est imposé au-delà de l’essentiel lié à l’activité.

### 6.6 Installation automatique de l’espace de travail

La plateforme installe l’environnement correspondant à l’activité, à l’entreprise, à la boutique et aux modules sélectionnés.

### 6.7 Accès au tableau de bord

L’utilisateur arrive sur son tableau de bord et peut commencer à travailler dans les modules activés.

### 6.8 Suite du parcours (usage quotidien)

Selon son rôle et les modules actifs :

- exploiter les modules métier au quotidien ;
- ajouter des boutiques ;
- activer ou désactiver des modules ;
- piloter l’activité depuis le tableau de bord ;
- faire évoluer l’organisation sans changer de plateforme.

Le parcours d’onboarding doit rester **court, guidé et rassurant** : chaque étape a un sens métier clair.

---

## 7. Principes de conception

Ces principes guident toutes les décisions produit :

1. **Plateforme SaaS**  
   GreenPOS est une plateforme de service accessible et partagée, pensée pour accueillir de nombreux clients et organisations, pas un outil installé isolément pour un seul usage figé.

2. **Architecture modulaire**  
   Les capacités métier sont découpées en modules. Le cœur de la plateforme reste stable ; la valeur métier s’exprime par composition de modules.

3. **Multi-entreprises**  
   Un compte peut porter plusieurs entreprises. La plateforme respecte cette réalité organisationnelle.

4. **Multi-boutiques**  
   Une entreprise peut gérer une ou plusieurs boutiques. Le pilotage local et global doit rester compréhensible.

5. **Modules activables**  
   Les modules s’activent et se désactivent selon le besoin. Le client ne paie pas en complexité ce qu’il n’utilise pas.

6. **Évolutivité**  
   De nouvelles activités et de nouveaux modules doivent pouvoir être ajoutés sans modifier le cœur de la plateforme.

7. **Simplicité**  
   Le parcours doit rester clair : compte, activité, entreprise, boutique, modules, tableau de bord. Moins d’écrans inutiles, plus d’actions évidentes.

8. **Performance**  
   L’expérience doit rester fluide, y compris lorsque plusieurs modules et plusieurs boutiques sont actifs.

9. **Sécurité**  
   Accès, rôles et périmètres (entreprise, boutique, modules) doivent protéger les données et les opérations sensibles.

10. **Adaptation au métier**  
    L’activité choisie détermine le catalogue pertinent. GreenPOS s’adapte au client ; le client ne contourne pas un outil générique inadapté.

11. **Progressivité**  
    On démarre avec l’essentiel, puis on active davantage. La V1 pose les fondations ; l’enrichissement métier vient ensuite.

12. **Confiance et clarté**  
    L’utilisateur doit toujours comprendre ce qui est activé, pour quelle boutique, et à quoi sert chaque module.

---

## 8. Objectifs de la V1

La V1 de GreenPOS v3 doit poser les **fondations de la plateforme** et démontrer qu’un client peut construire un espace de travail adapté à son activité.

### Objectifs plateforme

- Permettre la **création des comptes**.
- Permettre la **création des entreprises**.
- Permettre la **gestion de plusieurs boutiques**.
- Offrir un **catalogue des activités**.
- Offrir un **catalogue des modules**.
- Permettre l’**activation des modules**.
- Fournir un **tableau de bord** après installation de l’espace de travail.
- Livrer un **premier module métier entièrement fonctionnel** (preuve que la plateforme accueille réellement un usage métier, et non seulement une coquille vide).

### Objectif d’expérience

Un client doit pouvoir, avec GreenPOS V1 :

> créer son compte, choisir son activité, créer son entreprise et sa boutique, activer les modules utiles, puis accéder à un espace de travail prêt à l’emploi — sans formation longue.

### Objectif de qualité produit

- Parcours d’onboarding complet et compréhensible  
- Cohérence entre activité choisie, modules proposés et modules activés  
- Tableau de bord utile dès la première connexion post-installation  
- Premier module métier réellement utilisable de bout en bout  

La V1 ne cherche pas à couvrir tous les métiers ni tous les modules. Elle cherche à prouver que **la plateforme compose un système de gestion** à partir d’un compte, d’une activité, d’une entreprise, de boutiques et de modules.

---

## 9. Fonctionnalités volontairement exclues de la V1

Afin de protéger la qualité et le délai de livraison, la V1 **n’inclut pas** :

- Couverture complète de toutes les activités imaginables  
- Catalogue exhaustif de tous les modules pour chaque métier  
- Marketplace publique d’extensions tierces  
- Comptabilité générale et exports fiscaux complexes  
- E-commerce / vente en ligne avancée  
- Programme de fidélité sophistiqué  
- Intégrations bancaires / TPE avancées  
- Applications mobiles natives dédiées  
- Intelligence artificielle prédictive  
- Gestion RH complète (plannings, paie)  
- Facturation B2B avancée (devis complexes multi-étapes, échéanciers riches) pour tous les métiers  
- Automatisations poussées inter-modules au-delà du nécessaire au premier module métier  

Ces sujets pourront être réévalués après consolidation de la V1, sur la base des retours clients, de l’adoption des modules et des KPI.

---

## 10. Indicateurs de réussite (KPI)

Les KPI ci-dessous mesurent si GreenPOS v3 V1 tient sa promesse de **plateforme SaaS modulaire**.

### Adoption & onboarding

- Taux de comptes créés qui vont jusqu’à l’accès au tableau de bord  
- Temps moyen pour compléter le parcours : compte → activité → entreprise → boutique → modules → tableau de bord  
- Taux d’abandon à chaque étape de l’onboarding  
- Part des nouveaux clients qui activent au moins un module métier

### Modularité

- Nombre moyen de modules activés par entreprise au démarrage  
- Taux d’activation / désactivation de modules après la première semaine  
- Cohérence entre activité choisie et modules effectivement utilisés  
- Nombre d’activités réellement utilisées en production (au-delà du simple catalogue)

### Multi-organisation

- Part des entreprises ayant créé plus d’une boutique  
- Part des comptes gérant plus d’une entreprise (si proposé en V1)  
- Facilité déclarée à basculer entre boutiques / périmètres

### Usage du premier module métier

- Taux d’utilisation quotidienne du premier module métier livré  
- Temps moyen pour réaliser la première action métier réussie dans ce module  
- Taux d’incidents bloquants sur ce module

### Pilotage

- Consultation régulière du tableau de bord après installation  
- Satisfaction déclarée des propriétaires / administrateurs sur la clarté de l’espace de travail  
- Sentiment d’adéquation « l’outil correspond à mon métier »

### Succès produit (seuil qualitatif V1)

La V1 est considérée réussie si :

1. un nouveau client termine le parcours d’onboarding sans assistance et arrive sur son tableau de bord ;  
2. il comprend quels modules sont actifs et pourquoi ils correspondent à son activité ;  
3. il utilise réellement le premier module métier livré dans son quotidien ;  
4. il peut envisager d’activer un module supplémentaire plus tard sans changer de plateforme.

---

## 11. Glossaire des principaux termes métier

| Terme | Définition |
|-------|------------|
| **Plateforme** | Socle GreenPOS qui accueille comptes, entreprises, boutiques, activités et modules. |
| **SaaS** | Modèle de plateforme accessible en service, conçu pour servir de nombreux clients. |
| **Compte** | Accès utilisateur à la plateforme GreenPOS. |
| **Activité** | Métier de référence choisi par le client (restaurant, pharmacie, garage, hôtel, etc.). |
| **Catalogue des activités** | Liste des métiers supportés par la plateforme. |
| **Entreprise** | Entité organisationnelle créée par le client, portant boutiques et modules. |
| **Boutique** | Point d’exploitation rattaché à une entreprise (magasin, restaurant, atelier, etc.). |
| **Multi-entreprises** | Capacité à gérer plusieurs entreprises depuis la plateforme. |
| **Multi-boutiques** | Capacité à gérer plusieurs boutiques au sein d’une entreprise. |
| **Module** | Bloc fonctionnel activable correspondant à un besoin métier (POS, Stock, Réservations, Atelier…). |
| **Catalogue des modules** | Liste des modules disponibles pour une activité donnée. |
| **Activation de module** | Action de rendre un module disponible dans l’espace de travail. |
| **Désactivation de module** | Action de retirer un module de l’espace de travail actif. |
| **Espace de travail** | Environnement installé automatiquement après choix d’activité, entreprise, boutique(s) et modules. |
| **Tableau de bord** | Vue d’entrée et de pilotage après installation de l’espace de travail. |
| **Module métier** | Module orienté usage opérationnel d’un métier (par opposition aux seules fonctions plateforme). |
| **POS** | Module de point de vente / encaissement — un module parmi d’autres, non la plateforme entière. |
| **Onboarding** | Parcours initial : compte, activité, entreprise, boutique, modules, installation, tableau de bord. |
| **Propriétaire** | Décideur qui crée et configure l’organisation sur GreenPOS. |
| **Administrateur** | Utilisateur disposant des droits de configuration et de contrôle sur l’entreprise. |
| **Manager** | Responsable opérationnel d’une ou plusieurs boutiques. |
| **Utilisateur opérationnel** | Utilisateur qui exécute les tâches quotidiennes dans les modules activés. |
| **Évolutivité** | Capacité à ajouter activités et modules sans modifier le cœur de la plateforme. |
| **V1** | Première version livrable de GreenPOS v3, centrée sur les fondations plateforme et un premier module métier. |

---

## 12. Conclusion

GreenPOS v3 n’est pas un simple logiciel de caisse. C’est une **plateforme SaaS modulaire de gestion d’entreprise**, capable de construire un système adapté à chaque métier.

Le client crée son compte, choisit son activité, crée son entreprise et ses boutiques, sélectionne ses modules, puis accède à un espace de travail prêt à l’emploi. Il active ce dont il a besoin, désactive ce qui ne lui sert plus, et fait évoluer son organisation sans changer de fondation.

La V1 n’a pas pour ambition de couvrir tous les métiers ni tous les modules. Elle a pour ambition de prouver que GreenPOS **compose** réellement un système de gestion — compte après compte, activité après activité, module après module — avec simplicité, performance et sécurité.

Ce blueprint constitue la référence produit officielle. Toute décision ultérieure — priorisation, conception d’écrans, catalogue d’activités, roadmap modules — doit rester alignée avec cette vision de plateforme modulaire, multi-entreprises et multi-boutiques.

**Prochaine étape documentaire recommandée :** formaliser le catalogue d’activités V1, le catalogue de modules associés, et les règles d’onboarding (spécifications fonctionnelles plateforme), toujours sans décision technique prématurée.

---

*GreenPOS v3 — Document produit officiel — 01_PRODUCT_BLUEPRINT.md*
