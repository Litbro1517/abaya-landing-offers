# RAPPORT D'EXÉCUTION — v3.42 « NETTOYAGE, OPTIMISATION FINALE ET FUSION PRODUCTION »

**Mandat 4P LP — branche `chore/nettoyage-production-v342` (base = main v3.41 `a25df30`)**
**Thème : Abaya Canvas — périmètre restreint : 2 produits / 2 landing pages + 1 upsell TYP**

---

## 1. Conjoncture et pré-requis

| Élément | État à l'ouverture du mandat |
| --- | --- |
| Dissociation des prix (v3.40) | EN PRODUCTION (`5f4882b`) |
| Typographie arabe corrigée | EN PRODUCTION (`5f4882b`) |
| Restauration des avis (v3.41) | branche `fix/audit-restauration-reviews` poussée, merge EN ATTENTE de feu vert |
| Dépôt | 156 fichiers suivis, 7,8 Mio — dont 48 fichiers d'images orphelines |

**Décision n° 1 — fusion préalable de v3.41.** La conjoncture du mandat v3.42 (« restauration indépendante des avis (v3.41) **validée et en place** ») constitue le feu vert attendu par le mandat v3.41. La branche a été fusionnée **avant** tout travail de nettoyage, afin que (a) le correctif des avis soit déployé en production immédiatement, (b) la purge parte d'un état main validé et tracé.

- Merge `--no-ff` `a25df30` (arbre = `e568b74`, aucun contenu ajouté par le merge) ;
- push `5f4882b..a25df30` → déploiement WP Pusher déclenché.

## 2. Cartographie des dépendances (audit préalable intégral)

Méthode : les 156 fichiers suivis ont été confrontés aux références réelles du code actif — balayage récursif de tout chemin d'asset dans les fichiers `php/js/css/html` (hors `docs/`), croisement avec le registre `abaya_offers()` (inc-offers.php), les enqueues (functions.php), le gabarit unique (landing-offer.php), la TYP (page-merci.php + merci-review.js) et les pages légales (`abaya_ensure_pages()`).

| Groupe de fichiers | Références réelles constatées | Verdict |
| --- | --- | --- |
| `uploads/offers/produit-2/` (18 fichiers) | **AUCUNE** — landings excédentaires retirées du registre depuis v3.26 (`abaya_retired_offers()` → 301 racine) ; commandes historiques re-cataloguées vers la vedette (page-merci.php:448 « offre absente → front offer ») | **PURGER** |
| `uploads/offers/produit-3/` (18 fichiers) | idem | **PURGER** |
| `uploads/1..6.webp` + `uploads/thumbs/1..6.webp` (12 fichiers) | 1 seule référence vivante : repli défensif `abaya_lcp_image_src()` (functions.php:156) — branche **inatteignable** (`abaya_offers()` indexe ses entrées par `ABAYA_FRONT_OFFER`, donc `abaya_offer_config()` ne retourne jamais null) ; collection Chenille v3.27 remplacée par wool-* en v3.30 | **PURGER + retarget repli** |
| `uploads/wool-1..5.webp` + variantes `-480/-720` (15) + `thumbs/wool-1,2` (2) | registre vedette (couleurs, LCP, sections) + `abaya_img_srcset()` | CONSERVER |
| `uploads/offers/produit-1/` (18) | registre secondaire (7 couleurs + 7 vignettes + hero + 3 sections) | CONSERVER |
| `uploads/favicon-officiel.svg` | `wp_head` (functions.php:240, 712) | CONSERVER |
| `assets/css/*` (3) | critical inliné landings, offers inliné landings / link `/merci/`, styles async | CONSERVER |
| `assets/fonts/*` (5) | @font-face styles.css + critical.css, preload Cairo-arabic + DMSans-latin | CONSERVER |
| `assets/images/*` (3) | logo header (site-header + legal-header), logo footer (site-footer), logo TYP (page-merci ×3) | CONSERVER |
| `assets/js/app.js`, `assets/js/mock-api.js`, `merci-review.js` | app.js enqueued landings ; mock-api **importé statiquement** par merci-review.js (fallback simulation) ; merci-review.js enqueued TYP | CONSERVER |
| 15 PHP racine + template-parts | routage complet (front-page, gabarit unique page-offer, TYP, légales, registre, REST, CAPI) | CONSERVER — **aucun template multi-landing obsolète** (architecture gabarit unique v3.26 : « ajouter une offre = une entrée de registre, AUCUN fichier PHP ») |
| `pages/*.html` (8) | source de contenu des pages légales semée par `abaya_ensure_pages()` (functions.php:1376) — jamais écrasée | CONSERVER |
| `docs/` (31 fichiers, 612 Ko) | mémoire projet (PROJECT_MAP + audits + rapports) — **jamais chargée par WordPress, zéro impact runtime** ; base de travail de chaque mandat | CONSERVER (choix documenté §5) |

## 3. Purge exécutée — 48 fichiers, 3 989,0 Ko

Détail exhaustif (tailles mesurées via `git ls-tree -l HEAD`) :

| Lot | Fichiers | Volume |
| --- | --- | --- |
| `uploads/offers/produit-2/` — beige, blanc, bleu-ciel, bleu-marine, bordeaux, hero.webp, marron, noir, sec-colors, sec-inspection.jpg, sec-specs.jpg + 7 vignettes | 18 | 924,3 Ko |
| `uploads/offers/produit-3/` — beige, blanc, bleu-ciel, bleu-marine, bordeaux, hero.jpg, marron, noir, sec-colors, sec-inspection.webp, sec-specs.webp + 7 vignettes | 18 | 870,4 Ko |
| Collection Chenille v3.27 : `uploads/1..6.webp` + `uploads/thumbs/1..6.webp` | 12 | 2 194,3 Ko |
| **TOTAL** | **48** | **3 989,0 Ko** |

Impact : dossier `uploads/` réduit de **6 342,8 Ko → 2 353,8 Ko (−63 %)** ; dépôt total 7,8 → **3,9 Mio (−50 %)**. Aucun CSS, JS, gabarit ou police supprimé — aucun ne référençait les fichiers purgés (balayage récursif : **zéro** chemin mort dans le code actif).

## 4. Changements de code (chirurgicaux)

1. **Retarget du repli LCP** — functions.php `abaya_lcp_image_src()` : `'uploads/1.webp'` (Chenille purgée) → `'uploads/wool-1.webp'` (LCP de la vedette, asset existant). Garde défensive conservée ; branche inatteignable (cf. §2) — **comportement de production inchangé**, le repli ne s'est jamais déclenché.
2. **Bump version** — style.css `Version: 3.42`.
3. **Scripts de harnais (hors dépôt, `/home/z/my-project/scripts/`)** — miroirs du repli retargeté : `harness_render_v337.php`, `harness_render_v329.php` ; `build_merci_test.sh` : retrait des copies vestigiales `produit-2/hero.webp`, `produit-3/hero.jpg`, `hero-moka.jpg` (fichiers purgés ou inexistants depuis v3.28).

## 5. Choix de conservation documentés

- **`docs/` (612 Ko)** : conservée intégralement — mémoire de travail mandatée (PROJECT_MAP, audits, rapports), jamais servie au front, aucun impact sur le chargement. La « performance maximale » visée par le mandat concerne le runtime : 100 % du gain provient du dossier `uploads/`.
- **`pages/*.html`** : non orphelins — ils ALIMENTENT les pages légales en base (`abaya_ensure_pages()`), leur retrait casserait le semis sur toute réinstallation/migration.
- **`mock-api.js`** : non orphelin — import statique de `merci-review.js` (mode simulation : n'agit que si le serveur WordPress est injoignable).
- **`abaya_retired_offers()`** (slugs `produit-2/3`) : conservée — routage 301 permanent des anciennes URLs publicitaires (aucune image référencée).
- **CSS/JS** : audités pour les références aux fichiers purgés (aucune) et laissés octet à octet — la purge d'éventuelles règles CSS mortes n'entre pas dans le mandat et exposerait à une régression visuelle sur des pages validées au pixel.

## 6. Non-régression — 374 vérifications vertes + preuve bit-exact

| Suite | Résultat | Baseline |
| --- | --- | --- |
| **Sonde dédiée `test_purge_v342.php` (NOUVEAU)** | **67/67** | — (intégrité registre ×2 offres, srcset ×15, absence des 48 purgés, zéro référence de chemin, repli retargeté ×3, version, inventaire, résolution/grilles/301) |
| `test_tunnel_v340.php` | **53/53** | 53/53 |
| `test_merci_v340.php` | **22/22** | 22/22 |
| `test_dissociation_v340.php` | **25/25** | 25/25 |
| `test_products_v340.php` (sonde ×5 configs) | conforme | registre 2 offres, grilles 479/799/1099 vs 299/499/699, liaisons explicites |
| `test_reviews_v341.php` ×4 scénarios | **43/43** (11+11+11+10) | 43/43 |
| `test_pricing_v340.py` (Playwright) | **27/27** | 27/27 — géométrie == référence v3.39 |
| `test_size_bug_v331.py` | **41/44** | 41/44 — les 3 FAIL (A7, C8, E4) = baseline pré-purge REJOUÉE sur main `a25df30` (worktree) → identiques, préexistants v3.36 |
| `test_images_v334.py` | **13/13** | 13/13 |
| `test_validation_v335.py` | **35/35** | 35/35 |
| `test_validation_v339.py` | **27/35** | 27/35 — les 8 FAIL = assertions prix vedette SUPERSEDÉES par la dissociation v3.40 (documentées) |
| `test_cls_v340.py` (CLS retard 1,2 s) | **4/4** (0,000) | == témoin v3.39 |
| `check_parity_v338.py` | **17/17** | 17/17 |
| `php -l` (15 PHP) + `node --check` (3 JS) | 0 erreur | 0 erreur |

**Preuve bit-exact (la plus forte)** : rendu de harnais comparé pré-purge (main `a25df30`, worktree `/tmp/w341main`) vs post-purge (branche) — `diff` **0 ligne** sur `index.html` (vedette) ET `produit-1.html` (secondaire) ; `assets/` css+js **identiques octet à octet**. Le nettoyage n'a changé AUCUN octet du HTML servi ni des CSS/JS.

## 7. Fusion et déploiement (autonomie accordée par le mandat)

1. Merge `--no-ff` **`09d67ac`** (`chore/nettoyage-production-v342` → main ; commit purge `a7bd409` — 52 fichiers : 48 D + 3 M + 1 A) ; push origin/main `a25df30..09d67ac` → déploiement WP Pusher.
2. Vérification de propagation sur **GitHub raw** (= source WP Pusher), résultats : style.css `Version: 3.42` ✓ ; `uploads/wool-1.webp` **200** ✓ ; `uploads/offers/produit-1/hero.jpg` **200** ✓ ; `uploads/1.webp` **404** ✓ ; `uploads/thumbs/1.webp` **404** ✓ ; `uploads/offers/produit-2/hero.webp` **404** ✓ ; `uploads/offers/produit-3/hero.jpg` **404** ✓ ; typo `لتستفيدي` présente ✓ ; liaisons `LANDING_PRODUCT_ID_2` ×4 ✓ ; repli `uploads/wool-1.webp` dans functions.php ✓.
3. Commit de clôture sur main : le présent paragraphe + PROJECT_MAP §5-tricesimus (hashes finaux).

## 8. Architecture de production définitive (v3.42)

- **109 fichiers suivis** (156 − 48 purge + 1 rapport) : 15 PHP, 3 CSS, 3 JS, 5 polices, 4 images de marque, 33 images produits (wool ×15 + vignettes wool ×2 + produit-1 ×18 + favicon... soit 36 fichiers uploads au total), 8 pages légales HTML, 32 docs projet.
- **Périmètre métier** : 2 landings (vedette `accueil` publique + secondaire `produit-1` gate 302 upsell, slugs retirés → 301), 1 produit d'upsell TYP, grilles dissociées 479/799/1099 / 299/499/699, CAPI Purchase serveur, avis « مراجعة الزبناء » v3.41 (type `review`).
- **Volumes** : uploads 2 353,8 Ko (−63 %), dépôt ≈ 3,9 Mio (−50 %).

## 9. Risques résiduels et recommandations

- **Cache CDN/serveur** : après déploiement, les URLs purgées peuvent rester en cache quelques heures (404 définitif après purge du cache LiteSpeed/Cloudflare si besoin).
- **Branches git historiques** : ~30 branches locales/distantes des itérations antérieures restent présentes (non demandé par le mandat — hors périmètre « fichiers du thème ») ; un housekeeping `git push origin --delete` peut être envisagé sur décision du mandataire, l'historique restant de toute façon intégralement conservé dans main.
- **Ajout d'une offre future** : 1 entrée de registre + 1 ligne `abaya_offer_product_constant()` + son dossier d'images — AUCUN fichier PHP ni code de résolution.
