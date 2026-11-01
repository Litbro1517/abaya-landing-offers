# RAPPORT D'EXÉCUTION — v3.38 CORRECTIF CLS 0,39 (branche `fix/cls-v3.38`, mandat 4P LP)

**Date** : 01/11/2026 · **Base** : `origin/main` = v3.37 (4a18b7e) · **Branche** : `fix/cls-v3.38`
**Références** : `fix-cls-v3.38.patch` (pièce jointe du mandat) + `docs/AUDIT-CLS-v3.38.md` (audit v3.38)

---

## 1. Verdict de conformité

| Exigence du mandat | Statut |
|---|---|
| Patch `fix-cls-v3.38.patch` appliqué rigoureusement | ✅ VERBATIM (commit `9d4c1ae`) — **avec 1 alerte : défaut du patch corrigé en commit séparé (`d7b5996`), voir §3** |
| Parité `critical.css` ↔ `styles.css` au-dessus de la flottaison | ✅ prouvée programmatiquement 17/17 (§5.1) |
| CLS éliminé | ✅ 0,442 (témoin v3.37) → **0,000** (v3.38) sur banc harnais production, styles.css retardé 1,2 s (§5.2) |
| Zéro régression : design, multi-pièces, Shake & Red Border, tracking, srcset/preload v3.37 | ✅ 35/35 + 24/24 + 41/44 = baselines identiques (§5.3) |
| Périmètre : CSS + docs uniquement | ✅ 4 fichiers, **PHP/JS/images ZÉRO ligne** (§6) |
| Pas de merge vers `main` sans feu vert | ✅ branche poussée seule — **merge conditionnel en attente d'arbitrage** |
| `PROJECT_MAP.md` (section *5-vigesimus-sexto*) | ✅ livrée par le patch + complétée avec les preuves d'exécution |

---

## 2. Vérification préalable (avant application)

1. **État du dépôt** : `main` local == `origin/main` == v3.37 (`style.css` Version 3.37, commit 4a18b7e), arbre propre.
2. **Contexte du patch** : `git apply --check` OK — le hunk `critical.css` attend les lignes 440-443 se terminant par `.secure-note{font-size:12px}` (sans newline final) : conforme à l'état réel (443 lignes de contenu).
3. **Règles sources présentes dans `styles.css`** (vérifiées ligne à ligne, valeurs identiques au patch) :
   - `styles.css:1751-1753` — `.product-landing main{width:calc(100% - 20px);margin:10px auto 22px;border-radius:20px}` (≤700px) ;
   - `styles.css:1321` — `.product-landing .product-info h1{color:var(--ink);font-weight:900}` ;
   - `styles.css:2330-2333` — `.product-landing:not(.is-upsell) .hero{padding-block:26px 42px;border-bottom:1px solid var(--line)}` ;
   - `styles.css:1261-1294` — `.stock-overlay` / `.stock-badge` / `.form-submit.is-unavailable` ;
   - `styles.css:1295-1297` — reduced-motion `.stock-overlay{transition:none}` ;
   - `styles.css:1301-1305` — liste font-family « Cairo, Tahoma, Arial » (11 sélecteurs).
4. **Stratégie de chargement confirmée** (`functions.php:243-253`) : critical inline, `styles.css` en `media="print" onload` + `preload as=style` — conforme à la cause racine de l'audit.
5. **Cascade** : aucune règle conflictuelle préexistante dans `critical.css` — les anciennes règles `.hero` (0,1,0), `.product-info h1{font-weight:800}` (0,1,1) et le `font` shorthand `.eyebrow` (0,1,0) sont battues par les règles v3.38 (spécificité supérieure ou même spécificité + position ultérieure). Voir §5.1.

---

## 3. 🚨 ALERTE — DÉFAUT DÉTECTÉ DANS LE PATCH (et correction appliquée)

**Conformément à la PARTIE 1 §2 du mandat (signalement obligatoire avec preuves) :**

- **Constat** : le hunk `assets/css/critical.css` de `fix-cls-v3.38.patch` (ligne 76 du fichier de patch) se termine par :
  ```css
  +@media (prefers-reduced-motion: reduce) {
  ```
  **sans aucune règle à l'intérieur et sans accolade fermante** — le patch produisait un `critical.css` déséquilibré (**238 `{` contre 237 `}`**), en contradiction avec son propre commentaire (« copie conforme styles.css:1261-1294 » — le bloc reduced-motion de référence est `styles.css:1295-1297`).
- **Impact fonctionnel du défaut** : nul en pratique (un bloc `@media` non fermé en fin de fichier est implicitement clos à l'EOF ; aucune règle ne le suit dans `critical.css` ni dans le `<style>` inline — vérifié : le banc CLS sur l'état brut donne déjà 0,000). Mais : CSS mal formé (validateurs) + **piège latent** (toute règle ajoutée plus tard en fin de critical.css serait avalée par ce bloc `@media`).
- **Décision d'exécution** (arbitrage en toute transparence) : il n'y avait pas d'arbitrage à trancher entre des options — l'implémentation de référence existe verbatim dans `styles.css:1295-1297`. Correction **minimale en commit SÉPARÉ** (`d7b5996`) pour réversibilité immédiate :
  ```css
  @media (prefers-reduced-motion: reduce) {
    .stock-overlay { transition: none; }
  }
  ```
  Après correction : **239 `{` / 239 `}`** — équilibre parfait. Le commit verbatim (`9d4c1ae`) conserve l'état brut du patch pour audit.
- **Recommandation** : régénérer le patch depuis un arbre vérifié (ou intégrer `scripts/check_parity_v338.py` + un contrôle d'équilibre d'accolades dans la CI des patchs).

---

## 4. Étapes d'exécution (journal)

| # | Étape | Résultat |
|---|---|---|
| 1 | Fetch `origin`, vérification `main` == v3.37, arbre propre | ✅ |
| 2 | Création branche `fix/cls-v3.38` depuis `origin/main` | ✅ |
| 3 | `git apply --check` du patch | ✅ sans rejet |
| 4 | Application verbatim + commit `9d4c1ae` (patch, audit doc, PROJECT_MAP, bump 3.38) | ✅ |
| 5 | Contrôle du résultat : détection du bloc `@media` non fermé (238/237) | 🚨 alerte |
| 6 | Complétion minimale copie conforme `styles.css:1295-1297` + commit `d7b5996` | ✅ 239/239 |
| 7 | Parité programmatique `check_parity_v338.py` | ✅ 17/17 |
| 8 | Banc CLS harnais production `test_cls_v338.py` | ✅ 23/23 |
| 9 | Régressions : `test_validation_v335.py`, `test_perf_v337.py`, `test_size_bug_v331.py` | ✅ 35/35, 24/24, 41/44 |
| 10 | Complétion `PROJECT_MAP.md` §5-vigesimus-sexto + présent rapport | ✅ |
| 11 | Push branche `fix/cls-v3.38` vers origin (PAS main) | ✅ |
| 12 | Merge + push `main` via WP Pusher | ⏸ **en attente du feu vert** |

---

## 5. Preuves de validation

### 5.1 Parité programmatique — 17/17 (`scripts/check_parity_v338.py`)
Comparaison tokenizer CSS (contexte `@media`, propriété par propriété, tolérance zéro) :
- `main` ≤700px (3 propriétés), `.product-info h1` (2), `.hero` (2), `.stock-overlay` (12), `.stock-overlay.is-visible` (2), `.stock-badge` (13), `.form-submit.is-unavailable` (3), reduced-motion `.stock-overlay` (1) : **toutes identiques** aux règles sources de `styles.css`.
- font-family ×5 (`.wordmark-name`, `.footer-wordmark`, `.eyebrow`, `.price`, `.size-option`) : `"Cairo", Tahoma, Arial, sans-serif` identique à `styles.css:1301-1305`.
- Cascade : h1 900 bat les anciens 800 (spécificité 0,2,1 > 0,1,1) ; `.hero` v3.38 (0,3,0) bat les anciens `.hero` (0,1,0) ; `.eyebrow` : règle famille v3.38 (index 220) APRÈS le `font` shorthand (index 37), même spécificité → gagne au 1er rendu ; `main` : règle unique.
- Note : les 6 sélecteurs font-family de `styles.css:1301` non repris par le patch (`.bundle-price`, `.order-summary .summary-price`, `.review-card-score`, `.modal-close`, `.legal-eyebrow`, `.order-step-number`) sont hors périmètre « au-dessus de la flottaison » de l'audit (§5 : parité vérifiée jusqu'à `#formulaire-commande`).

### 5.2 Banc CLS harnais production — 23/23 (`scripts/test_cls_v338.py`)
Harnais reproduisant la structure réelle (critical **inline**, `styles.css` **async** `media="print" onload`, `offers.css` inline — état v3.37/v3.38), serveur retardant `styles.css` de 1,2 s, Chromium mobile 412×823 DPR 1.75, `PerformanceObserver('layout-shift', buffered)` :

| Cas | CLS mesuré | Attendu (audit) |
|---|---|---|
| A — v3.37 `origin/main` + retard 1,2 s (témoin) | **0,442** (sources : `MAIN`, `FIELDSET.choice-group`) | 0,390 — défaut reproduit ✅ |
| B — v3.38 branche + retard 1,2 s (correctif) | **0,000** | 0,000 ✅ |
| C — v3.38 branche sans retard | **0,000** | ~0,013 résidu ✅ (0 mesuré, fontes/harnais favorables) |

Parité géométrique sur la branche (styles.css en attente vs final) :
- positions identiques 1er rendu == final : `main` {w 392, top 10, left 10}, `h1` {w 358, top 525, left 27}, `.hero` {w 362, top 10, left 25}, **cadre photo LCP** {w 362, top 36, left 25} ;
- propriétés dès le 1er rendu : h1 `font-weight: 900`, `.hero` padding `26px/42px`, `.stock-overlay` `position: absolute`, `.eyebrow` `Cairo, Tahoma, Arial` ;
- post-fontes (`document.fonts.ready`, avant application de styles.css) : h1 hauteur **125,4 px identique** avec/sans styles.css (l'écart 83,6→125,4 du 1er rendu est l'artefact de chargement de la police Cairo du harnais — rules statiquement identiques, preuve par `__mid`) ; cadre photo LCP identique ;
- **géométrie finale identique v3.38 vs v3.37** (main/h1/hero) — zéro changement visuel final ;
- dérive sous-flottaison mesurée à l'application de styles.css : main −6301 px, hero −385 px — conforme au « résidu sous la flottaison » documenté audit §5 (grille d'offres, formulaire) : **hors CLS** (aucun élément visible ne bouge — CLS 0,000 le prouve), à résorber dans une version dédiée.
- Témoin v3.37 : 1er rendu `main` {w 412, top 0, left 0} ≠ final {w 392, top 10, left 10} = exactement le défaut décrit par l'audit (carte main inset).

### 5.3 Régressions rejouées sur harnais branche
| Suite | Résultat | Couverture |
|---|---|---|
| `test_validation_v335.py` | **35/35 PASS** | prix 479/529/توفير 50, sous-titre H1, Shake & Red Border (champs + couleur/taille), multi-picker, mobile 360 sans overflow |
| `test_perf_v337.py` (branche vs réf) | **24/24 PASS** | srcset/sizes ×5 visuels, preload, swap galerie, produit-1 sans srcset (bit-exact), géométrie @1280/@360 == référence |
| `test_size_bug_v331.py` | **41/44 = baseline main identique** | 3 écarts PRÉEXISTANTS prouvés sur main depuis v3.36 : 2× 404 polices (artefact harnais `__FONT_BASE__`), 1 assertion obsolète wool-2.jpg (v3.34) |

Tracking/REST/pixel : **aucun fichier PHP/JS modifié** (§6) — comportement inchangé par construction, confirmé par les suites (soumission formulaire, payload, C15).

---

## 6. Périmètre exact du diff (`origin/main..fix/cls-v3.38`)

```
assets/css/critical.css   | 51 ++++++++++++++++  (49 lignes patch + 2 lignes complétion §3)
docs/AUDIT-CLS-v3.38.md   | 54 +++++++++++++++    (pièce du patch)
docs/PROJECT_MAP.md       | 11 ++++++++--        (patch + preuves d'exécution)
style.css                 |  2 +-                (Version 3.37 → 3.38)
4 fichiers — PHP/JS/images : ZÉRO ligne.
```

---

## 7. Limites & suites (pour arbitrage)

1. **Re-mesure PSI sur dev APRÈS déploiement** (le lien PSI du mandat mesure l'état pré-correctif) : attendu CLS vert (~0,00-0,013) et score > 80 ; le banc local ≠ PSI (stubs WP, pas d'avis/Pixel réels).
2. **Dérive sous-flottaison** (grille d'offres, formulaire, sections visuelles) : mesurée (−6301 px main), sans effet CLS PSI, à résorber dans une version dédiée (recommandation audit §5).
3. **Règle d'or** (audit §6) : toute règle visible au 1er écran doit exister dans critical.css ET styles.css — proposer un test CI de parité (360/412/768/1280 px, tolérance 0) + contrôle d'équilibre d'accolades des patchs.
4. Le LCP 2,1 s n'est pas retravaillé par cette version (hors périmètre — déjà optimisé v3.37).

---

## 8. Statut final

- Branche `fix/cls-v3.38` poussée sur origin (3 commits : `9d4c1ae` patch verbatim, `d7b5996` complétion alerte, + docs) — **aucun merge vers main**.
- **En attente du feu vert d'arbitrage** : merge + push `main` via WP Pusher, puis re-mesure PSI.
