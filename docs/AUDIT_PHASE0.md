# AUDIT PHASE 0 — Mandat multi-landing (rapport, zéro code)

> Périmètre : socle `abayacollection-theme-clean` @ f25c7049 (v1.27) — référence INTACTE.
> Objectif : cartographier les points de rupture avant le passage « une landing à la racine » → « N offres + vedette à la racine ».

## 1. Usages de `is_front_page()` (7 occurrences)

| Fichier:l. | Usage | Impact si page non-front | Traitement (Phase 1) |
|---|---|---|---|
| header.php:13 | body class `product-landing`/`legal-page` | Les offres secondaires hériteraient `legal-page` (CSS cassé) | → `abaya_is_landing_page()` |
| header.php:15 | template-part site/legal header | Header légal sur une landing = incohérence visuelle | → `abaya_is_landing_page()` |
| footer.php:2 | footer landing vs légal | Idem | → `abaya_is_landing_page()` |
| functions.php:27 | titre document = `Abaya Collection` | Offres avec le titre de la racine (SEO) | → `document_title` du registre (vedette = valeur identique) |
| functions.php:37 | méta description hero | Offres avec la description de la vedette | → `meta_description` du registre (vedette = valeur identique) |
| functions.php:165 (hook p1) | preload LCP + polices critiques | ZÉRO preload sur les secondaires (régression LCP) | → `abaya_is_landing_page()` + LCP par offre |
| functions.php:202 (hook p5) | window.LANDING + app.js + Pixel | Aucun formulaire fonctionnel ni Pixel sur les secondaires | → `abaya_is_landing_page()` + config conditionnelle |

## 2. Usages de `LANDING_PRODUCT_ID` (7 occurrences)

| Fichier:l. | Rôle | Traitement |
|---|---|---|
| functions.php:10,20 | constante wp-config (défaut 15) | Conservée — produit de la VENDETTE (repli offre n°1) |
| functions.php:231 (Pixel ViewContent) | `content_ids:[15]`, `value:299` en dur | → produit/valeur DU REGISTRE (vedette = sortie identique) |
| functions.php:277 (CAPI) | `content_ids:[LANDING_PRODUCT_ID]` | → `$order['product_id']` (additif, vedette = valeur identique) |
| functions.php:424 (target avis) | repli produit pour avis | Conservé tel quel (cible globale des avis) |
| functions.php:527 (create-order) | produit de la commande | → résolution par offre (`product_id` registre, repli vedette) |

## 3. Usages de `abaya_catalog()` (9 occurrences)

front-page.php:7 (rendu), functions.php:125 (définition), 207 (window.LANDING), 576 (whitelist REST), 590-597 (prix autoritaire), page-merci.php:104 (visuel couleur), app.js:11,21 (docs). **Traitement** : `abaya_catalog($offer_id = null)` devient un adaptateur vers le registre `abaya_offers()` — null = offre courante ; la vedette renvoie EXACTEMENT le tableau du socle (diff racine = zéro, prouvé par harnais).

## 4. Valeurs en dur

| Valeur | Localisation | Traitement |
|---|---|---|
| `15` | produit par défaut | Constante conservée (repli vedette) |
| `299` | Pixel ViewContent `value` | → `(int) $cat['bundles'][1]` de l'offre (vedette = 299 identique) |
| `349` (prix barré) | front-page.php:49 | → `texts.old_price` du registre |
| `content_ids` | Pixel l.231 + CAPI l.277 | Par offre (vedette = valeurs identiques) |
| `212698738664` (WhatsApp) | front-page.php + page-merci.php | Conservé (structurel, identique toutes offres) |

## 5. Hooks « front uniquement » (p1/p5)

- **Priorité 1** : preload image LCP + 2 polices woff2 — gardé `is_front_page()` → `abaya_is_landing_page()`.
- **Priorité 5** : favicon, critical.css inline, `window.LANDING`, enqueue app.js, Meta Pixel — idem. Sur la VENDETTE, `window.LANDING` et le Pixel restent **octet-pour-octet identiques au socle** (clés offre injectées uniquement sur les secondaires).

## 6. Ce qui casserait sur une page non-front (confirmé par l'audit)

1. body class `legal-page` → CSS landing non appliqué (formulaire, swatches, bundles).
2. header/footer légaux (logo gris, pas de bandeau) sur une page de vente.
3. Zéro `window.LANDING` → app.js plante au premier rendu du prix (`LANDING.endpoint` indéfini).
4. Zéro Pixel/CAPI → perte de tracking ViewContent/Purchase sur les offres secondaires.
5. Zéro preload LCP → dégradation LCP des secondaires.
6. create-order utilisait le produit unique → commandes non attribuables par offre.

## 7. Traitement appliqué (résumé Phase 1-4)

Registre `inc-offers.php` (4 entrées : vedette `accueil` + `produit-1/2/3`) ; gabarit unique `template-parts/landing-offer.php` + `page-offer.php` (aucun PHP par offre) ; routing = pages WP créées par `abaya_ensure_pages()` (version 2.0) + filtre `template_include` garanti ; REST additive (`offer`/`offer_id`/absent=vedette) ; badge étoiles BDD sur secondaires ; `/merci/` = pop-up 1-clic + cross-sell + code promo ; mock `localStorage` dev-only.

## 8. État du dépôt cible AVANT intervention

Prototype HTML statique à la racine (index/produit-N/merci, duplications interdites par le mandat) + thème WordPress niché dans `uploads/` (basé sur l'ANCIENNE lignée 8 couleurs — aurait fait régresser la racine) + résidus (`*-2.html`, `app-2.js`, styles dupliqués, anciennes images 8 couleurs). **Rebase effectué** : socle v1.27 à la racine, architecture offres rebasée, prototype figé dans `preview/`, résidus purgés (preuve : uploads racine = exactement les 9 fichiers du socle).
