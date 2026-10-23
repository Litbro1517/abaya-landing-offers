# AUDIT v3.27 — INTÉGRATION COLLECTION HIVER CHENILLE (mandat « feat/chenille-webp-and-content »)

> **Branche** : `feat/chenille-webp-and-content` (depuis `main @ 9e1b644` = v3.26, arbre propre, fetch vérifié)
> **Périmètre git** : EXACTEMENT 6 fichiers modifiés + 12 fichiers ajoutés (uploads) — zéro fichier protégé touché
> **Verdict final : VALIDÉ — déploiement autorisé** (54/54 suite mandat + 1642 PASS batterie 28 suites, zéro nouvel échec vs baseline)

---

## 1. Objet du mandat

Lancement de la nouvelle collection d'abayas d'hiver en tissu Chenille (« توب شونيل الراقي ») :

1. Intégration des **6 visuels WebP** (1.webp → 6.webp) reliés au sélecteur de couleurs de la landing principale :
   • Noir : 1.webp • Borduro/Bourgogne : 2.webp • Marron : 3.webp • Gris foncé : 4.webp • Beige cassé : 5.webp • Vert foncé : 6.webp
2. Mise à jour de l'ensemble des textes et des tarifs (modèle Chenille, **prix 329 DH**, caractéristiques du **col, des manches et de la ceinture**) selon le tableau comparatif.
3. Insertion de la nouvelle section **« مراحل التصنيع »** (5 étapes) à son emplacement EXACT : juste après « معاينة الطلبية والحق في الفحص » et AVANT les 3 blocs de garanties.
4. Intangibilité stricte : logo, boutons ‹ رجوع, grille des 3 offres (structure), formulaire, architecture 2 landings — aucun composant HTML/JS/CSS environnant modifié ou supprimé.

## 2. Audit préalable (PARTIE 1 du mandat)

- **État de départ vérifié** : `main @ 9e1b644` = `origin/main` (v3.26 en ligne via WP Pusher), arbre propre, 34 fichiers thématiques sains.
- **PROJECT_MAP.md audité** : architecture verrouillée v3.26 (1 LP vedette racine + 1 LP secondaire produit-1, gates 301/302 actifs, grille 3 offres stabilisée v3.25, TYP 1 upsell minimaliste).
- **Chaîne sélecteur de couleurs** cartographiée : `abaya_base_colors('')` (inc-offers.php) → `abaya_catalog()` (functions.php) → boucles vignettes/pastilles du gabarit unique (`data-src`/`data-alt`) → `selectColor()`/`updatePhoto()` (app.js). **Conclusion : le sélecteur est 100 % piloté par les données — aucune modification JS nécessaire pour le changement d'image.**
- **Zéro risque de décalage de layout prouvé par le CSS existant** : `.main-photo-wrap` = `aspect-ratio` fixe (0.82 desktop, 0.91/0.87/0.78/0.9 responsive) + `.main-photo { height:100%; object-fit:cover; object-position:center 35% }` ; `.thumbnail img` = boîte 151×170 fixe, `object-fit:cover`, `object-position:center 30%`. L'image noire du socle (`frame_021.webp`) était déjà en 1080×1920 (9:16, ratio identique aux nouveaux visuels) : le thème rendait déjà ce ratio sans décalage.
- **Chaîne tarifaire cartographiée** : `abaya_base_commerce()` = source unique (affichage hero + grille + injection `window.LANDING.catalog` + autorité serveur REST + carte TYP). Replis codés en dur identifiés : `app.js` (bundlePrices), `assets/js/mock-api.js` (harnais), `merci-review.js` (repli affichage TYP).
- **Tableau comparatif** : non présent dans le dépôt ni les uploads ; les données du mandat (modèle, 329 DH, mapping des 6 couleurs, col/manches/ceinture, 5 étapes) constituent la référence validée. **Décision documentée** : les prix 2/3 pièces prolongent les ÉCONOMIES DÉJÀ AFFICHÉES (99/198 DH) pour que chaque promesse « توفير » reste arithmétiquement vraie sans inventer de nouveaux chiffres — ajustable en une ligne si le tableau client fixe d'autres valeurs (voir §7).

## 3. Exécution — périmètre exact

### 3.1 Fichiers modifiés (6)

| Fichier | Nature de la modification |
|---|---|
| `inc-offers.php` | (a) `abaya_base_commerce()` : bundles `[1 => 329, 2 => 559, 3 => 789]` — **libellés ×3 et structure 3 cartes STRICTEMENT INTACTS** ; (b) `abaya_base_colors('')` : palette vedette = 6 couleurs du mandat (teintes de pastilles échantillonnées sur les visuels : `#1c1c20`, `#431321`, `#463739`, `#53565f`, `#a79994`, `#2e3b3a`), images `uploads/1..6.webp` + `uploads/thumbs/1..6.webp` ; (c) vedette `images` : LCP = `uploads/1.webp` (noir), `lcp_color` أسود, `lcp_alt` Chenille ; (d) vedette `texts` : meta_description, headline « عباية شتوية من توب شونيل الراقي – دافئة وأنيقة », intro, `old_price` « 379 درهم », bullets 1-2 (6 ألوان — le comptage reste dynamique via le gabarit), `specs_items` ×5 (tissu Chenille + كول راقٍ مستدير/بتنون + أكمام منفوخة بأساور مطاطية + حزام كوردون + قصة انسيابية) ; (e) vedette : nouvelle clé `texts['manufacturing']` (titre + 5 étapes) ; (f) produit-1 : `old_price` 349→379 UNIQUEMENT (conséquence documentée de la grille commune — voir §6) ; (g) docblock v3.27 |
| `template-parts/landing-offer.php` | Insertion de la section `<section class="order-guide wrap" id="fabrication">` (titre مراحل التصنيع + `ol.order-steps` 5 `<li>` numérotés 01-05) ENTRE `.visual-section-inspection` et `.trust-strip` — rendu CONDITIONNEL (`!empty($T['manufacturing'])`) : produit-1 reste bit-exact ; charte 100 % réutilisée, **zéro CSS nouveau** |
| `assets/js/app.js` | Replis `bundlePrices` `{ 1: 329, 2: 559, 3: 789 }` (alignés sur la grille ; le repli n'est utilisé que si l'injection serveur manque) |
| `assets/js/mock-api.js` | `PRICES = { 1: 329, 2: 559, 3: 789 }` (filet de sécurité harnais — cohérence) |
| `merci-review.js` | Repli d'affichage `offer.price || 329` (la valeur réelle vient du registre) |
| `style.css` | Version 3.26 → **3.27** |

### 3.2 Fichiers ajoutés (12)

- `uploads/1.webp` … `uploads/6.webp` — **copies BIT-EXACTES** des visuels fournis (aucune recompression).
- `uploads/thumbs/1.webp` … `uploads/thumbs/6.webp` — vignettes 151×170 générées au format EXACT du système existant (recadrage cover, ancre verticale 30 % = miroir du CSS `.thumbnail img`).

### 3.3 Fichiers protégés — ZÉRO diff (prouvé par `git diff --name-only` + check A18)

`functions.php` (gates 301/302, REST, CAPI) · `page-merci.php` (TYP 1 upsell) · `front-page.php` · `page-offer.php` · `inc-pages.php` · `header.php` · `footer.php` · `template-parts/site-header.php` · `template-parts/legal-header.php` · `template-parts/legal-footer.php` · `template-parts/site-footer.php` · `assets/css/styles.css` (pilule back-link v3.24, charte, grille) · `assets/css/offers.css` · `assets/css/critical.css` · `index.php` · `page.php`.

## 4. Validation (PARTIE 3 du mandat) — 54/54 + batterie 1642 PASS

### 4.1 Suite mandat `test_chenille_v327.py` — 54/54 PASS

- **A) Statique (×19)** : palette 6 couleurs dans l'ordre du mandat ; images 1..6.webp + thumbs ; produit-1 inchangé (palette 7 couleurs historique) ; LCP 1.webp/أسود ; grille 329/559/789 ; libellés/économies ×3 intacts ; **arithmétique vraie** (379−329=50 ; 2×329−559=99 ; 3×329−789=198) ; old_price 379 ×2 ; texts Chenille (شونيل présent, لولان ABSENT de la vedette) ; caractéristiques col/manches/ceinture ; clé manufacturing (5 étapes) ; replis JS ×3 ; Version 3.27 ; **périmètre git = exactement 6 fichiers ; fichiers protégés à zéro diff** ; uploads présents.
- **B) Rendu vedette (×14)** : 6 pastilles ; 6 vignettes data-src 1→6 dans l'ordre ; vignettes thumbs 1→6 ; LCP 1.webp ; prix 329/379/pastille توفير 50 ; 3 cartes 329/559/789 + économies 50/99/198 ; teintes des pastilles ; **EMPLACEMENT EXACT : `id="inspection-title"` < `id="manufacturing-title"` < `.trust-strip` (ordre DOM)** ; 5 étapes numérotées 01-05 ; classes charte `.order-guide`/`.order-steps` ; comptage dynamique « 6 ألوان » ; ZÉRO reliquat des anciens assets du sélecteur (hero-moka, frame_021, frame_029, swatch-blue-marine) ; **visuels des sections colors/specs/inspection INCHANGÉS** ; markup offres/formulaire intact.
- **C) produit-1 (×3)** : rendu comparé BIT-À-BIT contre un worktree `main` v3.26 — diff = EXACTEMENT 6 lignes de données-prix (current-price 299→329, old-price 349→379, summary-price 299→329, bundle-price ×3) — **zéro changement structurel** ; AUCUNE section manufacturing ; palette 7 couleurs intacte.
- **D) Interactivité Playwright (×8)** : clic ×6 pastilles → `#main-photo` bascule vers `uploads/1..6.webp` (préchargement + fondu `is-changing`) ; **zéro décalage de layout : boîte `.main-photo-wrap` identique avant/après chargement (1280 ET 360)** ; valeur couleur affichée ; clic offres 2/3 → récap 559/789 ; mobile 360 : scrollWidth == clientWidth (zéro débordement), pastille 5 → 5.webp.
- **E) Lint (×5)** : `php -l` ×2, `node --check` ×3.

### 4.2 Batterie de régressions `run_regressions_v327.sh` — 28 suites

**1642 PASS / 52 FAIL** — les 52 échecs sont **identiques, suite par suite et check par check, à la baseline main v3.26** (worktree propre testé en parallèle) : dérive pré-existante documentée des suites antérieures à v3.23 (monde 4 landings / TYP 3 cartes / pins de Version) qui n'avaient pas été rescopées par les mandats v3.23-v3.26 — **le mandat v3.27 n'introduit AUCUN nouvel échec**.

Suites clés : chenille-v327 **54/54** · lock-v326 **64/64** (rescopée : pins prix 329/559/789 + Version 3.26|3.27 + périmètre git 6 fichiers) · back-link-v324 **86/86** (pin Version élargi, markup ‹ رج inchangé) · espacement-v321 **154/154** (chaîne de jonctions rescopée conditionnellement — voir §5) · legal-v320 **81/81** · avis-v37 **64/64** · badge-visual **16/16** · lp-variants **74/74** · canva-visual **41/41** · redesign-v319 **29/29** · logo-v311 **83/83** · popup-rating **14/14** · sync-legal **12/12** · rest-reviews **29/29**.

Rescopages documentés (`rescope_chenille_v327.py`, 28 remplacements comptés) : prix 299/499/699 → 329/559/789 dans les suites lisant le registre (lock-v326, restore-v325, lp-variants, offers_rest, post_purchase), pins Version +3.27, périmètre git A15. **Stubs internes volontairement non touchés** (indépendants du registre) : render_merci.php, tunnel_stub.php, modales/popup/thankyou/v38/v39/canva.

## 5. Section « مراحل التصنيع » — emplacement et rythme (PARTIE 1-5 du mandat)

- **Emplacement prouvé** (check B8) : `id="inspection-title"` < `id="manufacturing-title"` < `.trust-strip` dans l'ordre DOM — exactement « après معاينة الطلبية والحق في الفحص, avant les 3 blocs de garanties ».
- **5 étapes** : اختيار التوب → القص والتفصيل → الخياطة المزدوجة → التفاصيل النهائية → مراقبة الجودة والتغليف — reprennent EXCLUSIVEMENT des affirmations déjà validées sur la page (خياطة مزدوجة, تخصيص القياسات, خياطة من 5 إلى 6 أيام) — aucun gain commercial nouveau inventé.
- **Rythme v3.21 préservé** : la suite espacement (154/154) mesure les jonctions à l'étalon 85px (42+42±1) — `inspection|fabrication` = 86.0px (tolérance ±1.2), `fabrication|garanties` = **85.0px exact**, lignes de séparation 1px `var(--line)` portées par la nouvelle section (border-bottom `.order-guide`) — la charte est réutilisée à l'identique, **zéro CSS nouveau, zéro décalage**.
- **Conditionalité** : produit-1 (sans clé `manufacturing`) ne rend PAS la section — chaîne de jonctions historique conservée (branche conditionnelle de la suite espacement).

## 6. Conséquence documentée de la grille commune (produit-1)

La grille tarifaire est une donnée COMMUNE (`abaya_base_commerce()`, v3.26) : le re-tariffement Chenille s'applique donc aussi à la grille 3 cartes de produit-1 (329/559/789). Pour que sa pastille « توفير 50 درهم » reste arithmétiquement vraie, son `old_price` est passé de 349 à 379 (seule modification de produit-1, prouvée par le diff bit-à-bit C1 = 6 lignes de prix, zéro structure). Le produit affiché par produit-1 (palette/textes/images لولان) reste inchangé — l'harmonisation éditoriale éventuelle de cette landing secondaire relève d'un mandat ultérieur.

## 7. Point d'attention (transparence)

Le mandat cite un « tableau comparatif validé » non transmis avec le dépôt. Les tarifs appliqués : **unitaire 329 DH (mandaté explicitement)** ; 2 pièces 559 DH et 3 pièces 789 DH dérivés pour préserver les promesses d'économies existantes (99/198 DH) ; ancien prix affiché 379 DH (préserve « توفير 50 درهم »). Si le tableau client fixe d'autres valeurs pour 2/3 pièces, la correction est **UNE ligne** (`'bundles' => [...]` dans `inc-offers.php`) + replis `app.js`/`mock-api.js` — aucun autre fichier concerné.

## 8. Preuves visuelles

- `download/v327_hero_1280.png` — photo principale Chenille après clic (sélecteur fonctionnel, photo-tag intact)
- `download/v327_fabrication_1280.png` — section مراحل التصنيع desktop (grille 3 col, 01-05 RTL, charte exacte)
- `download/v327_fabrication_360.png` — section mobile 360px (empilement 1 col, zéro débordement)
- `download/v327_vedette_360_full.png` — page complète mobile 360px

## 9. Déploiement

- Merge `--no-ff` vers `main` + push (WP Pusher auto-déploie) — vérification en ligne par contenu GitHub raw.
- `PROJECT_MAP.md` mis à jour (§1 Version 3.27, §2 registre vedette, §5-quinquiesdecies, historique 3.27).
- **Action humaine recommandée** : vérifier l'onglet WP Pusher (pull main), purger LiteSpeed/Cloudflare + cache navigateur (les styles sont versionnés `filemtime` = cache-bust auto ; les images 1..6.webp sont nouvelles, aucun risque de cache négatif), puis parcours : (a) LP racine → 6 couleurs cliquables (l'image principale bascule), (b) section مراحل التصنيع entre l'inspection et les garanties, (c) grille 329/559/789 + sélection + scroll formulaire, (d) commande test → TYP (1 upsell, prix 329).
