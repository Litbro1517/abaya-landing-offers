<?php
/**
 * Page de remerciement dynamique /merci/ (mandat 4P — P1-1).
 *
 * WordPress associe automatiquement ce gabarit à la page de slug « merci »
 * (hiérarchie des templates : page-{slug}.php).
 *
 * INTÉGRATION MAQUETTE CANVA 4PLP (mandat « Page de Remerciement — Thank You
 * Page », maquette canva.com/design/DAHYAa7V--E) :
 *   - carte principale blanche (coins arrondis, ombre douce) sur fond crème
 *     #FAF7F2 ; logo calligraphié « شكراً » WebP en bordeaux centré TOUT EN
 *     HAUT de la carte, sur fond blanc pur ;
 *   - message de confirmation + pastille du numéro de commande (pilule rose) ;
 *   - tableau « تفاصيل الطلب » à 4 colonnes (المنتج | اللون/المقاس | الكمية |
 *     السعر) SANS cadre englobant lourd — l'image produit est alignée dans sa
 *     propre cellule-encadré à côté des informations textuelles ;
 *   - bloc cadeau « هدية خاصة لطلبك القادم! » à bordure turquoise : code promo
 *     personnalisé dans une pilule pointillée ;
 *   - bouton de suivi officiel WhatsApp (vert #25D366) à côté des coordonnées
 *     client (معلومات المراسلة) ;
 *   - section upsell « تشكيلة أخرى قد تعجبك أيضاً » : EXACTEMENT 3 produits au
 *     format vertical 3:5 (style Reels/Stories), badge « تخفيض خاص » et ancien
 *     prix barrés OPTIONNELS (clés de registre cross_badge / cross_old_price),
 *     boutons turquoise « إضافة للطلب » ;
 *   - charte graphique exclusive : police Cairo, Bordeaux #6B1D2F,
 *     Vert Turquoise #4D9AA4, Crème #FAF7F2.
 *
 * Sécurité d'accès au récapitulatif (inchangée) :
 *   - la requête doit porter ?order=ID&key=KEY ;
 *   - la clé est comparée à get_order_key() via hash_equals() (timing-safe) ;
 *   - la commande doit dater de moins de 48 h ET provenir du tunnel
 *     (« created_via = landing-abaya ») ;
 *   - sans clé valide ou au-delà de 48 h : version GÉNÉRIQUE, sans aucune
 *     donnée personnelle — un simple /merci/?order=ID n'expose rien.
 * Anti-cache : nocache_headers() + hook LiteSpeed (template_redirect,
 * functions.php) car la page affiche des données personnelles.
 */
if (!defined('ABSPATH')) exit;

/* CSS spécifique à la page merci + balise referrer — injectés dans le <head>
 * depuis ce gabarit (avant get_header()), aucune ressource externe ajoutée :
 * le score PageSpeed de la landing n'est pas affecté (page séparée). */
add_action('wp_head', function () {
?><meta name="referrer" content="no-referrer" />
<style id="abaya-merci">
/* ---- Intégration maquette Canva 4PLP : carte blanche sur fond crème,
 *      charte exclusive Cairo / Bordeaux #6B1D2F / Turquoise #4D9AA4 ---- */
body { background: #FAF7F2; }
.merci-page { max-width: 672px; margin: 0 auto; padding: 30px 14px 60px; font-family: 'Cairo', Arial, 'Helvetica Neue', 'Segoe UI', sans-serif; color: #3a2f2b; }
.merci-card { background: #fff; border-radius: 18px; box-shadow: 0 12px 42px rgba(107, 29, 47, .08); overflow: hidden; }
.merci-page h1, .merci-page h2 { font-weight: 700; }

/* ---- En-tête : logo WebP centré sur blanc pur + confirmation + pastille ---- */
.merci-head { padding: 32px 26px 28px; text-align: center; background: #fff; }
/* v3.12 (mandat 4P LP) : calligraphie شكراً GRAND FORMAT — le visuel haute
 * résolution logo-thank-you-2.webp (500×337, ratio 1.484) remplace logo.webp
 * (120×67). FIN de la contrainte miniature 92×92 : la boîte carrée + contain
 * réduisaient le rendu effectif à ≈92×52 px sur une image paysage. Désormais
 * width 240px desktop / 170px mobile, height auto = ratio source préservé
 * (zéro distorsion, object-fit: contain conservé), max-width 82% = garde-fou
 * anti-débordement petits écrans, centrage horizontal inchangé (display
 * block + margin auto). */
/* v3.13 (mandat 4P LP) : VERROUILLAGE DU CENTRAGE OPTIQUE VALIDÉ — bloc CSS
 * prescrit par le mandat, appliqué RIGOUREUSEMENT à .merci-logo sur l'ensemble
 * des 4 Thank You Pages. Les 6 déclarations v3.12 (grand format 240px, height
 * auto = ratio source préservé, garde-fou 82%, contain, block, margin auto +
 * 4px) sont reprises à l'identique ; la déclaration nouvelle — un décalage
 * horizontal de 25px vers la droite — compense la masse visuelle décentrée
 * de la calligraphie : le centrage GÉOMÉTRIQUE de la boîte (margin:0 auto)
 * devient centrage OPTIQUE du dessin.
 * La règle mobile ≤520px (width 170px, validée v3.12) reste en place et ne
 * surcharge que width/height : le verrou d'optique s'applique donc à TOUTES
 * les largeurs, zéro autre paramètre graphique touché. */
.merci-logo {
    width: 240px;
    height: auto;
    max-width: 82%;
    object-fit: contain;
    display: block;
    margin: 0 auto 4px;
    transform: translateX(25px) !important;
}
/* v3.6 (mandat 4P « Thank You Page ») : centrage PARFAIT du sous-titre et du
 * bloc explicatif sous le logo شكراً. Cause du défaut : la règle de base
 * h1 { max-width: 480px; margin: 18px 0 10px } collait la BOÎTE du titre au
 * bord droit du RTL (pas de margin-inline: auto) — le texte restait centré
 * DANS sa boîte, mais la boîte elle-même était décalée à droite. */
.merci-head h1 { color: #6B1D2F; font-size: 1.28rem; line-height: 1.9; letter-spacing: 0; max-width: none; margin: 0 auto 10px; text-align: center; }
.merci-lead { color: #8a7f75; font-size: .92rem; line-height: 2; margin: 0 auto; max-width: 470px; text-align: center; }
.merci-order-pill { display: inline-flex; align-items: center; gap: 8px; margin: 16px auto 0; background: #F4E6E9; color: #6B1D2F; border-radius: 999px; padding: 7px 22px; font-size: .86rem; font-weight: 600; }
.merci-order-pill strong { font-weight: 700; letter-spacing: .3px; }

/* ---- Bandeau de confirmation upsell (retour d'ajout à la commande) ---- */
.merci-added { display: flex; align-items: center; gap: 8px; margin: 0 26px; padding: 10px 16px; background: #E8F1F2; color: #3D8A95; border: 1px solid #4D9AA4; border-radius: 10px; font-weight: 700; font-size: .86rem; line-height: 1.7; }

/* ---- Sections internes : séparateur fin pleine largeur + titre à barre ---- */
.merci-sec { padding: 24px 26px 26px; border-top: 1px solid #F3ECE3; }
.merci-sec-title { display: flex; align-items: center; gap: 12px; color: #6B1D2F; font-size: 1.02rem; margin: 0 0 18px; }
.merci-sec-title::before { content: ""; width: 4px; height: 22px; border-radius: 4px; background: #6B1D2F; flex: 0 0 auto; }

/* ---- Tableau « تفاصيل الطلب » : 4 colonnes épurées + image produit à côté ---- */
.merci-order { display: flex; gap: 16px; align-items: stretch; }
.merci-order .merci-table { flex: 1 1 auto; min-width: 0; }
.merci-order table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.merci-order thead th { background: #F8F3EC; color: #5c4f47; font-weight: 600; font-size: .8rem; padding: 10px 9px; text-align: right; white-space: nowrap; }
.merci-order thead th:first-child { border-radius: 0 8px 8px 0; }
.merci-order thead th:last-child { border-radius: 8px 0 0 8px; text-align: left; }
.merci-order tbody td { padding: 13px 9px; border-bottom: 1px solid #F3ECE3; vertical-align: middle; }
.merci-order tbody tr:last-child td { border-bottom: 0; }
.merci-item-name { font-weight: 600; font-size: .9rem; line-height: 1.7; margin: 0; }
.merci-item-meta { color: #8a8378; font-size: .8rem; margin: 2px 0 0; line-height: 1.7; }
.merci-qty { text-align: center; color: #6f655c; }
.merci-price { text-align: left; white-space: nowrap; font-weight: 700; color: #6B1D2F; }
.merci-price small { display: block; color: #a39a8c; font-size: .74rem; font-weight: 400; margin-top: 2px; }
.merci-total-row td { font-weight: 700; color: #3a2f2b; padding-top: 15px; border-bottom: 0; }
.merci-total-row .merci-price { font-size: 1.02rem; }
.merci-product-img { flex: 0 0 104px; align-self: flex-start; margin: 0; border: 1px solid #F0EAE1; border-radius: 12px; background: #FAF7F2; overflow: hidden; aspect-ratio: 3 / 4; }
.merci-product-img img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* ---- Bloc cadeau : bordure turquoise, code promo en pilule pointillée ----
 * v3.6 (mandat 4P) : disposition en COLONNE — le texte de l'offre s'étale
 * fluidement sur toute la largeur du bloc (fin de la compression à droite)
 * et l'encadré du code promo passe SOUS le texte explicatif, centré en bas
 * du bloc. La pastille décorative (déjà masquée ≤520px) disparaît : le
 * cadeau 🎁 reste porté par le titre lui-même. */
.merci-gift { margin: 22px 0 0; display: flex; flex-direction: column; align-items: stretch; gap: 12px; background: #FBF8F3; border: 1.5px solid #4D9AA4; border-radius: 14px; padding: 16px 18px; }
.merci-gift-body { min-width: 0; }
.merci-gift-title { color: #3D8A95; font-weight: 700; font-size: .95rem; margin: 0 0 5px; }
.merci-gift-desc { color: #6f655c; font-size: .85rem; line-height: 1.9; margin: 0; }
.merci-gift-desc strong { color: #4D9AA4; font-weight: 700; }
.merci-gift-code { flex: 0 0 auto; align-self: center; background: #fff; border: 1.5px dashed #4D9AA4; border-radius: 10px; padding: 9px 18px 10px; text-align: center; }
.merci-gift-code small { display: block; color: #8a8378; font-size: .72rem; margin: 0 0 2px; }
.merci-gift-code code { font-family: 'Cairo', Arial, sans-serif; font-weight: 700; color: #3D8A95; font-size: 1.02rem; letter-spacing: .5px; word-break: break-all; }
.merci-gift-icon { display: none; }

/* ---- معلومات المراسلة : coordonnées + bouton WhatsApp officiel ---- */
.merci-contact { display: flex; align-items: center; gap: 18px; }
.merci-contact dl { flex: 1 1 auto; margin: 0; }
.merci-contact dl > div { display: flex; gap: 6px; align-items: baseline; padding: 4px 0; }
.merci-contact dt { color: #3a2f2b; font-weight: 700; font-size: .92rem; flex: 0 0 auto; }
.merci-contact dd { margin: 0; color: #6f655c; font-size: .92rem; line-height: 1.8; }
/* v3.6 (mandat 4P) : bouton raccourci (« تابع طلبك عبر الواتساب ») et
 * compacté (13px / 10px 16px) pour laisser l'espace nécessaire au libellé
 * complet du téléphone dans la grille de coordonnées. */
.merci-wa-btn { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 10px; background: #25D366; color: #fff; border-radius: 12px; padding: 10px 16px; font-weight: 700; font-size: 13px; line-height: 1.6; text-decoration: none; box-shadow: 0 6px 16px rgba(37, 211, 102, .26); transition: background .15s ease; }
.merci-wa-btn:hover { background: #1FB958; }
.merci-wa-btn svg { width: 22px; height: 22px; fill: #fff; flex: 0 0 auto; }
.merci-wa-line { text-align: center; margin: 4px 0 0; }
.merci-wa-line .merci-wa-btn { flex: 0 1 auto; max-width: 100%; }

/* ---- Upsell : EXACTEMENT 3 produits, format vertical 3:5 (Reels/Stories) ---- */
.merci-upsell { padding: 26px 26px 30px; border-top: 1px solid #F3ECE3; text-align: center; }
.merci-upsell-title { color: #6B1D2F; font-size: 1.1rem; margin: 0 0 4px; }
.merci-upsell-sub { color: #9a9086; font-size: .85rem; margin: 0 0 20px; }
.merci-upsell-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px 16px; }
.merci-upsell-item { position: relative; display: flex; flex-direction: column; text-decoration: none; color: inherit; }
.merci-upsell-frame { position: relative; border-radius: 14px; overflow: hidden; aspect-ratio: 3 / 5; background: #DFE9EA; }
.merci-upsell-frame img { width: 100%; height: 100%; object-fit: cover; display: block; transition: opacity .15s ease; }
.merci-upsell-badge { position: absolute; top: 10px; inset-inline-end: 10px; background: #6B1D2F; color: #fff; font-size: .7rem; font-weight: 600; padding: 3px 12px 4px; border-radius: 999px; z-index: 1; }
.merci-upsell-name { font-weight: 700; font-size: .9rem; color: #3a2f2b; line-height: 1.7; margin: 12px 0 0; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.merci-upsell-price { margin: 3px 0 12px; font-size: .95rem; color: #6B1D2F; font-weight: 700; }
.merci-upsell-price s { color: #b3a99e; font-size: .8rem; font-weight: 400; margin-inline-start: 8px; }
.merci-upsell-btn { display: block; width: 100%; background: #4D9AA4; color: #fff; border-radius: 10px; padding: 11px 10px; font-size: .88rem; font-weight: 700; transition: background .15s ease; }
.merci-upsell-item:hover .merci-upsell-btn { background: #3D7F88; }
.merci-upsell-item:hover .merci-upsell-frame img { opacity: .95; }

/* ---- Pied de carte ---- */
.merci-foot { border-top: 1px solid #F3ECE3; background: #FBF9F5; color: #9a9086; text-align: center; font-size: .78rem; padding: 14px 20px; }

/* ---- Version générique (sans clé valide) ---- */
.merci-generic .merci-head { border-top: 0; }
.merci-generic .merci-wa-line { padding: 6px 26px 28px; border-top: 1px solid #F3ECE3; }

/* ---- Mobile : tableau resserré, cadeau empilé, upsell en carrousel Stories ---- */
@media (max-width: 520px) {
  .merci-page { padding: 20px 10px 48px; }
  .merci-head { padding: 26px 16px 22px; }
  .merci-logo { width: 170px; height: auto; } /* v3.12 : grand format mobile (ratio préservé) */
  .merci-head h1 { font-size: 1.14rem; }
  .merci-added { margin: 0 16px; font-size: .8rem; }
  .merci-sec, .merci-upsell { padding-inline: 16px; }
  .merci-order { gap: 10px; }
  .merci-order table { font-size: .8rem; }
  .merci-order thead th { padding: 8px 6px; font-size: .74rem; }
  .merci-order tbody td { padding: 10px 6px; }
  .merci-product-img { flex: 0 0 78px; }
  /* v3.6 : overrides cadeau supprimés — la disposition colonne (texte pleine
   * largeur + code centré) s'applique désormais à TOUTES les largeurs. */
  .merci-contact { flex-direction: column; align-items: stretch; }
  .merci-wa-btn { justify-content: center; }
  .merci-upsell-grid { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 12px; padding-bottom: 8px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
  .merci-upsell-grid::-webkit-scrollbar { display: none; }
  /* v3.17 : peek de la 3ᵉ carte — les cartes passent de 60% (2 visibles,
   * la 3ᵉ totalement masquée, aucune affordance) à 42% : cartes 1 et 2
   * pleines + ~30px de la 3ᵉ carte VISIBLES au bord de fuite (gauche en
   * RTL) = signal d'affordance natif « il y a la suite, glissez » ;
   * scroll-snap-align: center → start pour un rythme de snap constant
   * (chaque carte s'aligne au bord de départ, le peek de la suivante
   * reste visible pendant tout le défilement). Largeurs : 320px →
   * peek ≈ 19px ; 390px → ≈ 30px ; 520px → ≈ 51px. Le max-width 200px
   * devient inutile (42% ≤ 196.6px dans tout le gabarit ≤520px). */
  .merci-upsell-item { flex: 0 0 42%; scroll-snap-align: start; }
}

/* ================= v3.11 — MANDAT 4P LP : CENTRAGE DU LOGO GLOBAL =================
 * Sélecteur visé (audit docs/AUDIT-LOGO-v3.11.md) : body > header > div > span > img
 * = sur les TYP : .legal-header > .logo-wrapper > .footer-wordmark > .header-logo-svg.
 * CAUSE DU DÉCALAGE (alignement à droite en RTL, PROUVÉE) : .legal-header est
 * display:flex + justify-content:space-between (styles.css:623) et son enfant UNIQUE
 * .logo-wrapper est flex:0 0 auto (styles.css:633) → docké au début de l'axe
 * principal = bord DROIT en RTL. Sur /merci/ il n'y a ni bouton retour ni lien
 * légal (abaya_upsell_context() exige un slug offre → false sur « merci »).
 * CORRECTIF : le wrapper OCCUPE la ligne (flex:1 1 auto, vainqueur de
 * flex:0 0 auto) — son justify-content:center natif (styles.css:276) centre
 * réellement le logo — puis prescription littérale du mandat sur l'img
 * (display:block + margin:0 auto). Logo STRICTEMENT non-cliquable conservé
 * (branche tunnel de legal-header.php = <span>, jamais <a>). ZÉRO interférence :
 * ce style inline n'existe QUE sur les TYP — pages légales et LP secondaires
 * (qui partagent le partial avec un 2e enfant) restent bit-à-bit inchangées. */
body > header.legal-header > div.logo-wrapper {
  flex: 1 1 auto !important;
  justify-content: center !important;
}
body > header.legal-header > div.logo-wrapper > span.footer-wordmark > img.header-logo-svg {
  display: block !important;
  margin: 0 auto !important;
}
/* ================= fin v3.11 ================= */

/* ================= v3.18 — RESTAURATION RESPONSIVE MOBILE (Galaxy S8 / 360px) =================
 * CAUSE RACINE (prouvée par mesure, diag_responsive_v318.py sur v3.17) :
 * scrollWidth document = 454px CONSTANT à 360/375/390 → débordement +94px
 * à 360px, tout le bloc principal (h1, adresse, sous-titre upsell) déborde
 * au-delà du bord gauche RTL = rendu « type bureau » écrasé/panoramiqué.
 * DÉCOMPOSITION 454 : tableau .merci-order table min-content 314px
 * (en-têtes white-space:nowrap × 4 colonnes, un tableau ne descend JAMAIS
 * sous son min-content même avec width:100%) + vignette 78px + gap 10px
 * = 402px de min-content pour .merci-order, propagé par le corps flex
 * column body.legal-page (styles.css:622) → main.merci-page 454px.
 * Défaut PRÉEXISTANT (mesuré 454/375 dès l'audit docs/AUDIT-LOGO-v3.11.md,
 * documenté « hors périmètre, mandat dédié recommandé ») — v3.17 innocent.
 * CORRECTIF ≤520px uniquement (desktop >520px BIT-À-BIT INCHANGÉ) :
 * 1) la vignette passe SOUS le tableau (flex-direction column) → le
 *    tableau respire sur ~308px pleins au lieu de 220px ;
 * 2) table-layout:fixed → la largeur du tableau = 100% du conteneur
 *    STRICTEMENT (fin du min-content 314px, garantie à 320px aussi) ;
 * 3) en-têtes wrappables (white-space:normal) + overflow-wrap:break-word
 *    sur cellules → zéro débordement possible quel que soit le contenu ;
 * 4) distribution fixe des colonnes 38/26/10/26% (produit / couleur-mqas /
 *    quantité / prix). Le nowrap socle de .merci-price est NEUTRALISÉ ≤520px :
 *    seul le token montant+درهم (bdi, soudé par espace insécable) reste
 *    nowrap — la ligne <small> du prix unitaire se replie librement (sinon
 *    « 249.50 درهم / القطعة » = 119px insécables) — et overflow-wrap:anywhere
 *    sur la cellule prix = soupape (un token pathologique se brise au lieu de
 *    déborder). Structure 4 colonnes, contenus et structure des prix
 *    INCHANGÉS (convention v3.9 respectée). */
@media (max-width: 520px) {
  .merci-order { flex-direction: column; gap: 12px; }
  .merci-product-img { flex: 0 0 auto; width: 120px; }
  .merci-order table { table-layout: fixed; }
  .merci-order thead th { white-space: normal; }
  .merci-order thead th, .merci-order tbody td { overflow-wrap: break-word; }
  .merci-order thead th:nth-child(1) { width: 38%; }
  .merci-order thead th:nth-child(2) { width: 26%; }
  .merci-order thead th:nth-child(3) { width: 10%; }
  .merci-order thead th:nth-child(4) { width: 26%; }
  .merci-order .merci-price, .merci-order .merci-price small { white-space: normal; }
  .merci-order .merci-price bdi { white-space: nowrap; }
  .merci-order .merci-price { overflow-wrap: anywhere; }
}
/* ================= fin v3.18 ================= */

/* ================= v3.19 — ERGONOMIE MOBILE : RÉCAP VERTICAL « MINIATURE ALIGNÉE À LA LIGNE PRODUIT » + CODE PROMO COURT + UPSELLS EMPILÉS =================
 * (mandat 4P LP — audit docs/AUDIT-ERGONOMIE-v3.19.md, captures client
 * 015930 carrousel tronqué / 015952 code promo cassé / 020024 vignette
 * isolée sous le tableau tassé.)
 * 1) TABLEAU « تفاصيل الطلب » → CARTE VERTICALE (maquette « Option 1 :
 *    Miniature Alignée à la Ligne Produit ») : v3.18 avait supprimé le
 *    débordement (table-layout fixed + vignette SOUS le tableau) mais la
 *    miniature restait isolée sous un tableau 4 colonnes tassé = grand vide
 *    blanc latéral. ≤520px : thead masqué, lignes empilées AVEC libellés
 *    (::before), bloc beige #F8F3EC (teinte des en-têtes existante — tons
 *    beiges harmonieux) regroupant 1.Produit(+miniature) 2.اللون/المقاس
 *    3.الكمية, miniature positionnée ABSOLUMENT sur la ligne produit (bord
 *    droit RTL, espacement réservé par padding-inline-start), 4.السعر
 *    séparé, 5.المجموع الإجمالي tout en bas, valeur en bordeaux marque
 *    #6B1D2F (couleur de mise en valeur). Desktop >520px BIT-À-BIT
 *    INCHANGÉ (les règles v3.18 restent posées — sans objet sous
 *    display:block — et l'invariant S33.13 racine est conservé).
 * 2) CODE PROMO : pilule `white-space: nowrap` (fin du word-break:break-all
 *    qui cassait le code sur 2 lignes) — la ville est supprimée À LA SOURCE
 *    (closure PHP + mock merci-review.js) : format [PRÉNOM]-[N°COMMANDE].
 * 3) UPSELLS : fin du carrousel horizontal coupé (cartes tronquées aux deux
 *    bords, capture 015930) — empilement VERTICAL, chaque carte occupe 100%
 *    de la largeur (image grand format 3:5, titre, prix, bouton empilés).
 *    Desktop : grille 3 colonnes INCHANGÉE. Les règles v3.17 (42% + snap)
 *    restent posées mais sont vaincues par la cascade (section plus bas). */

/* --- 2) code promo : une seule ligne centrée (toutes largeurs) --- */
.merci-gift-code { max-width: 100%; }
.merci-gift-code code { white-space: nowrap; word-break: normal; }

@media (max-width: 520px) {
  /* --- 1) carte verticale du récapitulatif --- */
  .merci-order { position: relative; }
  .merci-order table, .merci-order tbody, .merci-order tr, .merci-order td { display: block; }
  .merci-order thead { display: none; }
  /* libellés de lignes (les en-têtes thead étant masqués) — mêmes teintes
   * que les th (#5c4f47 / .78rem / 600) pour une harmonie parfaite */
  .merci-order tbody td::before { font-weight: 600; font-size: .78rem; color: #5c4f47; }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(2)::before { content: "اللون / المقاس:"; }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(3)::before { content: "الكمية:"; }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(4)::before { content: "السعر:"; }
  /* bloc beige : Produit + Couleur/Taille + Quantité (coins arrondis) */
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(-n+3) { background: #F8F3EC; border-bottom: 0; text-align: start; }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(1) {
    border-radius: 12px 12px 0 0;
    padding: 14px 106px 6px 14px; /* start 106px = miniature 84px + inset 10px + gap 12px */
    min-height: 74px; /* garantit la hauteur du bloc ≥ boîte miniature (84×4/3 + 2×10 = 132px) */
  }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(2),
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(3) { padding: 4px 106px 4px 14px; }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(3) { border-radius: 0 0 12px 12px; padding-bottom: 14px; }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(1) .merci-item-name { color: #6B1D2F; }
  /* commande multi-lignes (ajout upsell) : respiration entre les blocs */
  .merci-order tbody tr:not(.merci-total-row):not(:first-child) td:nth-child(1) { margin-top: 12px; }
  /* miniature alignée sur la ligne produit : bord droit RTL (start), DANS
   * le bloc beige — remplace la vignette v3.18 isolée sous le tableau */
  .merci-order .merci-product-img {
    position: absolute; top: 10px; inset-inline-start: 10px;
    width: 84px; flex: none; margin: 0;
  }
  /* sans visuel (couleur hors catalogue) : pas de miniature → espacement
   * normal (la classe conditionnelle évite tout vide réservé) */
  .merci-order.merci-order-noimg tbody tr:not(.merci-total-row) td:nth-child(1) { padding-inline: 14px; min-height: 0; }
  .merci-order.merci-order-noimg tbody tr:not(.merci-total-row) td:nth-child(2),
  .merci-order.merci-order-noimg tbody tr:not(.merci-total-row) td:nth-child(3) { padding-inline: 14px; }
  /* 4.السعر : ligne séparée libellé ↔ valeur */
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(4) {
    display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline;
    padding: 13px 2px 14px; margin-top: 14px;
  }
  .merci-order tbody tr:not(.merci-total-row) td:nth-child(4) small { flex: 1 1 100%; }
  /* 5.المجموع الإجمالي tout en bas (valeur bordeaux marque par socle .merci-price) */
  .merci-order tbody tr.merci-total-row { display: flex; justify-content: space-between; align-items: baseline; padding: 6px 2px 2px; }
  .merci-order tbody tr.merci-total-row td { padding: 2px 0; }
  .merci-order tbody tr.merci-total-row td.merci-price { color: #6B1D2F; /* couleur de mise en valeur de la marque (maquette) — bat #3a2f2b du socle */ }

  /* --- 3) upsells empilés verticalement (100% largeur, fin du carrousel) --- */
  .merci-upsell-grid { flex-direction: column; overflow-x: visible; scroll-snap-type: none; gap: 24px; padding-bottom: 0; }
  .merci-upsell-item { flex: 0 0 auto; width: 100%; scroll-snap-align: none; }
}
/* ================= fin v3.19 ================= */
</style>
<?php
/* Mandat multi-landing + simulation dev (mock-api.js) : config exposée à
 * merci-review.js. Le mock n'agit QUE si le serveur WordPress est injoignable
 * (404/50x réseau) — compatibilité native en production.
 * La liste des offres est construite APRÈS résolution de la commande (bas de
 * ce gabarit, stockée en $GLOBALS['abaya_mock_offers']) — une closure PHP
 * n'hérite pas de la portée du gabarit : même pattern que ratingCtx. Les
 * liens upsell du mock portent ainsi le même duo order+key que le rendu réel
 * (déverrouillage cohérent des landings secondaires, mandat ⑭). */
echo '<script>window.ABAYA_MOCK = ' . wp_json_encode([
    'reviewEndpoint' => wp_make_link_relative(rest_url('landing/v1/submit-review')),
    'assetBase'      => get_template_directory_uri() . '/uploads/offers',
    /* v3.12 (mandat 4P LP) : visuel haute résolution logo-thank-you-2.webp
     * (500×337 — remplace logo.webp 120×67, taille d'affichage revue à la
     * hausse via CSS) — utilisé par le gabarit natif ET par le design injecté
     * en mode mock (merci-review.js lit ce registre). */
    'logo'           => get_template_directory_uri() . '/assets/images/logo-thank-you-2.webp',
    'offers'         => isset($GLOBALS['abaya_mock_offers']) ? $GLOBALS['abaya_mock_offers'] : [],
    /* FIX mandat 4P LP (déclenchement pop-up) : une closure PHP n'hérite pas
     * de la portée du gabarit — $abaya_rating_ctx défini plus bas dans ce
     * fichier était invisible ici (ratingCtx toujours null → le pop-up
     * 1-clic ne s'ouvrait jamais automatiquement). Lecture via $GLOBALS,
     * le gabarit s'exécutant à la portée globale (template-loader WP). */
    'ratingCtx'      => isset($GLOBALS['abaya_rating_ctx']) ? $GLOBALS['abaya_rating_ctx'] : null,
]) . ';</script>' . "\n";
echo '<script type="module" src="' . esc_url(get_template_directory_uri() . '/merci-review.js?ver=' . abaya_asset_ver('merci-review.js')) . '"></script>' . "\n";
}, 6);

/* Numéro WhatsApp du service client (identique à la landing). */
$abaya_wa_number = '212698738664';

/* ---- Validation de l'accès au récapitulatif (aucune fuite sans clé valide) ----
 * Tunnel post-achat (mandat ⑭) : la garde est le résolveur PARTAGÉ
 * abaya_resolve_tunnel_order() — hash_equals timing-safe + < 48 h +
 * created_via = landing-abaya — consommé également par le contexte upsell
 * des landings secondaires et la route REST add-to-order. */
$abaya_order     = null;
$abaya_order_id  = isset($_GET['order']) ? absint($_GET['order']) : 0;
$abaya_order_key = isset($_GET['key']) ? sanitize_text_field((string) wp_unslash($_GET['key'])) : '';

if ($abaya_order_id > 0 && $abaya_order_key !== '') {
    $abaya_order = function_exists('abaya_resolve_tunnel_order') ? abaya_resolve_tunnel_order($abaya_order_id, $abaya_order_key) : null;
}

if ($abaya_order) {
    /* ---- Données du récapitulatif (commande validée par sa clé) ---- */
    $abaya_first = $abaya_order->get_billing_first_name();
    $abaya_last  = $abaya_order->get_billing_last_name();
    $abaya_phone = $abaya_order->get_billing_phone();
    $abaya_addr  = trim((string) $abaya_order->get_billing_address_1());
    $abaya_addr2 = trim((string) $abaya_order->get_billing_address_2());
    $abaya_city  = $abaya_order->get_billing_city();
    $abaya_country = $abaya_order->get_billing_country();
    if ($abaya_country !== '') {
        $abaya_countries = function_exists('WC') && isset(WC()->countries) ? WC()->countries->get_countries() : [];
        $abaya_country   = isset($abaya_countries[$abaya_country]) ? $abaya_countries[$abaya_country] : $abaya_country;
    }
    $abaya_address_full = implode('، ', array_filter([$abaya_addr, $abaya_addr2, $abaya_city, $abaya_country], function ($abaya_part) {
        return (string) $abaya_part !== '';
    }));

    /* Mandat multi-landing : offre d'origine de la commande (_abaya_offer,
     * alias _abaya_offer_id) — pilote l'upsell, le catalogue de la couleur
     * affichée et le contexte du pop-up d'évaluation 1-clic.
     * Commandes historiques (méta absente) = offre vedette. */
    $abaya_offer_id = (string) $abaya_order->get_meta('_abaya_offer');
    if ($abaya_offer_id === '') $abaya_offer_id = (string) $abaya_order->get_meta('_abaya_offer_id');
    if (!abaya_offer_config($abaya_offer_id)) $abaya_offer_id = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
    $abaya_offer = abaya_offer_config($abaya_offer_id);

    /* Couleur / taille : méta de commande (_abaya_color/_abaya_size) puis repli
     * sur la ligne d'article (اللون/المقاس) — les deux sources sont remplies
     * par le handler REST depuis le mandat 4P. */
    $abaya_color = (string) $abaya_order->get_meta('_abaya_color');
    $abaya_size  = (string) $abaya_order->get_meta('_abaya_size');
    if ($abaya_color === '' || $abaya_size === '') {
        foreach ($abaya_order->get_items() as $abaya_item) {
            if ($abaya_color === '') $abaya_color = (string) $abaya_item->get_meta('اللون');
            if ($abaya_size === '')  $abaya_size  = (string) $abaya_item->get_meta('المقاس');
        }
    }

    $abaya_total       = (float) $abaya_order->get_total();
    $abaya_total_text  = ($abaya_total == floor($abaya_total)) ? number_format($abaya_total, 0) : number_format($abaya_total, 2);

    /* Format prix — devise en toutes lettres en arabe « 299 درهم » (mandat 4P
     * LP v3.10 « Alignement RTL strict » — audit comparatif docs/
     * AUDIT-DEVISE-TYP-v3.10.md) : montant PUIS درهم, espace insecable
     * U+00A0 (fin du collement « درهم299 »), rendu identique à la LP —
     * le montant est LU en premier et درهم s'affiche à sa GAUCHE dans les
     * cellules RTL (parité LP : texte brut bidi naturel). Les unités de prix
     * restent isolées dans <bdi dir="rtl"> (5 points d'appel — lignes + unité
     * + total du récapitulatif + prix/ancien prix upsell) ; le forçage LTR
     * v3.8/v3.9 (« درهم 299 ») est aboli, y compris côté CSS (styles.css
     * v3.10) et côté chemin JS (merci-review.js dh() alignée). Tableau
     * récapitulatif ET blocs d'upsell ; les landing pages ne sont PAS
     * concernées (déjà conformes). */
    $abaya_dh = function ($abaya_amount) {
        $abaya_text = ($abaya_amount == floor($abaya_amount)) ? number_format($abaya_amount, 0) : number_format($abaya_amount, 2);
        return $abaya_text . "\u{00A0}درهم";
    };

    /* Visuel de la couleur choisie : chemin issu du catalogue DE CETTE OFFRE
     * (adaptateur abaya_catalog($offer_id) — source unique). */
    $abaya_cat        = abaya_catalog($abaya_offer_id);
    $abaya_color_img  = '';
    if (isset($abaya_cat['colors'][$abaya_color]['image'])) {
        $abaya_color_img = get_template_directory_uri() . '/' . $abaya_cat['colors'][$abaya_color]['image'];
    }

    /* Tableau récapitulatif : ligne(s) d'article (nom, quantité, prix unitaire
     * dérivé et total de ligne) — le tunnel landing crée UNE ligne unique
     * portant le prix du pack sélectionné. */
    $abaya_rows = [];
    foreach ($abaya_order->get_items() as $abaya_item) {
        $abaya_rows[] = [
            'name'     => (string) $abaya_item->get_name(),
            'qty'      => (int) $abaya_item->get_quantity(),
            'total'    => (float) $abaya_item->get_total(),
        ];
    }

    $abaya_wa_text = rawurlencode('مرحباً، أود متابعة طلبي رقم #' . $abaya_order->get_order_number());
    $abaya_wa_link = 'https://wa.me/' . $abaya_wa_number . '?text=' . $abaya_wa_text;

    /* Code promo « prochain achat » (mandat 4P) : format strict
     * [PRÉNOM]-[N°COMMANDE] — v3.19 (mandat 4P LP, audit
     * docs/AUDIT-ERGONOMIE-v3.19.md) : la composante VILLE est supprimée
     * (« ABDERRAZZAK-MARRAKECH-101 » = 26 caractères débordait sur deux
     * lignes dans la pilule pointillée ≤520px, capture client 015952) ;
     * ex. « ABDERRAZZAK-101 ». En cas de saisie incomplète : valeur par
     * défaut nettoyée depuis la base de données (CLIENTE) — le code
     * conserve toujours 2 segments. */
    $abaya_promo_prenom = preg_replace('/[^\p{L}\p{N}]+/u', '', trim((string) $abaya_first));
    if ($abaya_promo_prenom === '') $abaya_promo_prenom = 'CLIENTE';
    $abaya_promo_num    = (string) $abaya_order->get_order_number();
    /* Présentation MAJUSCULES : sans effet sur l'arabe, normalise les
     * segments latins du code. */
    $abaya_promo_code   = mb_strtoupper($abaya_promo_prenom . '-' . $abaya_promo_num, 'UTF-8');

    /* Upsell (maquette Canva : EXACTEMENT 3 produits au format vertical) :
     * toutes les offres du registre SAUF celles déjà présentes dans la
     * commande (mandat ⑭ : l'offre d'origine ET les offres ajoutées en upsell
     * via le méta de ligne _abaya_upsell_offer disparaissent des cartes — le
     * tableau récapitulatif devient la source de vérité, aucun produit déjà
     * acheté n'est re-proposé). Badge « تخفيض خاص » + ancien prix barrés
     * OPTIONNELS : clés de registre cross_badge / cross_old_price. */
    $abaya_purchased_offers = [$abaya_offer_id];
    foreach ($abaya_order->get_items() as $abaya_item) {
        $abaya_item_offer = (string) $abaya_item->get_meta('_abaya_upsell_offer');
        if ($abaya_item_offer !== '' && isset(abaya_offers()[$abaya_item_offer])) $abaya_purchased_offers[] = $abaya_item_offer;
    }
    $abaya_cross_offers = [];
    foreach (abaya_offers() as $abaya_cross_id => $abaya_cross_entry) {
        if (in_array($abaya_cross_id, $abaya_purchased_offers, true)) continue;
        if ($abaya_offer['cross_sell'] !== [] && !in_array($abaya_cross_id, $abaya_offer['cross_sell'], true)) continue;
        $abaya_cross_offers[$abaya_cross_id] = $abaya_cross_entry;
    }

    /* Contexte du pop-up d'évaluation 1-clic : coordonnées de la commande
     * active (prénom + ville + clés de commande) attachées à la note
     * enregistrée — order_id/order_key alimentent la règle du maximum et la
     * modération intelligente côté REST. */
    $abaya_rating_ctx = [
        'order_id' => $abaya_order->get_id(),
        'order_key' => $abaya_order->get_order_key(),
        'offer'    => $abaya_offer_id,
        'name'     => $abaya_first !== '' ? $abaya_first : 'عميلة',
        'city'     => $abaya_city !== '' ? $abaya_city : 'المغرب',
    ];
}

/* Config mock (dev) : construite APRÈS résolution de la commande et stockée
 * en $GLOBALS (lue par la closure wp_head au-dessus) — les liens upsell du
 * mock portent le duo order+key réel quand une commande est validée. La
 * carte de l'offre vedette vise la RACINE (aucune page /accueil/ — 404). */
$abaya_front_slug = defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil';
$abaya_mock_offers = [];
foreach (abaya_offers() as $abaya_mock_entry) {
    $abaya_mock_base = ($abaya_mock_entry['id'] === $abaya_front_slug) ? home_url('/') : home_url('/' . $abaya_mock_entry['id'] . '/');
    $abaya_mock_url  = $abaya_order
        ? add_query_arg(['order' => $abaya_order->get_id(), 'key' => $abaya_order->get_order_key()], $abaya_mock_base) . '#order-form-section'
        : $abaya_mock_base . '#order-form-section';
    $abaya_mock_offers[] = [
        'id'       => $abaya_mock_entry['id'],
        'url'      => $abaya_mock_url,
        'headline' => $abaya_mock_entry['texts']['headline'],
        'price'    => (int) $abaya_mock_entry['bundles'][1],
        'img'      => get_template_directory_uri() . '/' . $abaya_mock_entry['images']['lcp'],
    ];
}
$GLOBALS['abaya_mock_offers'] = $abaya_mock_offers;

get_header();
?>
<main class="merci-page">
  <div class="merci-card">
<?php if ($abaya_order) : ?>
  <header class="merci-head" aria-labelledby="merci-title">
    <?php /* v3.12 : visuel haute résolution logo-thank-you-2.webp 500×337
           (remplace logo.webp 120×67) — grand format CSS (240px desktop /
           170px mobile), height auto + object-fit contain = zéro distorsion,
           centrage conservé ; width/height = ratio naturel (anti-CLS). */ ?>
    <img class="merci-logo" src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-thank-you-2.webp'); ?>" alt="شكراً" width="240" height="162" decoding="async" />
    <h1 id="merci-title">تم استلام طلبكِ بنجاح! يسعدنا انضمامكِ إلينا</h1>
    <p class="merci-lead">سنتصل بكِ هاتفياً في أقرب وقت لتأكيد طلبكِ قبل الشحن. الدفع عند الاستلام، ويحق لكِ معاينة العباية قبل الدفع.</p>
    <p class="merci-order-pill"><span>رقم الطلب:</span> <strong dir="ltr">#<?php echo esc_html($abaya_order->get_order_number()); ?></strong></p>
  </header>

<?php /* Tunnel post-achat (mandat ⑭) : retour d'une ajout upsell réussi —
       la commande a été complétée, le récapitulatif ci-dessous est recalculé
       serveur depuis la même commande (même duo order+key). */ ?>
<?php if (!empty($_GET['added'])) : ?>
  <div class="merci-added" role="status"><span aria-hidden="true">✓</span> تمت إضافة المنتج إلى طلبكِ بنجاح — المجموع المحدَّث أدناه</div>
<?php endif; ?>

  <?php /* ---- تفاصيل الطلب : tableau 4 colonnes épuré + image produit à côté ---- */ ?>
  <section class="merci-sec" aria-label="تفاصيل الطلب">
    <h2 class="merci-sec-title">تفاصيل الطلب</h2>
    <div class="merci-order<?php echo $abaya_color_img ? '' : ' merci-order-noimg'; /* v3.19 : sans miniature (couleur hors catalogue), zéro espace réservé */ ?>">
      <div class="merci-table">
        <table>
          <thead>
            <tr><th scope="col">المنتج</th><th scope="col">اللون / المقاس</th><th scope="col">الكمية</th><th scope="col">السعر</th></tr>
          </thead>
          <tbody>
<?php foreach ($abaya_rows as $abaya_row) :
        $abaya_qty        = max(1, $abaya_row['qty']);
        $abaya_unit       = $abaya_row['total'] / $abaya_qty;
        $abaya_row_name   = $abaya_row['name'] !== '' ? $abaya_row['name'] : (string) $abaya_offer['name'];
?>
            <tr>
              <td><p class="merci-item-name"><?php echo esc_html($abaya_row_name); ?></p></td>
              <td class="merci-qty"><?php echo esc_html($abaya_color !== '' ? $abaya_color : '—'); ?> / <?php echo esc_html($abaya_size !== '' ? $abaya_size : '—'); ?></td>
              <td class="merci-qty"><?php echo esc_html($abaya_qty); ?></td>
              <td class="merci-price"><bdi dir="rtl"><?php echo esc_html($abaya_dh($abaya_row['total'])); ?></bdi><?php if ($abaya_qty > 1) : ?><small><bdi dir="rtl"><?php echo esc_html($abaya_dh($abaya_unit)); ?></bdi> / القطعة</small><?php endif; ?></td>
            </tr>
<?php endforeach; ?>
            <tr class="merci-total-row">
              <td colspan="3">المجموع الإجمالي:</td>
              <td class="merci-price"><bdi dir="rtl"><?php echo esc_html($abaya_dh($abaya_total)); ?></bdi></td>
            </tr>
          </tbody>
        </table>
      </div>
<?php if ($abaya_color_img) : ?>
      <figure class="merci-product-img">
        <img src="<?php echo esc_url($abaya_color_img); ?>" alt="العباية باللون <?php echo esc_attr($abaya_color); ?>" loading="lazy" decoding="async" />
      </figure>
<?php endif; ?>
    </div>

    <?php /* ---- Bloc cadeau : code promo personnalisé (bordure turquoise) ---- */ ?>
    <aside class="merci-gift" aria-labelledby="merci-gift-title">
      <div class="merci-gift-body">
        <p class="merci-gift-title" id="merci-gift-title">🎁 هدية خاصة لطلبكِ القادم!</p>
        <p class="merci-gift-desc">استلمي كود الخصم المخصص لكِ للحصول على <strong>خصم 20 درهم</strong> لكل عباية في طلبكِ القادم — لكِ أو لقريباتكِ.</p>
      </div>
      <div class="merci-gift-code">
        <small>كود الخصم الخاص بكِ</small>
        <code dir="ltr"><?php echo esc_html($abaya_promo_code); ?></code>
      </div>
      <div class="merci-gift-icon" aria-hidden="true">🎁</div>
    </aside>
  </section>

  <?php /* ---- معلومات المراسلة : coordonnées + bouton WhatsApp officiel ---- */ ?>
  <section class="merci-sec" aria-label="معلومات المراسلة">
    <h2 class="merci-sec-title">معلومات المراسلة</h2>
    <div class="merci-contact">
      <dl>
        <div><dt>الاسم:</dt><dd><?php echo esc_html(trim($abaya_first . ' ' . $abaya_last) !== '' ? trim($abaya_first . ' ' . $abaya_last) : '—'); ?></dd></div>
        <div><dt>الرقم الهاتفي:</dt><dd dir="ltr"><?php echo esc_html($abaya_phone !== '' ? $abaya_phone : '—'); ?></dd></div>
        <div><dt>العنوان:</dt><dd><?php echo esc_html($abaya_address_full !== '' ? $abaya_address_full : '—'); ?></dd></div>
      </dl>
      <a class="merci-wa-btn" href="<?php echo esc_url($abaya_wa_link); ?>" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        <span>تابع طلبك عبر الواتساب</span>
      </a>
    </div>
  </section>

  <?php /* ---- Upsell maquette Canva : EXACTEMENT 3 produits, vertical 3:5 (Reels/Stories) ---- */ ?>
<?php if (!empty($abaya_cross_offers)) : ?>
  <section class="merci-upsell" aria-labelledby="merci-upsell-title">
    <h2 class="merci-upsell-title" id="merci-upsell-title">تشكيلة أخرى قد تعجبكِ أيضاً</h2>
    <p class="merci-upsell-sub">أضيفي عباية أخرى لنستفيدي من الخصم المباشر في طلبكِ</p>
    <div class="merci-upsell-grid">
<?php foreach ($abaya_cross_offers as $abaya_cross_id => $abaya_cross_entry) :
        $abaya_cross_badge = isset($abaya_cross_entry['cross_badge']) ? (string) $abaya_cross_entry['cross_badge'] : '';
        $abaya_cross_old   = isset($abaya_cross_entry['cross_old_price']) ? (float) $abaya_cross_entry['cross_old_price'] : 0.0;
        /* Mandat ⑭ : la carte de l'offre vedette vise la RACINE (aucune page
         * /accueil/ — l'ancien href /accueil/ était un 404) ; le duo order+key
         * déverrouille le mode upsell de la landing ciblée. */
        $abaya_cross_base = ($abaya_cross_id === (defined('ABAYA_FRONT_OFFER') ? ABAYA_FRONT_OFFER : 'accueil')) ? home_url('/') : home_url('/' . $abaya_cross_id . '/');
?>
      <a class="merci-upsell-item" href="<?php echo esc_url(add_query_arg(['order' => $abaya_order->get_id(), 'key' => $abaya_order->get_order_key()], $abaya_cross_base) . '#order-form-section'); ?>">
        <span class="merci-upsell-frame">
<?php if ($abaya_cross_badge !== '') : ?>
          <span class="merci-upsell-badge"><?php echo esc_html($abaya_cross_badge); ?></span>
<?php endif; ?>
          <img src="<?php echo esc_url(get_template_directory_uri() . '/' . $abaya_cross_entry['images']['lcp']); ?>" alt="<?php echo esc_attr($abaya_cross_entry['texts']['headline']); ?>" loading="lazy" decoding="async" />
        </span>
        <strong class="merci-upsell-name"><?php echo esc_html($abaya_cross_entry['texts']['headline']); ?></strong>
        <span class="merci-upsell-price"><bdi dir="rtl"><?php echo esc_html($abaya_dh((float) $abaya_cross_entry['bundles'][1])); ?></bdi><?php if ($abaya_cross_old > 0) : ?><s><bdi dir="rtl"><?php echo esc_html($abaya_dh($abaya_cross_old)); ?></bdi></s><?php endif; ?></span>
        <span class="merci-upsell-btn">إضافة للطلب</span>
      </a>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
  <footer class="merci-foot">© <?php echo esc_html(date_i18n('Y')); ?> Abaya Collection — جميع الحقوق محفوظة</footer>
<?php else : ?>
  <?php /* Version générique : sans clé valide (ou commande > 48 h), aucune donnée personnelle n'est affichée.
         Le conteneur mock reste disponible pour la prévisualisation locale (mock-api.js). */ ?>
  <div id="mock-thanks-container" hidden></div>
  <section class="merci-generic" data-generic-thanks aria-labelledby="merci-title-generic">
    <header class="merci-head">
      <img class="merci-logo" src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-thank-you-2.webp'); ?>" alt="شكراً" width="240" height="162" decoding="async" />
      <h1 id="merci-title-generic">شكراً لكِ! تم استلام طلبكِ</h1>
      <p class="merci-lead">سيتصل بكِ فريقنا هاتفياً في أقرب وقت لتأكيد المقاس واللون والكمية وعنوان التسليم. الدفع عند الاستلام، ويحق لكِ معاينة العباية قبل الدفع.</p>
    </header>
    <p class="merci-wa-line"><a class="merci-wa-btn" href="https://wa.me/<?php echo esc_attr($abaya_wa_number); ?>" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      <span>تواصلي معنا عبر الواتساب</span></a></p>
  </section>
<?php endif; ?>
  </div>

<?php /* ---- Pop-up d'évaluation « 1-clic » (v3.15 — système d'étoiles interactives fournies) ---- --
 * S'affiche dès l'arrivée sur /merci/ lorsqu'une commande active est validée.
 * v3.15 (mandat 4P LP) : le code des étoiles est remplacé par le système
 * interactif fourni (index.html / styles.css / app.js) — boutons VIDES dans le
 * markup (les tracés SVG arrondis + dégradé satiné sont injectés par
 * merci-review.js), liseré fin #927936 au repos, remplissage en dégradé satiné
 * au survol/clic, bandeau supérieur = bande div 6px vert turquoise #3A7D84 ;
 * l'ancienne étoile centrale du cercle est supprimée (absente du
 * design fourni). Le flux tunnel est INCHANGÉ : un clic enregistre DIRECTEMENT
 * la note en base (route REST submit-review, coordonnées de la commande
 * attachées) ; le pop-up NE SE FERME PAS instantanément : message de
 * remerciement persistant avec zone de texte OPTIONNELLE — fermeture via la
 * croix (X), le bouton valider, ou après un délai prolongé. Étoiles seules =
 * auto-approbation (comment_approved = 1) ; avis avec texte = modération
 * (approved = 0). */ ?>
<?php if ($abaya_order) : ?>
  <div class="abaya-rating-overlay" id="abaya-rating-overlay" hidden>
    <div class="abaya-rating-modal" role="dialog" aria-modal="true" aria-labelledby="abaya-rating-title">
      <div class="abaya-rating-band" aria-hidden="true"></div>
      <button class="abaya-rating-close" type="button" id="abaya-rating-close" aria-label="إغلاق">×</button>
      <h2 id="abaya-rating-title">يسعدنا كثيراً خدمتك!</h2>
      <p class="abaya-rating-text">إذا أعجبتك تجربتك معنا، نرجو منك اختيار تقييمك بنقرة واحدة لمساعدتنا على تحسين خدمتنا.</p>
      <div class="abaya-rating-stars" id="abaya-rating-stars" role="radiogroup" aria-label="اختياري التقييم بنقرة واحدة">
<?php for ($abaya_star = 1; $abaya_star <= 5; $abaya_star++) : ?>
        <button class="abaya-rating-star" type="button" role="radio" aria-checked="false" data-rating="<?= $abaya_star ?>" aria-label="<?= $abaya_star ?> من 5"></button>
<?php endfor; ?>
      </div>
      <p class="abaya-rating-feedback" id="abaya-rating-feedback" role="status" hidden></p>
      <?php /* Persistance (mandat) : message de remerciement + zone de texte
       * optionnelle révélées après l'enregistrement de la note — la fermeture
       * se fait via la croix (X), le bouton valider, ou après un délai prolongé. */ ?>
      <div class="abaya-rating-after" id="abaya-rating-after" hidden>
        <p class="abaya-rating-after-msg">شكراً جزيلًا على تقييمك! هل ترغبين في إضافة كلمة بسيطة عن تجربتك؟</p>
        <textarea class="abaya-rating-note" id="abaya-rating-note" rows="3" maxlength="2000" placeholder="اكتبي كلمة بسيطة عن تجربتكِ (اختياري)"></textarea>
        <button class="abaya-rating-send" type="button" id="abaya-rating-send">إرسال</button>
      </div>
    </div>
  </div>
<?php endif; ?>
</main>
<?php get_footer(); ?>
