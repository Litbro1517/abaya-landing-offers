# RAPPORT D'EXÉCUTION — v3.43 : AUDIT ET NETTOYAGE DE LA STRUCTURE DU THÈME
**Mandat 4P LP « AUDIT ET NETTOYAGE DE LA STRUCTURE DU THÈME (DOUBLONS ET DOSSIER MAIN) » — branche `fix/nettoyage-structure-theme`**
Base : main `5a03993` (v3.42 en production). Fusion : autonomie accordée dès tests au vert (précédent v3.42).

---

## 1. Conjoncture

Capture d'écran fournie (Explorateur Windows, dossier de travail local) : les fichiers du thème (`functions.php`, `front-page.php`, `style.css`, `template-parts/`, `assets/`, `pages/`, `uploads/`…) se trouvent **à la racine**, aux côtés d'un dossier **`abaya-landing-offers-main`** daté du jour (01/11/2026 12:46), les fichiers racine étant tous datés du 13/10/2026 16:46. Cette cohabitation perturbe l'installation manuelle sur WordPress (quel dossier zipper ? quelle copie est à jour ?) et crée une redondance visuelle de tout le thème.

## 2. Explication technique du phénomène constaté (analyse préalable obligatoire)

### 2.1 Le dossier « -main » n'est PAS un doublon créé par le projet — c'est l'emballage d'export de GitHub

Quand on clique **« Code → Download ZIP »** sur GitHub, l'archive générée s'appelle `abaya-landing-offers-main.zip` : GitHub nomme systématiquement le dossier racine de l'export **`<nom-du-dépôt>-<nom-de-la-branche>`** (ici dépôt `abaya-landing-offers` + branche par défaut `main`). Ce dossier est l'export **brut** de la branche — c'est le même contenu que ce que consomme WP Pusher (endpoint API `zipball`), dont le dossier s'appelle lui `Litbro1517-abaya-landing-offers-<sha>`. Le suffixe n'a donc rien d'anormal ; il devient gênant quand l'extraction se fait dans un dossier qui contient déjà une copie du thème.

### 2.2 Les fichiers à la racine de la capture = une COPIE LOCALE PÉRIMÉE (pré-v2.0, du 13/10/2026)

L'archéologie git (datation par création/déplacement de fichiers) identifie exactement cette copie :

| Indice (capture du 13/10) | Dépôt actuel (main) | Datation |
|---|---|---|
| `PROJECT_MAP.md` **à la racine** (22 Ko) | `docs/PROJECT_MAP.md` (déplacé) | déplacement en v2.0 — **14/10/2026** (`ce86781`) |
| `inc-offers.php`, `page-offer.php`, `merci-review.js` **ABSENTS** | présents (10 PHP racine + 3 JS) | les 3 créés en v2.0 — **14/10/2026** (`ce86781`) |
| `ARCHITECTURE_PROJECT_MAP.md` (55 Ko) | **n'existe dans AUCUNE branche** (`git log --all` vide) | document local, jamais committé |
| `front-page.php` 24 Ko | **0,5 Ko** (gabarit refactoré dans `template-parts/`) | refactors v2.x → v3.26 |
| `functions.php` 42 Ko | **84,4 Ko** | + 16 versions d'évolutions |
| `page-merci.php` 12 Ko | **52,8 Ko** | + upsell, reviews, CAPI, typo arabe… |
| `style.css` 1 Ko | **0,3 Ko** (en-tête seul ; CSS → `assets/css/`) | mutualisation v2.x |

**Conclusion** : la copie racine est un instantané du socle **v1.x d'avant le 14/10/2026** (veille de v2.0, mandat multi-landing), cohérent avec la date 13/10/2026 16:46 de l'Explorateur. Elle est périmée de **16 versions** (elle ignore la verrouillage 2 landings v3.26, la dissociation des prix v3.40, la restauration des avis v3.41, la purge v3.42…).

### 2.3 La mécanique de la duplication

1. À une date antérieure, une copie du thème avait été extraite/posée à la racine du dossier de travail (fichiers remontés d'un niveau pour zipper « à plat ») — c'est l'instantané du 13/10.
2. Le 01/11, l'archive GitHub du jour (`abaya-landing-offers-main.zip`) est extraite **dans le même dossier** : Windows n'écrase pas et ne fusionne pas — il crée un sous-dossier → deux générations côte à côte.
3. **Risque réel** : zipper/installer par mégarde la copie racine périmée (pré-v2.0) à la place de l'actuelle — c'est précisément ce que la structure v3.43 ci-dessous rend impossible.

## 3. Audit du dépôt — résultat : structure DÉJÀ conforme

Balayage intégral de l'arbre suivi (`git ls-tree -r origin/main`, 109 fichiers) :

| Critère auditée | Résultat |
|---|---|
| Dossier emboîté `abaya-landing-offers-main/` ou équivalent | **AUCUN** |
| Archives obsolètes (`.zip`, `.tar`, `.gz`, `.rar`, `.bak`, `~`) | **AUCUNE** |
| Doublons de fichiers PHP/CSS/JS/assets | **AUCUN** (racine = racine du thème : 10 PHP + 1 JS + 1 CSS + 5 dossiers) |
| Dotfiles parasites | **AUCUN** |
| Références runtime vers `docs/` | **AUCUNE** (uniquement citée dans des commentaires PHP/CSS — jamais `include`/`require`/lecture) |

Le nettoyage des assets (48 orphelins, 3 989,0 Ko) avait déjà été exécuté en v3.42 (`a7bd409`), et la racine du dépôt est la racine du thème depuis l'origine — **le dépôt ne contenait donc RIEN à purger** : les doublons constatés sur la capture sont un artefact purement LOCAL (§2), et le seul poids non-runtime restant était la mémoire projet `docs/` (35 fichiers, 562,0 Ko), volontairement conservée dans le dépôt.

## 4. Actions exécutées (branche `fix/nettoyage-structure-theme`, commit `45a073f`, merge `141fd29`)

1. **`.gitattributes` (nouveau — la seule action structurelle)** : directives `export-ignore` sur `/docs` et les dotfiles. Effet : **les archives exportées** — ZIP « Download ZIP » de GitHub, zipball API consommé par WP Pusher, `git archive` — ne contiennent **PLUS que le thème exécutable**. La mémoire projet reste intégrale dans le dépôt (convention PROJECT_MAP/rapports inchangée). Le package téléchargeable passe de 109 à **74 fichiers** (−35 fichiers, −562,0 Ko).
   - *Sécurité* : `docs/` n'est jamais incluse, requise ni lue par le code actif (vérifié par balayage `git grep` — seuls des commentaires de documentation la citent) ; zéro impact runtime possible.
   - *Vérifié sur les DEUX canaux réels* : zipball codeload (« Download ZIP ») → 74 fichiers, 0 `docs/` ; zipball API (source WP Pusher) → 74 fichiers, 0 `docs/`.
2. **`style.css` : Version 3.42 → 3.43** (marqueur de propagation, seul fichier runtime touché — en-tête de thème, jamais lu par le front).
3. **ZÉRO fichier supprimé du dépôt** — l'audit §3 n'a trouvé ni doublon ni archive à purger ; la suppression des doublons demandée concerne la machine locale (§6) et est réalisée **dans le package exporté** (exclusion des 35 docs).

## 5. Package WordPress final — conformité

| Canal | Structure obtenue (vérifiée) | Installation |
|---|---|---|
| **ZIP livré** `abaya-landing-offers.zip` (2,6 Mo) | un dossier unique `abaya-landing-offers/` = 74 fichiers, en-tête `Theme Name: Abaya Canvas / Version: 3.43`, style.css/index.php/functions.php/template-parts/assets/uploads/pages tous présents, ZÉRO doc/dotfile | **Extensions → Ajouter → Téléverser un thème → Installer → Activer** : aucune manipulation |
| **ZIP GitHub** « Download ZIP » (après v3.43) | un dossier unique `abaya-landing-offers-main/` = 74 fichiers du thème, 0 doc | Installable telle quelle (WP ≥ 5.5 détecte le dossier unique) ; renommer le dossier en `abaya-landing-offers` avant de zipper soi-même si on veut le nom exact |
| **WP Pusher** (production) | consomme main/zipball API — 74 fichiers, 0 doc | inchangé, déploiement automatique à chaque push |

Contenu du package (74 fichiers) : 10 PHP racine + 5 `template-parts/` (gabarit landing unique, TYP, pages légales), 3 JS (`app.js`, `mock-api.js`, `merci-review.js`), 3 CSS, 5 polices, 3 images de marque, 36 `uploads/` (wool ×15 + vignettes ×2 + produit-1 ×18 + favicon), 8 pages légales `pages/*.html`, + `.gitattributes` exclu des exports. Périmètre métier intact : 2 landings (accueil + produit-1), 1 upsell TYP, grilles dissociées 479/799/1099 / 299/499/699, CAPI, avis v3.41.

## 6. Nettoyage de la machine LOCALE (hors dépôt — à exécuter par l'utilisateur)

Le dépôt ne peut pas piloter l'Explorateur Windows ; les doublons visibles sur la capture se retirent ainsi :

1. **Supprimer les fichiers racine du 13/10/2026** (copie périmée pré-v2.0) : `ARCHITECTURE_PROJECT_MAP.md`, `PROJECT_MAP.md`, `footer.php`, `front-page.php`, `functions.php`, `header.php`, `inc-pages.php`, `index.php`, `page.php`, `page-merci.php`, `style.css` + les dossiers racine `template-parts/`, `uploads/`, `assets/`, `pages/`. (Précaution : `ARCHITECTURE_PROJECT_MAP.md` n'existe nulle part dans le dépôt — l'archiver hors du dossier plutôt que le détruire s'il a de la valeur.)
2. **Ne garder qu'une seule source de vérité** : l'extraction GitHub la plus récente — après v3.43, un « Download ZIP » ne contient plus QUE les 74 fichiers du thème.
3. **Renommer** `abaya-landing-offers-main` → `abaya-landing-offers` si l'on souhaite un dossier proprement nommé (le suffixe `-main` est ajouté par GitHub, pas par le projet).
4. **Ou ne rien manipuler du tout** : utiliser le ZIP livré `abaya-landing-offers.zip` (dossier déjà nommé, docs exclus, installable tel quel).
5. **Rappel déploiement** : la production est mise à jour par **WP Pusher depuis GitHub main** à chaque push — aucune manipulation de ZIP n'est nécessaire pour déployer ; le ZIP ne sert qu'aux tests locaux/staging.

## 7. Non-régression — 374 vérifications vertes + preuve bit-exact

| Suite | Résultat v3.43 | Baseline |
|---|---|---|
| `test_tunnel_v340.php` | **53/53** | 53/53 |
| `test_merci_v340.php` (TYP) | **22/22** | 22/22 |
| `test_dissociation_v340.php` | **25/25** | 25/25 |
| `test_products_v340.php` ×5 configs | **5/5 conforme** (20/21 production, explicite `_2` > convention `_1`, convention, repli 15/15, éditorial `_SLUG` — grilles/old_price stables) | 5/5 |
| `test_reviews_v341.php` ×4 scénarios | **43/43** (11+11+11+10) | 43/43 |
| `test_purge_v342.php` | **67/67** (contrôle F maintenu 3.42 → 3.43) | 67/67 |
| `test_pricing_v340.py` (Playwright, géométrie vs harnais référence main `5a03993`) | **27/27** | 27/27 |
| `test_size_bug_v331.py` | **41/44** — FAIL = A7/C8/E4, exactement la baseline préexistante v3.36 | 41/44 |
| `test_images_v334.py` | **13/13** | 13/13 |
| `test_validation_v335.py` | **35/35** | 35/35 |
| `test_validation_v339.py` | **27/35** — 8 FAIL = assertions prix vedette supersédées v3.40 (attendu) | 27/35 |
| `test_cls_v340.py` | **4/4** (0,000 == témoin v3.39) | 4/4 |
| `check_parity_v338.py` | **17/17** | 17/17 |
| Lint | `php -l` ×15 : 0 erreur ; `node --check` ×3 : OK | idem |
| **Markup rendu** | **BIT-EXACT vs main `5a03993`** : diff 0 ligne sur `index.html` (vedette) ET `produit-1.html` (secondaire) ; `assets/` css+js identiques octet à octet | — |

## 8. Fusion & déploiement

- Commit branche `45a073f` (`.gitattributes` + `style.css` 3.43) → push `fix/nettoyage-structure-theme` → **merge `--no-ff` `141fd29`** vers main (autonomie de fusion accordée par le mandat v3.42 et reconduite par la nature méta-only de v3.43) → **push origin/main `5a03993..141fd29`** → déploiement WP Pusher déclenché.
- **Propagation vérifiée sur GitHub (source WP Pusher)** : raw `style.css` → `Version: 3.43` ; raw `.gitattributes` → 200 ; zipball codeload = dossier unique, 74 fichiers, 0 doc ; zipball API = 74 fichiers, 0 doc.
- Clôture : présent rapport + PROJECT_MAP §5-tricesimus-primus (commit docs sur main).

**Statut : FUSIONNÉ main + EN PRODUCTION (v3.43) — dépôt plane, package exporté conforme, zéro fichier purgé du dépôt (audit négatif), doublons localisés et documentés (§2/§6).**
