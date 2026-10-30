# AUDIT-SIZE-BUG-v3.31 — Boutons de taille du sélecteur multi-pièces

**Mandat 4P LP — « feat/wool-abaya-product-adaptation » (correctif)**
**Branche :** `feat/wool-abaya-product-adaptation` (base = `origin/main` `6beae86`, v3.30)
**Périmètre :** LP principale (vedette) uniquement — produit-1, TYP, tunnel : intacts.

---

## PARTIE A — RAPPORT D'AUDIT INITIAL (avant toute modification)

### A.1 Contexte constaté
Le sélecteur multi-pièces (v3.30, logique `abaya-multi-piece-picker.html`) s'affiche
correctement sur les offres 2/3 pièces : lignes couleur avec compteurs −/+ et
répartition par défaut (1+1 / 2+1), rangées de pièces avec puces de taille
S/M/L/XL/XXL. **Mais les puces de taille « ne s'activent pas » au clic** : aucun
état actif visible, la sélection paraît ne jamais valider.

### A.2 Méthode d'audit
1. Synchronisation git : `origin/main` = `6beae86` (v3.30) — branche recréée
   depuis `origin/main`. Assets déployés sur l'environnement de test
   (dev.abayacollection.store) comparés **octet par octet** au dépôt :
   `app.js` et `styles.css` **IDENTIQUES** — le bug est bien dans le code v3.30.
2. Lecture complète du chemin de clic : `assets/js/app.js` (délégation
   `pickerRoot` lignes 211-255, rendu `renderMultiPicker()` lignes 143-192),
   `template-parts/landing-offer.php` (conteneur `#multi-picker` ligne 128,
   fieldsets historiques lignes 88-116), charte CSS (`.size-option` :
   critical.css:168-175, styles.css:408-417 et 1319-1321, section v3.30 :
   styles.css:2441-2514).
3. Reproduction en navigateur réel (Playwright/Chromium) sur l'environnement
   déployé **et** sur le harnais local déterministe :
   - clic souris 1280×900, clic souris 360×800, **tap tactile émulé**
     (`has_touch=True, is_mobile=True` — l'appareil réel des clientes) ;
   - instrumentation : `document.elementFromPoint` (hit-test), état JS
     (`#f_json`), attributs `aria-pressed`, `classList`, styles calculés
     (`getComputedStyle`) **après déplacement du pointeur** (sans `:hover`),
     état du bouton de commande, erreurs console/page.

### A.3 Constats mesurés (pré-correctif)
| # | Constat | Mesure |
|---|---------|--------|
| 1 | La délégation JS fonctionne | après tap M pièce 1 : `f_json` `size:null → "M"`, `aria-pressed="true"` ✓ |
| 2 | **Aucune classe d'état appliquée** | `classList` reste `"size-option"` — jamais `.is-selected` |
| 3 | **Aucune règle CSS pour l'attribut** | `getComputedStyle(chip pressé)` sans hover : `background: rgba(0,0,0,0)`, `color: rgb(44,29,17)` (inchangé) |
| 4 | Le `:hover` masquait le défaut | souris résidente sur la puce → `background rgb(58,125,132)` (olive) : c'est le HOVER, pas l'état actif — d'où batterie v3.30 « verte » au clic souris |
| 5 | La validation JS est saine | tap M (pièce 1) puis L (pièce 2) → bouton de commande débloqué, résumé « قطعتان \| ق1: بني كريمي / M \| ق2: أسود / L » ✓ |
| 6 | Aucune erreur console/page | 0 message sur tous les scénarios |
| 7 | Hit-test propre | `elementFromPoint` = la puce elle-même ; aucun recouvrement |

### A.4 ORIGINE PRÉCISE DU BUG (cause racine unique)
**Désalignement entre l'état exposé par les puces dynamiques et la charte CSS.**

- Le contrat du fichier de référence (`abaya-multi-piece-picker.html`) style
  l'état actif par **sélecteur d'attribut** : `.chip[aria-pressed="true"]`.
- L'intégration v3.30 rend les puces du picker avec le même attribut
  (`aria-pressed="${item.size === size}"`, app.js:166) **mais** :
  1. le JS ne leur applique **jamais** la classe `.is-selected` (seules les
     puces statiques du fieldset « offre 1 » la reçoivent, app.js:380-388) ;
  2. la charte CSS ne connaît que `.size-option:hover` et
     `.size-option.is-selected` (critical.css:175, styles.css:417/1320-1321) —
     **aucune règle** `.size-option[aria-pressed="true"]` n'existe
     (section v3.30 styles.css:2441-2514 confirmée).
- Résultat : l'état actif existe dans le DOM (`aria-pressed="true"`) mais
  **aucun style ne le rend**. Sur desktop, le survol de la souris le masquait
  (`:hover` olive) ; sur mobile — l'appareil réel des clientes, sans hover —
  **zéro retour visuel** : les puces paraissent mortes et la sélection paraît
  ne jamais valider, alors que l'état interne et le déblocage du bouton
  fonctionnent (constats 1 et 5).

Ce n'est donc ni un problème d'écouteurs (délégation saine), ni de liaison DOM
(conteneur/fieldsets corrects), ni de CSS bloquant (hit-test propre) : c'est un
**défaut de liaison classe CSS ↔ état** sur les puces rendues dynamiquement.

---

## PARTIE B — DESCRIPTION DE LA CORRECTION (v3.31)

Liberté d'implémentation (mandat PARTIE 3) exercée **au minimum utile**, sans
toucher au design ni à la structure :

1. **`assets/js/app.js`** — `renderMultiPicker()` : les puces de taille rendues
   dynamiquement portent désormais la classe **`.is-selected`** quand la taille
   est choisie (même mécanique que les puces statiques historiques, app.js:383).
   L'état actif réutilise donc les sélecteurs de charte **existants et validés**
   (`.size-option.is-selected` → olive, 43×39, radius 10px) — identique au
   fieldset « offre 1 ». L'attribut `aria-pressed` (contrat a11y du handoff)
   est conservé inchangé.
2. **`assets/css/styles.css`** — règle NOUVELLE (section v3.31, fin de feuille,
   zéro règle existante modifiée) : filet de sécurité scopé au picker,
   `.multi-picker .size-option[aria-pressed="true"]` rend le même état olive si
   un chemin de rendu futur ne poser que l'attribut. Scopée à `.multi-picker` →
   zéro effet sur les fieldsets historiques.
3. **`style.css`** — Version 3.30 → 3.31.

**Ce qui n'est PAS touché** : structure HTML du gabarit, conteneur/affichage
conditionnel, compteurs, répartition 1+1/2+1, conservation des tailles par
index, payload REST (f_pieces/f_total/f_items/f_json), offre 1 (bit-exacte),
produit-1, TYP, tunnel, critical.css, offers.css, logo, back-link, images.

## PARTIE C — VALIDATION (post-correctif)

**Batterie `test_size_bug_v331.py` (harnais local déterministe, Playwright) : 44/44 PASS, 0 FAIL.**

| Scénario | Couverture | Résultat |
|---|---|---|
| A — État actif sans hover (LE bug) | tap tactile 360px sur M pièce 1 : classe `.is-selected` posée, fond olive `rgb(58,125,132)` calculé SANS hover, `aria-pressed="true"` cohérent, une seule puce active, cohérence classe/attribut/fond sur toutes les puces, 0 erreur console | 7/7 |
| B — Flux complet offre 2 | 2 taps → bouton de commande DÉBLOQUÉ, résumé « قطعتان \| ق1: بني كريمي / M \| ق2: أسود / L », `f_json` contractuel, `f_pieces=2`/`f_total=799`, changement fluide L→XL | 7/7 |
| C — Offre 3 + conservation par index | répartition 2+1, 3 rangées, M conservé sur pièce 1, déblocage après 3 tailles, `f_json` 3 pièces, overflow 0 @360px | 8/8 |
| D — Compteurs | déc noir → 2+0, `−` désactivé à 0, rangées masquées sous la somme (contrat handoff « wait until counters are complete »), hint (2/3), `+` réactivé puis désactivés à la somme | 9/9 |
| E — Régression offre 1 | picker masqué, swatch/puce statique `.is-selected` (comportement historique bit-exact), image wool-2, résumé historique | 8/8 |
| F — Régression produit-1 | aucun nœud `#multi-picker`, fieldsets visibles (aucun chemin picker exécuté) | 4/4 |
| G — Preuve desktop | clics M+L → cohérence complète + capture `v331_offer2_1280.png` | 1/1 |

**Preuves complémentaires :**
- Capture `scripts/shots_v331/v331_tap_M_piece1_360.png` — puce « M » pleine olive, texte blanc, layout intact.
- Rendus PHP (harnais déterministe) vedette + produit-1 : **BIT-EXACTS vs `origin/main`** (0 octet de diff — le correctif ne touche aucun gabarit).
- `php -l` ×3 (landing-offer.php, inc-offers.php, functions.php) : OK. `node --check app.js` : OK.
- Sondes réelles : `audit_size_bug_probe_v331.py` (dev, avant correctif — constats A.3), `audit_size_bug_touch_v331.py` (dev, tap tactile avant correctif), `probe_active_state_v331.py` (harnais pré/post fix).

