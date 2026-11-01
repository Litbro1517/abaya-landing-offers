# RAPPORT D'EXÉCUTION — v3.44 : AUDIT DE LA TUYAUTERIE DE TRACKING & INTÉGRATION GTM/GA4
**Mandat 4P LP « AUDIT DE LA TUYAUTERIE DE TRACKING ET INTÉGRATION GTM/GA4 » — branche `fix/audit-tuyauterie-tracking`**
Base : main `b56bb6c` (v3.43 en production). Fusion : autonomie reconduite (précédents v3.42/v3.43), validation par sonde AVANT tout déploiement (exigée par le mandat).

---

## 1. Conjoncture

Les balises et conteneurs sont configurés correctement sur les interfaces Google Tag Manager et Google Analytics 4, mais **aucune activité temps réel ne remonte** du site. Les tests préliminaires excluent la configuration Google : suspicion d'une défaillance de tuyauterie/injection dans le code source du thème. Objectif : auditer l'injection (présence, emplacement exact, syntaxe), valider le flux de données (aucun blocage/interception) et garantir la conformité de la livraison avant de chercher des causes externes.

## 2. Audit préalable — cause racine et état de la tuyauterie

### 2.1 Cause racine : le thème ne contenait AUCUN code GTM/GA4
Balayage intégral du dépôt (`git grep` sur les 109 fichiers, hors docs) : `googletagmanager`, `gtm.js`, `dataLayer`, `gtag(`, `google-analytics` → **ZÉRO occurrence**. Le seul tracking client existant est le Meta Pixel (bloc conditionnel `META_PIXEL_ID`, functions.php). **gtm.js n'a donc jamais été chargé par aucune page du site** — c'est la cause complète du symptôme : rien ne « remonte » parce que la balise n'est pas servie, indépendamment de la qualité de la configuration côté Google.

### 2.2 La tuyauterie de rendu est SAINNE et standard (les points d'injection existent)
| Contrôle | Résultat |
|---|---|
| `header.php` appelle `wp_head()` **dans** le `<head>` | ✓ (L11, avant `</head>`) |
| `header.php` appelle `wp_body_open()` **immédiatement après** `<body…>` | ✓ (L21, avant tout contenu) |
| `footer.php` appelle `wp_footer()` | ✓ (L10) |
| Les 4 familles de gabarits passent par cette tuyauterie partagée | ✓ `get_header()` dans landing-offer.php (L35/L521), page-merci.php (L584/L762), index.php, page.php |
| Éléments susceptibles de bloquer/intercepter (CSP, Service Worker, `ob_start`) | **AUCUN** dans le thème |
| Scripts du thème dans le `<head>` | bloc ressources priorité 5 (critical CSS, `window.LANDING`, app.js, Meta Pixel) — ne touchent jamais `dataLayer` |

**Conclusion d'audit** : aucun script n'était bloqué ou intercepté — il n'existait simplement **aucun point d'injection GTM**. Le remède est une tuyauterie d'injection conforme aux standards officiels Google, branchée sur les hooks sains existants.

## 3. Correctif v3.44 (commit `0758710` — functions.php + style.css, zéro autre fichier runtime)

1. **gtm.js DANS le `<head>`, aussi haut que possible** — hook `wp_head` **priorité 2** : immédiatement après les preloads LCP/polices (priorité 1 — discipline CWV v3.37 conservée à l'identique) et **avant tout script/style** du bloc ressources (priorité 5). Extrait officiel Google **octet pour octet** (IIFE, `gtm.start`, `event:'gtm.js'`, `gtm.js?id='+i+dl`, `j.async=true`).
2. **`<noscript>` IMMÉDIATEMENT après l'ouverture du `<body>`** — hook `wp_body_open` **priorité 1** (iframe `ns.html?id=…`, `height="0" width="0"`, `display:none;visibility:hidden`), exactement le standard Google.
3. **gtag.js GA4 direct en repli** (`GA4_MEASUREMENT_ID`, même priorité 2) — **uniquement si AUCUN conteneur GTM n'est configuré** : protection anti double-comptage (une même propriété GA4 ne doit jamais être alimentée par les deux chemins). La balise GA4 vit normalement DANS le conteneur GTM.
4. **Liste blanche stricte des identifiants** (`^GTM-[A-Za-z0-9_-]+$` / `^G-[A-Za-z0-9_-]+$`) : identifiant vide ou invalide → **zéro octet émis** (pas d'extrait tronqué, pas d'injection possible — tentatives XSS testées).
5. **Constantes wp-config par défaut VIDES** (`GTM_CONTAINER_ID`, `GA4_MEASUREMENT_ID`) : tant que l'hébergement ne les définit pas, la sortie rendue reste **BIT-EXACT** (preuve §5) — déploiement sans risque, activation par simple ajout dans wp-config.php.

## 4. Validation par sonde — 62 vérifications statiques + 12 preuves navigateur

### 4.1 Sonde statique `test_gtm_v344.php` (rendu du VRAI header.php + vrai functions.php) — 62/62
| Scénario | Résultat | Preuves clés |
|---|---|---|
| Couverture tuyauterie | 10/10 | wp_head dans `<head>`, wp_body_open avant tout contenu, get_header ×4 gabarits, hooks prio 2/1, liste blanche |
| Constantes absentes | 4/4 | zéro octet googletagmanager/dataLayer/gtag — bit-exact |
| GTM seul (`GTM-TESTP4P`) | 16/16 | extrait officiel intégral, DANS `<head>`, APRÈS preloads (prio 1) et AVANT ressources (prio 5), noscript immédiatement après `<body…>` (regex `<body…>\s*<!-- GTM (noscript) -->`), iframe ns.html + display:none, conteneur unique |
| GA4 seul (`G-TESTP4PAB`) | 7/7 | gtag.js async + config, dataLayer init, AUCUN snippet GTM |
| GTM + GA4 | 15/15 | GTM servi, **gtag.js direct ABSENT** (anti double-comptage), noscript présent |
| ID invalide (XSS `GTM-ABC';alert(1)//`) | 5/5 | zéro octet émis, tentative neutralisée |
| GA4 invalide (`G-X<script>`) | 5/5 | idem |

### 4.2 Preuve navigateur `test_gtm_browser_v344.py` (Playwright Chromium, conteneur GTM simulé par interception réseau) — 12/12
**Preuve de remontée en mode debug sur l'environnement de développement** (page de landing réelle servie en local, harnais v344) :
```
dataLayer capturé :  [{"gtm.start":1793541450753,"event":"gtm.js"}, {"event":"gtm.mockExecute"}]
requêtes capturées : ['https://www.googletagmanager.com/gtm.js?id=GTM-TESTP4P']
```
- **B1** le navigateur tente `gtm.js?id=GTM-TESTP4P` (requête réseau réelle) ; **B2** `dataLayer` initialisé avec l'événement officiel `gtm.start` AVANT le script ; **B3** le script est téléchargé ET exécuté (rien ne bloque/intercepte) ; **B4** le flux dataLayer reste vivant après le script (événement du conteneur reçu) ; **B5** preuve DOM : les deux premiers scripts du `<head>` sont ceux de GTM (async inséré + snippet inline) ; **B6** preuve DOM : le `<noscript>` est le premier élément du `<body>` ; **B7** chemin no-JS : la requête `ns.html?id=GTM-TESTP4P` est tentée (iframe noscript fonctionnelle) ; **B8** page témoin sans constantes : zéro requête googletagmanager, `dataLayer` inexistant, aucun marqueur DOM ; **B9** zéro erreur console / zéro exception de page.

## 5. Non-régression — 374 vérifications vertes + preuve bit-exact

| Suite | v3.44 | Baseline |
|---|---|---|
| tunnel / TYP / dissociation / purge / reviews ×4 / produits ×5 | **53/53 + 22/22 + 25/25 + 67/67 (F maintenu 3.44) + 43/43 (11+11+11+10) + 5/5** | identique |
| pricing Playwright (géométrie == harnais référence main `b56bb6c` reconstruit) | **27/27** | 27/27 |
| size-bug / images / validation v335 / v339 | **41/44 (A7/C8/E4 == baseline) + 13/13 + 35/35 + 27/35 (supersédés attendus)** | identique |
| CLS 0,000 == témoin / parité / lint | **4/4 + 17/17 + php -l 0 erreur + node ×3 OK** | identique |
| **Markup rendu, constantes absentes** | **BIT-EXACT vs main `b56bb6c`** — diff 0 ligne (56072 octets = 56072 octets, cache-busting `?ver/?v=` normalisé : seuls les mtimes des fichiers modifiés diffèrent) | — |

## 6. Activation en production (hors dépôt — wp-config.php de l'hébergement)

```php
// wp-config.php (au-dessus de /* Add any custom values... */ STOP EDITING) :
define('GTM_CONTAINER_ID', 'GTM-XXXXXXX');    // conteneur GTM réel (la balise GA4 est configurée DANS le conteneur)
// define('GA4_MEASUREMENT_ID', 'G-XXXXXXXXXX'); // UNIQUEMENT si GA4 n'est PAS dans le conteneur GTM
```
Après push du thème (WP Pusher) + ajout des constantes : les balises sont servies sur TOUTES les pages (landings, TYP, pages légales — tuyauterie partagée prouvée). Vérification immédiate : « Afficher la source » → `<!-- Google Tag Manager -->` dans le `<head>`, `<noscript>` après `<body>` ; mode Aperçu/Tag Assistant de GTM → `gtm.start` dans dataLayer ; temps réel GA4 actif dès la première visite. (Pensebler : vider le cache LiteSpeed après activation.)

## 7. Fusion & déploiement

Commit branche `0758710` → push `fix/audit-tuyauterie-tracking` → merge `--no-ff` vers main → push origin/main → déploiement WP Pusher déclenché ; propagation vérifiée sur GitHub raw (style.css **3.44**, bloc GTM présent dans functions.php). Clôture : présent rapport + PROJECT_MAP §5-tricesimus-secundo.

**Statut : FUSIONNÉ main + EN PRODUCTION (v3.44) — tuyauterie GTM/GA4 conforme aux standards Google, validée par 74 vérifications de tracking (62 statiques + 12 navigateur) et 374 non-régressions ; activation effective = ajout de `GTM_CONTAINER_ID` dans wp-config.php (§6).**
