# AUDIT ERGONOMIE MOBILE — v3.19 (mandat 4P LP « /app protocole mandat 4P LP »)

**Date** : 2026-10-20 · **Périmètre** : 3 points ergonomiques mobiles des Thank You Pages
**Base audité** : main @ e4a7888 (v3.18), arbre propre · **Captures client** : upload/
`Capture d'écran 2026-10-20 015930.png` (upsells), `015952.png` (code promo),
`020024.png` (tableau récap) + maquette de référence `pasted_image_1792459110943.png`
(« Option 1 : Miniature Alignée à la Ligne Produit »).

---

## PHASE 1 — AUDIT CIBLÉ : sources exactes des 3 défauts

### Point 1 — Tableau récapitulatif « تفاصيل الطلب » (capture 020024)
**Symptôme** : tableau 4 colonnes tassé à 360px + miniature produit rejetée SOUS le
tableau (vignette 120px isolée) = grand vide blanc latéral inesthétique.
**Source (prouvée)** : `page-merci.php` — le correctif v3.18 (mandat responsive
Galaxy S8) avait légitimement supprimé le débordement min-content 314px en passant
`.merci-order` en `flex-direction: column` (vignette sous le tableau) +
`table-layout: fixed` (colonnes 38/26/10/26%). Le débordement était corrigé, mais
la disposition horizontale 4 colonnes restait tassée à 360px et la miniature
se retrouvait isolée sous le tableau — exactement la capture 020024.
**Markup concerné** : `.merci-order > .merci-table > table (thead 4 th + tbody tr
×4 td + tr.merci-total-row)` + `figure.merci-product-img` (frère du .merci-table).

### Point 2 — Code promo « prochain achat » (capture 015952)
**Symptôme** : `ABDERRAZZAK-MARRAKECH-101` (26 caractères) casse sur DEUX lignes
dans la pilule pointillée (`…-1` / `01`).
**Sources (prouvées)** :
1. `page-merci.php:398-411` — closure PHP :
   `mb_strtoupper(prenom . '-' . ville . '-' . num)` → la composante VILLE
   alourdit le libellé (fallbacks CLIENTE/MAROC ×2 segments).
2. `merci-review.js:329` — mode mock (dev) :
   `[prenom, city, "#" + order_id].join("-")` — même alourdissement + préfixe « # ».
3. `page-merci.php` CSS `.merci-gift-code code { word-break: break-all; }` —
   autorise la cassage au milieu du token = rupture visuelle constatée.

### Point 3 — Carrousel d'upsells (capture 015930)
**Symptôme** : cartes tronquées aux DEUX bords (3ᵉ carte en liseré à gauche,
titre de la 1ʳᵉ coupé à droite) = illusion de bug de rendu.
**Source (prouvée)** : `page-merci.php` — bloc mobile v3.17 ≤520px :
`.merci-upsell-grid { display:flex; overflow-x:auto; scroll-snap-type:x mandatory }`
+ `.merci-upsell-item { flex: 0 0 42%; scroll-snap-align: start; }` — le « peek »
d'affordance v3.17 est perçu comme un rendu coupé (mandat actuel : suppression
demandée du défilement horizontal).

---

## PHASE 2 — CORRECTIFS (4 fichiers de production)

### Correctif 1 — Carte verticale « Miniature Alignée à la Ligne Produit » (≤520px)
Bloc v3.19 en fin de `<style id="abaya-merci">` (cascade gagnante, règles v3.18
conservées — sans objet sous `display:block`) :
- `table/thead/tbody/tr/td → display:block`, **thead masqué** ;
- **libellés de lignes** restitués par `::before` (teintes des th : #5c4f47 /
  .78rem / 600) : اللون / المقاس : · الكمية : · السعر : ;
- **bloc beige #F8F3EC** (teinte en-têtes existante = tons beiges harmonieux,
  coins arrondis 12px) regroupant **1.Produit → 2.اللون/المقاس → 3.الكمية** ;
- **miniature 84px positionnée absolument** (`top/inset-inline-start: 10px`)
  sur la ligne produit, bord droit RTL, DANS le bloc beige — espacement texte
  réservé par `padding-inline-start: 106px` (84+10+12) + `min-height: 74px`
  (garantie hauteur bloc ≥ boîte miniature 132px) ;
- classe conditionnelle PHP `merci-order-noimg` (couleur hors catalogue) :
  zéro espace réservé sans miniature ;
- **4.السعر** : ligne séparée flex `space-between` (libellé ↔ valeur) ;
- **5.المجموع الإجمالي** tout en bas : flex space-between, valeur bordeaux
  marque **#6B1D2F** (socle `.merci-price` existant) — conforme maquette.

### Correctif 2 — Code promo `[PRÉNOM]-[N°COMMANDE]`
- PHP : ville supprimée (`$abaya_promo_ville` + fallback MAROC supprimés) ;
- mock JS aligné (ville + préfixe « # » supprimés — parité stricte) ;
- CSS : `white-space: nowrap; word-break: normal;` sur le `<code>` + pilule
  `max-width: 100%` → code intégral sur UNE ligne centrée, toutes largeurs.

### Correctif 3 — Upsells empilés verticalement (≤520px)
- `.merci-upsell-grid { flex-direction: column; overflow-x: visible;
  scroll-snap-type: none; gap: 24px }` ;
- `.merci-upsell-item { flex: 0 0 auto; width: 100%; scroll-snap-align: none }`
  → image grand format 3:5 + titre + prix + bouton, empilés, pleine largeur ;
- desktop : grille 3 colonnes INCHANGÉE (règles v3.17 posées mais vaincues par
  la cascade — historique conservé).

### Non-interférence vérifiée
`.merci-logo` translateX(25px) v3.13 · étoiles Étape 2 non interactives v3.16 ·
garanties 3×2 v3.17 · modale sans émoji · devises v3.10 (bdi dir=rtl + NBSP) ·
footer v3.8/v3.9 · bande #3A7D84 + liseré #927936 · garde-fou v3.18 styles.css
(html overflow-x hidden ≤540px) — tous HORS TOUCH (bloc v3.19 scopé
`.merci-order` / `.merci-upsell-*` / `.merci-gift-code` ≤520px, sauf pilule
promo sans changement visuel desktop — code court).

---

## Validation (PHASE 3)
- `test_mobile_v319.py` NOUVEAU : 360px (carte verticale mesurée : thead masqué,
  miniature sur ligne produit, bloc beige, libellés, ordre vertical, upsells
  empilés 100%, code promo une ligne, zéro débordement scrollWidth==360,
  verrous re-mesurés) + 320px + 1280px bit-exact (table row + image à côté,
  grille 3 colonnes, pas de carte verticale).
- Régressions : 19 suites (validate_offers rescopée S32.6/S32.7/S33.14 →
  empilement vertical ; test_mobile_v317 C rescopée ; test_mobile_v318 TYP360
  rescopée — vignette sous tableau ABOLIE par la maquette mandat ;
  test_merci_canva C34/C35/C36 rescopées).
- Déploiement : push branche → merge main → WP Pusher → verify_deploy_v319.py.
