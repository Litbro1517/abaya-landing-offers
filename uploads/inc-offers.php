<?php
if (!defined('ABSPATH')) exit;

/** Configuration éditoriale et commerciale isolée de chaque landing. */
function abaya_offers() {
    $default_product = (int) (defined('LANDING_PRODUCT_ID') ? LANDING_PRODUCT_ID : 15);
    return [
        'produit-1' => [
            'id' => 'produit-1',
            'name' => 'Produit 1',
            'product_id' => (int) (defined('LANDING_PRODUCT_ID_1') ? LANDING_PRODUCT_ID_1 : $default_product),
            'headline' => 'عباية خريفية من توب لولان – أناقة وانسيابية',
            'slogan' => 'اختيار يومي أنيق',
            'intro' => 'عباية خريفية أنيقة وانسيابية، بتوب مميز وخياطة متقنة لراحة يومية.',
            'description' => 'عباية لولان أنيقة وانسيابية للاستخدام اليومي.',
        ],
        'produit-2' => [
            'id' => 'produit-2',
            'name' => 'Produit 2',
            'product_id' => (int) (defined('LANDING_PRODUCT_ID_2') ? LANDING_PRODUCT_ID_2 : $default_product),
            'headline' => 'عباية لولان تجمع بين الراحة والأناقة',
            'slogan' => 'أناقة ترافق إطلالتك كل يوم',
            'intro' => 'اختاري عباية لولان بإطلالة انسيابية وتفاصيل مناسبة لراحتك اليومية.',
            'description' => 'عباية لولان بإطلالة انسيابية وتفاصيل أنيقة.',
        ],
        'produit-3' => [
            'id' => 'produit-3',
            'name' => 'Produit 3',
            'product_id' => (int) (defined('LANDING_PRODUCT_ID_3') ? LANDING_PRODUCT_ID_3 : $default_product),
            'headline' => 'عباية لولان بلمسة راقية تناسبك',
            'slogan' => 'إطلالة مميزة بأسلوبك الخاص',
            'intro' => 'عباية عملية بإطلالة راقية، اختاري اللون والمقاس المناسبين لكِ.',
            'description' => 'عباية عملية بإطلالة راقية وألوان متعددة.',
        ],
    ];
}

function abaya_current_offer_id() {
    if (!empty($GLOBALS['abaya_offer_id'])) return sanitize_key($GLOBALS['abaya_offer_id']);
    if (is_page()) {
        $slug = (string) get_post_field('post_name', get_queried_object_id());
        if (isset(abaya_offers()[$slug])) return $slug;
    }
    return 'produit-1';
}

function abaya_offer_config($offer_id = null) {
    $offers = abaya_offers();
    $offer_id = $offer_id ? sanitize_key($offer_id) : abaya_current_offer_id();
    return isset($offers[$offer_id]) ? $offers[$offer_id] : null;
}

function abaya_is_landing_page() {
    return is_front_page() || (is_page() && isset(abaya_offers()[(string) get_post_field('post_name', get_queried_object_id())]));
}
