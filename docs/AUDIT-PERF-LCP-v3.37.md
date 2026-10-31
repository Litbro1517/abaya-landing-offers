# AUDIT PERFORMANCE / LCP — v3.37 (branche `opt/performance-audit`)

Mandat 4P LP — exécution sur le dépôt `Litbro1517/abaya-landing-offers` (base main @ v3.35, commit 3db9b8e).
Statut : **implémenté, testé (24/24 + 35/35 + 19/19 + bit-exact), fusionné `main` via WP Pusher.**
Audit d'inspiration : rapport PSI mobile (score 95, LCP 2,6 s, ~1 Mo d'images non redimensionnées) fourni avec le mandat.

## 1. Constats vérifiés sur le code réel (avant correctif)

| # | Constat | Preuve mesurée |
|---|---------|----------------|
| C1 | `wool-3.webp` 823,7 Kio et `wool-2.webp` 659,5 Kio : WebP quasi sans perte, 1080×1350, affichés à ~362 px sur mobile (PSI : 797 Kio d'économie sur wool-3 seul) | PIL : 823,7 / 659,5 / 178,6 / 215,4 / 167,9 Kio ×5 bases |
| C2 | Aucune image responsive : un seul fichier 1080 px pour tous les écrans (0 `srcset`/`sizes` dans `landing-offer.php`) | grep gabarit = 0 occurrence |
| C3 | `offers.css` (11 920 octets) = `<link>` bloquant émis par `wp_enqueue_style` (PSI : ~130 ms) ; `styles.css` déjà asynchrone, `critical.css` déjà inline | functions.php ligne ~1462 |
| C4 | LCP = `#main-photo` (`wool-1.webp`, 178,6 Kio) : déjà `fetchpriority=high` + preload priorité 1, mais sans `imagesrcset` → le navigateur précharge toujours le 1080 px | functions.php prio 1 |
| C5 | Attributs `width/height` 1170×1560 ≠ fichiers réels 1080×1350 ; le CSS `aspect-ratio` (.82/.91/.87 + sections 1/1) gouverne → zéro CLS aujourd'hui | styles.css + PIL |
| C6 | Points déjà sains : polices auto-hébergées + preload, CSS WP/Woo retirés (dequeue prio 100), JS en module, cache-busting filemtime | functions.php |

## 2. Correctifs appliqués (commit `perf(v3.37)`)

1. **Recompression** ciblée des bases quasi sans perte (1080×1350 conservés) : wool-2 659,5→**82,0 Kio** (q80, PSNR **40,5 dB**) ; wool-3 823,7→**78,0 Kio** (q80, PSNR **41,2 dB**). wool-1/4/5 intacts bit-à-bit.
2. **Variantes responsives** `wool-N-480.webp` (480×600) et `wool-N-720.webp` (720×900) pour N=1..5 — PSNR **≥ 40,1 dB** mesuré contre l'original redimensionné LANCZOS (métrique composité blanc : wool-1/2/5 sont RGBA détourés, les RGB des pixels transparents sont indéterminés). Alpha préservé (WebP alpha lossless).
3. **`srcset`+`sizes`** sur le Hero (#main-photo) et les 3 visuels de sections via `abaya_img_srcset($rel)` + `abaya_img_sizes($kind)` (functions.php) — émission **conditionnelle** : aucune variante ⇒ attribut absent ⇒ landings secondaires (produit-1) bit-exactes. Descripteur de la base = largeur intrinsèque réelle via `getimagesize` (jamais déduite du nom). `sizes` calculés d'après styles.css : Hero ≤540 `calc(100vw - 30px)`, ≤800 `min(calc(100vw - 38px), 560px)`, desktop 560px ; sections idem ≤700 puis 270px (701-800) et 561px desktop.
4. **Preload LCP** avec `imagesrcset`/`imagesizes` (mêmes valeurs que la balise `<img>`) → le navigateur précharge directement le candidat 720 px sur mobile (slot ≈330 px × DPR 1,75 ≈ 577 px) au lieu du 1080 px.
5. **Galerie** : `app.js` (`updatePhoto`) pose le `srcset` de la NOUVELLE couleur **avant** `src` (sinon le srcset de l'ancienne couleur primerait et l'ancienne image resterait affichée) + préchargement du même candidat que l'affichage (`sizes` miroir de #main-photo, une seule image décodée) ; `data-srcset` émis sur les vignettes du gabarit ; `colorImages` transporte `srcset`.
6. **`offers.css` inline** sur les landings (racine + secondaires) au **même rang de cascade** (`wp_head` priorité 8 == `wp_print_styles`) ; la `<link>` historique est conservée sur `/merci/` (pas une landing).

## 3. Gains mesurés (fichiers réels du dépôt)

| Image | Avant (Kio) | Après (Kio) | Candidat mobile 360×DPR1,75 (720w) |
|-------|-------------|-------------|-------------------------------------|
| wool-1 (LCP) | 178,6 | 178,6 (base inchangée) | **72,7** (−59 %) |
| wool-2 (swap couleur) | 659,5 | **82,0** (base) | **55,7** (−92 %) |
| wool-3 | 823,7 | **78,0** (base) | **57,3** (−93 %) |
| wool-4 | 215,4 | 215,4 (base inchangée) | **96,5** (−55 %) |
| wool-5 | 167,9 | 167,9 (base inchangée) | **68,1** (−59 %) |
| **Total hero + 3 sections (vue mobile initiale)** | **1 385,6** | **294,6** | **−78,7 %** |

Desktop DPR1 : mêmes 720w (slot 560) ; desktop DPR2 : bases 1080w recomprimées → 639,9 Kio (−54 %) + wool-2 swap 82,0 Kio.
Le LCP devrait baisser nettement (transfert du Hero ÷2,5 ; contention de bande passante réduite avec les sections lazy allégées ~4×) — le PSI simule un throttle sévère et le TTFB du serveur compte aussi : re-mesurer sur pagespeed.web.dev après déploiement.

## 4. Validation (zéro régression prouvée)

- `php -l` ×3 (functions.php, landing-offer.php, inc-offers.php) + `node --check` app.js — 0 erreur.
- Émissions `<head>` sans WordPress (stubs déterministes, scripts/test_head_v337.php) : **19/19 PASS** — vedette : preload `imagesrcset` 3 candidats + `imagesizes` hero + inline offers.css octet-à-octet à la priorité 8 ; produit-1 : preload SANS imagesrcset (inchangé) ; merci : preload absent + `<link>` historique conservée.
- Harnais déterministe : **produit-1 BIT-EXACT 0 octet** (main v3.35 vs branche v3.37) ; diff vedette = **exactement les 6 lignes d'images** (hero, 2 vignettes data-srcset, 3 sections).
- Playwright `test_perf_v337.py` : **24/24 PASS** — markup A1-A5, HTTP 200 ×17 (5 bases + 10 variantes + 2 vignettes), sélection responsive `currentSrc` ×4 DPR (1280×1 → 720w, 1280×2 → 1080w, 360×1 → 480w, 360×1,75 → 720w), swap galerie srcset-avant-src ×5, offre 2 multi-picker → galerie ×2, produit-1 sans srcset ×3, mobile 360 overflow 0, **géométrie de layout == référence v3.35** @1280 et @360 (7 blocs mesurés).
- Régression v3.35 rejouée sur le harnais v3.37 (`test_validation_v335.py`) : **35/35 PASS** — prix ×10, sous-titre ×4, validation « Shake & Red Border » desktop ×16, régression picker, mobile 360 ×5. **Design, ergonomie (validation formulaires, sélecteur multi-pièces) et tracking INTACTS.**

## 5. Non réalisé / recommandations suivantes (hors périmètre v3.37)

- Re-mesure PSI à exécuter sur l'URL réelle après purge des caches (le simulateur throttle fortement ; TTFB et délai de rendu comptent aussi) — atteindre < 1,5 s et 100/100 n'est pas garanti.
- AVIF via `<picture>` (−20-30 % vs WebP) — nécessite d'adapter le swap galerie.
- Corriger les attributs `width/height` intrinsèques (1080×1350) — sans effet CLS (aspect-ratio CSS gouverne), laissé volontairement hors périmètre pour un diff minimal.
- Purge/minification de `styles.css` (118,8 Kio non minifié) au build.
- Accessibilité 92 : hors périmètre de ce mandat.
