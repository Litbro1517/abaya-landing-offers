# AUDIT POST-EXÉCUTION — v3.30 « ADAPTATION PRODUIT ABAYA LAINE + SÉLECTEUR MULTI-PIÈCES »

> Mandat 4P LP : **`feat/wool-abaya-product-adaptation`** — branche dédiée du même nom, créée depuis `main` @ `ed6808f` (v3.29).
> Périmètre : **Landing page principale (vedette, racine) UNIQUEMENT** — landing secondaire (`/produit-1/`, Upsell) et Thank You Page (`page-merci.php`) **100 % intangibles**.
> Rapport exigé par la PARTIE 4 du mandat : respect de la structure/dimensions/agencement, intégration des 2 images JPG provisoires (avec signalement WebP), intégration de la logique `abaya-multi-piece-picker.html`, présence exacte de tous les textes.

---

## 1. Résultat d'ensemble

| Exigence du mandat | Statut | Preuve |
|---|---|---|
| Structure / dimensions / agencement intangibles | ✅ | Rendu déterministe : `produit-1` **bit-exact 0 octet** ; vedette : même squelette de gabarit, mêmes classes, même ordre de sections ; largeurs mesurées offres 2/3 : gallery **577 px** / product-info **532 px** / overflow **0 px** (1280 comme 360) |
| 2 images JPG provisoires intégrées | ✅ | `uploads/wool-1.jpg` (البني الكريمي) + `uploads/wool-2.jpg` (الأسود) + vignettes `thumbs/wool-1|2.jpg` 151×170 — **signalement WebP en fin de rapport** |
| Logique `abaya-multi-piece-picker.html` intégrée | ✅ | Compteurs −/+ par couleur (offres 2/3), tailles par pièce, gating du bouton, champs cachés `offer_pieces / offer_total / order_items / order_items_json` — batterie Playwright **66/66 PASS** |
| Tous les textes mis à jour sans altération | ✅ | Matrice mot-à-mot **51/51 PASS** (titre, héros, grille 479/799/1099, économies 20/159/338, الثمن المرجعي 499/958/1437, 2 couleurs, 9 détails, étapes 01/02, étape 03 inchangée) |
| Couleurs réduites à exactement 2 teintes | ✅ | دائرتان فقط : `بني كريمي #b9a590` + `أسود #1c1c1c` (ordre d'énumération du mandat) |
| Composants 100 % intangibles | ✅ | Logo, back-link ‹ رجوع, **grille 3 offres (STRUCTURE + libellés + `.is-selected` + défilement fluide)**, formulaire (4 champs + honeypot + CTA «اضغطي هنا للطلب»), tunnel COD, TYP — ZÉRO diff |

## 2. Matrice textuelle (PARTIE 3) — injection mot-à-mot

Injectée dans l'entrée **vedette seule** du registre (`inc-offers.php`) :

- **عنوان المنتج** : `عباية شتوية من توب الصوف – انسيابية ودوبل كلوش`
- **الوصف (الهيرو)** : `عباية شتوية من توب الصوف، بقصة دوبل كلوش واسعة وفضفاضة وانسيابية، تجمع بين الدفء والاحتشام والمظهر الأنيق.`
- **عروض الأسعار** (grille + héros) : 479 / 799 / 1099 درهم — توفير 20 / 159 / 338 درهم — الثمن المرجعي 499 / 958 / 1437 درهم (chaque توفير arithmétiquement VRAI : 499−479=20 ; 958−799=159 ; 1437−1099=338 ; les références 2/3 pièces = 2×479 / 3×479)
- **الألوان** : دائرتان فقط — البني الكريمي `#b9a590` et الأسود `#1c1c1c`
- **فقرة « لماذا هذه العباية هي الخيار المثالي؟ »** : `متوفرة بلونين: البني الكريمي والأسود.`
- **9 détails produit** (format « étiquette : texte » reproduit tel quel) — **le سطر الحزام de v3.28 est SUPPRIMÉ définitivement** (consigne « حذف سطر الحزام نهائياً »)
- **خطوات الطلب** : étapes 01 et 02 mot-à-mot (sélection de l'offre + couleur/moule **par pièce** ; confirmation téléphonique élargie à l'offre et au couple couleur/moule de chaque pièce) — **étape 03 inchangée**
- Hors matrice (conservés car non mandatés et toujours vrais) : slogan, titre d'onglet « Abaya Collection », inspection, مراحل التصنيع (v3.29 protégée), garanties, FAQ, contact.

## 3. Images provisoires (gestion JPG → WebP)

| Fichier | Source mandat | Couleur | Emplacements |
|---|---|---|---|
| `uploads/wool-1.jpg` (457×797, 70 Ko) | Capture 013607 (tons clairs/beiges) | البني الكريمي | LCP héros + section détails |
| `uploads/wool-2.jpg` (456×792, 60 Ko) | Capture 013457 (tons sombres) | الأسود | section لماذا + section inspection |
| `uploads/thumbs/wool-1|2.jpg` (151×170) | crop 30 % hauteur (format du système) | idem | rangée de vignettes |

- Dimensions natives conservées (aucun upscale destructif) : les emplacements `#main-photo` / `.visual-media` rendent en `object-fit: cover` dans le cadre 3:4 existant (attrs 1170×1560) — **aucune déformation**, cadrage `center 35 %`/`center 28 %` du socle inchangé.
- ⚠️ **SIGNALEMENT (exigé par le mandat)** : ces 2 JPG sont PROVISOIRES — à remplacer ultérieurement par des **WebP définitifs** (mêmes emplacements `wool-1|2.jpg` → conversion/renommage `.webp` + mise à jour des 4 chemins du registre). Les 6 visuels Chenille `1..6.webp` restent dans le dépôt (non mandatés au retrait) et ne sont plus référencés par la vedette.
- Cartographie couleur→image : البني الكريمي → wool-1 (ton clair), الأسود → wool-2 (ton sombre) ; visuels de section alternés wool-2/wool-1/wool-2.

## 4. Intégration du sélecteur multi-pièces (`abaya-multi-piece-picker.html`)

**Architecture (choix techniques justifiés)** :

1. **Verrouillage vedette** : `IS_FRONT = !LANDING.offer_id` dans `app.js` (l'absence de `offer_id` identifie la vedette — injection `functions.php` inchangée). Sur les secondaires, aucun chemin du module n'exécute : comportement historique bit-exact, preuve par rendu 0 octet + POST sans champs multi (test).
2. **Affichage conditionnel** (consigne « sans déformer le bloc principal ») : le conteneur `#multi-picker` est rendu **masqué** (attribut `hidden`) — zéro impact au chargement (critical.css insensible, LCP inchangé). Sélection d'une offre 2/3 pièces → conteneur affiché **à la place** des deux fieldsets simple choix (masqués via `.is-hidden`) ; offre 1 pièce → UI historique exacte. Le contenu grandit dans la colonne existante (aucun repositionnement d'élément).
3. **Données lues depuis le DOM rendu serveur** (swatches = clé catalogue + `--swatch-color`, chips = `.size-option`) : aucune duplication front/serveur des couleurs/tailles — la whitelist REST reste l'autorité unique.
4. **Contrat handoff respecté** : répartition par défaut 2→1+1 / 3→2+1 ; « + » désactivé à l'atteinte du total ; même couleur plusieurs fois autorisée ; une rangée de tailles par pièce avec couleur affichée automatiquement (aucun choix « avec/sans boutons ») ; bouton désactivé tant que somme ≠ pièces OU taille manquante ; changement d'offre conserve les tailles par **index de pièce** (§6 handoff) ; chaque interaction couleur émet l'événement DOM **`abaya:colorchange`** câblé sur la galerie (image principale + vignettes + légende).
5. **Mappage commande** : `color`/`size` du payload = **1ʳᵉ pièce** (whitelist serveur + méta اللون/المقاس) ; le détail complet part dans `order_items` (ligne lisible) et `order_items_json` (tableau `[{n,color,size}]`). Offre 1 pièce : payload historique strictement inchangé.
6. **Serveur (ADDITIF)** : `abaya_parse_order_items()` + `abaya_order_items_text()` — champs absents ⇒ `null` (comportement bit-exact, secondaires compris) ; présents ⇒ whitelist par pièce (même garde P0-2 que couleur/taille simple, 422 sinon) et total pièces == quantité de l'offre. **La ligne lisible est RÉGÉNÉRÉE côté serveur** depuis les pièces validées (la chaîne client n'est jamais stockée telle quelle). Persistance : méta ligne d'article اللون/المقاس (résumé « بني كريمي + أسود » / « M + L ») + `تفاصيل القطع`, méta commande `_abaya_order_items(_json)`, note service client enrichie — la TYP (non modifiée) affiche le résumé combiné et retrouve la miniature quand la couleur est unique.
7. **CSS** : section v3.30 en FIN de feuille (règles toutes nouvelles — zéro règle existante modifiée), tokens de la charte (`--line`, `--ink`, `--olive`, `--olive-dark`), pastilles 24 px du composant `.swatch span`, puces de taille = classe `.size-option` existante, ton d'erreur `.field-error` (#a14f43). Contrastes : +/− `#2d6a70`/blanc ≈ 5,9:1 ; texte `--ink` sur `#fffdf8` > 12:1 (AA).

## 5. Validation (auto-contrôle)

| Suite | Résultat |
|---|---|
| `php -l` (PHP 8.4) × 3 fichiers modifiés | ✅ 0 erreur |
| Rendu déterministe `harness_render_v329.php` (stubs WP) — `produit-1` v3.29 vs v3.30 | ✅ **BIT-EXACT, 0 octet** |
| Rendu déterministe — vedette | ✅ diff = exactement la matrice du mandat (textes, prix, couleurs, images, conteneur picker, 4 champs cachés) ; مراحل التصنيع v3.29, formulaire, grille (structure) absents du diff |
| Playwright fonctionnel (`test_wool_picker_v330.py`) — 1280 & 360 | ✅ **66/66** (offre 1 historique, picker 2/3 pièces, gating, compteurs, payload contractuel + redirection /merci/, `abaya:colorchange`, produit-1 sans picker ni champs multi, overflow 0 à 360 px avec 3 pièces affichées) |
| Unitaires serveur (`test_server_v330.php`) | ✅ **25/25** (absent→null, whitelist par pièce 422, total≠quantité 422, JSON invalide 422, injection HTML neutralisée, ligne régénérée mot-à-mot, résumés méta, arithmétique grille, WCAG pastilles) |
| Matrice textuelle (`test_matrix_v330.py`) | ✅ **51/51** (verbatim + absences : الحزام, شونيل, anciens prix) |
| Captures (`shots_v330/`) | ✅ offre 1 / offre 3 (1280), offre 2 (360), produit-1 — intégration charte, zéro déformation |

## 6. Périmètre des fichiers (branche)

```
modifiés  : inc-offers.php (registre vedette : grille par offre, 2 couleurs, textes, images)
            template-parts/landing-offer.php (conteneur picker + 4 champs cachés — conditionnels vedette)
            assets/js/app.js (module multi-pièces verrouillé vedette + repli grille 479/799/1099)
            functions.php (helper parse/text + handlers REST additifs create-order & add-to-order)
            assets/css/styles.css (section v3.30 en fin de feuille)
            assets/js/mock-api.js (repli dev 479/799/1099)
            style.css (Version 3.30)
ajoutés   : uploads/wool-1.jpg, uploads/wool-2.jpg, uploads/thumbs/wool-1.jpg, uploads/thumbs/wool-2.jpg
INTACTS   : page-merci.php (TYP), header.php, footer.php, inc-pages.php, page-offer.php,
            critical.css, offers.css, merci-review.js, uploads/offers/produit-1/*,
            1..6.webp + thumbs (retrait non mandaté), logo, back-link ‹ رجوع
```

## 7. Points d'attention / suites

1. **WebP définitifs** : remplacer `wool-1.jpg`/`wool-2.jpg` (+ thumbs) par les WebP optimisés — mêmes chemins si possible pour minimiser le diff (mise à jour des 4 références du registre si renommage `.webp`).
2. **Retrait des visuels Chenille** (1..6.webp + thumbs) : non mandaté ici — à traiter lors du passage WebP (le repli hors-landing de `abaya_lcp_image_src()` pointe encore `uploads/1.webp` : conservé volontairement).
3. **Comportement handoff §6** : les tailles suivent l'INDEX de pièce (une taille déjà choisie reste pré-sélectionnée si la couleur de la pièce change après un −/+). Visible et modifiable par la cliente — conforme au contrat du fichier de dev handoff.
4. Meta description adaptée à la nouvelle collection (même gabarit éditorial que v3.27/28 — le mandat n'en fournit pas).
