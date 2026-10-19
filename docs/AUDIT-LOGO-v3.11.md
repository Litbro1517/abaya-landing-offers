# AUDIT-LOGO v3.11 — Mandat 4P LP « Logo global centré + remplacement calligraphie شكراً »

**Date** : 2026-10-20 · **Portée** : les 4 Thank You Pages (gabarit unique `page-merci.php`, 4 origines
`accueil` / `produit-1` / `produit-2` / `produit-3`) · **Périmètre fermé** : 2 éléments seulement.

---

## ÉLÉMENT 1 — Calligraphie « شكراً » (`body > main > div.merci-card > header > img.merci-logo`)

### Localisation exacte (3 points de référence, tous dans `page-merci.php`)
| # | Ligne | Rôle |
|---|-------|------|
| 1 | 364 | `<img class="merci-logo" ...>` — vue **commande** (carte principale) |
| 2 | 479 | `<img class="merci-logo" ...>` — vue **générique** (sans clé valide) |
| 3 | 183 | Registre `'logo' => .../merci-logo-shukran.webp` → exposé via `window.ABAYA_MOCK.logo`, consommé par `merci-review.js:247,267` (vue mock/design injecté) |

Le chemin JS découle du registre PHP (source unique) : **aucune modification `merci-review.js` requise**.

### Fichier actuel
`assets/images/merci-logo-shukran.webp` (7 788 o, calligraphie Amiri générée).

### CSS existant (aucune retouche nécessaire)
`page-merci.php:55` — `.merci-logo { width:92px; height:92px; object-fit:contain; display:block; margin:0 auto 4px }` (mobile 80px, l.151).
`object-fit:contain` garantit **zéro distorsion** quel que soit le ratio du nouveau fichier ; `margin:0 auto` garantit le **centrage** dans `.merci-head` (`text-align:center`).

### Action v3.11
Copier l'upload `logo-thank-you-page.webp` → **`assets/images/logo.webp`** (3 038 o, WebP léger),
remplacer les 3 références PHP, supprimer l'ancien fichier (zéro orphan).

---

## ÉLÉMENT 2 — Logo global de la marque (`body > header > div > span > img`)

### Chaîne de rendu sur les TYP
`header.php:22` → `get_template_part('template-parts/' . (is_front_page() ? 'site-header' : 'legal-header'))`
→ sur `/merci/` (non front) : **`template-parts/legal-header.php`**, branche tunnel (`$abaya_tunnel_page`, l.10/15-16) :

```html
<header class="legal-header">
  <div class="logo-wrapper">
    <span class="footer-wordmark">                      <!-- SPAN = non-cliquable ✔ -->
      <img src=".../logo-header-color.svg" class="header-logo-svg" />
```

soit exactement `body > header > div > span > img` (`.legal-header > .logo-wrapper > .footer-wordmark > .header-logo-svg`).

### CAUSE RACINE du décalage (« alignement à droite en mode RTL ») — PROUVÉE
`assets/css/styles.css:623-633` :

```css
.legal-header { display: flex; align-items: center; justify-content: space-between;
                padding-inline: max(24px, calc((100% - 900px) / 2)); ... }
.legal-header .logo-wrapper { flex: 0 0 auto; }
```

Sur `/merci/`, le header n'a **qu'UN SEUL enfant** : `abaya_upsell_context()` (functions.php:494-512)
exige un slug du registre des offres → `false` sur le slug `merci` (pas de bouton retour, pas de lien légal).
Un item flex unique dans un conteneur `justify-content: space-between` est docké au **début de l'axe
principal** = **bord DROIT en RTL** → décalage latéral. Desktop 1280 px : `padding-inline = 190 px`
→ logo collé à droite à 190 px du bord ; mobile 390 px : padding 15 px + bord droit.

### Pourquoi le CSS prescrit seul (`margin:0 auto` sur l'img) est insuffisant
`.logo-wrapper` est `flex: 0 0 auto` (largeur = contenu) et reste docké à droite : centrer l'img
*DANS* le wrapper ne déplace pas le wrapper. Il faut que le wrapper **occupe la ligne**
(`flex: 1 1 auto`) — son `justify-content: center` natif (styles.css:276 / critical.css:52) centre
alors réellement le logo — puis appliquer la règle prescrite sur l'img.

### Correctif v3.11 (chirurgical, scopé TYP)
Injecté dans le bloc `<style id="abaya-merci">` de `page-merci.php` (chargé UNIQUEMENT sur les TYP
via `wp_head` depuis le gabarit, APRÈS styles.css → cascade gagnante) :

```css
/* v3.11 — MANDAT 4P LP : centrage parfait du logo global de la marque (TYP) */
body > header.legal-header > div.logo-wrapper {
  flex: 1 1 auto !important;            /* occupe la ligne (vainqueur de flex:0 0 auto) */
  justify-content: center !important;   /* centre le mot-mark dans la ligne */
}
body > header.legal-header > div.logo-wrapper > span.footer-wordmark > img.header-logo-svg {
  display: block !important;            /* prescription mandat */
  margin: 0 auto !important;            /* prescription mandat */
}
```

**Effets de bord analysés — zéro interférence** :
- **Pages légales** (about, contact…) : partagent `legal-header.php` mais le style inline
  `abaya-merci` n'existe pas sur ces pages → layout inchangé (logo + `legal-home-link` en
  `space-between` conservés bit-à-bit).
- **LP secondaires** (tunnel avec bouton retour) : même raison → inchangées.
- **LP principale** (`site-header.php`) : non concernée (le sélecteur exige `.legal-header`).
- Logo **non-cliquable conservé** : branche tunnel = `<span>` (jamais `<a>`), aucune retouche PHP du partial.
- `critical.css` ne contient AUCUNE règle `.legal-header` (vérifié) → pas de conflit above-the-fold.

---

## PLAN D'EXÉCUTION v3.11
1. `assets/images/logo.webp` (copie upload) + suppression `merci-logo-shukran.webp`.
2. `page-merci.php` : 3 refs → `logo.webp` + section CSS v3.11 ci-dessus.
3. `style.css` : `Version: 3.11`.
4. Rescopage tests : `validate_offers.py` (S9.1/S11.1/S21.1/S24.1 + **S26 nouveau**),
   `test_merci_canva_visual.py` (C5 → `logo.webp`), `build_merci_test.sh` (copie logo.webp),
   `render_merci.php` (harnais : include du PARTIEL RÉEL `legal-header.php` + stubs
   `abaya_is_landing_page`/`abaya_upsell_context` = markup de production fidèle).
5. **Auto-audit** : `test_logo_v311.py` NOUVEAU — 4 origines × 1280/390, mesure DOM
   (centre img = centre header, Δ ≤ 1 px) + screenshots mobile/desktop + vérif `logo.webp` chargé
   (naturalWidth > 0) + logo non-cliquable + suite statique + régressions complètes.
6. Push branche `fix/logo-header-v311` → merge `main` → déploiement WP Pusher → vérification API GitHub.

---

## DÉFAUT PRÉEXISTANT DÉCOUVERT (HORS PÉRIMÈTRE — NON CORRIGÉ, mandat dédié recommandé)

**Constat** (auto-audit v3.11, harnais rendant désormais le header RÉEL + `body class="legal-page"`
fidèle à la production) : sur mobile 375/390 px, les TYP présentent un débordement horizontal
`scrollWidth = 454` vs `clientWidth = 375` (le contenu décale de 79 px à gauche en RTL).

**Isolation (binaire, mesuré)** :
| Manipulation | scrollWidth |
|---|---|
| Référence (page complète) | 454 |
| Règles v3.11 **neutralisées** (contre-preuve) | **454 — identique** |
| `body.legal-page { display:block }` | 375 ✔ |
| Table de commande `.merci-order table` masquée | 375 ✔ |
| Grille upsell masquée | 454 |

**Cause exacte** : `.merci-order table` (récapitulatif 4 colonnes) a une largeur **min-content
≈ 402 px** ; dans le corps `body.legal-page { display: flex; flex-direction: column }` (header.php,
toute page non-landing — donc /merci/), les items flex ne peuvent passer sous leur min-content →
la table impose 402 + paddings = 454 à toute la chaîne. **Préexistant ≥ v3.6** (classe body +
table présents depuis l'uniformisation), sans aucune relation avec les règles v3.11 (contre-preuve
ci-dessus). La table de commande fait partie de la **structure des prix** — modification FORMELLEMENT
INTERDITE par le présent mandat (règle de non-ingérence §PARTIE 1.3). Recommandation : mandat dédié
(type « min-width:0 / table-layout fixed / wrapping cellules ») après validation de la fondatrice.
