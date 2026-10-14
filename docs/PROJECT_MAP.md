# PROJECT MAP — Thème « Abaya Canvas » multi-landing (`abaya-landing-offers`)

> Cartographie du dépôt de travail **`Litbro1517/abaya-landing-offers`** — architecture « N offres (landings) + une offre vedette à la racine » avec cross-sell sur /merci/.
> Socle de référence (INTACT, jamais modifié ici) : **`Litbro1517/abayacollection-theme-clean` @ f25c7049 (v1.27)**.
> Audit préalable complet : **`docs/AUDIT_PHASE0.md`**.

---

## 1. Identité

| Élément | Valeur |
|---|---|
| Thème WordPress | **Abaya Canvas** — Version **2.1** (style.css) |
| Architecture | 1 offre vedette à la racine + 3 landing pages secondaires = 4 landings |
| Gabarit | UNIQUE (`template-parts/landing-offer.php`) — une offre = une entrée de données, PAS un fichier PHP |
| Dépôt source | `abayacollection-theme-clean` (socle : tunnel REST, CAPI/Pixel, tracking) |
| Dépôt cible | `abaya-landing-offers` (branche `feat/multi-landing-offers` → main) |
| Text domain | `abaya-canvas` |

## 2. Registre des offres (`inc-offers.php` — source unique de données)

| Offre | Slug / URL | Produit (constante wp-config) | Assets isolés | Angle éditorial |
|---|---|---|---|---|
| **Vedette** | `accueil` — racine `/` | `LANDING_PRODUCT_ID` (15) | `uploads/` racine (inchangé bit-à-bit) | Offre actuelle (hero Moka) |
| Offre 2 | `produit-1` — `/produit-1/` | `LANDING_PRODUCT_ID_1` | `uploads/offers/produit-1/` | Dupliquée (à personnaliser) |
| Offre 3 | `produit-2` — `/produit-2/` | `LANDING_PRODUCT_ID_2` | `uploads/offers/produit-2/` | Confort quotidien (hero beige) |
| Offre 4 | `produit-3` — `/produit-3/` | `LANDING_PRODUCT_ID_3` | `uploads/offers/produit-3/` | Élégance sorties (hero bordeaux) |

Champs par entrée : `id, name, product_id, sku, layout, assets_dir, colors, sizes, bundles, bundle_labels, images (lcp/lcp_color/sections+alts), texts (hero, bullets, specs, inspection, guide, trust, faq, contact), cross_sell[], upsell` (réservé Phase 5).
Replis : produit inconnu du registre → `abaya_bad_product` (500) ; `LANDING_PRODUCT_ID_N` absent → produit vedette.

## 3. Arborescence

```
├── style.css                     # En-tête thème — Version 2.1
├── functions.php                 # Adaptateur abaya_catalog($offer_id), LCP par offre,
│   │                             # window.LANDING (clés offre = secondaires SEULEMENT),
│   │                             # Pixel/CAPI par offre, REST additive (offer/offer_id),
│   │                             # ensure_pages v2.0 (pages produit-N + template),
│   │                             # template_include (garantie gabarit unique),
│   │                             # badge étoiles SVG (abaya_review_summary/render —
│   │                             # format exact section avis, TOUTES les landings)
├── inc-offers.php                # REGISTRE DES OFFRES (source unique de données)
├── front-page.php                # Racine = offre vedette (ABAYA_FRONT_OFFER) → gabarit
├── page-offer.php                # GABARIT DE PAGE UNIQUE des secondaires (slug → offre)
├── page-merci.php                # Récap sécurisé (hash_equals, 48 h) + pop-up 1-clic
│                                 # + cross-sell (3 cartes) + code promo + config mock
├── merci-review.js               # Pop-up évaluation 1-clic (réel + mock localStorage)
├── header.php / footer.php       # abaya_is_landing_page() remplace is_front_page()
├── template-parts/landing-offer.php  # GABARIT UNIQUE du landing (toutes offres)
├── pages/*.html                  # Graines des pages légales (inchangées, socle)
├── assets/css/{critical,styles}.css  # SOCLE INCHANGÉS bit-à-bit
├── assets/css/offers.css         # Badge étoiles SVG (2 couches, clip --rating-fill)
│                                 # + pop-up + cross-sell (chargée TOUTES landings + merci)
├── assets/js/app.js              # Socle + paramètre `offer` additif (secondaires)
├── assets/js/mock-api.js         # Simulation localStorage (REST 404/50x uniquement)
├── uploads/                      # = EXACTEMENT les 9 fichiers du socle (preuve S9.4)
├── uploads/offers/produit-{1,2,3}/   # Assets ISOLÉS par offre (hero + 7 couleurs + thumbs)
├── preview/                      # Prototype HTML statique FIGÉ (dev uniquement)
└── docs/                         # AUDIT_PHASE0.md, PROJECT_MAP.md, OFFRES.md, RAPPORT_TESTS.md
```

## 4. Règles d'or respectées

1. **Socle intact** : aucun commit sur `abayacollection-theme-clean` ; CSS/JS socle bit-à-bit ; uploads racine = fichiers socle exacts ; tunnel REST (idempotence, anti-flood, prix autoritaire, piège anti-robot, hash_equals merci) inchangé et ADDITIF.
2. **Rendu racine : badge seul ajouté** : PROUVÉ par harnais (diff racine = uniquement le bloc badge étoiles sous le H1, mandat 4P LP v2.1) — tout le reste du rendu du socle vs gabarit unique est inchangé.
3. **Vraies connexions** : tous les formulaires (commande + avis) pointent vers `landing/v1/create-order` et `landing/v1/submit-review` (REST réelles) ; le mock n'agit QUE si le serveur est injoignable (404/50x réseau) — jamais sur une 4xx métier.

## 5. Phase 4 — Page /merci/

- **Pop-up évaluation 1-clic** : overlay discret dès l'arrivée (commande valide uniquement), message arabe du mandat, 5 étoiles SVG dorées (composant avis agrandi), X + Échap + clic voile ; 1 clic = enregistrement direct (route REST, coordonnées de la commande : prénom/ville, commentaire automatique « تقييم بنقرة واحدة »), confirmation « شكراً لك! تم تسجيل تقييمك بنجاح » puis fermeture auto (1,8 s). Modération conservée (avis « en attente » — architecture anti-spam du socle). Non réaffiché dans la session après action (sessionStorage).
- **FIX v2.1 (déclenchement)** : le callback `wp_head` de `page-merci.php` lisait `$abaya_rating_ctx` sans `use`/`$GLOBALS` — une closure PHP n'héritant pas de la portée du gabarit, `ratingCtx` était toujours `null` et le pop-up ne s'ouvrait JAMAIS automatiquement. Correction : lecture via `$GLOBALS['abaya_rating_ctx']`. Prouvé E2E (Playwright 14/14 : auto-ouverture, X, Échap, voile, clic étoile → POST REST → confirmation → fermeture auto, flag session, page générique sans pop-up, mock + Échap).
- **Cross-sell** : toutes les offres du registre SAUF celle de la commande (3 cartes pour un client vedette), visuel = LCP de l'offre, prix « ابتداءً من » = grille de l'offre.
- **Code promo** : `[PRÉNOM]-[VILLE]-[N°COMMANDE]` (méta commande, nettoyage `[^\\p{L}\\p{N}]`), défauts `CLIENTE`/`MAROC` si saisie incomplète — toujours 3 segments.

## 5-bis. Badge étoiles sous le H1 (v2.1 — mandat 4P LP « Ajustement Final & Badge Étoiles »)

- **Format SVG EXACT de la section avis** : même `<path d="m12 2.5 2.9 5.9 …">`, même `viewBox="0 0 24 24"`, classes `.review-star` / `.review-star.is-empty` identiques (byte-exact, vérifié programmatique) — aucun texte ni formulaire de la section avis dupliqué.
- **Garnissage dynamique depuis la BDD** : moyenne des avis « review » approuvés (`abaya_review_summary()`) → 2 couches (grise + dorée clippée à `--rating-fill:%`, ex. 4.9/5 → 98 %) + score chiffré « 4.9 / 5 » + compteur d'avis.
- **Placement** : sous le H1 (entre le titre et `.product-intro`) sur la landing principale ET les 3 secondaires (gabarit unique — une seule modification).
- **Taille** : 30 px desktop / 26 px mobile (scoping `.landing-rating .review-star` surclassant les tailles socle 15/22 px quel que soit l'ordre de chargement des feuilles) ; couleurs doré/gris héritées des règles socle du composant avis.
- **CSS** : `offers.css` désormais chargée sur TOUTES les landings + /merci/ (avant : secondaires + merci seulement).

## 6. Historique

| Version | Contenu |
|---|---|
| 2.0 | Mandat multi-landing : registre 4 offres, gabarit unique, routing, REST additive (`offer`), badge étoiles BDD, pop-up 1-clic merci, cross-sell, code promo, mock dev-only, rebase socle v1.27 (restructuration : prototype → `preview/`, purge résidus), validation 30/30 REST + 59/59 statique + rendu racine bit-à-bit |
| 2.1 | Mandat 4P LP « Ajustement Final & Badge Étoiles » (branche `feat/ratings-popup-adjustments`) : badge étoiles SVG au format EXACT de la section avis sous le H1 sur les 4 landings (avant : secondaires seules, étoiles Unicode), garnissage BDD 2 couches ; FIX P0 pop-up merci (`ratingCtx` closure PHP toujours null → auto-ouverture restaurée) ; correctif Échap mode mock ; offers.css chargée sur toutes les landings ; validation 59/59 statique + 30/30 REST + 14/14 E2E Playwright + 16/16 visuel + SVG byte-exact 10/10 |
