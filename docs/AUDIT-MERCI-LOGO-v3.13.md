# AUDIT — VERROUILLAGE DU CENTRAGE OPTIQUE DE LA CALLIGRAPHIE شكراً (v3.13)

**Mandat 4P LP — PHASE 1 : Audit et vérification de la règle CSS / PHASE 2 : Implémentation
du style CSS validé (périmètre FERMÉ — 1 sélecteur `.merci-logo`, 4 Thank You Pages).**

Élément audité : `body > main > div.merci-card > header > img.merci-logo`
(l'en-tête de la carte est `.merci-head` — classe `merci-card` portée par le
conteneur blanc, sélection `div.merci-card > header` conforme au libellé mandat).

---

## 1. PHASE 1 — ÉTAT DE LA RÈGLE CSS À L'OUVERTURE DU MANDAT (v3.12 @ 181610c)

### 1.1 Inventaire exhaustif des contraintes s'appliquant à `img.merci-logo`

| # | Règle / attribut | Localisation | Valeur constatée |
|---|------------------|--------------|------------------|
| 1 | `.merci-logo` (règle de base, desktop + toutes largeurs) | page-merci.php:63 — bloc inline `<style id="abaya-merci">` (chargé sur TYP seules) | `width: 240px; height: auto; max-width: 82%; object-fit: contain; display: block; margin: 0 auto 4px;` |
| 2 | `.merci-logo` (mobile ≤520px) | page-merci.php:159 — `@media (max-width: 520px)` | `width: 170px; height: auto;` — ne surcharge QUE `width`/`height` |
| 3 | Attributs HTML de rendu | page-merci.php:403 (vue commande) + :518 (vue générique) | `width="240" height="162"` (CLS, ratio naturel 500×337) |
| 4 | Attributs HTML (vue mock) | merci-review.js:270 (lit `config.logo`, registre PHP `ABAYA_MOCK.logo`) | `width="240" height="162"` |
| 5 | Source image | `assets/images/logo-thank-you-2.webp` — 500×337, 29 742 o, RIFF valide | déployée v3.12 |

Chaîne de rendu vérifiée : les 4 Thank You Pages (origines accueil / produit-1 /
produit-2 / produit-3) partagent le gabarit unique `page-merci.php` (vue
commande + vue générique) et le chemin mock `merci-review.js` (design injecté,
source unique `config.logo`) — toute modification de la règle CSS `.merci-logo`
du bloc `abaya-merci` couvre donc automatiquement l'ensemble des 4 pages.

### 1.2 Diagnostic — écart entre l'état v3.12 et le bloc CSS validé du mandat

Le bloc prescrit par le mandat :

```css
.merci-logo {
    width: 240px;
    height: auto;
    max-width: 82%;
    object-fit: contain;
    display: block;
    margin: 0 auto 4px;
    transform: translateX(25px) !important;
}
```

Comparaison déclarative avec la règle v3.12 (|1| ci-dessus) :

| Déclaration | v3.12 | Bloc validé | Verdict |
|---|---|---|---|
| `width: 240px` | ✔ | ✔ | identique |
| `height: auto` | ✔ | ✔ | identique |
| `max-width: 82%` | ✔ | ✔ | identique |
| `object-fit: contain` | ✔ | ✔ | identique |
| `display: block` | ✔ | ✔ | identique |
| `margin: 0 auto 4px` | ✔ | ✔ | identique |
| `transform: translateX(25px) !important` | ✖ ABSENTE | ✔ | **SEUL ÉCART — l'objet du mandat** |

**Conclusion PHASE 1** : le centrage GÉOMÉTRIQUE de la boîte était correct
(`display: block; margin: 0 auto` → Δ = 0.00 px mesuré v3.12), mais la
calligraphie « شكراً » possède une masse visuelle décentrée à l'intérieur de
sa boîte englobante : le dessin paraissait décalé alors que la boîte était
parfaitement centrée. Le mandat verrouille le **centrage OPTIQUE validé** par
`translateX(25px)`. Aucune règle `transform` concurrente n'existe dans le bloc
`abaya-merci` (vérifié : zéro occurrence avant correctif) — le `!important`
est appliqué verbatim tel que prescrit.

### 1.3 Analyse de cascade (point de vigilance mobile)

La règle mobile `@media (max-width: 520px)` (|2|) arrive APRÈS la règle de
base dans l'ordre source et ne surcharge que `width`/`height` :

- **Desktop (>520px)** : le bloc validé s'applique intégralement
  (240px, garde-fou 82%, translateX(25px)).
- **Mobile (≤520px)** : `width: 170px` (v3.12, validée et déployée) gagne sur
  la largeur ; **toutes les autres déclarations du bloc validé — dont le
  verrou d'optique `translateX(25px)` et `margin: 0 auto 4px` — restent
  actives** (non surchargées par la media query).

Décision d'implémentation (non-interférence stricte) : le bloc validé est
appliqué **verbatim** comme règle de base ; la media query mobile v3.12 est
CONSERVÉE telle quelle (paramètre graphique déjà validé — l'agrandir ou la
supprimer constituerait une modification hors mandat). Le verrou d'optique du
mandat est ainsi effectif sur les 4 TYP à TOUTES les largeurs.

Mesure d'insertion mobile (contrôle anti-débordement) : à 390px, contenu
`.merci-head` ≈ 338px → boîte 170px centrée (marges ≈ 84px) puis décalée
+25px → bord droit ≈ 279px < 338px : **aucun débordement induit**. À 1280px :
contenu ≈ 620px, boîte 240px, marges ≈ 190px, bord droit ≈ 455px < 620px :
aucun débordement. (Débordement préexistant documenté v3.11 —
`.merci-order table` × `body.legal-page` — hors périmètre, non touché.)

---

## 2. PHASE 2 — IMPLÉMENTATION (PÉRIMÈTRE FERMÉ)

| # | Fichier | Modification |
|---|---------|--------------|
| 1 | page-merci.php:73-81 | règle de base `.merci-logo` = **bloc validé appliqué verbatim** (7 déclarations, ordre du mandat, `transform: translateX(25px) !important` en dernière position) + commentaire v3.13 ; la règle mobile ≤520px (170px) et les attributs CLS ×3 sont INCHANGÉS |
| 2 | style.css | `Version: 3.12` → `3.13` |
| 3 | docs/PROJECT_MAP.md | §5-vicies-bis + historique \| 3.13 \| |
| 4 | docs/AUDIT-MERCI-LOGO-v3.13.md | le présent rapport |

**Zéro autre retouche** : ni PHP hors règle CSS, ni JS, ni attributs HTML,
ni `styles.css`, ni garanties v3.9, ni footer v3.8-v3.9, ni devise v3.10,
ni avis v3.7, ni prix/structure, ni logo global v3.11 (section centrage
intacte, non-cliquable) — conformément à l'interdiction formelle du mandat
(« ne modifier aucun autre paramètre graphique du tunnel »).

## 3. PLAN DE VALIDATION (AUTO-AUDIT AVANT PUSH)

1. `php -l page-merci.php` (syntaxe) ;
2. `validate_offers.py` — rescopage des assertions de version (S9.1/S21.1/
   S24.1/S25.1 → 3.13) + S27.4 rescopée (le littéral mono-ligne v3.12 devient
   le bloc multi-lignes v3.13) + **NOUVEAU bloc S28** (invariants v3.13 :
   bloc verbatim, unicité du translateX, continuité mobile 170px, CLS ×3,
   audit doc, PROJECT_MAP, continuités v3.11/v3.12) ;
3. `test_logo_v313.py` **NOUVEAU** — 4 origines × 1280/390 + vue générique :
   transform calculé = `matrix(1,0,0,1,25,0)` (offset optique exact +25px),
   centrage LAYOUT Δ ≤ 3px (mesure indépendante du transform), marginBottom
   4px calculé, grand format 240px desktop / 170px mobile, ratio 1.484,
   contain ≤82%, non-débordement du shift, non-interférence logo global v3.11 ;
4. `test_logo_v312.py` **rescopée** — la mesure de centrage (getBoundingClientRect)
   inclut désormais l'offset optique prescrit : assertion déplacée sur la
   composante LAYOUT (soustraction du matrix m41 calculé) ;
5. Régressions complètes (13 suites) puis captures desktop/mobile.
