# AUDIT — v3.28 « fix/main-lp-text-and-webp-injection » — Alignement MOT-À-MOT de la Landing Page Principale

> **Mandat 4P LP** : injection mot-à-mot de la matrice textuelle sur la LP principale UNIQUEMENT,
> remplacement des anciennes images restantes par les visuels Chenille (1.webp…6.webp),
> landing secondaire (Upsell / Thank You Page) 100% intacte, structure intangible
> (logo, back-link ‹ رجوع, grille 3 offres, formulaire), branche isolée, auto-audit,
> merge main + WP Pusher + PROJECT_MAP.

---

## 1. Périmètre exact (preuve git)

**7 fichiers modifiés + 15 uploads supprimés — AUCUN autre fichier touché :**

| Fichier | Nature de la modification |
|---|---|
| `inc-offers.php` | registre : headline, grille 799/188 + refs 379/658/987, palette (renommage + ordre du mandat), 3 visuels secondaires, 8 détails, 5 paragraphes fabrication |
| `template-parts/landing-offer.php` | résumé initial conditionnel vedette, ligne « الثمن المرجعي » conditionnelle vedette, boucle fabrication plate (sans titre d'étape) |
| `functions.php` | repli LCP hors-landing `hero-moka.jpg` → `uploads/1.webp` (+ commentaire) |
| `assets/js/app.js` | repli prix `3:799` + résumé initial conditionnel (`LANDING.offer_id`) |
| `assets/js/mock-api.js` | repli PRICES `3:799` |
| `assets/css/styles.css` | +1 règle NOUVELLE `.bundle-ref` en fin de feuille (section v3.28) — zéro règle existante modifiée/supprimée |
| `style.css` | Version 3.27 → **3.28** |
| 15 uploads (git rm) | `uploads/` : hero-moka.jpg, visual-bordeaux.jpg, visual-blue-sky.jpg, Robe_comfy_…12_.webp, Robe_comfy_…6_.webp, frame_021.webp, frame_029-2.webp, swatch-blue-marine.jpg ; `uploads/thumbs/` : les 7 équivalents |

**Zéro diff** sur : `header.php`, `footer.php`, `page.php`, `index.php`, `page-merci.php` (TYP),
`page-offer.php`, `front-page.php`, `inc-pages.php`, `template-parts/site-header.php`,
`template-parts/legal-header.php` (back-link ‹ رجوع), `template-parts/legal-footer.php`,
`template-parts/site-footer.php`, `assets/css/offers.css`, `assets/css/critical.css`,
`merci-review.js` — **logo, back-link, formulaire, tunnel COD, architecture 2 landings INTACTS**.

## 2. Matrice textuelle — conformité mot-à-mot (preuves test_mainlp_v328.py, 58/58 PASS)

| Clé du mandat | Valeur injectée | Preuve |
|---|---|---|
| عنوان المنتج | « عباية شتوية من توب شونيل الراقي – أنيقة وانسيابية » | A1 + B1 (h1 exact) |
| وصف المنتج | **CONSERVÉ tel quel** (consigne explicite) | A2 + B2 (bit-exact) |
| ثمن قطعة واحدة | 329 درهم + 379 درهم barré + توفير 50 درهم | B6 |
| اختيار اللون | 6 couleurs **dans l'ordre exact de l'énumération** : البلون كاسي، البني، الأخضر الغامق، الرمادي الغامق، الأسود، والبوردو | A5 + B3/B4 (pastilles + vignettes 5,3,6,4,1,2) |
| ملخص اختيارات المنتج | « اختيارك: اختاري اللون والمقاس » (vedette uniquement) | A16 + B8 + D1/D8 (JS initial) |
| عروض الأسعار | قطعة واحدة 329/توفير 50/المرجعي 379 • قطعتان 559/99/658 • 3 قطع **799/188/987** | A3 + B7 (+ carte par carte D5/D6) |
| قسم المميزات (الميزة الثانية) | « متوفرة في 6 ألوان كي تختاري لونك المفضل بكل حرية. » (déjà exacte — vérifiée) | A11 + B11 |
| تفاصيل المنتج | **Les 8 lignes injectées MOT-À-MOT** (injection brute, ponctuation du mandat reproduite, aucun préfixe) | A9 + B9 |
| مراحل التصنيع (NOUVELLE SECTION) | titre + **5 paragraphes MOT-À-MOT**, injection brute sans préfixe ni titre d'étape (numérotation 01-05 = habillage charte conservé) | A10 + B10 |

**Aucun préfixe, étiquette (« النص الأول : ») ni deux-points ajoutés** — les textes du mandat sont
les seuls contenus ajoutés/remplacés ; les libellés structurels pré-existants (اختيارك:, titres de
section charte) sont conservés à l'identique.

## 3. Cartographie des visuels WebP (inchangée + alignement secondaires)

| Couleur (terme du mandat) | Visuel | Usage |
|---|---|---|
| الأسود | `1.webp` | **LCP** (hero par défaut) + vignette + pastille |
| البوردو / البرغندي | `2.webp` | sélecteur + vignette + **section détails (specs)** |
| البني | `3.webp` | sélecteur + vignette |
| الرمادي الغامق | `4.webp` | sélecteur + vignette + **section inspection (معاينة الطلبية)** |
| **البلون كاسي** (terme exact du mandat, ex « البيج الكاسيه ») | `5.webp` | sélecteur + vignette + **section « لماذا هذه العباية »** |
| الأخضر الغامق | `6.webp` | sélecteur + vignette |

- **Sélecteur de couleurs** : clic pastille N → image principale bascule (préchargé, `is-changing`),
  vignette `is-current` synchronisée — prouvé Playwright ×6 à 1280 ET 360 (D2/D10).
- **Zéro layout shift** : boîte `.main-photo-wrap` à ratio fixe, mesurée AVANT/APRÈS chargement
  à 1280 et 360 — écarts < 1px (D3/D11).
- **Anciennes images** : les 15 fichiers orphelins supprimés du dépôt ; le HTML rendu ne contient
  **aucun** reliquat (hero-moka, visual-*, Robe_comfy, frame_*, swatch-*) — B12 ; uploads/ racine =
  favicon + 1..6.webp exactement (A23/A24) ; repli LCP `functions.php` aligné sur 1.webp (A18).

## 4. Landing secondaire (Upsell) — 100% intacte (preuve C1/C2/C3)

Rendu `produit-1` comparé **bit-à-bit** au worktree `main` v3.27 (même gabarit, harnais) :
**diff = EXACTEMENT 2 lignes de données-prix** (bundle-price 3 : 789→799 ; bundle-saving 3 :
198→188) — conséquence inévitable de la **grille commerciale commune** (même produit, le serveur
est la seule autorité prix : précédent v3.27 documenté, un prix différent par page serait un
mensonge affiché). Les textes v3.28 sont **conditionnels à la vedette**
(`abaya_is_front_offer()` — S21.3 rescopé : exactement 2 conditionnels) :
- PAS de ligne « الثمن المرجعي » sur produit-1 (C2) ;
- résumé initial produit-1 INCHANGÉ « اختاري اللون والمقاس - قطعة واحدة » (C2) ;
- PAS de section manufacturing (C3, clé absente du registre) ;
- palette 7 couleurs historique intacte (C4) ;
- `/merci/` (TYP) : `page-merci.php` à ZÉRO diff.

## 5. Arithmétique commerciale (chaque « توفير » reste VRAI)

379−329 = **50** ✓ ; 658−559 = **99** ✓ ; 987−799 = **188** ✓ — le « الثمن المرجعي » affiché
rend l'économie vérifiable sur la page elle-même (A4).

## 6. Validation (batterie + comparaison baseline)

- **test_mainlp_v328.py (NOUVEAU) : 58 PASS / 0 FAIL** — statique ×24 + rendu ×13 + produit-1 ×4 + Playwright ×17 (1280 + 360).
- **Batterie run_regressions_v328.sh : 28 suites / 1700 PASS / 52 FAIL** — les 52 échecs sont
  **identiques nom par nom** à la baseline `main` v3.27 exécutée avec les mêmes suites rescopées
  (`compare_fails_v328.sh` : comm vide sur les 17 suites concernées → **ZÉRO nouvel échec** ;
  dérive pré-existante documentée depuis v3.26 — suites TYP « 3 cartes », devise produit-2/3, etc.).
- Rescopage documenté `rescope_mainlp_v328.py` : **50 remplacements comptés** (phase 1 : 41 ;
  phase 2 : 9 pins Version/périmètre/specs + garde-fous S21.3/S33.12/S34.13/S36.3).
- `php -l` ×3 (inc-offers, functions, gabarit) + `node --check` ×2 (app.js, mock-api.js) : OK.
- Espacement v3.21 : 154/154 (jonctions fabrication|garanties = 85px préservées).
- Preuves visuelles : `download/v328_hero_1280.png`, `v328_offres_1280.png` (grille + الثمن المرجعي),
  `v328_fabrication_1280.png` (5 paragraphes 01-05), `v328_offres_360.png`, `v328_fabrication_360.png`,
  `v328_vedette_360_full.png`.

## 7. Décisions documentées du mandat

1. **« البلون كاسي »** : le terme exact du mandat remplace « البيج الكاسيه » (fidélité mot-à-mot ;
   le mandat l'écrit deux fois — cartographie + énumération).
2. **Ordre des couleurs** : l'énumération du mandat (« اختيار اللون ») est appliquée comme ordre
   d'affichage du sélecteur (البلون كاسي en 1er, البوردو en dernier). La cartographie des images
   reste inchangée (الأسود 1 … الأخضر الغامق 6) ; le LCP demeure 1.webp (الأسود).
3. **Prix 2/3 pièces** : 559 (inchangé) et **799** (au lieu de 789) — le mandat fixe 799 avec
   توفير 188 et مرجعي 987 (3×329) : arithmétiquement vrai.
4. **Fabrication sans titres d'étape** : le mandat fournit titre de section + 5 paragraphes SANS
   titres (« injection brute sans aucun préfixe ») — les titres v3.27 (اختيار التوب…) sont retirés,
   la numérotation 01-05 (habillage charte `.order-step-number`) est conservée.
5. **Grille des 3 offres** : structure/markup/CSS intacts ; seuls les textes du mandat sont appliqués
   (économies 188 + ligne الثمن المرجعي — conditionnels vedette).

## 8. Verdict

✅ Matrice textuelle injectée mot-à-mot sur la LP principale ; ✅ anciennes images remplacées
(6 visuels Chenille alignés, zéro résidu) ; ✅ sélecteur de couleurs fonctionnel sans layout shift
(1280 + 360) ; ✅ landing secondaire et TYP 100% intactes (hors 2 lignes de données-prix de la
grille commune, précédent documenté) ; ✅ composants protégés intacts ; ✅ zéro nouvel échec de
régression. **Prêt pour merge → main → WP Pusher.**
