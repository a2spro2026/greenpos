# GreenPOS v3 — Product Roadmap

**Document officiel**  
**Version :** 3.0  
**Statut :** Feuille de route de référence  
**Public :** Équipes produit, développement et direction  
**Documents liés :** `01_PRODUCT_BLUEPRINT.md`, `02_PRODUCT_PRINCIPLES.md`, `03_ACTIVITIES_AND_MODULES_CATALOG.md`

---

## 1. Vision de la Roadmap

GreenPOS est conçu comme une **plateforme évolutive**.  
Toutes les fonctionnalités ne seront pas développées dans la première version. La promesse du produit n’est pas de tout livrer immédiatement, mais de construire **dans le bon ordre**.

La priorité absolue est de poser un **socle robuste** avant d’ajouter de nouvelles activités, de nouveaux modules ou des capacités avancées.  
Sans ce socle — comptes, entreprises, boutiques, modules, utilisateurs, isolation des données, tableau de bord — aucune activité métier ne peut être durable.

Cette roadmap définit ce qui appartient à chaque version du produit. Elle sert de **référence de planification** pour toute l’équipe. Elle pourra être ajustée selon les retours terrain, mais elle ne doit jamais contredire les principes fondateurs de GreenPOS : modularité, multi-entreprises, multi-boutiques, isolation des données et simplicité.

---

## 2. Objectifs de la Version 1 (V1)

### 2.1 Promesse de la V1

La V1 doit permettre à une entreprise de :

- créer un compte ;
- créer une ou plusieurs entreprises ;
- créer une ou plusieurs boutiques ;
- choisir une activité ;
- activer des modules ;
- gérer les utilisateurs ;
- commencer immédiatement son activité.

La V1 n’est pas une démonstration partielle. C’est une **première version utilisable** : un client doit pouvoir onboarding, composer son espace de travail et démarrer réellement son métier sur le périmètre retenu.

### 2.2 Activités retenues en V1

| Activité | Raison de priorité |
|----------|--------------------|
| **Épicerie** | Commerce de proximité, parcours POS / stock clair |
| **Boutique** | Commerce de détail généraliste, socle catalogue + ventes |
| **Restaurant** | Premier métier avec modules spécialisés (Tables, Cuisine) |
| **Pharmacie** | Premier métier réglementé simple (Ordonnances) |
| **Matériaux de construction** | Premier métier B2B (Devis, Livraisons) |

Ces cinq activités couvrent des modèles suffisamment différents pour valider la modularité, sans ouvrir tout le catalogue.

### 2.3 Modules plateforme (V1)

Modules obligatoires du socle :

- Authentification  
- Entreprises  
- Boutiques  
- Utilisateurs  
- Permissions  
- Paramètres  
- Tableau de bord  

Ces modules constituent le **cœur non négociable** de la V1. Sans eux, aucune activité métier n’est livrable correctement.

### 2.4 Modules métier (V1)

Modules transverses au commerce et à la gestion quotidienne :

- POS  
- Produits  
- Stock  
- Achats  
- Fournisseurs  
- Clients  
- Ventes  
- Paiements  
- Caisse  
- Facturation  
- Rapports  

Ces modules forment le **socle métier commun** partagé par les activités V1, selon activation.

### 2.5 Modules spécialisés (V1)

#### Restaurant

- Tables  
- Cuisine  

#### Pharmacie

- Ordonnances  

#### Matériaux de construction

- Devis  
- Livraisons  

Les modules spécialisés démontrent que GreenPOS sait accueillir des besoins métier distincts **sans casser** le socle commun.

### 2.6 Ce que la V1 doit prouver

À la fin de la V1, GreenPOS doit avoir démontré que :

1. un client peut composer son système (compte → entreprise → boutique → activité → modules) ;  
2. plusieurs activités cohabitent sur les mêmes fondations ;  
3. les modules s’activent de façon cohérente ;  
4. l’isolation entre entreprises est respectée ;  
5. au moins un usage métier réel est possible dans chacune des activités retenues.

---

## 3. Version 2 (V2)

La V2 élargit le catalogue d’activités et enrichit la plateforme de modules d’organisation et de relation.

### 3.1 Nouvelles activités prévues

- Hôtel  
- Salon de coiffure  
- Institut de beauté  
- Salle de sport  
- Garage  
- Atelier mécanique  

### 3.2 Nouveaux modules prévus

- CRM avancé  
- RH  
- Salaires  
- Planning  
- Documents  
- Notifications  
- Maintenance  

### 3.3 Ambition de la V2

La V2 doit montrer que GreenPOS peut **s’étendre à de nouveaux métiers** en réutilisant le socle V1, et que des modules d’organisation (planning, RH, documents, notifications) renforcent la plateforme sans la complexifier inutilement.

La V2 ne démarre qu’une fois la V1 jugée stable selon les critères de passage définis ci-dessous.

---

## 4. Version 3 (V3)

La V3 ouvre GreenPOS vers l’échelle, l’écosystème et l’intelligence avancée.

### 4.1 Capacités prévues

- Marketplace de modules  
- Intelligence artificielle  
- Automatisations  
- API publique  
- Intégrations externes  
- Application mobile  
- Mode hors ligne  
- Business Intelligence avancée  
- Multi-langues  
- Multi-devises  

### 4.2 Ambition de la V3

La V3 transforme GreenPOS d’une plateforme modulaire aboutie en un **écosystème extensible** : modules tiers, automatisations, mobilité, analyses avancées et ouverture maîtrisée vers l’extérieur.

Aucune de ces capacités ne doit être anticipée au détriment de la stabilité du socle. Elles s’appuient sur une V1 solide et une V2 déjà étendue.

---

## 5. Critères de passage entre versions

Une version n’est considérée **terminée** — et la suivante ne démarre pleinement — que si les conditions suivantes sont réunies.

### 5.1 Stabilité

- Les parcours critiques fonctionnent de bout en bout sans incident bloquant récurrent.  
- Les régressions majeures sont rares et rapidement corrigées.  
- L’isolation des entreprises et des boutiques est vérifiée dans les usages réels.

### 5.2 Qualité

- Les modules livrés correspondent à leur intention produit.  
- L’expérience reste compréhensible pour les profils cibles.  
- Aucune fonctionnalité « à moitié faite » ne reste présentée comme terminée.

### 5.3 Documentation

- La documentation produit de la version est à jour.  
- Les écarts éventuels entre roadmap, catalogue et réalité livrée sont explicitement tracés.  
- Les équipes savent ce qui est inclus, exclu, et reporté.

### 5.4 Tests

- Les scénarios critiques d’onboarding, d’activation de modules et d’usage métier principal sont validés.  
- Les contrôles d’accès (utilisateurs / permissions) sont vérifiés.  
- Les cas d’erreur fréquents sont couverts et compréhensibles.

### 5.5 Retour des premiers clients

- Des retours utilisateurs réels ont été collectés sur le périmètre de la version.  
- Les freins majeurs d’adoption ont été identifiés et traités ou planifiés.  
- La version est jugée suffisamment utile pour servir de base à l’élargissement suivant.

### 5.6 Décision de passage

Le passage V1 → V2, puis V2 → V3, est une **décision produit explicite**.  
Il ne se déclenche ni par calendrier seul, ni par envie de nouveauté, mais par atteinte des critères ci-dessus.

---

## 6. Principes de développement

Les principes suivants gouvernent l’exécution de cette roadmap :

1. **La qualité prime sur la rapidité.**  
   Livrer vite une base fragile coûte plus cher que livrer juste une base solide.

2. **Aucune fonctionnalité ne doit casser l’existant.**  
   Chaque ajout doit préserver les parcours et les règles déjà en production.

3. **Chaque version doit être stable avant de commencer la suivante.**  
   L’empilement de versions inachevées est interdit.

4. **Toute nouvelle activité doit réutiliser les fondations existantes.**  
   On n’invente pas un nouveau cœur pour chaque métier. On compose à partir du socle.

5. **La modularité est permanente.**  
   Un module s’ajoute, s’active, évolue — sans imposer une refonte globale.

6. **La simplicité reste un critère de go / no-go.**  
   Une fonctionnalité puissante mais confuse n’est pas prête.

7. **Les principes fondateurs ne sont pas négociables.**  
   Multi-entreprises, multi-boutiques, isolation des données, activation sélective des modules.

---

## 7. Conclusion

Cette roadmap constitue la **référence officielle de planification** de GreenPOS.

Elle précise :

- ce que la V1 doit absolument permettre ;  
- ce que la V2 élargira ;  
- ce que la V3 ouvrira à l’échelle et à l’écosystème ;  
- les conditions de passage d’une version à l’autre ;  
- les principes qui protègent la qualité du produit dans la durée.

Elle pourra évoluer en fonction des retours utilisateurs, des priorités business et des apprentissages terrain — **sans remettre en cause les principes fondateurs** définis dans le Product Blueprint et les Product Principles.

Toute demande hors roadmap doit être arbitrée explicitement : intégrée à une version, reportée, ou refusée.  
Rien d’important ne doit entrer dans le produit « par accident ».

**Prochaine étape documentaire recommandée :** détailler le périmètre fonctionnel V1 (parcours d’onboarding + règles d’activation des modules retenus) sous forme de spécifications fonctionnelles.

---

*GreenPOS v3 — Document produit officiel — 00_PRODUCT_ROADMAP.md*
