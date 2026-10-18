<?php
/**
 * GABARIT UNIQUE DU LANDING (mandat multi-landing).
 * Alimenté intégralement par le registre abaya_offers() (inc-offers.php) :
 * l'offre vedette (racine) ET les landings secondaires /{slug}/ partagent
 * ce même gabarit — une offre = une entrée de données, pas un fichier PHP.
 *
 * Ordre des couleurs/tailles et grille tarifaire = abaya_catalog($offer_id)
 * (adaptateur vers le registre). Les textes/images viennent de $offer.
 */
$u     = esc_url(get_template_directory_uri());
$offer = abaya_offer_config();
$cat   = abaya_catalog($offer['id']);
$T     = $offer['texts'];
$I     = $offer['images'];

/* Variantes LP (mandat « feat/lp-variants-styling », correction v2.8 selon les
 * maquettes HTML validées) : UNE classe conditionnelle sur <main> pilote les
 * deux déclinaisons visuelles — la vedette (racine) porte la variante
 * PRINCIPALE (design d'origine validé « VF charte » : puces à fond vert très
 * léger #E8F0EE + cercle à contour émeraude + crochet émeraude rempli,
 * carte active dorée #C5A059 à halo doré, badge ✓ doré), les landings
 * secondaires (tunnel upsell ⑭) portent la variante INVERSÉE (puces à cercle
 * doré plein + crochet émeraude stroke 3, contours de cartes dorés, badge
 * « الأكثر طلباً » doré à texte contrasté). Chaque puce embarque les DEUX
 * rendus SVG (fill + stroke) et l'anneau ; offers.css masque l'un ou l'autre
 * par variante — zéro duplication de markup, zéro état JS. */
$abaya_lp_variant = abaya_is_front_offer($offer['id']) ? 'principale' : 'upsell';

get_header();
?>
<main class="lp-variant-<?= esc_attr($abaya_lp_variant) ?>">
      <section class="hero wrap" aria-labelledby="product-title">
        <div class="gallery">
          <div class="main-photo-wrap product-image-wrapper">
            <img id="main-photo" class="main-photo" src="<?php echo esc_url(abaya_lcp_image_src()); ?>" width="1170" height="1560" fetchpriority="high" decoding="async" alt="<?php echo esc_attr($I['lcp_alt']); ?>" />
            <span class="photo-tag"><?php echo esc_html($T['slogan']); ?></span>
            <?php /* v1.10 : overlay « Stock épuisé » — voile fin + badge vitré,
                   basculé par app.js quand la couleur sélectionnée porte le
                   drapeau out_of_stock du catalogue unique abaya_catalog(). */ ?>
            <div class="stock-overlay" id="stock-overlay" role="status" aria-hidden="true">
              <span class="stock-badge">غير متوفر حالياً في هذا اللون</span>
            </div>
            <button class="gallery-arrow gallery-prev" type="button" aria-label="الصورة السابقة">‹</button>
            <button class="gallery-arrow gallery-next" type="button" aria-label="الصورة التالية">›</button>
          </div>
          <div class="thumbnail-row" aria-label="صور العباية">
<?php /* Boucle vignettes du catalogue (P0-1). La vignette de la couleur LCP
       est is-current au chargement : le visuel Hero correspond à cette teinte. */
$lcp_color = isset($I['lcp_color']) ? $I['lcp_color'] : 'بني';
foreach ($cat['colors'] as $color_name => $color_data) :
    $is_lcp   = ($color_name === $lcp_color);
    $full_src = $is_lcp ? abaya_lcp_image_src() : $u . '/' . $color_data['image'];
    $color_alt  = 'العباية باللون ' . $color_data['label'];
    $color_aria = 'عرض اللون ' . $color_data['label'];
?>
            <button class="thumbnail<?= $is_lcp ? ' is-current' : '' ?>" type="button" data-color="<?= esc_attr($color_name) ?>" data-src="<?= esc_url($full_src) ?>" data-alt="<?= esc_attr($color_alt) ?>"<?= !empty($color_data['out_of_stock']) ? ' data-out-of-stock="1"' : '' ?> aria-label="<?= esc_attr($color_aria) ?>" aria-pressed="<?= $is_lcp ? 'true' : 'false' ?>">
              <img src="<?= $u ?>/<?= esc_attr($color_data['thumb']) ?>" width="151" height="170" loading="lazy" decoding="async" alt="" />
            </button>
<?php endforeach; ?>
          </div>
        </div>

        <div class="product-info">
          <p class="eyebrow"><span class="eyebrow-line"></span> ABAYA COLLECTION</p>
          <h1 id="product-title"><?php echo esc_html($T['headline']); ?></h1>
<?php /* Badge étoiles sous le H1 (mandat 4P LP « Ajustement Final ») : 5 étoiles
       SVG au format exact du composant de la section avis (#avis-clients
       .review-card-stars svg), garnies dynamiquement selon la moyenne calculée
       en base de données (avis « review » approuvés). Rendu sur la landing
       principale ET les 3 landings secondaires ; aucun texte ni formulaire
       de la section avis n'est dupliqué. */
abaya_render_rating_summary();
?>
          <p class="product-intro"><?php echo esc_html($T['intro']); ?></p>

          <div class="price-row">
            <strong id="current-price" class="price"><?= (int) $cat['bundles'][1] ?> <small>درهم</small></strong>
            <del id="old-price" class="old-price"><?php echo esc_html($T['old_price']); ?></del>
            <span class="discount-pill"><?= esc_html($cat['bundle_labels'][1]['saving']) ?></span>
          </div>

          <fieldset class="choice-group" data-attribute="color">
            <legend>اللون <span id="color-value" class="choice-value">غير محدد</span></legend>
<?php /* Mandat 4P : micro-texte d'erreur discret (style identique aux .field-error
       du formulaire), affiché au-dessus du bloc et nettoyé dès la sélection. */ ?>
            <small class="field-error choice-error" data-choice-error="color" role="alert" hidden></small>
            <div class="swatches">
<?php /* P0-3 : plus aucune couleur présélectionnée — aucune classe
       is-selected ni aria-pressed="true" au chargement. */
foreach ($cat['colors'] as $color_name => $color_data) : ?>
              <button class="swatch" type="button" data-color="<?= esc_attr($color_name) ?>"<?= !empty($color_data['out_of_stock']) ? ' data-out-of-stock="1"' : '' ?> aria-label="<?= esc_attr($color_name) ?>" title="<?= esc_attr($color_name) ?>" aria-pressed="false"><span style="--swatch-color:<?= esc_attr($color_data['hex']) ?>"></span></button>
<?php endforeach; ?>
            </div>
          </fieldset>

          <fieldset class="choice-group size-group" data-attribute="size">
            <legend>المقاس <span id="size-value" class="choice-value">غير محدد</span></legend>
            <small class="field-error choice-error" data-choice-error="size" role="alert" hidden></small>
            <div class="sizes" role="group" aria-label="اختيار المقاس">
<?php /* P0-3 : plus aucune taille présélectionnée. */
foreach ($cat['sizes'] as $size_name) : ?>
              <button type="button" class="size-option" data-size="<?= esc_attr($size_name) ?>"><?= esc_html($size_name) ?></button>
<?php endforeach; ?>
            </div>
            <button class="text-link size-guide-link" type="button" data-modal="size-guide-dialog" aria-haspopup="dialog" aria-controls="size-guide-dialog">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7.5 16.5 21l5-5L8 2.5l-5 5Z"/><path d="m12 7 2-2M15 10l2-2m-8 5 2-2m-5 5 2-2"/></svg>
              جدول المقاسات
              <span aria-hidden="true">⌄</span>
            </button>
          </fieldset>

          <section class="order-section hero-order" id="formulaire-commande" aria-labelledby="order-title">
            <div class="order-card" id="order-form-section">
              <div class="order-heading">
                <h2 id="order-title">معلومات الزبون</h2>
              </div>
              <div class="order-summary" aria-live="polite">
                <div class="summary-copy">
                  <span>اختيارك:</span>
                  <strong id="summary-choice">اختاري اللون والمقاس - قطعة واحدة</strong>
                </div>
                <strong id="summary-price" class="summary-price"><?= (int) $cat['bundles'][1] ?> درهم</strong>
              </div>
<?php /* Tunnel post-achat (mandat « fix/post-purchase-navigation-flow ») :
       landing secondaire déverrouillée depuis la Thank You Page : la cliente
       sait d'emblée que sa commande existante est complétée — aucun nouveau
       ticket créé, le récapitulatif /merci/ se met à jour au retour. */ ?>
<?php $abaya_upsell_ctx = function_exists('abaya_upsell_context') ? abaya_upsell_context() : false; ?>
<?php if ($abaya_upsell_ctx) : ?>
              <p class="upsell-order-hint">سيُضاف هذا المنتج إلى طلبكِ الحالي رقم <strong dir="ltr">#<?php echo esc_html($abaya_upsell_ctx['order']->get_order_number()); ?></strong> — لن يُنشأ طلب جديد</p>
<?php endif; ?>
              <form class="order-form-grid" id="order-form" novalidate>
                <label class="form-field">
                  <span>الاسم الكامل</span>
                  <input name="name" autocomplete="name" placeholder="الاسم الكامل" required />
                  <small class="field-error"></small>
                </label>
                <label class="form-field">
                  <span>الهاتف</span>
                  <input name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="الهاتف" required />
                  <small class="field-error"></small>
                </label>
                <label class="form-field">
                  <span>المدينة</span>
                  <input name="city" autocomplete="address-level2" placeholder="المدينة" required />
                  <small class="field-error"></small>
                </label>
                <label class="form-field">
                  <span>العنوان</span>
                  <input name="address" autocomplete="street-address" placeholder="العنوان" required />
                  <small class="field-error"></small>
                </label>
                <div class="hp-field" aria-hidden="true">
                  <label>Ne pas remplir <input type="text" name="extra_note" tabindex="-1" autocomplete="off" /></label>
                </div>
                <button class="primary-button form-submit" type="submit">اضغطي هنا للطلب</button>
              </form>
              <div class="form-result" id="form-result" role="status" hidden></div>
            </div>
          </section>
          <p class="secure-note"><span aria-hidden="true">✓</span> الدفع عند الاستلام · توصيل مجاني</p>
        </div>
      </section>

      <section class="bundles-section wrap" aria-labelledby="bundles-title">
        <div class="section-heading">
          <h2 id="bundles-title">عروض خاصة</h2>
        </div>
        <div class="bundle-grid" role="group" aria-label="اختاري العرض">
<?php /* P0-1 : prix, libellés et économies des offres générés depuis
       la grille autoritaire du registre — plus aucune duplication front/serveur.
       Variantes LP : le badge ✓ de sélection est un span CSS pur dont la
       visibilité est liée à .is-selected (bascule exhaustive par app.js) —
       la liaison dynamique carte active ⇄ badge est garantie sans aucun
       état JS additionnel, donc sans persistance erronée sur le 1er bloc.
       Le ruban « الأكثر طلباً » (carte 2, les deux maquettes validées)
       se décline par variante : vert à texte blanc (principale, maquette
       « VF charte ») / doré à texte contrasté (upsell) — structure
       identique, couleurs portées par offers.css. */
foreach ($cat['bundles'] as $bundle_qty => $bundle_price) :
    $bundle_is_first = ($bundle_qty === 1);
?>
          <button class="bundle-card offer-card<?= $bundle_is_first ? ' is-selected' : '' ?>" type="button" data-quantity="<?= (int) $bundle_qty ?>" aria-pressed="<?= $bundle_is_first ? 'true' : 'false' ?>">
            <span class="bundle-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span>
<?php if ($bundle_qty === 2) : ?><span class="bundle-flag">الأكثر طلباً</span><?php endif; ?>
            <span class="bundle-badge"><?= esc_html($cat['bundle_labels'][$bundle_qty]['label']) ?></span>
            <strong class="bundle-price"><?= (int) $bundle_price ?> <small>درهم</small></strong>
            <span class="bundle-saving"><?= esc_html($cat['bundle_labels'][$bundle_qty]['saving']) ?></span>
          </button>
<?php endforeach; ?>
        </div>
      </section>

      <section class="visual-section visual-section-colors wrap" aria-labelledby="visual-colors-title">
        <div class="visual-row image-left">
          <figure class="visual-media">
            <img src="<?= $u ?>/<?php echo esc_attr($I['colors']['src']); ?>" width="1170" height="1560" alt="<?php echo esc_attr($I['colors']['alt']); ?>" loading="lazy" decoding="async" />
          </figure>
          <div class="visual-copy color-copy">
            <h2 class="visual-title" id="visual-colors-title">لماذا هذه العباية هي الخيار المثالي؟</h2>
            <ul class="visual-check-list" aria-label="مميزات العباية">
<?php foreach ($T['colors_bullets'] as $bullet_index => $bullet_text) :
    /* Le nombre de couleurs est injecté dynamiquement depuis le registre (« 7 ألوان »). */
    $bullet_text = preg_replace('/\d+ ألوان/', count($cat['colors']) . ' ألوان', $bullet_text);
    $is_centered = ($bullet_index < 2) ? ' class="is-centered"' : '';
    if (strpos($bullet_text, '|') !== false) {
        list($strong_part, $rest_part) = explode('|', $bullet_text, 2);
?>
              <li<?= $is_centered ?>>
                <span class="checkmark-badge" aria-hidden="true"><span class="lp-bullet-ring"><svg class="lp-check-fill" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg></span><svg class="lp-check-stroke" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span>
                <span><strong><?php echo esc_html($strong_part); ?>:</strong> <?php echo esc_html($rest_part); ?></span>
              </li>
<?php } else { ?>
              <li<?= $is_centered ?>>
                <span class="checkmark-badge" aria-hidden="true"><span class="lp-bullet-ring"><svg class="lp-check-fill" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg></span><svg class="lp-check-stroke" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span>
                <span><?php echo esc_html($bullet_text); ?></span>
              </li>
<?php } endforeach; ?>
            </ul>
          </div>
        </div>
      </section>

      <section class="visual-section visual-section-specs wrap" aria-labelledby="product-specs-title">
        <div class="visual-row">
          <figure class="visual-media">
            <img src="<?= $u ?>/<?php echo esc_attr($I['specs']['src']); ?>" width="1170" height="1560" alt="<?php echo esc_attr($I['specs']['alt']); ?>" loading="lazy" decoding="async" />
          </figure>
          <div class="visual-copy">
            <h2 class="visual-title" id="product-specs-title"><?php echo esc_html($T['specs_title']); ?></h2>
            <ul class="product-spec-list visual-check-list">
<?php foreach ($T['specs_items'] as $spec_item) : ?>
              <li><span class="checkmark-badge" aria-hidden="true"><span class="lp-bullet-ring"><svg class="lp-check-fill" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg></span><svg class="lp-check-stroke" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></span><span><?php echo esc_html($spec_item); ?></span></li>
<?php endforeach; ?>
            </ul>
            <a class="primary-button description-cta" href="#formulaire-commande">اضغطي هنا للطلب</a>
          </div>
        </div>
      </section>

      <section class="visual-section visual-section-inspection wrap" aria-labelledby="inspection-title">
        <div class="visual-row image-left">
          <figure class="visual-media">
            <img src="<?= $u ?>/<?php echo esc_attr($I['inspection']['src']); ?>" width="1170" height="1560" alt="<?php echo esc_attr($I['inspection']['alt']); ?>" loading="lazy" decoding="async" />
          </figure>
          <div class="visual-copy">
            <h2 class="visual-title" id="inspection-title"><?php echo esc_html($T['inspection_title']); ?></h2>
            <p class="visual-lead"><?php echo esc_html($T['inspection_lead']); ?></p>
            <p class="inspection-note"><?php echo esc_html($T['inspection_note']); ?></p>
          </div>
        </div>
      </section>

      <section class="trust-strip" id="delivery" aria-label="مميزات التوصيل والاستبدال">
        <div class="trust-grid wrap">
<?php /* Icônes SVG = partie structurelle du gabarit ; textes = registre. */
$trust_icons = [
    '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M5 11.5 16 5l11 6.5v13L16 31 5 24.5v-13Z"/><path d="m5.5 11.5 10.5 6 10.5-6M16 18v12M11 8l11 6"/></svg>',
    '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M3 9h17v15H3zM20 14h5l4 5v5h-9z"/><circle cx="9" cy="25" r="3"/><circle cx="24" cy="25" r="3"/><path d="M6 13h8m-8 4h5"/></svg>',
    '<svg viewBox="0 0 32 32" aria-hidden="true"><path d="M26 10V4l-5 5a12 12 0 0 0-18 9m3 4v6l5-5a12 12 0 0 0 18-9"/><path d="M10 14h12m-12 4h8"/></svg>',
];
foreach ($T['trust'] as $trust_index => $trust_item) :
    $trust_id = ($trust_index === 0) ? ' id="payment"' : '';
?>
          <article class="trust-item">
            <span class="trust-icon"><?php echo $trust_icons[$trust_index]; ?></span>
            <div<?php echo $trust_id; ?>><h2><?php echo esc_html($trust_item[0]); ?></h2><p><?php echo esc_html($trust_item[1]); ?></p></div>
          </article>
<?php endforeach; ?>
        </div>
      </section>

      <section class="order-guide wrap" aria-labelledby="order-guide-title">
        <div class="section-heading">
          <h2 id="order-guide-title"><?php echo esc_html($T['guide_title']); ?></h2>
        </div>
        <ol class="order-steps">
<?php foreach ($T['guide_steps'] as $guide_index => $guide_step) : ?>
          <li>
            <span class="order-step-number">0<?= $guide_index + 1 ?></span>
            <h3><?php echo esc_html($guide_step[0]); ?></h3>
            <p><?php echo esc_html($guide_step[1]); ?></p>
          </li>
<?php endforeach; ?>
        </ol>
      </section>

      <section class="social-section wrap" id="avis-clients">
        <div class="section-heading">
          <h2>مراجعة الزبناء</h2>
        </div>
        <button class="review-button" type="button" data-modal="review-dialog" aria-haspopup="dialog" aria-controls="review-dialog">إضافة تقييمك</button>
        <p class="review-feedback" id="reviews-feedback" role="status" hidden></p>
        <div class="reviews-list-container" aria-live="polite">
<?php
/* Correctif C2 : rendu serveur des avis (persistance MySQL via wp_insert_comment,
 * type « review », statut « en attente » à la soumission). Plus aucune volatilité
 * au rafraîchissement.
 * Découplage étoiles/avis (mandat fix/rating-comments-decoupling) : la requête
 * cible les avis approuvés et abaya_render_review_card() impose l'étanchéité
 * finale — seuls les avis TEXTUELS validés par l'admin s'affichent ; les
 * évaluations d'étoiles seules (approuvées, sans texte) n'apparaissent jamais
 * comme cartes partielles/vides. Le nombre est porté à 50 pour que les
 * enregistrements filtrés ne réduisent pas la fenêtre des cartes visibles. */
$abaya_reviews_args   = ['status' => 'approve', 'type' => 'review', 'number' => 50];
$abaya_reviews_target = abaya_reviews_target_post_id();
if ($abaya_reviews_target) $abaya_reviews_args['post_id'] = $abaya_reviews_target;
foreach (get_comments($abaya_reviews_args) as $abaya_review) {
    abaya_render_review_card($abaya_review);
}
?>
        </div>
      </section>

      <section class="faq-section" id="faq">
        <div class="wrap faq-inner">
          <div class="section-heading">
            <h2>الأسئلة الشائعة:</h2>
          </div>
          <ul class="faq-list">
<?php foreach ($T['faq'] as $faq_item) : ?>
            <li>
              <p class="faq-question"><?php echo esc_html($faq_item[0]); ?></p>
              <p class="faq-answer"><?php echo esc_html($faq_item[1]); ?></p>
            </li>
<?php endforeach; ?>
          </ul>
        </div>
      </section>

      <section class="contact-section" id="contact-section">
        <h2><?php echo esc_html($T['contact_heading']); ?></h2>
        <p><a class="ltr-phone whatsapp-link" href="https://wa.me/212698738664" dir="ltr"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="#25D366"/><path fill="#fff" d="M16.7 14.4c-.26-.13-1.54-.76-1.78-.85-.24-.09-.41-.13-.58.13-.17.26-.67.85-.82 1.02-.15.17-.3.19-.56.06-.26-.13-1.1-.41-2.1-1.3-.78-.7-1.3-1.57-1.45-1.83-.15-.26-.02-.4.11-.53.12-.12.26-.3.39-.45.13-.15.17-.26.26-.43.09-.17.04-.32-.02-.45-.06-.13-.58-1.39-.8-1.9-.21-.5-.42-.43-.58-.44h-.5c-.17 0-.45.06-.69.32-.24.26-.9.88-.9 2.14s.92 2.48 1.05 2.65c.13.17 1.8 2.75 4.36 3.85.61.26 1.08.42 1.45.54.61.19 1.17.16 1.61.1.49-.07 1.54-.63 1.76-1.24.21-.61.21-1.13.15-1.24-.06-.11-.24-.17-.5-.3z"/></svg><span>+212 698-738664</span></a></p>
      </section>
    </main>

<dialog class="info-modal size-guide-modal" id="size-guide-dialog" aria-labelledby="measurements-title">
      <button class="modal-close" type="button" aria-label="إغلاق">×</button>
      <h2 id="measurements-title">جدول المقاسات</h2>
      <div class="size-table-wrap">
        <table>
          <thead><tr><th>المقاس</th><th>القامة / الطول</th><th>الوزن</th></tr></thead>
          <tbody>
            <tr><th>S</th><td>150–158 cm</td><td>50–60 kg</td></tr>
            <tr><th>M</th><td>158–164 cm</td><td>60–70 kg</td></tr>
            <tr><th>L</th><td>164–170 cm</td><td>70–80 kg</td></tr>
            <tr><th>XL</th><td>170–175 cm</td><td>80–90 kg</td></tr>
            <tr><th>XXL</th><td>175–180 cm</td><td>90–100 kg</td></tr>
          </tbody>
        </table>
      </div>
    </dialog>

    <dialog class="info-modal review-modal" id="review-dialog" aria-labelledby="review-title">
      <button class="modal-close" type="button" aria-label="إغلاق">×</button>
      <h2 id="review-title">إضافة تقييمك</h2>
      <form id="review-form">
        <div class="review-form-row">
          <label class="review-field">
            <span>الاسم</span>
            <input name="reviewer_name" type="text" autocomplete="name" placeholder="اسمك" required maxlength="50" />
          </label>
          <label class="review-field">
            <span>المدينة</span>
            <input name="reviewer_city" type="text" autocomplete="address-level2" placeholder="المدينة" required maxlength="50" />
          </label>
        </div>
        <label class="review-field">
          <span>تقييمك</span>
          <select id="review-rating" name="rating" required aria-label="عدد النجوم">
            <option value="5">★★★★★ — ممتاز</option>
            <option value="4">★★★★☆ — جيد جداً</option>
            <option value="3">★★★☆☆ — جيد</option>
            <option value="2">★★☆☆☆ — مقبول</option>
            <option value="1">★☆☆☆☆ — ضعيف</option>
          </select>
        </label>
        <label class="review-field">
          <span>رأيك في المنتج</span>
          <textarea id="review-comment" name="review" rows="4" placeholder="اكتبي تقييمك هنا" required></textarea>
        </label>
        <button class="primary-button review-submit" type="submit">إرسال التقييم</button>
      </form>
    </dialog>

<?php get_footer(); ?>
