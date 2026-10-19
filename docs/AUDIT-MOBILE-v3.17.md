# AUDIT v3.17 — TROIS DERNIERS AJUSTEMENTS UX/UI (GARANTIES MOBILE, CARROUSEL UPSELLS, MODALE SANS ÉMOJI)

**Mandat 4P LP** — « /app protocole mandat 4P LP » (PHASE 1 audit des 3 derniers ajustements mobile & modale ; PHASE 2 implémentation et rectification chirurgicale).
**Branche** : `fix/mobile-garanties-upsells-v317` — **Base** : main @ b7a6f11 (v3.16).
**Captures client analysées** : `Capture d'écran 2026-10-20 001939.png` (= image_9b11c4, garanties horizontales), `002311.png` (= image_9b6ae0, carrousel upsells mobile), `002431.png` (= image_9b6bbc, modale d'avis avec émoji 🌿).

---

## PHASE 1 — AUDIT

### 1. Blocs de garantie (mobile) — `.trust-grid`

**Localisation** : gabarit unique `template-parts/landing-offer.php:288-307` (les 4 landings — vedette + secondaires ; les TYP n'ont PAS de trust-strip, zéro effet de bord possible). Textes : registre `inc-offers.php` (`$T['trust']` ×4 : الدفع عند الاستلام / توصيل سريع / استبدال المقاس — ordre du markup CONSERVÉ, aucune retouche PHP).

**État actuel (cascade mesurée)** :
- Racine (toutes largeurs) : `styles.css:487` `.trust-grid { grid-template-columns: repeat(3, 1fr) }` réaffirmé par v3.8 (`:2043`) et v3.9 (`:2050`) → **3 colonnes horizontales à toute largeur** ; v3.9 (`:2051`) empile l'icône AU-DESSUS du texte (flex-direction: column + align-items: center), `:2052` centre l'icône dans sa colonne (margin-inline: auto) ; v3.8 (`:2043`) texte centré `!important`.
- Socle ≤800px (`:703-706`) et ≤540px (`:723-729`) : resserrage (padding, font-sizes 10px/8px, icône 32px).
- `:489` `.trust-item + .trust-item { border-right: 1px solid #ddd9d0 }` + `:1428` (couleur LP) → **séparateurs verticaux apparents** entre les 3 colonnes (visibles sur la capture client).

**Constat (capture 001939)** : sur mobile les 3 colonnes écrasées (~120px chacune à 390px) forcent des césures multiples, les icônes empilées gaspillent la hauteur, et les filets verticaux donnent un aspect « tableau ». Le mandat prescrit : **grille verticale 3 lignes × 2 colonnes — ligne = [petit conteneur icône centrée H+V à DROITE en RTL][texte à GAUCHE], sans bordures apparentes, mise en page purement CSS**.

**Décisions d'implémentation** (styles.css, section v3.17 en FIN de feuille — gagnante de cascade sur v3.8/v3.9 de même spécificité) :
- `@media (max-width: 540px)` = gabarit small-mobile canonique du thème (bloc ≤540px existant) ; desktop/tablette >540px **bit-à-bit inchangés** (v3.8/v3.9 préservés verbatim).
- `grid-template-columns: 1fr` + `row-gap: 18px` + `align-items: stretch` (neutralise le flex-start v3.9 qui rétrécissait les pistes) = 3 lignes pleine largeur.
- `.trust-item { flex-direction: row; align-items: center; gap: 14px; text-align: start !important }` → en RTL le premier enfant du markup (.trust-icon) se docke à DROITE, le texte à GAUCHE — **zéro retouche markup** (l'ordre « icône à droite » du mandat est obtenu par le seul sens RTL). Le `!important` contre-dépôt neutralise le `text-align: center !important` v3.8.
- `.trust-item + .trust-item { border-right: 0 }` → fin des séparateurs (le shorthand écrase aussi la couleur scopée LP :2140 ; largeur 0 = aucun filet rendu).
- `.trust-icon { margin-inline: 0; 40×40px }` (svg 20px) → neutralise le centrage colonne v3.9 (margin-inline: auto qui, en ligne, absorberait l'espace et repousserait l'icône) ; le centrage PARFAIT H+V dans le petit conteneur rond reste garanti par `display: grid; place-items: center` du socle `:490` (inchangé).

### 2. Carrousel d'upsells (mobile) — `.merci-upsell-grid`

**Localisation** : `page-merci.php:152-165` (grille desktop 3 colonnes, INCHANGÉE) + bloc ≤520px `:191-193` (carrousel Stories). Les 4 TYP partagent le gabarit.

**Constat (capture 002311 + calcul)** : `.merci-upsell-item { flex: 0 0 60%; max-width: 200px; scroll-snap-align: center }` → à 390px (contenu 338px après paddings 10px/16px) : carte 1 pleine (200px) + 126px de la carte 2 + **carte 3 intégralement hors viewport** ; scrollbar masquée (`scrollbar-width: none`), aucun peek, aucun indicateur → l'utilisateur ne peut pas savoir qu'il existe une 3ᵉ proposition.

**Correctif** (page-merci.php:202, même bloc ≤520px) : `.merci-upsell-item { flex: 0 0 42%; scroll-snap-align: start }` → cartes 1-2 pleines + **peek ≈ 30px de la 3ᵉ carte** au bord de fuite (gauche en RTL) : affordance native « glissez » (option 1 du mandat, zéro JS). Largeurs : 320px → peek ≈ 19px ; 520px → ≈ 51px. Le snap `start` (au lieu de `center`) garde le peek de la carte suivante visible pendant TOUT le défilement et évite le flottement de center-snap. `max-width: 200px` supprimé (inactif : 42% ≤ 196.6px dans tout le gabarit). Grille desktop et rail (flex/overflow/snap-type/gap) **inchangés**.

### 3. Émoji 🌿 dans la pop-up d'avis

**Occurrences trouvées (3, toutes dans le flux de la modale)** :
1. `page-merci.php:580` (natif, 4 TYP) — message après-note : « شكراً جزيلًا على تقييمك! 🌿 هل ترغبين… » (ENTRE les étoiles et le textarea = exactement la cible de la capture 002431) ;
2. `merci-review.js:432` (mock/preview, même phrase) ;
3. `merci-review.js:281` (message de modération post-envoi « سيُنشر رأيك بعد المراجعة 🌿 » — même ligne éditoriale de la même modale).

**Correctif** : suppression de l'émoji (+ espace collatéral) aux 3 emplacements → « شكراً جزيلًا على تقييمك! هل ترغبين… » et « شكراً لكِ! سيُنشر رأيك بعد المراجعة ». Texte formel et élégant, aucun autre mot modifié.

---

## RÈGLE DE NON-INTERFÉRENCE (vérifications statiques S32)

- `.merci-logo` v3.13 : bloc 7 déclarations + `translateX(25px) !important` UNIQUE (page-merci.php) — intouché.
- Logo global v3.11 (règles inline body > header.legal-header), devises v3.10 (règle `.merci-price bdi…` v3.10 verbatim), footer v3.8/v3.9 (règles verbatim), étoiles ÉTAPE 2 non-interactives v3.16 (locked + gardes + pointer-events none — re-mesurées au runtime), bande 6px #3A7D84, liseré #927936, dégradé satiné — **intouchés**.
- Règles racine v3.8/v3.9 (.trust-*) et socle ≤800px/≤540px : **verbatim** (desktop re-mesuré 1280px au runtime).
- `styles.css` Desktop : seule AJOUT la section v3.17 en fin de feuille + Version 3.17. `critical.css` : intouché.
- Markup PHP : AUCUNE retouche structurelle (2 textes émoji-only + 1 règle CSS inline page-merci.php).

## PLAN DE VALIDATION

1. Statique : `validate_offers.py` rescopée (S9.1/S24.1/S25.1/S28.7/S29.11/S30.17/S31.12 → 3.17 ; S21.1 +3.17) + **S32 nouveau** (13 invariants).
2. Runtime : `test_mobile_v317.py` NOUVEAU — LP 390/1280 (grille 3×2 mesurée : 1 piste, icône à droite, svg centré dans le rond, zéro filet ; desktop 3 pistes inchangé), TYP 390 (peek mesuré ∈ [15, 80]px + scroll requis + snap start), TYP flux complet (Étape 1 → 4 étoiles → Étape 2 : texte SANS émoji + verrous v3.16 re-mesurés → envoi → message modération SANS émoji), `.merci-logo` re-mesurée, mock générique.
3. Rescopages : `test_merci_canva_visual.py` C36 (60% → 42% + peek), régressions complètes (18 suites).
