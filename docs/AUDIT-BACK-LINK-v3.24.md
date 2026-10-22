# AUDIT — LIEN DE RETOUR UNIFIÉ « ‹ رجوع » (v3.24)

**Mandat** : « protocole mandat 4P LP — fix/unified-back-link »
**Branche** : `fix/unified-back-link` (isolée depuis main @ 1e3763b = v3.23)
**Périmètre modifié** : `template-parts/legal-header.php` + `template-parts/site-header.php` + `assets/css/offers.css` + `assets/css/styles.css` + `style.css` (Version 3.24)
**Hors périmètre (INTACT)** : logo (taille/hauteur/dimensions), tunnel COD (formulaire, validation, champs), destinations de tous les liens, TYP, landings, footers, JS.

---

## 1. Constat AVANT (preuves mesurées)

| Écran | Constat | Preuve |
|---|---|---|
| Upsell mobile (legal-header + ctx upsell, 360px) | Le lien `.upsell-back-btn` (long libellé « العودة إلى صفحة التأكيد » + SVG) est **squeezé par le flex** du `.legal-header` (item flex-shrink par défaut) : le texte s'empile sur **3 lignes**, la boîte étroite+haute avec `border-radius: 999px` se déforme en **cercle volumineux et médiocre** | `download/v324_upsell_header_360_avant_header.png` (reproduction harnais : ovale bleu/grand pill 3 lignes) |
| Réglementaires (360px) | `.legal-home-link` = texte nu « العودة إلى صفحة الطلب » **sans flèche indicative**, wrapé sur 2 lignes, déséquilibré selon les écrans (font-size 10px mobile) | `download/v324_legal_header_360_avant_header.png` |
| Desktop | Libellés longs et déséquilibrés entre Upsell (SVG + 4 mots) et réglementaires (texte nu, aucune flèche) | captures `*_1280_avant_*` |

## 2. Correctifs appliqués

### 2.1 Harmonisation du libellé HTML (mandat PARTIE 3.1 — à la lettre)
Les **3 occurrences** du lien de retour portent désormais la formule universelle courte :

```html
<a class="upsell-back-btn" href="{merci_url du duo order+key}">
  <span class="back-arrow" aria-hidden="true">‹</span> <span class="back-text">رجوع</span>
</a>
```

| Occurrence | Fichier | Destination (CONSERVÉE) |
|---|---|---|
| Landing secondaire en mode upsell | `legal-header.php` (branche `$abaya_upsell_ctx`) | **Thank You Page personnalisée** (`$abaya_upsell_ctx['merci_url']` — inchangée) |
| Racine en mode upsell (barre `.upsell-back-bar`) | `site-header.php` | **Thank You Page personnalisée** (inchangée) |
| Pages réglementaires (CGV, mentions, livraison, etc.) | `legal-header.php` (branche hors tunnel) | **Accueil** (`href="/"` — inchangée) |

- La flèche SVG héritée est remplacée par le glyphe `‹` (U+2039) porté par `.back-arrow` : en contexte **RTL** (`<html lang="ar" dir="rtl">`) le caractère possède la propriété bidi **Bidi_Mirrored** → il se rend **orienté vers la droite**, à l'**inline-start** de la lecture arabe (mesuré : `arrow.x > text.x` sur tous les écrans) — même direction que la flèche SVG historique validée (chevron ›).
- L'ancien libellé « رجوع إلى طلبي » reste absent (verrou S13.3 conservé).

### 2.2 Style CSS du bouton (mandat PARTIE 3.2 — pilule élégante, liberté guidée)
Recette **identique dans les 2 feuilles** (offers.css `.upsell-back-btn` + styles.css `.legal-home-link`, commentée v3.24) :

```css
display: inline-flex;      /* impératif mandat */
align-items: center;       /* impératif mandat */
gap: 4px;                  /* impératif mandat */
white-space: nowrap;       /* impératif mandat — empilement IMPOSSIBLE */
flex: 0 0 auto;            /* anti-squeeze : largeur naturelle préservée */
border-radius: 50px;       /* pilule (le 999px hérité est supprimé) */
padding: 6px 14px;         /* spec mandat */
background: #fff; border: 1px solid #e5ded6;
color: #3A7D84;            /* turquoise tunnel (charte conservée) */
font-size: 12.5px; font-weight: 700; line-height: 1;
```

- `.upsell-back-btn svg { ... }` **retirée** (plus de SVG) ; overrides mobile (`font-size: 11.5px` offers.css ≤560px, `font-size: 10px` styles.css ≤768px) **supprimés** : la formule est **universelle desktop/mobile** — plus aucun déséquilibre d'écran.
- Hover aligné sur la charte existante (`border-color #3A7D84` + fond `#FAF7F2`), soulignement historique du lien réglementaire remplacé par la pilule.
- **Cause racine du cercle éliminée définitivement** : `white-space: nowrap` + `flex: 0 0 auto` rendent structurellement impossible l'empilement multiligne et l'écrasement qui produisaient l'ovale (`border-radius` élevé sur boîte étroite/haute).

### 2.3 Alignement avec le logo (mandat PARTIE 2, objectif 3)
- `.legal-header` = flex `align-items: center` : la pilule et le logo `.header-logo-svg` partagent la même ligne, **centres verticaux confondus** (mesuré : |ΔcentreY| = **0.0px** à 360/390/1280px).
- Logo **jamais retouché** : `.header-logo-svg { height: 40px; max-width: min(100%, 240px) }` inchangé (mesuré : h=40px, w=240px), `span.wordmark`/`a.footer-wordmark` inchangés selon les contextes (non-cliquable tunnel / cliquable hors tunnel).
- Racine (site-header) : la barre `.upsell-back-bar` reste centrée au-dessus du logo — rendu par défaut SANS duo order+key **inchangé** (aucune barre rendue, vérifié).

## 3. Autocontrôle mesuré (suite `test_back_link_v324.py` — 86/86 PASS)

| Contrôle | Résultat |
|---|---|
| Libellé « ‹ رجوع » net (back-arrow ‹ + back-text رجوع) sur UPSELL 360/390/1280 et LÉGAL 360/1280 | PASS ×5 écrans |
| Pilule mesurée : radius **50px** (≠ 50%), fond blanc, bordure 1px #e5ded6, padding 6/14, gap 4px, nowrap, recette impérative | PASS ×5 |
| **AUCUNE déformation circulaire** : h = 29px (< 40), w = 69px > h, `scrollWidth == clientWidth` (une seule ligne) | PASS ×5 |
| Alignement vertical logo ↔ bouton : **|ΔcentreY| = 0.0px** (tous écrans) | PASS ×5 |
| Flèche à l'inline-start RTL (arrow.x > text.x — miroir bidi ‹ → ›) | PASS ×5 |
| Destinations : upsell → `https://…/merci/?order=…&key=…` (TYP personnalisée) ; réglementaires → `/` (Accueil) | PASS ×5 |
| Logo intact (h=40px) + même ligne que le bouton | PASS ×5 |
| Longs libellés historiques ABSENTS des deux templates | PASS ×5 |
| Rendu par défaut racine sans contexte : aucune barre (inchangé) | PASS |
| Statique : markup ×3 occurrences, recette CSS ×2 feuilles, zéro `999px`/`50%` résiduel, hover, Version 3.24 | PASS ×16 |

## 4. Non-régression (batterie `run_regressions_v324.sh` — **1499 PASS / 0 FAIL**, 28 suites)

- Suites existantes re-validées : single-upsell-v323 (40), espacement-v321 (92), legal-v320 (81), mobile v317/318/319 (96), modales v315/316 (108), redesign TYP (29), avis (64), logo ×3 (117), devise (29), v38/v39 (57), canva (41), lp-variants (73), badge (16), popup (14), shots v35/v36 (77), sync-légal (12), REST commandes (42) + avis (29), tunnel post-achat (66), statique validate_offers (330).
- **Rescopage documenté** (`scripts/rescope_v324.py` — 32 remplacements comptés, idempotent) : 13 pins Version 3.23→3.23|3.24 (validate_offers ×12 + S21.1 + logo v312/v313 + modales v315/v316), libellés S13.3/S21.9/T5.2e portés sur le markup unifié (les anciennes épingles passaient par le long libellé ou son commentaire).
- `php -l` OK sur les 2 templates ; zéro modification HTML/PHP/JS du tunnel, du formulaire, de la TYP et des landings.

## 5. Preuves visuelles (dossier `download/`)

| Fichier | Contenu |
|---|---|
| `v324_upsell_header_360_avant_header.png` | AVANT — cercle volumineux (pilule squeezée 3 lignes) |
| `v324_legal_header_360_avant_header.png` | AVANT — lien réglementaire sans flèche, wrapé 2 lignes |
| `v324_upsell_header_360_apres_header.png` (+ zoom ×3) | APRÈS — pilule « ‹ رجوع » une ligne, alignée au logo |
| `v324_legal_header_360_apres_header.png` (+ zoom ×3) | APRÈS — même pilule avec flèche indicative |
| `v324_upsell_header_1280_apres_header.png` / `v324_legal_header_1280_apres_header.png` | APRÈS desktop 1280 — pilule inline-end, logo inline-start, alignement parfait |
| `v324_*_{360,1280}_{avant,apres}_full.png` | Pages complètes (contexte) |

## 6. Conclusion

- **« ‹ رجوع » s'affiche net** sur mobile (360/390) et desktop (1280), sur l'Upsell ET les pages réglementaires — une seule ligne, pilule compacte 69×29px.
- **Le cercle volumineux de l'Upsell mobile a disparu** (cause racine éliminée : nowrap + flex:0 0 auto ; radius 50px ; preuves AVANT/APRÈS).
- **Redirections respectées** : réglementaires → Accueil (`/`), Upsell → Thank You Page personnalisée (duo order+key).
- **Logo strictement inchangé** (40px, dimensions, calligraphie, cliquabilité par contexte).
- Fusion sur `main` autorisée : rendu 100 % propre sur les deux types d'écran, déploiement WP Pusher déclenché par le push.
