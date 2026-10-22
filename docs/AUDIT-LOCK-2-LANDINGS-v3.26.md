# AUDIT — VERROUILLAGE ARCHITECTURE 2 LANDINGS (v3.26)

**Mandat 4P LP « protocole mandat 4P LP »** — *« Restructurer définitivement le site pour qu'il contienne strictement et uniquement : 1 Landing Page Principale + 1 Landing Page Secondaire (avec 1 seul produit d'upsell minimaliste). Stabiliser définitivement la grille d'offres de la landing page sans casser l'architecture globale. »*

- **Branche** : `fix/lock-2-landings-v326` (depuis `main` @ `a2d1f27` = v3.25)
- **Périmètre modifié** : EXACTEMENT 4 fichiers — `inc-offers.php`, `functions.php`, `page-merci.php`, `style.css` (prouvé par `git diff --name-only`, check A15)
- **Hors touch (bit-exacts)** : `template-parts/landing-offer.php`, `assets/css/styles.css`, `assets/css/offers.css`, `assets/css/critical.css`, `assets/js/app.js`, `front-page.php`, `page-offer.php`, `header.php`, `footer.php`, `template-parts/*`, `inc-pages.php`, `merci-review.js`, `mock-api.js`, `pages/*`, `uploads/*`

---

## 1. Conjoncture & diagnostic (audit préalable)

La capture d'écran fournie (« تشكيلة أخرى قد تعجبكِ أيضاً » — **3 abayas à 299 د.م.** avec boutons « إضافة للطاب ») montrait la régression structurelle : au lieu de la règle stricte **1 LP principale + 1 LP secondaire (upsell mono-produit minimaliste)**, le site servait 4 landings et une TYP multi-produits.

**Origine exacte (git log/diff)** : le commit `208d3ba` (v3.25, *réparation d'urgence* de la grille des offres) avait exécuté un **revert bit-exact vers l'état v3.22 intégral** — restaurant la grille des 3 offres (validé) MAIS réintroduisant dans le même mouvement :
1. les landings excédentaires `produit-2` / `produit-3` dans le registre `inc-offers.php` (fin de `abaya_retired_offers()`, fin des redirections 301) ;
2. le TYP multi-upsell « offres restantes » = registre SAUF commande = **3 cartes** (le bloc exact de la capture : `page-merci.php` lignes 502-512 + 637-665).

Le travail v3.24 (back-link « ‹ رجوع ») était resté intact et demeure un **actif protégé**.

## 2. Méthode chirurgicale (extraction Git, zéro improvisation)

Extraction des **composants d'ARCHITECTURE uniquement** du commit v3.23 `76444df` (l'audit avait déjà établi que la mutilation v3.23 était data-driven) — avec une différence majeure et assumée : **la grille commerciale est restée STABILISÉE** (bundles `[1 => 299, 2 => 499, 3 => 699]` + labels ×3, restaurés par v3.25 et validés — JAMAIS re-réduits).

| Fichier | Changement (v3.26) |
|---|---|
| `inc-offers.php` | Entrées `produit-2`/`produit-3` RETIRÉES du registre ; `abaya_retired_offers() = ['produit-2','produit-3']` réintroduite ; docblock d'architecture. **bundles ×3 INTACTS** (diff vs `76444df` = wording seul + grille ×3). |
| `functions.php` | `abaya_gate_secondary_landing()` : redirection **301 PERMANENTE** vers la racine pour tout slug retiré, AVANT tout autre traitement (precedence sur le déverrouillage upsell ; gate 302 historique inchangé) — code bit-identique à v3.23 (wording v3.26). |
| `page-merci.php` | Docblock + commentaires alignés (1 upsell minimaliste) ; ré-ajout du bloc CSS `@media (min-width:521px) .merci-upsell-item:only-child` (carte unique centrée 340px, snap none) — le registre à 2 offres rend automatiquement 1 seule carte (`$abaya_cross_offers`) ; verrou v3.19 (empilement 100% ≤520px) intact. |
| `style.css` | Version 3.25 → **3.26**. |

Effet automatique data-driven : registre = **2 offres** (`accueil` vedette + `produit-1` secondaire) → TYP = **1 carte upsell** (la restante, exclusions d'achats inchangées) ; `/produit-2/` et `/produit-3/` → **301 racine** (pages WP historiques jamais servies — semis « ne jamais écraser » respecté, anciens liens publicitaires conservés).

## 3. Preuves de non-régression (actifs protégés)

- **Back-link « ‹ رجوع » (v3.24)** : `test_back_link_v324.py` → **86/86 PASS** (markup ×2 legal-header + barre racine, pilule `inline-flex/nowrap/radius 50px/padding 6px 14px`, une seule ligne, miroir bidi ‹, destinations upsell→TYP / réglementaire→Accueil, logo 40px). Rescopage documenté d'UN pin de version (`3.24|3.25` → `3.24|3.25|3.26` — bump mandat, zéro changement back-link).
- **Pages réglementaires (v3.20)** : `test_legal_v320.py` → **81/81 PASS**. **Avis (v3.7)** : **64/64 PASS**. **Badge étoiles** : **16/16 PASS**.
- **Grille d'offres stabilisée** : 3 cartes `.bundle-card` desktop même rangée + mobile 360 grille 3 colonnes fixes v3.17, clic ×3 → `.is-selected` + contour émeraude `rgb(58,125,132)` + aria-pressed + récap 299/499/699 + scroll `#order-form-section` (LP vedette ET landing secondaire produit-1).
- **Tunnel COD intangible** : aucune modification serveur (validation/hash_equals/idempotence/anti-flood/CAPI bit-exacts) — le serveur reste la seule autorité prix (qty 2 → 499, qty 3 → 699).
- **Lint** : `php -l` ×3 OK ; `git diff --name-only` = les 4 fichiers du périmètre uniquement.

## 4. Validation fonctionnelle — `test_lock_v326.py` : 64/64 PASS

| Bloc | Contrôles |
|---|---|
| A — Statique (15) | registre ×3 stabilisé, entrées retirées, `abaya_retired_offers()`, gate 301 présent **avant** le 302, 302 intact, `:only-child` TYP v3.26 présent, verrou v3.19 intact, styles.css SANS `:only-child` (grille historique), pilule v3.24 préservée, replis app.js ×3, Version 3.26, back-link statique, périmètre git = 4 fichiers |
| B — Gate exécuté (8) | registre vivant `keys=[accueil, produit-1]` + `retired=[produit-2, produit-3]` + bundles 299/499/699 ; **`/produit-2/` → 301** `/` ; **`/produit-3/` → 301** `/` ; `/produit-1/` → **302** (gate historique) ; racine → aucune redirection ; `/merci/` → aucune redirection |
| C — LP vedette 1280 (19) | 3 cartes même rangée, largeurs ~1/3, labels ×3, prix 299/499/699, aria serveur, clic ×3 (sélection + émeraude + récap + scroll) |
| D — LP vedette 360 (5) | 3 cartes, zéro débordement, grille 3 colonnes fixes, récap 499, scroll formulaire |
| E — LP produit-1 1280 (3) | 3 cartes même rangée, sélection offre 3 → 699 |
| F — TYP (16) | **EXACTEMENT 1 carte** (correctif capture) ; lien racine + duo order+key + `#order-form-section` ; prix 299 ; tout-acheté → section ABSENTE ; commande accueil → carte produit-1 ; isolation tunnel (zéro lien retour sur /merci/) ; logo intact ; desktop 1280 carte **centrée ±3px / 340px** ; mobile 390 largeur 100% (v3.19) ; zéro débordement ; titre « تشكيلة أخرى قد تعجبكِ أيضاً » inchangé |

## 5. Preuves visuelles (`download/v326_*`)

| Fichier | Preuve |
|---|---|
| `v326_lp_racine_1280.png` / `v326_lp_racine_360.png` | Grille des 3 offres stabilisée (desktop + mobile) |
| `v326_produit1_1280.png` | Landing secondaire active : grille commune |
| `v326_typ_1280_apres.png` | **Le correctif de la capture** : 1 carte upsell centrée |
| `v326_typ_390_apres.png` | TYP mobile : empilement v3.19 |

NB harnais : les cadres d'image paraissent vides sur les captures (assets `uploads/` non copiés dans l'environnement de test local — artefact de harnais uniquement, images servies normalement en production).

## 6. Déploiement

Merge → `main` + push (WP Pusher auto-déployé depuis GitHub) — vérification en ligne par `scripts/verify_deploy_v326.py` (sonde raw.githubusercontent = source WP Pusher, exécutée hors dépôt après push). Résultat consigné dans le rapport de mission (worklog + synthèse finale).
