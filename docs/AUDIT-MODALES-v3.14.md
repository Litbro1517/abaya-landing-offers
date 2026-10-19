# AUDIT — Harmonisation design des modales d'avis (Thank You Pages) — v3.14

**Mandat 4P LP** : « PHASE 1 : Audit et localisation des composants modales — inspecter le CSS
des fenêtres modales d'évaluation/avis sur les Thank You Pages, identifier les règles appliquant
la couleur jaune fluo/jaunâtre au bandeau supérieur et le style des icônes/étoiles de notation ».
**PHASE 2** : bandeau → vert turquoise CTA `#3A7D84` ; étoiles → doré premium `#c0a758`
(identique étoiles du titre LP) ; retrait du contour/stroke déformant ; non-interférence stricte
(règles verrouillées conservées, dont `transform: translateX(25px)` de `.merci-logo` v3.13).

---

## PHASE 1 — Audit et localisation

### Chaîne de rendu des modales

- Les « fenêtres modales d'évaluation/avis » des Thank You Pages = le **pop-up d'évaluation
  1-clic** `.abaya-rating-overlay > .abaya-rating-modal` (les 2 états des captures client :
  état initial « étoile centrale dans le cercle + rangée 5 étoiles » et état persistant
  « message + zone de texte + bouton إرسال » — même modale, même charte).
- **Markup natif** : `page-merci.php:558-578` (rendu sur les 4 TYP — gabarit unique) ;
  **injection mock** : `merci-review.js:315-336` (même markup, même flux — prévisualisation
  locale uniquement). Les sélecteurs `.abaya-rating-*` n'existent sur AUCUNE autre page :
  **le correctif est de facto scopé Thank You Pages**.
- **CSS** : `assets/css/offers.css` section « 2) Pop-up d'évaluation 1-clic (/merci/) »
  (L55-196). Aucune règle `.abaya-rating-*` dans styles.css / critical.css / bloc inline
  abaya-merci — source unique.

### Règles fautives identifiées (jaune vif / contour déformant)

| # | Règle (offers.css avant v3.14) | Constat | Prescription mandat |
|---|---|---|---|
| 1 | `.abaya-rating-modal { border-top: 4px solid #d4af37; }` | **Bandeau supérieur jaune/doré vif** (screenshots 210501 & 210648) | `#3A7D84` (vert turquoise CTA) |
| 2 | `.abaya-rating-emoji { color: #d4af37; }` | Étoile centrale du cercle en jaune vif (glyph texte ★ → `color`) | `color: #c0a758 !important` |
| 3 | `.abaya-rating-star.is-hot svg, .abaya-rating-star.is-picked svg { fill: #d4af37; stroke: #b38e1f; }` | Rangée 5 étoiles actives : doré vif + **stroke foncé #b38e1f = contour déformant** qui détériore l'arrondi naturel | `fill: #c0a758 !important` + retrait du contour contrastant |
| 4 | `.abaya-rating-star:focus-visible { outline: 2px solid #d4af37; }` | Anneau de focus clavier en doré résiduel | Harmonisation `#c0a758` |

### Référence charte (étoiles du titre de la LP principale)

La règle v3.4 (styles.css:1879-1883, scopée vedette) rend les étoiles d'avis + badge sous H1 en
**doré premium** avec un stroke ALIGNÉ sur le fill :

```css
.product-landing:not(.is-upsell) .review-star {
  fill: #c0a758;
  stroke: #c0a758;   /* stroke = fill → aucun contour contrastant, arrondi naturel */
}
```

C'est ce pattern exact (stroke = fill = `#c0a758`) qui est répliqué sur les étoiles de la
modale : le contour « épais déformant » disparaît **visuellement** (stroke invisible car
confondu avec le remplissage) sans altérer la géométrie du tracé SVG.

### Éléments DÉJÀ conformes (continuité — intouchés)

- `.abaya-rating-send { background: #3A7D84 }` (bouton إرسال turquoise — capture 210648) ;
- `.abaya-rating-note:focus { border-color: #3A7D84 }` ;
- état VIDE des étoiles (`fill: #e7e1d6; stroke: #d8d0c2`) — gris neutre, hors périmètre doré.

---

## PHASE 2 — Implémentation (offers.css, source unique)

1. **Bandeau supérieur** : `border-top: 4px solid #d4af37` → **`border-top: 4px solid #3A7D84`**
   (remplacement de la couleur à la source — la largeur 4px et le rayon sont conservés).
2. **Étoile centrale du cercle** : `color: #d4af37` → **`color: #c0a758 !important`**
   (prescription littérale du mandat).
3. **Rangée 5 étoiles (états survolés/enregistrés)** :
   `fill: #d4af37; stroke: #b38e1f` → **`fill: #c0a758 !important; stroke: #c0a758 !important`**
   (doré premium prescrit + stroke aligné = pattern LP v3.4 → fin du contour déformant,
   arrondi naturel préservé).
4. **Focus clavier** : `outline: 2px solid #d4af37` → **`#c0a758`** (harmonisation, doré
   résiduel banni de la modale).
5. **Commentaire de section v3.14** documentant la charte + mise à jour du commentaire du
   badge (référence exacte : vedette #c0a758 v3.4 / socle-upsell #d4af37).

Aucune autre feuille modifiée : styles.css, critical.css, page-merci.php, merci-review.js
**bit-à-bit inchangés** par ce mandat.

## Non-interférence (règle stricte du mandat)

- **`.merci-logo` v3.13** : le bloc validé verbatim (`width 240px / height auto / max-width
  82% / object-fit contain / display block / margin 0 auto 4px / transform translateX(25px)
  !important`) est INTACT dans page-merci.php (re-vérifié par S28 + S29 + mesure in-browser
  `matrix(1, 0, 0, 1, 25, 0)` dans test_modales_v314.py).
- **Logo global v3.11** (centrage + non-cliquable), **garanties v3.9**, **footer v3.8-v3.9**,
  **devise v3.10**, **charte avis v3.7**, **prix** : aucun sélecteur touché.
- **LP** : le socle `.review-star` (styles.css:544, `#d4af37` global pour l'upsell v2.9) et la
  règle v3.4 (styles.css:1879) restent bit-à-bit identiques — le pop-up n'existe pas sur les
  landings, zéro régression possible.

## Validation

- Statique : bloc S29 de validate_offers.py (invariants v3.14 : bandeau #3A7D84, doré
  premium !important, zéro #d4af37/#b38e1f en déclaration, stroke=fill, continuité déjà-
  conformes #3A7D84, socle LP intact, .merci-logo v3.13 intact, Version 3.14, audit +
  PROJECT_MAP).
- Visuelle : `test_modales_v314.py` — 4 TYP (accueil + produit-1/2/3) × 2 viewports
  (1280 / 390) : `border-top-color` calculé = rgb(58, 125, 132), étoile centrale
  rgb(192, 167, 88), étoile active fill ET stroke rgb(192, 167, 88) (contour aligné),
  étoile vide inchangée, bouton إرسال inchangé, non-interférence `.merci-logo` (ox = 25) ;
  captures auto-audit download/ (états initial + post-évaluation).
- Régressions : 13 suites (validate_offers rescopée, logo v311/v312/v313, canva, v38/v39/v310,
  avis v37, lp_variants, shot_v36, badge, REST ×2, tunnel) + popup baseline 10/14 inchangée.
