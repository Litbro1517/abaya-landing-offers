# AUDIT — MODALES D'AVIS : VERROUILLAGE STATIQUE DES ÉTOILES EN ÉTAPE 2 (v3.16)

**Mandat 4P LP** — « PHASE 1 : Audit du flux des modales d'avis (étape 1 vs étape 2) / PHASE 2 : Rectification et verrouillage de l'étape 2 (non-interactif) ».

## PHASE 1 — AUDIT DU FLUX ÉTAPE 1 VS ÉTAPE 2

### 1. Architecture du flux (état v3.15, main @ 8456452)

Le pop-up d'évaluation « 1-clic » est un composant à deux états dans une MÊME modale
(`.abaya-rating-modal`) :

- **Étape 1 (choix de la note)** : titre + texte d'invite + `radiogroup` de 5 boutons
  `.abaya-rating-star` (tracés SVG arrondis injectés par le JS, liseré fin `#927936` au
  repos, dégradé satiné + stroke none à l'état `.is-active`). Un clic enregistre
  DIRECTEMENT la note (POST REST `landing/v1/submit-review`, règle du maximum).
- **Étape 2 (pop-up message)** : la même modale reçoit la classe `.is-rated` → le texte
  d'invite disparaît (offers.css l.136), `.abaya-rating-after` s'affiche (message de
  remerciement + `textarea.abaya-rating-note` + bouton `إرسال`). Les 5 étoiles restent
  visibles au-dessus.

Sources UNIQUEMENTS responsables : markup natif `page-merci.php:564-583` + injection mock
`merci-review.js:390-407` ; logique `merci-review.js` (`bindRatingFlow`) ; CSS
`assets/css/offers.css` section 2 (zéro règle `.abaya-rating-*` ailleurs — re-vérifié par
grep sur styles.css/critical.css).

### 2. Constat : les étoiles de l'Étape 2 restent INTERACTIVES (bug des captures)

Dans `bindRatingFlow()` (merci-review.js), AUCUN des gestionnaires n'est désactivé au
passage en Étape 2 :

| # | Gestionnaire | Ligne | Effet parasite en Étape 2 |
|---|---|---|---|
| 1 | `pointerenter` par étoile | l.188 | Survoler une étoile **inférieure** à la note choisie → `hoveredRating` prend cette valeur → `renderRating()` (l.174 : `hoveredRating \|\| selectedRating`) affiche MOINS d'étoiles remplies que la note validée. **C'est exactement la capture 232827.png : 4/5 choisies en Étape 1 → 2/5 affichées dans la pop-up message** (le pointeur, en se dirigeant vers le champ de texte, a survolé la 2ᵉ étoile). |
| 2 | `click` sur le groupe | l.212 | Un clic en Étape 2 RE-SOUMET une nouvelle note (nouveau POST REST) — la note peut être modifiée après validation. |
| 3 | `focus` / `blur` par étoile | l.194-201 | La tabulation clavier prévisualise d'autres notes (les 5 boutons restent dans l'ordre de tabulation). |
| 4 | `pointerleave` du groupe | l.204 | Réinitialise la prévisualisation — incohérence d'affichage supplémentaire. |
| 5 | CSS `:hover` | offers.css l.174 | `.abaya-rating-star:hover svg { transform: scale(1.08) }` s'applique encore en Étape 2 + `cursor: pointer` (l.152) ment sur la sémantique. |

**Diagnostic** : l'Étape 2 viole le contrat attendu — les étoiles y sont un indicateur
informatif (rappel de la note validée) mais le code les traite encore comme un contrôle
actif. Le rendu peut diverger de la note validée (survol) et la note reste modifiable
(clic).

### 3. Éléments DÉJÀ CONFORMES (intouchés)

- Bandeau supérieur = bande 6px `#3A7D84` (v3.15, fournie) — conforme au maintien exigé.
- Styles visuels des étoiles : liseré fin `#927936` (vide) / dégradé satiné + stroke none
  (rempli) — conformes, aucun changement de charte.
- Étape 2 : `textarea` + bouton `إرسال` turquoise `#3A7D84` déjà actifs et fonctionnels ;
  fermetures (croix, voile, Échap, délai) = flux tunnel préservé.
- Règle du maximum côté serveur : la note renvoyée après submit = note validée — c'est
  elle que l'Étape 2 doit figer (`selectedRating` post-submit = résultat du max).

## PHASE 2 — PLAN DE RECTIFICATION (périmètre fermé : merci-review.js + offers.css)

1. **Verrouillage dur JS** (`merci-review.js`) : drapeau `locked` posé au passage en
   Étape 2 (succès du POST) — `renderRating()` ignore alors `hoveredRating` (affichage
   figé sur `selectedRating`, la note exacte validée) ; `pointerenter`/`focus`/`blur`/
   `pointerleave`/`click` court-circuités (`if (locked) return;`) — plus aucune
   prévisualisation, plus aucune re-soumission.
2. **A11y** : `tabindex = -1` + `aria-disabled="true"` sur les 5 étoiles + `aria-disabled`
   sur le groupe — les étoiles sortent de l'ordre de tabulation (seuls `textarea` et
   `إرسال` — et la croix de fermeture, flux inchangé — restent atteignables).
3. **Verrouillage dur CSS** (`offers.css`) : `.abaya-rating-modal.is-rated
   .abaya-rating-star { pointer-events: none; cursor: default; }` — aucun événement
   pointeur ne peut atteindre les étoiles en Étape 2, même si un gestionnaire futur
   réapparaissait ; neutralisation du `:hover` résiduel (scale 1.08 → 1.06, état
   statique pur) au cas où le survol était déjà posé au moment où `.is-rated` atterrit.
4. **Non-interférence stricte** : Étape 1 STRICTEMENT inchangée (survol = dégradé
   satiné fluide, clic = enregistrement direct) ; flux tunnel bit-à-bit inchangé (POST
   REST, règle du maximum, persistance textarea/إرسال, fermetures, mock) ;
   `.merci-logo` v3.13, logo global, devises, footer, socle LP v3.4 — intouchés ;
   styles.css/critical.css/page-merci.php bit-à-bit inchangés.

## Fichiers autorisés à changer

| Fichier | Changement |
|---|---|
| `merci-review.js` | drapeau `locked` + `lockStars()` + garde `renderRating` + gardes événements + a11y + commentaires v3.16 |
| `assets/css/offers.css` | bloc v3.16 verrou (2 règles scopées `.is-rated`) + commentaire |
| `style.css` | Version 3.15 → 3.16 |
| `docs/PROJECT_MAP.md` | §5-vicies-quinto + historique \| 3.16 \| |
| `docs/AUDIT-MODALES-v3.16.md` | le présent rapport |

Toute autre différence au `git diff` = régression à corriger avant push.
