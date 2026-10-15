# PROJECT MAP — Thème « Abaya Canvas » multi-landing (`abaya-landing-offers`)

> Cartographie du dépôt de travail **`Litbro1517/abaya-landing-offers`** — architecture « N offres (landings) + une offre vedette à la racine » avec cross-sell sur /merci/.
> Socle de référence (INTACT, jamais modifié ici) : **`Litbro1517/abayacollection-theme-clean` @ f25c7049 (v1.27)**.
> Audit préalable complet : **`docs/AUDIT_PHASE0.md`**.

---

## 1. Identité

| Élément | Valeur |
|---|---|
| Thème WordPress | **Abaya Canvas** — Version **2.4** (style.css) |
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
├── style.css                     # En-tête thème — Version 2.4
├── functions.php                 # Adaptateur abaya_catalog($offer_id), LCP par offre,
│   │                             # window.LANDING (clés offre = secondaires SEULEMENT),
│   │                             # Pixel/CAPI par offre, REST additive (offer/offer_id),
│   │                             # ensure_pages v2.0 (pages produit-N + template),
│   │                             # template_include (garantie gabarit unique),
│   │                             # badge étoiles SVG (abaya_review_summary/render —
│   │                             # format exact section avis, TOUTES les landings,
│   │                             # agrégat DÉCOUPLÉ de la modération v2.4)
├── inc-offers.php                # REGISTRE DES OFFRES (source unique de données)
├── front-page.php                # Racine = offre vedette (ABAYA_FRONT_OFFER) → gabarit
├── page-offer.php                # GABARIT DE PAGE UNIQUE des secondaires (slug → offre)
├── page-merci.php                # Récap sécurisé (hash_equals, 48 h) — INTÉGRATION
│                                 # MAQUETTE CANVA 4PLP v2.3 (carte blanche sur crème,
│                                 # logo شكراً WebP, pastille n° commande, tableau
│                                 # 4 colonnes + image, bloc cadeau turquoise,
│                                 # WhatsApp officiel, upsell 3 verticales 3:5)
│                                 # + pop-up 1-clic persistant + config mock
├── merci-review.js               # Pop-up évaluation 1-clic perfectionné (remplissage
│                                 # progressif RTL, persistance + texte optionnel,
│                                 # fermeture X/valider/délai 30 s) — réel + mock
│                                 # + injection du design Canva en mode mock
├── header.php / footer.php       # abaya_is_landing_page() remplace is_front_page()
├── template-parts/landing-offer.php  # GABARIT UNIQUE du landing (toutes offres)
├── pages/*.html                  # Graines des pages légales (inchangées, socle)
├── assets/css/{critical,styles}.css  # SOCLE INCHANGÉS bit-à-bit
├── assets/css/offers.css         # Badge étoiles SVG (2 couches, clip --rating-fill)
│                                 # + pop-up (étoiles nues, is-hot/is-picked, accents
│                                 # bordeaux/turquoise charte) — chargée TOUTES
│                                 # landings + merci ; la mise en page merci vit
│                                 # dans le style inline du gabarit (v2.3)
├── assets/images/merci-logo-shukran.webp  # Logo calligraphié شكراً (bordeaux #6B1D2F
│                                 # sur blanc pur, 474×474, ~8 Ko) — remplaçable
│                                 # sans code par l'asset officiel client
├── assets/js/app.js              # Socle + paramètre `offer` additif (secondaires)
├── assets/js/mock-api.js         # Simulation localStorage (REST 404/50x uniquement)
│                                 # + miroir règle max / modération (v2.2)
│                                 # + agrégat découplé des statuts (v2.4)
├── uploads/                      # = EXACTEMENT les 9 fichiers du socle (preuve S9.4)
├── uploads/offers/produit-{1,2,3}/   # Assets ISOLÉS par offre (hero + 7 couleurs + thumbs)
├── preview/                      # Prototype HTML statique FIGÉ (dev uniquement)
└── docs/                         # AUDIT_PHASE0.md, PROJECT_MAP.md, OFFRES.md, RAPPORT_TESTS.md
```

## 4. Règles d'or respectées

1. **Socle intact** : aucun commit sur `abayacollection-theme-clean` ; CSS/JS socle bit-à-bit ; uploads racine = fichiers socle exacts ; tunnel REST (idempotence, anti-flood, prix autoritaire, piège anti-robot, hash_equals merci) inchangé et ADDITIF.
2. **Rendu racine : badge seul ajouté** : PROUVÉ par harnais (diff racine = uniquement le bloc badge étoiles sous le H1, mandat 4P LP v2.1) — tout le reste du rendu du socle vs gabarit unique est inchangé.
3. **Vraies connexions** : tous les formulaires (commande + avis) pointent vers `landing/v1/create-order` et `landing/v1/submit-review` (REST réelles) ; le mock n'agit QUE si le serveur est injoignable (404/50x réseau) — jamais sur une 4xx métier.

## 5. Page /merci/ — Intégration maquette Canva 4PLP (v2.3, mandat « Page de Remerciement »)

- **Référence** : maquette Canva 4PLP (`canva.com/design/DAHYAa7V--E`) — clonée via navigateur réel (rendu 200 OK, captures intégrales), structure/textes/palette reproduits fidèlement.
- **Carte principale blanche** (radius 18 px, ombre douce bordeaux) sur fond crème **#FAF7F2** ; sections internes séparées par des filets 1 px `#F3ECE3` ; titres de section à barre bordeaux (`::before` 4×22 px).
- **En-tête** : logo calligraphié « شكراً » **WebP** (bordeaux #6B1D2F sur **fond blanc pur**, 92 px, généré Amiri Bold — remplaçable sans code par l'asset officiel) + H1 bordeaux « تم استلام طلبكِ بنجاح! يسعدنا انضمامكِ إلينا » + sous-texte réassurance (appel de confirmation, paiement à la livraison) + **pastille numéro de commande** (pilule rose `#F4E6E9`).
- **تفاصيل الطلب** : tableau **4 colonnes** `المنتج | اللون / المقاس | الكمية | السعر` SANS cadre englobant lourd (bande d'en-tête crème `#F8F3EC`, filets fins) — l'**image produit est alignée proprement à côté** des informations textuelles dans sa propre cellule-encadré (3:4, radius 12 px, bord `#F0EAE1`) ; format prix maquette « **DH 450** » (`<bdi dir="ltr">`, chiffres occidentaux) ; rangée totale « المجموع الإجمالي: ».
- **Bloc cadeau** (bordure turquoise 1,5 px **#4D9AA4**, fond crème) : titre « 🎁 هدية خاصة لطلبكِ القادم! », description avec **خصم 20 درهم** en gras turquoise, **code promo personnalisé en pilule pointillée** (`كود الخصم الخاص بكِ` + code) + icône cadeau décorative (masquée en mobile) — format code inchangé `[PRÉNOM]-[VILLE]-[N°COMMANDE]`, présentation MAJUSCULES (fidèle maquette « RAK - ABD - 89421 », `mb_strtoupper` sans effet sur l'arabe).
- **معلومات المراسلة** : coordonnées épurées (الاسم / الرقم الهـ / العنوان complet ville+pays) + **bouton de suivi officiel WhatsApp** (vert **#25D366**, logo SVG officiel, ombre verte, « تواصل معنا أو تابع طلبك عبر الواتساب ») — lien wa.me inchangé (numéro + texte du n° de commande).
- **Section upsell — EXACTEMENT 3 produits au format vertical 3:5 (style Reels/Stories)** : titre centré bordeaux « تشكيلة أخرى قد تعجبكِ أيضاً » + sous-titre gris « أضيفي عباية أخرى لنستفيدي من الخصم المباشر في طلبكِ » ; cartes image plein cadre (radius 14 px, fond de repli `#DFE9EA`), nom clampé, prix bordeaux + **bouton turquoise pleine largeur « إضافة للطلب »** (hover #3D7F88) → `{landing}/#order-form-section` ; **badge « تخفيض خاص » + ancien prix barrés OPTIONNELS** via clés de registre `cross_badge` / `cross_old_price` (désactivés par défaut — aucune invention de prix) ; offres = registre SAUF celle commandée (4 offres → 3 verticales). **Mobile : carrousel horizontal scroll-snap** (60 % viewport, scrollbar masquée).
- **Typographie** : **Cairo exclusivement** (DM Sans bannie du gabarit — code promo en Cairo 700) ; **palette charte** : Bordeaux #6B1D2F, Vert Turquoise #4D9AA4, Crème #FAF7F2.
- **Pied de carte** : « © {année} Abaya Collection — جميع الحقوق محفوظة » (année dynamique `date_i18n`).
- **Version générique** (sans clé valide) : même carte + logo + H1 + réassurance + bouton WhatsApp — zéro donnée personnelle.
- **Pop-up d'évaluation 1-clic** (inchangé v2.2, harmonisé charte) :
  - **Remplissage progressif RTL** : survol ou focus sur la N-ième étoile → étoiles 1..N allumées simultanément (classes `.is-hot` posées en JS, sens RTL respecté) ; étoiles NUES (fini les boîtes isolées), 48 px, dorées `#c49b55` ;
  - **Persistance (mandat)** : le clic enregistre DIRECTEMENT la note (REST) puis le pop-up RESTE OUVERT — message « شكراً جزيلًا على تقييمك! 🌿 هل ترغبين في إضافة كلمة بسيطة عن تجربتك؟ » + zone de texte optionnelle (apparition douce animée) ;
  - **Fermeture** : croix (X), bouton valider (envoi du texte ; sans texte = fermeture gracieuse), touche Échap, clic voile, ou **délai prolongé 30 s** réinitialisé à chaque saisie (2,6 s après envoi du texte) ;
  - **Règle du maximum** : le serveur renvoie `rating = max(note_existante, nouvelle_note)` — l'affichage des étoiles s'aligne sur la valeur conservée (l'avis existant est retrouvé par la méta `review_order_id`) ;
  - **Modération intelligente** : étoiles seules + contexte commande valide (order_id + order_key revalidés `hash_equals`, `created_via = landing-abaya`) → `comment_approved = 1` (auto-approbation : la note alimente immédiatement la moyenne du badge sous le H1, agrégat découplé v2.4 — la carte correspondante ne s'affiche JAMAIS dans le listing) ; avis avec texte → `comment_approved = 0` (modération) ; sans contexte commande valide → statu quo anti-spam (0). Le formulaire avis de la landing (app.js) suit le même chemin serveur sans régression (texte toujours exigé côté client).
  - **Réponse REST enrichie** : `moderation` (bool), `rating` (note finale conservée), `average` + `count` (moyenne recalculée depuis la base = même source que le badge).
- **Mock (dev)** : `saveMockReview` réplique la règle max + la modération (statuts `approved`/`pending`, filtrage d'affichage existant respecté) ; le mock injecte le design Canva complet (carte, logo via clé `logo` de ABAYA_MOCK, pastille, tableau 4 col + image, cadeau, WhatsApp, upsell 3:5, pied) — `preview/merci.css` miroir exact du style inline + pop-up (générateur `scripts/gen_preview_mirrors.py`).
- **Sécurité inchangée** : accès récap `hash_equals` + 48 h + `created_via` ; anti-flood 3 avis/10 min/IP ; `no-referrer` ; version générique sans données personnelles.
- **Historique v2.1 conservé** : le FIX déclenchement (`$GLOBALS['abaya_rating_ctx']`, closure PHP) reste en place et prouvé E2E (Playwright).

## 5-bis. Badge étoiles sous le H1 (v2.1 — mandat 4P LP « Ajustement Final & Badge Étoiles »)

- **Format SVG EXACT de la section avis** : même `<path d="m12 2.5 2.9 5.9 …">`, même `viewBox="0 0 24 24"`, classes `.review-star` / `.review-star.is-empty` identiques (byte-exact, vérifié programmatique) — aucun texte ni formulaire de la section avis dupliqué.
- **Garnissage dynamique depuis la BDD** : moyenne de TOUTES les notes « review » (approuvées ET en attente — découplée de la modération depuis v2.4, `abaya_review_summary()`) → 2 couches (grise + dorée clippée à `--rating-fill:%`, ex. 4.9/5 → 98 %) + score chiffré « 4.9 / 5 » + compteur d'avis.
- **Placement** : sous le H1 (entre le titre et `.product-intro`) sur la landing principale ET les 3 secondaires (gabarit unique — une seule modification).
- **Taille** : 30 px desktop / 26 px mobile (scoping `.landing-rating .review-star` surclassant les tailles socle 15/22 px quel que soit l'ordre de chargement des feuilles) ; couleurs doré/gris héritées des règles socle du composant avis.
- **CSS** : `offers.css` désormais chargée sur TOUTES les landings + /merci/ (avant : secondaires + merci seulement).

## 5-ter. Découplage Étoiles / Avis Clients (v2.4 — mandat « fix/rating-comments-decoupling »)

- **Problème corrigé** : l'affichage global des étoiles (score sous le titre) était couplé à la soumission des commentaires — une note « étoiles seules » s'affichait dans le listing « Avis Clients » sous forme de carte partielle/vide (placeholder `تقييم بنقرة واحدة`) sans modération préalable ; inversement la moyenne ignorait les notes associées à un texte en attente.
- **Global Score Ratio (étanchéité 1)** : `abaya_review_summary()` agrège TOUTES les notes de type « review » via une union déterministe `approve` + `hold` (spam/corbeille exclus par construction) — **chaque note attribuée met à jour la moyenne sous le H1 immédiatement**, indépendamment du corps du commentaire et de son statut de modération.
- **Section Avis Clients (étanchéité 2)** : le listing n'affiche **QUE SI ET SEULEMENT SI** l'avis textuel est validé par l'administrateur. Garde double dans `abaya_render_review_card()` : (1) `comment_approved === 1` exigé ; (2) texte réel exigé (contenu vide ou placeholder du 1-clic banni) — les évaluations d'étoiles seules ne génèrent JAMAIS de carte. Requête listing : `status=approve` + nombre porté à 50 (fenêtre de cartes préservée).
- **Liaison relationnelle préservée** : note et commentaire restent liés en base (même enregistrement « review » + méta `rating` + `review_order_id`) ; seuls les rendus visuels sont étanches et conditionnés par la modération.
- **Mise à jour immédiate (Étape B)** : la réponse REST `submit-review` (average/count recalculés en base) pilote `updateRatingBadge()` dans `app.js` — score, garnissage `--rating-fill`, compteur et `aria-label` du badge H1 actualisés live à la soumission ; miroir `preview/app.js` (mock = agrégat local toutes notes).
- **Modération inchangée** : texte → `comment_approved = 0` (en attente de validation admin) ; étoiles seules + commande valide → 1 ; sans commande valide → 0. Règle `max(note)` et anti-flood intacts.
- **Mock miroir (v2.4)** : `getReviewSummary` porte sur toutes les notes (approuvées ET en attente) ; `getMockReviews` (listing public) reste approuvé seul.
- **Validation** : suite avis 29/29 (4 assertions découplage T7.1-T7.4 dont étanchéité renderer + affichage après validation admin) + 5 invariants statiques S12.1-S12.5 ; correction au passage d'un bug masqué du harnais T7 (nom 1 caractère rejeté 422 — l'ancienne assertion count=1 passait sans créer l'avis texte).

## 6. Historique

| Version | Contenu |
|---|---|
| 2.0 | Mandat multi-landing : registre 4 offres, gabarit unique, routing, REST additive (`offer`), badge étoiles BDD, pop-up 1-clic merci, cross-sell, code promo, mock dev-only, rebase socle v1.27 (restructuration : prototype → `preview/`, purge résidus), validation 30/30 REST + 59/59 statique + rendu racine bit-à-bit |
| 2.1 | Mandat 4P LP « Ajustement Final & Badge Étoiles » (branche `feat/ratings-popup-adjustments`) : badge étoiles SVG au format EXACT de la section avis sous le H1 sur les 4 landings (avant : secondaires seules, étoiles Unicode), garnissage BDD 2 couches ; FIX P0 pop-up merci (`ratingCtx` closure PHP toujours null → auto-ouverture restaurée) ; correctif Échap mode mock ; offers.css chargée sur toutes les landings ; validation 59/59 statique + 30/30 REST + 14/14 E2E Playwright + 16/16 visuel + SVG byte-exact 10/10 |
| 2.2 | Mandat 4P LP « Refonte UI/UX Thank You Page, Offres Upsell & Logique de Notation » (branche `feat/thankyou-page-redesign`) : /merci/ épurée (typographie fine Cairo/Arial, zéro bloc massif), tableau récap 2 colonnes à séparateurs fins + miniature, grille client compacte sans boîtes, upsell SANS cadres (visuels libres + bouton d'achat direct), promo fin ; pop-up perfectionné (remplissage progressif RTL, étoiles nues, persistance + texte optionnel, fermeture X/valider/délai 30 s) ; REST : texte optionnel, règle `max(note)` par commande (méta `review_order_id`), modération intelligente (étoiles seules = auto-approbation, texte = modération), réponse enrichie (rating/average/count) ; mock + preview miroirs (fix syntaxe `ockOrder` au passage) ; validation php -l + node ESM + E2E Playwright + visuel mobile/RTL |
| 2.3 | Mandat 4PLP « Intégration Maquette Canva — Page de Remerciement » (branche `feat/merci-canva-integration`) : maquette Canva (`DAHYAa7V--E`) extraite par navigateur réel et intégrée fidèlement — carte blanche sur crème #FAF7F2, logo calligraphié شكراً WebP (bordeaux sur blanc pur), pastille n° commande, tableau 4 colonnes المنتج/اللون-المقاس/الكمية/السعر avec cellule image produit, bloc cadeau bordure turquoise + code promo en pilule pointillée (MAJUSCULES), bouton WhatsApp officiel vert + SVG, upsell EXACTEMENT 3 verticales 3:5 style Reels/Stories (carrousel scroll-snap mobile, badge تخفيض خاص + ancien prix optionnels via registre, boutons turquoise إضافة للطلب), pied © ; Cairo exclusif (DM Sans bannie) ; pop-up harmonisé (bouton bordeaux, focus turquoise) ; logo WebP généré Amiri Bold (remplaçable sans code) ; validation 67/67 statique + 29/29 E2E + 41/41 visuel Canva + 26/26 avis + 30/30 orders |
| 2.4 | Mandat « MANDAT COMBO : Audit Préalable & Protocole — Découplage Étoiles / Avis Clients » (branche `fix/rating-comments-decoupling`) : étanchéité stricte entre score global et avis — `abaya_review_summary()` agrège toutes les notes (union approve+hold, indépendante de la modération du texte), garde double dans `abaya_render_review_card()` (approved=1 + texte réel, placeholder 1-clic banni du listing), mise à jour live du badge H1 via `updateRatingBadge()` (réponse REST), requête listing approve + number 50, mock/preview miroirs (agrégat toutes notes) ; validation 72/72 statique + 29/29 avis + 30/30 orders + 16/16 badge + 29/29 E2E + 41/41 Canva (217/217) |
