# PROJECT MAP — Thème « Abaya Canvas » multi-landing (`abaya-landing-offers`)

> Cartographie du dépôt de travail **`Litbro1517/abaya-landing-offers`** — architecture « N offres (landings) + une offre vedette à la racine » avec cross-sell sur /merci/.
> Socle de référence (INTACT, jamais modifié ici) : **`Litbro1517/abayacollection-theme-clean` @ f25c7049 (v1.27)**.
> Audit préalable complet : **`docs/AUDIT_PHASE0.md`**.

---

## 1. Identité

| Élément | Valeur |
|---|---|
| Thème WordPress | **Abaya Canvas** — Version **2.2** (style.css) |
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
├── style.css                     # En-tête thème — Version 2.2
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
├── page-merci.php                # Récap sécurisé (hash_equals, 48 h) — DESIGN ÉPURÉ v2.2
│                                 # (en-tête fin, tableau 2 colonnes, grille client
│                                 # compacte, upsell sans cadres, code promo fin)
│                                 # + pop-up 1-clic persistant + config mock
├── merci-review.js               # Pop-up évaluation 1-clic perfectionné (remplissage
│                                 # progressif RTL, persistance + texte optionnel,
│                                 # fermeture X/valider/délai 30 s) — réel + mock
├── header.php / footer.php       # abaya_is_landing_page() remplace is_front_page()
├── template-parts/landing-offer.php  # GABARIT UNIQUE du landing (toutes offres)
├── pages/*.html                  # Graines des pages légales (inchangées, socle)
├── assets/css/{critical,styles}.css  # SOCLE INCHANGÉS bit-à-bit
├── assets/css/offers.css         # Badge étoiles SVG (2 couches, clip --rating-fill)
│                                 # + pop-up (étoiles nues, is-hot/is-picked) — chargée
│                                 # TOUTES landings + merci ; la mise en page merci
│                                 # vit dans le style inline du gabarit (v2.2)
├── assets/js/app.js              # Socle + paramètre `offer` additif (secondaires)
├── assets/js/mock-api.js         # Simulation localStorage (REST 404/50x uniquement)
│                                 # + miroir règle max / modération (v2.2)
├── uploads/                      # = EXACTEMENT les 9 fichiers du socle (preuve S9.4)
├── uploads/offers/produit-{1,2,3}/   # Assets ISOLÉS par offre (hero + 7 couleurs + thumbs)
├── preview/                      # Prototype HTML statique FIGÉ (dev uniquement)
└── docs/                         # AUDIT_PHASE0.md, PROJECT_MAP.md, OFFRES.md, RAPPORT_TESTS.md
```

## 4. Règles d'or respectées

1. **Socle intact** : aucun commit sur `abayacollection-theme-clean` ; CSS/JS socle bit-à-bit ; uploads racine = fichiers socle exacts ; tunnel REST (idempotence, anti-flood, prix autoritaire, piège anti-robot, hash_equals merci) inchangé et ADDITIF.
2. **Rendu racine : badge seul ajouté** : PROUVÉ par harnais (diff racine = uniquement le bloc badge étoiles sous le H1, mandat 4P LP v2.1) — tout le reste du rendu du socle vs gabarit unique est inchangé.
3. **Vraies connexions** : tous les formulaires (commande + avis) pointent vers `landing/v1/create-order` et `landing/v1/submit-review` (REST réelles) ; le mock n'agit QUE si le serveur est injoignable (404/50x réseau) — jamais sur une 4xx métier.

## 5. Page /merci/ — Refonte UI/UX v2.2 (mandat 4P LP « Thank You Page Redesign »)

- **Design épuré & aéré** : fin des gros blocs massifs (check-circle 56 px, liste 3 étapes, boîte promo dashed, bouton WhatsApp bulle) — typographie fine `Cairo / Arial / System Sans-Serif`, conteneur 640 px, informations de confirmation purement informatives et restreintes. En-tête : petit check filigrane + titre chaleureux personnalisé + sous-texte discret (confirmation téléphonique) + numéro de commande en méta grise.
- **Tableau récapitulatif à 2 colonnes** (`المنتج | السعر`) : bordures inférieures fines 1 px `#f0f0f0`, miniature de la couleur choisie intégrée (58×72), nom du modèle × quantité, options اللون/المقاس en méta fine, prix unitaire dérivé (`total/qty`, affiché si qty > 1) + total de ligne ; rangées de pied `المجموع` + `طريقة الدفع`. Source : `$order->get_items()` (générique multi-lignes).
- **Informations client** : grille compacte à 2 colonnes (`الهاتف، المدينة، العنوان، طريقة الدفع، الحالة`) SANS boîtes — séparateur fin supérieur, libellés gris 0.8rem. Lien WhatsApp réduit à un lien texte souligné discret.
- **Code promo** : conservé (actif marketing) en présentation fine — fond neutre `#faf8f4` sans bordure, code en DM Sans 700, une ligne de description. Format inchangé `[PRÉNOM]-[VILLE]-[N°COMMANDE]` (nettoyage `[^\p{L}\p{N}]`, défauts `CLIENTE`/`MAROC`).
- **Section upsell SANS cadres** : accroche sobre « اكتشفي عروضاً أخرى قد تناسبك » au-dessus, grille 3 colonnes (2 en mobile) de visuels produits LIBRES (fond neutre, radius 10 px, zéro carte/bordure), titre fin clampé 2 lignes + prix « ابتداءً من » + bouton d'achat direct discret pill « اطلبي الآن » (hover inversé) — lien vers `{landing}/#order-form-section` (achat direct). Offres = registre SAUF celle commandée (inchangé).
- **Pop-up d'évaluation 1-clic perfectionné** (merci-review.js) :
  - **Remplissage progressif RTL** : survol ou focus sur la N-ième étoile → étoiles 1..N allumées simultanément (classes `.is-hot` posées en JS, sens RTL respecté) ; étoiles NUES (fini les boîtes isolées), 48 px, dorées `#c49b55` ;
  - **Persistance (mandat)** : le clic enregistre DIRECTEMENT la note (REST) puis le pop-up RESTE OUVERT — message « شكراً جزيلًا على تقييمك! 🌿 هل ترغبين في إضافة كلمة بسيطة عن تجربتك؟ » + zone de texte optionnelle (apparition douce animée) ;
  - **Fermeture** : croix (X), bouton valider (envoi du texte ; sans texte = fermeture gracieuse), touche Échap, clic voile, ou **délai prolongé 30 s** réinitialisé à chaque saisie (2,6 s après envoi du texte) ;
  - **Règle du maximum** : le serveur renvoie `rating = max(note_existante, nouvelle_note)` — l'affichage des étoiles s'aligne sur la valeur conservée (l'avis existant est retrouvé par la méta `review_order_id`) ;
  - **Modération intelligente** : étoiles seules + contexte commande valide (order_id + order_key revalidés `hash_equals`, `created_via = landing-abaya`) → `comment_approved = 1` (auto-approbation immédiate → la moyenne du badge sous le H1 est actualisée instantanément) ; avis avec texte → `comment_approved = 0` (modération) ; sans contexte commande valide → statu quo anti-spam (0). Le formulaire avis de la landing (app.js) suit le même chemin serveur sans régression (texte toujours exigé côté client).
  - **Réponse REST enrichie** : `moderation` (bool), `rating` (note finale conservée), `average` + `count` (moyenne recalculée depuis la base = même source que le badge).
- **Mock (dev)** : `saveMockReview` réplique la règle max + la modération (statuts `approved`/`pending`, filtrage d'affichage existant respecté) ; le mock injecte le nouveau design complet (en-tête, tableau, grille client, promo, upsell sans cadres, pop-up persistant) — `preview/merci.css` miroir exact du style inline + pop-up.
- **Sécurité inchangée** : accès récap `hash_equals` + 48 h + `created_via` ; anti-flood 3 avis/10 min/IP ; `no-referrer` ; version générique sans données personnelles.
- **Historique v2.1 conservé** : le FIX déclenchement (`$GLOBALS['abaya_rating_ctx']`, closure PHP) reste en place et prouvé E2E (Playwright).

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
| 2.2 | Mandat 4P LP « Refonte UI/UX Thank You Page, Offres Upsell & Logique de Notation » (branche `feat/thankyou-page-redesign`) : /merci/ épurée (typographie fine Cairo/Arial, zéro bloc massif), tableau récap 2 colonnes à séparateurs fins + miniature, grille client compacte sans boîtes, upsell SANS cadres (visuels libres + bouton d'achat direct), promo fin ; pop-up perfectionné (remplissage progressif RTL, étoiles nues, persistance + texte optionnel, fermeture X/valider/délai 30 s) ; REST : texte optionnel, règle `max(note)` par commande (méta `review_order_id`), modération intelligente (étoiles seules = auto-approbation, texte = modération), réponse enrichie (rating/average/count) ; mock + preview miroirs (fix syntaxe `ockOrder` au passage) ; validation php -l + node ESM + E2E Playwright + visuel mobile/RTL |
