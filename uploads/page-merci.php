<?php
/**
 * Page de remerciement dynamique /merci/ (mandat 4P — P1-1).
 *
 * WordPress associe automatiquement ce gabarit à la page de slug « merci »
 * (hiérarchie des templates : page-{slug}.php).
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
.merci-page { max-width: 720px; margin: 0 auto; padding: 28px 16px 56px; }
.merci-card { background: #fff; border: 1px solid #e7e1d6; border-radius: 16px; padding: 28px 20px 24px; text-align: center; }
.merci-check { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 50%; background: #792f3c; color: #fff; font-size: 30px; line-height: 56px; font-weight: 700; }
.merci-card h1 { font-size: 1.4rem; margin: 0 0 8px; }
.merci-lead { color: #5c554a; margin: 0 0 20px; }
.merci-recap { display: flex; gap: 16px; align-items: flex-start; text-align: right; background: #faf8f4; border: 1px solid #e7e1d6; border-radius: 12px; padding: 14px; }
.merci-photo { width: 110px; height: auto; border-radius: 10px; flex: 0 0 auto; }
.merci-details { margin: 0; flex: 1; display: grid; gap: 8px; align-content: start; }
.merci-details > div { display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px dashed #e7e1d6; padding-bottom: 6px; }
.merci-details > div:last-child { border-bottom: 0; padding-bottom: 0; }
.merci-details dt { color: #5c554a; font-weight: 400; }
.merci-details dd { margin: 0; font-weight: 700; }
.merci-steps { list-style: none; margin: 22px 0 0; padding: 0; text-align: right; display: grid; gap: 14px; }
.merci-steps li { display: flex; gap: 12px; align-items: flex-start; color: #8a8378; }
.merci-steps li.is-done { color: #2f2a24; }
.merci-steps .merci-step-dot { flex: 0 0 auto; width: 28px; height: 28px; border-radius: 50%; border: 2px solid currentColor; text-align: center; line-height: 24px; font-weight: 700; font-family: 'DM Sans', sans-serif; font-size: .85rem; }
.merci-steps li.is-done .merci-step-dot { background: #792f3c; border-color: #792f3c; color: #fff; }
.merci-steps h2 { font-size: 1rem; margin: 2px 0 2px; }
.merci-steps p { margin: 0; font-size: .9rem; color: #5c554a; }
.merci-wa { display: inline-block; margin: 22px auto 0; background: #25D366; color: #fff; font-weight: 700; padding: 12px 22px; border-radius: 999px; text-decoration: none; }
.merci-wa:hover { filter: brightness(.95); }
.merci-ref { color: #8a8378; font-size: .85rem; margin: 14px 0 0; }
.merci-home { display: inline-block; margin-top: 14px; color: #792f3c; font-weight: 700; }
.merci-promo { margin: 22px auto 0; padding: 16px 18px; background: #faf8f4; border: 1px dashed #c49b55; border-radius: 12px; text-align: center; }
.merci-promo-eyebrow { color: #5c554a; font-size: .85rem; font-weight: 700; margin: 0 0 8px; }
.merci-promo-code { display: inline-block; font-family: 'DM Sans', 'Cairo', sans-serif; font-size: 1.25rem; font-weight: 800; color: #792f3c; letter-spacing: .5px; padding: 8px 14px; background: #fff; border-radius: 8px; word-break: break-all; }
.merci-promo-desc { color: #5c554a; font-size: .88rem; margin: 8px 0 0; line-height: 1.6; }
.merci-review { margin-top: 22px; padding: 20px; border: 1px solid #e7e1d6; border-right: 4px solid #c49b55; border-radius: 12px; background: #fffdf9; text-align: right; }
.merci-review h2, .merci-cross-sell h2 { margin: 0 0 8px; font-size: 1.1rem; }
.merci-review p { margin: 0 0 14px; color: #5c554a; font-size: .92rem; line-height: 1.7; }
.merci-review-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.merci-review label { display: grid; gap: 5px; color: #5c554a; font-size: .85rem; }
.merci-review input, .merci-review select, .merci-review textarea { width: 100%; border: 1px solid #ded8ce; border-radius: 8px; padding: 10px; background: #fff; font: inherit; }
.merci-review textarea { min-height: 94px; margin-top: 10px; }
.merci-review-submit { margin-top: 10px; border: 0; border-radius: 999px; background: #792f3c; color: white; padding: 10px 18px; font: inherit; font-weight: 700; cursor: pointer; }
.merci-review-submit:disabled { opacity: .65; cursor: wait; }
.merci-review-feedback { margin-top: 10px !important; }
.merci-review-feedback.is-error { color: #a32929; }
.merci-cross-sell { margin: 26px auto 0; max-width: 920px; }
.merci-cross-sell-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.merci-cross-sell-card { display: grid; grid-template-columns: 86px 1fr; gap: 12px; align-items: center; padding: 12px; border: 1px solid #e7e1d6; border-radius: 12px; background: white; color: inherit; text-decoration: none; }
.merci-cross-sell-card img { width: 86px; height: 96px; object-fit: cover; border-radius: 8px; }
.merci-cross-sell-card strong, .merci-cross-sell-card span { display: block; }
.merci-cross-sell-card span { margin-top: 4px; color: #792f3c; font-size: .88rem; font-weight: 700; }
@media (max-width: 560px) { .merci-review-grid, .merci-cross-sell-grid { grid-template-columns: 1fr; } }
@media (max-width: 480px) {
  .merci-recap { flex-direction: column; align-items: center; }
  .merci-photo { width: 140px; }
  .merci-details { width: 100%; }
}
</style>
<?php
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

    $abaya_sku = '';
    foreach ($abaya_order->get_items() as $abaya_item) {
        $abaya_sku = (string) $abaya_item->get_meta('SKU');
        if ($abaya_sku !== '') break;
    }

    $abaya_total       = (float) $abaya_order->get_total();
    $abaya_total_text  = ($abaya_total == floor($abaya_total)) ? number_format($abaya_total, 0) : number_format($abaya_total, 2);

    /* Visuel de la couleur choisie : chemin issu d'abaya_catalog() (source unique). */
    $abaya_offer_id  = (string) $abaya_order->get_meta('_abaya_offer_id');
    if (!abaya_offer_config($abaya_offer_id)) $abaya_offer_id = 'produit-1';
    $abaya_offer     = abaya_offer_config($abaya_offer_id);
    $abaya_cat        = abaya_catalog($abaya_offer_id);
    $abaya_color_img  = '';
    if (isset($abaya_cat['colors'][$abaya_color]['image'])) {
        $abaya_color_img = get_template_directory_uri() . '/' . $abaya_cat['colors'][$abaya_color]['image'];
    }

    $abaya_wa_text = rawurlencode('مرحباً، أود متابعة طلبي رقم #' . $abaya_order->get_order_number());
    $abaya_wa_link = 'https://wa.me/' . $abaya_wa_number . '?text=' . $abaya_wa_text;

    /* Code promo de premier achat (mandat 4P) : -20 DH par abaya sur la prochaine commande.
     * Format strict sans espace : {PRENOM}-{VILLE}-{NUMERO} ou {VILLE}-{NUMERO} si prénom absent. */
    $abaya_promo_prenom = preg_replace('/\s+/u', '', trim((string) $abaya_first));
    $abaya_promo_ville  = preg_replace('/\s+/u', '', trim((string) $abaya_city));
    $abaya_promo_num    = (string) $abaya_order->get_order_number();
    if ($abaya_promo_prenom !== '' && $abaya_promo_ville !== '') {
        $abaya_promo_code = $abaya_promo_prenom . '-' . $abaya_promo_ville . '-' . $abaya_promo_num;
    } elseif ($abaya_promo_ville !== '') {
        $abaya_promo_code = $abaya_promo_ville . '-' . $abaya_promo_num;
    } else {
        $abaya_promo_code = $abaya_promo_num; // repli minimal (ville absente — cas très rare)
    }
}

$abaya_mock_offers = [];
foreach (abaya_offers() as $mock_offer) {
    $abaya_mock_offers[] = ['id' => $mock_offer['id'], 'url' => home_url('/' . $mock_offer['id'] . '/'), 'headline' => $mock_offer['headline']];
}
add_action('wp_head', function () use ($abaya_mock_offers) {
    $mock_config = [
        'reviewEndpoint' => wp_make_link_relative(rest_url('landing/v1/submit-review')),
        'assetBase' => get_template_directory_uri() . '/uploads/offers',
        'offers' => $abaya_mock_offers,
    ];
    echo '<script>window.ABAYA_MOCK = ' . wp_json_encode($mock_config) . ';</script>';
    echo '<script type="module" src="' . esc_url(get_template_directory_uri() . '/merci-review.js?ver=' . abaya_asset_ver('merci-review.js')) . '"></script>';
}, 7);

get_header();
?>
<main class="merci-page">
  <div id="mock-thanks-container" hidden></div>
<?php if ($abaya_order) : ?>
  <section class="merci-card" aria-labelledby="merci-title">
    <div class="merci-check" aria-hidden="true">✓</div>
    <h1 id="merci-title"><?php echo $abaya_first ? 'شكراً لكِ ' . esc_html($abaya_first) . '! تم استلام طلبكِ بنجاح' : 'شكراً لكِ! تم استلام طلبكِ بنجاح'; ?></h1>
    <p class="merci-lead">سنتصل بكِ هاتفياً في أقرب وقت لتأكيد الطلب. الدفع عند الاستلام، ويحق لكِ معاينة العباية قبل الدفع.</p>

    <div class="merci-recap">
      <?php if ($abaya_color_img) : ?>
      <img class="merci-photo" src="<?php echo esc_url($abaya_color_img); ?>" alt="العباية باللون <?php echo esc_attr($abaya_color); ?>" width="220" height="293" loading="lazy" decoding="async" />
      <?php endif; ?>
      <dl class="merci-details">
        <div><dt>اللون</dt><dd><?php echo esc_html($abaya_color !== '' ? $abaya_color : '—'); ?></dd></div>
        <div><dt>المقاس</dt><dd><?php echo esc_html($abaya_size !== '' ? $abaya_size : '—'); ?></dd></div>
        <?php if ($abaya_sku !== '') : ?><div><dt>SKU</dt><dd dir="ltr"><?php echo esc_html($abaya_sku); ?></dd></div><?php endif; ?>
        <div><dt>المجموع</dt><dd><strong><?php echo esc_html($abaya_total_text); ?> درهم</strong> — الدفع عند الاستلام</dd></div>
        <div><dt>الهاتف</dt><dd dir="ltr"><?php echo esc_html($abaya_phone); ?></dd></div>
        <div><dt>العنوان</dt><dd><?php echo esc_html(trim($abaya_addr . ($abaya_city !== '' ? '، ' . $abaya_city : ''))); ?></dd></div>
      </dl>
    </div>

    <ol class="merci-steps">
      <li class="is-done"><span class="merci-step-dot" aria-hidden="true">✓</span><div><h2>تم استلام الطلب</h2><p>وصلنا طلبكِ بنجاح وهو الآن قيد المعالجة.</p></div></li>
      <li><span class="merci-step-dot" aria-hidden="true">2</span><div><h2>التأكيد الهاتفي</h2><p>سنتصل بكِ خلال 24 إلى 72 ساعة لتأكيد المقاس واللون وعنوان التسليم.</p></div></li>
      <li><span class="merci-step-dot" aria-hidden="true">3</span><div><h2>الشحن والدفع عند الاستلام</h2><p>التوصيل خلال 24 إلى 72 ساعة عموماً (طلب الخياطة من 5 إلى 6 أيام). تفحصين العباية قبل الدفع.</p></div></li>
    </ol>

    <div class="merci-promo" aria-labelledby="merci-promo-title">
      <p class="merci-promo-eyebrow" id="merci-promo-title">🎁 كود خصم خاص بكِ للطلب القادم</p>
      <code class="merci-promo-code" dir="ltr"><?php echo esc_html($abaya_promo_code); ?></code>
      <p class="merci-promo-desc">خصم 20 درهم على كل عباية في طلبك القادم، أو لأي شخص يشتري باستخدام كودك الشخصي.</p>
    </div>
    <section class="merci-review" aria-labelledby="merci-review-title">
      <h2 id="merci-review-title">رأيكِ يهمنا</h2>
      <p>يسعدنا أن تخبرينا عن تجربتكِ. تقييمكِ وملاحظاتكِ تساعدنا على تحسين خدمتنا وتقديم تجربة أفضل لكِ.</p>
      <form id="merci-review-form" data-offer-id="<?php echo esc_attr($abaya_offer_id); ?>">
        <div class="merci-review-grid">
          <label>الاسم<input name="reviewer_name" required minlength="2" maxlength="50" value="<?php echo esc_attr($abaya_first); ?>" /></label>
          <label>المدينة<input name="reviewer_city" required minlength="2" maxlength="50" value="<?php echo esc_attr($abaya_city); ?>" /></label>
          <label>تقييمكِ<select name="rating" required><option value="" selected disabled>اختاري عدد النجوم</option><option value="5">5 نجوم</option><option value="4">4 نجوم</option><option value="3">3 نجوم</option><option value="2">نجمتان</option><option value="1">نجمة واحدة</option></select></label>
        </div>
        <label>مشاركتكِ<textarea name="review" required minlength="3" maxlength="2000" placeholder="اكتبي لنا عن تجربتكِ"></textarea></label>
        <button class="merci-review-submit" type="submit">إرسال التقييم</button>
        <p class="merci-review-feedback" id="merci-review-feedback" role="status" hidden></p>
      </form>
    </section>
    <a class="merci-wa" href="<?php echo esc_url($abaya_wa_link); ?>" target="_blank" rel="noopener">متابعة الطلب عبر الواتساب</a>
    <p class="merci-ref">رقم الطلب: <strong>#<?php echo esc_html($abaya_order->get_order_number()); ?></strong></p>
    <a class="merci-home" href="<?php echo esc_url(home_url('/')); ?>">العودة إلى المتجر</a>
  </section>
<?php else : ?>
  <?php /* Version générique : sans clé valide (ou commande > 48 h), aucune donnée personnelle n'est affichée. */ ?>
  <section class="merci-card" data-generic-thanks aria-labelledby="merci-title-generic">
    <div class="merci-check" aria-hidden="true">✓</div>
    <h1 id="merci-title-generic">شكراً لكِ! تم استلام طلبكِ</h1>
    <p class="merci-lead">سيتصل بك فريقنا هاتفياً في أقرب وقت لتأكيد المقاس واللون والكمية وعنوان التسليم.</p>
    <p class="merci-lead">الدفع عند الاستلام، ويحق لك معاينة العباية قبل الدفع.</p>
    <a class="merci-wa" href="https://wa.me/<?php echo esc_attr($abaya_wa_number); ?>" target="_blank" rel="noopener">تواصلي معنا عبر الواتساب</a>
    <a class="merci-home" href="<?php echo esc_url(home_url('/')); ?>">العودة إلى المتجر</a>
  </section>
<?php endif; ?>
<?php if ($abaya_order) : ?>
  <section class="merci-cross-sell" aria-labelledby="merci-cross-sell-title">
    <h2 id="merci-cross-sell-title">اكتشفي عروضاً أخرى من تشكيلتنا</h2>
    <div class="merci-cross-sell-grid">
      <?php foreach (abaya_offers() as $offer_id => $cross_offer) : if ($offer_id === $abaya_offer_id) continue; $cross_catalog = abaya_catalog($offer_id); ?>
        <a class="merci-cross-sell-card" href="<?php echo esc_url(home_url('/' . $offer_id . '/')); ?>">
          <img src="<?php echo esc_url(get_template_directory_uri() . '/uploads/offers/' . $offer_id . '/beige.webp'); ?>" alt="<?php echo esc_attr($cross_offer['headline']); ?>" width="86" height="96" loading="lazy" decoding="async" />
          <div><strong><?php echo esc_html($cross_offer['headline']); ?></strong><span>ابتداءً من <?php echo esc_html(number_format_i18n($cross_catalog['bundles'][1])); ?> درهم · اكتشفي العرض</span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
</main>
<?php get_footer(); ?>
