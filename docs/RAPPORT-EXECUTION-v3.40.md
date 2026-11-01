# RAPPORT D'EXÉCUTION v3.40 — DISSOCIATION DES PRODUITS & DES GRILLES TARIFAIRES PAR LANDING + TYPOGRAPHIE ARABE

**Mandat 4P LP** : « CORRECTION DE LA DISSOCIATION DES PRODUITS & TYPOGRAPHIE ARABE »
**Branche isolée** : `fix/dissociation-produits-landing` (base = `fix/logique-recuperation-landing` @ `e1f802e`, v3.39)
**Commits** : `066771b` (feat — dissociation) · `1a359f1` (fix — typographie arabe) · docs (rapport + PROJECT_MAP)
**Statut** : branche poussée — **merge vers `main` INTERDIT sans feu vert explicite** (PARTIE 1 & PARTIE 4 du mandat).

---

## 1. Audit préalable (PARTIE 1 — PROJECT_MAP, structure, scripts de résolution des offres)

### 1.1 Chaîne de résolution héritée (v3.39, branche `fix/logique-recuperation-landing`)

La v3.39 (avenant multi-produits) avait introduit un résolveur générique `abaya_offer_product_id($offer_id)` (inc-offers.php) :

| Étape | Mécanique | Résultat pour la landing secondaire |
|---|---|---|
| 1 | Convention de slug : vedette → `LANDING_PRODUCT_ID`, `produit-N` → `LANDING_PRODUCT_ID_N`, éditorial → `LANDING_PRODUCT_ID_{SLUG}` | `produit-1` écoutait **`LANDING_PRODUCT_ID_1`** |
| 2 | Repli historique documenté (PROJECT_MAP §2) : constante absente → produit vedette (15) | **`LANDING_PRODUCT_ID_2` du wp-config n'était JAMAIS consultée** → retombée systématique sur le produit de la landing principale |

### 1.2 Cause racine du défaut « même produit, même prix » (PROUVÉE)

Le wp-config.php de production définit les constantes **`LANDING_PRODUCT_ID`** (landing principale) et **`LANDING_PRODUCT_ID_2`** (landing secondaire). Le résolveur v3.39 ne s'en tenait qu'à la convention `_N` déduite du slug : `produit-1 → LANDING_PRODUCT_ID_1` (absente du wp-config) → repli → **produit vedette imposé à la page secondaire**. C'est exactement la conjoncture du mandat : « la page secondaire récupère par défaut le même produit et le même prix que la page principale, ce qui fausse le tunnel d'achat multi-produits ».

### 1.3 Prix — grille partagée + hardcodes résiduels

- La grille commerciale était **commune aux deux landings** via `abaya_base_commerce()` (299/499/699 — unification v3.39), donc structurellement incapable de distinguer les deux offres.
- Hardcodes de grille résiduels côté JS : `app.js` (`bundlePrices … || {1:299,2:499,3:699}`), `mock-api.js` (`PRICES = {1:299,…}`), `merci-review.js` (`offer.price || 299`).
- Le gabarit `landing-offer.php` est 100 % piloté par les données (hero `$cat['bundles'][1]`, `$T['old_price']`, cartes en boucle, refs/économies `$cat['bundle_labels']`) — **aucune modification de gabarit nécessaire** : la dissociation des données du registre propage partout (affichage, `window.LANDING.catalog`, autorité prix REST `$cat['bundles'][$quantity]`, carte upsell TYP `bundles[1]`, CAPI `ViewContent`).
- Histoire des prix (légitime rétrospectivement) : la vedette avait porté SA grille 479/799/1099 (v3.30, ajustée v3.35 : barré 529/توفير 50) ; la v3.39 l'avait retirée pour unifier sur 299/499/699. L'écran de référence du mandat (dernier screenshot) confirme que **les vrais prix du modèle sur la landing principale sont 479/529 — 799/958 — 1099/1437**, la landing secondaire restant à 299 comme actuellement.

### 1.4 Typographie arabe

La faute `لنستفيدي` existait aux **DEUX chemins de rendu de la TYP** : `page-merci.php:679` (rendu natif PHP) et `merci-review.js:411` (rendu mock/simulation), dans « أضيفي عباية أخرى لنستفيدي من الخصم المباشر في طلبكِ ».

### 1.5 Arborescence audité

`inc-offers.php` (registre + résolveur) · `functions.php` (`abaya_catalog()`, injection `window.LANDING`, handlers REST `create-order`/`add-to-order`, CAPI) · `page-offer.php` (résolution par slug) · `template-parts/landing-offer.php` (gabarit unique) · `page-merci.php` + `merci-review.js` + `mock-api.js` (TYP) · `assets/js/app.js` (front).

---

## 2. Solutions techniques retenues (PARTIE 3 — liberté d'implémentation, justifications)

### 2.1 Liaison explicite offre → constante wp-config (le correctif central)

Nouvelle table de liaison **donnée, pas logique** — `abaya_offer_product_constant($offer_id)` (inc-offers.php) :

```php
static $map = [
    'accueil'   => 'LANDING_PRODUCT_ID',     // landing principale
    'produit-1' => 'LANDING_PRODUCT_ID_2',   // landing secondaire (wp-config production)
];
```

`abaya_offer_product_id()` applique désormais la **chaîne de priorité dissociée** :

1. **Liaison explicite** (ci-dessus) — la constante déclarée par l'entrée, auditable en un seul regard ;
2. **Convention de slug** v3.39 (`produit-N → LANDING_PRODUCT_ID_N`, éditorial → `_SLUG`) — conservée pour les offres futures ;
3. **Repli historique** (constante absente → produit vedette) — conservé pour la sécurité de déploiement (dev/staging/harnais sans wp-config), mais **plus chemin nominal d'aucune landing active** en production.

**Justifications techniques** :
- **Pourquoi une table explicite plutôt que renommer le slug ?** L'URL `/produit-1/` est vivante (publicités, historique, SEO) : la renommer en `produit-2` pour « coller » à la constante `_2` aurait été une régression majeure interdite par le mandat. La liaison est donc déclarée en données — la convention générique reste la voie par défaut pour toute offre future (aucun code de résolution à retoucher).
- **Pourquoi conserver le repli historique ?** L'éliminer ferait planter le rendu (produit 0) dans tout environnement sans wp-config (harnais de test, staging frais). Il est documenté comme strictement dev/staging ; la priorité explicite garantit que la production dissociée ne l'emprunte plus jamais (prouvé par la sonde ×5 configurations).
- **Nettoyage thème** (PARTIE 1) : le « code en dur forçant un ID unique pour toutes les landing pages » est supprimé aux trois niveaux où il existait — (a) le repli résolveur devenait le chemin nominal de la secondaire (corrigé par la liaison explicite) ; (b) la grille commerciale partagée (corrigée par les grilles par entrée) ; (c) les grilles en dur JS (supprimées, §2.3).

### 2.2 Grilles tarifaires dissociées (SA grille dans SA propre entrée)

`abaya_base_commerce()` est réduite aux tailles communes ; chaque entrée du registre porte **sa** grille autoritaire :

| Landing | Offre 1 | Offre 2 (الأكثر طلباً) | Offre 3 | Barrés (unitaire × quantité) | توفير | old_price Hero |
|---|---|---|---|---|---|---|
| **Vedette** (accueil) | **479** | **799** | **1099** | 529 / 958 / 1437 | 50 / 159 / 338 | **529 درهم** |
| **Secondaire** (produit-1) | 299 | 499 | 699 | 349 / 598 / 897 | 50 / 99 / 198 | 349 درهم (inchangé) |

Arithmétique vérifiée par test (G1e) : 529−479=50 · 958−799=159 · 1437−1099=338 · 349−299=50 · 598−499=99 · 897−699=198 — **chaque « توفير » affiché est vrai**. Structure 3 cartes et libellés (قطعة واحدة/قطعتان/3 قطع) strictement intacts ; le rendu conditionnel des barrés (vedette seule) est inchangé.

### 2.3 Suppression des grilles en dur JS

- `app.js` : `bundlePrices = (LANDING.catalog && LANDING.catalog.bundles) || {}` — en production l'injection serveur est toujours présente (functions.php, landings uniquement) ; un objet vide = dégradation honnête, jamais un prix d'une autre landing.
- `mock-api.js` : `PRICES` = grille de **cette** landing lue dans `window.LANDING.catalog.bundles` (garde `?? 0` en bout de chaîne).
- `merci-review.js` : prix upsell mock `offer.price || 0` (la donnée vient de la config mock injectée par page-merci.php depuis le registre).

### 2.4 Typographie arabe (PARTIE 2 — consigne littérale)

`لنستفيدي` → **`لتستفيدي`** (ت au lieu de ن) dans « أضيفي عباية أخرى **لتستفيدي** من الخصم المباشر في طلبكِ » — page-merci.php **et** merci-review.js. Strictement le mot mandaté ; aucun autre caractère touché.

### 2.5 Périmètre diff (6 fichiers, zéro CSS, zéro gabarit visuel)

`inc-offers.php` (registre + résolveur) · `assets/js/app.js` · `assets/js/mock-api.js` · `merci-review.js` · `page-merci.php` · `style.css` (bump 3.40 uniquement). **Preuve bit-exact** (harnais déterministe v3.39 vs v3.40) : `produit-1.html` **0 octet de diff** ; `index.html` **22 lignes de diff = prix seuls** (hero prix/barré, résumé, 3 cartes × prix/barré/économie) — zéro structure, zéro CSS, zéro texte hors prix.

---

## 3. Autocontrôle final (PARTIE 1 — « self-check complet du tunnel »)

### 3.1 Suites dédiées v3.40

| Suite | Résultat | Périmètre |
|---|---|---|
| `php -l` ×5 + `node --check` ×3 | ✅ 0 erreur | Syntaxe PHP/JS |
| `test_tunnel_v340.php` | ✅ **53/53** | Grilles dissociées ×2 landings (G1a-G1l), arithmétique vraie ×6, liaison explicite, prix serveur create-order 479/799/1099 vs 299/499/699, bornes quantité, whitelist par offre (422 croisés), upsell add-to-order (479+299=778, 1ᵉʳ article intact, variantes par ligne), multi-pièces (978), idempotence, anti-flood 429, honeypot, CF-IP, CAPI |
| `test_merci_v340.php` | ✅ **22/22** | Récap TYP dissocié (479+299=778, titres courts par ligne, variantes par ligne), carte upsell vedette à **479** (SA grille), multi 799/399.50, **typo arabe (T2f « لتستفيدي » présent, T2g « لنستفيدي » absent)**, replis historiques, gardes d'accès |
| `test_dissociation_v340.php` | ✅ **25/25** | **Constantes production (20 + `_2`=21)** : registre → produits 20/21, create-order rattaché au BON produit par landing (`add_product`), Order Meta `_abaya_product_id`, `_abaya_offer`, CAPI dispatch `product_id`, upsell ligne 2 sur produit 21, grilles étanches (799 vs 499/699 vs 1099) |
| Sonde `test_products_v340.php` ×5 configs | ✅ **5/5** | Priorité prouvée : explicite `_2` (21) **gagne** sur la convention `_1` (22) ; convention en repli ; repli vedette (15) ; grilles + old_price stables dans toutes les configs |
| Bit-exact harnais v3.39 ↔ v3.40 | ✅ | produit-1 **0 octet** ; vedette **22 lignes = prix seuls** |

### 3.2 Playwright (navigateur réel, harnais v3.40)

| Suite | Résultat | Points clés |
|---|---|---|
| `test_pricing_v340.py` | ✅ **27/27** | Vedette : hero 479/529/توفير 50, cartes 479/799/1099 + barrés 529/958/1437 + توفير 50/159/338, `window.LANDING.catalog.bundles` dissocié, interactions cartes → récap 799/1099/479 + f_total, multi-picker ; **produit-1 : 299/349 + cartes 299/499/699 intacts** ; **géométrie == référence v3.39** @1280 et @360 (9 blocs) ; mobile 360 zéro débordement |
| `test_validation_v335.py` (replay) | ✅ **35/35** | La batterie prix/« Shake & Red Border » de la v3.35 (dont les assertions correspondent exactement au retour de la vedette aux vrais prix du modèle) passe intégralement |
| `check_parity_v338.py` (replay) | ✅ **17/17** | Parité critical.css ↔ styles.css (CSS zéro diff) |
| `test_size_bug_v331.py` (replay) | ✅ **41/44 = baseline main identique** | 3 écarts PRÉEXISTANTS documentés depuis v3.36 (2× 404 polices artefact harnais `__FONT_BASE__`, 1 assertion obsolète wool-2.jpg v3.34) — aucun nouvel écart |
| `test_images_v334.py` (replay) | ✅ **13/13** | Mapping wool-1..5, srcset, produit-1 inchangé |
| `test_cls_v340.py` | ✅ **4/4** | Banc CLS méthodologie exacte v3.38 (harnais production, styles.css retardé 1,2 s, 412×823 DPR 1.75) : **0,000** vedette et produit-1, **identiques au témoin v3.39** — le correctif CLS v3.38 reste intact |

**Total : 242 vérifications vertes** (53 + 22 + 25 + 27 + 35 + 17 + 41 + 13 + 4 + sonde ×5 + bit-exact + lint).

### 3.3 Validation du tunnel dissocié de bout en bout (résumé)

1. Landing principale → commande → ligne rattachée au produit `LANDING_PRODUCT_ID` (20), prix imposé **479/799/1099** (grille vedette), Item/Order Meta complètes, CAPI `content_ids = [20]` ;
2. Landing secondaire → commande → ligne rattachée au produit **`LANDING_PRODUCT_ID_2` (21)** — fin du tunnel faussé — prix imposé **299/499/699**, CAPI `content_ids = [21]` ;
3. Upsell depuis la TYP → 2ᵉ ligne sur le produit 21 au prix de la grille secondaire, 1ᵉʳ article (produit 20, 479) strictement intact → total 778 ;
4. TYP : chaque ligne affiche SON titre court, SES variantes et SON prix ; carte upsell au prix de SA propre grille.

---

## 4. Livrables (PARTIE 4)

| Livrable | État |
|---|---|
| Code corrigé sur la branche isolée `fix/dissociation-produits-landing` | ✅ commits `066771b` (feat) + `1a359f1` (typo) — base `e1f802e` (v3.39) |
| Rapport d'exécution détaillé | ✅ le présent document |
| PROJECT_MAP.md actualisé | ✅ §1 (constante secondaire + grille propre), §2 (chaîne de priorité + grilles dissociées), **§5-vigesimus-octavo** (nouveau), statut v3.39 annoté « supersédée pour la grille » |
| Branch pushed | ✅ `git push origin fix/dissociation-produits-landing` |
| Merge vers main | ⛔ **INTERDIT sans feu vert explicite** (validation des tests de non-régression + des grilles tarifaires dissociées) |

## 5. Points d'attention pour l'arbitrage

1. **Priorité de liaison** : si un environnement définit à la fois `LANDING_PRODUCT_ID_1` et `LANDING_PRODUCT_ID_2`, la liaison explicite `_2` gagne (déterministe, documenté, testé).
2. **Repli historique conservé** (constante absente → produit vedette) : strictement dev/staging/harnais ; en production les deux landings ont leur constante (prouvé par test_dissociation_v340).
3. **Replis JS à objet vide** : si l'injection `window.LANDING` manquait (impossible en production — injectée par functions.php sur toutes les landings), le front n'affiche plus un prix d'une autre landing mais dégrade honnêtement (le serveur reste la seule autorité sur le prix).
4. **Statut v3.39** : la branche v3.39 est incluse dans la base de celle-ci ; son unification de grille est **supersedée** par la dissociation v3.40, ses autres acquis (titre court TYP, variantes par ligne, résolveur générique) sont conservés et re-testés.

**Statut final : branche `fix/dissociation-produits-landing` poussée — en attente du feu vert. Aucun merge vers main n'a été exécuté.**
