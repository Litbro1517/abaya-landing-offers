# RAPPORT DE MISSION — v3.39 RESTAURATION ET SYNCHRONISATION DES LANDING PAGES (branche `fix/logique-recuperation-landing`, mandat 4P LP)

**Date** : 01/11/2026 · **Base** : `origin/main` = v3.38 (bd684c0) · **Branche** : `fix/logique-recuperation-landing`
**Commit de correction** : `414a12d` (8 fichiers, +79/−39 — PHP/JS uniquement, ZÉRO CSS, ZÉRO gabarit visuel)

---

## 1. Verdict de conformité

| Exigence du mandat | Statut |
|---|---|
| Branche isolée `fix/logique-recuperation-landing`, aucun commit direct sur `main` | ✅ branche créée depuis `origin/main`, poussée seule — **merge en attente du feu vert** |
| Design/structure/CSS des landing pages INTACTS (zéro régression esthétique) | ✅ ZÉRO ligne CSS modifiée, ZÉRO gabarit visuel — géométrie Playwright **identique à la référence v3.38** @1280 et @360 (9 blocs ×3 pages, §5.3-D) |
| Audit initial (historique des anciennes versions : récupération données + transmission TYP) | ✅ §2 — logique ancienne = `item->get_name()` (v2.2:196 → v3.26:497) + grille canonique 299/499/699 (v3.25 `208d3ba` / v3.26 `35528c2`) |
| Grille tarifaire officielle 299/499/699 (barré 349 + proportionnels) | ✅ restaurée sur les DEUX landings (§3.1) — économies 50/99/198 arithmétiquement VRAIES |
| Nom TYP = titre court officiel configuré (adapté au tableau) | ✅ `short_title` ×2 offres + `set_name()` sur la ligne d'article WooCommerce (§3.2) |
| Prix et variantes transmis proprement, sans fausser le 1ᵉʳ article | ✅ prix serveur autoritaire (inchangé, vérifié), variantes PAR LIGNE en TYP (§3.3), upsell = ligne séparée (§5.2-G6) |
| Autocontrôle final du tunnel complet | ✅ 156 vérifications vertes + sonde produits ×4 (§5) |
| **AVENANT** — ID produits : méthode de liaison déduite de PROJECT_MAP + sources (sans accès wp-config) | ✅ §2.4 — chaîne complète documentée (constante → registre → handlers → Item/Order Meta → TYP → Pixel/CAPI) |
| **AVENANT** — multi-produits (1, 2 produits et plus) sans retouche de code | ✅ §3.5 — résolution générique `abaya_offer_product_id()` par convention de slug, repli historique respecté, sonde ×4 configurations |
| PROJECT_MAP.md mis à jour | ✅ §5-vigesimus-septimo + historique 3.39 + en-tête Version 3.39 |
| Feu vert avant merge | ⏸ **en attente d'arbitrage** |

---

## 2. AUDIT INITIAL (résultats)

### 2.1 Architecture réelle de la liaison de données (lecture du code v3.38)
1. **Affichage landing** : 100 % data-driven depuis le registre `inc-offers.php` (`abaya_base_commerce()` grille commune + override vedette) → `landing-offer.php` (hero `#current-price`/`#old-price`/pill + 3 cartes prix/barré/économie) ; `window.LANDING.catalog.bundles` injecté du registre → `app.js` (récap + `offer_total`). Aucun prix dur dans les gabarits.
2. **Tunnel REST** (`functions.php`) : prix AUTORITAIRE serveur depuis le catalogue (`$cat['bundles']`, jamais le prix produit WC), whitelist couleur/taille PAR OFFRE, méta par ligne (`اللون`/`المقاس`/`تفاصيل القطع` régénérées serveur), idempotence 10 min, anti-flood 5/10 min, CAPI dédupliquée. L'upsell (`add-to-order`) ajoute une ligne séparée — le 1ᵉʳ article n'est jamais touché. **Cœur sain, conservé tel quel.**
3. **TYP** (`page-merci.php`) : récapitulatif rendu depuis la commande WooCommerce validée par son duo order+key (`abaya_resolve_tunnel_order`, 48 h) ; total serveur ; nom de ligne = `$abaya_item->get_name()`.

### 2.2 Logique des anciennes versions (conformément au mandat)
- `git show 208d3ba` (v3.25 « RESTAURATION de la grille ») : **bundles [1=>299, 2=>499, 3=>699], économies 50/99/198, refs barrées = N × prix unitaire (598/897)** — c'est LA grille canonique « officielle » du dépôt ; v3.26 (`35528c2`) la déclare « STABILISÉE et INTACTE » sur les 2 landings.
- Le nom TYP : `git show 1a88d44:page-merci.php` (v2.2) et v3.26 affichaient DÉJÀ `item->get_name()` — la mécanique ancienne est correcte ; c'est la DONNÉE portée par la ligne de commande qui était périmée (titre du produit WooCommerce lié, produit n° 15 = ancien produit « عباية دريفية »).

### 2.3 Défauts constatés (conformes à la conjoncture du mandat)
| # | Défaut | Preuve | Cause racine |
|---|---|---|---|
| D1 | Prix périmés sur les 2 landings + tunnel | registre : commune 329/559/799 (refs 379/658/987, توفير 50/99/188) ; vedette override 479/799/1099 (refs 529/958/1437, توفير 50/159/338) ; TYP capture #135 à 479 | overrides successifs v3.27/v3.28 (Chenille) puis v3.30/v3.35 (vedette « Abaya Laine ») — jamais revenus à la grille canonique |
| D2 | Nom TYP périmé (« عباية دريفية » au lieu du titre court du produit vendu) | `page-merci.php:497` (`item->get_name()`) — captures TYP #135 | la ligne de commande porte le titre du produit WooCommerce lié au registre (produit 15 = ancien produit) — aucune donnée « titre court » n'existait côté offre |
| D3 | Variantes FAUSSÉES sur la 2ᵉ ligne TYP | captures #135 : deux lignes « أسود / L » — `page-merci.php:615` rendait la méta au niveau COMMANDE (`_abaya_color/_abaya_size`) pour TOUTES les lignes | les bonnes valeurs existent en Item Meta par ligne (écrites par les handlers) mais n'étaient pas lues par ligne |
| D4 | Prix hardcodés (replis) | `app.js:30` {1:479,2:799,3:1099} ; `mock-api.js:5` idem ; `merci-review.js:364` repli `\|\| 329` | replis datés des grilles v3.30/v3.27 (jamais exécutés en production mais sources de confusion) |

### 2.4 AVENANT — méthode de liaison des ID produits (déduite de PROJECT_MAP + sources, sans accès wp-config)
La chaîne complète, reconstruite depuis PROJECT_MAP §1 (tableau des offres : vedette → `LANDING_PRODUCT_ID` (15), produit-1 → `LANDING_PRODUCT_ID_1`, produit-2/3 → `LANDING_PRODUCT_ID_2/3` — retirées) et §2 (replis : « `LANDING_PRODUCT_ID_N` absent → produit vedette »), le modèle `wp-config-meta-sample.php` (staging : `define('LANDING_PRODUCT_ID', 15)` — « ID du produit WooCommerce réellement en ligne ») et les sources :
1. **wp-config.php** (serveur, hors dépôt) : constantes `LANDING_PRODUCT_ID[_N]` + `META_PIXEL_ID`/`META_CAPI_TOKEN` ;
2. **Registre** `inc-offers.php` : chaque entrée résout son `product_id` ( prix pack = grille du registre, le prix WooCommerce n'est plus consulté) ;
3. **Handlers REST** : `$offer['product_id']` → `wc_get_product()` → `add_product()` (prix pack imposé sur la ligne) → Item Meta (`اللون`/`المقاس`/`تفاصيل القطع`/`Offre`/`SKU`) + Order Meta (`_abaya_product_id`, `_abaya_offer`, `_abaya_offer_id`, `_abaya_offer_name`) ;
4. **TYP** : méta `_abaya_offer` → registre → catalogue (couleur/image) ; nom de ligne = `item->get_name()` (titre court v3.39) ; total serveur ;
5. **Pixel/CAPI** : `content_ids` = product_id de l'offre (ViewContent + Purchase dédupliqués).

---

## 3. SOLUTIONS IMPLÉMENTÉES (commit `414a12d` + avenant)

### 3.1 Grille tarifaire officielle — restauration (D1)
- `abaya_base_commerce()` : **bundles [1=>299, 2=>499, 3=>699]** ; labels intacts (قطعة واحدة/قطعتان/3 قطع) ; économies **توفير 50/99/198 درهم** ; refs barrées **349/598/897** — « prix barré proportionnel calculé » du mandat = prix unitaire × quantité, exactement le schéma canonique v3.25 (658 = 2×329, 987 = 3×329) ; chaque économie reste VRAIE : 349−299=50 ; 598−499=99 ; 897−699=198.
- **Vedette** : l'override `bundles`/`bundle_labels` (v3.30/v3.35) est RETIRÉ → les DEUX landings consomment la même grille commune (unification demandée). Hero `old_price` ×2 → **« 349 درهم »** (l'écart de 50 درهم est maintenu).
- Replis JS alignés : `app.js` {1:299,2:499,3:699}, `mock-api.js` PRICES, `merci-review.js` \|\|299. Le tunnel (prix serveur) suit automatiquement le registre — aucune modification du handler de prix nécessaire (il lisait déjà `$cat['bundles']`).

### 3.2 Titre court officiel vers la TYP (D2)
- Nouvelle clé de registre **`short_title`** par offre (documentée dans le docblock « Clés de chaque entrée ») : vedette **« عباية شتوية من توب الصوف »**, produit-1 **« عباية خريفية من توب لولان »** — forme officielle du produit (premier segment du headline, sans slogan), dimensionnée pour le tableau récapitulatif.
- Handlers REST `create-order` ET `add-to-order` : après `add_product()`, **`$item->set_name($offer['short_title'])`** — le nom court est écrit DANS l'objet WooCommerce (ligne d'article) : la TYP (`item->get_name()` — mécanique ancienne conservée), l'admin WooCommerce et les e-mails affichent tous le même nom officiel. L'upsell n'écrit QUE sa propre ligne.
- Repli TYP : nom de ligne vide → `short_title` de l'offre → nom éditorial générique (dernier ressort, inchangé).

### 3.3 Variantes par ligne en TYP (D3)
- `$abaya_rows` construit avec **couleur/taille PAR LIGNE** depuis l'Item Meta `اللون`/`المقاس` ; repli méta au niveau commande pour la 1ʳᵉ ligne uniquement (compat commandes historiques, comportement ancien préservé). La cellule récapitulatif rend les valeurs de ligne — la ligne d'upsell affiche SES variantes, plus celles du 1ᵉʳ article.
- Périmètre des prix multi-pièces : le résumé lisible « بني كريمي + أسود » / « M + L » (méta commande v3.30) reste affiché sur la ligne unique (comportement documenté, inchangé).

### 3.4 Zéro esthétique
- `styles.css`, `critical.css`, `offers.css`, `style.css` (hors bump Version 3.39), gabarits HTML/CSS : **zéro ligne modifiée**. Les seuls changements visibles = TEXTES de prix (mandatés) et le nom/variantes du récap TYP (mandatés).

### 3.5 AVENANT — anticipation production : résolution générique multi-produits (1, 2, N)
Les résolutions étaient codées en dur par entrée (`$default_product` pour la vedette, `LANDING_PRODUCT_ID_1` pour produit-1) : une future 3ᵉ offre aurait exigé de retoucher le registre. Nouveau helper unique **`abaya_offer_product_id($offer_id)`** (inc-offers.php, défini avant `abaya_offers()`) :
- vedette (`accueil`) → `LANDING_PRODUCT_ID` ; `produit-N` → `LANDING_PRODUCT_ID_{N}` ; autre slug futur → `LANDING_PRODUCT_ID_{SLUG_NORMALISÉ}` ;
- **repli historique documenté respecté** : constante absente → produit vedette (`LANDING_PRODUCT_ID`, lui-même replié sur 15 — « config production ») — ZÉRO changement comportemental pour les configurations existantes ;
- les 2 entrées du registre consomment le helper (`abaya_offer_product_id($front_offer)` / `abaya_offer_product_id('produit-1')`) — **l'ajout d'une offre (produit-2, produit-3, …) = une entrée de registre + sa constante wp-config, AUCUN code de résolution** ;
- traçabilité et cohérence TYP inchangées (§2.4) : `$offer['product_id']` consommé tel quel par les handlers ; titre court v3.39 porté par la ligne ; `_abaya_offer` → registre.

---

## 4. 🚨 POINTS D'ARBITRAGE (transparence — à trancher au feu vert)

1. **Interprétation du « prix barré proportionnel »** : retenu **refs = N × prix unitaire** (598/897, économies affichées 99/198) — c'est le schéma CANONIQUE du dépôt (v3.25/v3.28 : 658=2×329, 987=3×329, « économies affichées 99/198 » documentées). L'alternative (refs = N × 349 = 698/1047, économies 199/348) n'a aucun précédent dans le dépôt. Un changement d'interprétation = 2 lignes dans `abaya_base_commerce()` uniquement.
2. **Application de la grille aux DEUX landings** (vedette 479/799/1099 → 299/499/699) : retenu parce que le mandat parle d'UNE « grille tarifaire officielle » singulière, que « le tunnel » (TYP 479/808 observés) est cité parmi les prix erronés, et que la grille 299/499/699 est historiquement LA grille des deux landings (v3.25/v3.26). Si la vedette devait conserver un positionnement supérieur, réintroduire un override `bundles` sur son entrée de registre (1 ligne).
3. **Valeurs des titres courts** : « عباية شتوية من توب الصوف » / « عباية خريفية من توب لولان » = forme officielle (headline sans slogan) — ajustables en 2 lignes de registre si le client préfère une autre formulation « configurée ».

---

## 5. AUTOCONTRÔLE FINAL (156 vérifications vertes)

### 5.1 Contrôles statiques
`php -l` ×4 (inc-offers, functions, page-merci, landing-offer) + `node --check` ×3 (app.js, mock-api.js, merci-review.js) : **tous verts**. Scan des reliques de prix : plus aucune valeur 479/529/559/799/1099/1437/958/379 dans le code actif (reliques restantes = commentaires historiques + faux positifs SVG/hex).

### 5.2 Tunnel REST — `scripts/test_tunnel_v339.php` : **51/51**
Harnais WooCommerce stubs (héritier du harnais canonique 299/499/699 de l'époque v1.13, adapté au registre v3.39) :
- G1 grille ×2 landings + refs 349/598/897 + économies 50/99/198 + ARITHMÉTIQUE VRAIE + old_price 349 درهم ×2 + short_title ×2 ;
- G2 create-order : total 299 (jamais le prix produit 1234 injected), **nom de ligne = titre court** (pas « عباية دريفية »), Item Meta اللون/المقاس, CAPI 299 ;
- G3 multi-pièces : 499 (PAS 598) / 699 (PAS 897), تفاصيل القطع régénérée serveur ;
- G4 clamp 0/99 → 299/699 ; G5 whitelist PAR OFFRE (بني كريمي refusée sur produit-1 → 422, بيج refusée sur la vedette → 422, أسود/بيج valides chacune sur SA landing) ;
- **G6 upsell** : 2ᵉ ligne indépendante — nom = titre court produit-1, SES variantes (بيج/M), total 299+299=598, 1ᵉʳ article INTACT (nom/total/variantes), idempotence par `_abaya_upsell_event` ; G7 upsell 2 pièces = 499 ajouté (total 798) ;
- G8 non-régressions : honeypot 400, téléphone 422, idempotence event_id (1 commande, duplicate=true, même order_key), anti-flood 5→429, produit absent 500, event_id serveur, IP Cloudflare.

### 5.3 Rendu TYP — `scripts/test_merci_v339.php` : **20/20**
Scénarios rendus par le VRAI `page-merci.php` (stubs WC) :
- T1 commande 2 lignes (= capture client #135) : ligne 1 = titre court vedette + أسود/L, **ligne 2 = titre court produit-1 + بيج/M (SES variantes — plus celles du 1ᵉʳ article)**, prix 299×2, total 598 درهم, upsell ABSENT (2 offres achetées), nom générique « Abaya Collection » absent du récap ;
- T2 commande produit-1 : carte upsell = vedette (headline officiel, **299 درهم**, lien racine + duo order+key) ;
- T3 multi-pièces : résumé « بني كريمي + أسود », total 499 درهم, unitaire 249.50 درهم / القطعة ;
- T4 commande historique (pas d'Item Meta) : repli nom = titre court, variantes = méta commande (1ʳᵉ ligne) ;
- T5 gardes d'accès inchangées (clé invalide/sans paramètres → générique, zéro fuite).

### 5.4 Landing pages — `scripts/test_pricing_v339.py` (Playwright) : **27/27**
- A (vedette @1280) : hero 299/349/توفير 50 ; `LANDING.catalog.bundles` = {1:299,2:499,3:699} ; cartes 299/499/699 + barrées 349/598/897 + توفير 50/99/198 ; clic carte 2/3/1 → récap 499/699/299, sélecteur multi-pièces 2/3 pièces puis masqué, `f_total` = 499/699/299, is-selected + aria-pressed ;
- B (vedette @360) : zéro débordement, hero + cartes conformes, clic carte 2 → récap 499 + picker ;
- C (produit-1) : hero 299/349, cartes 299/499/699, SANS `.bundle-old` (charte vedette only), récap 499 au clic, `LANDING.offer_id` + bundles ;
- **D — ZÉRO RÉGRESSION GÉOMÉTRIQUE** : boîtes de 9 blocs (main/hero/gallery/wrap/photo/3 sections/offers/form) **identiques à la référence `origin/main` (v3.38)** @1280 et @360 sur les 2 pages — diff HTML branch/réf = exactement les 22 lignes de textes de prix.

### 5.5 Régressions comportementales — `scripts/test_validation_v339.py` : **35/35**
Batterie v3.35 rejouée avec les attentes de la grille officielle (adaptation documentée : 479→299, 529→349, 799/958/159→499/598/99, 1099/1437/338→699/897/198) : prix Hero/cartes/résumé, sous-titre H1 homogène mot-à-mot, **Shake & Red Border** complet (4 champs + blocs couleur/taille, #e74c3c, abaya-shake, messages « المرجو… »), C12-C16 (téléphone invalide, nettoyage, soumission, multi-picker), mobile 360 (barré 349, pill توفير 50, cadre rouge, zéro débordement).

### 5.6 CLS — `scripts/test_cls_v338.py` rejoué sur l'arbre v3.39 : **23/23**
CSS intouché par v3.39 : témoin v3.37 0,442 reproduit, branche **CLS 0,000** (styles.css retardé 1,2 s), géométrie finale identique — le correctif CLS v3.38 reste intact.

### 5.7 AVENANT — sonde convention produits `scripts/test_products_v339.php` : **4/4 configurations**
| Configuration wp-config simulée | vedette | produit-1 | produit-2 (futur) | slug éditorial |
|---|---|---|---|---|
| Aucune constante | 15 | 15 (repli) | 15 (repli) | 15 (repli) |
| `LANDING_PRODUCT_ID=20` seule | 20 | 20 (repli vedette) | 20 (repli) | 20 (repli) |
| 2 produits : 20 + `LANDING_PRODUCT_ID_1=21` | 20 | **21** | 20 (repli) | 20 (repli) |
| N produits : 20+21+`_2=22`+`_EDITION_OR=30` | 20 | 21 | **22** | **30** |
Après l'avenant : rejeu `test_tunnel_v339.php` **51/51** + `test_merci_v339.php` **20/20** (zéro changement comportemental).

---

## 6. Périmètre exact du diff (`origin/main..fix/logique-recuperation-landing`, commit `414a12d`)

```
assets/js/app.js                 |  5 ++-   (repli {1:299,2:499,3:699} + commentaire)
assets/js/mock-api.js            |  2 +-   (PRICES 299/499/699)
functions.php                    |  9 +++   (set_name ×2 handlers + commentaires)
inc-offers.php                   | 77 +++--  (grille officielle + short_title ×2 + old_price ×2 + docblock)
merci-review.js                  |  4 +--   (repli || 299)
page-merci.php                   | 17 +++-  (variantes par ligne + repli titre court)
style.css                        |  2 +-   (Version 3.39)
template-parts/landing-offer.php |  2 +-   (commentaire contrat §DATA)
8 fichiers — CSS : ZÉRO ligne · gabarit visuel : ZÉRO ligne · images : ZÉRO
```

---

## 7. Limites & suites (pour arbitrage)

1. **Produits WooCommerce liés** : les entrées du registre pointent toujours leurs `product_id` (constantes `LANDING_PRODUCT_ID[_1]` surchargeables dans wp-config.php). La ligne d'article affiche désormais le titre court officiel quelle que soit la liaison produit ; si le client souhaite aussi rattacher la vedette à un NOUVEAU produit WooCommerce (prix/stock côté WC), c'est un réglage wp-config, pas du code.
2. **Commandes existantes** : les lignes déjà créées (ex. #135) conservent leur nom d'origine (snapshot WooCommerce) — le titre court s'applique aux nouvelles commandes ; le repli TYP (titre court sur nom vide) ne concerne que les lignes sans nom.
3. **Re-mesure PSI/dev** après déploiement (hors périmètre : aucune modification de performance — CSS/chargement intacts).
4. **Tests de référence historiques** : `test_validation_v335.py` (35 checks) contient les prix v3.35 — la copie adaptée `test_validation_v339.py` (35/35) le remplace pour ce mandat ; l'original reste intact pour l'historique.

---

## 8. Statut final

- Branche **`fix/logique-recuperation-landing`** créée depuis `origin/main` (bd684c0) — commit de correction **`414a12d`** + commit documentation — poussée sur origin. **AUCUN merge vers main** (aucun commit direct sur main).
- Autocontrôle final : **156 vérifications vertes** (51 REST + 20 TYP + 27 Playwright + 35 validation + 23 CLS) **+ sonde convention produits 4/4 configurations** (avenant), zéro régression visuelle (géométrie == v3.38), zéro régression tunnel (idempotence/anti-flood/honeypot/CAPI/IP), zéro CSS.
- **En attente du feu vert d'arbitrage** : merge + push `main` via WP Pusher (points d'arbitrage §4 — interprétation des barrés proportionnels, périmètre de la grille, valeurs des titres courts).
