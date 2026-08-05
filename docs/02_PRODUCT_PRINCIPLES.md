# GreenPOS v3 — Product Principles

**Document officiel**  
**Version :** 3.0  
**Statut :** Référence des règles métier fondamentales  
**Public :** Équipe produit, design et développement  
**Document lié :** `01_PRODUCT_BLUEPRINT.md`

---

Ce document définit les **règles métier fondamentales** de GreenPOS.  
Toutes les futures décisions d’architecture, de conception et de priorisation devront les respecter.  
En cas de doute, ces principes priment sur les habitudes techniques ou les raccourcis de livraison.

---

## 1. Philosophie générale

GreenPOS est une **plateforme SaaS modulaire de gestion d’entreprise**.

Elle ne propose pas un logiciel unique figé pour tous les métiers. Elle fournit un socle commun sur lequel chaque entreprise **construit son propre système de gestion**, en activant uniquement les modules dont elle a besoin.

Le client choisit son activité, crée son entreprise et ses boutiques, puis compose son espace de travail module par module. Ce qu’il n’active pas ne doit ni alourdir son expérience, ni contaminer son organisation.

GreenPOS vend une capacité à **assembler** un système adapté — pas une suite imposée.  
La simplicité d’usage, l’indépendance des modules et l’isolation des organisations sont au cœur de cette philosophie.

---

## 2. Hiérarchie officielle

La structure organisationnelle de GreenPOS est stricte et ordonnée. Chaque niveau a un rôle précis. Aucun niveau ne peut être contourné ou confondu avec un autre.

```
Compte utilisateur
    ↓
Entreprise
    ↓
Boutiques
    ↓
Modules
    ↓
Utilisateurs
    ↓
Données métier
```

### Compte utilisateur

Point d’entrée sur la plateforme.  
Le compte permet de s’authentifier, de créer et d’administrer une ou plusieurs entreprises, et d’accéder aux espaces de travail autorisés.  
Le compte n’est pas l’entreprise : c’est l’identité d’accès à la plateforme.

### Entreprise

Entité organisationnelle principale.  
Elle porte l’activité choisie, les boutiques, les modules activés, les utilisateurs et les paramètres globaux de l’organisation.  
Chaque entreprise constitue un univers indépendant.

### Boutiques

Points d’exploitation rattachés à une entreprise (magasin, restaurant, atelier, pharmacie, etc.).  
La boutique est le lieu concret où s’exerce une partie de l’activité opérationnelle.  
Une entreprise peut en posséder une ou plusieurs.

### Modules

Blocs fonctionnels activables qui composent l’espace de travail (POS, Stock, Réservations, Atelier, etc.).  
Les modules s’appliquent dans le cadre de l’entreprise et de ses boutiques.  
Ils ne remplacent ni l’entreprise ni la boutique : ils ajoutent des capacités métier.

### Utilisateurs

Personnes rattachées à une entreprise, avec des rôles et des permissions.  
Ils agissent dans le périmètre qui leur est autorisé (entreprise, boutique(s), modules).  
Un utilisateur n’existe pas « en dehors » du cadre organisationnel de l’entreprise.

### Données métier

Informations produites par l’usage des modules (ventes, stocks, réservations, réparations, etc.).  
Les données appartiennent toujours à une entreprise, et le plus souvent à une boutique précise.  
Elles ne circulent jamais librement entre entreprises.

**Règle de lecture :**  
tout élément métier doit pouvoir être rattaché clairement à cette hiérarchie. Si un concept ne trouve pas sa place, il n’est pas encore suffisamment défini.

---

## 3. Entreprises

Règles officielles :

- Un compte peut créer **plusieurs entreprises**.
- Chaque entreprise est **totalement indépendante**.
- **Aucune donnée** n’est partagée entre deux entreprises.
- Chaque entreprise possède ses **propres paramètres**.
- Chaque entreprise choisit son **activité** et active ses **propres modules**.
- Les utilisateurs, boutiques, configurations et historiques d’une entreprise n’appartiennent qu’à elle.
- Deux entreprises créées par le même compte restent des univers séparés : le partage de compte ne crée aucun partage de données.

L’entreprise est la **frontière d’isolation** fondamentale de GreenPOS.

---

## 4. Boutiques

Règles officielles :

- Une entreprise possède **une ou plusieurs boutiques**.
- Une boutique appartient **toujours à une seule entreprise**.
- Une boutique ne peut pas être partagée entre plusieurs entreprises.
- Chaque boutique possède ses propres :
  - stocks ;
  - ventes ;
  - caisses ;
  - paramètres opérationnels ;
  - données produites par les modules activés, dans la mesure où ces données sont liées à l’exploitation locale.
- La création de la première boutique fait partie du parcours normal de mise en service.
- L’ajout de boutiques supplémentaires ne doit pas remettre en cause l’indépendance des boutiques déjà existantes.

La boutique est le **périmètre opérationnel local**.  
L’entreprise est le **périmètre organisationnel global**.

---

## 5. Activités

Une **activité** est le métier de référence choisi pour une entreprise.  
Elle oriente le catalogue de modules proposés et donne un sens métier à l’espace de travail.

L’activité ne remplace pas l’entreprise : elle la **qualifie**.

### Exemples d’activités

- Restaurant  
- Pharmacie  
- Hôtel  
- Construction / Matériaux de construction  
- Garage  
- Boutique  
- Librairie  
- Salon de coiffure  
- et d’autres métiers futurs  

### Règles liées aux activités

- Chaque entreprise est associée à une activité.
- Chaque activité propose une **liste de modules recommandés**.
- Le client n’est pas obligé d’activer tous les modules recommandés.
- De nouvelles activités doivent pouvoir être ajoutées sans modifier le cœur de GreenPOS.
- Deux entreprises ayant la même activité restent totalement isolées l’une de l’autre.

L’activité sert à **guider** la composition du système, pas à imposer une configuration unique.

---

## 6. Modules

Les modules sont les briques fonctionnelles de GreenPOS.  
C’est par eux que la plateforme devient un système de gestion concret.

### Principes officiels

- Chaque module est **indépendant** dans sa responsabilité métier.
- Un module peut être **activé** ou **désactivé**.
- Certains modules peuvent **dépendre** d’autres modules : ces dépendances doivent être explicites et compréhensibles pour le client.
- L’activation d’un module ajoute des capacités ; la désactivation retire l’accès à ces capacités selon les règles produit définies.
- Les modules pourront **évoluer** (nouvelles fonctions, améliorations) sans modifier le cœur de GreenPOS.
- De nouveaux modules doivent pouvoir être ajoutés sans remettre en cause les modules existants.
- Un module n’a de sens que dans le cadre d’une entreprise (et, selon sa nature, de ses boutiques).
- Le POS est un module parmi d’autres : il n’est pas la plateforme entière.

### Esprit de conception des modules

Un module doit être :

- utile par lui-même dans le métier qu’il sert ;
- activable sans obliger le client à tout prendre ;
- suffisamment isolé pour évoluer sans casser le reste ;
- suffisamment cohérent avec les autres modules lorsqu’une dépendance existe.

---

## 7. Utilisateurs

Règles officielles :

- Les utilisateurs appartiennent à une **entreprise**.
- Ils peuvent avoir différents **rôles**.
- Leurs **permissions** dépendent de leur rôle.
- Un utilisateur agit uniquement dans le périmètre qui lui est autorisé (entreprise, boutique(s), modules).
- Les droits d’un utilisateur dans une entreprise n’ont aucun effet dans une autre entreprise.
- La gestion des accès doit rester claire : chacun voit et fait uniquement ce dont il a besoin.

Les rôles détaillés (admin, manager, caissier, etc.) seront définis dans un document ultérieur.  
Ce document pose uniquement le principe : **pas d’utilisateur sans entreprise, pas d’action sans permission**.

---

## 8. Isolation des données

L’isolation des données est une **règle fondamentale**, non négociable.

- Chaque entreprise est **totalement isolée**.
- Aucune entreprise ne peut accéder aux données d’une autre.
- Aucun partage implicite n’existe entre entreprises, même si elles partagent le même compte créateur, la même activité ou des modules similaires.
- Les données d’une boutique restent dans le périmètre de son entreprise, et ne se mélangent pas avec celles d’une autre boutique sauf règle métier explicite **au sein de la même entreprise**.
- La sécurité des accès et des périmètres prime sur la commodité.
- Toute fonctionnalité future qui floute la frontière entre entreprises est, par défaut, incompatible avec GreenPOS.

Sans isolation, il n’y a pas de confiance.  
Sans confiance, la plateforme SaaS n’a pas de valeur.

---

## 9. Évolutivité

GreenPOS doit pouvoir accueillir, dans le temps :

- de **nouvelles activités** ;
- de **nouveaux modules** ;
- de **nouvelles fonctionnalités** au sein des modules ;

**sans remettre en cause** l’architecture organisationnelle existante (compte → entreprise → boutiques → modules → utilisateurs → données).

### Règles d’évolutivité

- Le cœur de la plateforme reste stable.
- L’ajout d’un métier ne doit pas exiger de reconcevoir les fondations.
- L’ajout d’un module ne doit pas casser les modules déjà activés.
- Les entreprises déjà en production ne doivent pas subir de rupture conceptuelle à chaque évolution.
- La plateforme grandit par **extension**, pas par **refonte permanente**.

L’évolutivité n’est pas une option de roadmap : c’est une condition de survie du produit.

---

## 10. Règles immuables

Les principes suivants **ne devront jamais être violés**.  
Ils constituent le contrat permanent de GreenPOS.

1. **GreenPOS est une plateforme SaaS modulaire**, pas un logiciel de caisse unique.
2. **La hiérarchie officielle est respectée** : Compte → Entreprise → Boutiques → Modules → Utilisateurs → Données métier.
3. **Une boutique appartient à une seule entreprise.**
4. **Chaque entreprise est indépendante** ; aucune donnée n’est partagée entre deux entreprises.
5. **Les modules sont indépendants** dans leur responsabilité, même lorsqu’ils déclarent des dépendances explicites.
6. **Les modules sont activables et désactivables.**
7. **Les données sont isolées** ; la sécurité des périmètres est non négociable.
8. **Les utilisateurs appartiennent à une entreprise** et agissent selon leur rôle et leurs permissions.
9. **De nouvelles activités et de nouveaux modules** doivent pouvoir être ajoutés sans modifier le cœur de GreenPOS.
10. **La simplicité prime sur la complexité.**
11. **Le client n’active que ce dont il a besoin** ; rien d’inutile ne doit être imposé.
12. **Le POS est un module**, jamais le substitut de la plateforme entière.
13. **Aucune décision future** ne peut justifier de briser l’isolation entre entreprises.
14. **Toute ambiguïté organisationnelle** (où vit une donnée ? à qui appartient une boutique ? quel module est actif ?) doit être résolue avant livraison.

---

## Conclusion

Ces principes ne sont pas des suggestions.  
Ils sont la **référence métier permanente** de GreenPOS.

Toute proposition de fonctionnalité, d’écran, de parcours ou d’évolution devra pouvoir répondre clairement à ces questions :

- À quel niveau de la hiérarchie cela appartient-il ?
- Quelle entreprise est concernée ?
- Quelle boutique, le cas échéant ?
- Quel module active cette capacité ?
- L’isolation des données est-elle préservée ?
- La simplicité et la modularité sont-elles respectées ?

Si la réponse est floue, la proposition n’est pas prête.

**Document suivant recommandé :** formaliser le catalogue d’activités, le catalogue de modules, et les règles d’activation / dépendances entre modules.

---

*GreenPOS v3 — Document produit officiel — 02_PRODUCT_PRINCIPLES.md*
