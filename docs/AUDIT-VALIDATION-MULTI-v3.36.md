# AUDIT & RAPPORT D'EXÉCUTION — v3.36
## Généralisation du système de validation « Shake & Red Border » aux offres 2 et 3 pièces

- **Dépôt** : `Litbro1517/abaya-landing-offers`
- **Branche** : `feat/wool-abaya-product-adaptation` (base = `origin/main` @ `3db9b8e`, v3.35 — sync fast-forward effectuée avant toute modification)
- **Mandat** : « protocole mandat 4P LP » — PARTIE 1 (audit préalable du script de validation fonctionnel sur l'offre 1 pièce), PARTIE 2 (généralisation à l'identique aux sélecteurs des offres 2 et 3 pièces), PARTIE 3 (rapport, code testé mobile/desktop mergé & poussé sur `main` via WP Pusher, `PROJECT_MAP.md`)
- **Version thème** : 3.35 → **3.36** (`style.css`)

---

## 1. Audit préalable (PARTIE 1) — le système de validation fonctionnel (offre 1 pièce, v3.35)

### 1.1 Les DEUX mécanismes du système validé

**A — Champs du formulaire `معلومات الزبون` (`setFieldError`, app.js:556)** — au clic sur « اضغطي هنا للطلب » :
1. **Cadre rouge voyant** `border: 2px solid #e74c3c` : rendu par `border-color: #e74c3c` + `box-shadow: 0 0 0 1px #e74c3c` sur `input[aria-invalid="true"]` (styles.css bloc v3.35, spécificité (0,4,1) battant la recoloration `.hero-order` (0,3,1)) — bordure de repos 1px et géométrie 46px bit-exactes (zéro décalage) ;
2. **Micro-secousse** : classe `.form-field.is-shaking` → `animation: abaya-shake .45s cubic-bezier(.36,.07,.19,.97) both` (keyframes styles.css:1234), **relancée à chaque tentative** par reflow (`void wrapper.offsetWidth`), neutralisée par `prefers-reduced-motion` ;
3. **Message rouge ciblé** : `<small class="field-error">` `#c0392b` (10px, AA 5,4:1) au phrasé « المرجو… » (« المرجو إدخال الاسم الكامل », « المرجو إدخال رقم الهاتف », « المرجو إدخال رقم هاتف مغربي صحيح », « المرجو إدخال المدينة », « المرجو إدخال العنوان »), **nettoyé dès la saisie** (listeners `input`/`change`) ;
4. Focus du premier champ invalide ; validation : nom ≥ 3, téléphone marocain (regex `^(?:(?:\+|00)212)?0?[67]\d{8}$`), ville ≥ 2, adresse ≥ 4.

**B — Blocs couleur/taille 1 pièce (`setChoiceError`/`flagChoiceError`, app.js:272)** :
1. **Contour rouge** : classe `.attribute-error-box` → `outline: 2px solid #e74c3c` + `outline-offset: 3px` (technique outline : zéro décalage de mise en page) ;
2. **Micro-secousse** : relancée par reflow à chaque tentative (`block.classList.remove(...); void block.offsetWidth; block.classList.add(...)`) ;
3. **Message rouge ciblé** : slot `<small class="field-error choice-error" data-choice-error="…" role="alert">` au-dessus du bloc — `CHOICE_ERROR_MESSAGES` : couleur = **« المرجو اختيار اللون »**, taille = **« المرجو اختيار المقاس »** (exemples exacts de la matrice) — nettoyé dès la sélection (`selectColor`/handler `.size-option` → `setChoiceError(…, false)`) ;
4. Défilement vers le premier bloc manquant (`scrollIntoView`, block center, reduced-motion respecté) ; les DEUX blocs sont encadrés simultanément si aucun choix.

### 1.2 État constaté du sélecteur multi-pièces (offres 2/3, v3.30/v3.31) — TROIS ÉCARTS vs mandat

| # | Écart constaté (main v3.35, `3db9b8e`) | Conséquence vs PARTIE 2 du mandat |
|---|---|---|
| **G1** | `updateSubmitState()` (app.js:348) **DÉSACTIVE le bouton** tant que la sélection est incomplète : `submitButton.disabled = stockBlocked \|\| pickerBlocked` où `pickerBlocked = isMultiMode() && !isValidMulti()` (contrat handoff §5 de v3.30) | Le scénario du mandat — « sélectionne l'offre 2/3 et **clique** sur « اضغطي هنا للطلب » sans avoir choisi » — est **impossible** : aucun clic, aucun retour visuel, la cliente reste sans explication |
| **G2** | Branche multi du submit (app.js:651-652) : `if (!validMulti \|\| !validColor \|\| !validSize) missingChoice = pickerRoot;` → **scroll seul** : AUCUNE secousse, AUCUN cadre rouge, AUCUN message « المرجو… » sur les blocs de pièces | Les blocs de pièces ne réagissent PAS « de façon rigoureusement identique » aux champs du formulaire et aux blocs 1 pièce |
| **G3** | Le DOM du picker est **re-rendu intégralement** à chaque interaction (`renderMultiPicker()`, `innerHTML` — app.js:147-203) ; seuls indices visuels existants : `.picker-field.is-missing > span { color:#a14f43 }` (étiquette) et `.picker-hint` « اختاري المقاس لكل قطعة » (#a14f43, role=status) | Toute erreur posée par simple classe DOM serait **écrasée au re-rendu** → l'état d'erreur doit vivre dans l'état JS (`multi`) et être rendu par `renderMultiPicker()` |

Signaux réutilisables sans duplication : keyframes `abaya-shake` (styles.css:1234), couleur cadre `#e74c3c`, couleur message `#c0392b` via `.field-error` (v3.35), messages `CHOICE_ERROR_MESSAGES` (chaîne identique pour les pièces : « المرجو اختيار المقاس » / « المرجو اختيار اللون »).

### 1.3 Décision d'architecture (liberté d'implémentation PARTIE 3)

1. **Fin du blocage G1** (changement délibéré du contrat handoff §5, exigé par le mandat) : le bouton reste **ACTIF** en mode multi-pièces — la tentative de soumission déclenche la validation ciblée, exactement comme l'offre 1 pièce (dont le bouton n'a jamais été désactivé pour manque de choix) et comme les champs du formulaire. La garde serveur (`abaya_out_of_stock`, 422) reste l'autorité finale ; la garde couleur épuisée (`stockBlocked`) est conservée.
2. **État d'erreur dans `multi`** (G3) : `multi.errors = { colors: bool, sizes: [bool par pièce] }` + drapeau transitoire `multi.shake` — rendus par `renderMultiPicker()` (les nœuds reconstruits portent l'animation à l'insertion : la secousse **rejoue à chaque tentative de soumission** sans astuce de reflow) ; nettoyage ciblé dès la sélection (taille choisie → erreur de la pièce effacée ; répartition complétée → erreur couleur effacée ; changement d'offre → tout effacé).
3. **Trois signaux identiques au système validé** : cadre solide 2px `#e74c3c` sur le bloc de pièce (border-color + box-shadow 1px sur sa bordure existante 1px — zéro décalage), contour `outline 2px #e74c3c` sur le sélecteur de tailles manquant et sur le bloc des compteurs couleur (technique outline des choice-groups — zéro décalage), secousse `abaya-shake .45s` (keyframes existantes), messages `.field-error` (#c0392b — style EXACT des messages du formulaire) « المرجو اختيار المقاس » par pièce et « المرجو اختيار اللون » pour la répartition.
4. **Cohérence globale** : au clic, champs texte ET blocs multi signalent leurs erreurs SIMULTANÉMENT (même soumission, même visuel) ; le défilement va au premier bloc manquant (compteurs couleur → première pièce incomplète → sinon focus premier champ invalide).

---

## 2. Modifications appliquées (PARTIE 2) — périmètre exact : 3 fichiers

### 2.1 `assets/js/app.js`
- **État** : `multi = { counts, items, errors: { colors: false, sizes: [] }, shake: false }` ; reset complet dans `syncMultiOffer()` (changement d'offre) ;
- **`renderMultiPicker()`** : rend les erreurs depuis l'état — bloc `.picker-colors` → `is-invalid` (+ `is-shaking` si tentative) + `<p class="field-error picker-error" role="alert">المرجو اختيار اللون</p>` ; chaque `.picker-piece` incomplet → `is-invalid` (+ `is-shaking`) + contour du `.picker-field.is-missing` + `<p class="field-error picker-error" role="alert">المرجو اختيار المقاس</p>` ; drapeau `multi.shake` consommé à chaque rendu (la secousse ne rejoue qu'à chaque nouvelle tentative) ;
- **`flagMultiErrors()`** (nouveau) : calcule `errors.colors` (somme ≠ pièces) et `errors.sizes` (pièces sans taille), pose `shake`, rend, retourne l'état ;
- **Handlers** : clic taille → efface l'erreur de la pièce ; inc/dec compteur → efface l'erreur couleur dès la répartition complète ; les deux re-rendent (nettoyage visible immédiat) ;
- **`updateSubmitState()`** : `submitButton.disabled = stockBlocked` — fin de `pickerBlocked` (G1, cf. 1.3-1) ;
- **Submit multi** : remplace le scroll seul (G2) par `flagMultiErrors()` + défilement vers le premier bloc manquant (`.picker-colors` puis première pièce invalide) ; condition d'échec inchangée (`validMulti`) ; payload/REST **inchangés**.

### 2.2 `assets/css/styles.css` — bloc v3.36 fin de feuille (règles TOUTES NOUVELLES, zéro règle existante modifiée)
- `.multi-picker .picker-piece.is-invalid` → `border-color: #e74c3c` + `box-shadow: 0 0 0 1px #e74c3c` (cadre SOLIDE 2px du mandat, géométrie bit-exacte) ;
- `.multi-picker .picker-piece.is-invalid .picker-field.is-missing` → `outline: 2px solid #e74c3c` (offset 2px, radius 8px) autour du sélecteur de tailles ;
- `.multi-picker .picker-colors.is-invalid` → `outline: 2px solid #e74c3c` (offset 3px, radius 12px) autour des compteurs couleur ;
- `.multi-picker .picker-piece.is-shaking, .multi-picker .picker-colors.is-shaking` → `animation: abaya-shake .45s cubic-bezier(.36,.07,.19,.97) both` (keyframes existantes) + override `prefers-reduced-motion: reduce` ;
- Messages : classe `.field-error` EXISTANTE réutilisée (10px #c0392b, AA 5,4:1) — aucun style ajouté nécessaire (même style que le formulaire, conformité « de la même manière que pour l'offre 1 pièce »).

### 2.3 `style.css`
- `Version: 3.35` → **`3.36`**.

### 2.4 Zéro modification
- `template-parts/landing-offer.php` : **0 ligne** (les erreurs vivent dans l'état JS et le rendu dynamique du picker) ;
- `inc-offers.php` / `functions.php` / handlers REST / `page-merci.php` / `critical.css` / `offers.css` : **0 ligne** ;
- Offre 1 pièce (UI, validation, messages), offre 2/3 (cartes, prix 799/1099, ruban, compteurs, résumé, payload), TYP, tunnel, logo, back-link, images : **0 ligne**.

---

## 3. Intangibilité prouvée (harnais déterministe `build_harness_v331.sh`)

- Rendus PHP comparés **main `3db9b8e` (v3.35)** vs **branche v3.36** : `index.html` (vedette) et `produit-1.html` **diff = 0 octet (BIT-EXACT)** — prévu : aucune modification de gabarit/PHP, seuls `app.js`/`styles.css` (externes) et `style.css` (version) changent.

## 4. Validation (PARTIE 3) — `test_validation_multi_v336.py` Playwright

Batterie en 7 parties (desktop 1280 + mobile 360) : A régression offre 1 pièce (validation v3.35 inchangée) · B offre 2 (bouton actif, erreurs simultanées formulaire+2 pièces, messages exacts, cadre #e74c3c, secousse) · C nettoyage ciblé par pièce + relance de la secousse à la 2ᵉ tentative · D flux complet → soumission tentée · E offre 3 (erreur couleur « المرجو اختيار اللون » + nettoyage) · F régression produit-1 · G mobile 360 (erreurs visibles, overflow 0) + captures `shots_v336/`.

## 5. Déploiement
- Commit sur `feat/wool-abaya-product-adaptation` → merge `main` → push `origin` (les deux branches) → **WP Pusher auto-déploiement** ; vérification post-déploiement sur les fichiers bruts du commit + site en ligne.
