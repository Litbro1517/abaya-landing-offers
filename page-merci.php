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
.merci-logo { width: 92px; height: 92px; object-fit: contain; display: block; margin: 0 auto 4px; }
.merci-head h1 { color: #6B1D2F; font-size: 1.28rem; line-height: 1.9; margin: 0 0 10px; }
.merci-lead { color: #8a7f75; font-size: .92rem; line-height: 2; margin: 0 auto; max-width: 470px; }
.merci-order-pill { display: inline-flex; align-items: center; gap: 8px; margin: 16px auto 0; background: #F4E6E9; color: #6B1D2F; border-radius: 999px; padding: 7px 22px; font-size: .86rem; font-weight: 600; }
.merci-order-pill strong { font-weight: 700; letter-spacing: .3px; }

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

/* ---- Bloc cadeau : bordure turquoise, code promo en pilule pointillée ---- */
.merci-gift { margin: 22px 0 0; display: flex; align-items: center; gap: 16px; background: #FBF8F3; border: 1.5px solid #4D9AA4; border-radius: 14px; padding: 16px 18px; }
.merci-gift-body { flex: 1 1 auto; min-width: 0; }
.merci-gift-title { color: #3D8A95; font-weight: 700; font-size: .95rem; margin: 0 0 5px; }
.merci-gift-desc { color: #6f655c; font-size: .85rem; line-height: 1.9; margin: 0; }
.merci-gift-desc strong { color: #4D9AA4; font-weight: 700; }
.merci-gift-code { flex: 0 0 auto; background: #fff; border: 1.5px dashed #4D9AA4; border-radius: 10px; padding: 9px 18px 10px; text-align: center; }
.merci-gift-code small { display: block; color: #8a8378; font-size: .72rem; margin: 0 0 2px; }
.merci-gift-code code { font-family: 'Cairo', Arial, sans-serif; font-weight: 700; color: #3D8A95; font-size: 1.02rem; letter-spacing: .5px; word-break: break-all; }
.merci-gift-icon { flex: 0 0 auto; width: 54px; height: 54px; border-radius: 12px; background: #E8F1F2; display: flex; align-items: center; justify-content: center; font-size: 26px; }

/* ---- معلومات المراسلة : coordonnées + bouton WhatsApp officiel ---- */
.merci-contact { display: flex; align-items: center; gap: 18px; }
.merci-contact dl { flex: 1 1 auto; margin: 0; }
.merci-contact dl > div { display: flex; gap: 6px; align-items: baseline; padding: 4px 0; }
.merci-contact dt { color: #3a2f2b; font-weight: 700; font-size: .92rem; flex: 0 0 auto; }
.merci-contact dd { margin: 0; color: #6f655c; font-size: .92rem; line-height: 1.8; }
.merci-wa-btn { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 10px; background: #25D366; color: #fff; border-radius: 12px; padding: 13px 20px; font-weight: 700; font-size: .9rem; line-height: 1.6; text-decoration: none; box-shadow: 0 6px 16px rgba(37, 211, 102, .26); transition: background .15s ease; }
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
  .merci-logo { width: 80px; height: 80px; }
  .merci-head h1 { font-size: 1.14rem; }
  .merci-sec, .merci-upsell { padding-inline: 16px; }
  .merci-order { gap: 10px; }
  .merci-order table { font-size: .8rem; }
  .merci-order thead th { padding: 8px 6px; font-size: .74rem; }
  .merci-order tbody td { padding: 10px 6px; }
  .merci-product-img { flex: 0 0 78px; }
  .merci-gift { flex-wrap: wrap; }
  .merci-gift-icon { display: none; }
  .merci-gift-code { flex: 1 1 100%; order: 3; }
  .merci-contact { flex-direction: column; align-items: stretch; }
  .merci-wa-btn { justify-content: center; }
  .merci-upsell-grid { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 12px; padding-bottom: 8px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
  .merci-upsell-grid::-webkit-scrollbar { display: none; }
  .merci-upsell-item { flex: 0 0 60%; max-width: 200px; scroll-snap-align: center; }
}
</style>
<?php
/* Mandat multi-landing + simulation dev (mock-api.js) : config exposée à
 * merci-review.js. Le mock n'agit QUE si le serveur WordPress est injoignable
 * (404/50x réseau) — compatibilité native en production. */
$abaya_mock_offers = [];
foreach (abaya_offers() as $abaya_mock_entry) {
    $abaya_mock_offers[] = [
        'id'       => $abaya_mock_entry['id'],
        'url'      => home_url('/' . $abaya_mock_entry['id'] . '/#order-form-section'),
        'headline' => $abaya_mock_entry['texts']['headline'],
        'price'    => (int) $abaya_mock_entry['bundles'][1],
        'img'      => get_template_directory_uri() . '/' . $abaya_mock_entry['images']['lcp'],
    ];
}
echo '<script>window.ABAYA_MOCK = ' . wp_json_encode([
    'reviewEndpoint' => wp_make_link_relative(rest_url('landing/v1/submit-review')),
    'assetBase'      => get_template_directory_uri() . '/uploads/offers',
    /* Maquette Canva 4PLP : logo calligraphié شكراً (WebP, bordeaux sur blanc)
     * — utilisé par le gabarit natif ET par le design injecté en mode mock. */
    'logo'           => get_template_directory_uri() . '/assets/images/merci-logo-shukran.webp',
    'offers'         => $abaya_mock_offers,
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

/* ---- Validation de l'accès au récapitulatif (aucune fuite sans clé valide) ---- */
$abaya_order     = null;
$abaya_order_id  = isset($_GET['order']) ? absint($_GET['order']) : 0;
$abaya_order_key = isset($_GET['key']) ? sanitize_text_field((string) wp_unslash($_GET['key'])) : '';

if ($abaya_order_id > 0 && $abaya_order_key !== '' && function_exists('wc_get_order')) {
    $abaya_candidate = wc_get_order($abaya_order_id);
    if ($abaya_candidate && hash_equals((string) $abaya_candidate->get_order_key(), $abaya_order_key)) {
        $abaya_created = $abaya_candidate->get_date_created();
        $abaya_age     = $abaya_created ? (time() - $abaya_created->getTimestamp()) : PHP_INT_MAX;
        if ($abaya_age <= 48 * HOUR_IN_SECONDS && $abaya_candidate->get_created_via() === 'landing-abaya') {
            $abaya_order = $abaya_candidate;
        }
    }
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

    /* Format prix de la maquette Canva : « DH 450 » (préfixe latin, chiffres
     * occidentaux) — rendu LTR stable à l'intérieur des cellules RTL. */
    $abaya_dh = function ($abaya_amount) {
        $abaya_text = ($abaya_amount == floor($abaya_amount)) ? number_format($abaya_amount, 0) : number_format($abaya_amount, 2);
        return 'DH ' . $abaya_text;
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
     * [PRÉNOM]-[VILLE]-[N°COMMANDE] (tirets moyens, sans espace). En cas de
     * saisie incomplète : valeur par défaut nettoyée depuis la base de
     * données (CLIENTE / MAROC) — le code conserve toujours 3 segments. */
    $abaya_promo_prenom = preg_replace('/[^\p{L}\p{N}]+/u', '', trim((string) $abaya_first));
    $abaya_promo_ville  = preg_replace('/[^\p{L}\p{N}]+/u', '', trim((string) $abaya_city));
    if ($abaya_promo_prenom === '') $abaya_promo_prenom = 'CLIENTE';
    if ($abaya_promo_ville === '') $abaya_promo_ville = 'MAROC';
    $abaya_promo_num    = (string) $abaya_order->get_order_number();
    /* Présentation MAJUSCULES (fidèle à la maquette Canva « RAK - ABD - 89421 ») :
     * sans effet sur l'arabe, normalise les segments latins du code. */
    $abaya_promo_code   = mb_strtoupper($abaya_promo_prenom . '-' . $abaya_promo_ville . '-' . $abaya_promo_num, 'UTF-8');

    /* Upsell (maquette Canva : EXACTEMENT 3 produits au format vertical) :
     * toutes les offres du registre SAUF celle de la commande (le registre
     * compte 4 offres → 3 verticales). Badge « تخفيض خاص » + ancien prix
     * barrés OPTIONNELS : clés de registre cross_badge / cross_old_price. */
    $abaya_cross_offers = [];
    foreach (abaya_offers() as $abaya_cross_id => $abaya_cross_entry) {
        if ($abaya_cross_id === $abaya_offer_id) continue;
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

get_header();
?>
<main class="merci-page">
  <div class="merci-card">
<?php if ($abaya_order) : ?>
  <header class="merci-head" aria-labelledby="merci-title">
    <img class="merci-logo" src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/merci-logo-shukran.webp'); ?>" alt="شكراً" width="92" height="92" decoding="async" />
    <h1 id="merci-title">تم استلام طلبكِ بنجاح! يسعدنا انضمامكِ إلينا</h1>
    <p class="merci-lead">سنتصل بكِ هاتفياً في أقرب وقت لتأكيد طلبكِ قبل الشحن. الدفع عند الاستلام، ويحق لكِ معاينة العباية قبل الدفع.</p>
    <p class="merci-order-pill"><span>رقم الطلب:</span> <strong dir="ltr">#<?php echo esc_html($abaya_order->get_order_number()); ?></strong></p>
  </header>

  <?php /* ---- تفاصيل الطلب : tableau 4 colonnes épuré + image produit à côté ---- */ ?>
  <section class="merci-sec" aria-label="تفاصيل الطلب">
    <h2 class="merci-sec-title">تفاصيل الطلب</h2>
    <div class="merci-order">
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
              <td class="merci-price"><bdi dir="ltr"><?php echo esc_html($abaya_dh($abaya_row['total'])); ?></bdi><?php if ($abaya_qty > 1) : ?><small><bdi dir="ltr"><?php echo esc_html($abaya_dh($abaya_unit)); ?></bdi> / القطعة</small><?php endif; ?></td>
            </tr>
<?php endforeach; ?>
            <tr class="merci-total-row">
              <td colspan="3">المجموع الإجمالي:</td>
              <td class="merci-price"><bdi dir="ltr"><?php echo esc_html($abaya_dh($abaya_total)); ?></bdi></td>
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
        <div><dt>الرقم الهـ:</dt><dd dir="ltr"><?php echo esc_html($abaya_phone !== '' ? $abaya_phone : '—'); ?></dd></div>
        <div><dt>العنوان:</dt><dd><?php echo esc_html($abaya_address_full !== '' ? $abaya_address_full : '—'); ?></dd></div>
      </dl>
      <a class="merci-wa-btn" href="<?php echo esc_url($abaya_wa_link); ?>" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        <span>تواصل معنا أو تابع طلبك عبر الواتساب</span>
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
?>
      <a class="merci-upsell-item" href="<?php echo esc_url(home_url('/' . $abaya_cross_id . '/#order-form-section')); ?>">
        <span class="merci-upsell-frame">
<?php if ($abaya_cross_badge !== '') : ?>
          <span class="merci-upsell-badge"><?php echo esc_html($abaya_cross_badge); ?></span>
<?php endif; ?>
          <img src="<?php echo esc_url(get_template_directory_uri() . '/' . $abaya_cross_entry['images']['lcp']); ?>" alt="<?php echo esc_attr($abaya_cross_entry['texts']['headline']); ?>" loading="lazy" decoding="async" />
        </span>
        <strong class="merci-upsell-name"><?php echo esc_html($abaya_cross_entry['texts']['headline']); ?></strong>
        <span class="merci-upsell-price"><bdi dir="ltr"><?php echo esc_html($abaya_dh((float) $abaya_cross_entry['bundles'][1])); ?></bdi><?php if ($abaya_cross_old > 0) : ?><s><bdi dir="ltr"><?php echo esc_html($abaya_dh($abaya_cross_old)); ?></bdi></s><?php endif; ?></span>
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
      <img class="merci-logo" src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/merci-logo-shukran.webp'); ?>" alt="شكراً" width="92" height="92" decoding="async" />
      <h1 id="merci-title-generic">شكراً لكِ! تم استلام طلبكِ</h1>
      <p class="merci-lead">سيتصل بكِ فريقنا هاتفياً في أقرب وقت لتأكيد المقاس واللون والكمية وعنوان التسليم. الدفع عند الاستلام، ويحق لكِ معاينة العباية قبل الدفع.</p>
    </header>
    <p class="merci-wa-line"><a class="merci-wa-btn" href="https://wa.me/<?php echo esc_attr($abaya_wa_number); ?>" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      <span>تواصلي معنا عبر الواتساب</span></a></p>
  </section>
<?php endif; ?>
  </div>

<?php /* ---- Pop-up d'évaluation « 1-clic » (inchangé — validation v2.2) ---- --
 * S'affiche dès l'arrivée sur /merci/ lorsqu'une commande active est validée.
 * Remplissage progressif (survol/clic sur la N-ième étoile → étoiles 1..N
 * allumées simultanément, sens RTL). Un clic enregistre DIRECTEMENT la note
 * en base (route REST submit-review, coordonnées de la commande attachées) ;
 * le pop-up NE SE FERME PAS instantanément : message de remerciement persistant
 * avec zone de texte OPTIONNELLE — fermeture via la croix (X), le bouton
 * valider, ou après un délai prolongé. Étoiles seules = auto-approbation
 * (comment_approved = 1) ; avis avec texte = modération (approved = 0). */ ?>
<?php if ($abaya_order) : ?>
  <div class="abaya-rating-overlay" id="abaya-rating-overlay" hidden>
    <div class="abaya-rating-modal" role="dialog" aria-modal="true" aria-labelledby="abaya-rating-title">
      <button class="abaya-rating-close" type="button" id="abaya-rating-close" aria-label="إغلاق">×</button>
      <div class="abaya-rating-emoji" aria-hidden="true">★</div>
      <h2 id="abaya-rating-title">يسعدنا كثيراً خدمتك!</h2>
      <p class="abaya-rating-text">إذا أعجبتك تجربتك معنا، نرجو منك اختيار تقييمك بنقرة واحدة لمساعدتنا على تحسين خدمتنا.</p>
      <div class="abaya-rating-stars" id="abaya-rating-stars" role="radiogroup" aria-label="اختياري التقييم بنقرة واحدة">
<?php for ($abaya_star = 1; $abaya_star <= 5; $abaya_star++) : ?>
        <button class="abaya-rating-star" type="button" role="radio" aria-checked="false" data-rating="<?= $abaya_star ?>" aria-label="<?= $abaya_star ?> من 5">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3.1-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z"/></svg>
        </button>
<?php endfor; ?>
      </div>
      <p class="abaya-rating-feedback" id="abaya-rating-feedback" role="status" hidden></p>
      <?php /* Persistance (mandat) : message de remerciement + zone de texte
       * optionnelle révélées après l'enregistrement de la note — la fermeture
       * se fait via la croix (X), le bouton valider, ou après un délai prolongé. */ ?>
      <div class="abaya-rating-after" id="abaya-rating-after" hidden>
        <p class="abaya-rating-after-msg">شكراً جزيلًا على تقييمك! 🌿 هل ترغبين في إضافة كلمة بسيطة عن تجربتك؟</p>
        <textarea class="abaya-rating-note" id="abaya-rating-note" rows="3" maxlength="2000" placeholder="اكتبي كلمة بسيطة عن تجربتكِ (اختياري)"></textarea>
        <button class="abaya-rating-send" type="button" id="abaya-rating-send">إرسال</button>
      </div>
    </div>
  </div>
<?php endif; ?>
</main>
<?php get_footer(); ?>
