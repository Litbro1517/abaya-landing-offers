# AUDIT-RESPONSIVE-v3.18 — Restauration du responsive mobile de la Thank You Page

**Mandat 4P LP — « Autodiagnostic et correction de la régression responsive mobile (Galaxy S8 / 360px) »**
État audité : v3.17 (main @ cb4fc43) · État livré : v3.18 · Date : 2026-10-20

---

## 1. Symptôme (captures utilisateur, Galaxy S8 ≈ 360px)

Trois captures du site en production (`/merci/`) montrent :

1. `Capture … 010728.png` — la carte « تفاصيل الطلب » excède le bord gauche de
   l'écran (la marge crème gauche du bloc a disparu, première colonne du
   tableau partiellement hors écran).
2. `Capture … 010741.png` — la ligne « العنوان : marrakech, marrakech safi
   maroc, … » est tronquée au bord gauche (le mot « marrakech » amputé de son
   début), le texte déborde hors de la fenêtre.
3. `Capture … 010802.png` — le sous-titre « تشكيلة أخرى قد تعجبكِ أيضاً »
   tronqué au bord gauche ; le rendu général paraît « zoomé-dézoomé »,
   typographie écrasée = impression d'affichage « type bureau » sur mobile.

## 2. Protocole d'autodiagnostic (mesure AVANT correction)

Script : `scripts/landing-offers-tests/diag_responsive_v318.py` (Playwright,
harnais convention v3.17 : `/tmp/merci-test` rebuild + LP wrappée), mesure de
`document.scrollWidth` vs `innerWidth` + énumération des éléments excédant la
viewport, aux largeurs 320 / 360 / 375 / 390 (TYP) et 360 (LP vedette).

### Résultats AVANT (v3.17)

| Mesure                      | @360 | @375 | @390 |
|-----------------------------|------|------|------|
| `scrollWidth` document      | **454** | **454** | **454** |
| Débordement horizontal      | **+94 px** | +79 px | +64 px |
| `main.merci-page`           | 454 px | 454 px | 454 px |
| `.merci-card`               | 434 px | 434 px | 434 px |
| `.merci-order`              | **402 px** | 402 px | 402 px |
| Table (min-content figé)    | **314 px** | 314 px | 314 px |
| LP vedette `scrollWidth`    | 360 (PROPRE, overflow 0) | — | — |

**Constat central : la largeur du document est CONSTANTE (454px) quelle que
soit la viewport (360/375/390).** Le contenu impose sa largeur minimale au
document : les navigateurs mobiles pans/écrasent le rendu → impression
« bureau ». La régression est **confinée à la TYP /merci** (LP saine).

## 3. Source exacte (décomposition arithmétique prouvée)

```
454 px (main.merci-page)
 = 434 (merci-card) + 2 × 10  (padding .merci-page ≤520px)
 = 402 (min-content .merci-order) + 2 × 16 (padding .merci-sec ≤520px)
 = 314 (min-content table) + 78 (vignette flex 0 0 78px) + 10 (gap)
```

- **Le tableau `.merci-order table` ne descend jamais sous son min-content
  (314px)** : un `width: 100%` ne peut pas réduire un tableau sous la somme de
  ses colonnes incompressibles — ici les 4 en-têtes du thead en
  `white-space: nowrap` (règle socle `styles inline TYP :106`) + paddings.
- **La chaîne de propagation** : `body.legal-page { display:flex;
  flex-direction:column }` (styles.css:622) laisse la min-content de la
  descendance dilater `main.merci-page` (flex item) au-delà de la viewport ;
  le `overflow: hidden` de `.merci-card` ne peut rien y faire (la croissance
  a lieu chez les ancêtres).
- **Responsabilité historique : défaut PRÉEXISTANT**, non introduit par
  v3.17. Il était déjà mesuré (454/375) et documenté dans
  `docs/AUDIT-LOGO-v3.11.md` comme « débordement horizontal mobile 454/375 —
  isolation binaire `.merci-order table` × `body.legal-page` — hors périmètre,
  mandat dédié recommandé ». Le mandat v3.17 (garanties/upsell/émoji) est
  **innocent** ; il ne faisait pas l'objet du présent verrou.
- Le resserrement v3.6 (vignette 104→78px ≤520px) avait réduit le
  débordement sans l'annuler : 314 + 78 + 10 = 402 > toute viewport mobile.

## 4. Correctif livré (v3.18) — chirurgical, scopé ≤520px

### 4.1 TYP — style inline `page-merci.php` (bloc `v3.18` en fin de `<style>`)

1. **`table-layout: fixed`** sur `.merci-order table` ≤520px : la largeur du
   tableau devient **strictement** celle du conteneur (fin définitive du
   min-content 314px — garanti aussi à 320px).
2. **Vignette produit sous le tableau** (`.merci-order { flex-direction:
   column }`, `.merci-product-img { width: 120px }`) : le tableau passe de
   ~220px à ~308px pleins à 360px (au lieu de combattre pour chaque pixel).
3. **En-têtes wrappables** (`white-space: normal`) + `overflow-wrap:
   break-word` sur th/td : zéro débordement possible quel que soit le contenu.
4. **Distribution fixe 38/26/10/26%** (produit / couleur-mqas / quantité /
   prix) — structure 4 colonnes et contenus INCHANGÉS (convention v3.9).
5. **Prix** : le `white-space: nowrap` socle de `.merci-price` est neutralisé
   ≤520px ; seul le token montant+درهم (le `<bdi>`, soudé par espace
   insécable) reste nowrap, la ligne `<small>` du prix unitaire se replie
   (sinon « 249.50 درهم / القطعة » ≈ 119px insécables) et
   `overflow-wrap: anywhere` sur la cellule sert de soupape anti-débordement.

### 4.2 Garde-fou transverse — `assets/css/styles.css` (section v3.18 en fin de feuille)

```css
@media (max-width: 540px) {
  html { overflow-x: hidden; }                       /* clip viewport, sticky-safe */
  body { max-width: 100%; box-sizing: border-box; }
}
```

- `html { overflow-x: hidden }` est propagé au viewport par l'UA et **ne crée
  pas de conteneur de défilement interne** → `position: sticky` du
  `.site-header` LP (styles.css:265) reste fonctionnel (**re-mesuré : header
  collé à top 0 à scrollY 600** à 360px). Un `overflow-x: hidden` sur `body`
  aurait cassé le sticky — choix technique documenté ici.
- Inactif >540px : desktop bit-à-bit inchangé (re-mesuré : `overflow visible`
  à 1280px).

## 5. Résultats APRÈS (v3.18)

| Mesure                        | @320 | @360 | @375 | @390 |
|-------------------------------|------|------|------|------|
| `scrollWidth` document        | **320** | **360** | **375** | **390** |
| Débordement horizontal        | **0** | **0** | **0** | **0** |
| `.merci-order` / table        | 268/268 | 308/308 | 323/323 | 338/338 |

- Tableau = 100% du conteneur SANS débordement interne
  (`scrollWidth == clientWidth == 308` à 360px).
- Adresse et sous-titre entièrement dans l'écran (fin du clipping gauche).
- Desktop 1280px bit-exact : flex row (image à côté du tableau),
  `table-layout: auto`, aucune largeur de colonne fixée, garde-fou inactif.

## 6. Non-interférence (verrous re-mesurés, tous INTACTS)

| Verrou                                        | Preuve (test_mobile_v318.py / suites)          |
|-----------------------------------------------|------------------------------------------------|
| Suppression émoji 🌱 modale d'avis (v3.17)     | modale sans émoji, scan ×0 — S32.8/32.9        |
| Garanties 3 lignes × 2 colonnes (v3.17)       | LP 390 : 1 piste, icône à droite — 26/26 v3.17 |
| `.merci-logo` translateX(25px) (v3.13)        | `matrix(1, 0, 0, 1, 25, 0)` re-mesuré          |
| Étoiles Étape 2 non interactives (v3.16)      | `pointer-events: none` offers.css intact       |
| Carrousel upsell 42% + peek + snap start      | peek 104px, rail scrollable à 360px            |
| Devises v3.10 / footer v3.8-v3.9 / header     | suites dédiées au vert (53/22/35 checks)       |

## 7. Validation complète avant fusion

- Statique : `validate_offers.py` **285/285** (269 historiques rescopés
  Version 3.18 + 16 nouvelles assertions S33).
- Comportemental : `test_mobile_v318.py` **26/26** (créé).
- Batterie de régressions : **19 suites, 936 checks verts, 0 FAIL**
  (mobile-v317 37, modales-v316 59, modales-v315 93, redesign 29, avis-v37 64,
  logo-v311 83, logo-v312 59, logo-v313 59, devise-v310 53, v38 22, v39 35,
  canva 41, lp-variants 75, badge 16, popup 14, shot-v36 33, REST orders 30,
  REST reviews 29, tunnel 79).
- `php -l page-merci.php` : OK.
