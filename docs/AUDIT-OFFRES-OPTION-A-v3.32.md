# RAPPORT D'EXÉCUTION — v3.32 « OPTION A OFFRES + NETTOYAGE DES PRIX + SOUS-TITRE H1 »

**Mandat 4P LP** — branche active `feat/wool-abaya-product-adaptation` (continuité Wool).
**Périmètre** : vedette (racine) — design des offres, nettoyage des prix, sous-titre H1, liste des caractéristiques ; intégration temporaire des 2 JPG vérifiée.
**Référentiel design** : `AUDIT-OFFRES-v3.30.pdf` (audit design section offres, joint au mandat) — Option A validée par le mandat.

---

## 1. Audit préalable (état v3.31, AUCUNE modification avant ce relevé)

Relevé sur le dépôt à jour (`main` @ `cfb788b` = `feat/wool-abaya-product-adaptation` @ `44c27c2`, v3.31 déployée) :

| Point mandaté | État v3.31 constaté | Action v3.32 |
|---|---|---|
| Titre H1 `عباية شتوية من توب الصوف – انسيابية ودوبل كلوش` | ✅ déjà en place (v3.30) — vérifié mot-à-mot | Aucun changement |
| Grille 479/799/1099 (توفير 20/159/338) | ✅ déjà en place (v3.30) — arithmétique vraie | Aucun changement de données |
| Mention « الثمن المرجعي: 499/958/1437 درهم » | ❌ affichée sous l'économie (`.bundle-ref`, v3.28) | **SUPPRIMÉE** → prix d'origine barré gris sous le prix principal |
| Ruban « الأكثر طلباً » | ❌ émeraude `#3A7D84`, 9px (couche v3.0/v3.2) — même teinte que l'état sélectionné (constat audit : « une seule teinte, quatre métiers ») | **Option A** : bordeaux `#6B1D2F`, 11px/900 |
| État sélectionné | ❌ contour seul (écart bordures 2,7:1 < 3:1, fond transparent v3.4) | **Option A** : zone teintée émeraude 7 % + bordure `#3A7D84` (4,56:1) |
| Crochet de sélection | ❌ 20px (12px glyphe) | **Option A** : 26px, glyphe 13px/800, anneau blanc 3px `#fbfbf8` |
| Économies | ❌ `#3A7D84` (concurrence l'état) | **Option A** : `#2D6A70` (5,95:1 sur `#fbfbf8`) |
| Paragraphe sous le H1 | ⚠️ Fusha sommaire, pas de phrase de finition isolée | **Fusha enrichie + `intro_finish`** (ligne indépendante, centrée, fine, italique) |
| Caractéristiques produit | ⚠️ 9 lignes (dont الستايل والديكور et الخياطة والفينيسيون en double-emploi avec la phrase de finition) | **Liste finale : EXACTEMENT 6 lignes mot-à-mot** |
| 2 JPG provisoires (wool-1/wool-2 + thumbs) | ✅ intégrées v3.30, référencées (LCP + sections) | Vérification : HTTP 200 ×3 + rendu galerie + bascule couleur → wool-2 |

---

## 2. Exécution (4 fichiers)

### 2.1 Option A — `assets/css/styles.css` (bloc v3.32 en FIN de feuille, zéro règle existante modifiée)

Règles appliquées à l'identique du prescriptif §3 de l'audit (sélecteurs `:not(.is-upsell)` tels que donnés) :

1. **Ruban** `.bundle-card:nth-child(2)::after` → `background:#6B1D2F; color:#FAF7F2; font-size:11px; font-weight:900; letter-spacing:.02em; top:-12px; padding:3px 10px; box-shadow:0 2px 8px rgb(107 29 47 / 30%)`.
2. **État sélectionné** `.bundle-card.is-selected` → `border-color:#3A7D84; background:rgb(58 125 132 / 7%)` (annule le transparent v3.4 — « l'état occupe une zone, plus seulement un contour »).
3. **Crochet** `.is-selected::before` → `26×26px @ top/right −9px; font:800 13px/1 Cairo; box-shadow:0 0 0 3px #fbfbf8, 0 2px 8px rgb(44 29 17 / 22%)` (anneau blanc = détachement garanti mobile, même sur le ruban).
4. **Économies** → `#2D6A70`. **Adaptation de cascade assumée** : la règle unique de l'audit (spécificité 0,4,0) ne pouvait pas battre la couche v3.3 carte 2 (0,5,0, ligne 1862) — les DEUX sélecteurs (`.bundle-saving` et `.bundle-card:nth-child(2) .bundle-saving`) sont déclarés ensemble pour que les TROIS cartes passent au `#2D6A70`, conformément à l'intention documentée de l'audit (« sortir de l'émeraude pour ne pas concurrencer l'état », sans exception de carte).
5. **Mobile ≤540px** : ruban `9px / top −10px / padding 2px 7px` ; crochet `22×22px @ −7px, glyphe 11px` (les 2 ajustements mobiles de l'audit).

Grammaire des couleurs résultante (objectif de l'audit) : **l'émeraude dit « vous avez choisi »** (bordure + zone teintée + crochet), **le bordeaux dit « le plus demandé »** (ruban) — deux informations, deux couleurs, zéro collision.

### 2.2 Nettoyage des prix — `template-parts/landing-offer.php`

- Le `<span class="bundle-ref">الثمن المرجعي: …</span>` est **SUPPRIMÉ** (ainsi que sa règle CSS `.bundle-ref` v3.28 — remplacée par une note de sépulture).
- À sa place, **sous le prix principal de chaque carte** : `<del class="bundle-old" style="color: #888; font-size: 0.85em;">499 درهم</del>` — **style inline EXACT du mandat**, donnée `ref` du registre (499/958/1437) réutilisée telle quelle, `text-decoration: line-through` natif de `<del>`.
- Ordre dans la carte : badge → **prix principal** → **prix d'origine barré gris** → توفير (le `del` est bien SOUS le prix, mesuré Playwright : `del.top ≥ price.bottom`).
- Conditionnel `abaya_is_front_offer()` conservé : contenu vedette-seul.

### 2.3 Sous-titre H1 — `inc-offers.php` + gabarit + CSS

- **Paragraphe Fusha** (`intro`) : rédigé en arabe littéraire, faits exclusivement issus de la liste des caractéristiques (tوب صوف دافئ كثيف / دوبل كلوش واسع وفضفاض / لا يلتصق بالجسم ولا يظهر تفاصيله / ألوان هادئة وجذابة / احتشام + أناقة + حرية حركة).
- **Phrase de finition** (`intro_finish`, nouvelle clé) : `بالنسبة للفينيسيون نقية وخياطة متقونة من الداخل ومن برا` — **verbatim du mandat**, rendue en `<p class="product-intro-finish">` LIGNE INDÉPENDANTE : `display:block; text-align:center; font-weight:300; font-style:italic` (+ couleur texte courant `#55564f`).
- **Police fine réellement rendue** : Cairo est une police VARIABLE (axe wght 200–1000, vérifié fonttools) mais les déclarations existantes couvrent 400–900 (critical.css). Déclaration ADDITIVE `@font-face Cairo 200–399` (MÊME fichier woff2, unicode-range arabe identique) → la graisse 300 est réellement fine, **zéro requête réseau supplémentaire** (preload inchangé).
- Conditionnel `!empty($T['intro_finish'])` : produit-1 (sans la clé) bit-exact.

### 2.4 Liste finale des caractéristiques — `inc-offers.php`

`specs_items` : les 9 lignes v3.30 sont remplacées par **EXACTEMENT les 6 lignes de la matrice du mandat, mot-à-mot (ponctuation incluse)** :
نوع التوب / الانسدال والجودة / قصة العباية / **الفتحة والكول** (fusion) / الأكمام / الجيوب.
Les lignes الستايل والديكور et الخياطة والفينيسيون disparaissent (cette dernière migre vers le sous-titre H1 en phrase de finition).

### 2.5 Intégration temporaire des 2 JPG (PARTIE 1 du mandat)

Déjà exécutée en v3.30 (wool-1.jpg البني الكريمي LCP / wool-2.jpg الأسود + vignettes 151×170) — **re-vérifiée en v3.32** : HTTP 200 (wool-1 71 790 o, wool-2 61 060 o, thumb 200), rendu galerie capturé (`shots_v332/v332_gallery_1280.png`), bascule vignette 2 → wool-2.jpg fonctionnelle. En attente des WebP définitifs (consigne mandat inchangée).

---

## 3. Validation

| Contrôle | Résultat |
|---|---|
| `php -l` (inc-offers, landing-offer, functions) | ✅ 3/3 — zéro erreur (PHP 8.3) |
| `node --check app.js` | ✅ (fichier inchangé) |
| Équilibre accolades `styles.css` | ✅ 705 / 705 |
| Harnais déterministe (`harness_render_v329.php`) — **produit-1 main vs branche** | ✅ **BIT-EXACT : 0 octet de diff** |
| Harnais — diff vedette | ✅ 24 lignes = exactement les changements mandatés (2 intro/finition + 3 del − 3 ref + 15/6 specs), aucun autre nœud modifié |
| Playwright `shot_v332_visual.py` | ✅ **35 PASS / 0 FAIL** — ruban bordeaux `rgb(107,29,47)` + texte `#FAF7F2` + 11px/900 + ombre ; sélection `rgba(58,125,132,0.07)` + bordure `#3A7D84` ; crochet 26px anneau blanc 3px (mobile 22px) ; économies 3× `rgb(45,106,112)` ; 3 del `#888` 13,6px line-through SOUS le prix ; 0 occurrence « المرجعي » dans le DOM ; finition centrée/300/italique/ligne propre ; 6 caractéristiques exactes ; H1 verbatim ; sélection suit le clic (carte 3) ; mobile 9px/22px ; **overflow 0 @360** ; produit-1 contenu Wool absent + grille données 329/559/799 inchangée |
| Contrastes calculés (WCAG 2.1) | ✅ ruban blanc/bordeaux **10,7:1** (audit annonçait 11,4:1 — écart de méthode de calcul, largement AAA dans les deux cas) ; économies **5,95:1** AA ; bordure active **4,56:1** > seuil 3:1 (contre 2,7:1 constaté par l'audit) ; prix barré `#888` 3,42:1 = valeur mandatée (élément historique secondaire) |
| Captures | ✅ `scripts/shots_v332/` : offres 1280 (état initial + carte 3 sélectionnée), offres 360, héros info 1280/360, galerie ×2 |

---

## 4. Composants protégés — INTACTS

- **Structure globale / layout** : zéro retouche hors bloc v3.32 fin de feuille + 2 blocs conditionnels du gabarit — grille 3 colonnes, `.is-selected`, smooth scroll, formulaire, tunnel 2 pages, TYP, logo `Abaya Collection`, back-link `‹ رجوع` : ZÉRO diff (prouvé harnais).
- **produit-1** : données BIT-EXACTES (0 octet) ; sa charte offres suit la vedette **par conception** (uniformisation v3.6 : les landings secondaires sont des copies conformes — les sélecteurs `:not(.is-upsell)` de l'audit s'y appliquent exactement comme les couches v3.2/v3.3/v3.4).
- **Données commerciales** : grille 479/799/1099, توفير 20/159/338, refs 499/958/1437, 2 couleurs, 2 JPG, sélecteur multi-pièces v3.30/v3.31 : INCHANGÉS.
- **app.js / functions.php / mock-api.js / critical.css / offers.css** : 0 octet modifié.

## 5. Points de vigilance transmis

1. **Ruban = position, pas choix** : le ruban est posé sur `:nth-child(2)` (carte 2) — sémantiquement « le plus demandé » ; si un jour la 2 pièces cesse d'être le best-seller, c'est le CSS (pas les données) qu'il faudra changer.
2. **Ruban + crochet partagent le bord supérieur de la carte 2** : ~2px d'écart sur mobile 320 — l'anneau blanc du crochet assure le détachement (prescriptif de l'audit, validé sur capture 360).
3. **WebP définitifs** : les 2 JPG restent PROVISOIRES (signalement reconduit).
4. Le prix barré `#888` à 3,42:1 est la valeur explicite du mandat (élément décoratif/historique, doublé par le توفير en 5,95:1).
