# AUDIT — MODALES D'AVIS v3.15 — INTÉGRATION DU SYSTÈME D'ÉTOILES INTERACTIVES FOURNI

**Mandat** : `/app protocole mandat 4P LP` — « Remplacer l'ancien code des pop-ups
d'évaluation sur les 4 Thank You Pages par les logiques fournies dans index.html,
styles.css et app.js » — étoiles vides à liseré fin au repos, étoiles remplies en
dégradé satiné fluide au survol et au clic, bandeau supérieur maintenu en vert
turquoise CTA `#3A7D84`.

**Périmètre restreint** : l'intérieur du pop-up d'évaluation 1-clic uniquement.
Règle stricte de non-interférence : logo header, `.merci-logo` (translateX(25px)),
devises, footer, flux tunnel (POST REST, règle du maximum, persistance,
textarea/إرسال), LP, garanties, avis, prix — INTACTS.

---

## PHASE 1 — AUDIT

### 1. Vérification des fichiers fournis

| Fichier | Contenu vérifié | Verdict |
|---|---|---|
| `index.html` | Structure de la modale : `.review-modal` > `.modal-band` (bandeau) + `.close-button` + `.modal-content` (titre `h1#review-title`, invite `#review-hint`, conteneur `.rating` role=group avec **5 `.star-button` VIDES** porteurs de `data-rating`, statut `.rating-status` aria-live=polite). Aucun SVG inline dans le markup — les boutons sont remplis par le script. | ✅ conforme |
| `styles.css` | Charte des états : **état vide** = `fill: transparent` + liseré fin `stroke: #927936` (width 1.8, linejoin round) ; **état rempli** = `fill: var(--star-gradient)` + `stroke: none` + scale 1.06 ; hover scale 1.08 ; **bandeau de modale `.modal-band { height: 6px; background: #3A7D84; }`** ; modale radius 17px, bord `#e2dfd5`, `overflow: hidden`, ombre douce `0 18px 56px rgb(48 42 27 / 9%)` ; close-button 34px cercle `#f5f3ed` ×25px ; focus-visible `#3A7D84` ; transitions 150ms ; `prefers-reduced-motion`. | ✅ conforme |
| `app.js` | **Génération des tracés SVG arrondis** : `createRoundedStarPath()` — 10 sommets (rayons 21 / 9.4), coins adoucis par courbes quadratiques `Q` (rayons de coin 2.2 / 1.1) — l'arrondi naturel est DANS LA GÉOMÉTRIE du tracé, plus aucun stroke déformant. `starSvg(gradientId)` : `<radialGradient>` satiné or (`#f5dfa7` 0% → `#dfbd72` 42% → `#c7973d` 78% → `#a87824` 100%, cx 50% cy 42% r 72%) injecté par bouton (`gold-fill-<n>`), viewBox 48×48. **Gestion dynamique des événements** : `pointerenter` (souris/plume uniquement — le tactile ne survole pas), `focus`/`blur`, `click`, `pointerleave` du groupe ; `renderRating()` — `visible = hovered || selected` → classe `is-active` + `aria-pressed`. | ✅ conforme |

⚠️ **Anomalie détectée dans `app.js` fourni** : accolade `}` orpheline en fin de
fichier (ligne 86 — résidu du wrapper de la page de démonstration). Elle rend le
script invalide tel quel ; elle est **neutralisée à l'intégration** (le corps du
script est repris verbatim, sans le résidu de wrapper).

### 2. Audit du code actuel des modales (cible du remplacement)

Architecture confirmée : pop-up d'évaluation 1-clic `.abaya-rating-overlay` >
`.abaya-rating-modal` — markup natif `page-merci.php:558-581` (gabarit unique des
4 TYP, rendu si commande valide) + injection mock identique `merci-review.js:315-336`
(vue générique prévisualisation). CSS source unique `assets/css/offers.css`
section 2 (les sélecteurs `.abaya-rating-*` n'existent dans AUCUNE autre feuille —
zéro règle dans styles.css / critical.css / blocs inline : scopé TYP de facto).

Quatre règles à remplacer (état v3.14) :

| # | Règle actuelle | Remplacement v3.15 (fourni) |
|---|---|---|
| 1 | Bandeau = `border-top: 4px solid #3A7D84` sur la modale | **Bande div dédiée `.abaya-rating-band` height 6px + background #3A7D84** (miroir `.modal-band` fourni) — même teinte turquoise CTA, implémentation fournie |
| 2 | Étoile centrale du cercle `.abaya-rating-emoji` (glyph ★, `#c0a758 !important` v3.14) | **SUPPRIMÉE** — absente du design fourni ; la rangée des 5 étoiles porte seule la notation |
| 3 | 5 boutons avec SVG inline 24×24 à arêtes vives + état vide GRIS (`fill #e7e1d6` / `stroke #d8d0c2`) + états JS `.is-hot`/`.is-picked` (`fill/stroke #c0a758 !important`) | **Boutons VIDES** (le markup ne porte plus aucun SVG) remplis par `merci-review.js` avec les tracés arrondis 48×48 + dégradé satiné fournis ; **état vide = liseré fin `#927936`** ; **état rempli = dégradé satiné + `stroke: none`** ; états JS unifiés `.is-active` |
| 4 | Close-button 34px, bord `#ded8ce`, fond `#fff`, ×18px, top 10px | **Close-button fourni** : 34px cercle sans bord, fond `#f5f3ed`, ×25px, top 18px, focus `#3A7D84` |

### 3. Flux tunnel — HORS périmètre, préservé (non-interférence)

Le remplacement porte sur le **code des étoiles et l'habillage** de la modale.
Le flux métier validé depuis v2.2 reste bit-à-bit identique :

- **1-clic = enregistrement direct** : POST REST `landing/v1/submit-review`
  (coordonnées de la commande attachées) — étoiles seules = auto-approbation
  (`comment_approved = 1`), avis avec texte = modération ;
- **Règle du maximum** : le serveur renvoie la note conservée,
  l'affichage s'aligne sur `max(note_existante, nouvelle_note)` ;
- **Persistance** : la modale ne se ferme pas après la note — message de
  remerciement + textarea optionnelle + bouton إرسال (turquoise `#3A7D84`
  déjà conforme), fermeture via croix / voile / Échap / délai prolongé ;
- **Mode mock** : repli localStorage répliquant la règle du maximum.

### 4. Adaptations d'intégration documentées (fourni → thème)

1. **Espace de noms** : les classes fournies (`.review-modal`, `.modal-band`,
   `.star-button`, `.rating-status`, `.close-button`) sont mappées sur le
   namespace existant `.abaya-rating-*` (`.abaya-rating-modal`,
   `.abaya-rating-band`, `.abaya-rating-star`, `.abaya-rating-feedback`,
   `.abaya-rating-close`) — zéro risque de collision globale dans le thème
   (sélecteurs nus `h1`, `*` du fichier de démo jamais copiés).
2. **Textes arabes RTL conservés** (titre « يسعدنا كثيراً خدمتك! », invite,
   message de remerciement, aria-labels « N من 5 ») — la démo fournie est
   française mais l'audience TYP est arabophone ; la typographie fournie
   (letter-spacing −0.035em, Inter) est spécifique au latin et **s'applique
   mal à la graphie cursive arabe** — les styles de texte actuels sont conservés.
3. **A11y** : la sémantie `role="radiogroup"` / `role="radio"` /
   `aria-checked` (validée v2.2) est conservée ; le rendu fourni pose
   `aria-pressed` sur des boutons simples — l'équivalent sémantique exact
   (`aria-checked` sur l'étoile choisie uniquement) est appliqué.
4. **Largeur de modale** : 700px dans la démo (page plein écran) ;
   `min(420px, 100%)` conservé — modale compacte au-dessus du contenu TYP,
   ergonomie mobile validée (390px : rangée ≈247px < 308px utiles).
5. **Interactions** : la délégation `mouseover`/`mouseleave` est remplacée par
   le pattern fourni par bouton (`pointerenter` filtré souris/plume,
   `focus`/`blur`, `pointerleave` du groupe) — le tactile n'allume qu'au clic.
6. **Sens RTL** : le remplissage 1..N suit le sens de lecture arabe
   (étoiles DOM 1→5 rendues de droite à gauche) — inchangé, la logique fournie
   `index < visible` produit exactement ce comportement en contexte RTL.
7. **JS injecte les SVG** : `createRoundedStarPath()` + `starSvg()` portés
   verbatim dans `merci-review.js` (module ES existant) ; le `}` orphelin du
   fichier fourni est écarté.

### 5. Déjà-conformes intouchés

- `.abaya-rating-send` background `#3A7D84` + hover `#2D6A70` ;
- `.abaya-rating-note:focus` border `#3A7D84` ;
- overlay (voile `rgba(47,42,36,.45)` + blur) + animation d'entrée ;
- `.merci-logo` v3.13 (bloc validé verbatim + `translateX(25px) !important`) ;
- socle LP `.review-star #d4af37` (upsell v2.9) + règle v3.4 `#c0a758`
  (styles.css — HORS offers.css, bit-à-bit intacts).

## PHASE 2 — PLAN D'IMPLÉMENTATION CHIRURGICALE

1. `page-merci.php` — markup : bande div, suppression emoji, boutons d'étoiles
   VIDES (les SVG arrivent du JS), close-button conservé (id/aria-label) ;
2. `merci-review.js` — port verbatim `createRoundedStarPath`/`starSvg`,
   `renderRating` (hovered/selected), événements fournis, **flux tunnel
   inchangé** (POST/max/persistance), injection mock mise au même gabarit ;
3. `assets/css/offers.css` — section 2 réécrite selon la charte fournie
   scopée `.abaya-rating-*` (band 6px `#3A7D84`, liseré `#927936`, dégradé
   satiné `stroke: none`, close `#f5f3ed`, focus `#3A7D84`, reduced-motion) ;
4. `style.css` → Version 3.15 ; PROJECT_MAP §5-vicies-quater + historique ;
5. Validation : test_modales_v315.py (statique + 4 origines × 1280/390 + flux
   réel REST intercepté + générique), validate_offers.py rescopée (S9.1, S21.1,
   S24.1, S25.1, S28.7, S29 adapté + nouveau bloc S30), rescopages
   test_thankyou_redesign (is-active) + logo v312/v313 (tuple Version) +
   verify_deploy_v314 (rescopée), auto-audit visuel mobile/desktop, régressions
   complètes, puis push branche → merge main → WP Pusher.
