# AUDIT — SOUS-TITRE H1 : BLOC HOMOGÈNE, TIRETS, ITALIQUE SÉLECTIF (v3.33)

**Mandat 4P LP** — « Rectification exclusive du sous-titre H1 (description du produit) ».
**Branche** : `feat/wool-abaya-product-adaptation` (branche active, resynchronisée sur `origin/main` = v3.32 `89d9270`).
**Périmètre autorisé** : la description sous le H1 UNIQUEMENT — « aucune autre modification ne doit être effectuée sur la page, tous les autres éléments (prix, offres, sélecteur) étant déjà validés et en place ».

---

## 1. Audit préalable (état v3.32 = `origin/main`)

Relevé du rendu du sous-titre avant correction :

| # | Constat v3.32 | Exigence du mandat v3.33 | Écart |
|---|---|---|---|
| 1 | La phrase Darija est rendue par un **deuxième paragraphe** `<p class="product-intro-finish">` (`display:block`, ligne indépendante) | **Continuité** — « ne doit pas être rejetée en bas ou centrée à part ; elle doit suivre immédiatement le texte principal dans le même flux » | ❌ isolée |
| 2 | `text-align: center` + `font-weight: 300` (Cairo fine) sur la phrase Darija | **Texte homogène** — « même police et même taille que le corps du texte principal » | ❌ centrée + graisse fine |
| 3 | Phrase Darija **sans tirets encadrants** | **Encadrement par des tirets** — « placée entre deux tirets (ex : `-بالنسبة للفينيسيون نقية وخياطة متقونة من الداخل ومن برا-`) » | ❌ sans tirets |
| 4 | Italique correct, mais porté par le paragraphe isolé tout entier | **Italique sélectif** — « seule cette phrase en darija (située entre les tirets) doit être en italique … en conservant la même police de base » | ⚠️ à refondre |
| 5 | Texte Fusha v3.32 (« عباية شتوية فاخرة من توب الصوف الدافئ الكثيف… ») | **Texte exact PARTIE 3** — « عباية شتوية من توب الصوف، بقصة دوبل كلوش واسعة وفضفاضة وانسيابية، تجمع بين الدفء والاحتشام والمظهر الأنيق. » | ❌ à remplacer |

Vérifications connexes : aucune référence JS à `intro_finish`/`product-intro` (app.js/merci-review.js : 0 occurrence) ; `critical.css` ne connaît que `.product-intro` (3 points de rupture) — **aucune retouche critical.css nécessaire** ; `@font-face Cairo 200–399` (v3.32) est **également utilisé par `.modal-close` (styles.css:613, `font: 300 27px/1 "Cairo"…`)** → il est CONSERVÉ tel quel (son retrait aurait modifié le rendu de la croix de modale = modification hors périmètre interdite).

## 2. Exécution (4 fichiers — périmètre strict)

1. **`inc-offers.php`** (registre vedette) :
   - `'intro'` remplacé par la phrase Fusha **EXACTE de la PARTIE 3** (mot-à-mot, ponctuation incluse) ;
   - `'intro_finish'` **conservé tel quel** (phrase Darija pure, sans tirets — les tirets sont posés par le gabarit, ils encadrent la phrase) ;
   - commentaire documentaire v3.32 → v3.33.
2. **`template-parts/landing-offer.php`** : les DEUX paragraphes v3.32 (`p.product-intro` + `p.product-intro-finish`) sont remplacés par **UN SEUL paragraphe** :
   ```php
   <p class="product-intro"><?php echo esc_html($T['intro']); ?><?php if (!empty($T['intro_finish'])) : ?> <em class="product-intro-darija">-<?php echo esc_html($T['intro_finish']); ?>-</em><?php endif; ?></p>
   ```
   - l'espace avant `<em>` est DANS la branche conditionnelle → `produit-1` (sans la clé) reste **bit-exact** ;
   - `<em>` = élément **inline** → continuité du flux garantie par la structure même du HTML ;
   - les tirets `-` encadrent la phrase Darija (inclus dans l'`<em>` — la PARTIE 3 du mandat italicise « la partie `-…-` »).
3. **`assets/css/styles.css`** : la règle `.product-intro-finish` (bloc/centré/300/italique) et sa déclinaison mobile (`.product-intro-finish { margin-top: 2px; }`) sont **SUPPRIMÉES** et remplacées par la règle unique :
   ```css
   .product-intro .product-intro-darija { font-style: italic; }
   ```
   — même police, même taille, même graisse et même couleur que le corps via **héritage intégral** de `.product-intro` ; l'italique est explicite (mandat §2.4) et scopé à la seule phrase Darija. `@font-face Cairo 200–399` **CONSERVÉ** (utilisé par `.modal-close`). Aucune autre règle touchée.
4. **`style.css`** : version 3.32 → **3.33**.

## 3. Intangibilité prouvée (harnais déterministe v329)

Rendus PHP hors WordPress (`harness_render_v329.php`, stubs déterministes — `accueil` et `produit-1`) :

- **`produit-1.html` : diff BIT-EXACT 0 octet** entre main v3.32 et branche v3.33 (la landing secondaire est structurellement hors de portée : la clé `intro_finish` y est absente et la branche ne s'ouvre pas).
- **`index.html` (vedette) : diff = 3 lignes**, confinées au seul bloc sous-titre (2 paragraphes v3.32 → 1 paragraphe v3.33). Toute autre ligne de la page est identique octet pour octet.
- `git diff --stat` : 4 fichiers (+37/−28), dont commentaires documentaires — **prix, offres, sélecteur, formulaire, tunnel, TYP, images, logo, back-link : ZÉRO diff**.

## 4. Validation (Playwright, `test_subtitle_v333.py` — 24 PASS / 0 FAIL)

- **A. Structure & verbatim** : UN seul `p.product-intro` ; ZÉRO `.product-intro-finish` ; `em.product-intro-darija` présent DANS le paragraphe (`parentElement` = le paragraphe) ; texte de l'`em` = `-بالنسبة للفينيسيون نقية وخياطة متقونة من الداخل ومن برا-` (les DEUX tirets) ; texte complet du paragraphe = `FUSHA. -DARIJA-` mot-à-mot PARTIE 3 ; la phrase Fusha ouvre le paragraphe (premier nœud texte).
- **B. Texte homogène** : `getComputedStyle` — même `font-size`, même `font-family`, même `font-weight`, même `color`, même `line-height` entre le paragraphe et l'`em`.
- **C. Continuité** : `display: inline` sur l'`em` ; paragraphe non centré (`text-align ≠ center`) ; rectangle de l'`em` inclus dans celui du paragraphe (même flux) ; l'`em` suit immédiatement le nœud texte Fusha (finissant par « . »).
- **D. Italique sélectif** : paragraphe `font-style: normal`, **seule** l'`em` en `italic`.
- **E. Régressions nulles** : les 3 prix barrés `499/958/1437 درهم` intacts ; H1 verbatim intact ; ruban « الأكثر طلباً » (Option A) intact ; **`.modal-close` toujours Cairo 300** (prouve la conservation du `@font-face 200–399`).
- **F. Mobile 360px** : homogénéité p/em conservée (16px == 16px — la règle 16px de styles.css:803 étant inconditionnelle depuis avant v3.32, probe baseline v3.32 @360 = 16px identique : **zéro changement de taille**), italique + tirets présents, `overflow` horizontal = 0.
- **G. produit-1** : ni `em`, ni darija, ni finish ; intro « عباية خريفية… » inchangée (conforme au diff bit-exact).
- **Lint** : `php -l` ×2 (inc-offers.php, landing-offer.php) — 0 erreur.
- **Captures** : `scripts/shots_v333/` — vedette 1280 (sous-titre homogène, darija italique tiretée dans le flux), vedette 360 (idem mobile, aucun débordement), produit-1 1280.

## 5. Conclusion

La description du sous-titre H1 est désormais **un bloc homogène** : la phrase en arabe littéraire (texte exact de la PARTIE 3) est suivie **immédiatement, dans le même flux**, par la phrase en darija **encadrée de deux tirets** et **seule elle italique**, à la même police et la même taille que le corps. Aucun autre élément de la page n'a été modifié (produit-1 bit-exact, diff vedette confiné au bloc intro).
