# AUDIT — Calligraphie « شكراً » : format trop réduit → remplacement grand format (v3.12)

**Mandat 4P LP — PHASE 1 (audit et localisation) + PHASE 2 (implémentation, périmètre fermé).**
Périmètre STRICT : `body > main > div.merci-card > header > img.merci-logo` (4 Thank You
Pages = gabarit unique `page-merci.php`). Le logo global de la marque
(`body > header > div > span > img`) ayant déjà été centralisé en v3.11, AUCUN autre
paramètre graphique du tunnel n'est touché (garanties, icônes, avis, footer, prix/devises).

---

## 1. ÉLÉMENT AUDITÉ — `img.merci-logo`

Le gabarit unique `page-merci.php` produit la calligraphie sur les 4 origines TYP
(accueil + produit-1/2/3) via **3 points de sortie** :

| # | Ligne | Rôle |
|---|-------|------|
| 1 | page-merci.php:392 (v3.11) | `<img class="merci-logo">` — vue **commande** (carte principale) |
| 2 | page-merci.php:507 (v3.11) | `<img class="merci-logo">` — vue **générique** (sans clé valide) |
| 3 | page-merci.php:209 (v3.11) | Registre `'logo' => .../logo.webp` → `window.ABAYA_MOCK.logo`, consommé par `merci-review.js:247,267` (vue mock/design injecté) |

Source unique de vérité : le registre PHP. `merci-review.js` ne code aucun chemin en dur
(`config.logo || ""`) — la mise à jour du registre couvre donc automatiquement le chemin mock.

## 2. CONTRAINTES DE TAILLE IDENTIFIÉES (causes du rendu « trop petit »)

| # | Contrainte | Localisation | Effet |
|---|-----------|--------------|-------|
| 1 | Attributs `width="92" height="92"` | page-merci.php:392/507 + merci-review.js:267 | Boîte d'affichage miniature (CLS hint carré) |
| 2 | `.merci-logo { width:92px; height:92px }` | page-merci.php:55 (bloc inline `abaya-merci`, TYP seules) | Plafond desktop 92px |
| 3 | `.merci-logo { width:80px; height:80px }` | page-merci.php:151 (@media ≤520px) | Plafond mobile 80px |
| 4 | **Source 120×67 (paysage) dans boîte CARRÉE 92×92 + `object-fit:contain`** | assets/images/logo.webp (v3.11, 3 038 o) | Letterboxing vertical : rendu effectif **≈ 92×52 px** — miniature |

Constat : le fichier v3.11 (120×67, 4.2× plus petit en pixels que le nouveau visuel) affiché
dans une boîte carrée produisait une calligraphie d'à peine 5 cm de large sur desktop, peu
visible et floue sur écrans haute densité.

## 3. NOUVEAU VISUEL FOURNI

- **Fichier mandat** : « Logo thank you_2.webp » (upload utilisateur) → déployé sous le nom
  repo-safe **`assets/images/logo-thank-you-2.webp`** (slug du nom mandat, cohérence kebab-case
  du dépôt + **cache-bust garanti** : nouvelle URL ≠ `logo.webp` jamais servie par les caches
  navigateur/CDN avec l'ancien contenu).
- **Dimensions** : 500×337 px (ratio 1.484, paysage) — RGBA, **29 742 o**, signature RIFF/WEBP
  validée. Résolution linéaire ×4.2 vs l'ancien fichier : net sur écrans Retina/2× à 240px.

## 4. PLAN D'IMPLÉMENTATION (v3.12)

1. `assets/images/logo-thank-you-2.webp` (copie upload) + SUPPRESSION `assets/images/logo.webp`
   (zéro orphan — seule la calligraphie TYP référençait ce fichier).
2. `page-merci.php` : 3 refs → `/assets/images/logo-thank-you-2.webp` (registre + vue commande +
   vue générique).
3. CSS (bloc inline `abaya-merci`, TYP seules = zéro interférence) :
   `.merci-logo { width: 240px; height: auto; max-width: 82%; object-fit: contain; display: block; margin: 0 auto 4px }`
   + mobile ≤520px `width: 170px; height: auto`.
   — `height: auto` = ratio source 1.484 PRÉSERVÉ (fin du letterboxing carré), zéro distorsion ;
   — `max-width: 82%` = garde-fou anti-débordement (plus petit écran 320px : 170px < 82% de la
   largeur d'en-tête) ;
   — centrage horizontal conservé (`display: block + margin: 0 auto`), en-tête blanc inchangé.
4. Attributs CLS alignés sur le ratio naturel : `width="240" height="162"` (×3 PHP/JS) — le
   navigateur réserve la bonne place avant chargement ; CSS `height:auto` gouverne le rendu final.
5. `merci-review.js` : uniquement les attributs (le src reste `config.logo`, source unique PHP).
6. `style.css` Version 3.11 → **3.12**.

## 5. GARDE-FOUS NON-INTERFÉRENCE

- Les règles `.merci-logo` vivent UNIQUEMENT dans le bloc inline `abaya-merci` de
  `page-merci.php` (chargé sur les TYP seules) — pages légales et LP hors de portée.
- La section v3.11 (centrage logo global wrapper `flex:1 1 auto` + img `display:block;
  margin:0 auto !important`) est INTACTE — vérifiée par les suites rescopées (test_logo_v311
  + S26 + S27.7).
- ZÉRO touch : garanties v3.9, footer mobile v3.8-v3.9, devise RTL v3.10 (bdi dir=rtl +
  NBSP), charte avis v3.7, structure des prix (interdiction formelle).

## 6. VALIDATION

- Statique : `validate_offers.py` rescopée (S9.1/S11.1/S21.1/S24.1/S25.1/S26.1/S26.2) +
  **S27 v3.12** (10 invariants : fichier 500×337 RIFF 29 742 o, zéro orphan, 3 refs, CSS
  240/170/height auto/max-width 82%, attributs CLS ×3, continuité v3.11, audit doc, PROJECT_MAP).
- Visuel : `test_logo_v312.py` NOUVEAU — 4 origines × 1280/390 + vue générique :
  src = logo-thank-you-2.webp, naturalWidth = 500, **largeur rendue MESURÉE ≥ 230px desktop /
  ≥ 160px mobile**, ratio rendu = 1.484 ± 3% (zéro distorsion), centrage Δ ≤ 3px, logo global
  v3.11 toujours centré Δ ≤ 1px, non-cliquable.
- Régressions complètes (13 suites) + auto-audit mobile/desktop avant push `main`.
