# AUDIT — v3.23 « protocole mandat 4P LP » : Upsell unique & réduction à 2 landings

> **Mandat** : le stock ne dispose que d'UN SEUL produit prêt pour l'e-commerce.
> Épure des grilles multi-produits (upsells) vers une version minimaliste à un
> seul produit, et réduction du nombre de landing pages à deux (1 principale +
> 1 secondaire). Tunnel de commande Cash on Delivery INTANGIBLE.
> **Branche** : `feat/single-upsell-cleanup` (depuis main @ v3.22 / 0dae11e).

---

## 1. Périmètre mandaté et décisions

| Exigence mandat | Implémentation v3.23 |
|---|---|
| Réduire le bloc d'offres des LP à **un seul produit** (supprimer cartes 2/3 pièces) | `abaya_base_commerce()` : `bundles => [1 => 299]` + `bundle_labels` réduit à la pièce unique. La boucle du gabarit (`template-parts/landing-offer.php`) rend donc **1 seule carte** — **zéro retouche HTML/PHP du gabarit** (données seules). |
| TYP : **un seul produit d'upsell minimaliste** | Registre réduit à 2 offres → `$abaya_cross_offers` = 1 carte automatiquement. Présentation minimaliste CSS : carte unique **centrée** ≥521px (340px, format 3:5 conservé, scroll-snap neutralisé) ; empilement 100 % ≤520px (verrou v3.19 **intact**). |
| **1 LP principale + 1 LP secondaire** | Registre réduit à 2 entrées (`accueil` + `produit-1`). `produit-2`/`produit-3` retirées → `abaya_retired_offers()` + **redirection 301 permanente** vers la racine dans `abaya_gate_secondary_landing()` (precedence sur le déverrouillage upsell). |
| Tunnel COD **intangible** | Formulaire (nom/téléphone/ville/adresse + piège anti-robot), validation serveur, hash_equals merci, idempotence, anti-flood, CAPI/Pixel : **bit-exacts**. Seule la donnée commerciale (grille autoritaire) change : quantités 2/3 → **422** (l'`isset($cat['bundles'][$quantity])` historique fait foi, aucune ligne de validation modifiée). |

## 2. Cartographie des changements (7 fichiers)

1. **`inc-offers.php`** — grille `[1 => 299]`, 2 offres actives, `abaya_retired_offers()` = `['produit-2', 'produit-3']`, docblock v3.23.
2. **`functions.php`** — gate : `in_array($abaya_slug, abaya_retired_offers(), true)` → `wp_safe_redirect(home_url('/'), 301); exit;` AVANT tout autre traitement. `nocache_headers()` reste ×3 (invariant S13.5).
3. **`template-parts/landing-offer.php`** — **INCHANGÉ** (boucle data-driven ; `is-selected` + `aria-pressed="true"` serveur sur la carte unique).
4. **`assets/css/styles.css`** — section v3.23 FIN de feuille : `.product-landing .bundle-grid .bundle-card:only-child { grid-column: 1 / -1; justify-self: center; width: min(420px, 100%); }` (~420px = largeur d'une carte historique 3-col 1160px ; mobile : min() = 100 %). `:only-child` = retour multi-produits ⇒ rendu historique automatique.
5. **`page-merci.php`** — bloc v3.23 fin du `<style abaya-merci>` : `@media (min-width: 521px) { .merci-upsell-item:only-child { grid-column: 1/-1; justify-self: center; flex: 0 0 auto; width: min(340px,100%); margin-inline: auto; scroll-snap-align: none; } }` (scopé ≥521px : l'empilement v3.19 100 % ≤520px est un verrou intact) + docblock.
6. **`assets/js/app.js`** — replis locaux alignés : `bundlePrices || { 1: 299 }`, `quantityLabels = { 1: "قطعة واحدة" }` (jamais exécutés en production : `window.LANDING.catalog` toujours injecté ; cohérence source unique).
7. **`style.css`** — Version 3.22 → **3.23**.

**Non supprimé volontairement** : `uploads/offers/produit-{2,3}/` (assets orphelins non référencés, aucune URL produite) et les pages WordPress historiques en base (semis one-shot « ne jamais écraser ») — servies par la redirection 301, aucun rendu.

## 3. Conséquences tunnel (validation serveur)

- `create-order` / `add-to-order` : `quantity = max(1, min(3, …))` **inchangé**, puis `isset($cat['bundles'][$quantity])` → qty 2/3 **rejetées 422** `يرجى اختيار العرض المناسب` — le serveur reste la seule autorité prix (299 MAD).
- Offres inconnues (ex. `offer=produit-2`) → 500 `abaya_bad_product` (comportement existant).
- Historique des commandes (qty 2/3 passées) : non affecté.

## 4. Flux dissociés après v3.23

```
LP principale (racine /)  ── commande ──►  /merci/ ── carte upsell UNIQUE ──►  /produit-1/?order&key
LP secondaire (/produit-1/) ── commande ─►  /merci/ ── carte upsell UNIQUE ──►  /?order&key (racine)
/produit-2|produit-3/  ──────────────────►  301 permanent ──►  racine (tout visiteur, duo présent ou non)
2 offres achetées au total (racine + upsell) → section upsell ABSENTE de la TYP
```

## 5. Validation (auto-audit avant fusion)

- **Nouvelle suite `test_single_upsell_v323.py` — 40/40** : LP 360 (principale + secondaire) ×10 checks chacune (1 carte, badge `قطعة واحدة`, 299 درهم, `is-selected`/`aria-pressed`, ruban absent, coche présente, carte centrée, zéro débordement, tunnel COD intact 4 champs + piège + submit, résumé `قطعة واحدة`, titres charte v3.6) ; LP 1280 (carte centrée ~420px, grille 3 pistes conservée, zéro readonly sur la racine publique) ; TYP 390 (1 carte empilée 100 % verrou v3.19, récap/cadeau/WhatsApp intacts) ; TYP 1280 (carte centrée 340px, ratio 3:5, snap `none`) ; TYP « tout acheté » (section absente).
- **Batterie complète `run_regressions_v323.sh` — 27 suites / 1413 PASS / 0 FAIL** (dont statique rescopée 330/330, REST 42/42, tunnel 66/66, lp-variants 73/73, canva 41/41, mobile v317-v319, espacements 92, légal 81+12, logo ×3, devise, modales ×2, avis, badge, popup, shots v35/v36).
- **Rescopage documenté** (`rescope_v323.py` + `rescope_lpv_v323.py`, 130+ remplacements comptés) : pins Version 3.21→3.23 + drift v3.22 absorbé (S14.2 `--check-mark: #e8dcb8`, fin-de-feuille `fin v3.23`), comptes « upsell×3 » → « upsell×1 », monde 3 cartes → assertions d'absence (cartes 2/3, ruban), mock redesign produit-1/299, ORIGINS 2 offres, **et** défaut `THEME` des suites `abaya-repo` (clone v3.21 stale) → clone actif `abaya-landing-offers` — les suites exécutées sans `ABAYA_THEME` testaient silencieusement l'ancien clone.
- **Dérives préexistantes absorbées** : S5.1/S5.5 (×4 offres → ×2), T5.2e libellé v3.6 « العودة إلى صفحة التأكيد » (le check portait le libellé d'avant v3.6).
- `php -l` ×3 + `node --check` app.js/merci-review.js : OK.
- Captures : `download/v323_lp_principale_360.png`, `v323_lp_principale_1280.png`, `v323_merci_390.png`, `v323_merci_1280.png` (cadre image gris = artefact `loading="lazy"` du harnais en capture full-page, fond de repli `#DFE9EA` prévu par la maquette — la géométrie mesurée n'en dépend pas).

## 6. Risques et garde-fous

- **Retour arrière produit** : rétablir `bundles => [1=>299, 2=>499, 3=>699]` (et/ou des entrées d'offres) dans le registre suffit — les règles CSS `:only-child` deviennent inertes et le rendu historique (3 cartes, ruban, carrousel) revient bit-exact ; `abaya_retired_offers()` est la seule liste à vider pour réactiver des slugs.
- **SEO/anciens liens** : 301 permanente vers la racine (cacheable CDN), aucune page vide ni 404.
- **Verrous intacts** : formulaire COD v3.1/v3.6 (pré-remplissage/readonly upsell), sticky v3.5, logo v3.11-13, modales v3.14-16, avis v3.7, footer v3.8/v3.9, devises v3.10, garde-fou v3.18, ergonomie TYP v3.19, légal v3.20, rythme v3.21, accessibilité v3.22.
