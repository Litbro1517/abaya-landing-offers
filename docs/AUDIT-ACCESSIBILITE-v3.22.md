# AUDIT-ACCESSIBILITE-v3.22 — Correctifs WCAG 2.1 (mandat 4P LP « fix/accessibility-quickfixes »)

**Date** : 2026-10-22 · **Branche** : `fix/accessibility-quickfixes` · **Base** : main @ 4f9eec2 (v3.21)
**Périmètre autorisé** : `assets/css/styles.css` + `assets/css/critical.css` (style.css = en-tête version 3.22, métadonnée de livraison).
**Zéro modification** : markup HTML/PHP/JS, propriétés géométriques (`padding`, `margin`, `width`, `height`, `font-size`, `display`), composants fonctionnels.

---

## 1. Conjoncture et diagnostic

Trois blocages d'accessibilité pénalisaient les landing pages avant l'injection des visuels produits :

| # | Bloqueur | État AVANT (mesuré) | Critère WCAG |
|---|---|---|---|
| 1 | Surtitres or illisibles | `.eyebrow` = `var(--gold)` #d4af37 → **2.03:1** sur fond #fbfbf8 (or VF #C5A059 → 2.37:1) | 1.4.3 (> 4.5:1) |
| 2 | Coche des garanties invisible | `--check-mark` #d4af37 sur fond émeraude `--check-bg` #3a7d84 → **2.25:1** | 1.4.11 (> 3:1) |
| 3 | Focus clavier invisible | `:focus` = halo émeraude 10-12 % + `outline: 0` socle ; champs `[readonly]` : `outline:none; box-shadow:none` explicites | 2.4.7 |

## 2. Correctifs appliqués (couleurs/tokens/focus uniquement)

### Correctif 1 — Surtitres or lisibles
- `styles.css` bloc `.eyebrow` : `color: var(--gold)` → **`color: #7d6117`** (en place, commenté v3.22).
- `critical.css` bloc `.eyebrow` : même remplacement (règle anti-flash : les deux fichiers portent la valeur identique).
- **Mesure** : 5.64:1 sur #fbfbf8 (vedette + variantes, v3.4+) · 5.15:1 sur #F5F0E8 (crème v3.3 résiduel) — **> 5:1 exigé : PASS**.
- Hors touch (assumé, non textes) : `.eyebrow-line` (filet décoratif 27×1px, reste `var(--gold)` or marque), `.legal-eyebrow` (pages réglementaires v3.20 — hors périmètre landing), `.secure-note span` (span icône SVG, texte réel hérite émeraude).

### Correctif 2 — Coche des garanties sur fond émeraude
- Déclarations de token : `--check-mark: #d4af37` → **`--check-mark: #e8dcb8`** dans `styles.css` (bloc `:root`, L227) ET `critical.css` (L18).
- **Mesure** : #e8dcb8 sur #3a7d84 = **3.45:1 — > 3.4:1 exigé : PASS** (AVANT : 2.25:1 FAIL).
- Effet rendu réel : les landings affichaient déjà coche blanche (override v3.3 `.bundle-card::before` #3A7D84/blanc — INTACT, mesuré inchangé) et puces SVG émeraude (override v3.0 — INTACT, mesuré inchangé) ; la valeur token or résiduelle s'appliquait aux badges `✓` en rendu socle (cas upsell) — désormais crème lisible. Zéro régression : la correction ne fait qu'améliorer le contraste partout où le token s'applique.

### Correctif 3 — Anneau de focus clavier visible
- `styles.css` — section **v3.22** en FIN de feuille + `critical.css` — immédiatement après la règle `:focus` existante (anti-flash) :
  - `.form-field input:focus-visible, .form-field select:focus-visible { outline: 2px solid var(--olive); outline-offset: 0; }`
  - `.product-landing .order-form-grid input[readonly]:focus-visible { outline: 2px solid var(--olive); outline-offset: 0; }`
- Les `:focus` historiques (halo 10-12 %, changement de bordure) restent intacts pour le pointeur ; l'anneau émeraude `2px solid var(--olive)` #3a7d84 ajoute l'indicateur manquant sans toucher au markup ni à aucune propriété géométrique.
- Le compagnon `[readonly]` restaure l'indicateur de tabulation sur les champs pré-remplis verrouillés (tunnel) : valeurs et apparence grise (`#f1efe8`, `cursor: not-allowed`, opacité .78) inchangées — seule la visibilité du focus clavier (WCAG 2.4.7) est rétablie ; le verrou `:focus` historique n'est pas supprimé (règle nouvelle en cascade).

## 3. Auto-audit exécuté (100 % conformité avant fusion)

1. **Contrastes calculés** (`scripts/wcag_contrast_v322.py`, formule WCAG 2.1) : #7d6117/#fbfbf8 = 5.64:1 PASS · #7d6117/#F5F0E8 = 5.15:1 PASS · #e8dcb8/#3a7d84 = 3.45:1 PASS · preuves AVANT : 2.03:1 / 2.25:1 FAIL.
2. **Rendu mesuré Playwright** (`scripts/shot_v322_a11y.py`) : **17/17 PASS** —
   - `.eyebrow` computed = rgb(125, 97, 23) en rendu complet ET critical-only (anti-flash) ;
   - coche upsell = rgb(232, 220, 184) sur rgb(58, 125, 132) ; gardes : coche landing (chip #E8F0EE + SVG émeraude) et `.eyebrow-line` or décoratif **inchangés** ;
   - anneau de focus : Tab clavier sur champs éditables ET `[readonly]` → `outline 2px solid rgb(58,125,132)` + `matches(':focus-visible')` true, en rendu complet ET critical-only ;
   - preuves AVANT : eyebrow rgb(212,175,55), coche or, outline 0px/none.
3. **Zéro dérive géométrique** : computed styles comparés CSS v3.21 (git main) vs v3.22 sur 9 éléments (landing + upsell) × 17 propriétés (`padding*`, `margin*`, `width`, `height`, `font-size`, `display`, `border-width`, `gap`, `line-height`, `min-height`, `max-width`, `letter-spacing`) → **AUCUNE différence**.
4. **Audit diff git** : fichiers touchés = `assets/css/styles.css`, `assets/css/critical.css`, `style.css` (en-tête Version 3.22) — aucun HTML/PHP/JS ; aucune ligne ajoutée/modifiée ne contient de propriété géométrique ; accolades équilibrées (671/671, 228/228).
5. **Captures 360px** (Galaxy S8) : `v322_landing_360.png`, `v322_upsell_360.png`, `v322_focus_ring_360.png` — surtitre or foncé lisible, coche visible, anneau net sur le champ focalisé, zéro débordement.

## 4. Interdits respectés

- Branche isolée `fix/accessibility-quickfixes` ; `main` inchangée avant auto-audit vert.
- Aucune modification de markup/PHP/JS, aucune propriété géométrique, aucun composant fonctionnel (formulaire v3.1, sticky v3.5, logo v3.11-13, modales v3.14-16, avis v3.7, garanties v3.8/v3.9/v3.17, footer v3.8/v3.9, devises v3.10, garde-fou v3.18, légal v3.20, rythme v3.21).
- Règle anti-flash : chaque valeur corrective présente à l'identique dans `styles.css` et `critical.css` (token --check-mark, .eyebrow, règles :focus-visible).
- `--gold` #d4af37 et `--accent-gold` #C5A059 conservés pour leurs usages légitimes (logo `.wordmark-rule`, filet décoratif, accents upsell) — seuls les éléments ciblés (eyebrows) et le token --check-mark changent.

## 5. Décision

Auto-audit **17/17 PASS + contrastes conformes + zéro dérive géométrique** → fusion vers `main` autorisée, déploiement WP Pusher déclenché, statut vert pour la phase d'injection des images produits.
