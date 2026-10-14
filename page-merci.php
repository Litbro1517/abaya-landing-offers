<?php
/**
 * Page de remerciement dynamique /merci/ (mandat 4P — P1-1).
 *
 * WordPress associe automatiquement ce gabarit à la page de slug « merci »
 * (hiérarchie des templates : page-{slug}.php).
 *
 * Refonte UI/UX (mandat 4P LP « Thank You Page Redesign ») :
 *   - présentation minimaliste et aérée (typographie fine Cairo / Arial /
 *     System Sans-Serif), zéro bloc massif : les informations de confirmation
 *     sont purement informatives et restreintes à l'essentiel ;
 *   - récapitulatif de commande en TABLEAU à deux colonnes (Produit | Prix)
 *     à séparateurs fins, miniature intégrée ;
 *   - grille client compacte à 2 colonnes sans boîtes imposantes ;
 *   - section upsell SANS cadres : visuels libres sur fond neutre, texte
 *     fluide, bouton d'achat direct discret.
 *
 * Sécurité d'accès au récapitulatif :
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
/* ---- Refonte épurée (mandat 4P LP) : typographie fine, aération, zéro cadre lourd ---- */
.merci-page { max-width: 640px; margin: 0 auto; padding: 34px 16px 64px; font-family: 'Cairo', Arial, 'Helvetica Neue', 'Segoe UI', sans-serif; color: #2f2a24; }
.merci-page h1, .merci-page h2 { font-weight: 600; }

/* ---- En-tête & message de confirmation ---- */
.merci-head { text-align: center; }
.merci-check { width: 44px; height: 44px; margin: 0 auto 16px; border-radius: 50%; border: 1px solid #e3dcd0; background: #faf8f4; display: flex; align-items: center; justify-content: center; }
.merci-check svg { width: 20px; height: 20px; stroke: #792f3c; stroke-width: 2.2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
.merci-head h1 { font-size: 1.32rem; margin: 0 0 10px; line-height: 1.7; }
.merci-lead { color: #6b6458; font-size: .95rem; line-height: 2; margin: 0 auto; max-width: 480px; }
.merci-ref { color: #a39a8c; font-size: .84rem; margin: 12px 0 0; }

/* ---- Tableau récapitulatif (2 colonnes, séparateurs fins) ---- */
.merci-summary { margin: 26px 0 0; }
.merci-summary table { width: 100%; border-collapse: collapse; font-size: .93rem; }
.merci-summary th { text-align: right; font-weight: 600; font-size: .85rem; color: #8a8378; padding: 0 0 10px; border-bottom: 1px solid #f0f0f0; }
.merci-summary th:last-child { text-align: left; }
.merci-summary td { padding: 14px 0; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.merci-item { display: flex; align-items: center; gap: 12px; }
.merci-item img { width: 58px; height: 72px; object-fit: cover; border-radius: 8px; flex: 0 0 auto; }
.merci-item-name { font-weight: 600; font-size: .92rem; line-height: 1.6; margin: 0; }
.merci-item-meta { color: #8a8378; font-size: .82rem; margin: 3px 0 0; line-height: 1.7; }
.merci-price { text-align: left; white-space: nowrap; font-weight: 600; }
.merci-price small { display: block; color: #a39a8c; font-size: .78rem; font-weight: 400; margin-top: 2px; }
.merci-total-row td { border-bottom: 0; padding-top: 16px; }
.merci-total-row .merci-price { font-size: 1.02rem; font-weight: 700; }
.merci-pay-row td { border-bottom: 0; padding-top: 4px; color: #6b6458; font-size: .88rem; }
.merci-pay-row td:first-child { color: #8a8378; font-size: .84rem; }

/* ---- Informations client : grille compacte 2 colonnes, sans boîtes ---- */
.merci-client { margin: 8px 0 0; padding: 18px 0 0; border-top: 1px solid #f0f0f0; display: grid; grid-template-columns: 1fr 1fr; gap: 14px 28px; }
.merci-client > div > dt { color: #8a8378; font-size: .8rem; font-weight: 400; margin: 0 0 3px; }
.merci-client > div > dd { margin: 0; font-size: .92rem; line-height: 1.7; }
.merci-client .merci-wide { grid-column: 1 / -1; }
.merci-wa-line { margin: 18px 0 0; text-align: center; }
.merci-wa { color: #792f3c; font-size: .9rem; font-weight: 600; text-decoration: none; border-bottom: 1px solid #e3c8cc; padding-bottom: 2px; }
.merci-wa:hover { border-bottom-color: #792f3c; }

/* ---- Code promo : présentation fine, sans encadré lourd ---- */
.merci-promo { margin: 26px 0 0; padding: 18px 16px; background: #faf8f4; border-radius: 12px; text-align: center; }
.merci-promo-eyebrow { color: #8a8378; font-size: .84rem; font-weight: 400; margin: 0 0 8px; }
.merci-promo-code { display: inline-block; font-family: 'DM Sans', 'Cairo', Arial, sans-serif; font-size: 1.12rem; font-weight: 700; color: #792f3c; letter-spacing: .5px; word-break: break-all; }
.merci-promo-desc { color: #8a8378; font-size: .82rem; margin: 6px 0 0; line-height: 1.8; }

/* ---- Section upsell : SANS cadres, visuels libres sur fond neutre ---- */
.merci-upsell { margin: 40px 0 0; }
.merci-upsell-title { text-align: center; font-size: 1.05rem; font-weight: 600; color: #2f2a24; margin: 0 0 20px; }
.merci-upsell-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px 18px; }
.merci-upsell-item { display: flex; flex-direction: column; text-decoration: none; color: inherit; }
.merci-upsell-item img { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; border-radius: 10px; }
.merci-upsell-name { font-weight: 400; font-size: .88rem; line-height: 1.7; margin: 10px 0 0; color: #2f2a24; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.merci-upsell-price { color: #792f3c; font-size: .9rem; font-weight: 600; margin: 4px 0 10px; }
.merci-upsell-btn { align-self: flex-start; border: 1px solid #2f2a24; border-radius: 999px; padding: 6px 18px; font-size: .82rem; color: #2f2a24; transition: background .15s ease, color .15s ease; }
.merci-upsell-item:hover .merci-upsell-btn { background: #2f2a24; color: #fff; }
.merci-upsell-item:hover img { opacity: .94; }

/* ---- Version générique (sans clé valide) ---- */
.merci-generic { text-align: center; }
.merci-generic .merci-lead { margin-bottom: 6px; }
.merci-generic .merci-wa-line { margin-top: 20px; }

@media (max-width: 520px) {
  .merci-page { padding: 26px 14px 54px; }
  .merci-head h1 { font-size: 1.18rem; }
  .merci-upsell-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px 12px; }
  .merci-client { gap: 12px 18px; }
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
    $abaya_phone = $abaya_order->get_billing_phone();
    $abaya_addr  = trim((string) $abaya_order->get_billing_address_1());
    $abaya_city  = $abaya_order->get_billing_city();

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
    $abaya_promo_code   = $abaya_promo_prenom . '-' . $abaya_promo_ville . '-' . $abaya_promo_num;

    /* Upsell (mandat : offres complémentaires SANS cadres) : toutes les offres
     * du registre SAUF celle de la commande. */
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
<?php if ($abaya_order) : ?>
  <header class="merci-head" aria-labelledby="merci-title">
    <span class="merci-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4.5 12.5l5 5 10-11"/></svg></span>
    <h1 id="merci-title"><?php echo $abaya_first ? 'شكراً لكِ ' . esc_html($abaya_first) . '! تم استلام طلبكِ بنجاح' : 'شكراً لكِ! تم استلام طلبكِ بنجاح'; ?></h1>
    <p class="merci-lead">سنتصل بكِ هاتفياً في أقرب وقت لتأكيد طلبكِ قبل الشحن. الدفع عند الاستلام، ويحق لكِ معاينة العباية قبل الدفع.</p>
    <p class="merci-ref">رقم الطلب: <strong>#<?php echo esc_html($abaya_order->get_order_number()); ?></strong></p>
  </header>

  <?php /* ---- Tableau récapitulatif à deux colonnes (Produit | Prix) ---- */ ?>
  <section class="merci-summary" aria-label="ملخص الطلب">
    <table>
      <thead>
        <tr><th scope="col">المنتج</th><th scope="col">السعر</th></tr>
      </thead>
      <tbody>
<?php foreach ($abaya_rows as $abaya_row) :
        $abaya_qty        = max(1, $abaya_row['qty']);
        $abaya_unit       = $abaya_row['total'] / $abaya_qty;
        $abaya_line_text  = ($abaya_row['total'] == floor($abaya_row['total'])) ? number_format($abaya_row['total'], 0) : number_format($abaya_row['total'], 2);
        $abaya_unit_text  = ($abaya_unit == floor($abaya_unit)) ? number_format($abaya_unit, 0) : number_format($abaya_unit, 2);
        $abaya_row_name   = $abaya_row['name'] !== '' ? $abaya_row['name'] : (string) $abaya_offer['name'];
?>
        <tr>
          <td>
            <div class="merci-item">
<?php if ($abaya_color_img) : ?>
              <img src="<?php echo esc_url($abaya_color_img); ?>" alt="العباية باللون <?php echo esc_attr($abaya_color); ?>" width="58" height="72" loading="lazy" decoding="async" />
<?php endif; ?>
              <div>
                <p class="merci-item-name"><?php echo esc_html($abaya_row_name); ?> × <?php echo esc_html($abaya_qty); ?></p>
                <p class="merci-item-meta">اللون: <?php echo esc_html($abaya_color !== '' ? $abaya_color : '—'); ?> · المقاس: <?php echo esc_html($abaya_size !== '' ? $abaya_size : '—'); ?></p>
              </div>
            </div>
          </td>
          <td class="merci-price"><?php echo esc_html($abaya_line_text); ?> درهم<?php if ($abaya_qty > 1) : ?><small><?php echo esc_html($abaya_unit_text); ?> درهم / القطعة</small><?php endif; ?></td>
        </tr>
<?php endforeach; ?>
        <tr class="merci-total-row">
          <td>المجموع</td>
          <td class="merci-price"><?php echo esc_html($abaya_total_text); ?> درهم</td>
        </tr>
        <tr class="merci-pay-row">
          <td>طريقة الدفع</td>
          <td class="merci-price" style="font-weight:400;">الدفع عند الاستلام</td>
        </tr>
      </tbody>
    </table>
  </section>

  <?php /* ---- Informations client : grille compacte à 2 colonnes, sans boîtes ---- */ ?>
  <dl class="merci-client">
    <div><dt>الهاتف</dt><dd dir="ltr"><?php echo esc_html($abaya_phone); ?></dd></div>
    <div><dt>المدينة</dt><dd><?php echo esc_html($abaya_city !== '' ? $abaya_city : '—'); ?></dd></div>
    <div class="merci-wide"><dt>العنوان</dt><dd><?php echo esc_html($abaya_addr !== '' ? $abaya_addr : '—'); ?></dd></div>
    <div><dt>طريقة الدفع</dt><dd>الدفع عند الاستلام</dd></div>
    <div><dt>الحالة</dt><dd>قيد التأكيد الهاتفي</dd></div>
  </dl>
  <p class="merci-wa-line"><a class="merci-wa" href="<?php echo esc_url($abaya_wa_link); ?>" target="_blank" rel="noopener">متابعة الطلب عبر الواتساب</a></p>

  <?php /* ---- Code promo : présentation fine et épurée ---- */ ?>
  <div class="merci-promo" aria-labelledby="merci-promo-title">
    <p class="merci-promo-eyebrow" id="merci-promo-title">كود خصم خاص بكِ للطلب القادم</p>
    <code class="merci-promo-code" dir="ltr"><?php echo esc_html($abaya_promo_code); ?></code>
    <p class="merci-promo-desc">خصم 20 درهم على كل عباية في طلبك القادم، أو لأي شخص يشتري باستخدام كودك الشخصي.</p>
  </div>

<?php /* ---- Upsell (mandat : offres complémentaires SANS cadres — visuels libres,
 *       texte fluide, bouton d'achat direct discret) ---- */ ?>
<?php if (!empty($abaya_cross_offers)) : ?>
  <section class="merci-upsell" aria-labelledby="merci-upsell-title">
    <h2 id="merci-upsell-title">اكتشفي عروضاً أخرى قد تناسبك</h2>
    <div class="merci-upsell-grid">
<?php foreach ($abaya_cross_offers as $abaya_cross_id => $abaya_cross_entry) : ?>
      <a class="merci-upsell-item" href="<?php echo esc_url(home_url('/' . $abaya_cross_id . '/#order-form-section')); ?>">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/' . $abaya_cross_entry['images']['lcp']); ?>" alt="<?php echo esc_attr($abaya_cross_entry['texts']['headline']); ?>" width="240" height="320" loading="lazy" decoding="async" />
        <strong class="merci-upsell-name"><?php echo esc_html($abaya_cross_entry['texts']['headline']); ?></strong>
        <span class="merci-upsell-price">ابتداءً من <?php echo esc_html(number_format_i18n($abaya_cross_entry['bundles'][1])); ?> درهم</span>
        <span class="merci-upsell-btn">اطلبي الآن</span>
      </a>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
<?php else : ?>
  <?php /* Version générique : sans clé valide (ou commande > 48 h), aucune donnée personnelle n'est affichée.
         Le conteneur mock reste disponible pour la prévisualisation locale (mock-api.js). */ ?>
  <div id="mock-thanks-container" hidden></div>
  <section class="merci-generic" data-generic-thanks aria-labelledby="merci-title-generic">
    <header class="merci-head">
      <span class="merci-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4.5 12.5l5 5 10-11"/></svg></span>
      <h1 id="merci-title-generic">شكراً لكِ! تم استلام طلبكِ</h1>
      <p class="merci-lead">سيتصل بك فريقنا هاتفياً في أقرب وقت لتأكيد المقاس واللون والكمية وعنوان التسليم.</p>
      <p class="merci-lead">الدفع عند الاستلام، ويحق لك معاينة العباية قبل الدفع.</p>
    </header>
    <p class="merci-wa-line"><a class="merci-wa" href="https://wa.me/<?php echo esc_attr($abaya_wa_number); ?>" target="_blank" rel="noopener">تواصلي معنا عبر الواتساب</a></p>
  </section>
<?php endif; ?>

<?php /* ---- Pop-up d'évaluation « 1-clic » (mandat : perfectionnement UX) ---- --
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
