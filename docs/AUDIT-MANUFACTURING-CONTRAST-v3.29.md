# AUDIT MANUFACTURING & CONTRAST — LP PRINCIPALE v3.29

> Mandat 4P LP « **fix/main-lp-manufacturing-and-contrast** » — rapport d'autocontrôle post-exécution.
> Base : `origin/main` @ `14aab36` (v3.28), arbre propre. Branche de travail : `fix/main-lp-manufacturing-and-contrast`.
> Méthode : harnais de rendu PHP déterministe (v3.28 vs v3.29) + contrôles géométriques Playwright (1280/360px)
> + calcul des contrastes WCAG 2.1 sur les couleurs réellement composées + lint.
> Audit d'inspiration : `AUDIT-LP-GLOBAL-v3.29.md` (agent designer, déposé hors du dépôt).

---

## 0. Réponse directe aux quatre demandes du mandat

| Demande | Avant (v3.28) | Après (v3.29) | Verdict |
|---|---|---|---|
| 4 blocs d'étapes avec titres en gras | 5 `<p>` plats sans `<h3>` | 4 `<li>` : pastille 01→04 + `<h3>` (gras 800) + `<p>` | ✓ fait |
| 5ᵉ case supprimée → bandeau d'accroche `.inspection-banner` | 5ᵉ étape « هاد الشي كلو… » dans la liste | clé `note` rendue en `.inspection-banner` sous les 4 cartes | ✓ fait |
| Numérotation conforme (> 4,5:1) sections fabrication ET commande | `opacity:.3` → **1,46:1** (échec) | pastille `--olive-dark` sur émeraude 10 % → **5,25:1** (AA texte courant) | ✓ fait |
| Grille fabrication 2×2 desktop (≥701px) | 3 colonnes → 3+1 (carte orpheline) | `repeat(2, minmax(0,1fr))` → 2×2 mesuré, zéro orpheline | ✓ fait |

**Périmètre Git exact : 4 fichiers** — `inc-offers.php`, `template-parts/landing-offer.php`,
`assets/css/styles.css`, `style.css` (version 3.29). **Aucun autre fichier touché.**

---

## 1. Audit préalable (état v3.28 confirmé en dépôt)

Les constats de l'audit designer ont été reproduits ligne à ligne sur `main @ 14aab36` :

- `inc-offers.php:188-197` — 5 chaînes plates, sans structure (aucun titre d'étape).
- `template-parts/landing-offer.php:306-323` — section `#fabrication` conditionnelle
  (`!empty($T['manufacturing'])`), boucle plate `<p>` sans `<h3>` ; numérotation 01→05 générée.
- `assets/css/styles.css:1466-1475` — `.product-landing .order-step-number` : `position:absolute;
  top:8px; left:12px; opacity:.3` → contraste composé **1,46:1** (reproduit par calcul, cf. §4).
- `assets/css/styles.css:1036` — `.order-steps` 3 colonnes desktop ; `:1107` — 1 colonne ≤700px
  (seul palier) : 4 blocs y auraient rendu **3+1** (carte orpheline — même défaut corrigé en v3.26 TYP).
- Le composant `.inspection-banner` existe (`landing-offer.php:291-294`, styles `styles.css:1661+`),
  scopé `.product-landing:not(.is-upsell)` : directement réutilisable dans `#fabrication` sans CSS nouveau.
- La section commande (`كيف يمكنني تقديم الطلب؟`) a ses `<h3>` et partage `.order-steps` /
  `.order-step-number` : tout correctif du composant corrige les deux sections d'une seule passe (§2.1 audit).

## 2. Choix d'implémentation (justifications techniques)

### 2.1 Données — couples `[titre, texte]` + clé `note` (vs tableaux parallèles)

Le mandat fixe les 4 titres ; l'audit designer (§1.2) démontre la correspondance un-pour-un
titre ↔ paragraphe existant. La structure retenue est celle **déjà en production pour la section
commande** (`guide_steps` = couples `[titre, texte]`) : même forme de données, même gabarit de
boucle, même charte `<h3>` — zéro schéma nouveau, lisibilité immédiate pour les futurs mandats.
Le 5ᵉ paragraphe (clôture, pas une étape) est extrait en clé `note` avec rendu conditionnel
(`!empty($T['manufacturing']['note'])`) : une landing future sans note resterait valide.
**Les 4 textes restent BIT-EXACTS v3.28** (prouvé par extraction comparée, cf. §3 D.5).

### 2.2 Gabarit — `<h3>` + bandeau réutilisé (zéro CSS nouveau pour les titres)

- `<h3>` dans la boucle : la charte `.order-steps h3` existe déjà (styles.css:1059 — 16px/800,
  + override produit-landing `--ink`) : le mandat « titres en gras » est satisfait **sans une ligne
  de CSS nouvelle**, avec l'exact rendu de la section commande.
- Bandeau : réutilisation **littérale** du markup `.inspection-banner` + `.inspection-banner-icon`
  + `.inspection-note` (même SVG check-circle) — les deux bandeaux de la page (inspection et
  fabrication) partagent la même famille visuelle (liseré émeraude 4px, fond crème, pastille
  émeraude 10 %, radius 16px), comme recommandé (audit §1.3 : « aucun CSS nouveau nécessaire »).
- Le scoping `.product-landing:not(.is-upsell)` du bandeau est compatible avec `#fabrication`
  (la classe `is-upsell` n'est plus posée depuis v3.6 — règle conservée par sécurité).

### 2.3 Numérotation — pastille pleine (édition en place) plutôt qu'override en fin de feuille

Le mandat dit « **remplacer** le filigrane ». Éditer la règle scoppée en place (styles.css:1466)
évite de laisser en fin de feuille une règle contradictoire morte (`opacity:.3` surchargée) —
la feuille reste véridique. Traitement = recommandation du designer (§1.4) :

```css
.product-landing .order-step-number {
  position: static; display: inline-grid; place-items: center;
  min-width: 34px; height: 26px; margin: 0 0 10px; padding: 0 8px;
  border-radius: 7px; background: rgb(58 125 132 / 10%);
  color: var(--olive-dark); font-size: 15px; font-weight: 900;
  letter-spacing: .06em; opacity: 1;
}
```

Justifications :

1. **Contraste AA texte courant** : `#2d6a70` sur pastille composée `#e8eeec` (émeraude 10 %
   sur carte `#fbfbf8`) = **5,25:1** — le seuil 4,5:1 est dépassé même en texte courant,
   pas seulement en grand-texte (3:1). Sur l'hypothèse carte crème 60 % (`#e5e8e3`) : 4,98:1 —
   conforme dans les deux cas (marge de sécurité vs une future retouche de fond de carte).
2. **RTL corrigé d'office** : `left:12px` (position physique) rejetait le numéro à la fin du
   parcours visuel arabe ; en flux statique la pastille précède naturellement le titre à
   l'inline-start (droite). Le `direction:ltr` de la règle socle (styles.css:1051) est conservé :
   les chiffres « 01…04 » gardent leur ordre occidental dans la pastille.
3. **Composant partagé — les deux sections corrigées d'une passe** : la règle scoppée
   `.product-landing .order-step-number` couvre fabrication ET commande (mandat PARTIE 2/3 :
   « pour les sections de fabrication et de commande » ; audit §2.1 : « le correctif corrige
   les deux d'un seul coup »). Conséquence documentée : la LP secondaire (produit-1), qui rend
   la section commande avec le même composant, voit sa numérotation passer au même traitement
   AA — **sans aucune modification de son markup ni de ses données** (rendu prouvé bit-exact,
   cf. §3 C.1). C'est le même précédent que la grille commune de données-prix (v3.27/v3.28) :
   composant commun mandaté, gabarit secondaire intouché.
4. **Famille visuelle** : pastille émeraude 10 % = teinte exacte des pastilles
   `.inspection-banner-icon` — cohérence système, pas une couleur inventée.

### 2.4 Grille — 2×2 scopé `#fabrication` (≥701px), règle nouvelle en fin de feuille

```css
@media (min-width: 701px) {
  #fabrication .order-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
```

- **2×2 et non 4-colonnes** : à 1160px de contenu, 4 colonnes ≈ 250px par carte — titre arabe
  + paragraphe de 3 lignes = mesure de lecture trop étroite (audit §1.5) ; 2×2 ≈ 572px mesurés.
- **Scopage par `#fabrication`** : la section commande conserve ses 3 colonnes (processus
  séquentiel horizontal) — la disposition différencie les deux sections numérotées consécutives
  (audit §2.2 : « la disposition raconte la différence »).
- **Palier mobile intact** : ≤700px reste 1 colonne (règle historique styles.css:1107 non touchée) ;
  le `min-width:701px` ne chevauche pas le `max-width:700px` existant (aucun conflit de cascade).
- Règle ajoutée **en fin de feuille** dans une section commentée v3.29 (convention du dépôt,
  cf. fin v3.28) — aucune règle existante modifiée pour la grille.

### 2.5 Hors périmètre (assumé)

Les pistes 5-6 de l'audit designer (compensation `padding-bottom` de la barre CTA fixe ;
renommer le CTA de la barre `footer.php`) sont explicitement dissociables (audit §4 : « elles
peuvent être dissociées si le périmètre doit rester strictement limité à la fabrication »).
Le mandat PARTIE 3 liste 4 axes seulement : elles ne sont **pas** implémentées — `footer.php`
est de surcroît un fichier partagé dont l'intangibilité de fait protège le rendu validé des
autres pages. À soumettre en mandat dédié si retenu.

## 3. Preuves (validation exécutée)

### 3.A Lint
`php -l` × 2 : `inc-offers.php`, `template-parts/landing-offer.php` — **0 erreur**.
(`styles.css`/`style.css` : texte pur, aucun changement structurel ; `critical.css` insensible
— aucune règle `order-step` — pas de synchronisation anti-flash nécessaire.)

### 3.B/C Harnais de rendu déterministe v3.28 vs v3.29 (`scripts/harness_render_v329.php`)

Rendu complet du gabarit SANS WordPress (stubs déterministes, base URI constante) avec le
registre RÉEL de chaque version (v3.28 = `git show main:…`) :

- **B.1 — diff vedette 100 % confiné à `#fabrication`** : le diff ligne-à-ligne des deux rendus
  complets (24 885 octets) tombe entièrement dans la section fabrication :
  ajout des 4 `<h3>`, suppression du `<li>` 05, ajout du bloc `.inspection-banner`.
  Aucune autre ligne du rendu (hero, offres, couleurs, specs, inspection, garanties, commande,
  avis, FAQ, contact) n'a changé.
- **C.1 — produit-1 BIT-EXACT** : rendu v3.28 vs v3.29 = **0 octet de différence** (22 631 octets).
  La landing secondaire ne contient pas la clé `manufacturing` (rendu conditionnel intact) —
  son markup, ses données et son rendu sont STRICTEMENT inchangés.
- Suite Python `scripts/validate_manufacturing_v329.py` : **39/39 PASS** (matrice, structure,
  CSS, WCAG, version, périmètre git).

### 3.D Matrice textuelle (extraite du rendu v3.29)

| Contrôle | Résultat |
|---|---|
| D.1 Exactement **4** étapes rendues (pas 5) | ✓ |
| D.2 Numérotation `01 → 04` | ✓ |
| D.3 Titres MOT-À-MOT : اختيار الموديل والتوب • اختبار التوب والجودة • الفصالة والخياطة • مراقبة الجودة والمتانة | ✓ |
| D.4 État de départ v3.28 = 5 paragraphes plats | ✓ (reproduit) |
| D.5 Textes des étapes 1-4 **inchangés mot-à-mot** vs v3.28 | ✓ |
| D.6 5ᵉ paragraphe v3.28 = texte du bandeau (clôture → note) | ✓ |
| D.7/D.8 Bandeau `.inspection-banner` présent, texte MOT-À-MOT « هاد الشي كلو باش توصلك عباية مخدومة بعناية، بخياطة متقونة وجودة عالية. » | ✓ |
| D.9 Bandeau = composant existant (icône + note), zéro classe nouvelle | ✓ |

### 3.E CSS (contrôles statiques + calculés)

- E.2/E.3 — filigrane supprimé : plus d'`opacity:.3`, plus de `position:absolute`, plus de `left:12px`.
- E.4 — pastille : `position:static; inline-grid; 34×26px; radius 7px; émeraude 10%; --olive-dark; 15px/900; opacity:1`.
- E.6 — grille 2×2 ≥701px scopée `#fabrication` ; E.7 — mobile ≤700px 1 colonne INTACTE ;
  E.8 — commande 3 colonnes desktop INTACTES ; E.9 — charte `<h3>` existante (zéro CSS nouveau) ;
  E.10 — `critical.css` sans règle `order-step` (aucun risque anti-flash).

### 3.F Contrastes WCAG 2.1 (calculés sur couleurs composées réelles)

| Traitement | Couleurs composées | Ratio | Seuil | Verdict |
|---|---|---|---|---|
| **AVANT** — filigrane olive @30 % sur carte crème | `#bfd0ce` / `#f8f4ee` | **1,46:1** | 3:1 (grand texte) | ✗ échec (reproduit) |
| **APRÈS** — pastille `--olive-dark` sur émeraude 10 % / carte `#fbfbf8` | `#2d6a70` / `#e8eeec` | **5,25:1** | 4,5:1 (texte courant) | ✓ AA |
| **APRÈS** — hypothèse carte crème 60 % | `#2d6a70` / `#e5e8e3` | **4,98:1** | 4,5:1 | ✓ AA |

### 3.G Rendu mesuré (Playwright, vrai styles.css — captures `download/v329_*`)

| Mesure | Résultat |
|---|---|
| 1280px — colonnes `#fabrication .order-steps` | **2 colonnes (572px + 572px)** |
| 1280px — position des 4 cartes | 2 rangées × 2 cartes (top 3583/3773, left 60/648) — **zéro carte orpheline** |
| 360px — colonnes fabrication | **1 colonne (310px)** — zéro débordement horizontal (scrollWidth == clientWidth) |
| 1280px — colonnes section commande | **3 colonnes (376px ×3) INTACTES** |
| Pastille (computed) | `position:static; opacity:1; color:rgb(45,106,112); background:rgba(58,125,132,.1); height:26px; radius:7px` |
| RTL | Pastille à l'inline-start (droite) AVANT le titre, ordre 01→02→03→04 en lecture arabe (01/03 colonne de droite) |

Captures : `download/v329_fabrication_1280.png` (2×2 + bandeau), `download/v329_fabrication_360.png`
(1 colonne), `download/v329_commande_1280.png` (3 colonnes, pastilles partagées).

### 3.H Version + périmètre
`style.css` **Version: 3.29** ; `git status` = EXACTEMENT les 4 fichiers du mandat.
Logo, back-link `‹ رجوع`, grille 3 offres (structure + tarifs), formulaire, tunnel COD,
TYP (`page-merci.php`), architecture 2 landings : **ZÉRO diff** (hors effet partagé documenté §2.3-3).

## 4. Limites

- Mesures Playwright sur rendu harnais (HTML réel du gabarit + vrai styles.css, images réelles) ;
  l'en-tête/pied réels (menu, barre CTA fixe) sont des stubs — sans incidence sur la section
  mesurée (`#fabrication` est auto-portante, aucun héritage de header requis).
- Le 5ᵉ texte du bandeau conserve le point final de la chaîne v3.28 validée mot-à-mot
  (citation du mandat sans guillemet final — reprise littérale du registre privilégiée).
- L'audit designer signale l'absence historique du harnais de test dans le dépôt : les scripts
  v3.29 (`scripts/harness_render_v329.php`, `scripts/validate_manufacturing_v329.py`,
  `scripts/visual_manufacturing_v329.py`) sont conservés HORS du dépôt (convention établie).
