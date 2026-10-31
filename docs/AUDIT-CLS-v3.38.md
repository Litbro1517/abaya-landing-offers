# AUDIT CLS — v3.38 (branche `fix/cls-v3.38`, base `origin/main` = v3.37)

PSI mobile du 31/10/2026 23:27 : Performance 80 (était 95), **CLS 0,39**, FCP 1,0 s, LCP 2,1 s, TBT 0 ms.

## 1. Cause racine (reproduite localement)
Le CSS critique inline (`assets/css/critical.css`) n'est plus identique à `assets/css/styles.css`
pour les éléments au-dessus de la ligne de flottaison. `styles.css` est chargé en asynchrone
(`media="print" onload`). Tant qu'il arrive AVANT le 1er rendu, le défaut est invisible ;
s'il arrive APRÈS, la page est repeinte avec une mise en page différente = **un décalage unique de 0,39**.

Écarts mesurés (mobile 412 px, computed styles avec / sans styles.css) :
| Élément | critical.css seul | après styles.css | Règle manquante dans critical.css |
|---|---|---|---|
| `<main>` / `.wrap` / Hero / photo | 382 px, top 116 | 362 px, top 134 | `styles.css:1752` `.product-landing main{width:calc(100% - 20px);margin:10px auto 22px;border-radius:20px}` (≤700px, v2.9) |
| `h1` | 800, hauteur 84 px | 900, hauteur 125 px (+41 px, re-wrap) | `styles.css:1321` `.product-landing .product-info h1{font-weight:900}` |
| `.hero` | padding 18/40 | 26/42 | `styles.css:2330` `.product-landing:not(.is-upsell) .hero{padding-block:26px 42px}` |
| `.stock-overlay` | en flux (30 px) | absolu, invisible | `styles.css:1261-1294` |
| `.eyebrow` | `Cairo, sans-serif` | `Cairo, Tahoma, Arial` | `styles.css:1301` |

## 2. Pourquoi la régression apparaît avec la v3.37
`assets/css/` n'a PAS été modifié par la v3.37 (diff = 0 ligne). Le défaut existait déjà en v3.36 :
mesuré **0,390 avec la v3.36 ET la v3.37** dès que `styles.css` est retardé de 1,2 s.
Ce qui a changé, c'est le **timing** : `offers.css` était une feuille bloquante qui retardait le 1er rendu
jusqu'à ce que `styles.css` (demandé en parallèle) soit arrivé ; en l'inlinant (recommandation « −130 ms »
de l'audit v3.37) le 1er rendu se fait maintenant sur le CSS critique seul → le défaut latent est exposé.
Les images plus légères accélèrent aussi le chargement et n'aident pas : elles ne sont pas en cause
(`srcset`/`sizes`, `aspect-ratio` des cadres : 0 décalage mesuré sur les images).
→ Hypothèses de la demande : galerie dynamique, `width/height` du srcset, inline `offers.css` = **écartées comme cause directe** ; l'inline n'est que le déclencheur de timing.

## 3. Preuve (banc d'essai Chromium 153, 412×823, DPR 1,75, thème rendu via PHP + stubs WP)
| Cas | CLS |
|---|---|
| v3.36, styles.css retardé 1,2 s | **0,390** (source : `MAIN` 0,98→0,108 + enfants) |
| v3.37 (main), styles.css retardé 1,2 s | **0,390** |
| v3.37, aucun retard | 0,000 (+ résidu 0,013) |
| **v3.38, styles.css retardé 1,2 s** | **0,000** |
La valeur 0,390 coïncide exactement avec le CLS PSI.

## 4. Correctif (v3.38) — `assets/css/critical.css` uniquement (+ bump style.css)
Ajout en fin de fichier des règles ci-dessus, mêmes sélecteurs/valeurs que `styles.css` → rendu final identique
(géométrie finale main vs v3.38 : identique), aucune modification PHP/JS/images/HTML.
Parité vérifiée : toutes les boîtes au-dessus de la flottaison (jusqu'à `#formulaire-commande`) ont la même géométrie avec et sans `styles.css`.

## 5. Limites / à valider
- Le lien PSI ne peut pas être lu automatiquement (rendu JS) ; analyse basée sur vos captures + reproduction locale.
- Banc d'essai ≠ PSI : stubs WordPress (pas d'avis en base, pas de Pixel). Valeur 0,39 retrouvée, mais **re-mesurer PSI sur dev**.
- Score > 95 attendu (CLS vert) mais non mesuré ; le LCP 2,1 s n'a pas été retravaillé ici.
- Résidu : ~0,013 (étoiles `.landing-rating` 26→32 px, `.product-intro`), très en dessous de 0,1.
- Dérive résiduelle SOUS la flottaison (grille d'offres, formulaire, sections visuelles) : sans effet sur le CLS PSI mais à résorber.

## 6. Recommandations
1. Régénérer `critical.css` depuis `styles.css` à chaque version (ou test CI : comparer les boîtes avec/sans styles.css à 360/412/768/1280 px, tolérance 0).
2. Ne plus ajouter de règle « above the fold » dans `styles.css` seul ; modifier les deux.
3. Alternative défensive : charger `styles.css` en bloquant (perd ~130 ms de FCP) — inutile si la parité est tenue.
