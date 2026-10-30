# AUDIT — MAPPING DES 5 IMAGES WEBP DÉFINITIVES « ABAYA LAINE » (v3.34)

**Mandat 4P LP** — « Intégration et mapping des nouvelles images (au format WebP / optimisées) selon la structure validée, sans perturber le design global de la landing page ».
**Branche** : `feat/wool-abaya-product-adaptation` (branche active, resynchronisée — v3.33 en place).
**Périmètre autorisé** : les emplacements images existants UNIQUEMENT — structure et emplacements visuels strictement inchangés.

---

## 1. Audit préalable (état v3.33)

- Les 5 fichiers du mandat sont présents dans les uploads de la conversation et **vérifiés WebP** (magic bytes `RIFF….WEBP`) — tous **1080×1350 natifs (4:5)** :
  `1 (6).webp` 178,6 KB · `2 (3).webp` 659,5 KB · `6 (1).webp` 167,9 KB · `7.webp` 215,4 KB · `8.webp` 823,7 KB.
- **Nomenclature du mandat vs fichiers réellement fournis** : la matrice cite « 2 (3).**jpg** » et « 8.**jpg** » ; les fichiers livrés sont `2 (3).webp` et `8.webp` (formats WebP — conformes à l'intitulé « au format WebP »). Le mapping utilise les fichiers fournis.
- Emplacements existants (aucun cadre à créer) :
  - **Hero (Sélection des couleurs)** = galerie : photo principale (`#main-photo`, LCP via `abaya_lcp_image_src()`) + 2 vignettes par couleur (palette vedette : بني كريمي / أسود — « دائرتان ») ;
  - **3 sections de contenu** à cadre (`figure.visual-media`, boîte `aspect-ratio: 1/1`, `object-fit: cover`) : 1ʳᵉ `visual-section-colors` (« لماذا هذه العباية هي الخيار المثالي؟ »), 2ᵉ `visual-section-specs` (« معلومات إضافية وتفاصيل عن المنتج »), 3ᵉ `visual-section-inspection` (« معاينة الطلبية والحق في الفحص ») — données `$I['colors'/'specs'/'inspection']['src']`.
- Références des JPG provisoires v3.30 (`wool-1.jpg`/`wool-2.jpg`) : **uniquement** dans `inc-offers.php` (données) — zéro référence JS/CSS/functions (le preload LCP lit la même clé `lcp`).
- Cadres CSS : `.main-photo-wrap` (aspect-ratio, fond #ece8df) et `.visual-media` (1:1) **ne dépendent pas des attributs width/height** du gabarit (`width/height 100% + object-fit: cover`) → gabarit PHP **INTACT** (attributs 1170×1560 conservés — constante partagée : produit-1 reste bit-exact).

## 2. Mapping exécuté (copies BIT-EXACTES — zéro réencodage, dimensions natives)

| Emplacement (mandat) | Fichier mandat | Fichier déployé | Rôle |
|---|---|---|---|
| **Hero — image principale** | `1 (6).webp` | `uploads/wool-1.webp` | LCP + image بني كريمي (vignette 1, courante) |
| **Hero — seconde image (variante de couleur)** | `2 (3).webp` *(cité « 2 (3).jpg »)* | `uploads/wool-2.webp` | image الأسود (vignette 2, bascule galerie) |
| **1ʳᵉ section de contenu** (manches) | `8.webp` *(cité « 8.jpg »)* | `uploads/wool-3.webp` | cadre `visual-section-colors` |
| **2ᵉ section de contenu** | `7.webp` | `uploads/wool-4.webp` | cadre `visual-section-specs` |
| **3ᵉ section de contenu** | `6 (1).webp` | `uploads/wool-5.webp` | cadre `visual-section-inspection` |

- **Vignettes galerie** : régénérées **151×170** (format du système) depuis wool-1/wool-2.webp — recadrage cover, ancre verticale 30 % (même intention que `.thumbnail img { object-position: center 30% }`) — 6,3 / 5,3 KB.
- **Retrait des JPG provisoires v3.30** devenus non référencés (précédent v3.28) : `uploads/wool-1.jpg`, `uploads/wool-2.jpg`, `uploads/thumbs/wool-1.jpg`, `uploads/thumbs/wool-2.jpg` → uploads vedette = `favicon + wool-1..5.webp + thumbs/wool-1|2.webp`.
- **`inc-offers.php`** : palette (chemins `image`/`thumb`) + bloc `images` (lcp/lcp_color/lcp_alt/colors/specs/inspection) re-câblés ; alts des 3 sections de contenu actualisés **sans mention de couleur** (contenu visuel non contractuel — seul `8.webp` est décrit factuellement « إبراز الأكمام الواسعة » comme le mandat : « mettant en valeur les manches ») ; `lcp_alt` conservé (rattaché à la teinte بني كريمي du sélecteur, inchangée) ; commentaires documentaires v3.34 (fin du signalement « JPG provisoires »).
- **Contenu visuel vérifié** : `1 (6).webp` = modèle pied-de-poule (poches avant, manches larges) — même produit que le LCP v3.30 en haute résolution ; `2 (3).webp` = même modèle ton plus clair (variante) ; `8.webp` = bras levé mettant en valeur la manche ; `7.webp` = deux modèles de face ; `6 (1).webp` = gros plan inspection.

## 3. Intangibilité prouvée

- **produit-1 : BIT-EXACT 0 octet** (harnais déterministe main v3.33 vs branche — images `offers/produit-1/*` hors de portée).
- **Diff vedette = chemins d'images UNIQUEMENT** (preload/main-photo/vignettes/data-src/3 figures) — texte, prix, offres, sélecteur, formulaire, tunnel, TYP, logo, back-link : ZÉRO diff.
- **Gabarit PHP : ZÉRO ligne modifiée** (les cadres CSS gouvernent seuls l'affichage — attributs intrinsèques inchangés).
- **ZÉRO règle CSS modifiée** (style.css : bump de version 3.33 → 3.34 uniquement).

## 4. Validation (`test_images_v334.py` — 13 PASS / 0 FAIL)

- **A. Hero** : photo principale = `wool-1.webp` (LCP) ; exactement 2 vignettes (« دائرتان ») ; vignette 1 بني كريمي → `thumbs/wool-1.webp` courante au chargement ; **seconde image Hero / variante** الأسود → `wool-2.webp`.
- **B. Sections PAR ORDRE DE PRIORITÉ** : 1ʳᵉ = `wool-3.webp` (alt manches), 2ᵉ = `wool-4.webp`, 3ᵉ = `wool-5.webp`.
- **C. Servabilité** : 5 images + 2 vignettes = **HTTP 200 ×7**.
- **D. Fonctionnement galerie intouché** : clic الأسود → bascule sur `wool-2.webp`, retour بني كريمي → `wool-1.webp`.
- **E. Cadres design inchangés** : wrap aspect-ratio 1/1 @1280 (== baseline v3.33), fond #ece8df, media 1:1, `object-fit: cover`.
- **F. Mobile 360** : Hero = `wool-1.webp`, zéro débordement horizontal.
- **G. produit-1** : images inchangées (`hero.jpg`), aucun `wool-`.
- **Lint** : `php -l inc-offers.php` — 0 erreur. **Captures** : `scripts/shots_v334/` (hero 1280, sections ×3 + composite, hero 360, produit-1 1280).

## 5. Conclusion

Les **5 images WebP définitives** sont positionnées aux emplacements exacts du mandat (2 Hero + 3 sections de contenu en ordre de priorité), en copies bit-exactes 1080×1350, **sans aucune retouche** du gabarit, des cadres CSS, du design global ni de produit-1. Les JPG provisoires v3.30 sont retirés (fin du signalement v3.30).
