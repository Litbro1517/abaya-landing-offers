# AUDIT — RYTHME VERTICAL & ESPACEMENTS INTER-SECTIONS DES 4 LANDING PAGES (v3.21)

> Mandat 4P LP « Harmonisation des marges/paddings inter-sections — 4 landing pages ».
> Méthode : **mesure réelle Playwright** (harnais `render_landing.php` + en-tête + pied de page
> réels, styles de production) sur les **4 landings** (vedette `/` + `produit-1/2/3` — gabarit
> unique `template-parts/landing-offer.php`, `body.product-landing` sans `is-upsell` depuis v3.6)
> aux viewports **360px (Galaxy S8)** et **1280px (desktop)**.
> Script : `scripts/landing-offers-tests/audit_espacement_v321.py` (sortie horodatée ci-dessous).
> Cartographie : `docs/PROJECT_MAP.md` §5 (gabarit unique) + règles CSS `assets/css/styles.css`.

---

## 1. Cartographie CSS de l'espacement (état v3.20)

Trois strates se superposent sur les sections de landing :

| Strate | Règles | Spécificité | Effet réel |
|---|---|---|---|
| Socle | `.hero{padding-block:52px 72px}`, `.bundles-section{42px 52px}`, `.visual-section{62px + border-bottom}`, `.faq-section{55px 62px}`, `.order-guide{54px 60px}`, `.social-section{26px}`, media ≤800/≤700/≤540 (22/45, 28/42, 18/40, 35px, 39px…) | (0,1,0) | **Battues** par v3.5 |
| Bloc « Landing page corrections » | `.hero{padding-block:38px 58px}` + media ≤800 `28px 42px` + ≤700 `18px 40px` | (0,1,0) | Détermine **encore le padding-bottom du hero** (58/42/40px) |
| **v3.5 (mandat espacements)** | `.product-landing:not(.is-upsell) .hero{padding-top:26px}` + 7 sections `{padding-block:42px}` | **(0,3,0), sans media query** | **Valeur effective à TOUTES les largeurs** pour les 7 sections ; ne touche PAS le padding-bottom du hero ni le padding interne du `.trust-grid` |

Règles mortes documentées (conservées, zéro effet) : media socle ≤700 `.visual-section{39px}`,
≤540 `.bundles-section{35px}`, `.social-section{19px}`, `.faq-section{49px 55px}`,
`.contact-section{38px}` — battues par la spécificité (0,3,0) de v3.5.

**Ligne de séparation validée** : `border-bottom: 1px solid var(--line)` sur `.visual-section`
(`--line: #e2d9cc`) — posée sur les 3 sections « Caractéristiques » (couleurs / specs /
inspection) uniquement. Interdiction mandat : ne JAMAIS modifier sa couleur, son épaisseur ou
sa typologie — elle est réutilisée à l'identique.

## 2. Mesures réelles (Playwright — gap = dernier contenu d'une section → premier contenu de la suivante)

Les 4 landings rendent **identiquement** (gabarit unique + mêmes règles scopées) :

| Jonction | Mobile 360px | Desktop 1280px | Ligne de séparation |
|---|---|---|---|
| en-tête → hero | 26px (padding-top hero, **validé v3.5 — intouchable**) | 26px | — |
| hero → **Offres** | 82px (40+42) | **100px (58+42)** ← plus grand écart | **AUCUNE** |
| Offres → Caractéristiques | 84px | 84px | **AUCUNE** |
| couleurs → specs | 85px | 85px | 1px var(--line) ✔ (existante) |
| specs → inspection | 85px | 85px | 1px var(--line) ✔ (existante) |
| inspection → **Garanties** | 85px | 85px | 1px var(--line) ✔ (existante) |
| Garanties → Mode de commande | 84px | 84px | **AUCUNE** |
| Mode de commande → Avis | 84px | 84px | **AUCUNE** |
| Avis → FAQ | 84px (cartes présentes) / **108px (conteneur d'avis vide)** | idem | **AUCUNE** |
| FAQ → Contact | 84px | 84px | **AUCUNE** |
| Contact → pied de page | 42px + frontière naturelle du footer sombre (#292a25) | idem | — |

### Trous visuels identifiés (conforme au constat du mandat)

1. **« Au-dessus du titre du bloc des offres »** — jonction hero → offres : **100px desktop**
   (le padding-bottom 58px du hero hérite du bloc « Landing page corrections », jamais
   harmonisé par v3.5 qui ne pose que le padding-top) et 82px mobile, **sans ligne de
   séparation** → l'espace se lit comme un vide non structuré.
2. **« Autour de la section des garanties »** — le `.trust-grid` ajoute son padding interne
   `20px` vertical aux 42px de section : de la ligne (bas inspection) au premier contenu =
   42+1+42+**20** = **105px**, et du dernier contenu au titre suivant = **20**+42+42 = **104px**
   → les garanties sont le bloc le plus « aéré » de la page, asymétrique par rapport à tous
   les autres chapitres (42px).
3. **Avis → FAQ à 108px** — la marge haute (24px, `!important`) du conteneur d'avis
   `.reviews-list-container` reste occupée même quand le conteneur est **vide** (page sans
   avis approuvés, ex. landings secondaires) : 24+42+42 = 108px au lieu de l'étalon.
4. **Séparateur irrégulier** — la ligne 1px var(--line) n'existe qu'après les 3 sections
   visuelles (3 lignes), absente des 6 autres jonctions de chapitres : présence/absence
   sans régularité, exactement le constat du mandat.

## 3. Espacement étalon retenu

**42px / 42px** (padding-block de chaque section, soit **84px entre bords de sections,
85px lorsque la jonction porte la ligne de 1px**) — c'est le rythme **dominant mesuré
(6 jonctions sur 10 à l'identique)**, établi et validé par le mandat v3.5 (« 84px cumulés »),
le plus régulier du parcours. Les jonctions déviantes (100 / 105 / 108 / 82) sont ramenées
à l'étalon : aucune valeur nouvelle n'est inventée, l'étalon existant est répliqué.

## 4. Plan de correction (CSS uniquement — section v3.21 en FIN de feuille, cascade gagnante, scopée `.product-landing:not(.is-upsell)`)

1. **hero** : `padding-block: 26px 42px` (padding-top 26px validé v3.5 CONSERVÉ ;
   padding-bottom harmonisé 58/42/40 → **42px toutes largeurs**) → jonction hero|offres =
   84px partout ; + ligne de séparation `border-bottom: 1px solid var(--line)`.
2. **Garanties** : `.trust-grid` padding vertical **20px → 0** (l'horizontal 10px et tous
   les verrous v3.8/v3.9/v3.17 intacts) → contenu des garanties à l'étalon 42px du bord,
   comme tous les chapitres.
3. **Séparateurs réguliers** — réutilisation EXACTE de la règle validée
   `1px solid var(--line)` : `border-bottom` sur `.hero`, `.bundles-section`,
   `.social-section` ; `border-block` sur `.order-guide` (ligne en bas = jonction
   mode|avis, ligne en haut = jonction garanties|mode, car `.trust-strip` est full-bleed
   et le trait doit rester à la largeur du `.wrap` comme les 3 lignes existantes) ;
   jonction faq|contact (les deux sections sont full-bleed) : ligne `::after` absolue en
   fond de `.faq-section`, largeur calquée sur `.wrap` aux 3 paliers (min(1160px, 100%-64px)
   / min(100%-38px, 560px) ≤800px / 100%-30px ≤540px) — même position, même étendue,
   même typologie.
4. **Avis vide** : garde `.reviews-list-container:not(:has(*)) { margin-top: 0 !important }`
   — la marge 24px reste l'écart bouton→cartes quand il y a des avis (inchangé), elle ne
   crée plus de vide fantôme en l'absence de cartes → jonction avis|faq = 84px dans tous
   les cas.
5. **Sans ligne, assumé et documenté** : en-tête→hero (haut de page, 26px validé) et
   contact→pied de page (frontière naturelle du footer sombre — toute ligne claire au
  -dessus du bloc #292a25 serait un artefact visuel).

## 5. Périmètre intouché (interdictions mandat)

- **La ligne validée** : couleur `var(--line)` #e2d9cc, épaisseur 1px, typologie solid —
  réutilisée à l'identique ; les 3 lignes existantes des sections visuelles ne sont pas
  touchées (bit-exact).
- **Composants fonctionnels** : formulaire + CTA (v3.1), sticky galerie desktop (v3.5),
  verrous du logo (v3.11/v3.12/v3.13), modales d'avis (v3.14-v3.16), cartes avis + réponse
  admin (v3.7), garanties desktop v3.8/v3.9 + mobile 3×2 v3.17, footer mobile v3.8/v3.9,
  devises v3.10, garde-fou v3.18, pages réglementaires v3.20, badge étoiles, offres
  3 colonnes + formulaire 1 colonne (v3.2).
- **HTML/PHP/JS** : zéro retouche markup (séparateurs et espacements en CSS pur ;
  le `::after` du faq et la garde `:has()` n'exigent aucun changement de gabarit).
- `critical.css` (miroir above-the-fold du socle) : inchangé — les règles v3.21 scopées
  gagnent la cascade dès le chargement de styles.css.
- Règles mortes du socle : conservées (inertes, documentées §1) — aucune purge hors scope.
