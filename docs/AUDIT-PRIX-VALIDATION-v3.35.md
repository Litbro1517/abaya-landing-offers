# AUDIT & RAPPORT D'EXÉCUTION — v3.35
## Prix Hero + Offre 1 pièce (529/توفير 50) · Sous-titre H1 homogène fusionné · Validation « Shake & Red Border »

- **Dépôt** : `Litbro1517/abaya-landing-offers`
- **Branche** : `feat/wool-abaya-product-adaptation` (base = `origin/main` @ `d0f72ef`, v3.34 — sync fast-forward effectuée avant toute modification)
- **Mandat** : « protocole mandat 4P LP » — PARTIE 1 (audit préalable Hero / offre 1 pièce / formulaire), PARTIE 2 (matrice prix/textes/validation), PARTIE 3 (rapport, code testé mergé & poussé sur `main` via WP Pusher, `PROJECT_MAP.md`)
- **Version thème** : 3.34 → **3.35** (`style.css`)

---

## 1. Audit préalable (PARTIE 1) — état avant modification

| Zone | État constaté (main v3.34, `d0f72ef`) |
|---|---|
| **Hero** (`.price-row`) | `#current-price` = **479 درهم** (`bundles[1]`, registre) · `#old-price` (del) = **499 درهم** (`$T['old_price']`) · `.discount-pill` = **توفير 20 درهم** (`bundle_labels[1]['saving']`) |
| **Offre 1 pièce** (carte `[data-quantity="1"]`) | `.bundle-price` = **479 درهم** · `.bundle-old` (del gris #888/0.85em, style v3.32) = **499 درهم** (`bundle_labels[1]['ref']`) · `.bundle-saving` = **توفير 20 درهم** (clé partagée Hero/carte) |
| **Sous-titre H1** (`.product-intro`) | v3.33 : Fusha `intro` + phrase Darija `intro_finish` rendue en `<em class="product-intro-darija">-…-</em>` (encadrée de tirets, italique sélectif) |
| **Formulaire** (`#order-form`, `novalidate`) | 4 champs texte `required` (name/phone/city/address) + slots `<small class="field-error">` ; fieldsets couleur/taille avec slots `.choice-error` + mécanique `attribute-error-box` (**outline 2px #e53e3e** + keyframes `abaya-shake` v4P) ; validation JS existante : nom ≥ 3, téléphone marocain (regex), ville ≥ 2, adresse ≥ 4, couleur + taille obligatoires ; messages `#a14f43` (10px) au phrasé « يرجى…/أدخلي… » ; **écarts vs mandat** : (a) AUCUN cadre rouge sur les champs texte, (b) AUCUNE secousse sur les champs texte, (c) couleur de cadre #e53e3e ≠ #e74c3c, (d) messages ni rouges-voyants ni au phrasé « المرجو… » |
| **Multi-pièces (offres 2/3)** | Bouton DÉSACTIVÉ tant que la répartition/tailles sont incomplètes (`app.js:347` `submitButton.disabled = stockBlocked \|\| pickerBlocked`) → la validation du mandat concerne le flux 1 pièce + champs texte ; picker NON modifié |
| **Arithmétique** | 529 − 479 = **50** → le badge « توفير 50 درهم » reste arithmétiquement VRAI (tradition du registre : chaque توفير affiché est vrai) |

Interprétation de la matrice prix : Hero et carte 1 affichent le MÊME ensemble 1-pièce (prix principal / prix barré / économie). Le mandat fait évoluer **le prix barré/référence (499 → 529)** et **l'économie (توفير 20 → توفير 50)** ; le prix principal **479** n'est cité nulle part comme à changer (l'offre 1 pièce ne porte que « Prix barré & Économie ») — c'est la seule combinaison cohérente (un prix principal 529 rendrait le barré 499 inférieur au prix de vente, et contredirait la carte 1 restée à 479 ainsi que `#summary-price`).

## 2. Modifications appliquées (PARTIE 2) — périmètre exact : 4 fichiers

### 2.1 Prix & économie (Hero + Offre 1 pièce) — `inc-offers.php`
- `bundle_labels[1]` : `'saving' => 'توفير 20 درهم'` → **`'توفير 50 درهم'`**, `'ref' => 499` → **`529`** (carte 1 + pill Hero — clé partagée)
- `old_price` : `'499 درهم'` → **`'529 درهم'`** (del du Hero)
- **INTACTS** : `bundles[1] => 479` (prix principal Hero + carte 1 + `#summary-price`), offres 2/3 (`799/1099` — barrés `958/1437` — توفير `159/338`), libellés, ruban, crochets.

### 2.2 Sous-titre H1 — texte homogène validé — `inc-offers.php`
- `intro` = **« عباية شتوية من توب الصوف، بقصة دوبل كلوش واسعة وفضفاضة وانسيابية، تجمع بين الدفء والاحتشام والمظهر الأنيق، بفينيسيون نقية وخياطة متقونة من الداخل ومن برا. »** (mot-à-mot PARTIE 2.2)
- `intro_finish` = `''` → la branche conditionnelle `<em>` du gabarit ne rend **plus rien** (fin des tirets + italique v3.33, remplacés par le texte homogène validé). Le gabarit `landing-offer.php` n'est **pas modifié** (mécanisme conservé, produit-1 bit-exact).
- La règle CSS v3.33 `.product-intro .product-intro-darija` est conservée (inerte : aucun `<em>` rendu).

### 2.3 Validation « Shake & Red Border » — `app.js` + `styles.css`
- **`app.js`** :
  - `CHOICE_ERROR_MESSAGES` → phrasé du mandat : `color: "المرجو اختيار اللون"`, `size: "المرجو اختيار المقاس"` (exemple exact de la matrice) ;
  - `setFieldError()` : en cas d'erreur, la zone concernée reçoit la classe **`.is-shaking`** relancée à CHAQUE tentative de soumission (reflow — même mécanique que `flagChoiceError`) ; nettoyage dès la saisie ;
  - messages champs : nom **« المرجو إدخال الاسم الكامل »**, téléphone vide **« المرجو إدخال رقم الهاتف »** (exemple exact), téléphone au format invalide **« المرجو إدخال رقم هاتف مغربي صحيح »** (message dédié conservé, phrasé harmonisé), ville **« المرجو إدخال المدينة »**, adresse **« المرجو إدخال العنوان »** (sans point final, aligné sur les exemples du mandat).
- **`styles.css`** :
  - `.field-error` : couleur **#a14f43 → #c0392b** (message d'alerte ROUGE ; #c0392b = 5,4:1 sur blanc — AA pour ce texte de 10px ; #e74c3c reste réservé aux cadres) ; tailles/marges inchangées (zéro décalage) ;
  - `.choice-group.attribute-error-box` : outline **#e53e3e → #e74c3c** (technique outline conservée — zéro décalage de mise en page, cf. commentaire d'origine) ;
  - bloc **v3.35** fin de feuille :
    - `.form-field input[aria-invalid="true"], … select[aria-invalid="true"]` (+ variante `.product-landing .hero-order` en spécificité **(0,4,1)** pour battre la recoloration « VF charte » `.hero-order` (0,3,1) ligne ~1340 et le `:focus` par position de cascade) → **`border-color: #e74c3c` + `box-shadow: 0 0 0 1px #e74c3c`** = cadre SOLIDE 2px `#e74c3c` (`border: 2px solid #e74c3c` du mandat) avec bordure de repos 1px et géométrie 46px **bit-exactes** (zéro décalage) ;
    - `.form-field.is-shaking` → `animation: abaya-shake .45s …` (keyframes existantes réutilisées) + override `prefers-reduced-motion: reduce` (secousse neutralisée, accessible).

### 2.4 Version — `style.css`
- `Version: 3.34` → **`3.35`**.

### 2.5 Zéro modification
- `template-parts/landing-offer.php` : **0 ligne** ;
- offres 2/3 pièces (cartes, données, sélecteur multi-pièces, gating) : **0 ligne** ;
- critical.css / offers.css / page-merci.php / handlers REST / TYP : **0 ligne**.

## 3. Intangibilité prouvée (harnais déterministe `build_harness_v331.sh`)

- Rendus PHP comparés **main `d0f72ef` (v3.34)** vs **branche v3.35** :
  - **`produit-1.html` : diff = 0 octet (BIT-EXACT)** ;
  - **`index.html` (vedette) : diff = 16 lignes confinées aux 3 zones mandatées** — (1) ligne 39 sous-titre (texte fusionné, `<em>` disparu), (2) lignes 43-44 Hero (`del` 499→529, pill توفير 20→50), (3) lignes 132-133 carte 1 (`del` 499→529, saving توفير 20→50). Cartes 2/3, prix 479, H1, ruban, images, logo, back-link, formulaire : absents du diff = intacts.
- Les états d'erreur (aria-invalid / is-shaking / attribute-error-box) sont des états JS **clients** : le HTML servi est inchangé (aucun impact serveur/cache).

## 4. Validation (PARTIE 3.2) — `test_validation_v335.py` Playwright : **35 PASS / 0 FAIL**

- **A. Prix & économie ×10** : Hero `#current-price` 479 / `#old-price` « 529 درهم » / `.discount-pill` « توفير 50 درهم » ; carte 1 (479 / barré « 529 درهم » / توفير 50) ; **cartes 2/3 INTACTES** (799/958/159 · 1099/1437/338) ; ruban « الأكثر طلباً » bordeaux #6B1D2F intact ; `#summary-price` 479 درهم.
- **B. Sous-titre ×4** : texte == mot-à-mot du mandat ; **0 `<em>`** ; **0 tiret** ; H1 intact.
- **C. Validation desktop ×16** : soumission à vide → `aria-invalid` ×4 + **cadre rgb(231, 76, 60)** ×4 + **shake `abaya-shake`** ×4 + messages exacts « المرجو… » ×4 en **#c0392b** ; blocs couleur/taille : `attribute-error-box` + **outline 2px rgb(231, 76, 60)** + shake + « المرجو اختيار اللون/المقاس » ; **AUCUNE soumission** ; secousse **relancée** à la 2ᵉ tentative ; téléphone « 123 » → message dédié « المرجو إدخال رقم هاتف مغربي صحيح » ; saisie valide → **nettoyage complet** (aria-invalid, messages, cadres, secousses) puis soumission tentée (`#form-result.is-error` visible — endpoint harnais 404 attendu) ; régression : offre 2 pièces → multi-picker affiché.
- **M. Mobile 360 ×5** : Hero 529/توفير 50 ; cadre+shake+message sur champ nom ; bloc taille + « المرجو اختيار المقاس » ; **overflow horizontal = 0px**.
- **Lint** : `php -l inc-offers.php` OK (binaire harnais) · `node --check app.js` OK.
- **Captures** : `scripts/shots_v335/` — `v335_hero_1280.png`, `v335_offers_1280.png`, `v335_form_errors_1280.png` (4 champs encadrés de rouge + messages rouges), `v335_hero_360.png`, `v335_form_errors_360.png`.

## 5. Déploiement

- Commit branche → **merge `--no-ff` → push `main`** (déclenche le déploiement automatique **WP Pusher**) ;
- Vérification post-déploiement en ligne (fichiers `main` bruts GitHub) : `verify_deploy_v335.py` — 15 vérifications (prices, sous-titre, messages, bloc CSS v3.35, version 3.35).

## 6. Conclusion

La matrice PARTIE 2 est appliquée **intégralement et uniquement** là où le mandat la demande : prix barré/référence **529 درهم** + économie **توفير 50 درهم** au Hero ET sur l'offre 1 pièce (arithmétique vraie, 529−479=50), sous-titre H1 remplacé par le **texte homogène validé** (une seule phrase, sans tirets ni italique), et un système de validation **shake + cadre rouge #e74c3c + messages rouges « المرجو… »** opérationnel sur tous les champs obligatoires (taille, couleur, nom, téléphone, adresse, ville) au clic sur « اضغطي هنا للطلب ». Toute structure validée (prix principal 479, offres 2/3, multi-pièces, ruban, H1, images v3.34, logo, back-link, tunnel, TYP) est intacte — **produit-1 bit-exact 0 octet**.
